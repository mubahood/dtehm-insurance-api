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
        Schema::create('insurance_subscription_payments', function (Blueprint $table) {
            $table->id();
            
            // Relationships (NO CASCADE)
            $table->unsignedBigInteger('insurance_subscription_id');
            $table->unsignedBigInteger('insurance_user_id');
            $table->unsignedBigInteger('insurance_program_id');
            
            // Period Information
            $table->string('period_name')->comment('e.g., OCTOBER-2025, 2025-10-27');
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->string('year', 4);
            $table->string('month_number', 2)->nullable();
            $table->string('week_number', 2)->nullable();
            $table->enum('billing_frequency', ['Weekly', 'Monthly', 'Quarterly', 'Annually']);
            
            // Payment Details
            $table->date('due_date');
            $table->decimal('amount', 15, 2)->default(0)->comment('Expected payment amount');
            $table->decimal('paid_amount', 15, 2)->default(0)->comment('Actual amount paid');
            $table->decimal('penalty_amount', 15, 2)->default(0)->comment('Late payment penalty');
            $table->decimal('total_amount', 15, 2)->default(0)->comment('Amount + Penalty');
            
            // Payment Status
            $table->enum('payment_status', ['Pending', 'Paid', 'Partial', 'Overdue', 'Waived'])->default('Pending');
            $table->date('payment_date')->nullable();
            $table->date('overdue_date')->nullable()->comment('Date when payment became overdue');
            $table->integer('days_overdue')->default(0);
            
            // Payment Method & Reference
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('transaction_id')->nullable();
            
            // Coverage Impact
            $table->enum('coverage_affected', ['Yes', 'No'])->default('No')->comment('Did non-payment affect coverage');
            $table->date('coverage_suspended_date')->nullable();
            
            // Description & Notes
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            
            // Metadata
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('paid_by')->nullable()->comment('User who processed payment');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('insurance_subscription_id');
            $table->index('insurance_user_id');
            $table->index('insurance_program_id');
            $table->index('payment_status');
            $table->index('due_date');
            $table->index('payment_date');
            $table->index('period_name');
            $table->index('year');
            $table->index('month_number');
            $table->index('created_at');
            
            // Unique constraint to prevent duplicate billing periods
            $table->unique(['insurance_subscription_id', 'period_name'], 'unique_subscription_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_subscription_payments');
    }
};
