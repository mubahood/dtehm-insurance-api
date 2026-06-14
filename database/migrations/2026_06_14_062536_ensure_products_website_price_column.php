<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureProductsWebsitePriceColumn extends Migration
{
    public function up()
    {
        // Add price_2 (website price) if it doesn't already exist
        if (!Schema::hasColumn('products', 'price_2')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('price_2', 12, 2)->nullable()->after('price_1')
                    ->comment('Website / public price. Defaults to price_1 (app price) when null.');
            });
        }

        // Backfill: set price_2 = price_1 for every row where price_2 is NULL or 0
        DB::statement('UPDATE products SET price_2 = price_1 WHERE price_2 IS NULL OR price_2 = 0');
    }

    public function down()
    {
        // Not dropping — column may have pre-existed and removing it loses data
    }
}
