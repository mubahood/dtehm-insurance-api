<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\ProjectTransaction;
use App\Models\Disbursement;
use App\Models\AccountTransaction;
use App\Models\InsuranceProgram;
use App\Models\InsuranceSubscription;
use App\Models\MedicalServiceRequest;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->title('Dashboard')
            ->description('Complete System Overview')
            ->row(function (Row $row) {
                // SECTION 1: KEY PERFORMANCE INDICATORS (4 columns)
                $this->addKPISection($row);
            })
            ->row(function (Row $row) {
                // SECTION 2: FINANCIAL OVERVIEW (2 columns)
                $this->addFinancialOverview($row);
            })
            ->row(function (Row $row) {
                // SECTION 3: PROJECT & INVESTMENT ANALYTICS (2 columns)
                $this->addProjectAnalytics($row);
            })
            ->row(function (Row $row) {
                // SECTION 4: INSURANCE SYSTEM OVERVIEW (3 columns)
                $this->addInsuranceOverview($row);
            })
            ->row(function (Row $row) {
                // SECTION 5: USER & ORDER ANALYTICS (2 columns)
                $this->addUserOrderAnalytics($row);
            })
            ->row(function (Row $row) {
                // SECTION 6: RECENT ACTIVITIES (Full width)
                $this->addRecentActivities($row);
            });
    }

    /**
     * SECTION 1: Key Performance Indicators
     */
    private function addKPISection(Row $row)
    {
        // Total Revenue
        $row->column(3, function (Column $column) {
            $totalRevenue = ProjectTransaction::where('type', 'income')
                ->where('source', '!=', 'share_purchase')
                ->sum('amount');
            
            $monthRevenue = ProjectTransaction::where('type', 'income')
                ->where('source', '!=', 'share_purchase')
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('amount');
            
            $growth = $this->calculateGrowth('revenue');
            $growthHtml = $growth ? "<div style='color: #fff; margin-top: 5px; font-size: 14px;'><i class='fa fa-arrow-up'></i> {$growth}</div>" : '';
            
            $content = "
                <div style='background: #05179F; padding: 20px; color: white; min-height: 150px; border: 1px solid #e0e0e0;'>
                    <div style='opacity: 0.3; font-size: 48px; text-align: center;'>
                        <i class='fa fa-money-bill-wave'></i>
                    </div>
                    <div style='margin-top: -40px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 28px; color: white; font-weight: bold;'>UGX " . number_format($totalRevenue, 0) . "</h2>
                        <p style='margin: 10px 0 0 0; color: rgba(255,255,255,0.95);'>This Month: UGX " . number_format($monthRevenue, 0) . "</p>
                        {$growthHtml}
                    </div>
                </div>
            ";
            
            $box = new Box('💰 Total Revenue', $content);
            $column->append($box);
        });

        // Active Projects
        $row->column(3, function (Column $column) {
            $activeProjects = Project::where('status', 'ongoing')->count();
            $totalProjects = Project::count();
            $completedProjects = Project::where('status', 'completed')->count();
            
            $content = "
                <div style='background: #05179F; padding: 20px; color: white; min-height: 150px; border: 1px solid #e0e0e0;'>
                    <div style='opacity: 0.3; font-size: 48px; text-align: center;'>
                        <i class='fa fa-project-diagram'></i>
                    </div>
                    <div style='margin-top: -40px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 28px; color: white; font-weight: bold;'>{$activeProjects} Active</h2>
                        <p style='margin: 10px 0 0 0; color: rgba(255,255,255,0.95);'>{$completedProjects} Completed | {$totalProjects} Total</p>
                    </div>
                </div>
            ";
            
            $box = new Box('📊 Projects', $content);
            $column->append($box);
        });

        // Total Investors
        $row->column(3, function (Column $column) {
            $totalInvestors = ProjectShare::distinct('investor_id')->count('investor_id');
            $newThisMonth = ProjectShare::whereMonth('created_at', Carbon::now()->month)
                ->distinct('investor_id')
                ->count('investor_id');
            
            $totalInvestment = ProjectTransaction::where('source', 'share_purchase')
                ->sum('amount');
            
            $content = "
                <div style='background: #05179F; padding: 20px; color: white; min-height: 150px; border: 1px solid #e0e0e0;'>
                    <div style='opacity: 0.3; font-size: 48px; text-align: center;'>
                        <i class='fa fa-users'></i>
                    </div>
                    <div style='margin-top: -40px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 28px; color: white; font-weight: bold;'>{$totalInvestors} Investors</h2>
                        <p style='margin: 10px 0 0 0; color: rgba(255,255,255,0.95);'>Total Investment: UGX " . number_format($totalInvestment, 0) . "</p>
                        <div style='color: #fff; margin-top: 5px; font-size: 14px;'>+{$newThisMonth} this month</div>
                    </div>
                </div>
            ";
            
            $box = new Box('👥 Investors', $content);
            $column->append($box);
        });

        // Insurance Subscribers
        $row->column(3, function (Column $column) {
            $activeSubscribers = InsuranceSubscription::where('status', 'active')->count();
            $totalSubscribers = InsuranceSubscription::count();
            $monthlyPremiums = InsuranceSubscription::where('status', 'active')
                ->sum('premium_amount');
            
            $content = "
                <div style='background: #05179F; padding: 20px; color: white; min-height: 150px; border: 1px solid #e0e0e0;'>
                    <div style='opacity: 0.3; font-size: 48px; text-align: center;'>
                        <i class='fa fa-shield-alt'></i>
                    </div>
                    <div style='margin-top: -40px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 28px; color: white; font-weight: bold;'>{$activeSubscribers} Active</h2>
                        <p style='margin: 10px 0 0 0; color: rgba(255,255,255,0.95);'>Monthly Premiums: UGX " . number_format($monthlyPremiums, 0) . "</p>
                        <div style='color: #fff; margin-top: 5px; font-size: 14px;'>{$totalSubscribers} total subscribers</div>
                    </div>
                </div>
            ";
            
            $box = new Box('🏥 Insurance', $content);
            $column->append($box);
        });
    }

    /**
     * SECTION 2: Financial Overview
     */
    private function addFinancialOverview(Row $row)
    {
        $row->column(6, function (Column $column) {
            $data = $this->getFinancialData();
            
            $content = "
                <table class='table table-bordered table-striped' style='margin-bottom:0;'>
                    <thead style='background: #05179F; color: white;'>
                        <tr>
                            <th>Metric</th>
                            <th class='text-right'>Amount (UGX)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class='fa fa-arrow-up text-success'></i> <strong>Total Investment</strong></td>
                            <td class='text-right'><strong>" . number_format($data['total_investment'], 0) . "</strong></td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-chart-line text-primary'></i> Project Revenue</td>
                            <td class='text-right'>" . number_format($data['project_revenue'], 0) . "</td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-coins text-warning'></i> Total Profits</td>
                            <td class='text-right text-success'><strong>" . number_format($data['total_profits'], 0) . "</strong></td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-hand-holding-usd text-info'></i> Disbursed to Investors</td>
                            <td class='text-right'>" . number_format($data['disbursed'], 0) . "</td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-wallet text-success'></i> Pending Disbursements</td>
                            <td class='text-right'>" . number_format($data['pending_disbursements'], 0) . "</td>
                        </tr>
                        <tr style='background: #f8f9fa;'>
                            <td><strong><i class='fa fa-money-check-alt text-danger'></i> Total Expenses</strong></td>
                            <td class='text-right'><strong>" . number_format($data['total_expenses'], 0) . "</strong></td>
                        </tr>
                    </tbody>
                </table>
            ";
            
            $box = new Box('💰 Investment & Financial Overview', $content);
            $box->style('primary');
            $column->append($box);
        });

        $row->column(6, function (Column $column) {
            $data = $this->getInsuranceFinancials();
            
            $content = "
                <table class='table table-bordered table-striped' style='margin-bottom:0;'>
                    <thead style='background: #05179F; color: white;'>
                        <tr>
                            <th>Insurance Metric</th>
                            <th class='text-right'>Amount (UGX)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class='fa fa-file-invoice-dollar text-primary'></i> <strong>Total Expected</strong></td>
                            <td class='text-right'><strong>" . number_format($data['total_expected'], 0) . "</strong></td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-check-circle text-success'></i> Total Collected</td>
                            <td class='text-right text-success'>" . number_format($data['total_collected'], 0) . "</td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-hourglass-half text-warning'></i> Pending Payments</td>
                            <td class='text-right text-warning'><strong>" . number_format($data['pending'], 0) . "</strong></td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-exclamation-triangle text-danger'></i> Overdue Payments</td>
                            <td class='text-right text-danger'>" . number_format($data['overdue'], 0) . "</td>
                        </tr>
                        <tr style='background: #e8f5e9;'>
                            <td><strong><i class='fa fa-percentage text-success'></i> Collection Rate</strong></td>
                            <td class='text-right text-success'><strong>" . $data['collection_rate'] . "%</strong></td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-hand-holding-medical text-info'></i> Medical Requests</td>
                            <td class='text-right'>" . $data['medical_requests'] . " pending</td>
                        </tr>
                    </tbody>
                </table>
            ";
            
            $box = new Box('🏥 Insurance Financial Overview', $content);
            $box->style('danger');
            $column->append($box);
        });
    }

    /**
     * SECTION 3: Project & Investment Analytics
     */
    private function addProjectAnalytics(Row $row)
    {
        $row->column(6, function (Column $column) {
            $projects = Project::withCount('shares')
                ->orderBy('shares_count', 'desc')
                ->limit(5)
                ->get();
            
            $content = "
                <table class='table table-hover' style='margin-bottom:0;'>
                    <thead style='background: #f5f5f5;'>
                        <tr>
                            <th>Project</th>
                            <th class='text-center'>Status</th>
                            <th class='text-right'>Investment</th>
                            <th class='text-right'>Profit</th>
                            <th class='text-right'>ROI</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($projects as $project) {
                $roi = $project->total_investment > 0 
                    ? round(($project->total_profits / $project->total_investment) * 100, 1)
                    : 0;
                
                $statusClass = [
                    'pending' => 'warning',
                    'ongoing' => 'primary',
                    'completed' => 'success',
                    'cancelled' => 'danger'
                ][$project->status] ?? 'default';
                
                $content .= "
                    <tr>
                        <td><strong>" . $project->name . "</strong><br><small>" . $project->shares_count . " investors</small></td>
                        <td class='text-center'><span class='label label-{$statusClass}'>" . ucfirst($project->status) . "</span></td>
                        <td class='text-right'>" . number_format($project->total_investment, 0) . "</td>
                        <td class='text-right text-success'>" . number_format($project->total_profits, 0) . "</td>
                        <td class='text-right'><strong>" . $roi . "%</strong></td>
                    </tr>";
            }
            
            $content .= "
                    </tbody>
                </table>
            ";
            
            $box = new Box('📊 Top 5 Projects by Investment', $content);
            $box->style('info');
            $column->append($box);
        });

        $row->column(6, function (Column $column) {
            $topInvestors = DB::table('project_shares')
                ->join('users', 'project_shares.investor_id', '=', 'users.id')
                ->select('users.name', 
                    DB::raw('COUNT(DISTINCT project_shares.project_id) as project_count'),
                    DB::raw('SUM(project_shares.total_amount_paid) as total_invested'))
                ->whereNull('project_shares.deleted_at')
                ->groupBy('project_shares.investor_id', 'users.name')
                ->orderBy('total_invested', 'desc')
                ->limit(8)
                ->get();
            
            $content = "
                <table class='table table-hover' style='margin-bottom:0;'>
                    <thead style='background: #f5f5f5;'>
                        <tr>
                            <th>Investor</th>
                            <th class='text-center'>Projects</th>
                            <th class='text-right'>Total Investment</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($topInvestors as $investor) {
                $content .= "
                    <tr>
                        <td><i class='fa fa-user-circle text-primary'></i> <strong>" . $investor->name . "</strong></td>
                        <td class='text-center'><span class='badge bg-primary'>" . $investor->project_count . "</span></td>
                        <td class='text-right'><strong>UGX " . number_format($investor->total_invested, 0) . "</strong></td>
                    </tr>";
            }
            
            $content .= "
                    </tbody>
                </table>
            ";
            
            $box = new Box('👥 Top Investors', $content);
            $box->style('success');
            $column->append($box);
        });
    }

    /**
     * SECTION 4: Insurance System Overview
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
     * SECTION 5: User & Order Analytics
     */
    private function addUserOrderAnalytics(Row $row)
    {
        $row->column(6, function (Column $column) {
            $stats = [
                'total_users' => User::count(),
                'admins' => User::where('user_type', 'admin')->count(),
                'vendors' => User::where('user_type', 'vendor')->count(),
                'customers' => User::where('user_type', 'customer')->count(),
                'new_this_month' => User::whereMonth('created_at', Carbon::now()->month)->count(),
                'insurance_users' => User::where('user_type', 'Customer')->count(),
            ];
            
            $content = "
                <div class='row' style='padding: 10px;'>
                    <div class='col-md-4 col-sm-6'>
                        <div style='text-align: center; padding: 20px; background: #05179F; color: white; border: 1px solid #e0e0e0; margin-bottom: 15px;'>
                            <i class='fa fa-users' style='font-size: 24px;'></i>
                            <h2 style='margin: 10px 0;'>" . $stats['total_users'] . "</h2>
                            <small>Total Users</small>
                        </div>
                    </div>
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
                    </div>
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
            $orderStats = [
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
            $column->append($box);
        });
    }

    /**
     * SECTION 6: Recent Activities
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
                        <td><strong>" . ($trans->project->name ?? 'N/A') . "</strong></td>
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
                        <div><strong>" . $disbursement->project->name . "</strong></div>
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

    /**
     * Helper: Get Financial Data
     */
    private function getFinancialData()
    {
        return [
            'total_investment' => ProjectTransaction::where('source', 'share_purchase')->sum('amount'),
            'project_revenue' => ProjectTransaction::where('type', 'income')
                ->where('source', '!=', 'share_purchase')
                ->sum('amount'),
            'total_profits' => Project::sum('total_profits'),
            'disbursed' => Disbursement::sum('amount'),
            'pending_disbursements' => Project::where('status', 'ongoing')->sum('total_profits') - Disbursement::sum('amount'),
            'total_expenses' => ProjectTransaction::where('type', 'expense')->sum('amount'),
        ];
    }

    /**
     * Helper: Get Insurance Financials
     */
    private function getInsuranceFinancials()
    {
        $totalExpected = InsuranceProgram::sum('total_premiums_expected');
        $totalCollected = InsuranceProgram::sum('total_premiums_collected');
        $collectionRate = $totalExpected > 0 ? round(($totalCollected / $totalExpected) * 100, 1) : 0;
        
        return [
            'total_expected' => $totalExpected,
            'total_collected' => $totalCollected,
            'pending' => InsuranceProgram::sum('total_premiums_balance'),
            'overdue' => InsuranceSubscription::where('payment_status', 'overdue')->count(),
            'collection_rate' => $collectionRate,
            'medical_requests' => MedicalServiceRequest::whereIn('status', ['pending', 'processing'])->count(),
        ];
    }

    /**
     * Helper: Calculate Growth
     */
    private function calculateGrowth($type)
    {
        $currentMonth = ProjectTransaction::where('type', 'income')
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('amount');
        
        $lastMonth = ProjectTransaction::where('type', 'income')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->sum('amount');
        
        if ($lastMonth == 0) return null;
        
        $growth = round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1);
        return $growth > 0 ? "+{$growth}%" : "{$growth}%";
    }
}
