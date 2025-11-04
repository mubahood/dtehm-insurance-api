<?php

use App\Models\ChatHead;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        return;
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(ChatHead::class);
            $table->foreignIdFor(User::class, 'sender_id');
            $table->foreignIdFor(User::class, 'receiver_id');
            $table->text('sender_name')->nullable();
            $table->text('sender_photo')->nullable();
            $table->text('receiver_name')->nullable();
            $table->text('receiver_photo')->nullable();
            $table->text('body')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->text('audio')->nullable();
            $table->text('video')->nullable();
            $table->text('document')->nullable();
            $table->text('photo')->nullable();
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
}
