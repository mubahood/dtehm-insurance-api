<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectSharesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('investor_id')->comment('User who bought the shares');
            $table->date('purchase_date');
            $table->integer('number_of_shares')->comment('Shares bought in this transaction');
            $table->decimal('total_amount_paid', 15, 2)->comment('number_of_shares * share_price at purchase time');
            $table->decimal('share_price_at_purchase', 15, 2)->comment('Share price at time of purchase');
            $table->unsignedBigInteger('payment_id')->nullable()->comment('Link to universal_payments');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('project_id');
            $table->index('investor_id');
            $table->index('payment_id');
            $table->index('purchase_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_shares');
    }
}
