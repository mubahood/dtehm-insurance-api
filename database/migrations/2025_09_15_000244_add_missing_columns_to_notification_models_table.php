<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToNotificationModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notification_models', function (Blueprint $table) {
            // Scheduling fields
            $table->datetime('scheduled_at')->nullable()->after('delivery_type');
            $table->string('recurring_pattern')->nullable()->after('scheduled_at');
            $table->datetime('start_at')->nullable()->after('recurring_pattern');
            $table->datetime('end_at')->nullable()->after('start_at');
            
            // Media upload type fields
            $table->string('icon_type')->default('default')->after('url');
            $table->string('picture_type')->default('none')->after('icon_type');
            $table->string('large_icon_upload')->nullable()->after('large_icon');
            $table->string('big_picture_upload')->nullable()->after('big_picture');
            
            // Advanced targeting and settings
            $table->json('target_devices')->nullable()->after('target_segments');
            $table->boolean('send_after_time_passed')->default(false)->after('data');
            $table->integer('ttl')->nullable()->after('send_after_time_passed');
            $table->json('priority_countries')->nullable()->after('ttl');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notification_models', function (Blueprint $table) {
            $table->dropColumn([
                'scheduled_at',
                'recurring_pattern', 
                'start_at',
                'end_at',
                'icon_type',
                'picture_type',
                'large_icon_upload',
                'big_picture_upload',
                'target_devices',
                'send_after_time_passed',
                'ttl',
                'priority_countries'
            ]);
        });
    }
}
