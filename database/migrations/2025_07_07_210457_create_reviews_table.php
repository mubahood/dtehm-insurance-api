<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::dropIfExists('reviews'); 
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('rating')->comment('Rating between 1-5');
            $table->text('comment');
            $table->timestamps();
            
            // Ensure one review per user per product
            $table->unique(['product_id', 'user_id'], 'unique_user_product_review');
            
            // Add indexes for better performance
            $table->index(['product_id', 'rating'], 'product_rating_index');
            $table->index('user_id', 'user_reviews_index');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}
