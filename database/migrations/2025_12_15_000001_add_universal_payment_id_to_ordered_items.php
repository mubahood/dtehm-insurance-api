<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniversalPaymentIdToOrderedItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ordered_items', function (Blueprint $table) {
            $table->foreignId('universal_payment_id')->nullable()->after('order')->constrained('universal_payments')->onDelete('set null');
            $table->index('universal_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ordered_items', function (Blueprint $table) {
            $table->dropForeign(['universal_payment_id']);
            $table->dropColumn('universal_payment_id');
        });
    }
}
