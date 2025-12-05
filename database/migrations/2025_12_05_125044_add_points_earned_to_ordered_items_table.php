<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPointsEarnedToOrderedItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ordered_items', function (Blueprint $table) {
            $table->integer('points_earned')->nullable()->default(0)->after('commission_parent_10')->comment('Points earned by sponsor for selling this product');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ordered_items', function (Blueprint $table) {
            $table->dropColumn('points_earned');
        });
    }
}
