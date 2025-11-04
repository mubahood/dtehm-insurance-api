<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePesapalIpnLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pesapal_ipn_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            
            // IPN notification details
            $table->text('order_tracking_id')->nullable();
            $table->text('merchant_reference')->nullable();
            $table->text('notification_type')->nullable();
            
            // Request details
            $table->text('request_method')->nullable();
            $table->text('payload')->nullable();
            $table->text('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            // Processing details
            $table->text('status')->nullable();
            $table->text('processed_at')->nullable();
            $table->text('processing_notes')->nullable();
            $table->text('response_sent')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pesapal_ipn_logs');
    }
}
