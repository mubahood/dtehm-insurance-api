<?php

namespace App\Console\Commands;

use App\Models\InsuranceProgram;
use App\Models\InsuranceSubscription;
use App\Models\InsuranceSubscriptionPayment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fixes policies whose money was recorded on the wrong months (e.g. the LAST months of the year marked
 * paid while the FIRST ones stay overdue) and reports months missing from a policy's schedule.
 *
 * Read-only unless --apply is given. Money is never created or removed: the same amounts, dates and
 * references are simply moved to the oldest months. A late fee is removed from a month only when the
 * money that now covers it was paid before that month became late.
 */
class InsuranceRepair extends Command
{
    protected $signature = 'insurance:repair
        {--apply : Write the changes (default is a dry run that only prints them)}
        {--restore-months : Also restore months that were deleted from an active policy}
        {--policy= : Only this policy id}';

    protected $description = 'Re-apply recorded premiums to the oldest months first and report/restore missing months';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'APPLYING changes' : 'DRY RUN - nothing will be written (use --apply)');

        $query = InsuranceSubscription::with('insuranceProgram', 'user')->whereIn('status', ['Active', 'Suspended']);
        if ($this->option('policy')) {
            $query->where('id', (int) $this->option('policy'));
        }

        $changed = 0;
        foreach ($query->get() as $policy) {
            $who = '#' . $policy->id . ' ' . ($policy->user->name ?? 'user ' . $policy->user_id);
            $changed += $this->missingMonths($policy, $who, $apply) ? 1 : 0;
            $changed += $this->reorder($policy, $who, $apply) ? 1 : 0;
            $changed += $this->coverEnd($policy, $who, $apply) ? 1 : 0;
        }

        $this->info($changed ? "{$changed} change(s) " . ($apply ? 'applied.' : 'would be made.') : 'All policies are consistent.');
        return 0;
    }

    private function missingMonths(InsuranceSubscription $policy, string $who, bool $apply): bool
    {
        $program = $policy->insuranceProgram;
        if (!$program) {
            return false;
        }
        $schedule = InsuranceSubscription::buildSchedule(Carbon::parse($policy->start_date), $program);
        $rows = InsuranceSubscriptionPayment::withTrashed()->where('insurance_subscription_id', $policy->id)->get()->keyBy('period_name');

        $deleted = [];
        $absent = [];
        foreach ($schedule['periods'] as $p) {
            $row = $rows->get($p['name']);
            if (!$row) {
                $absent[] = $p['name'];
            } elseif ($row->trashed()) {
                $deleted[] = $row;
            }
        }
        if (!$deleted && !$absent) {
            return false;
        }

        $names = array_merge(array_map(fn ($r) => $r->period_name, $deleted), $absent);
        $this->warn("Policy {$who}: " . count($names) . ' month(s) missing from the schedule: ' . implode(', ', $names));

        if (!$this->option('restore-months')) {
            $this->line('   (left as is - run with --restore-months to put them back)');
            return false;
        }
        if ($apply) {
            foreach ($deleted as $row) {
                $row->restore();
            }
            if ($absent) {
                DB::table('insurance_subscriptions')->where('id', $policy->id)->update(['prepared' => 'No']);
                InsuranceSubscription::prepare($policy->fresh());
            }
            $policy->updateBalances();
            $this->line('   restored.');
        }
        return true;
    }

    /** Policies created before Oct 2026 stored a cover end one day past the last month. */
    private function coverEnd(InsuranceSubscription $policy, string $who, bool $apply): bool
    {
        // withTrashed: a deleted month is still part of what the member bought - never shorten the cover
        $last = InsuranceSubscriptionPayment::withTrashed()->where('insurance_subscription_id', $policy->id)->orderByDesc('due_date')->first();
        if (!$last) {
            return false;
        }
        $end = InsuranceSubscription::periodEnd(Carbon::parse($last->period_start_date ?: $last->due_date), $last->billing_frequency ?: 'Monthly')->format('Y-m-d');
        $stored = optional($policy->end_date)->format('Y-m-d');
        if ($stored === $end && optional($last->period_end_date)->format('Y-m-d') === $end) {
            return false;
        }
        $this->line("Policy {$who}: cover end {$stored} -> {$end}");
        if ($apply) {
            DB::table('insurance_subscriptions')->where('id', $policy->id)->update(['end_date' => $end, 'coverage_end_date' => $end, 'updated_at' => now()]);
            DB::table('insurance_subscription_payments')->where('id', $last->id)->update(['period_end_date' => $end]);
            $policy->refresh();
        }
        return true;
    }

    private function reorder(InsuranceSubscription $policy, string $who, bool $apply): bool
    {
        $rows = InsuranceSubscriptionPayment::where('insurance_subscription_id', $policy->id)
            ->where('payment_status', '!=', 'Waived')
            ->orderBy('due_date')->orderBy('id')->get();

        // Money already recorded on this policy, kept with its own date and reference
        $money = $rows->filter(fn ($r) => (float) $r->paid_amount > 0)
            ->sortBy(fn ($r) => ($r->payment_date ? Carbon::parse($r->payment_date)->format('Ymd') : '99999999') . $r->due_date)
            ->map(fn ($r) => [
                'amount' => (float) $r->paid_amount,
                'date' => $r->payment_date ? Carbon::parse($r->payment_date) : null,
                'method' => $r->payment_method,
                'reference' => $r->payment_reference,
                'transaction_id' => $r->transaction_id,
                'paid_by' => $r->paid_by,
            ])->values()->all();

        if (!$money) {
            return false;
        }

        $grace = (int) optional($policy->insuranceProgram)->grace_period_days;
        $today = Carbon::today();
        $plan = [];
        $queue = $money;

        foreach ($rows as $row) {
            $penalty = (float) $row->penalty_amount;
            $fundedBy = $queue[0] ?? null;
            if ($fundedBy && $fundedBy['date'] && $fundedBy['date']->lte(Carbon::parse($row->due_date)->addDays($grace))) {
                $penalty = 0.0; // the money arrived before this month was late
            }
            $total = round((float) $row->amount + $penalty, 2);
            $paid = 0.0;
            $meta = null;
            while ($paid < $total - 0.009 && $queue) {
                $take = min($queue[0]['amount'], $total - $paid);
                $paid = round($paid + $take, 2);
                $meta = $meta ?: $queue[0];
                $queue[0]['amount'] = round($queue[0]['amount'] - $take, 2);
                if ($queue[0]['amount'] <= 0.009) {
                    array_shift($queue);
                }
            }

            $late = $today->gt(Carbon::parse($row->due_date)->addDays($grace));
            if ($paid >= $total - 0.009) {
                $status = 'Paid';
            } elseif ($paid > 0) {
                $status = 'Partial';
            } else {
                $status = $late ? 'Overdue' : 'Pending';
            }

            $plan[$row->id] = [
                'row' => $row,
                'paid_amount' => $paid,
                'penalty_amount' => $penalty,
                'total_amount' => $total,
                'payment_status' => $status,
                'meta' => $meta,
            ];
        }

        $diff = array_filter($plan, function ($p) {
            $r = $p['row'];
            // Only money movements count; plain overdue status updates are the daily job's work
            return abs((float) $r->paid_amount - $p['paid_amount']) > 0.009
                || abs((float) $r->penalty_amount - $p['penalty_amount']) > 0.009;
        });
        if (!$diff) {
            return false;
        }

        $from = $rows->filter(fn ($r) => (float) $r->paid_amount > 0)->pluck('period_name')->implode(', ');
        $to = collect($plan)->filter(fn ($p) => $p['paid_amount'] > 0)->map(fn ($p) => $p['row']->period_name)->implode(', ');
        $feesRemoved = collect($plan)->sum(fn ($p) => max(0, (float) $p['row']->penalty_amount - $p['penalty_amount']));
        $this->warn("Policy {$who}: UGX " . number_format(array_sum(array_column($money, 'amount')), 0) . " recorded on [{$from}] -> oldest months [{$to}]"
            . ($feesRemoved > 0 ? '; late fees removed: UGX ' . number_format($feesRemoved, 0) : ''));

        if (!$apply) {
            return true;
        }

        DB::transaction(function () use ($plan, $policy) {
            foreach ($plan as $id => $p) {
                $r = $p['row'];
                if (abs((float) $r->paid_amount - $p['paid_amount']) <= 0.009 && abs((float) $r->penalty_amount - $p['penalty_amount']) <= 0.009) {
                    continue; // untouched month
                }
                $m = $p['paid_amount'] > 0 ? $p['meta'] : null;
                DB::table('insurance_subscription_payments')->where('id', $id)->update([
                    'paid_amount' => $p['paid_amount'],
                    'penalty_amount' => $p['penalty_amount'],
                    'total_amount' => $p['total_amount'],
                    'payment_status' => $p['payment_status'],
                    'days_overdue' => $p['payment_status'] === 'Paid' ? 0 : $p['row']->days_overdue,
                    'payment_date' => $m && $m['date'] ? $m['date']->format('Y-m-d') : null,
                    'payment_method' => $m['method'] ?? null,
                    'payment_reference' => $m['reference'] ?? null,
                    'transaction_id' => $m['transaction_id'] ?? null,
                    'paid_by' => $m['paid_by'] ?? null,
                    'notes' => DB::raw("TRIM(BOTH ' | ' FROM CONCAT(COALESCE(notes,''), ' | Re-applied oldest-first by insurance:repair on " . now()->toDateString() . "'))"),
                    'updated_at' => now(),
                ]);
            }
            $policy->updateBalances();
        });
        $this->line('   fixed.');
        return true;
    }
}
