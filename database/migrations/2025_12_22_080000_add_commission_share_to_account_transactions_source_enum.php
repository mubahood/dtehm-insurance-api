<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddCommissionShareToAccountTransactionsSourceEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE account_transactions MODIFY COLUMN source ENUM('disbursement', 'withdrawal', 'deposit', 'product_commission', 'dtehm_referral_commission', 'commission_share') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Before rolling back, update any commission_share records to deposit
        DB::table('account_transactions')
            ->where('source', 'commission_share')
            ->update(['source' => 'deposit']);
            
        DB::statement("ALTER TABLE account_transactions MODIFY COLUMN source ENUM('disbursement', 'withdrawal', 'deposit', 'product_commission', 'dtehm_referral_commission') NOT NULL");
    }
}
