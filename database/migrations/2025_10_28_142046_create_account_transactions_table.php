<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('User who owns this transaction');
            $table->decimal('amount', 15, 2)->comment('Positive for credit, negative for debit');
            $table->date('transaction_date');
            $table->text('description')->nullable();
            $table->enum('source', ['disbursement', 'withdrawal', 'deposit'])->comment('Source of transaction');
            $table->unsignedBigInteger('related_disbursement_id')->nullable()->comment('If source is disbursement');
            $table->unsignedBigInteger('created_by_id')->comment('User who created the transaction');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('source');
            $table->index('transaction_date');
            $table->index('related_disbursement_id');
            $table->index('created_by_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_transactions');
    }
}
