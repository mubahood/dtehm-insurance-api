<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentMethodToMultipleOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('multiple_orders', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('payment_status')
                ->comment('Payment method used: pesapal, credit_balance, admin_bypass');
            $table->text('payment_note')->nullable()->after('payment_method')
                ->comment('Additional payment notes (e.g., Credit Balance transaction ID, admin notes)');
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
            $table->dropColumn(['payment_method', 'payment_note']);
        });
    }
}
