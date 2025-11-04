<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttributeTypeToProductCategoryAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_category_attributes', function (Blueprint $table) {
            if (!Schema::hasColumn('product_category_attributes', 'attribute_type')) {
                $table->string('attribute_type')->default('text')->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_category_attributes', function (Blueprint $table) {
            if (Schema::hasColumn('product_category_attributes', 'attribute_type')) {
                $table->dropColumn('attribute_type');
            }
        });
    }
}
