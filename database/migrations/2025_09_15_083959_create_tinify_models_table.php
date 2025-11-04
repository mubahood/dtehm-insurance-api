<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTinifyModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tinify_models', function (Blueprint $table) {
            $table->id();
            $table->string('api_key')->unique();
            $table->string('status')->default('active'); // active, inactive, expired
            $table->integer('usage_count')->default(0);
            $table->integer('monthly_limit')->default(500);
            $table->integer('compressions_this_month')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_reset_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tinify_models');
    }
}
