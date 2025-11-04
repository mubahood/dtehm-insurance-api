<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMailThingsToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
        $form->radio('order_state', __('Order State'))
        ->options([
            0 => 'Pending',
            1 => 'Processing',
            2 => 'Completed',
            3 => 'Canceled',
            4 => 'Failed',
        ]);         
         */
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'pending_mail_sent')) {
                $table->string('pending_mail_sent')->default('No')->nullable();
            }
            if (!Schema::hasColumn('orders', 'processing_mail_sent')) {
                $table->string('processing_mail_sent')->default('No')->nullable();
            }
            if (!Schema::hasColumn('orders', 'completed_mail_sent')) {
                $table->string('completed_mail_sent')->default('No')->nullable();
            }
            if (!Schema::hasColumn('orders', 'canceled_mail_sent')) {
                $table->string('canceled_mail_sent')->default('No')->nullable();
            }
            if (!Schema::hasColumn('orders', 'failed_mail_sent')) {
                $table->string('failed_mail_sent')->default('No')->nullable();
            }
            if (!Schema::hasColumn('orders', 'sub_total')) {
                $table->bigInteger('sub_total')->default(0)->nullable();
            }
            if (!Schema::hasColumn('orders', 'tax')) {
                $table->bigInteger('tax')->default(0)->nullable();
            }
            if (!Schema::hasColumn('orders', 'discount')) {
                $table->bigInteger('discount')->default(0)->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivery_fee')) {
                $table->bigInteger('delivery_fee')->default(0)->nullable();
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
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
}
