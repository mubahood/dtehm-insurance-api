<?php

namespace App\Admin\Controllers;

use App\Models\MembershipPayment;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class DipMemberController extends AdminController
{
    protected $title = 'DIP Members (20,000 UGX)';

    protected function grid()
    {
        $grid = new Grid(new User());

        // Only DIP members
        $grid->model()
            ->where('is_dip_member', 'Yes')
            ->orderBy('business_name', 'asc');

        $grid->column('id', __('ID'))->sortable()->width(60);

        $grid->column('business_name', __('DIP ID'))
            ->display(function ($dipId) {
                return $dipId
                    ? "<span class='label label-primary' style='font-size:12px;'>{$dipId}</span>"
                    : '-';
            })
            ->sortable()
            ->width(100);

        $grid->column('name', __('Member Name'))
            ->display(function () {
                $name = trim($this->first_name . ' ' . $this->last_name);
                $link = "<a href='/admin/dip-members/{$this->id}'><strong>{$name}</strong></a>";
                return "{$link}<br><small class='text-muted'>{$this->phone_number}</small>";
            })
            ->width(200);

        $grid->column('sex', __('Gender'))
            ->label(['Male' => 'info', 'Female' => 'danger'])
            ->width(80);

        $grid->column('sponsor_id', __('Sponsor'))
            ->display(function () {
                if (empty($this->sponsor_id)) {
                    return '<span class="text-muted">-</span>';
                }
                $sponsor = User::where('dtehm_member_id', $this->sponsor_id)
                    ->orWhere('business_name', $this->sponsor_id)
                    ->first();
                $label = $sponsor
                    ? "<strong>{$this->sponsor_id}</strong><br><small>{$sponsor->first_name} {$sponsor->last_name}</small>"
                    : "<span class='label label-warning'>{$this->sponsor_id}</span>";
                return $label;
            })
            ->width(140);

        $grid->column('is_dtehm_member', __('DTEHM?'))
            ->label(['Yes' => 'success', 'No' => 'default'])
            ->width(80);

        $grid->column('membership_payment_status', __('DIP Payment'))
            ->display(function () {
                $payment = MembershipPayment::where('user_id', $this->id)
                    ->orderBy('id', 'desc')
                    ->first();
                if (!$payment) {
                    return "<span class='label label-default'>No Payment</span>";
                }
                $colors = [
                    'CONFIRMED' => 'success',
                    'PENDING'   => 'warning',
                    'FAILED'    => 'danger',
                    'REFUNDED'  => 'secondary',
                ];
                $color = $colors[$payment->status] ?? 'default';
                $amount = 'UGX ' . number_format($payment->amount, 0);
                return "<span class='label label-{$color}'>{$payment->status}</span><br>
                        <small>{$amount}</small>";
            })
            ->width(130);

        $grid->column('account_balance', __('Balance (UGX)'))
            ->display(function ($balance) {
                return number_format($balance ?? 0, 0);
            })
            ->sortable()
            ->width(120);

        $grid->column('total_points', __('Points'))
            ->display(function ($pts) {
                return number_format($pts ?? 0, 0);
            })
            ->sortable()
            ->width(80);

        $grid->column('created_at', __('Registered'))
            ->display(function ($date) {
                return $date ? date('M d, Y', strtotime($date)) : '-';
            })
            ->sortable()
            ->width(110);

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('first_name', 'First Name');
            $filter->like('last_name', 'Last Name');
            $filter->like('phone_number', 'Phone Number');
            $filter->like('business_name', 'DIP ID');
            $filter->like('sponsor_id', 'Sponsor ID');
            $filter->equal('is_dtehm_member', 'DTEHM Member?')->select([
                'Yes' => 'Yes',
                'No'  => 'No',
            ]);
            $filter->between('created_at', 'Registration Date')->date();
        });

        $grid->export(function ($export) {
            $export->filename('DIP_Members_' . date('Y-m-d'));
            $export->except(['deleted_at']);
        });

        $grid->actions(function ($actions) {
            $actions->disableDelete();
        });

        $grid->disableBatchActions();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->panel()->tools(function ($tools) {
            $tools->disableDelete();
        });

        // Identity
        $show->divider('DIP Member Identity');
        $show->field('business_name', __('DIP ID'));
        $show->field('dtehm_member_id', __('DTEHM ID'))->as(function ($v) {
            return $v ?: 'N/A';
        });
        $show->field('id', __('System ID'));

        // Personal Information
        $show->divider('Personal Information');
        $show->field('first_name', __('First Name'));
        $show->field('last_name', __('Last Name'));
        $show->field('sex', __('Gender'));
        $show->field('phone_number', __('Phone Number'));
        $show->field('email', __('Email'));
        $show->field('address', __('Address'));
        $show->field('tribe', __('Tribe'));
        $show->field('country', __('Country'));

        // Membership Info
        $show->divider('Membership Information');
        $show->field('is_dip_member', __('DIP Member?'));
        $show->field('is_dtehm_member', __('DTEHM Member?'));
        $show->field('sponsor_id', __('Sponsor ID'));

        // Financial
        $show->divider('Financial');
        $show->field('account_balance', __('Balance (UGX)'))->as(function ($v) {
            return 'UGX ' . number_format($v ?? 0, 0);
        });
        $show->field('total_points', __('Points'));

        // DIP Membership Payments
        $show->divider('DIP Membership Payments');
        $show->relation('membershipPayments', function ($model) {
            $table = new Grid(new MembershipPayment());
            $table->model()->where('user_id', $model->id)->orderBy('id', 'desc');
            $table->column('id', 'ID')->width(50);
            $table->column('payment_reference', 'Reference');
            $table->column('amount', 'Amount')->display(function ($v) {
                return 'UGX ' . number_format($v, 0);
            });
            $table->column('status', 'Status')->display(function ($s) {
                $colors = ['CONFIRMED' => 'success', 'PENDING' => 'warning', 'FAILED' => 'danger'];
                $c = $colors[$s] ?? 'default';
                return "<span class='label label-{$c}'>{$s}</span>";
            });
            $table->column('payment_method', 'Method');
            $table->column('payment_date', 'Date');
            $table->disableCreateButton();
            $table->disableActions();
            $table->disablePagination();
            return $table;
        });

        // Timestamps
        $show->divider('Audit');
        $show->field('created_at', __('Registered At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new User());
        $form->disableCreatingCheck();
        $form->disableViewCheck();
        $form->disableReset();

        if ($form->isCreating()) {
            $form->html('<div class="alert alert-info">
                <strong>DIP Member Registration</strong><br>
                A DIP ID (<code>DIP001</code>, <code>DIP002</code>…) is auto-generated on save.<br>
                DIP Membership fee: <strong>UGX 20,000</strong>
            </div>');

            $form->hidden('user_type')->value('Customer');
            $form->hidden('is_dip_member')->value('Yes');

            $form->divider('Basic Information');

            $form->row(function ($row) {
                $row->width(3)->text('first_name', __('First Name'))->rules('required')->required();
                $row->width(3)->text('last_name', __('Last Name'))->rules('required')->required();
                $row->width(3)->radio('sex', __('Gender'))
                    ->options(['Male' => 'Male', 'Female' => 'Female'])
                    ->rules('required')->default('Male');
                $row->width(3)->text('phone_number', __('Phone Number'))->rules('required')->required();
            });

            $form->divider('Membership & Sponsor');

            $form->row(function ($row) {
                $sponsors = [];
                foreach (User::whereNotNull('dtehm_member_id')->orderBy('dtehm_member_id')->get() as $s) {
                    $sponsors[$s->dtehm_member_id] = $s->dtehm_member_id . ' - ' . $s->first_name . ' ' . $s->last_name;
                }

                $row->width(4)->select('is_dtehm_member', __('Also DTEHM Member?'))
                    ->options(['Yes' => 'Yes', 'No' => 'No'])
                    ->default('No')
                    ->rules('required');

                $row->width(4)->select('sponsor_id', __('Sponsor ID'))
                    ->options($sponsors)
                    ->rules('required')->required();

                $row->width(4)->select('stockist_area', __('Center'))
                    ->options(function () {
                        return User::where('is_stockist', 'Yes')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(function ($u) {
                                $label = $u->name;
                                if ($u->dtehm_member_id) $label .= " ({$u->dtehm_member_id})";
                                elseif ($u->business_name) $label .= " ({$u->business_name})";
                                return [$u->name => $label];
                            });
                    });
            });

            $form->divider('Payment');

            $form->html('<div class="alert alert-warning">
                <strong>DIP Membership Fee: UGX 20,000</strong><br>
                If not yet paid, a payment record will be created after registration.
            </div>');

            $form->radio('payment_status', __('Has Member Paid?'))
                ->options([
                    'paid'     => 'Yes — Member has already paid (UGX 20,000)',
                    'not_paid' => 'No — Process payment after registration',
                ])
                ->default('not_paid')
                ->rules('required');

            return $form;
        }

        // ── EDIT FORM ──────────────────────────────────────────────

        $form->html('<div class="alert alert-info">
            <strong>Editing DIP Member</strong> — DIP ID is auto-managed and cannot be changed manually.
        </div>');

        $form->divider('Personal Information');

        $form->row(function ($row) {
            $row->width(3)->text('first_name', __('First Name'))->rules('required');
            $row->width(3)->text('last_name', __('Last Name'))->rules('required');
            $row->width(3)->text('phone_number', __('Phone Number'))->rules('required');
            $row->width(3)->radio('sex', __('Gender'))
                ->options(['Male' => 'Male', 'Female' => 'Female'])
                ->default('Male');
        });

        $form->row(function ($row) {
            $row->width(4)->text('email', __('Email'));
            $row->width(4)->text('address', __('Address'));
            $row->width(4)->text('tribe', __('Tribe'));
        });

        $form->divider('Membership');

        $form->row(function ($row) {
            $sponsors = [];
            foreach (User::whereNotNull('dtehm_member_id')->orderBy('dtehm_member_id')->get() as $s) {
                $sponsors[$s->dtehm_member_id] = $s->dtehm_member_id . ' - ' . $s->first_name . ' ' . $s->last_name;
            }

            $row->width(4)->select('is_dip_member', __('DIP Member?'))
                ->options(['Yes' => 'Yes', 'No' => 'No'])
                ->rules('required');

            $row->width(4)->select('is_dtehm_member', __('DTEHM Member?'))
                ->options(['Yes' => 'Yes', 'No' => 'No']);

            $row->width(4)->select('sponsor_id', __('Sponsor ID'))
                ->options($sponsors);
        });

        $form->divider('DIP ID');
        $form->display('business_name', __('DIP ID'))->help('Auto-generated — read only');

        $form->divider('Account');
        $form->row(function ($row) {
            $row->width(6)->currency('account_balance', __('Balance (UGX)'))->symbol('UGX');
            $row->width(6)->number('total_points', __('Total Points'));
        });

        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->model()->is_dip_member = 'Yes';
            }
        });

        $form->saved(function (Form $form) {
            admin_toastr('DIP Member saved successfully', 'success');
        });

        $form->ignore(['created_at', 'updated_at', 'deleted_at']);
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
