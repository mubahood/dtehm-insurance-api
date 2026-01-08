<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddProductPurchaseToAccountTransactionsSourceEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE account_transactions MODIFY COLUMN source ENUM('disbursement', 'withdrawal', 'deposit', 'product_commission', 'dtehm_referral_commission', 'commission_share', 'product_purchase') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Before rolling back, update any product_purchase records to withdrawal
        DB::table('account_transactions')
            ->where('source', 'product_purchase')
            ->update(['source' => 'withdrawal']);
            
        DB::statement("ALTER TABLE account_transactions MODIFY COLUMN source ENUM('disbursement', 'withdrawal', 'deposit', 'product_commission', 'dtehm_referral_commission', 'commission_share') NOT NULL");
    }
}
