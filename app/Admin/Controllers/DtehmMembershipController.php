<?php

namespace App\Admin\Controllers;

use App\Models\DtehmMembership;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DtehmMembershipController extends AdminController
{
    protected $title = 'DTEHM Memberships (76,000 UGX)';

    // ─────────────────────────────────────────────────────────────────────────
    // GRID
    // ─────────────────────────────────────────────────────────────────────────
    protected function grid()
    {
        $grid = new Grid(new DtehmMembership());

        $grid->model()->with('user')->orderBy('id', 'desc');

        // ── Stats header ──────────────────────────────────────────────────────
        $total     = DtehmMembership::count();
        $confirmed = DtehmMembership::where('status', 'CONFIRMED')->count();
        $pending   = DtehmMembership::where('status', 'PENDING')->count();
        $failed    = DtehmMembership::where('status', 'FAILED')->count();
        $revenue   = DtehmMembership::where('status', 'CONFIRMED')->sum('amount');

        $grid->header(function () use ($total, $confirmed, $pending, $failed, $revenue) {
            return "
            <div class='row' style='margin-bottom:12px;'>
                <div class='col-sm-2'>
                    <div class='info-box' style='min-height:60px;padding:8px 10px;'>
                        <span class='info-box-icon bg-aqua' style='height:60px;line-height:60px;width:50px;'><i class='fa fa-list'></i></span>
                        <div class='info-box-content' style='margin-left:60px;padding-top:0;'>
                            <span class='info-box-text' style='font-size:11px;'>Total</span>
                            <span class='info-box-number' style='font-size:20px;'>{$total}</span>
                        </div>
                    </div>
                </div>
                <div class='col-sm-2'>
                    <div class='info-box' style='min-height:60px;padding:8px 10px;'>
                        <span class='info-box-icon bg-green' style='height:60px;line-height:60px;width:50px;'><i class='fa fa-check'></i></span>
                        <div class='info-box-content' style='margin-left:60px;padding-top:0;'>
                            <span class='info-box-text' style='font-size:11px;'>Confirmed</span>
                            <span class='info-box-number' style='font-size:20px;'>{$confirmed}</span>
                        </div>
                    </div>
                </div>
                <div class='col-sm-2'>
                    <div class='info-box' style='min-height:60px;padding:8px 10px;'>
                        <span class='info-box-icon bg-yellow' style='height:60px;line-height:60px;width:50px;'><i class='fa fa-clock-o'></i></span>
                        <div class='info-box-content' style='margin-left:60px;padding-top:0;'>
                            <span class='info-box-text' style='font-size:11px;'>Pending</span>
                            <span class='info-box-number' style='font-size:20px;'>{$pending}</span>
                        </div>
                    </div>
                </div>
                <div class='col-sm-2'>
                    <div class='info-box' style='min-height:60px;padding:8px 10px;'>
                        <span class='info-box-icon bg-red' style='height:60px;line-height:60px;width:50px;'><i class='fa fa-times'></i></span>
                        <div class='info-box-content' style='margin-left:60px;padding-top:0;'>
                            <span class='info-box-text' style='font-size:11px;'>Failed</span>
                            <span class='info-box-number' style='font-size:20px;'>{$failed}</span>
                        </div>
                    </div>
                </div>
                <div class='col-sm-4'>
                    <div class='info-box' style='min-height:60px;padding:8px 10px;'>
                        <span class='info-box-icon bg-purple' style='height:60px;line-height:60px;width:50px;'><i class='fa fa-money'></i></span>
                        <div class='info-box-content' style='margin-left:60px;padding-top:0;'>
                            <span class='info-box-text' style='font-size:11px;'>Revenue Confirmed</span>
                            <span class='info-box-number' style='font-size:18px;'>UGX " . number_format($revenue, 0) . "</span>
                        </div>
                    </div>
                </div>
            </div>";
        });

        // ── Columns ───────────────────────────────────────────────────────────
        $grid->column('id', __('ID'))->sortable()->width(60);

        $grid->column('user.dtehm_member_id', __('DTEHM ID'))
            ->display(function () {
                if (!$this->user) return '<span class="text-muted">—</span>';
                $dtehmId = $this->user->dtehm_member_id;
                return $dtehmId
                    ? "<span class='label label-success' style='font-size:12px;letter-spacing:1px;'>{$dtehmId}</span>"
                    : '<span class="text-muted label label-default">No ID</span>';
            })->width(110);

        $grid->column('user.name', __('Member'))
            ->display(function () {
                if (!$this->user) return '<span class="text-muted">Unknown</span>';
                $name  = trim($this->user->first_name . ' ' . $this->user->last_name) ?: $this->user->name;
                $phone = $this->user->phone_number ?? '';
                $id    = $this->user->id;
                return "<a href='/admin/dtehm-members/{$id}'><strong>{$name}</strong></a>" .
                       ($phone ? "<br><small class='text-muted'>{$phone}</small>" : '');
            })->width(190);

        $grid->column('payment_reference', __('Reference'))
            ->copyable()
            ->label('info')
            ->width(200);

        $grid->column('amount', __('Amount'))
            ->display(function ($amount) {
                return "<strong style='color:#28a745;'>UGX " . number_format($amount, 0) . "</strong>";
            })->width(120);

        $grid->column('status', __('Status'))
            ->display(function ($status) {
                $colors = [
                    'PENDING'   => 'warning',
                    'CONFIRMED' => 'success',
                    'FAILED'    => 'danger',
                    'REFUNDED'  => 'secondary',
                ];
                $color = $colors[$status] ?? 'default';
                return "<span class='label label-{$color}'>{$status}</span>";
            })->filter([
                'PENDING'   => 'Pending',
                'CONFIRMED' => 'Confirmed',
                'FAILED'    => 'Failed',
                'REFUNDED'  => 'Refunded',
            ])->width(100);

        $grid->column('payment_method', __('Method'))
            ->display(function ($method) {
                $icons = [
                    'CASH'          => '<i class="fa fa-money"></i> Cash',
                    'MOBILE_MONEY'  => '<i class="fa fa-mobile"></i> Mobile Money',
                    'BANK_TRANSFER' => '<i class="fa fa-bank"></i> Bank',
                    'PESAPAL'       => '<i class="fa fa-credit-card"></i> Pesapal',
                ];
                return $icons[$method] ?? ($method ?: '—');
            })->filter([
                'CASH'          => 'Cash',
                'MOBILE_MONEY'  => 'Mobile Money',
                'BANK_TRANSFER' => 'Bank Transfer',
                'PESAPAL'       => 'Pesapal',
            ])->width(130);

        $grid->column('payment_date', __('Paid On'))
            ->display(function ($date) {
                return $date ? date('M d, Y', strtotime($date)) : '—';
            })->sortable()->width(100);

        $grid->column('confirmed_at', __('Confirmed'))
            ->display(function ($date) {
                return $date ? date('M d, Y H:i', strtotime($date)) : '—';
            })->sortable()->width(130);

        $grid->column('registeredBy.name', __('Reg. By'))
            ->display(function () {
                return $this->registeredBy ? $this->registeredBy->name : '—';
            })->width(120);

        // ── Filters ───────────────────────────────────────────────────────────
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('user.name', 'Member Name');
            $filter->like('user.phone_number', 'Phone Number');
            $filter->like('user.dtehm_member_id', 'DTEHM ID');
            $filter->like('payment_reference', 'Payment Reference');
            $filter->between('payment_date', 'Payment Date')->date();
            $filter->between('confirmed_at', 'Confirmed Date')->datetime();
            $filter->equal('status', 'Status')->select([
                'PENDING'   => 'Pending',
                'CONFIRMED' => 'Confirmed',
                'FAILED'    => 'Failed',
                'REFUNDED'  => 'Refunded',
            ]);
            $filter->equal('payment_method', 'Payment Method')->select([
                'CASH'          => 'Cash',
                'MOBILE_MONEY'  => 'Mobile Money',
                'BANK_TRANSFER' => 'Bank Transfer',
                'PESAPAL'       => 'Pesapal',
            ]);
        });

        // ── Actions ───────────────────────────────────────────────────────────
        $grid->actions(function ($actions) {
            $actions->add(new \App\Admin\Actions\ConfirmDtehmMembership());
        });

        $grid->batchActions(function ($batch) {
            $batch->add(new \App\Admin\Actions\BatchConfirmDtehmMembership());
        });

        // ── Export ────────────────────────────────────────────────────────────
        $grid->export(function ($export) {
            $export->filename('DTEHM_Memberships_' . date('Y-m-d'));
            $export->except(['deleted_at']);
        });

        $grid->disableCreateButton();

        return $grid;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DETAIL
    // ─────────────────────────────────────────────────────────────────────────
    protected function detail($id)
    {
        $show = new Show(DtehmMembership::findOrFail($id));

        $show->panel()->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableDelete();
        });

        // ── Member Information ────────────────────────────────────────────────
        $show->divider('Member Information');
        $show->field('user.dtehm_member_id', __('DTEHM ID'));
        $show->field('user.name', __('Member Name'));
        $show->field('user.phone_number', __('Phone Number'));
        $show->field('user.email', __('Email'));
        $show->field('user.address', __('Address'));

        // ── Payment Details ───────────────────────────────────────────────────
        $show->divider('Payment Details');
        $show->field('id', __('Membership Record ID'));
        $show->field('payment_reference', __('Payment Reference'))->copyable();
        $show->field('amount', __('Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        $show->field('status', __('Status'))->using([
            'PENDING'   => 'Pending',
            'CONFIRMED' => 'Confirmed',
            'FAILED'    => 'Failed',
            'REFUNDED'  => 'Refunded',
        ])->dot([
            'PENDING'   => 'warning',
            'CONFIRMED' => 'success',
            'FAILED'    => 'danger',
            'REFUNDED'  => 'secondary',
        ]);
        $show->field('payment_method', __('Payment Method'))->using([
            'CASH'          => 'Cash',
            'MOBILE_MONEY'  => 'Mobile Money',
            'BANK_TRANSFER' => 'Bank Transfer',
            'PESAPAL'       => 'Pesapal',
        ]);
        $show->field('payment_phone_number', __('Payment Phone'));
        $show->field('payment_account_number', __('Account Number'));
        $show->field('payment_date', __('Payment Date'));
        $show->field('confirmed_at', __('Confirmed At'));
        $show->field('receipt_photo', __('Receipt Photo'))->image();
        $show->field('description', __('Description'));
        $show->field('notes', __('Notes'));

        // ── Pesapal Details ───────────────────────────────────────────────────
        $show->divider('Pesapal Details');
        $show->field('pesapal_merchant_reference', __('Merchant Reference'));
        $show->field('pesapal_tracking_id', __('Tracking ID'));
        $show->field('pesapal_payment_status_code', __('Status Code'));
        $show->field('pesapal_payment_status_description', __('Status Description'));

        // ── Audit Trail ───────────────────────────────────────────────────────
        $show->divider('Audit Information');
        $show->field('registeredBy.name', __('Registered By'));
        $show->field('creator.name', __('Created By'));
        $show->field('confirmer.name', __('Confirmed By'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FORM
    // ─────────────────────────────────────────────────────────────────────────
    protected function form()
    {
        $form = new Form(new DtehmMembership());

        $form->html('<div class="alert alert-info">
            <strong><i class="fa fa-info-circle"></i> Note:</strong>
            DTEHM memberships are automatically created when admins register new users via
            <a href="/admin/dtehm-members/create">DTEHM Members &rarr; Create</a>.
            Use this form only to manually record or correct a membership payment.
        </div>');

        // ── Member ────────────────────────────────────────────────────────────
        $form->divider('Member');

        // Build select only from DTEHM members (is_dtehm_member = 'Yes')
        $form->select('user_id', __('DTEHM Member'))
            ->options(function ($id) {
                if ($id) {
                    $u = User::find($id);
                    return $u ? [$u->id => ($u->dtehm_member_id ? "[{$u->dtehm_member_id}] " : '') . $u->name . " — {$u->phone_number}"] : [];
                }
                return [];
            })
            ->ajax('/admin/api/users')
            ->rules('required')
            ->help('Search by name or phone number');

        // ── Payment Details ───────────────────────────────────────────────────
        $form->divider('Payment Details');

        if ($form->isEditing()) {
            $form->display('payment_reference', __('Payment Reference'));
        }

        $form->currency('amount', __('Amount (UGX)'))
            ->default(76000)
            ->rules('required')
            ->symbol('UGX')
            ->help('Default: 76,000 UGX');

        $form->select('status', __('Payment Status'))
            ->options([
                'PENDING'   => 'Pending',
                'CONFIRMED' => 'Confirmed',
                'FAILED'    => 'Failed',
                'REFUNDED'  => 'Refunded',
            ])
            ->default('PENDING')
            ->rules('required');

        $form->select('payment_method', __('Payment Method'))
            ->options([
                'CASH'          => 'Cash',
                'MOBILE_MONEY'  => 'Mobile Money',
                'BANK_TRANSFER' => 'Bank Transfer',
                'PESAPAL'       => 'Pesapal',
            ])
            ->default('CASH')
            ->rules('required');

        $form->row(function ($row) {
            $row->width(6)->text('payment_phone_number', __('Payment Phone Number'))
                ->help('Phone number used for payment');
            $row->width(6)->text('payment_account_number', __('Account Number'))
                ->help('Bank account or mobile money account');
        });

        $form->row(function ($row) {
            $row->width(6)->datetime('payment_date', __('Payment Date'))
                ->default(date('Y-m-d H:i:s'))
                ->rules('required');
            $row->width(6)->datetime('confirmed_at', __('Confirmed At'))
                ->help('Leave empty for pending payments');
        });

        // ── Supporting Documents ──────────────────────────────────────────────
        $form->divider('Supporting Documents');

        $form->image('receipt_photo', __('Receipt Photo'))
            ->uniqueName()
            ->move('receipts/dtehm')
            ->help('Upload payment receipt/proof');

        $form->row(function ($row) {
            $row->width(6)->textarea('description', __('Description'))
                ->rows(3);
            $row->width(6)->textarea('notes', __('Internal Notes'))
                ->rows(3)
                ->help('Not visible to member');
        });

        // ── Pesapal Integration ───────────────────────────────────────────────
        $form->divider('Pesapal Details (Optional)');

        $form->row(function ($row) {
            $row->width(6)->text('pesapal_merchant_reference', __('Pesapal Merchant Reference'));
            $row->width(6)->text('pesapal_tracking_id', __('Pesapal Tracking ID'));
        });

        $form->row(function ($row) {
            $row->width(6)->text('pesapal_payment_status_code', __('Pesapal Status Code'));
            $row->width(6)->text('confirmation_code', __('Confirmation Code'));
        });

        $form->textarea('pesapal_payment_status_description', __('Pesapal Status Description'))
            ->rows(2);

        // ── Hooks ─────────────────────────────────────────────────────────────
        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->created_by       = \Admin::user()->id;
                $form->registered_by_id = \Admin::user()->id;
            }
            $form->updated_by = \Admin::user()->id;

            if ($form->status === 'CONFIRMED' && !$form->confirmed_by) {
                $form->confirmed_by = \Admin::user()->id;
                if (!$form->confirmed_at) {
                    $form->confirmed_at = now();
                }
            }
        });

        $form->saved(function (Form $form) {
            admin_toastr('DTEHM Membership saved successfully', 'success');
        });

        $form->ignore(['created_at', 'updated_at', 'deleted_at']);

        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->disableCreatingCheck();

        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
