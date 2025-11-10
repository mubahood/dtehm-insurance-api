<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembershipPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('membership_payments', function (Blueprint $table) {
            $table->id();
            
            // User Information
            $table->bigInteger('user_id')->nullable();
            
            // Payment Information
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 10, 2)->default(20000);
            $table->enum('status', ['PENDING', 'CONFIRMED', 'FAILED', 'REFUNDED'])->default('PENDING');
            
            // Payment Method Details
            $table->string('payment_method')->nullable(); // CASH, MOBILE_MONEY, BANK_TRANSFER, PESAPAL
            $table->string('payment_phone_number')->nullable();
            $table->string('payment_account_number')->nullable();
            
            // Dates
            $table->date('payment_date')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            
            // Membership Details
            $table->enum('membership_type', ['LIFE', 'ANNUAL', 'MONTHLY'])->default('LIFE');
            $table->date('expiry_date')->nullable(); // NULL for LIFE membership
            
            // Additional Information
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('receipt_photo')->nullable();
            
            // Payment Gateway Integration
            $table->string('pesapal_order_tracking_id')->nullable();
            $table->string('pesapal_merchant_reference')->nullable();
            $table->text('pesapal_response')->nullable();
            $table->string('confirmation_code')->nullable();
            
            // Universal Payment Link
            $table->bigInteger('universal_payment_id')->nullable();
            
            // Metadata
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->bigInteger('confirmed_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('membership_payments');
    }
}
