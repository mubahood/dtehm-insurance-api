<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMultipleOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('multiple_orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            
            // User Information
            $table->unsignedInteger('user_id')->nullable()->comment('User who created this order');
            $table->string('sponsor_id')->nullable()->comment('Sponsor ID (can be user_id, dtehm_member_id, or username)');
            $table->unsignedInteger('sponsor_user_id')->nullable()->comment('Resolved sponsor user ID');
            $table->string('stockist_id')->nullable()->comment('Stockist ID');
            $table->unsignedInteger('stockist_user_id')->nullable()->comment('Resolved stockist user ID');
            
            // Order Items (JSON stored as LONGTEXT)
            $table->longText('items_json')->comment('JSON array of order items with product_id, qty, unit_price, subtotal, etc');
            
            // Order Totals
            $table->decimal('subtotal', 15, 2)->default(0)->comment('Sum of all item subtotals');
            $table->decimal('delivery_fee', 15, 2)->default(0)->comment('Delivery/shipping fee');
            $table->decimal('total_amount', 15, 2)->default(0)->comment('Final total amount (subtotal + delivery_fee)');
            $table->string('currency', 10)->default('UGX')->comment('Currency code');
            
            // Payment Status
            $table->enum('payment_status', [
                'PENDING', 
                'PROCESSING', 
                'COMPLETED', 
                'FAILED', 
                'CANCELLED',
                'REVERSED'
            ])->default('PENDING')->comment('Payment status');
            $table->timestamp('payment_completed_at')->nullable()->comment('When payment was completed');
            
            // Pesapal Integration Fields
            $table->string('pesapal_order_tracking_id')->nullable()->unique()->comment('Pesapal order tracking ID');
            $table->string('pesapal_merchant_reference')->nullable()->comment('Unique merchant reference for Pesapal');
            $table->string('pesapal_redirect_url')->nullable()->comment('Pesapal payment page URL');
            $table->string('pesapal_callback_url')->nullable()->comment('Callback URL after payment');
            $table->string('pesapal_notification_id')->nullable()->comment('Pesapal IPN notification ID');
            $table->string('pesapal_status')->nullable()->comment('Pesapal payment status');
            $table->string('pesapal_status_code')->nullable()->comment('Pesapal status code (0,1,2,3)');
            $table->string('pesapal_payment_method')->nullable()->comment('Payment method used (Mobile Money, Card, etc)');
            $table->string('pesapal_confirmation_code')->nullable()->comment('Pesapal confirmation/transaction code');
            $table->string('pesapal_payment_account')->nullable()->comment('Payment account/phone number used');
            $table->longText('pesapal_response')->nullable()->comment('Full Pesapal API response JSON');
            $table->timestamp('pesapal_last_check')->nullable()->comment('Last time status was checked');
            
            // Conversion Status
            $table->enum('conversion_status', [
                'PENDING', 
                'PROCESSING', 
                'COMPLETED', 
                'FAILED'
            ])->default('PENDING')->comment('Status of conversion to OrderedItems');
            $table->timestamp('converted_at')->nullable()->comment('When this was converted to OrderedItems');
            $table->longText('conversion_result')->nullable()->comment('JSON result of conversion process');
            $table->text('conversion_error')->nullable()->comment('Error message if conversion failed');
            
            // Additional Information
            $table->text('customer_notes')->nullable()->comment('Customer notes/comments');
            $table->string('delivery_method')->default('delivery')->comment('delivery or pickup');
            $table->text('delivery_address')->nullable()->comment('Delivery address details');
            $table->string('customer_phone')->nullable()->comment('Customer contact phone');
            $table->string('customer_email')->nullable()->comment('Customer email');
            
            // Metadata
            $table->string('ip_address', 45)->nullable()->comment('IP address of user');
            $table->text('user_agent')->nullable()->comment('User agent string');
            $table->enum('status', ['active', 'cancelled', 'expired'])->default('active')->comment('Overall order status');
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('sponsor_user_id');
            $table->index('stockist_user_id');
            $table->index('payment_status');
            $table->index('conversion_status');
            $table->index('pesapal_merchant_reference');
            $table->index('created_at');
            
            // Note: Foreign key constraints not added to maintain compatibility with existing database structure
            // Relationships are maintained through application logic
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('multiple_orders');
    }
}
