<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnesignalDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('onesignal_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('player_id')->unique(); // OneSignal Player ID
            $table->string('device_type')->default('mobile'); // mobile, web, etc.
            $table->string('app_version')->nullable();
            $table->timestamp('last_active')->nullable();
            $table->timestamps();
            
            // Foreign key constraint (optional - depends on your users table)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Composite index for faster queries
            // $table->index(['user_id', 'player_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('onesignal_devices');
    }
}
