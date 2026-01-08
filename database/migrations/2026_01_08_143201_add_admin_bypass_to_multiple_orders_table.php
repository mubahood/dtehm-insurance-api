<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminBypassToMultipleOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('multiple_orders', function (Blueprint $table) {
            $table->boolean('is_paid_by_admin')->default(false)->after('payment_status')->comment('Admin bypass - cash payment received');
            $table->text('admin_payment_note')->nullable()->after('is_paid_by_admin')->comment('Admin note for cash payment');
            $table->timestamp('paid_at')->nullable()->after('payment_completed_at')->comment('Timestamp when payment received (admin bypass)');
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
            $table->dropColumn(['is_paid_by_admin', 'admin_payment_note', 'paid_at']);
        });
    }
}
