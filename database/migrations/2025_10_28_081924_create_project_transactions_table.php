<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->decimal('amount', 15, 2)->comment('Positive for income, negative for expense');
            $table->date('transaction_date');
            $table->unsignedBigInteger('created_by_id')->comment('Admin user who created the transaction');
            $table->text('description')->nullable();
            $table->enum('type', ['income', 'expense']);
            $table->enum('source', ['share_purchase', 'project_profit', 'project_expense', 'returns_distribution']);
            $table->unsignedBigInteger('related_share_id')->nullable()->comment('If source is share_purchase');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('project_id');
            $table->index('created_by_id');
            $table->index('type');
            $table->index('source');
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_transactions');
    }
}
