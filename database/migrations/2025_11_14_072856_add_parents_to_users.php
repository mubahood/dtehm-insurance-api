<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentsToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('parent_1')->nullable();
            $table->bigInteger('parent_2')->nullable();
            $table->bigInteger('parent_3')->nullable();
            $table->bigInteger('parent_4')->nullable();
            $table->bigInteger('parent_5')->nullable();
            $table->bigInteger('parent_6')->nullable();
            $table->bigInteger('parent_7')->nullable();
            $table->bigInteger('parent_8')->nullable();
            $table->bigInteger('parent_9')->nullable();
            $table->bigInteger('parent_10')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
