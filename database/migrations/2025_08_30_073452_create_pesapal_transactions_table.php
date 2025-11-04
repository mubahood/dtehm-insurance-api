<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePesapalTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pesapal_transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            
            // Order reference
            $table->text('order_id')->nullable();
            
            // Pesapal identifiers
            $table->text('order_tracking_id')->nullable();
            $table->text('merchant_reference')->nullable();
            
            // Transaction details
            $table->text('amount')->nullable();
            $table->text('currency')->nullable();
            $table->text('status')->nullable();
            $table->text('status_code')->nullable();
            
            // Payment details
            $table->text('payment_method')->nullable();
            $table->text('confirmation_code')->nullable();
            $table->text('payment_account')->nullable();
            
            // URLs and IDs
            $table->text('redirect_url')->nullable();
            $table->text('callback_url')->nullable();
            $table->text('notification_id')->nullable();
            
            // Additional fields
            $table->text('description')->nullable();
            $table->text('pesapal_response')->nullable();
            $table->text('error_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pesapal_transactions');
    }
}
