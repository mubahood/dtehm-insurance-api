<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUniversalPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('universal_payments', function (Blueprint $table) {
            $table->id();
            
            // Payment Identification
            $table->string('payment_reference')->unique(); // UNI-PAY-{TIMESTAMP}-{RANDOM}
            $table->string('payment_type'); // insurance_subscription, insurance_transaction, order, invoice, etc.
            $table->string('payment_category')->default('general'); // insurance, ecommerce, service, etc.
            
            // User Information
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            
            // Payment Items (JSON array of items being paid for)
            $table->json('payment_items'); // [{type: 'insurance_subscription_payment', id: 1, amount: 50000}]
            $table->integer('items_count')->default(1);
            
            // Financial Details
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('UGX');
            $table->string('description')->nullable();
            
            // Payment Gateway Information
            $table->string('payment_gateway')->default('pesapal'); // pesapal, stripe, manual, mpesa, airtel_money
            $table->string('payment_method')->nullable(); // mobile_money, visa_mastercard, bank_transfer
            $table->string('payment_account')->nullable(); // phone number, card last 4 digits, etc.
            
            // Payment Status
            $table->string('status')->default('PENDING'); // PENDING, PROCESSING, COMPLETED, FAILED, CANCELLED, REFUNDED
            $table->string('payment_status_code')->nullable(); // Gateway specific status code
            $table->string('status_message')->nullable();
            
            // Pesapal Integration
            $table->string('pesapal_order_tracking_id')->nullable()->unique();
            $table->string('pesapal_merchant_reference')->nullable()->unique();
            $table->string('pesapal_redirect_url', 500)->nullable();
            $table->string('pesapal_callback_url', 500)->nullable();
            $table->string('pesapal_notification_id')->nullable();
            $table->integer('pesapal_status_code')->nullable();
            $table->text('pesapal_response')->nullable();
            
            // Payment Confirmation
            $table->string('confirmation_code')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            
            // Processing & Callbacks
            $table->boolean('items_processed')->default(false); // Have the paid items been marked as paid?
            $table->timestamp('items_processed_at')->nullable();
            $table->text('processing_notes')->nullable();
            $table->string('processed_by')->nullable(); // System, admin user ID, etc.
            
            // IPN & Webhooks
            $table->timestamp('last_ipn_at')->nullable();
            $table->integer('ipn_count')->default(0);
            $table->timestamp('last_status_check')->nullable();
            
            // Refund Information
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->timestamp('refunded_at')->nullable();
            $table->string('refund_reason')->nullable();
            
            // Additional Metadata
            $table->json('metadata')->nullable(); // Any additional data
            $table->string('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            
            // Error Handling
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            
            // Audit Trail
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->softDeletes();
            
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index('payment_type');
            $table->index('payment_category');
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_gateway');
            $table->index('pesapal_order_tracking_id');
            $table->index('pesapal_merchant_reference');
            $table->index('created_at');
            $table->index(['payment_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('universal_payments');
    }
}
