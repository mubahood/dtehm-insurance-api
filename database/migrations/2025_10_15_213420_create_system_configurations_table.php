<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //drop table if exists
        if (Schema::hasTable('system_configurations')) {
            Schema::dropIfExists('system_configurations');
        }
        Schema::create('system_configurations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('company_name')->nullable();
            $table->text('company_email')->nullable();
            $table->text('company_phone')->nullable();
            $table->text('company_pobox')->nullable();
            $table->text('company_address')->nullable();
            $table->text('company_website')->nullable();
            $table->text('company_logo')->nullable();
            $table->text('company_details')->nullable();
            $table->dateTime('insurance_start_date')->nullable();
            $table->integer('insurance_price')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_configurations');
    }
}
