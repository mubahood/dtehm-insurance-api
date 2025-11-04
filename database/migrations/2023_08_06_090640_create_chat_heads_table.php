<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatHeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        return;
        Schema::create('chat_heads', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Product::class);
            $table->text('product_name')->nullable();
            $table->text('product_photo')->nullable();
            $table->integer('product_owner_id')->nullable();
            $table->text('product_owner_name')->nullable();
            $table->text('product_owner_photo')->nullable();
            $table->string('product_owner_last_seen')->nullable();

            $table->integer('customer_id')->nullable();
            $table->text('customer_name')->nullable();
            $table->text('customer_photo')->nullable();
            $table->string('customer_last_seen')->nullable();
            $table->text('last_message_body')->nullable();
            $table->string('last_message_time')->nullable();
            $table->string('last_message_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_heads');
    }
}
