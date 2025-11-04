<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReviewStatsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('review_count')->default(0)->after('tags');
            $table->decimal('average_rating', 3, 2)->default(0.00)->after('review_count');
            
            // Add index for sorting by rating
            $table->index('average_rating', 'products_rating_index');
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
            $table->dropIndex('products_rating_index');
            $table->dropColumn(['review_count', 'average_rating']);
        });
    }
}
