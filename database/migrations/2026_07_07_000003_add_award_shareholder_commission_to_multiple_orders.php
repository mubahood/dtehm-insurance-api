<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddAwardShareholderCommissionToMultipleOrders extends Migration
{
    const AWARD_RATE       = 1.2;
    const SHAREHOLDER_RATE = 1.5;

    public function up()
    {
        Schema::table('multiple_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('multiple_orders', 'award_commission')) {
                $table->decimal('award_commission', 12, 2)->default(0)->after('total_amount')
                    ->comment('1.2% of total_amount — company awards pool');
            }
            if (!Schema::hasColumn('multiple_orders', 'shareholder_commission')) {
                $table->decimal('shareholder_commission', 12, 2)->default(0)->after('award_commission')
                    ->comment('1.5% of total_amount — shareholders pool');
            }
        });

        // Backfill existing cart orders
        DB::statement("
            UPDATE multiple_orders
            SET
                award_commission       = ROUND(total_amount * " . self::AWARD_RATE . " / 100, 2),
                shareholder_commission = ROUND(total_amount * " . self::SHAREHOLDER_RATE . " / 100, 2)
            WHERE total_amount > 0
        ");
    }

    public function down()
    {
        Schema::table('multiple_orders', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('multiple_orders', 'award_commission'))       $cols[] = 'award_commission';
            if (Schema::hasColumn('multiple_orders', 'shareholder_commission')) $cols[] = 'shareholder_commission';
            if ($cols) $table->dropColumn($cols);
        });
    }
}
