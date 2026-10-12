<?php

namespace App\Admin\Controllers;

use App\Models\WithdrawRequest;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use PDF;

class WithdrawRequestController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Withdraw Requests';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new WithdrawRequest());
        // Pending requests first (oldest waiting longest at the top), then the rest newest first
        $grid->model()->with(['user', 'processedBy'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN status = 'pending' THEN created_at END ASC")
            ->orderBy('created_at', 'desc');
        if (in_array(request('tab'), ['pending', 'approved', 'rejected'], true)) {
            $grid->model()->where('status', request('tab'));
        }

        // ID Column
        $grid->column('id', __('ID'))
            ->sortable()
            ->width(60)
            ->style('font-weight: bold;');

        // User Column
        $grid->column('user.name', __('User'))
            ->display(function () {
                return '<a href="' . admin_url('users/' . $this->user_id) . '">' . $this->user->name . '</a><br>' .
                    '<small class="text-muted">' . $this->user->phone_number . '</small>';
            })
            ->width(180);

        // DIP ID Column
        $grid->column('user.business_name', __('DIP ID'))
            ->display(function () {
                if (empty($this->user->business_name)) {
                    return '-';
                }
                return '<span class="label label-primary">' . $this->user->business_name . '</span>';
            })
            ->width(90);

        // Shop Column
        $grid->column('user.shop.name', __('Shop'))
            ->display(function () {
                $shopName = optional(optional($this->user)->shop)->name;
                return $shopName
                    ? "<span style='background:#00c0ef;color:#fff;font-size:10px;padding:2px 7px;border-radius:3px;'>{$shopName}</span>"
                    : '<span style="color:#ccc;font-size:11px;">—</span>';
            })
            ->width(130);

        // Amount Column
        $grid->column('amount', __('Amount'))
            ->display(function ($amount) {
                return '<strong style="color: #d9534f;">UGX ' . number_format($amount, 2) . '</strong>';
            })
            ->sortable()
            ->width(130);

        // Balance Before Column
        $grid->column('account_balance_before', __('Balance Before'))
            ->display(function ($balance) {
                return 'UGX ' . number_format($balance, 2);
            })
            ->width(130);

        // Current Balance Column
        $grid->column('current_balance', __('Current Balance'))
            ->display(function () {
                $currentBalance = $this->user->calculateAccountBalance();
                $color = $currentBalance >= $this->amount ? 'success' : 'danger';
                return '<span class="text-' . $color . '">UGX ' . number_format($currentBalance, 2) . '</span>';
            })
            ->width(130);

        // Status Column
        $grid->column('status', __('Status'))
            ->label([
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
            ])
            ->sortable()
            ->filter([
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ])
            ->width(100);

        // Payment Method Column
        $grid->column('payment_method', __('Payment Method'))
            ->display(function ($method) {
                return ucfirst(str_replace('_', ' ', $method));
            })
            ->width(120);

        // Payment Phone Column
        $grid->column('payment_phone_number', __('Payment Phone'))
            ->width(120);

        // Description Column
        $grid->column('description', __('Description'))
            ->limit(50)
            ->width(200);

        // Processed By Column
        $grid->column('processedBy.name', __('Processed By'))
            ->display(function () {
                if (!$this->processed_by_id) {
                    return '<span class="text-muted">-</span>';
                }
                return $this->processedBy->name ?? '-';
            })
            ->width(120);

        // Processed At Column
        $grid->column('processed_at', __('Processed At'))
            ->display(function ($date) {
                if (!$date) {
                    return '<span class="text-muted">-</span>';
                }
                return date('d M Y H:i', strtotime($date));
            })
            ->sortable()
            ->width(130);

        // Created At Column
        $grid->column('created_at', __('Requested At'))
            ->display(function ($date) {
                return date('d M Y H:i', strtotime($date));
            })
            ->sortable()
            ->width(130);

        // Quick Search
        $grid->quickSearch('id', 'user.name', 'user.phone_number', 'user.business_name')
            ->placeholder('Search by ID, name, phone, or DIP ID');

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->equal('status', 'Status')->radio([
                '' => 'All',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ]);

            $filter->like('user.name', 'User Name');
            $filter->like('user.phone_number', 'Phone Number');
            $filter->like('user.business_name', 'DIP ID');
            $filter->between('amount', 'Amount')->decimal();
            $filter->between('created_at', 'Requested Date')->date();

            // Shop filter
            $shopOptions = User::where('is_stockist', 'Yes')
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn($u) => [$u->id => $u->name . ($u->dtehm_member_id ? " ({$u->dtehm_member_id})" : '')]);
            $filter->where(function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('shop_id', $this->input);
                });
            }, 'Shop', 'shop_filter')->select($shopOptions);
        });

        // Export
        $grid->export(function ($export) {
            $export->filename('Withdraw Requests');
            $export->column('id', 'ID');
            $export->column('created_at', 'Requested At');
            $export->column('user.name', 'User');
            $export->column('user.business_name', 'DIP ID');
            $export->column('user.phone_number', 'Phone');
            $export->column('user.shop.name', 'Shop');
            $export->column('amount', 'Amount');
            $export->column('status', 'Status');
            $export->column('payment_method', 'Payment Method');
            $export->column('payment_phone_number', 'Payment Phone');
        });

        // Actions: secure POST dialogs (laravel-admin row actions), only on pending requests
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            if ($actions->row->status === 'pending' && is_null($actions->row->account_transaction_id)) {
                $actions->add(new \App\Admin\Actions\Withdraw\ApproveWithdraw());
                $actions->add(new \App\Admin\Actions\Withdraw\RejectWithdraw());
            }
        });
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
            $batch->add(new \App\Admin\Actions\Withdraw\BatchApproveWithdraw());
        });

        // Disable create button
        $grid->disableCreateButton();

        // Add statistics row with PDF button
        $grid->header(function ($query) {
            $pending = WithdrawRequest::where('status', 'pending');
            $monthStart = now()->startOfMonth();
            $stats = [
                'pending' => (clone $pending)->count(),
                'pending_amount' => (clone $pending)->sum('amount'),
                'oldest' => optional((clone $pending)->min('created_at') ? \Carbon\Carbon::parse((clone $pending)->min('created_at')) : null),
                'approved_month' => WithdrawRequest::where('status', 'approved')->where('processed_at', '>=', $monthStart)->sum('amount'),
                'approved_month_count' => WithdrawRequest::where('status', 'approved')->where('processed_at', '>=', $monthStart)->count(),
            ];
            $tab = request('tab', '');
            $tabs = ['' => 'All', 'pending' => 'Pending (' . $stats['pending'] . ')', 'approved' => 'Approved', 'rejected' => 'Rejected'];
            $tabHtml = '';
            foreach ($tabs as $k => $label) {
                $tabHtml .= '<a href="' . admin_url('withdraw-requests' . ($k ? '?tab=' . $k : '')) . '" class="btn btn-sm ' . ($tab === $k ? 'btn-primary' : 'btn-default') . '">' . $label . '</a> ';
            }
            $oldest = $stats['oldest'] ? ' · oldest waiting ' . $stats['oldest']->diffForHumans(null, true) : '';
            return '<div class="wr-head">'
                . '<div class="wr-kpis">'
                . '<div class="wr-kpi wr-kpi-warn"><span>Waiting for approval</span><b>' . $stats['pending'] . '</b><small>UGX ' . number_format($stats['pending_amount'], 0) . $oldest . '</small></div>'
                . '<div class="wr-kpi wr-kpi-ok"><span>Paid out this month</span><b>UGX ' . number_format($stats['approved_month'], 0) . '</b><small>' . $stats['approved_month_count'] . ' request(s)</small></div>'
                . '</div>'
                . '<div class="wr-tabs">' . $tabHtml
                . '<a href="' . admin_url('withdraw-requests/pdf-pending') . '" target="_blank" class="btn btn-sm btn-default pull-right"><i class="fa fa-file-pdf-o"></i> Pending list (PDF)</a></div>'
                . '<p class="text-muted small">Approve deducts the amount from the member balance (checked at that moment). Reject deducts nothing; the reason is shown to the member. Tick several rows to approve them together.</p>'
                . '</div>';
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(WithdrawRequest::findOrFail($id));

        $withdrawRequest = WithdrawRequest::findOrFail($id);

        $show->field('id', __('ID'));

        $show->field('user.name', __('User Name'));
        $show->field('user.email', __('User Email'));
        $show->field('user.phone_number', __('User Phone'));
        $show->field('user.business_name', __('User DIP ID'));

        $show->field('amount', __('Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 2);
        });

        $show->field('account_balance_before', __('Balance Before'))->as(function ($balance) {
            return 'UGX ' . number_format($balance, 2);
        });

        $show->field('current_balance', __('Current Balance'))->as(function () {
            $currentBalance = $this->user->calculateAccountBalance();
            return 'UGX ' . number_format($currentBalance, 2);
        });

        $show->field('status', __('Status'))->unescape()->as(function ($status) {
            $colors = [
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
            ];
            return '<span class="label label-' . $colors[$status] . '">' . ucfirst($status) . '</span>';
        });

        $show->field('description', __('Description'));
        $show->field('payment_method', __('Payment Method'))->as(function ($method) {
            return ucfirst(str_replace('_', ' ', $method));
        });
        $show->field('payment_phone_number', __('Payment Phone Number'));

        $show->field('admin_note', __('Admin Note'));
        $show->field('processedBy.name', __('Processed By'));
        $show->field('processed_at', __('Processed At'));

        $show->field('accountTransaction.id', __('Transaction ID'));

        $show->field('created_at', __('Requested At'));
        $show->field('updated_at', __('Updated At'));

        // Add action buttons for pending requests
        if ($withdrawRequest->status === 'pending' && is_null($withdrawRequest->account_transaction_id)) {
            $show->panel()
                ->tools(function ($tools) use ($id, $withdrawRequest) {
                    $csrf = csrf_field();
                    $approveUrl = admin_url('withdraw-requests/' . $id . '/approve');
                    $rejectUrl = admin_url('withdraw-requests/' . $id . '/reject');
                    $amount = number_format((float) $withdrawRequest->amount, 0);
                    $tools->append(
                        '<form method="post" action="' . $approveUrl . '" style="display:inline" data-confirm="Approve UGX ' . $amount . '? It will be deducted from the member balance.">' . $csrf
                        . '<input type="text" name="note" class="form-control input-sm" style="display:inline;width:200px" placeholder="Payout reference (optional)"> '
                        . '<button class="btn btn-sm btn-success"><i class="fa fa-check"></i> Approve</button></form> '
                        . '<form method="post" action="' . $rejectUrl . '" style="display:inline">' . $csrf
                        . '<input type="text" name="reason" required class="form-control input-sm" style="display:inline;width:200px" placeholder="Reason for rejecting"> '
                        . '<button class="btn btn-sm btn-danger"><i class="fa fa-times"></i> Reject</button></form>'
                    );
                });
        }

        return $show;
    }

    /**
     * Approve a withdraw request
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve($id)
    {
        if (request()->isMethod('get')) {
            return redirect(admin_url('withdraw-requests/' . $id)); // actions only by POST (no link prefetch / CSRF)
        }
        $withdrawRequest = WithdrawRequest::findOrFail($id);
        $result = $withdrawRequest->approve(auth('admin')->user(), trim((string) request('note')) ?: null);
        admin_toastr($result['message'], $result['success'] ? 'success' : 'error', ['timeOut' => 7000]);
        return redirect(admin_url('withdraw-requests/' . $id));
    }

    public function reject($id)
    {
        if (request()->isMethod('get')) {
            return redirect(admin_url('withdraw-requests/' . $id));
        }
        $withdrawRequest = WithdrawRequest::findOrFail($id);
        $result = $withdrawRequest->reject(auth('admin')->user(), (string) request('reason'));
        admin_toastr($result['message'], $result['success'] ? 'success' : 'error', ['timeOut' => 7000]);
        return redirect(admin_url('withdraw-requests/' . $id));
    }

    /**
     * Generate PDF for pending withdraw requests
     *
     * @return \Illuminate\Http\Response
     */
    public function generatePendingPDF()
    {
        try {
            // Get all pending withdraw requests
            $requests = WithdrawRequest::where('status', 'pending')
                ->with(['user'])
                ->orderBy('created_at', 'asc')
                ->get();

            // Calculate totals
            $totalAmount = $requests->sum('amount');
            $totalCount = $requests->count();

            // Prepare data for view
            $data = [
                'requests' => $requests,
                'totalAmount' => $totalAmount,
                'totalCount' => $totalCount,
                'generatedAt' => now()->format('F d, Y h:i A'),
                'generatedBy' => auth('admin')->user()->name ?? 'Admin',
            ];

            // Load view and generate PDF
            $pdf = \PDF::loadView('admin.withdraw-requests-pdf', $data);
            
            // Set paper size and orientation (portrait for better A4 layout)
            $pdf->setPaper('A4', 'portrait');
            
            // Stream PDF to browser (opens in new tab)
            return $pdf->stream('pending-withdraw-requests-' . date('Y-m-d') . '.pdf');

        } catch (\Exception $e) {
            \Log::error('Withdraw PDF failed: ' . $e->getMessage());
            admin_error('Error', 'Failed to generate the PDF. Please try again.');
            return back();
        }
    }

    /**
     * Make a form builder (disabled).
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new WithdrawRequest());

        // Disable all form actions since we handle approvals/rejections separately
        $form->disableCreatingCheck();
        $form->disableEditingCheck();
        $form->disableViewCheck();

        return $form;
    }
}
