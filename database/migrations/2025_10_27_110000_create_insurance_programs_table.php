<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('insurance_programs', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('name');
            $table->text('description')->nullable();
            
            // Coverage Details
            $table->decimal('coverage_amount', 15, 2)->default(0)->comment('Maximum coverage/benefit amount');
            $table->decimal('premium_amount', 15, 2)->default(0)->comment('Amount to pay per billing cycle');
            
            // Billing Configuration
            $table->enum('billing_frequency', ['Weekly', 'Monthly', 'Quarterly', 'Annually'])->default('Monthly');
            $table->integer('billing_day')->default(1)->comment('Day of week (1-7) or month (1-31) for billing');
            $table->integer('duration_months')->default(12)->comment('Program duration in months');
            
            // Payment Rules
            $table->integer('grace_period_days')->default(7)->comment('Days allowed for late payment');
            $table->decimal('late_payment_penalty', 15, 2)->default(0)->comment('Penalty for late payment');
            $table->enum('penalty_type', ['Fixed', 'Percentage'])->default('Fixed');
            
            // Eligibility & Requirements
            $table->integer('min_age')->default(18);
            $table->integer('max_age')->default(70);
            $table->text('requirements')->nullable()->comment('JSON array of requirements');
            $table->text('benefits')->nullable()->comment('JSON array of benefits');
            
            // Program Status
            $table->enum('status', ['Active', 'Inactive', 'Suspended'])->default('Active');
            $table->date('start_date')->nullable()->comment('Program availability start date');
            $table->date('end_date')->nullable()->comment('Program availability end date');
            
            // Statistics
            $table->integer('total_subscribers')->default(0);
            $table->decimal('total_premiums_collected', 15, 2)->default(0);
            $table->decimal('total_premiums_expected', 15, 2)->default(0);
            $table->decimal('total_premiums_balance', 15, 2)->default(0);
            
            // Additional Details
            $table->text('terms_and_conditions')->nullable();
            $table->string('icon')->nullable()->comment('Icon identifier for UI');
            $table->string('color')->nullable()->comment('Color code for UI');
            
            // Metadata
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index('billing_frequency');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_programs');
    }
};
