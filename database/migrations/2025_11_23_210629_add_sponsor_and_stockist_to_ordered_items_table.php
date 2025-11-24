<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSponsorAndStockistToOrderedItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ordered_items', function (Blueprint $table) {
            // Sponsor ID - DTEHM member who is purchasing/selling
            $table->string('sponsor_id')->nullable()->after('product');
            
            // Stockist ID - DTEHM member who owns the shop/stockist location
            $table->string('stockist_id')->nullable()->after('sponsor_id');
            
            // Stockist commission (8% of product price)
            $table->decimal('commission_stockist', 10, 2)->default(0)->after('commission_seller');
            
            // Stockist user ID for reference
            $table->bigInteger('stockist_user_id')->unsigned()->nullable()->after('dtehm_user_id');
            
            // Sponsor user ID for reference
            $table->bigInteger('sponsor_user_id')->unsigned()->nullable()->after('stockist_user_id');
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
            $table->dropColumn([
                'sponsor_id',
                'stockist_id',
                'commission_stockist',
                'stockist_user_id',
                'sponsor_user_id',
            ]);
        });
    }
}
