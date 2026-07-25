<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\OrderedItem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Support\Facades\DB;

class ShopsDashboardController extends AdminController
{
    protected $title = 'Shops Dashboard';

    public function index(Content $content)
    {
        $content->title('🏪 Shops Dashboard')
            ->description('Sales and commission analysis by shop / stockist');

        // ── Aggregate data ─────────────────────────────────────────────────
        $shops = User::where('is_stockist', 'Yes')
            ->orderBy('name')
            ->get();

        $totalShops   = $shops->count();
        $totalMembers = User::whereNotNull('shop_id')->count();

        // Total sales routed through any shop (stockist_user_id set)
        $totalSalesViaShops    = OrderedItem::whereNotNull('stockist_user_id')->sum('subtotal');
        $totalStockistCommission = OrderedItem::whereNotNull('stockist_user_id')->sum('commission_stockist');

        // ── Top-level stat boxes ────────────────────────────────────────────
        $content->row(function (Row $row) use ($totalShops, $totalMembers, $totalSalesViaShops, $totalStockistCommission) {
            $row->column(3, function (Column $col) use ($totalShops) {
                $col->append(new InfoBox('Total Shops', 'fa-building', 'aqua', admin_url('stockists'), $totalShops));
            });
            $row->column(3, function (Column $col) use ($totalMembers) {
                $col->append(new InfoBox('Members in Shops', 'fa-users', 'green', admin_url('dtehm-members'), number_format($totalMembers)));
            });
            $row->column(3, function (Column $col) use ($totalSalesViaShops) {
                $col->append(new InfoBox('Shop Sales (UGX)', 'fa-shopping-cart', 'yellow', '#', 'UGX ' . number_format($totalSalesViaShops, 0)));
            });
            $row->column(3, function (Column $col) use ($totalStockistCommission) {
                $col->append(new InfoBox('Stockist Commission', 'fa-money', 'red', '#', 'UGX ' . number_format($totalStockistCommission, 0)));
            });
        });

        // ── Per-shop breakdown table ────────────────────────────────────────
        $shopStats = [];
        foreach ($shops as $shop) {
            $memberCount = User::where('shop_id', $shop->id)->count();

            $salesData = OrderedItem::where('stockist_user_id', $shop->id)
                ->selectRaw('COUNT(*) as sale_count, SUM(subtotal) as total_sales, SUM(commission_stockist) as total_commission, SUM(award_commission) as total_award, SUM(shareholder_commission) as total_shareholder')
                ->first();

            $shopStats[] = [
                'shop'                 => $shop,
                'member_count'         => $memberCount,
                'sale_count'           => $salesData->sale_count ?? 0,
                'total_sales'          => $salesData->total_sales ?? 0,
                'total_commission'     => $salesData->total_commission ?? 0,
                'total_award'          => $salesData->total_award ?? 0,
                'total_shareholder'    => $salesData->total_shareholder ?? 0,
            ];
        }

        // Sort by total sales desc
        usort($shopStats, fn($a, $b) => $b['total_sales'] <=> $a['total_sales']);

        // Build table HTML
        $rows = '';
        foreach ($shopStats as $i => $s) {
            $rank   = $i + 1;
            $badge  = $rank <= 3 ? ['🥇', '🥈', '🥉'][$i] : "#$rank";
            $shopId = $s['shop']->dtehm_member_id ?? $s['shop']->business_name ?? '—';
            $rows  .= "<tr>
                <td><strong>{$badge}</strong></td>
                <td><strong>{$s['shop']->name}</strong><br><small class='text-muted'>{$shopId}</small></td>
                <td>{$s['member_count']}</td>
                <td>{$s['sale_count']}</td>
                <td class='text-right'><strong>UGX " . number_format($s['total_sales'], 0) . "</strong></td>
                <td class='text-right' style='color:#00a65a'>UGX " . number_format($s['total_commission'], 0) . "</td>
                <td class='text-right' style='color:#f39c12'>UGX " . number_format($s['total_award'], 0) . "</td>
                <td class='text-right' style='color:#3c8dbc'>UGX " . number_format($s['total_shareholder'], 0) . "</td>
            </tr>";
        }

        $tableHtml = "
        <div class='table-responsive'>
        <table class='table table-hover table-bordered' style='font-size:13px;'>
            <thead style='background:#f4f6f9;'>
                <tr>
                    <th>#</th>
                    <th>Shop / Stockist</th>
                    <th>Members</th>
                    <th>Sales Count</th>
                    <th class='text-right'>Total Revenue</th>
                    <th class='text-right'>Stockist Commission (7%)</th>
                    <th class='text-right'>Awards Pool (1.2%)</th>
                    <th class='text-right'>Shareholders Pool (1.5%)</th>
                </tr>
            </thead>
            <tbody>{$rows}</tbody>
        </table>
        </div>";

        $content->row(function (Row $row) use ($tableHtml) {
            $row->column(12, function (Column $col) use ($tableHtml) {
                $box = new Box('Shop Performance Breakdown', $tableHtml);
                $box->style('primary');
                $col->append($box);
            });
        });

        // ── Members without a shop assignment ──────────────────────────────
        $unassignedCount = User::where('is_dtehm_member', 'Yes')
            ->where('is_stockist', '!=', 'Yes')
            ->whereNull('shop_id')
            ->count();

        $unassignedHtml = "
        <div class='alert alert-warning' style='margin:0;'>
            <i class='fa fa-exclamation-triangle'></i>
            <strong>{$unassignedCount}</strong> DTEHM members have not been assigned to any shop.
            <a href='" . admin_url('dtehm-members') . "' class='btn btn-xs btn-warning' style='margin-left:10px;'>
                <i class='fa fa-pencil'></i> Assign Shops
            </a>
        </div>";

        $content->row(function (Row $row) use ($unassignedHtml) {
            $row->column(12, function (Column $col) use ($unassignedHtml) {
                $col->append(new Box('Unassigned Members', $unassignedHtml));
            });
        });

        return $content;
    }
}
