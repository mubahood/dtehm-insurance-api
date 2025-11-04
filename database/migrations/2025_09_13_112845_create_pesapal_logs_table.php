<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePesapalLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pesapal_logs', function (Blueprint $table) {
            $table->id();
            $table->string('test_type')->default('manual'); // manual, scenario, bulk
            $table->string('action')->index(); // initialize, status_check, callback, etc.
            $table->string('method')->default('POST'); // GET, POST, DELETE
            $table->string('endpoint')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('tracking_id')->nullable()->index();
            $table->string('merchant_reference')->nullable();
            
            // Request data
            $table->json('request_data')->nullable();
            $table->text('request_headers')->nullable();
            
            // Response data
            $table->boolean('success')->default(false)->index();
            $table->string('status_code')->nullable();
            $table->text('message')->nullable();
            $table->json('response_data')->nullable();
            $table->text('response_headers')->nullable();
            
            // Payment specific fields
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 3)->default('UGX');
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('description')->nullable();
            
            // Timing
            $table->decimal('response_time_ms', 8, 2)->nullable(); // Response time in milliseconds
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Error handling
            $table->text('error_message')->nullable();
            $table->text('error_trace')->nullable();
            
            // Test metadata
            $table->string('test_scenario')->nullable(); // success, high_amount, minimal, etc.
            $table->string('environment')->default('sandbox');
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            
            $table->timestamps();
            
            // Indexes for better performance
            // $table->index(['action', 'success']);
            // $table->index(['test_type', 'created_at']);
            // $table->index(['amount', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pesapal_logs');
    }
}
