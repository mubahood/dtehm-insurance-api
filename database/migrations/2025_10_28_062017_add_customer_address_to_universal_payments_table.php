<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerAddressToUniversalPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('universal_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('universal_payments', 'customer_address')) {
                $table->string('customer_address')->nullable()->after('customer_phone');
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
        Schema::table('universal_payments', function (Blueprint $table) {
            if (Schema::hasColumn('universal_payments', 'customer_address')) {
                $table->dropColumn('customer_address');
            }
        });
    }
}
