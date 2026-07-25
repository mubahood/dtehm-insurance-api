<?php

namespace App\Admin\Controllers;

use App\Models\OrderedItem;
use App\Models\ShareholderWithdrawal;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Support\Facades\DB;

class ShareholdersDashboardController extends AdminController
{
    protected $title = 'Shareholders Dashboard';

    const SHAREHOLDER_RATE = 1.5;

    public function index(Content $content)
    {
        $content->title('📊 Shareholders Dashboard')
            ->description('Shareholders commission pool tracking (1.5% per sale)');

        // ── Summary stats ───────────────────────────────────────────────────
        $totalPool      = (float) OrderedItem::sum('shareholder_commission');
        $totalWithdrawn = ShareholderWithdrawal::totalWithdrawn();
        $available      = $totalPool - $totalWithdrawn;
        $thisMonthPool  = OrderedItem::whereYear('created_at', now()->year)
                              ->whereMonth('created_at', now()->month)
                              ->sum('shareholder_commission');
        $lastMonthPool  = OrderedItem::whereYear('created_at', now()->subMonth()->year)
                              ->whereMonth('created_at', now()->subMonth()->month)
                              ->sum('shareholder_commission');

        $growth = $lastMonthPool > 0
            ? round((($thisMonthPool - $lastMonthPool) / $lastMonthPool) * 100, 1)
            : 0;

        // ── Top stat boxes — row 1: pool + withdrawals ──────────────────────
        $content->row(function (Row $row) use ($totalPool, $totalWithdrawn, $available, $thisMonthPool) {
            $row->column(3, function (Column $col) use ($totalPool) {
                $col->append(new InfoBox('Total Pool Collected', 'fa-pie-chart', 'aqua', '#', 'UGX ' . number_format($totalPool, 0)));
            });
            $row->column(3, function (Column $col) use ($totalWithdrawn) {
                $col->append(new InfoBox('Total Withdrawn', 'fa-minus-circle', 'red', admin_url('shareholder-withdrawals'), 'UGX ' . number_format($totalWithdrawn, 0)));
            });
            $row->column(3, function (Column $col) use ($available) {
                $color = $available > 0 ? 'green' : 'red';
                $col->append(new InfoBox('Available Balance', 'fa-bank', $color, admin_url('shareholder-withdrawals/create'), 'UGX ' . number_format($available, 0)));
            });
            $row->column(3, function (Column $col) use ($thisMonthPool) {
                $col->append(new InfoBox('This Month\'s Pool', 'fa-calendar-check-o', 'yellow', '#', 'UGX ' . number_format($thisMonthPool, 0)));
            });
        });

        // ── Rate explanation ────────────────────────────────────────────────
        $rateHtml = "
        <div class='alert alert-info' style='margin:0;'>
            <i class='fa fa-info-circle'></i>
            <strong>Shareholders Commission = " . self::SHAREHOLDER_RATE . "% of every sale amount.</strong>
            This fund is retained by the company and distributed periodically to registered shareholders
            based on their shareholding proportion. It is computed transparently from every sale
            alongside member commissions.
        </div>";

        $content->row(function (Row $row) use ($rateHtml) {
            $row->column(12, function (Column $col) use ($rateHtml) {
                $col->append(new Box('How Shareholders Commission Works', $rateHtml));
            });
        });

        // ── Monthly breakdown ───────────────────────────────────────────────
        $monthly = DB::table('ordered_items')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, DATE_FORMAT(created_at, '%b %Y') as month_label,
                         COUNT(*) as sales, SUM(subtotal) as revenue,
                         SUM(award_commission) as award_pool,
                         SUM(shareholder_commission) as shareholder_pool,
                         SUM(total_commission_amount) as member_commissions")
            ->groupBy('month_key', 'month_label')
            ->orderBy('month_key', 'desc')
            ->limit(24)
            ->get();

        $monthRows = '';
        $cumulativePool = 0;
        foreach (array_reverse($monthly->toArray()) as $m) {
            $cumulativePool += $m->shareholder_pool;
        }

        // Rebuild for display (most-recent first)
        foreach ($monthly as $m) {
            $pct = $m->revenue > 0 ? round($m->shareholder_pool / $m->revenue * 100, 2) : 0;
            $monthRows .= "<tr>
                <td>{$m->month_label}</td>
                <td class='text-right'>{$m->sales}</td>
                <td class='text-right'>UGX " . number_format($m->revenue, 0) . "</td>
                <td class='text-right' style='color:#3c8dbc;font-weight:600;'>UGX " . number_format($m->shareholder_pool, 0) . "</td>
                <td class='text-right' style='color:#f39c12;'>UGX " . number_format($m->award_pool, 0) . "</td>
                <td class='text-right' style='color:#00a65a;'>UGX " . number_format($m->member_commissions, 0) . "</td>
                <td class='text-right'>{$pct}%</td>
            </tr>";
        }

        $monthTable = "
        <div class='table-responsive'>
        <table class='table table-hover table-bordered' style='font-size:13px;'>
            <thead style='background:#f4f6f9;'><tr>
                <th>Month</th><th class='text-right'>Sales</th><th class='text-right'>Revenue</th>
                <th class='text-right'>Shareholders Pool</th><th class='text-right'>Awards Pool</th>
                <th class='text-right'>Member Commissions</th><th class='text-right'>% of Revenue</th>
            </tr></thead>
            <tbody>{$monthRows}</tbody>
            <tfoot style='background:#f4f6f9;font-weight:bold;'>
                <tr>
                    <td colspan='3' class='text-right'>All-time Totals:</td>
                    <td class='text-right' style='color:#3c8dbc;'>UGX " . number_format($totalPool, 0) . "</td>
                    <td class='text-right' style='color:#f39c12;'>UGX " . number_format(OrderedItem::sum('award_commission'), 0) . "</td>
                    <td class='text-right' style='color:#00a65a;'>UGX " . number_format(OrderedItem::sum('total_commission_amount'), 0) . "</td>
                    <td></td>
                </tr>
            </tfoot>
        </table></div>";

        $content->row(function (Row $row) use ($monthTable) {
            $row->column(12, function (Column $col) use ($monthTable) {
                $box = new Box('📅 Monthly Breakdown — All Commission Pools', $monthTable);
                $box->style('primary');
                $col->append($box);
            });
        });

        // ── Commission pool summary ─────────────────────────────────────────
        $totalAward      = OrderedItem::sum('award_commission');
        $totalMember     = OrderedItem::sum('total_commission_amount');
        $totalRevenue    = OrderedItem::sum('subtotal');
        $totalRetained   = $totalRevenue - $totalMember - $totalPool - $totalAward;

        $summaryHtml = "
        <table class='table table-bordered' style='font-size:14px;'>
            <tbody>
                <tr><td><strong>Total Revenue (all sales)</strong></td><td class='text-right'><strong>UGX " . number_format($totalRevenue, 0) . "</strong></td></tr>
                <tr><td style='padding-left:20px;color:#00a65a;'>&nbsp;↳ Member Commissions (Stockist 7% + Sponsor 8% + GN1-10)</td><td class='text-right' style='color:#00a65a;'>UGX " . number_format($totalMember, 0) . "</td></tr>
                <tr><td style='padding-left:20px;color:#f39c12;'>&nbsp;↳ Awards Pool (1.2%)</td><td class='text-right' style='color:#f39c12;'>UGX " . number_format($totalAward, 0) . "</td></tr>
                <tr><td style='padding-left:20px;color:#3c8dbc;'>&nbsp;↳ Shareholders Pool (1.5%)</td><td class='text-right' style='color:#3c8dbc;'>UGX " . number_format($totalPool, 0) . "</td></tr>
                <tr style='background:#f4f6f9;'><td><strong>Net Company Retained</strong></td><td class='text-right'><strong>UGX " . number_format($totalRetained, 0) . "</strong></td></tr>
            </tbody>
        </table>";

        $content->row(function (Row $row) use ($summaryHtml) {
            $row->column(12, function (Column $col) use ($summaryHtml) {
                $box = new Box('💼 Revenue Distribution Summary', $summaryHtml);
                $box->style('info');
                $col->append($box);
            });
        });

        // ── Withdrawal history ──────────────────────────────────────────────
        $withdrawals = ShareholderWithdrawal::with('creator')->orderByDesc('withdrawal_date')->limit(50)->get();

        $wRows = '';
        foreach ($withdrawals as $w) {
            $creator = optional($w->creator)->name ?? '—';
            $wRows .= "<tr>
                <td>" . date('d M Y', strtotime($w->withdrawal_date)) . "</td>
                <td><strong style='color:#dd4b39;'>UGX " . number_format($w->amount, 0) . "</strong></td>
                <td>" . \Illuminate\Support\Str::limit($w->description, 80) . "</td>
                <td>" . ($w->recipient ?: '<span class="text-muted">—</span>') . "</td>
                <td>" . ($w->reference_number ? "<span class='label label-default'>{$w->reference_number}</span>" : '<span class="text-muted">—</span>') . "</td>
                <td><small class='text-muted'>{$creator}</small></td>
            </tr>";
        }

        if (!$wRows) {
            $wRows = '<tr><td colspan="6" class="text-center text-muted" style="padding:20px;">No withdrawals recorded yet. <a href="' . admin_url('shareholder-withdrawals/create') . '">Record first withdrawal</a></td></tr>';
        }

        $wHtml = "
        <div style='margin-bottom:10px;'>
            <a href='" . admin_url('shareholder-withdrawals/create') . "' class='btn btn-danger btn-sm'>
                <i class='fa fa-plus'></i> Record New Withdrawal
            </a>
            <a href='" . admin_url('shareholder-withdrawals') . "' class='btn btn-default btn-sm' style='margin-left:5px;'>
                <i class='fa fa-list'></i> View All Withdrawals
            </a>
        </div>
        <div class='table-responsive'>
        <table class='table table-hover table-bordered' style='font-size:13px;'>
            <thead style='background:#f4f6f9;'><tr>
                <th>Date</th><th>Amount</th><th>Description</th><th>Recipient</th><th>Reference</th><th>Recorded By</th>
            </tr></thead>
            <tbody>{$wRows}</tbody>
        </table></div>";

        $content->row(function (Row $row) use ($wHtml) {
            $row->column(12, function (Column $col) use ($wHtml) {
                $box = new Box('🏦 Shareholders Withdrawal History', $wHtml);
                $box->style('danger');
                $col->append($box);
            });
        });

        return $content;
    }
}
