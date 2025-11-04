<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryTypeNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notification_models', function (Blueprint $table) {
            $table->string('delivery_type')->default('immediate')->after('status'); // e.g., 'immediate', 'scheduled', 'recurring'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notification_models', function (Blueprint $table) {
            //
        });
    }
}
