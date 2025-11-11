<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWithdrawRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdraw_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('User requesting withdrawal');
            $table->decimal('amount', 15, 2)->comment('Amount to withdraw');
            $table->decimal('account_balance_before', 15, 2)->comment('Account balance at time of request');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('description')->nullable()->comment('User description/reason for withdrawal');
            $table->string('payment_method', 100)->nullable()->comment('Mobile money, bank, etc.');
            $table->string('payment_phone_number', 20)->nullable()->comment('Phone number for mobile money');
            $table->text('admin_note')->nullable()->comment('Admin note when approving/rejecting');
            $table->unsignedBigInteger('processed_by_id')->nullable()->comment('Admin who processed the request');
            $table->timestamp('processed_at')->nullable()->comment('When request was approved/rejected');
            $table->unsignedBigInteger('account_transaction_id')->nullable()->comment('Related transaction if approved');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('processed_by_id');
            $table->index('account_transaction_id');
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
        Schema::dropIfExists('withdraw_requests');
    }
}
