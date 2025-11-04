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
        Schema::create('insurance_subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Relationships (NO CASCADE - as per your requirement)
            $table->unsignedBigInteger('insurance_user_id');
            $table->unsignedBigInteger('insurance_program_id');
            
            // Subscription Period
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_billing_date')->nullable();
            
            // Status Management
            $table->enum('status', ['Active', 'Suspended', 'Cancelled', 'Expired', 'Pending'])->default('Pending');
            $table->enum('payment_status', ['Current', 'Late', 'Defaulted'])->default('Current');
            
            // Coverage Status
            $table->enum('coverage_status', ['Active', 'Suspended', 'Terminated'])->default('Active');
            $table->date('coverage_start_date')->nullable();
            $table->date('coverage_end_date')->nullable();
            
            // Payment Tracking
            $table->decimal('premium_amount', 15, 2)->default(0)->comment('Subscription premium per cycle');
            $table->decimal('total_expected', 15, 2)->default(0)->comment('Total expected premium payments');
            $table->decimal('total_paid', 15, 2)->default(0)->comment('Total amount paid');
            $table->decimal('total_balance', 15, 2)->default(0)->comment('Outstanding balance');
            $table->integer('payments_completed')->default(0);
            $table->integer('payments_pending')->default(0);
            
            // Subscription Details
            $table->text('notes')->nullable();
            $table->text('beneficiaries')->nullable()->comment('JSON array of beneficiaries');
            $table->string('policy_number')->nullable()->unique()->comment('Generated policy number');
            
            // Preparation Status
            $table->enum('prepared', ['Yes', 'No'])->default('No')->comment('Payment records generated');
            
            // Cancellation/Suspension Details
            $table->date('suspended_date')->nullable();
            $table->date('cancelled_date')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Metadata
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes (NO foreign key constraints)
            $table->index('insurance_user_id');
            $table->index('insurance_program_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('coverage_status');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('next_billing_date');
            $table->index('policy_number');
            $table->index('created_at');
            
            // Unique constraint: One active subscription per user
            // Note: Enforced in application logic, not database
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_subscriptions');
    }
};
