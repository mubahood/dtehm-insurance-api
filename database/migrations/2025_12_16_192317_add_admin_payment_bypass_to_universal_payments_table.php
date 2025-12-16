<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminPaymentBypassToUniversalPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('universal_payments', function (Blueprint $table) {
            $table->boolean('paid_by_admin')->default(false)->after('items_processed')->comment('If true, admin marked this as already paid (bypass payment gateway)');
            $table->text('admin_payment_note')->nullable()->after('paid_by_admin')->comment('Admin note about the payment');
            $table->unsignedBigInteger('marked_paid_by')->nullable()->after('admin_payment_note')->comment('Admin user ID who marked as paid');
            $table->timestamp('marked_paid_at')->nullable()->after('marked_paid_by')->comment('When admin marked as paid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('universal_payments', function (Blueprint $table) {
            $table->dropColumn(['paid_by_admin', 'admin_payment_note', 'marked_paid_by', 'marked_paid_at']);
        });
    }
}
