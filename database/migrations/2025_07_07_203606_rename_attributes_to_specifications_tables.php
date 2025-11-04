<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameAttributesToSpecificationsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Rename product_has_attributes to product_has_specifications
        Schema::rename('product_has_attributes', 'product_has_specifications');
        
        // Rename product_category_attributes to product_category_specifications
        Schema::rename('product_category_attributes', 'product_category_specifications');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Reverse the renames
        Schema::rename('product_has_specifications', 'product_has_attributes');
        Schema::rename('product_category_specifications', 'product_category_attributes');
    }
}
