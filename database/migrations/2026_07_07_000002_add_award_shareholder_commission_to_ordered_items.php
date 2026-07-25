<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddAwardShareholderCommissionToOrderedItems extends Migration
{
    // Rates as constants for clarity and easy change
    const AWARD_RATE       = 1.2; // 1.2% per sale — company awards pool
    const SHAREHOLDER_RATE = 1.5; // 1.5% per sale — shareholders pool

    public function up()
    {
        Schema::table('ordered_items', function (Blueprint $table) {
            if (!Schema::hasColumn('ordered_items', 'award_commission')) {
                $table->decimal('award_commission', 12, 2)->default(0)->after('balance_after_commission')
                    ->comment('1.2% of subtotal — company awards pool');
            }
            if (!Schema::hasColumn('ordered_items', 'shareholder_commission')) {
                $table->decimal('shareholder_commission', 12, 2)->default(0)->after('award_commission')
                    ->comment('1.5% of subtotal — shareholders pool');
            }
        });

        // Backfill for all existing sales that have a subtotal > 0
        DB::statement("
            UPDATE ordered_items
            SET
                award_commission       = ROUND(subtotal * " . self::AWARD_RATE . " / 100, 2),
                shareholder_commission = ROUND(subtotal * " . self::SHAREHOLDER_RATE . " / 100, 2)
            WHERE subtotal > 0
        ");
    }

    public function down()
    {
        Schema::table('ordered_items', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('ordered_items', 'award_commission'))       $cols[] = 'award_commission';
            if (Schema::hasColumn('ordered_items', 'shareholder_commission')) $cols[] = 'shareholder_commission';
            if ($cols) $table->dropColumn($cols);
        });
    }
}
