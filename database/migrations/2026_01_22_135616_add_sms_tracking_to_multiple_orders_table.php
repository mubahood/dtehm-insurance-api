<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSmsTrackingToMultipleOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('multiple_orders', function (Blueprint $table) {
            $table->boolean('sms_notifications_sent')->default(false)->after('conversion_error');
            $table->timestamp('sms_sent_at')->nullable()->after('sms_notifications_sent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('multiple_orders', function (Blueprint $table) {
            $table->dropColumn(['sms_notifications_sent', 'sms_sent_at']);
        });
    }
}
