<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\OrderedItem;
use App\Models\AwardWithdrawal;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Support\Facades\DB;

class AwardsDashboardController extends AdminController
{
    protected $title = 'Awards Dashboard';

    // 1.2% of each sale goes into the awards pool
    const AWARD_RATE = 1.2;

    public function index(Content $content)
    {
        $content->title('🏆 Awards Dashboard')
            ->description('Awards commission pool and top performer tracking (1.2% per sale)');

        // ── Summary stats ───────────────────────────────────────────────────
        $totalPool       = (float) OrderedItem::sum('award_commission');
        $totalWithdrawn  = AwardWithdrawal::totalWithdrawn();
        $available       = $totalPool - $totalWithdrawn;
        $thisMonthPool   = OrderedItem::whereYear('created_at', now()->year)
                               ->whereMonth('created_at', now()->month)
                               ->sum('award_commission');
        $totalSalesCount = OrderedItem::count();

        // ── Top stat boxes ──────────────────────────────────────────────────
        $content->row(function (Row $row) use ($totalPool, $totalWithdrawn, $available, $thisMonthPool, $totalSalesCount) {
            $row->column(3, function (Column $col) use ($totalPool) {
                $col->append(new InfoBox('Total Awards Pool', 'fa-trophy', 'yellow', '#', 'UGX ' . number_format($totalPool, 0)));
            });
            $row->column(3, function (Column $col) use ($totalWithdrawn) {
                $col->append(new InfoBox('Total Spent on Awards', 'fa-gift', 'red', admin_url('award-withdrawals'), 'UGX ' . number_format($totalWithdrawn, 0)));
            });
            $row->column(3, function (Column $col) use ($available) {
                $color = $available > 0 ? 'green' : 'red';
                $col->append(new InfoBox('Available Balance', 'fa-bank', $color, admin_url('award-withdrawals/create'), 'UGX ' . number_format($available, 0)));
            });
            $row->column(3, function (Column $col) use ($thisMonthPool) {
                $col->append(new InfoBox('This Month\'s Pool', 'fa-calendar', 'aqua', '#', 'UGX ' . number_format($thisMonthPool, 0)));
            });
        });

        // ── Rate explanation ────────────────────────────────────────────────
        $rateHtml = "
        <div class='alert alert-info' style='margin:0;'>
            <i class='fa fa-info-circle'></i>
            <strong>Awards Commission = " . self::AWARD_RATE . "% of every sale amount.</strong>
            This fund is retained by the company to purchase awards and prizes for top performers
            based on their <strong>total points earned</strong> from product sales.
            A member earns <strong>1 point per product</strong> sold.
        </div>";

        $content->row(function (Row $row) use ($rateHtml) {
            $row->column(12, function (Column $col) use ($rateHtml) {
                $col->append(new Box('How Awards Commission Works', $rateHtml));
            });
        });

        // ── Top performers by points ────────────────────────────────────────
        $topByPoints = User::where('is_dtehm_member', 'Yes')
            ->where('total_points', '>', 0)
            ->orderByDesc('total_points')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'dtehm_member_id', 'total_points', 'shop_id']);

        $pointRows = '';
        foreach ($topByPoints as $i => $u) {
            $rank    = $i + 1;
            $medal   = $rank <= 3 ? ['🥇', '🥈', '🥉'][$i] : "#$rank";
            $shopName = $u->shop ? $u->shop->name : '<span class="text-muted">—</span>';
            $pointRows .= "<tr>
                <td><strong>{$medal}</strong></td>
                <td>{$u->dtehm_member_id}</td>
                <td><strong>{$u->first_name} {$u->last_name}</strong></td>
                <td>{$shopName}</td>
                <td class='text-right'><span class='badge' style='background:#f39c12;font-size:13px;'>{$u->total_points}</span></td>
            </tr>";
        }

        if (!$pointRows) {
            $pointRows = '<tr><td colspan="5" class="text-center text-muted">No points data yet</td></tr>';
        }

        $pointsTable = "
        <div class='table-responsive'>
        <table class='table table-hover table-bordered' style='font-size:13px;'>
            <thead style='background:#f4f6f9;'><tr>
                <th>#</th><th>DTEHM ID</th><th>Member Name</th><th>Shop</th><th class='text-right'>Total Points</th>
            </tr></thead>
            <tbody>{$pointRows}</tbody>
        </table></div>";

        // ── Top performers by sales amount ──────────────────────────────────
        $topBySales = DB::table('ordered_items')
            ->join('users', 'ordered_items.dtehm_user_id', '=', 'users.id')
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.dtehm_member_id',
                DB::raw('SUM(ordered_items.subtotal) as total_revenue'),
                DB::raw('SUM(ordered_items.award_commission) as award_contribution'),
                DB::raw('COUNT(ordered_items.id) as sale_count'))
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.dtehm_member_id')
            ->orderByDesc('total_revenue')
            ->limit(20)
            ->get();

        $salesRows = '';
        foreach ($topBySales as $i => $row) {
            $rank  = $i + 1;
            $medal = $rank <= 3 ? ['🥇', '🥈', '🥉'][$i] : "#$rank";
            $salesRows .= "<tr>
                <td><strong>{$medal}</strong></td>
                <td>{$row->dtehm_member_id}</td>
                <td><strong>{$row->first_name} {$row->last_name}</strong></td>
                <td class='text-right'>{$row->sale_count}</td>
                <td class='text-right'><strong>UGX " . number_format($row->total_revenue, 0) . "</strong></td>
                <td class='text-right' style='color:#f39c12;'>UGX " . number_format($row->award_contribution, 0) . "</td>
            </tr>";
        }

        if (!$salesRows) {
            $salesRows = '<tr><td colspan="6" class="text-center text-muted">No sales data yet</td></tr>';
        }

        $salesTable = "
        <div class='table-responsive'>
        <table class='table table-hover table-bordered' style='font-size:13px;'>
            <thead style='background:#f4f6f9;'><tr>
                <th>#</th><th>DTEHM ID</th><th>Member Name</th><th class='text-right'>Sales</th>
                <th class='text-right'>Revenue</th><th class='text-right'>Awards Contribution</th>
            </tr></thead>
            <tbody>{$salesRows}</tbody>
        </table></div>";

        $content->row(function (Row $row) use ($pointsTable, $salesTable) {
            $row->column(6, function (Column $col) use ($pointsTable) {
                $box = new Box('🏅 Top Performers by Points', $pointsTable);
                $box->style('warning');
                $col->append($box);
            });
            $row->column(6, function (Column $col) use ($salesTable) {
                $box = new Box('💰 Top Performers by Sales', $salesTable);
                $box->style('success');
                $col->append($box);
            });
        });

        // ── Monthly breakdown ───────────────────────────────────────────────
        $monthly = DB::table('ordered_items')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, DATE_FORMAT(created_at, '%b %Y') as month_label, COUNT(*) as sales, SUM(subtotal) as revenue, SUM(award_commission) as award_pool")
            ->whereYear('created_at', '>=', now()->subYears(1)->year)
            ->groupBy('month_key', 'month_label')
            ->orderBy('month_key', 'desc')
            ->limit(12)
            ->get();

        $monthRows = '';
        foreach ($monthly as $m) {
            $monthRows .= "<tr>
                <td>{$m->month_label}</td>
                <td class='text-right'>{$m->sales}</td>
                <td class='text-right'>UGX " . number_format($m->revenue, 0) . "</td>
                <td class='text-right' style='color:#f39c12;font-weight:600;'>UGX " . number_format($m->award_pool, 0) . "</td>
            </tr>";
        }

        $monthTable = "
        <div class='table-responsive'>
        <table class='table table-hover table-bordered' style='font-size:13px;'>
            <thead style='background:#f4f6f9;'><tr>
                <th>Month</th><th class='text-right'>Sales</th><th class='text-right'>Revenue</th><th class='text-right'>Awards Pool</th>
            </tr></thead>
            <tbody>{$monthRows}</tbody>
        </table></div>";

        $content->row(function (Row $row) use ($monthTable) {
            $row->column(12, function (Column $col) use ($monthTable) {
                $box = new Box('📅 Monthly Awards Pool Breakdown', $monthTable);
                $box->style('info');
                $col->append($box);
            });
        });

        // ── Withdrawal / award spend history ────────────────────────────────
        $withdrawals = AwardWithdrawal::with('creator')->orderByDesc('withdrawal_date')->limit(50)->get();

        $wRows = '';
        foreach ($withdrawals as $w) {
            $creator = optional($w->creator)->name ?? '—';
            $wRows .= "<tr>
                <td>" . date('d M Y', strtotime($w->withdrawal_date)) . "</td>
                <td><span class='label label-warning'>" . ($w->award_name ?: '—') . "</span></td>
                <td><strong style='color:#f39c12;'>UGX " . number_format($w->amount, 0) . "</strong></td>
                <td>" . \Illuminate\Support\Str::limit($w->description, 70) . "</td>
                <td>" . ($w->recipient ?: '<span class="text-muted">—</span>') . "</td>
                <td><small class='text-muted'>{$creator}</small></td>
            </tr>";
        }

        if (!$wRows) {
            $wRows = '<tr><td colspan="6" class="text-center text-muted" style="padding:20px;">No awards recorded yet. <a href="' . admin_url('award-withdrawals/create') . '">Record first award purchase</a></td></tr>';
        }

        $wHtml = "
        <div style='margin-bottom:10px;'>
            <a href='" . admin_url('award-withdrawals/create') . "' class='btn btn-warning btn-sm'>
                <i class='fa fa-plus'></i> Record New Award Purchase
            </a>
            <a href='" . admin_url('award-withdrawals') . "' class='btn btn-default btn-sm' style='margin-left:5px;'>
                <i class='fa fa-list'></i> View All Award Records
            </a>
        </div>
        <div class='table-responsive'>
        <table class='table table-hover table-bordered' style='font-size:13px;'>
            <thead style='background:#f4f6f9;'><tr>
                <th>Date</th><th>Award / Prize</th><th>Amount</th><th>Description</th><th>Recipient / Winner</th><th>Recorded By</th>
            </tr></thead>
            <tbody>{$wRows}</tbody>
        </table></div>";

        $content->row(function (Row $row) use ($wHtml) {
            $row->column(12, function (Column $col) use ($wHtml) {
                $box = new Box('🎁 Awards & Prizes History', $wHtml);
                $box->style('warning');
                $col->append($box);
            });
        });

        return $content;
    }
}
