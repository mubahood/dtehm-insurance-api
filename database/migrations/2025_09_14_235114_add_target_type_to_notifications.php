<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTargetTypeToNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notification_models', function (Blueprint $table) {
            $table->string('target_type')->default('all')->after('type'); // e.g., 'all', 'users', 'segments', 'filters'
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
