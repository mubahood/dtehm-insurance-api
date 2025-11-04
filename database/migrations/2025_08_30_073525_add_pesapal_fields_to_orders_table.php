<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPesapalFieldsToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Payment gateway information
            $table->text('payment_gateway')->nullable();
            
            // Pesapal specific fields
            $table->text('pesapal_order_tracking_id')->nullable();
            $table->text('pesapal_merchant_reference')->nullable();
            $table->text('pesapal_status')->nullable();
            $table->text('pesapal_payment_method')->nullable();
            $table->text('pesapal_redirect_url')->nullable();
            
            // Payment status tracking
            $table->text('payment_status')->nullable();
            $table->text('payment_completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_gateway',
                'pesapal_order_tracking_id',
                'pesapal_merchant_reference',
                'pesapal_status',
                'pesapal_payment_method',
                'pesapal_redirect_url',
                'payment_status',
                'payment_completed_at'
            ]);
        });
    }
}
