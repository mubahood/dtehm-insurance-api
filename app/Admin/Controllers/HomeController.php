<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Admin\Helpers\RoleBasedDashboard;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\ProjectTransaction;
use App\Models\Disbursement;
use App\Models\AccountTransaction;
use App\Models\InsuranceProgram;
use App\Models\InsuranceSubscription;
use App\Models\InsuranceSubscriptionPayment;
use App\Models\MedicalServiceRequest;
use App\Models\Product;
use App\Models\UniversalPayment;
use App\Models\MembershipPayment;
use Carbon\Carbon;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    use RoleBasedDashboard;

    public function index(Content $content)
    {
        // Add Chart.js CDN
        Admin::script('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js');
        
        $content = $content
            ->title($this->getDashboardTitle())
            ->description($this->getDashboardDescription())
            ->row(function (Row $row) {
                // SECTION 1: KEY PERFORMANCE INDICATORS (4 columns) - ALL USERS
                $this->addKPISection($row);
            });

        // ADMIN ONLY SECTIONS - Detailed financial and analytics data
        if ($this->canSeeDetailedAnalytics()) {
            $content->row(function (Row $row) {
                // SECTION 2: REVENUE & FINANCIAL TRENDS (Chart)
                $this->addRevenueCharts($row);
            });
        }

        if ($this->canSeeFinancialDetails()) {
            $content->row(function (Row $row) {
                // SECTION 3: FINANCIAL OVERVIEW (2 columns)
                $this->addFinancialOverview($row);
            })
            ->row(function (Row $row) {
                // SECTION 4: PROJECT ANALYTICS WITH CHARTS (2 columns)
                $this->addProjectAnalytics($row);
            });
        }



        return $content;
    }

    /**
     * SECTION 1: Key Performance Indicators
     */
    private function addKPISection(Row $row)
    {
        // Product Sales Revenue
        $row->column(3, function (Column $column) {
            $totalSales = \App\Models\OrderedItem::sum('subtotal');
            $monthSales = \App\Models\OrderedItem::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('subtotal');
            $salesCount = \App\Models\OrderedItem::count();
            
            $content = "
                <div style='background: #2c3e50; padding: 20px; color: white; min-height: 140px; border-radius: 4px;'>
                    <div style='text-align: center;'>
                        <div style='font-size: 13px; margin-bottom: 8px; opacity: 0.9;'>Product Sales Revenue</div>
                        <h2 style='margin: 0; font-size: 28px; font-weight: bold;'>UGX " . number_format($totalSales, 0) . "</h2>
                        <div style='margin-top: 10px; font-size: 12px; opacity: 0.85;'>
                            This Month: UGX " . number_format($monthSales, 0) . "<br>
                            Total Sales: {$salesCount}
                        </div>
                    </div>
                </div>
            ";
            
            $box = new Box('Sales Revenue', $content);
            $column->append($box);
        });

        // Total Commissions Paid
        $row->column(3, function (Column $column) {
            $totalCommissions = \App\Models\OrderedItem::where('commission_is_processed', 'Yes')
                ->sum('total_commission_amount');
            $pendingCommissions = \App\Models\OrderedItem::where('commission_is_processed', 'No')
                ->sum('subtotal');
            $processedCount = \App\Models\OrderedItem::where('commission_is_processed', 'Yes')->count();
            
            $content = "
                <div style='background: #27ae60; padding: 20px; color: white; min-height: 140px; border-radius: 4px;'>
                    <div style='text-align: center;'>
                        <div style='font-size: 13px; margin-bottom: 8px; opacity: 0.9;'>Commissions Paid</div>
                        <h2 style='margin: 0; font-size: 28px; font-weight: bold;'>UGX " . number_format($totalCommissions, 0) . "</h2>
                        <div style='margin-top: 10px; font-size: 12px; opacity: 0.85;'>
                            Pending: UGX " . number_format($pendingCommissions * 0.10, 0) . "<br>
                            Processed Sales: {$processedCount}
                        </div>
                    </div>
                </div>
            ";
            
            $box = new Box('Commissions', $content);
            $column->append($box);
        });

        // Account Balances
        $row->column(3, function (Column $column) {
            $totalBalance = AccountTransaction::sum('amount');
            $usersWithBalance = AccountTransaction::distinct('user_id')->count();
            $thisMonthTransactions = AccountTransaction::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('amount');
            
            $content = "
                <div style='background: #e67e22; padding: 20px; color: white; min-height: 140px; border-radius: 4px;'>
                    <div style='text-align: center;'>
                        <div style='font-size: 13px; margin-bottom: 8px; opacity: 0.9;'>Total Account Balances</div>
                        <h2 style='margin: 0; font-size: 28px; font-weight: bold;'>UGX " . number_format($totalBalance, 0) . "</h2>
                        <div style='margin-top: 10px; font-size: 12px; opacity: 0.85;'>
                            This Month: UGX " . number_format($thisMonthTransactions, 0) . "<br>
                            Active Accounts: {$usersWithBalance}
                        </div>
                    </div>
                </div>
            ";
            
            $box = new Box('Account Balances', $content);
            $column->append($box);
        });

        // DTEHM Members
        $row->column(3, function (Column $column) {
            $dtehmMembers = User::where('is_dtehm_member', 'Yes')->count();
            $newThisMonth = User::where('is_dtehm_member', 'Yes')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();
            $totalMembership = \App\Models\DtehmMembership::where('status', 'CONFIRMED')->sum('amount');
            
            $content = "
                <div style='background: #8e44ad; padding: 20px; color: white; min-height: 140px; border-radius: 4px;'>
                    <div style='text-align: center;'>
                        <div style='font-size: 13px; margin-bottom: 8px; opacity: 0.9;'>DTEHM Members</div>
                        <h2 style='margin: 0; font-size: 28px; font-weight: bold;'>{$dtehmMembers}</h2>
                        <div style='margin-top: 10px; font-size: 12px; opacity: 0.85;'>
                            New This Month: {$newThisMonth}<br>
                            Membership Revenue: UGX " . number_format($totalMembership, 0) . "
                        </div>
                    </div>
                </div>
            ";
            
            $box = new Box('DTEHM Members', $content);
            $column->append($box);
        });
    }

    /**
     * SECTION 2: Sales & Commission Trends (Charts)
     */
    private function addRevenueCharts(Row $row)
    {
        // Sales Trend Chart (Last 6 months)
        $row->column(8, function (Column $column) {
            $months = [];
            $sales = [];
            $commissions = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $months[] = $date->format('M Y');
                
                $monthSales = \App\Models\OrderedItem::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('subtotal');
                $sales[] = $monthSales;
                
                $monthCommissions = \App\Models\OrderedItem::where('commission_is_processed', 'Yes')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('total_commission_amount');
                $commissions[] = $monthCommissions;
            }
            
            $content = "
                <canvas id='salesChart' height='80'></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('salesChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: " . json_encode($months) . ",
                                datasets: [{
                                    label: 'Sales Revenue',
                                    data: " . json_encode($sales) . ",
                                    backgroundColor: '#2c3e50',
                                    borderWidth: 0
                                }, {
                                    label: 'Commissions Paid',
                                    data: " . json_encode($commissions) . ",
                                    backgroundColor: '#27ae60',
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        labels: {
                                            font: { size: 13 },
                                            padding: 15
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Sales Revenue vs Commissions (Last 6 Months)',
                                        font: { size: 15, weight: 'bold' }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return 'UGX ' + value.toLocaleString();
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
                </script>
            ";
            
            $box = new Box('Sales & Commission Trends', $content);
            $column->append($box);
        });

        // Top Selling Products
        $row->column(4, function (Column $column) {
            $topProducts = \App\Models\OrderedItem::select('product', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
                ->groupBy('product')
                ->orderBy('total_revenue', 'desc')
                ->limit(5)
                ->get();
            
            $productNames = [];
            $revenues = [];
            
            foreach ($topProducts as $item) {
                $product = \App\Models\Product::find($item->product);
                $productNames[] = $product ? \Illuminate\Support\Str::limit($product->name, 20) : "Product #{$item->product}";
                $revenues[] = $item->total_revenue;
            }
            
            $content = "
                <canvas id='topProductsChart' height='200'></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('topProductsChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: " . json_encode($productNames) . ",
                                datasets: [{
                                    data: " . json_encode($revenues) . ",
                                    backgroundColor: ['#2c3e50', '#34495e', '#7f8c8d', '#95a5a6', '#bdc3c7'],
                                    borderWidth: 2,
                                    borderColor: '#fff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            font: { size: 11 },
                                            padding: 8
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Top 5 Products by Revenue',
                                        font: { size: 14, weight: 'bold' }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return context.label + ': UGX ' + context.parsed.toLocaleString();
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
                </script>
            ";
            
            $box = new Box('Top Products', $content);
            $column->append($box);
        });
    }

    /**
     * SECTION 3: Account Transactions & Commission Overview
     */
    private function addFinancialOverview(Row $row)
    {
        $row->column(6, function (Column $column) {
            // Account Transaction Summary by Source
            $transactions = AccountTransaction::select('source', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('source')
                ->orderBy('total', 'desc')
                ->get();
            
            $totalBalance = AccountTransaction::sum('amount');
            
            $content = "
                <table class='table table-bordered' style='margin-bottom:0; font-size: 13px;'>
                    <thead style='background: #2c3e50; color: white;'>
                        <tr>
                            <th>Transaction Source</th>
                            <th class='text-right'>Total Amount</th>
                            <th class='text-center'>Count</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($transactions as $trans) {
                $sourceName = ucwords(str_replace('_', ' ', $trans->source));
                $content .= "
                    <tr>
                        <td>{$sourceName}</td>
                        <td class='text-right'><strong>UGX " . number_format($trans->total, 0) . "</strong></td>
                        <td class='text-center'><span class='badge' style='background: #95a5a6;'>{$trans->count}</span></td>
                    </tr>";
            }
            
            $content .= "
                    </tbody>
                    <tfoot style='background: #ecf0f1; font-weight: bold;'>
                        <tr>
                            <td>Total Balance</td>
                            <td class='text-right' style='font-size: 15px;'>UGX " . number_format($totalBalance, 0) . "</td>
                            <td class='text-center'>" . AccountTransaction::count() . "</td>
                        </tr>
                    </tfoot>
                </table>
            ";
            
            $box = new Box('Account Transactions by Source', $content);
            $column->append($box);
        });

        $row->column(6, function (Column $column) {
            // Commission Breakdown by Level
            $commissionData = [
                'Stockist (7%)' => \App\Models\OrderedItem::where('commission_is_processed', 'Yes')->sum('commission_stockist'),
                'Sponsor (8%)' => \App\Models\OrderedItem::where('commission_is_processed', 'Yes')->sum('commission_seller'),
                'Parent 1 (3%)' => \App\Models\OrderedItem::where('commission_is_processed', 'Yes')->sum('commission_parent_1'),
                'Parent 2 (2.5%)' => \App\Models\OrderedItem::where('commission_is_processed', 'Yes')->sum('commission_parent_2'),
                'Parent 3 (2%)' => \App\Models\OrderedItem::where('commission_is_processed', 'Yes')->sum('commission_parent_3'),
                'Parent 4 (1.5%)' => \App\Models\OrderedItem::where('commission_is_processed', 'Yes')->sum('commission_parent_4'),
                'Parent 5 (1%)' => \App\Models\OrderedItem::where('commission_is_processed', 'Yes')->sum('commission_parent_5'),
            ];
            
            $totalCommissions = array_sum($commissionData);
            $processedSales = \App\Models\OrderedItem::where('commission_is_processed', 'Yes')->count();
            $pendingSales = \App\Models\OrderedItem::where('commission_is_processed', 'No')->count();
            
            $content = "
                <table class='table table-bordered' style='margin-bottom:0; font-size: 13px;'>
                    <thead style='background: #27ae60; color: white;'>
                        <tr>
                            <th>Commission Level</th>
                            <th class='text-right'>Total Paid</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($commissionData as $level => $amount) {
                if ($amount > 0) {
                    $content .= "
                        <tr>
                            <td>{$level}</td>
                            <td class='text-right'><strong>UGX " . number_format($amount, 0) . "</strong></td>
                        </tr>";
                }
            }
            
            $content .= "
                    </tbody>
                    <tfoot style='background: #ecf0f1;'>
                        <tr style='font-weight: bold;'>
                            <td>Total Commissions Paid</td>
                            <td class='text-right' style='font-size: 15px;'>UGX " . number_format($totalCommissions, 0) . "</td>
                        </tr>
                        <tr>
                            <td>Processed Sales</td>
                            <td class='text-right'><span class='badge' style='background: #27ae60;'>{$processedSales}</span></td>
                        </tr>
                        <tr>
                            <td>Pending Processing</td>
                            <td class='text-right'><span class='badge' style='background: #e67e22;'>{$pendingSales}</span></td>
                        </tr>
                    </tfoot>
                </table>
            ";
            
            $box = new Box('Commission Breakdown by Level', $content);
            $column->append($box);
        });
    }

    /**
     * SECTION 4: Recent Sales & Top Performers
     */
    private function addProjectAnalytics(Row $row)
    {
        $row->column(6, function (Column $column) {
            $recentSales = \App\Models\OrderedItem::orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            $content = "
                <div style='max-height: 450px; overflow-y: auto;'>
                    <table class='table table-hover' style='margin-bottom:0; font-size: 12px;'>
                        <thead style='background: #2c3e50; color: white; position: sticky; top: 0;'>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th class='text-center'>Qty</th>
                                <th class='text-right'>Amount</th>
                                <th>Sponsor</th>
                            </tr>
                        </thead>
                        <tbody>";
            
            foreach ($recentSales as $sale) {
                $product = Product::find($sale->product);
                $productName = $product ? \Illuminate\Support\Str::limit($product->name, 25) : "Product #{$sale->product}";
                $sponsor = User::find($sale->sponsor_user_id);
                $sponsorName = $sponsor ? \Illuminate\Support\Str::limit($sponsor->name, 20) : '-';
                $commissionBadge = $sale->commission_is_processed === 'Yes' 
                    ? '<span class="label label-success" style="font-size: 9px;">Paid</span>' 
                    : '<span class="label label-warning" style="font-size: 9px;">Pending</span>';
                
                $content .= "
                    <tr>
                        <td><small>" . $sale->created_at->format('d M, H:i') . "</small></td>
                        <td><strong>{$productName}</strong></td>
                        <td class='text-center'>{$sale->qty}</td>
                        <td class='text-right'><strong>UGX " . number_format($sale->subtotal, 0) . "</strong></td>
                        <td><small>{$sponsorName}</small> {$commissionBadge}</td>
                    </tr>";
            }
            
            $content .= "
                        </tbody>
                    </table>
                </div>
            ";
            
            $box = new Box('Recent Product Sales', $content);
            $column->append($box);
        });

        // Top Sponsors by Commission Earned
        $row->column(6, function (Column $column) {
            $topSponsors = \App\Models\OrderedItem::select('sponsor_user_id', DB::raw('SUM(commission_seller) as total_commission'), DB::raw('COUNT(*) as sales_count'))
                ->where('commission_is_processed', 'Yes')
                ->whereNotNull('sponsor_user_id')
                ->groupBy('sponsor_user_id')
                ->orderBy('total_commission', 'desc')
                ->limit(10)
                ->get();
            
            $content = "
                <div style='max-height: 450px; overflow-y: auto;'>
                    <table class='table table-hover' style='margin-bottom:0; font-size: 12px;'>
                        <thead style='background: #27ae60; color: white; position: sticky; top: 0;'>
                            <tr>
                                <th>Rank</th>
                                <th>Sponsor Name</th>
                                <th class='text-center'>Sales</th>
                                <th class='text-right'>Commission Earned</th>
                            </tr>
                        </thead>
                        <tbody>";
            
            $rank = 1;
            foreach ($topSponsors as $item) {
                $sponsor = User::find($item->sponsor_user_id);
                $sponsorName = $sponsor ? $sponsor->name : "User #{$item->sponsor_user_id}";
                $memberId = $sponsor && $sponsor->dtehm_member_id ? " ({$sponsor->dtehm_member_id})" : '';
                
                $content .= "
                    <tr>
                        <td><strong>#{$rank}</strong></td>
                        <td><strong>" . \Illuminate\Support\Str::limit($sponsorName, 25) . "</strong><br><small style='color: #7f8c8d;'>{$memberId}</small></td>
                        <td class='text-center'><span class='badge' style='background: #95a5a6;'>{$item->sales_count}</span></td>
                        <td class='text-right'><strong style='color: #27ae60;'>UGX " . number_format($item->total_commission, 0) . "</strong></td>
                    </tr>";
                $rank++;
            }
            
            $content .= "
                        </tbody>
                    </table>
                </div>
            ";
            
            $box = new Box('Top 10 Sponsors by Commission', $content);
            $column->append($box);
        });
    }
            
            $content = "
                <canvas id='investmentChart' height='250'></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('investmentChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: " . json_encode($projectNames) . ",
                                datasets: [{
                                    label: 'Total Investment (UGX)',
                                    data: " . json_encode($investmentAmounts) . ",
                                    backgroundColor: [
                                        'rgba(5, 23, 159, 0.9)',
                                        'rgba(5, 23, 159, 0.8)',
                                        'rgba(5, 23, 159, 0.7)',
                                        'rgba(5, 23, 159, 0.6)',
                                        'rgba(5, 23, 159, 0.5)',
                                        'rgba(5, 23, 159, 0.4)'
                                    ],
                                    borderColor: '#fff',
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            font: { size: 12 },
                                            padding: 10
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Investment Distribution by Project',
                                        font: { size: 14, weight: 'bold' }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.label || '';
                                                if (label) {
                                                    label += ': ';
                                                }
                                                if (context.parsed !== null) {
                                                    label += 'UGX ' + context.parsed.toLocaleString();
                                                }
                                                return label;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
                </script>
            ";
            
            $box = new Box('💼 Investment Distribution', $content);
            $column->append($box);
        });
    }

    /**
     * SECTION 6: Payment Gateway Statistics
     */
    private function addPaymentGatewayStats(Row $row)
    {
        // Universal Payments Overview
        $row->column(4, function (Column $column) {
            $paymentStats = [
                'total' => UniversalPayment::count(),
                'completed' => UniversalPayment::where('status', 'COMPLETED')->count(),
                'pending' => UniversalPayment::where('status', 'PENDING')->count(),
                'failed' => UniversalPayment::where('status', 'FAILED')->count(),
                'total_amount' => UniversalPayment::sum('amount'),
                'completed_amount' => UniversalPayment::where('status', 'COMPLETED')->sum('amount'),
            ];
            
            $content = "
                <div style='padding: 10px;'>
                    <div class='row'>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #e8f5e9; border: 1px solid #e0e0e0; margin-bottom: 10px; border-radius: 4px;'>
                                <h2 style='margin: 0; color: #4caf50;'>" . $paymentStats['completed'] . "</h2>
                                <small>Completed</small>
                            </div>
                        </div>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #fff3e0; border: 1px solid #e0e0e0; margin-bottom: 10px; border-radius: 4px;'>
                                <h2 style='margin: 0; color: #ff9800;'>" . $paymentStats['pending'] . "</h2>
                                <small>Pending</small>
                            </div>
                        </div>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #ffebee; border: 1px solid #e0e0e0; border-radius: 4px;'>
                                <h2 style='margin: 0; color: #f44336;'>" . $paymentStats['failed'] . "</h2>
                                <small>Failed</small>
                            </div>
                        </div>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #e3f2fd; border: 1px solid #e0e0e0; border-radius: 4px;'>
                                <h2 style='margin: 0; color: #2196f3;'>" . $paymentStats['total'] . "</h2>
                                <small>Total</small>
                            </div>
                        </div>
                    </div>
                    <div style='text-align: center; margin-top: 15px; padding: 15px; background: #05179F; border-radius: 4px; color: white;'>
                        <h3 style='margin: 0;'>UGX " . number_format($paymentStats['completed_amount'], 0) . "</h3>
                        <small>Total Collected</small>
                    </div>
                </div>
            ";
            
            $box = new Box('💳 Universal Payments', $content);
            $column->append($box);
        });

        // Payment Gateway Distribution (Pie Chart)
        $row->column(4, function (Column $column) {
            $gatewayStats = UniversalPayment::select('payment_gateway', DB::raw('count(*) as count'))
                ->groupBy('payment_gateway')
                ->get();
            
            $gateways = [];
            $counts = [];
            $colors = [
                'rgba(5, 23, 159, 0.9)',
                'rgba(5, 23, 159, 0.7)',
                'rgba(5, 23, 159, 0.5)',
                'rgba(5, 23, 159, 0.3)',
                '#ff9800',
                '#f44336'
            ];
            
            foreach ($gatewayStats as $index => $gateway) {
                $gateways[] = ucfirst($gateway->payment_gateway ?? 'Unknown');
                $counts[] = $gateway->count;
            }
            
            $content = "
                <canvas id='gatewayChart' height='250'></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('gatewayChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: " . json_encode($gateways) . ",
                                datasets: [{
                                    data: " . json_encode($counts) . ",
                                    backgroundColor: " . json_encode(array_slice($colors, 0, count($gateways))) . ",
                                    borderWidth: 2,
                                    borderColor: '#fff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            font: { size: 12 },
                                            padding: 10
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Payment Gateway Distribution',
                                        font: { size: 14, weight: 'bold' }
                                    }
                                }
                            }
                        });
                    }
                });
                </script>
            ";
            
            $box = new Box('🌐 Payment Gateways', $content);
            $column->append($box);
        });

        // Payment Type Distribution
        $row->column(4, function (Column $column) {
            $typeStats = UniversalPayment::select('payment_type', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->where('status', 'COMPLETED')
                ->groupBy('payment_type')
                ->get();
            
            $content = "
                <table class='table table-sm table-hover' style='margin-bottom:0; font-size: 13px;'>
                    <thead style='background: #f5f5f5;'>
                        <tr>
                            <th>Payment Type</th>
                            <th class='text-center'>Count</th>
                            <th class='text-right'>Amount</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($typeStats as $type) {
                $typeLabel = str_replace('_', ' ', $type->payment_type);
                $content .= "
                    <tr>
                        <td><strong>" . ucwords(strtolower($typeLabel)) . "</strong></td>
                        <td class='text-center'><span class='badge bg-primary'>" . $type->count . "</span></td>
                        <td class='text-right'><strong>UGX " . number_format($type->total, 0) . "</strong></td>
                    </tr>";
            }
            
            $total_count = $typeStats->sum('count');
            $total_amount = $typeStats->sum('total');
            
            $content .= "
                    </tbody>
                    <tfoot style='background: #f5f5f5; font-weight: bold;'>
                        <tr>
                            <td>Total</td>
                            <td class='text-center'><span class='badge bg-success'>{$total_count}</span></td>
                            <td class='text-right'>UGX " . number_format($total_amount, 0) . "</td>
                        </tr>
                    </tfoot>
                </table>
            ";
            
            $box = new Box('📊 Payment Types', $content);
            $column->append($box);
        });
    }

    /**
     * SECTION 5: Insurance System Overview
     */
    private function addInsuranceOverview(Row $row)
    {
        $row->column(4, function (Column $column) {
            $programs = InsuranceProgram::withCount('subscriptions')
                ->orderBy('subscriptions_count', 'desc')
                ->get();
            
            $content = "
                <table class='table table-sm table-hover' style='margin-bottom:0;'>
                    <thead style='background: #f0f0f0;'>
                        <tr>
                            <th>Program</th>
                            <th class='text-center'>Status</th>
                            <th class='text-right'>Subscribers</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($programs as $program) {
                $statusClass = $program->status === 'Active' ? 'success' : 'default';
                $content .= "
                    <tr>
                        <td><strong>" . $program->name . "</strong><br><small>UGX " . number_format($program->premium_amount, 0) . "/" . $program->billing_frequency . "</small></td>
                        <td class='text-center'><span class='label label-{$statusClass}'>" . $program->status . "</span></td>
                        <td class='text-right'><span class='badge bg-info'>" . $program->subscriptions_count . "</span></td>
                    </tr>";
            }
            
            $content .= "
                    </tbody>
                </table>
            ";
            
            $box = new Box('🏥 Insurance Programs', $content);
            $box->style('warning');
            $column->append($box);
        });

        $row->column(4, function (Column $column) {
            $stats = [
                'active' => InsuranceSubscription::where('status', 'active')->count(),
                'suspended' => InsuranceSubscription::where('status', 'suspended')->count(),
                'cancelled' => InsuranceSubscription::where('status', 'cancelled')->count(),
                'expired' => InsuranceSubscription::where('status', 'expired')->count(),
            ];
            
            $total = array_sum($stats);
            
            $content = "
                <div style='padding: 10px;'>
                    <div class='row'>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #e8f5e9; border: 1px solid #e0e0e0; margin-bottom: 10px;'>
                                <h2 style='margin: 0; color: #4caf50;'>" . $stats['active'] . "</h2>
                                <small>Active</small>
                            </div>
                        </div>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #fff3e0; border: 1px solid #e0e0e0; margin-bottom: 10px;'>
                                <h2 style='margin: 0; color: #ff9800;'>" . $stats['suspended'] . "</h2>
                                <small>Suspended</small>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #ffebee; border: 1px solid #e0e0e0;'>
                                <h2 style='margin: 0; color: #f44336;'>" . $stats['cancelled'] . "</h2>
                                <small>Cancelled</small>
                            </div>
                        </div>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #f5f5f5; border: 1px solid #e0e0e0;'>
                                <h2 style='margin: 0; color: #9e9e9e;'>" . $stats['expired'] . "</h2>
                                <small>Expired</small>
                            </div>
                        </div>
                    </div>
                    <div style='text-align: center; margin-top: 15px; padding-top: 15px; border-top: 2px solid #ddd;'>
                        <h3 style='margin: 0;'>" . $total . "</h3>
                        <small>Total Subscriptions</small>
                    </div>
                </div>
            ";
            
            $box = new Box('📋 Subscription Status', $content);
            $box->style('info');
            $column->append($box);
        });

        $row->column(4, function (Column $column) {
            $requests = MedicalServiceRequest::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
            
            $content = "
                <div style='padding: 10px;'>
                    <table class='table table-sm' style='margin-bottom: 0;'>
                        <tbody>
                            <tr>
                                <td><i class='fa fa-clock text-warning'></i> Pending</td>
                                <td class='text-right'><span class='badge bg-warning'>" . ($requests['pending'] ?? 0) . "</span></td>
                            </tr>
                            <tr>
                                <td><i class='fa fa-spinner text-primary'></i> Processing</td>
                                <td class='text-right'><span class='badge bg-primary'>" . ($requests['processing'] ?? 0) . "</span></td>
                            </tr>
                            <tr>
                                <td><i class='fa fa-calendar-check text-info'></i> Scheduled</td>
                                <td class='text-right'><span class='badge bg-info'>" . ($requests['scheduled'] ?? 0) . "</span></td>
                            </tr>
                            <tr>
                                <td><i class='fa fa-check-circle text-success'></i> Completed</td>
                                <td class='text-right'><span class='badge bg-success'>" . ($requests['completed'] ?? 0) . "</span></td>
                            </tr>
                            <tr>
                                <td><i class='fa fa-times-circle text-danger'></i> Cancelled</td>
                                <td class='text-right'><span class='badge bg-danger'>" . ($requests['cancelled'] ?? 0) . "</span></td>
                            </tr>
                            <tr>
                                <td><i class='fa fa-ban text-secondary'></i> Rejected</td>
                                <td class='text-right'><span class='badge bg-secondary'>" . ($requests['rejected'] ?? 0) . "</span></td>
                            </tr>
                        </tbody>
                    </table>
                    <div style='text-align: center; margin-top: 15px; padding-top: 15px; border-top: 2px solid #ddd;'>
                        <h3 style='margin: 0;'>" . array_sum($requests) . "</h3>
                        <small>Total Medical Requests</small>
                    </div>
                </div>
            ";
            
            $box = new Box('🏥 Medical Service Requests', $content);
            $box->style('danger');
            $column->append($box);
        });
    }

    /**
     * SECTION 7: User & Order Analytics
     */
    private function addUserOrderAnalytics(Row $row)
    {
        $row->column(6, function (Column $column) {
            $stats = [
                'total_users' => User::count(),
                'new_this_month' => User::whereMonth('created_at', Carbon::now()->month)->count(),
                'insurance_users' => User::where('user_type', 'Customer')->count(),
            ];
            
            // Admin sees detailed breakdown, Manager sees basic stats only
            if ($this->canSeeUserDetails()) {
                $stats['admins'] = User::where('user_type', 'admin')->count();
                $stats['vendors'] = User::where('user_type', 'vendor')->count();
                $stats['customers'] = User::where('user_type', 'customer')->count();
            }
            
            $content = "
                <div class='row' style='padding: 10px;'>
                    <div class='col-md-4 col-sm-6'>
                        <div style='text-align: center; padding: 20px; background: #05179F; color: white; border: 1px solid #e0e0e0; margin-bottom: 15px;'>
                            <i class='fa fa-users' style='font-size: 24px;'></i>
                            <h2 style='margin: 10px 0;'>" . $stats['total_users'] . "</h2>
                            <small>Total Users</small>
                        </div>
                    </div>";
            
            // Only show detailed user types to admin
            if ($this->canSeeUserDetails()) {
                $content .= "
                    <div class='col-md-4 col-sm-6'>
                        <div style='text-align: center; padding: 20px; background: #05179F; color: white; border: 1px solid #e0e0e0; margin-bottom: 15px;'>
                            <i class='fa fa-user-shield' style='font-size: 24px;'></i>
                            <h2 style='margin: 10px 0;'>" . $stats['admins'] . "</h2>
                            <small>Administrators</small>
                        </div>
                    </div>
                    <div class='col-md-4 col-sm-6'>
                        <div style='text-align: center; padding: 20px; background: #05179F; color: white; border: 1px solid #e0e0e0; margin-bottom: 15px;'>
                            <i class='fa fa-store' style='font-size: 24px;'></i>
                            <h2 style='margin: 10px 0;'>" . $stats['vendors'] . "</h2>
                            <small>Vendors</small>
                        </div>
                    </div>
                    <div class='col-md-4 col-sm-6'>
                        <div style='text-align: center; padding: 20px; background: #05179F; color: white; border: 1px solid #e0e0e0; margin-bottom: 15px;'>
                            <i class='fa fa-shopping-bag' style='font-size: 24px;'></i>
                            <h2 style='margin: 10px 0;'>" . $stats['customers'] . "</h2>
                            <small>Customers</small>
                        </div>
                    </div>";
            }
            
            $content .= "
                    <div class='col-md-4 col-sm-6'>
                        <div style='text-align: center; padding: 20px; background: #05179F; color: white; border: 1px solid #e0e0e0; margin-bottom: 15px;'>
                            <i class='fa fa-heartbeat' style='font-size: 24px;'></i>
                            <h2 style='margin: 10px 0;'>" . $stats['insurance_users'] . "</h2>
                            <small>Insurance Users</small>
                        </div>
                    </div>
                    <div class='col-md-4 col-sm-6'>
                        <div style='text-align: center; padding: 20px; background: #05179F; color: white; border: 1px solid #e0e0e0; margin-bottom: 15px;'>
                            <i class='fa fa-user-plus' style='font-size: 24px;'></i>
                            <h2 style='margin: 10px 0;'>" . $stats['new_this_month'] . "</h2>
                            <small>New This Month</small>
                        </div>
                    </div>
                </div>
            ";
            
            $box = new Box('👥 User Statistics', $content);
            $box->style('primary');
            $column->append($box);
        });

        $row->column(6, function (Column $column) {
            /* $orderStats = [
                'total' => Order::count(),
                'pending' => Order::where('order_state', 0)->count(),
                'processing' => Order::where('order_state', 1)->count(),
                'completed' => Order::where('order_state', 2)->count(),
                'cancelled' => Order::whereIn('order_state', [3, 4])->count(), // canceled + failed
                'total_value' => Order::sum('order_total'),
            ];
            
            $products = Product::count();
            
            $content = "
                <div style='padding: 10px;'>
                    <div class='row'>
                        <div class='col-xs-4'>
                            <div style='text-align: center; padding: 15px; background: #e3f2fd; border: 1px solid #e0e0e0; margin-bottom: 10px;'>
                                <h3 style='margin: 0; color: #1976d2;'>" . $orderStats['total'] . "</h3>
                                <small>Total Orders</small>
                            </div>
                        </div>
                        <div class='col-xs-4'>
                            <div style='text-align: center; padding: 15px; background: #fff3e0; border: 1px solid #e0e0e0; margin-bottom: 10px;'>
                                <h3 style='margin: 0; color: #f57c00;'>" . $orderStats['pending'] . "</h3>
                                <small>Pending</small>
                            </div>
                        </div>
                        <div class='col-xs-4'>
                            <div style='text-align: center; padding: 15px; background: #e8f5e9; border: 1px solid #e0e0e0; margin-bottom: 10px;'>
                                <h3 style='margin: 0; color: #388e3c;'>" . $orderStats['completed'] . "</h3>
                                <small>Completed</small>
                            </div>
                        </div>
                    </div>
                    <div style='background: #05179F; padding: 20px; border: 1px solid #e0e0e0; color: white; text-align: center; margin: 15px 0;'>
                        <h2 style='margin: 0;'>UGX " . number_format($orderStats['total_value'], 0) . "</h2>
                        <p style='margin: 5px 0 0 0;'>Total Order Value</p>
                    </div>
                    <table class='table table-sm' style='margin-bottom: 0;'>
                        <tr>
                            <td><i class='fa fa-box text-primary'></i> Total Products</td>
                            <td class='text-right'><strong>" . $products . "</strong></td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-spinner text-info'></i> Processing</td>
                            <td class='text-right'><span class='badge bg-info'>" . $orderStats['processing'] . "</span></td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-times-circle text-danger'></i> Cancelled</td>
                            <td class='text-right'><span class='badge bg-danger'>" . $orderStats['cancelled'] . "</span></td>
                        </tr>
                    </table>
                </div>
            ";
            
            $box = new Box('🛒 Order & Product Statistics', $content);
            $box->style('success');
            $column->append($box); */
        });
    }

    /**
     * SECTION 8: Recent Activities
     */
    private function addRecentActivities(Row $row)
    {
        $row->column(12, function (Column $column) {
            // Recent Transactions
            $recentTransactions = ProjectTransaction::with(['project', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            // Recent Disbursements
            $recentDisbursements = Disbursement::with(['project', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            $content = "
                <div class='row'>
                    <div class='col-md-8'>
                        <h4><i class='fa fa-exchange-alt'></i> Recent Project Transactions</h4>
                        <div style='max-height: 400px; overflow-y: auto;'>
                            <table class='table table-hover table-sm'>
                                <thead style='background: #f5f5f5;'>
                                    <tr>
                                        <th>Date</th>
                                        <th>Project</th>
                                        <th>Type</th>
                                        <th class='text-right'>Amount</th>
                                        <th>By</th>
                                    </tr>
                                </thead>
                                <tbody>";
            
            foreach ($recentTransactions as $trans) {
                $typeClass = $trans->type === 'income' ? 'success' : 'danger';
                $icon = $trans->type === 'income' ? 'arrow-up' : 'arrow-down';
                
                $content .= "
                    <tr>
                        <td><small>" . $trans->created_at->format('d M, H:i') . "</small></td>
                        <td><strong>" . ($trans->project->title ?? 'N/A') . "</strong></td>
                        <td><span class='label label-{$typeClass}'><i class='fa fa-{$icon}'></i> " . ucfirst($trans->type) . "</span></td>
                        <td class='text-right'><strong>UGX " . number_format($trans->amount, 0) . "</strong></td>
                        <td><small>" . ($trans->creator->name ?? 'System') . "</small></td>
                    </tr>";
            }
            
            $content .= "
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class='col-md-4'>
                        <h4><i class='fa fa-hand-holding-usd'></i> Recent Disbursements</h4>
                        <div style='max-height: 400px; overflow-y: auto;'>";
            
            foreach ($recentDisbursements as $disbursement) {
                $content .= "
                    <div style='padding: 10px; margin-bottom: 10px; background: #f9f9f9; border-left: 4px solid #4caf50; border: 1px solid #e0e0e0;'>
                        <div><strong>" . ($disbursement->project->title ?? 'N/A') . "</strong></div>
                        <div style='color: #4caf50; font-size: 16px; font-weight: bold; margin: 5px 0;'>
                            UGX " . number_format($disbursement->amount, 0) . "
                        </div>
                        <div><small><i class='fa fa-calendar'></i> " . $disbursement->created_at->format('d M Y, H:i') . "</small></div>
                        <div><small><i class='fa fa-user'></i> " . ($disbursement->creator->name ?? 'System') . "</small></div>
                    </div>";
            }
            
            $content .= "
                        </div>
                    </div>
                </div>
            ";
            
            $box = new Box('🔄 Recent Activities', $content);
            $box->style('default');
            $column->append($box);
        });
    }

    /**
     * Helper: Create KPI Card
     */
    private function createKPICard($mainValue, $subText, $growth, $colorClass, $icon)
    {
        $growthHtml = $growth ? "<div style='color: #4caf50; margin-top: 5px;'><i class='fa fa-arrow-up'></i> {$growth}</div>" : '';
        
        return "
            <div style='text-align: center; padding: 15px;'>
                <div style='font-size: 48px; color: white; opacity: 0.2;'>
                    <i class='fa {$icon}'></i>
                </div>
                <div style='margin-top: -40px;'>
                    <h2 style='margin: 0; font-size: 28px; color: white;'>{$mainValue}</h2>
                    <p style='margin: 10px 0 0 0; color: rgba(255,255,255,0.9);'>{$subText}</p>
                    {$growthHtml}
                </div>
            </div>
        ";
    }

}
