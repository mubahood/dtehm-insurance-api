<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameInsuranceUserIdToUserIdInAllTables extends Migration
{
    /**
     * Run the migrations.
     * Rename insurance_user_id to user_id in all insurance tables
     *
     * @return void
     */
    public function up()
    {
        // Rename insurance_user_id to user_id in transactions table
        if (Schema::hasColumn('transactions', 'insurance_user_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->renameColumn('insurance_user_id', 'user_id');
            });
        }

        // Rename insurance_user_id to user_id in insurance_subscriptions table
        if (Schema::hasColumn('insurance_subscriptions', 'insurance_user_id')) {
            Schema::table('insurance_subscriptions', function (Blueprint $table) {
                $table->renameColumn('insurance_user_id', 'user_id');
            });
        }

        // Rename insurance_user_id to user_id in insurance_subscription_payments table
        if (Schema::hasColumn('insurance_subscription_payments', 'insurance_user_id')) {
            Schema::table('insurance_subscription_payments', function (Blueprint $table) {
                $table->renameColumn('insurance_user_id', 'user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert user_id back to insurance_user_id in transactions table
        if (Schema::hasColumn('transactions', 'user_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->renameColumn('user_id', 'insurance_user_id');
            });
        }

        // Revert user_id back to insurance_user_id in insurance_subscriptions table
        if (Schema::hasColumn('insurance_subscriptions', 'user_id')) {
            Schema::table('insurance_subscriptions', function (Blueprint $table) {
                $table->renameColumn('user_id', 'insurance_user_id');
            });
        }

        // Revert user_id back to insurance_user_id in insurance_subscription_payments table
        if (Schema::hasColumn('insurance_subscription_payments', 'user_id')) {
            Schema::table('insurance_subscription_payments', function (Blueprint $table) {
                $table->renameColumn('user_id', 'insurance_user_id');
            });
        }
    }
}
