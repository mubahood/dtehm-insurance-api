<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('transactions');
        
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys - No cascade constraints
            $table->unsignedBigInteger('insurance_user_id');
            $table->index('insurance_user_id');
            
            // Transaction Details
            $table->decimal('amount', 15, 2); // Can be negative (withdrawal) or positive (deposit)
            $table->enum('type', ['DEPOSIT', 'WITHDRAWAL'])->default('DEPOSIT');
            $table->text('description')->nullable();
            
            // Payment Information
            $table->string('reference_number')->nullable()->unique();
            $table->enum('payment_method', ['CASH', 'MOBILE_MONEY', 'BANK_TRANSFER', 'CHEQUE', 'OTHER'])->default('CASH');
            $table->string('payment_phone_number')->nullable();
            $table->string('payment_account_number')->nullable();
            
            // Status & Metadata
            $table->enum('status', ['PENDING', 'COMPLETED', 'FAILED', 'CANCELLED'])->default('COMPLETED');
            $table->date('transaction_date')->nullable();
            $table->text('remarks')->nullable();
            
            // Receipt/Proof
            $table->string('receipt_photo')->nullable();
            
            // Tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Additional Indexes
            $table->index('type');
            $table->index('status');
            $table->index('transaction_date');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
