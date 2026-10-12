<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class InsuranceSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'insurance_program_id',
        'start_date',
        'end_date',
        'next_billing_date',
        'status',
        'payment_status',
        'coverage_status',
        'coverage_start_date',
        'coverage_end_date',
        'premium_amount',
        'total_expected',
        'total_paid',
        'total_balance',
        'payments_completed',
        'payments_pending',
        'notes',
        'beneficiaries',
        'policy_number',
        'prepared',
        'suspended_date',
        'cancelled_date',
        'suspension_reason',
        'cancellation_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'coverage_start_date' => 'date',
        'coverage_end_date' => 'date',
        'suspended_date' => 'date',
        'cancelled_date' => 'date',
        'premium_amount' => 'decimal:2',
        'total_expected' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_balance' => 'decimal:2',
        'beneficiaries' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Dates are never taken from the caller: coverage = the program's schedule from the start date
            // (e.g. start 12 Oct 2026, 12 monthly payments -> covered 12 Oct 2026 to 11 Oct 2027).
            $program = InsuranceProgram::find($model->insurance_program_id);
            if ($program && $model->start_date) {
                $schedule = self::buildSchedule(Carbon::parse($model->start_date), $program);
                $model->start_date = $schedule['start_date'];
                $model->end_date = $schedule['end_date'];
                $model->coverage_start_date = $schedule['start_date'];
                $model->coverage_end_date = $schedule['end_date'];
                $model->next_billing_date = $schedule['start_date'];
                $model->premium_amount = $program->premium_amount;
            }

            $model = self::validate($model);
            $model->prepared = 'No';
            $model->total_expected = 0;
            $model->total_paid = 0;
            $model->total_balance = 0;
            $model->payments_completed = 0;
            $model->payments_pending = 0;
            
            // Generate policy number if not set
            if (empty($model->policy_number)) {
                $model->policy_number = self::generatePolicyNumber();
            }
            
            return true;
        });

        static::updating(function ($model) {
            $model = self::validate($model);
            return true;
        });

        static::created(function ($model) {
            // Generate payment records
            self::prepare($model);
            
            // Update program statistics
            $program = InsuranceProgram::find($model->insurance_program_id);
            if ($program) {
                $program->updateStatistics();
            }
        });

        static::updated(function ($model) {
            // Update program statistics
            $program = InsuranceProgram::find($model->insurance_program_id);
            if ($program) {
                $program->updateStatistics();
            }
        });

        static::deleting(function ($model) {
            // Delete all payment records
            InsuranceSubscriptionPayment::where('insurance_subscription_id', $model->id)->delete();
            
            // Update program statistics
            $program = InsuranceProgram::find($model->insurance_program_id);
            if ($program) {
                $program->updateStatistics();
            }
        });
    }

    public static function validate($model)
    {
        // Validate dates
        $start_date = Carbon::parse($model->start_date);
        $end_date = Carbon::parse($model->end_date);

        if ($start_date->gt($end_date)) {
            throw new \Exception("Subscription start date cannot be greater than end date.", 1);
        }

        $days = $start_date->diffInDays($end_date);
        if ($days < 7) {
            throw new \Exception("Subscription period should be at least 7 days.", 1);
        }

        // Validate status
        if (!in_array($model->status, ['Active', 'Suspended', 'Cancelled', 'Expired', 'Pending'])) {
            throw new \Exception("Invalid status.", 1);
        }

        // Validate payment status
        if (!in_array($model->payment_status, ['Current', 'Late', 'Defaulted'])) {
            throw new \Exception("Invalid payment status.", 1);
        }

        // Validate coverage status
        if (!in_array($model->coverage_status, ['Active', 'Suspended', 'Terminated'])) {
            throw new \Exception("Invalid coverage status.", 1);
        }

        // Check if user already has an active subscription to THIS SPECIFIC PROGRAM
        if ($model->status == 'Active') {
            $existingActive = self::where('user_id', $model->user_id)
                ->where('insurance_program_id', $model->insurance_program_id)
                ->where('status', 'Active')
                ->where('id', '!=', $model->id ?? 0)
                ->first();
            
            if ($existingActive) {
                throw new \Exception("User already has an active subscription to this insurance program. You cannot enroll in the same program twice.", 1);
            }
        }

        // Verify program exists
        $program = InsuranceProgram::find($model->insurance_program_id);
        if (!$program) {
            throw new \Exception("Insurance program not found.", 1);
        }

        // Verify user exists
        $user = User::find($model->user_id);
        if (!$user) {
            throw new \Exception("User not found.", 1);
        }

        // Set premium amount from program if not set
        if (empty($model->premium_amount) || $model->premium_amount <= 0) {
            $model->premium_amount = $program->premium_amount;
        }

        return $model;
    }

    /**
     * Generate unique policy number
     */
    public static function generatePolicyNumber()
    {
        do {
            $policyNumber = 'POL-' . strtoupper(uniqid());
        } while (self::where('policy_number', $policyNumber)->exists());
        
        return $policyNumber;
    }

    /**
     * The payment schedule of a policy - THE single place where periods are calculated.
     *
     * Every period is counted from the start date (never from the previous period), and months never
     * overflow: a policy starting on 31 Jan bills 31 Jan, 28 Feb, 31 Mar ... so no month is ever skipped
     * or doubled. Each period ends the day before the next one starts and the cover ends the day before
     * the first un-billed period (12 months from 12 Oct 2026 = 12 Oct 2026 .. 11 Oct 2027).
     * The first premium is due on the start date; each later one on its period start.
     *
     * @return array{start_date:string,end_date:string,periods:array<int,array{start:string,end:string,due:string,name:string}>}
     */
    public static function buildSchedule(Carbon $start, InsuranceProgram $program): array
    {
        $start = $start->copy()->startOfDay();
        $months = max(1, (int) $program->duration_months);
        $coverEnd = $start->copy()->addMonthsNoOverflow($months)->subDay();
        $frequency = $program->billing_frequency ?: 'Monthly';

        $startOf = function (int $i) use ($start, $frequency) {
            switch ($frequency) {
                case 'Weekly':
                    return $start->copy()->addWeeks($i);
                case 'Quarterly':
                    return $start->copy()->addMonthsNoOverflow(3 * $i);
                case 'Annually':
                    return $start->copy()->addYearsNoOverflow($i);
                default:
                    return $start->copy()->addMonthsNoOverflow($i);
            }
        };

        $periods = [];
        for ($i = 0; $i < 600; $i++) {
            $pStart = $startOf($i);
            if ($pStart->gt($coverEnd)) {
                break;
            }
            $pEnd = $startOf($i + 1)->subDay();
            if ($pEnd->gt($coverEnd)) {
                $pEnd = $coverEnd->copy();
            }
            $periods[] = [
                'start' => $pStart->format('Y-m-d'),
                'end' => $pEnd->format('Y-m-d'),
                'due' => $pStart->format('Y-m-d'),
                'name' => self::generatePeriodName($pStart, $frequency),
            ];
        }

        return [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $coverEnd->format('Y-m-d'),
            'periods' => $periods,
        ];
    }

    /** Last day covered by a period that starts on $start (the day before the next period). */
    public static function periodEnd(Carbon $start, string $frequency): Carbon
    {
        switch ($frequency) {
            case 'Weekly':
                return $start->copy()->addWeek()->subDay();
            case 'Quarterly':
                return $start->copy()->addMonthsNoOverflow(3)->subDay();
            case 'Annually':
                return $start->copy()->addYearNoOverflow()->subDay();
            default:
                return $start->copy()->addMonthNoOverflow()->subDay();
        }
    }

    /**
     * Create the premium rows of a policy from its schedule (once).
     */
    public static function prepare($model)
    {
        if ($model->prepared == 'Yes') {
            return;
        }

        $program = InsuranceProgram::find($model->insurance_program_id);
        if (!$program) {
            throw new \Exception("Insurance program not found.", 1);
        }

        $schedule = self::buildSchedule(Carbon::parse($model->start_date), $program);
        if (count($schedule['periods']) > 520) {
            throw new \Exception('Subscription period too long.', 1);
        }

        foreach ($schedule['periods'] as $period) {
            // withTrashed: a row deleted earlier still owns its (subscription, period) unique key
            $exists = InsuranceSubscriptionPayment::withTrashed()
                ->where('insurance_subscription_id', $model->id)
                ->where('period_name', $period['name'])
                ->exists();
            if ($exists) {
                continue;
            }

            InsuranceSubscriptionPayment::create([
                'insurance_subscription_id' => $model->id,
                'user_id' => $model->user_id,
                'insurance_program_id' => $model->insurance_program_id,
                'period_name' => $period['name'],
                'period_start_date' => $period['start'],
                'period_end_date' => $period['end'],
                'year' => substr($period['start'], 0, 4),
                'month_number' => substr($period['start'], 5, 2),
                'week_number' => Carbon::parse($period['start'])->format('W'),
                'billing_frequency' => $program->billing_frequency,
                'due_date' => $period['due'],
                'amount' => $model->premium_amount,
                'paid_amount' => 0,
                'penalty_amount' => 0,
                'total_amount' => $model->premium_amount,
                'payment_status' => 'Pending',
                'description' => $program->name . ' - ' . $period['name'],
            ]);
        }

        DB::table('insurance_subscriptions')->where('id', $model->id)->update(['prepared' => 'Yes']);

        $model->updateBalances();
    }

    /**
     * Period name, unique within a policy (e.g. OCTOBER-2026, Q4-2026, 2026, 2026-10-12)
     */
    public static function generatePeriodName($date, $frequency)
    {
        $carbon = Carbon::parse($date);

        switch ($frequency) {
            case 'Weekly':
                return strtoupper($carbon->format('Y-m-d'));
            case 'Quarterly':
                return 'Q' . ceil($carbon->month / 3) . '-' . $carbon->format('Y');
            case 'Annually':
                return $carbon->format('Y');
            default:
                return strtoupper($carbon->format('F-Y'));
        }
    }

    /**
     * Record money received by the office (cash, mobile money ...) against this policy.
     * The amount is spread over the OLDEST unpaid months first (late fees included); the last month
     * touched may become Partial. Refuses more than the policy still owes. Row-locked and transactional.
     *
     * @return array{months: string[], amount: float}
     * @throws \DomainException with a message for the admin
     */
    public function recordOfficePayment(float $amount, string $method, ?string $reference, int $adminId): array
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \DomainException('Enter an amount greater than zero.');
        }
        if (!in_array($this->status, ['Active', 'Suspended'], true)) {
            throw new \DomainException('Payments can only be recorded on an active or suspended policy.');
        }

        return DB::transaction(function () use ($amount, $method, $reference, $adminId) {
            $open = InsuranceSubscriptionPayment::where('insurance_subscription_id', $this->id)
                ->whereIn('payment_status', self::OPEN_STATUSES)
                ->orderBy('due_date')->orderBy('id')
                ->lockForUpdate()->get();

            $owed = round($open->sum(fn ($r) => max(0, (float) $r->total_amount - (float) $r->paid_amount)), 2);
            if ($amount > $owed + 0.009) {
                throw new \DomainException('This policy only owes UGX ' . number_format($owed, 0) . '.');
            }

            $left = $amount;
            $months = [];
            foreach ($open as $row) {
                if ($left <= 0.009) {
                    break;
                }
                $due = round((float) $row->total_amount - (float) $row->paid_amount, 2);
                if ($due <= 0) {
                    continue;
                }
                $take = min($due, $left);
                $paid = round((float) $row->paid_amount + $take, 2);
                $row->update([
                    'paid_amount' => $paid,
                    'payment_status' => $paid + 0.009 >= (float) $row->total_amount ? 'Paid' : 'Partial',
                    'payment_date' => now()->toDateString(),
                    'payment_method' => $method,
                    'payment_reference' => $reference ?: $row->payment_reference,
                    'paid_by' => (string) $adminId,
                    'updated_by' => (string) $adminId,
                ]);
                $left = round($left - $take, 2);
                $months[] = $row->period_name;
            }

            $this->updateBalances();
            return ['months' => $months, 'amount' => $amount];
        });
    }

    /** Settled rows no longer need money; everything else (Pending / Overdue / Partial) still does. */
    public const OPEN_STATUSES = ['Pending', 'Overdue', 'Partial'];

    /** Unpaid premium rows of this policy, oldest first - money is always applied in this order. */
    public function openPaymentsOldestFirst()
    {
        return InsuranceSubscriptionPayment::where('insurance_subscription_id', $this->id)
            ->whereIn('payment_status', self::OPEN_STATUSES)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * Everything a member needs to know about a policy, calculated on the server so every screen
     * shows the same numbers.
     */
    public function summary(bool $withMonths = true): array
    {
        $rows = InsuranceSubscriptionPayment::where('insurance_subscription_id', $this->id)
            ->orderBy('due_date')->orderBy('id')->get();
        $counted = $rows->where('payment_status', '!=', 'Waived');
        $today = Carbon::today();
        $program = $this->insuranceProgram;

        $outstanding = function ($r) {
            return max(0, round((float) $r->total_amount - (float) $r->paid_amount, 2));
        };

        $dueNowRows = $counted->filter(function ($r) use ($today) {
            return in_array($r->payment_status, self::OPEN_STATUSES, true) && Carbon::parse($r->due_date)->lte($today);
        });
        $overdueRows = $counted->where('payment_status', 'Overdue');
        $openRows = $counted->filter(fn ($r) => in_array($r->payment_status, self::OPEN_STATUSES, true));
        $next = $openRows->first();

        $expected = round($counted->sum(fn ($r) => (float) $r->total_amount), 2);
        $paid = round($counted->sum(fn ($r) => (float) $r->paid_amount), 2);
        $first = $rows->first();
        $last = $rows->last();

        if ($this->status === 'Cancelled') {
            $state = 'cancelled';
            $label = 'Cancelled';
        } elseif ($this->status === 'Expired') {
            $state = 'expired';
            $label = 'Ended';
        } elseif ($this->status === 'Suspended') {
            $state = 'suspended';
            $label = 'Suspended';
        } elseif ($openRows->isEmpty()) {
            $state = 'completed';
            $label = 'Fully paid';
        } elseif ($overdueRows->isNotEmpty()) {
            $state = 'overdue';
            $label = $overdueRows->count() === 1 ? '1 month overdue' : $overdueRows->count() . ' months overdue';
        } elseif ($dueNowRows->isNotEmpty()) {
            $state = 'due';
            $label = 'Payment due';
        } else {
            $state = 'up_to_date';
            $label = 'Up to date';
        }

        $monthLabel = function ($r) {
            $d = Carbon::parse($r->period_start_date ?: $r->due_date);
            return $r->billing_frequency === 'Weekly' ? 'Week of ' . $d->format('j M Y') : $d->format('M Y');
        };

        $out = [
            'id' => $this->id,
            'policy_number' => $this->policy_number,
            'program_id' => $this->insurance_program_id,
            'program_name' => $program->name ?? 'Insurance',
            'coverage_amount' => (float) ($program->coverage_amount ?? 0),
            'billing_frequency' => $program->billing_frequency ?? 'Monthly',
            'premium_amount' => (float) $this->premium_amount,
            'status' => $this->status,
            'state' => $state,
            'state_label' => $label,
            'start_date' => $first ? Carbon::parse($first->period_start_date ?: $first->due_date)->format('Y-m-d') : optional($this->start_date)->format('Y-m-d'),
            // Last covered day = end of the last billed period (policies created before Oct 2026 stored one day more)
            'end_date' => $last
                ? self::periodEnd(Carbon::parse($last->period_start_date ?: $last->due_date), $last->billing_frequency ?: 'Monthly')->format('Y-m-d')
                : optional($this->end_date)->format('Y-m-d'),
            'first_month' => $first ? $monthLabel($first) : null,
            'last_month' => $last ? $monthLabel($last) : null,
            'months_total' => $counted->count(),
            'months_paid' => $counted->where('payment_status', 'Paid')->count(),
            'months_left' => $openRows->count(),
            'total_amount' => $expected,
            'paid_amount' => $paid,
            'balance' => max(0, round($expected - $paid, 2)),
            'due_now_amount' => round($dueNowRows->sum($outstanding), 2),
            'due_now_months' => $dueNowRows->count(),
            'overdue_months' => $overdueRows->count(),
            'late_fees' => round($dueNowRows->sum(fn ($r) => (float) $r->penalty_amount), 2),
            'next_due_date' => $next ? Carbon::parse($next->due_date)->format('Y-m-d') : null,
            'next_due_month' => $next ? $monthLabel($next) : null,
            'next_due_amount' => $next ? $outstanding($next) : 0,
            'can_pay' => in_array($this->status, ['Active', 'Suspended'], true) && $openRows->isNotEmpty(),
        ];

        if ($withMonths) {
            $out['months'] = $rows->map(function ($r) use ($today, $outstanding, $monthLabel) {
                $due = Carbon::parse($r->due_date);
                if ($r->payment_status === 'Paid') {
                    $st = 'paid';
                } elseif ($r->payment_status === 'Waived') {
                    $st = 'waived';
                } elseif ($r->payment_status === 'Overdue') {
                    $st = 'overdue';
                } elseif ($due->lte($today)) {
                    $st = 'due';
                } elseif ($r->payment_status === 'Partial') {
                    $st = 'partial';
                } else {
                    $st = 'upcoming';
                }
                return [
                    'id' => $r->id,
                    'label' => $monthLabel($r),
                    'period_name' => $r->period_name,
                    'due_date' => $due->format('Y-m-d'),
                    'amount' => (float) $r->amount,
                    'late_fee' => (float) $r->penalty_amount,
                    'total' => (float) $r->total_amount,
                    'paid' => (float) $r->paid_amount,
                    'outstanding' => $outstanding($r),
                    'status' => $st,
                    'paid_on' => $r->payment_date ? Carbon::parse($r->payment_date)->format('Y-m-d') : null,
                ];
            })->values()->all();
        }

        return $out;
    }

    /**
     * Update subscription balances
     */
    public function updateBalances()
    {
        return DB::transaction(function () {
            $subscription = self::lockForUpdate()->find($this->id);

            if (!$subscription) {
                return false;
            }

            $rows = InsuranceSubscriptionPayment::where('insurance_subscription_id', $subscription->id);

            // Waived rows (e.g. after cancellation) are no longer owed.
            $expected = (clone $rows)->where('payment_status', '!=', 'Waived')->sum('amount');
            // Partial payments count as money received too.
            $paid = (clone $rows)->where('payment_status', '!=', 'Waived')->sum('paid_amount');
            $balance = max(0, $expected - $paid);

            $completed = (clone $rows)->where('payment_status', 'Paid')->count();
            $pending = (clone $rows)->whereIn('payment_status', ['Pending', 'Partial', 'Overdue'])->count();
            $nextDue = (clone $rows)->whereIn('payment_status', ['Pending', 'Partial', 'Overdue'])->orderBy('due_date')->value('due_date');
            $hasOverdue = (clone $rows)->where('payment_status', 'Overdue')->exists();
            $paymentStatus = $subscription->payment_status === 'Defaulted' ? 'Defaulted' : ($hasOverdue ? 'Late' : 'Current');

            // Quiet update: recalculating totals must not re-run the model's validation hooks.
            DB::table('insurance_subscriptions')->where('id', $subscription->id)->update([
                'total_expected' => $expected,
                'total_paid' => $paid,
                'total_balance' => $balance,
                'payments_completed' => $completed,
                'payments_pending' => $pending,
                'next_billing_date' => $nextDue,
                'payment_status' => $paymentStatus,
                'updated_at' => now(),
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function insuranceProgram()
    {
        return $this->belongsTo(InsuranceProgram::class, 'insurance_program_id');
    }

    public function payments()
    {
        return $this->hasMany(InsuranceSubscriptionPayment::class, 'insurance_subscription_id');
    }

    public function pendingPayments()
    {
        return $this->hasMany(InsuranceSubscriptionPayment::class, 'insurance_subscription_id')
            ->whereIn('payment_status', ['Pending', 'Overdue']);
    }

    public function paidPayments()
    {
        return $this->hasMany(InsuranceSubscriptionPayment::class, 'insurance_subscription_id')
            ->where('payment_status', 'Paid');
    }
}
