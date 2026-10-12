<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class InsuranceProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'coverage_amount',
        'premium_amount',
        'billing_frequency',
        'billing_day',
        'duration_months',
        'grace_period_days',
        'late_payment_penalty',
        'penalty_type',
        'min_age',
        'max_age',
        'requirements',
        'benefits',
        'status',
        'start_date',
        'end_date',
        'total_subscribers',
        'total_premiums_collected',
        'total_premiums_expected',
        'total_premiums_balance',
        'terms_and_conditions',
        'icon',
        'color',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'coverage_amount' => 'decimal:2',
        'premium_amount' => 'decimal:2',
        'late_payment_penalty' => 'decimal:2',
        'total_premiums_collected' => 'decimal:2',
        'total_premiums_expected' => 'decimal:2',
        'total_premiums_balance' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'requirements' => 'array',
        'benefits' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model = self::validate($model);
            $model->total_subscribers = 0;
            $model->total_premiums_collected = 0;
            $model->total_premiums_expected = 0;
            $model->total_premiums_balance = 0;
            return true;
        });

        static::updating(function ($model) {
            $model = self::validate($model);
            return true;
        });

        static::deleting(function ($model) {
            // Never wipe a program that has taken members' money - deactivate it instead
            $hasMoney = InsuranceSubscriptionPayment::where('insurance_program_id', $model->id)
                ->where('paid_amount', '>', 0)->exists();
            if ($hasMoney) {
                throw new \DomainException('This program has premiums paid against it and cannot be deleted. Set its status to Inactive instead.');
            }

            // Delete all subscriptions and their payments
            $subscriptions = InsuranceSubscription::where('insurance_program_id', $model->id)->get();
            foreach ($subscriptions as $subscription) {
                $subscription->delete();
            }
        });
    }

    public static function validate($model)
    {
        // Sensible defaults so a program can be created from just name, premium, frequency and duration
        if (empty($model->penalty_type)) {
            $model->penalty_type = 'Fixed';
        }
        // Same defaults as the table itself (age 18-70, 7 days grace)
        if ($model->min_age === null || $model->min_age === '') {
            $model->min_age = 18;
        }
        if ($model->max_age === null || $model->max_age === '') {
            $model->max_age = 70;
        }
        if ($model->grace_period_days === null || $model->grace_period_days === '') {
            $model->grace_period_days = 7;
        }
        if (empty($model->status)) {
            $model->status = 'Active';
        }
        if (empty($model->billing_day)) {
            $model->billing_day = 1;
        }

        // Validate amounts
        $model->coverage_amount = abs((float)$model->coverage_amount);
        $model->premium_amount = abs((float)$model->premium_amount);
        $model->late_payment_penalty = abs((float)$model->late_payment_penalty);
        $model->grace_period_days = abs((int)$model->grace_period_days);
        $model->duration_months = abs((int)$model->duration_months);
        $model->billing_day = abs((int)$model->billing_day);

        // Validate billing frequency
        if (!in_array($model->billing_frequency, ['Weekly', 'Monthly', 'Quarterly', 'Annually'])) {
            throw new \Exception("Invalid billing frequency. Must be Weekly, Monthly, Quarterly, or Annually.", 1);
        }

        // Validate billing day
        if ($model->billing_frequency == 'Weekly') {
            if ($model->billing_day < 1 || $model->billing_day > 7) {
                throw new \Exception("Billing day for weekly frequency must be between 1-7 (day of week).", 1);
            }
        } elseif ($model->billing_frequency == 'Monthly') {
            if ($model->billing_day < 1 || $model->billing_day > 31) {
                throw new \Exception("Billing day for monthly frequency must be between 1-31 (day of month).", 1);
            }
        }

        // Validate status
        if (!in_array($model->status, ['Active', 'Inactive', 'Suspended'])) {
            throw new \Exception("Invalid status. Must be Active, Inactive, or Suspended.", 1);
        }

        // Validate penalty type
        if (!in_array($model->penalty_type, ['Fixed', 'Percentage'])) {
            throw new \Exception("Invalid penalty type. Must be Fixed or Percentage.", 1);
        }

        // Validate dates if provided
        if ($model->start_date && $model->end_date) {
            $start_date = Carbon::parse($model->start_date);
            $end_date = Carbon::parse($model->end_date);

            if ($start_date->gt($end_date)) {
                throw new \Exception("Program start date cannot be greater than end date.", 1);
            }
        }

        // Validate ages
        if ($model->min_age < 0 || $model->min_age > 150) {
            throw new \Exception("Invalid minimum age.", 1);
        }
        if ($model->max_age < 0 || $model->max_age > 150) {
            throw new \Exception("Invalid maximum age.", 1);
        }
        if ($model->min_age > $model->max_age) {
            throw new \Exception("Minimum age cannot be greater than maximum age.", 1);
        }

        // Validate amounts
        if ($model->coverage_amount < 0) {
            throw new \Exception("Coverage amount cannot be negative.", 1);
        }
        if ($model->premium_amount <= 0) {
            throw new \Exception("Premium amount must be greater than zero.", 1);
        }
        if ($model->duration_months < 1) {
            throw new \Exception("Duration must be at least 1 month.", 1);
        }

        return $model;
    }

    /**
     * Update program statistics from subscriptions
     */
    public function updateStatistics()
    {
        return DB::transaction(function () {
            $program = self::lockForUpdate()->find($this->id);
            
            if (!$program) {
                return false;
            }

            // Count active subscriptions
            $program->total_subscribers = InsuranceSubscription::where('insurance_program_id', $program->id)
                ->whereIn('status', ['Active', 'Suspended'])
                ->count();

            // Calculate total expected premiums
            $program->total_premiums_expected = InsuranceSubscriptionPayment::where('insurance_program_id', $program->id)
                ->sum('amount');

            // Calculate total collected premiums
            $program->total_premiums_collected = InsuranceSubscriptionPayment::where('insurance_program_id', $program->id)
                ->where('payment_status', 'Paid')
                ->sum('paid_amount');

            // Calculate balance
            $program->total_premiums_balance = $program->total_premiums_expected - $program->total_premiums_collected;

            $program->save();
            
            return $program;
        });
    }

    /**
     * Get all subscriptions for this program
     */
    public function subscriptions()
    {
        return $this->hasMany(InsuranceSubscription::class, 'insurance_program_id');
    }

    /**
     * Get all payments for this program
     */
    public function payments()
    {
        return $this->hasMany(InsuranceSubscriptionPayment::class, 'insurance_program_id');
    }

    /**
     * Get active subscriptions
     */
    public function activeSubscriptions()
    {
        return $this->hasMany(InsuranceSubscription::class, 'insurance_program_id')
            ->where('status', 'Active');
    }

    /**
     * Check if program is currently available for enrollment
     */
    /**
     * Can members join right now, and if not, a sentence explaining why (shown in the app as-is).
     * @return array{open:bool,reason:?string}
     */
    public function enrollmentStatus(): array
    {
        if ($this->status != 'Active') {
            return ['open' => false, 'reason' => 'This plan is not accepting new members at the moment.'];
        }
        $now = Carbon::now();
        if ($this->start_date && $now->lt(Carbon::parse($this->start_date))) {
            return ['open' => false, 'reason' => 'Enrolment opens on ' . Carbon::parse($this->start_date)->format('j M Y') . '.'];
        }
        if ($this->end_date && $now->gt(Carbon::parse($this->end_date)->endOfDay())) {
            return ['open' => false, 'reason' => 'Enrolment for this plan closed on ' . Carbon::parse($this->end_date)->format('j M Y') . '. Please contact DTEHM.'];
        }
        return ['open' => true, 'reason' => null];
    }

    public function isAvailableForEnrollment()
    {
        if ($this->status != 'Active') {
            return false;
        }

        $now = Carbon::now();

        if ($this->start_date && $now->lt(Carbon::parse($this->start_date))) {
            return false;
        }

        if ($this->end_date && $now->gt(Carbon::parse($this->end_date))) {
            return false;
        }

        return true;
    }

    /**
     * Get formatted coverage amount
     */
    public function getFormattedCoverageAmountAttribute()
    {
        return 'UGX ' . number_format($this->coverage_amount, 0);
    }

    /**
     * Get formatted premium amount
     */
    public function getFormattedPremiumAmountAttribute()
    {
        return 'UGX ' . number_format($this->premium_amount, 0);
    }

    /**
     * Get billing frequency display text
     */
    public function getBillingFrequencyTextAttribute()
    {
        $frequencies = [
            'Weekly' => 'Per Week',
            'Monthly' => 'Per Month',
            'Quarterly' => 'Per Quarter',
            'Annually' => 'Per Year',
        ];
        
        return $frequencies[$this->billing_frequency] ?? $this->billing_frequency;
    }
}
