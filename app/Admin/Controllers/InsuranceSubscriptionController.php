<?php

namespace App\Admin\Controllers;

use App\Models\InsuranceSubscription;
use App\Models\InsuranceProgram;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class InsuranceSubscriptionController extends AdminController
{
    protected $title = 'Insurance Subscriptions';

    protected function grid()
    {
        $grid = new Grid(new InsuranceSubscription());
        
        $grid->model()->orderBy('id', 'desc');
        $grid->disableExport();
        $grid->disableCreateButton();
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('insurance_program_id', 'Program')
                ->select(InsuranceProgram::pluck('name', 'id'));
            $filter->equal('status', 'Status')->select([
                'active' => 'Active',
                'suspended' => 'Suspended',
                'cancelled' => 'Cancelled',
                'expired' => 'Expired',
            ]);
        });

        $grid->column('id', __('ID'))->sortable();
        $grid->column('user.name', __('User'))->sortable();
        $grid->column('insuranceProgram.name', __('Program'))->sortable();
        $grid->column('policy_number', __('Policy #'))->sortable();
        $grid->column('payment_status', __('Payment'))->label([
            'paid' => 'success',
            'pending' => 'warning',
            'overdue' => 'danger',
        ])->sortable();
        $grid->column('coverage_status', __('Coverage'))->label([
            'active' => 'success',
            'suspended' => 'warning',
            'cancelled' => 'danger',
        ])->sortable();
        $grid->column('status', __('Status'))->label([
            'active' => 'success',
            'suspended' => 'warning',
            'cancelled' => 'danger',
            'expired' => 'default',
        ])->sortable();
        $grid->column('next_billing_date', __('Next Billing'))
            ->display(function ($date) {
                return $date ? date('d M Y', strtotime($date)) : '-';
            })
            ->sortable();
        $grid->column('start_date', __('Start Date'))
            ->display(function ($date) {
                return $date ? date('d M Y', strtotime($date)) : '-';
            })
            ->sortable();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(InsuranceSubscription::findOrFail($id));
        $show->field('id', __('ID'));
        $show->field('policy_number', __('Policy Number'));
        $show->field('user.name', __('User'));
        $show->field('insuranceProgram.name', __('Program'));
        $show->field('premium_amount', __('Premium'))->as(function ($premium) {
            return 'UGX ' . number_format($premium, 0);
        });
        $show->field('total_expected', __('Total Expected'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        $show->field('total_paid', __('Total Paid'))->as(function ($paid) {
            return 'UGX ' . number_format($paid, 0);
        });
        $show->field('total_balance', __('Balance'))->as(function ($balance) {
            return 'UGX ' . number_format($balance, 0);
        });
        $show->field('payment_status', __('Payment Status'));
        $show->field('coverage_status', __('Coverage Status'));
        $show->field('status', __('Status'));
        $show->field('next_billing_date', __('Next Billing Date'));
        $show->field('start_date', __('Start Date'));
        $show->field('end_date', __('End Date'));
        $show->field('beneficiaries', __('Beneficiaries'));
        $show->field('notes', __('Notes'));
        $show->field('created_at', __('Created At'));
        return $show;
    }

    protected function form()
    {
        $form = new Form(new InsuranceSubscription());
        $form->select('user_id', __('User'))
            ->options(User::pluck('name', 'id'))
            ->rules('required');
        $form->select('insurance_program_id', __('Program'))
            ->options(InsuranceProgram::pluck('name', 'id'))
            ->rules('required');
        $form->date('start_date', __('Start Date'))
            ->default(date('Y-m-d'))
            ->rules('required');
        $form->date('end_date', __('End Date'))
            ->rules('required');
        $form->date('next_billing_date', __('Next Billing Date'))
            ->rules('required');
        $form->select('status', __('Status'))
            ->options([
                'active' => 'Active',
                'suspended' => 'Suspended',
                'cancelled' => 'Cancelled',
                'expired' => 'Expired',
            ])
            ->default('active')
            ->rules('required');
        $form->select('payment_status', __('Payment Status'))
            ->options([
                'paid' => 'Paid',
                'pending' => 'Pending',
                'overdue' => 'Overdue',
            ])
            ->default('pending')
            ->rules('required');
        $form->select('coverage_status', __('Coverage Status'))
            ->options([
                'active' => 'Active',
                'suspended' => 'Suspended',
                'cancelled' => 'Cancelled',
            ])
            ->default('active')
            ->rules('required');
        $form->textarea('beneficiaries', __('Beneficiaries'))
            ->help('Enter beneficiary details')
            ->rows(3);
        $form->textarea('notes', __('Notes'))
            ->rows(3);
        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();
        return $form;
    }
}
