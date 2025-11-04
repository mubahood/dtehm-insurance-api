<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_models', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('general'); // general, promotion, order, etc.
            $table->json('target_users')->nullable(); // null = all users, array of user IDs
            $table->json('target_segments')->nullable(); // OneSignal segments
            $table->json('filters')->nullable(); // OneSignal filters
            $table->string('onesignal_id')->nullable(); // OneSignal notification ID
            $table->integer('recipients')->default(0); // Number of recipients
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->text('error_message')->nullable();
            $table->json('data')->nullable(); // Additional data payload
            $table->string('url')->nullable(); // Action URL
            $table->string('large_icon')->nullable(); // Large icon URL
            $table->string('big_picture')->nullable(); // Big picture URL
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // $table->index(['status', 'created_at']);
            // $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_models');
    }
}
