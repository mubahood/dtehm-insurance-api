<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommissionTrackingColumnsToAccountTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            // Add columns to prevent duplicate commissions
            $table->string('commission_type')->nullable()->after('source')
                ->comment('Type of commission: product_commission, dtehm_referral, etc.');
            
            $table->unsignedBigInteger('commission_reference_id')->nullable()->after('commission_type')
                ->comment('Reference ID: ordered_item_id for product commissions, membership_id for referrals');
            
            $table->decimal('commission_amount', 15, 2)->nullable()->after('commission_reference_id')
                ->comment('Commission amount for quick duplicate checks');
            
            // Add composite index for duplicate prevention
            $table->index(['user_id', 'commission_type', 'commission_reference_id'], 'idx_commission_duplicate_check');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_commission_duplicate_check');
            $table->dropColumn(['commission_type', 'commission_reference_id', 'commission_amount']);
        });
    }
}
