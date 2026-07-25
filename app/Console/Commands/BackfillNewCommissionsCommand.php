<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillNewCommissionsCommand extends Command
{
    protected $signature   = 'commissions:backfill-new {--dry-run : Show counts without updating}';
    protected $description = 'Backfill award_commission (1.2%) and shareholder_commission (1.5%) for all existing sales';

    const AWARD_RATE       = 1.2;
    const SHAREHOLDER_RATE = 1.5;

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        // ── ordered_items ──────────────────────────────────────────────────
        $itemsNeedingUpdate = DB::table('ordered_items')
            ->where('subtotal', '>', 0)
            ->where(function ($q) {
                $q->whereNull('award_commission')
                  ->orWhere('award_commission', 0)
                  ->orWhereNull('shareholder_commission')
                  ->orWhere('shareholder_commission', 0);
            })
            ->count();

        $this->info("ordered_items needing backfill: {$itemsNeedingUpdate}");

        if (!$dryRun && $itemsNeedingUpdate > 0) {
            $updated = DB::statement("
                UPDATE ordered_items
                SET
                    award_commission       = ROUND(subtotal * " . self::AWARD_RATE       . " / 100, 2),
                    shareholder_commission = ROUND(subtotal * " . self::SHAREHOLDER_RATE . " / 100, 2)
                WHERE subtotal > 0
            ");
            $this->info('ordered_items updated.');
        }

        // ── multiple_orders ────────────────────────────────────────────────
        $ordersNeedingUpdate = DB::table('multiple_orders')
            ->where('total_amount', '>', 0)
            ->where(function ($q) {
                $q->whereNull('award_commission')
                  ->orWhere('award_commission', 0)
                  ->orWhereNull('shareholder_commission')
                  ->orWhere('shareholder_commission', 0);
            })
            ->count();

        $this->info("multiple_orders needing backfill: {$ordersNeedingUpdate}");

        if (!$dryRun && $ordersNeedingUpdate > 0) {
            DB::statement("
                UPDATE multiple_orders
                SET
                    award_commission       = ROUND(total_amount * " . self::AWARD_RATE       . " / 100, 2),
                    shareholder_commission = ROUND(total_amount * " . self::SHAREHOLDER_RATE . " / 100, 2)
                WHERE total_amount > 0
            ");
            $this->info('multiple_orders updated.');
        }

        // ── Verification summary ───────────────────────────────────────────
        $totalRevenue      = DB::table('ordered_items')->sum('subtotal');
        $totalAward        = DB::table('ordered_items')->sum('award_commission');
        $totalShareholder  = DB::table('ordered_items')->sum('shareholder_commission');

        $expectedAward       = round($totalRevenue * self::AWARD_RATE       / 100, 2);
        $expectedShareholder = round($totalRevenue * self::SHAREHOLDER_RATE / 100, 2);

        $this->newLine();
        $this->info('── Verification (ordered_items) ──────────────────────────────');
        $this->line("Total revenue:               UGX " . number_format($totalRevenue, 2));
        $this->line("Award pool stored:           UGX " . number_format($totalAward, 2));
        $this->line("Award pool expected (1.2%):  UGX " . number_format($expectedAward, 2));
        $this->line("Δ award:                     " . number_format(abs($totalAward - $expectedAward), 2) . " (rounding)");
        $this->line("Shareholder pool stored:     UGX " . number_format($totalShareholder, 2));
        $this->line("Shareholder expected (1.5%): UGX " . number_format($expectedShareholder, 2));
        $this->line("Δ shareholder:               " . number_format(abs($totalShareholder - $expectedShareholder), 2) . " (rounding)");
        $this->newLine();

        if ($dryRun) {
            $this->warn('Dry-run mode: no data was changed. Remove --dry-run to apply.');
        } else {
            $this->info('Backfill complete.');
        }

        return 0;
    }
}
