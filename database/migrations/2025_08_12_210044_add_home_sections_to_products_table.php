<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHomeSectionsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Add home section columns to control where products appear on homepage
            $table->string('home_section_1', 10)->default('No')->comment('Flash Sales section - Yes/No');
            $table->string('home_section_2', 10)->default('No')->comment('Super Buyer section - Yes/No');
            $table->string('home_section_3', 10)->default('No')->comment('Top Products section - Yes/No');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['home_section_1', 'home_section_2', 'home_section_3']);
        });
    }
}
