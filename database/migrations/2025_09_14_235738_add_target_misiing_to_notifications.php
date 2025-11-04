<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTargetMisiingToNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notification_models', function (Blueprint $table) {
            if (!Schema::hasColumn('notification_models', 'target_users')) {
                $table->text('target_users')->nullable();
            }
            if (!Schema::hasColumn('notification_models', 'target_segments')) {
                $table->text('target_segments')->nullable();
            }
            if (!Schema::hasColumn('notification_models', 'filters')) {
                $table->text('filters')->nullable();
            }
            if (!Schema::hasColumn('notification_models', 'onesignal_id')) {
                $table->text('onesignal_id')->nullable();
            }
            if (!Schema::hasColumn('notification_models', 'recipients')) {
                $table->text('recipients')->nullable();
            }
            if (!Schema::hasColumn('notification_models', 'error_message')) {
                $table->text('error_message')->nullable();
            }
            if (!Schema::hasColumn('notification_models', 'click_count')) {
                $table->text('click_count')->nullable();
            }
            if (!Schema::hasColumn('notification_models', 'large_icon')) {
                $table->text('large_icon')->nullable();
            }
            if (!Schema::hasColumn('notification_models', 'big_picture')) {
                $table->text('big_picture')->nullable();
            }
            if (!Schema::hasColumn('notification_models', 'sent_at')) {
                $table->text('sent_at')->nullable();
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
        Schema::table('notification_models', function (Blueprint $table) {
            //
        });
    }
}
