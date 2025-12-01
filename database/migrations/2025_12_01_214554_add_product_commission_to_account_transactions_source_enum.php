<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductCommissionToAccountTransactionsSourceEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE account_transactions MODIFY COLUMN source ENUM('disbursement', 'withdrawal', 'deposit', 'product_commission', 'dtehm_referral_commission') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Before rolling back, update any product_commission records to deposit
        DB::table('account_transactions')
            ->where('source', 'product_commission')
            ->orWhere('source', 'dtehm_referral_commission')
            ->update(['source' => 'deposit']);
            
        DB::statement("ALTER TABLE account_transactions MODIFY COLUMN source ENUM('disbursement', 'withdrawal', 'deposit') NOT NULL");
    }
}
