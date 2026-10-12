<?php

namespace App\Console\Commands;

use App\Models\InsuranceSubscription;
use App\Models\InsuranceSubscriptionPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Daily insurance housekeeping:
 *  1. Pending/Partial premiums past their due date -> Overdue / penalty applied (active subscriptions only)
 *  2. Subscriptions whose end date has passed -> Expired
 *  3. Subscription payment_status -> Late when something is overdue, Current otherwise
 */
class InsuranceMaintain extends Command
{
    protected $signature = 'insurance:maintain';
    protected $description = 'Mark overdue premiums, expire finished subscriptions, refresh subscription payment status';

    public function handle(): int
    {
        try {
            InsuranceSubscriptionPayment::updateOverduePayments();
        } catch (\Throwable $e) {
            \Log::error('insurance:maintain overdue sweep failed', ['error' => $e->getMessage()]);
            $this->error('Overdue sweep failed: ' . $e->getMessage());
        }

        // Each subscription is handled on its own so one bad / orphaned legacy record cannot stop the rest.
        $expired = 0;
        $refreshed = 0;
        $failed = 0;

        InsuranceSubscription::whereIn('status', ['Active', 'Suspended'])->get()->each(function ($s) use (&$expired, &$refreshed, &$failed) {
            try {
                $updates = [];

                if ($s->status === 'Active' && $s->end_date && $s->end_date->lt(now()->startOfDay())) {
                    $updates = ['status' => 'Expired', 'coverage_status' => 'Terminated'];
                    $expired++;
                } else {
                    $hasOverdue = InsuranceSubscriptionPayment::where('insurance_subscription_id', $s->id)
                        ->where('payment_status', 'Overdue')->exists();
                    $target = $hasOverdue ? 'Late' : 'Current';
                    if ($s->payment_status !== 'Defaulted' && $s->payment_status !== $target) {
                        $updates = ['payment_status' => $target];
                        $refreshed++;
                    }
                }

                if ($updates) {
                    // Quiet write: this is housekeeping, it must not re-run enrolment validation.
                    DB::table('insurance_subscriptions')->where('id', $s->id)->update($updates + ['updated_at' => now()]);
                }
                $s->updateBalances();
            } catch (\Throwable $e) {
                $failed++;
                \Log::error('insurance:maintain failed for subscription ' . $s->id, ['error' => $e->getMessage()]);
            }
        });

        $this->info('[' . now()->toDateTimeString() . "] insurance:maintain expired: {$expired}, payment status refreshed: {$refreshed}, failed: {$failed}");
        return 0;
    }
}
