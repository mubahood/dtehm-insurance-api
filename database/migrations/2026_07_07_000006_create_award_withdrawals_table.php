<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAwardWithdrawalsTable extends Migration
{
    public function up()
    {
        Schema::create('award_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 15, 2);
            $table->text('description');
            $table->string('award_name', 255)->nullable()->comment('Name of the award/prize purchased');
            $table->string('recipient', 255)->nullable()->comment('Winner or recipient');
            $table->date('withdrawal_date');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('award_withdrawals');
    }
}
