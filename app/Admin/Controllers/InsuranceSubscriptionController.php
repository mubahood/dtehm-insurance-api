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
        
        $grid->quickSearch('policy_number')->placeholder('Search by policy number');
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('user_id', 'User')
                ->select(User::where('user_type', 'insurance_user')->pluck('name', 'id'));
            $filter->equal('insurance_program_id', 'Program')
                ->select(InsuranceProgram::pluck('name', 'id'));
            $filter->equal('payment_status', 'Payment Status')->select([
                'paid' => 'Paid',
                'pending' => 'Pending',
                'overdue' => 'Overdue',
            ]);
            $filter->equal('coverage_status', 'Coverage Status')->select([
                'active' => 'Active',
                'suspended' => 'Suspended',
                'cancelled' => 'Cancelled',
            ]);
            $filter->equal('status', 'Status')->select([
                'active' => 'Active',
                'suspended' => 'Suspended',
                'cancelled' => 'Cancelled',
                'expired' => 'Expired',
            ]);
            $filter->between('start_date', 'Start Date')->date();
            $filter->between('next_billing_date', 'Next Billing Date')->date();
        });

        $grid->column('id', __('ID'))
            ->sortable()
            ->width(60);
        
        $grid->column('policy_number', __('Policy #'))
            ->display(function ($policy) {
                return '<span style="color: #05179F; font-weight: 600;">' . $policy . '</span>';
            })
            ->sortable()
            ->width(140);
        
        $grid->column('user.name', __('User'))
            ->sortable()
            ->width(150);
        
        $grid->column('insuranceProgram.name', __('Program'))
            ->sortable()
            ->width(180);
        
        $grid->column('premium_amount', __('Premium'))
            ->display(function ($amount) {
                return '<span style="color: #05179F;">UGX ' . number_format($amount, 0) . '</span>';
            })
            ->sortable()
            ->width(110);
        
        $grid->column('total_balance', __('Balance'))
            ->display(function ($balance) {
                if ($balance > 0) {
                    return '<span style="color: #dc3545; font-weight: 600;">UGX ' . number_format($balance, 0) . '</span>';
                } else {
                    return '<span style="color: #28a745;">UGX 0</span>';
                }
            })
            ->sortable()
            ->width(120);
        
        $grid->column('payment_status', __('Payment'))
            ->label([
                'paid' => 'success',
                'pending' => 'warning',
                'overdue' => 'danger',
            ])
            ->sortable()
            ->width(90);
        
        $grid->column('coverage_status', __('Coverage'))
            ->label([
                'active' => 'success',
                'suspended' => 'warning',
                'cancelled' => 'danger',
            ])
            ->sortable()
            ->width(95);
        
        $grid->column('status', __('Status'))
            ->label([
                'active' => 'success',
                'suspended' => 'warning',
                'cancelled' => 'danger',
                'expired' => 'default',
            ])
            ->editable('select', [
                'active' => 'Active',
                'suspended' => 'Suspended',
                'cancelled' => 'Cancelled',
                'expired' => 'Expired',
            ])
            ->sortable()
            ->width(90);
        
        $grid->column('next_billing_date', __('Next Billing'))
            ->display(function ($date) {
                if (!$date) return '<span style="color: #6c757d;">-</span>';
                $timestamp = strtotime($date);
                $today = strtotime('today');
                $color = $timestamp < $today ? '#dc3545' : '#28a745';
                return '<span style="color: ' . $color . ';">' . date('M d, Y', $timestamp) . '</span>';
            })
            ->sortable()
            ->width(110);
        
        $grid->column('start_date', __('Start Date'))
            ->display(function ($date) {
                return $date ? date('M d, Y', strtotime($date)) : '-';
            })
            ->sortable()
            ->width(100);

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
        
        // SECTION 1: Subscription Details
        $form->divider('1. Subscription Details');
        
        $form->select('user_id', __('Insurance User'))
            ->options(User::where('user_type', 'insurance_user')->pluck('name', 'id'))
            ->rules('required')
            ->required()
            ->ajax('/admin/api/users?user_type=insurance_user')
            ->help('Select the user subscribing to the insurance program');
        
        $form->select('insurance_program_id', __('Insurance Program'))
            ->options(InsuranceProgram::where('status', 'Active')->pluck('name', 'id'))
            ->rules('required')
            ->required()
            ->help('Select the insurance program to subscribe to');
        
        $form->text('policy_number', __('Policy Number'))
            ->readonly()
            ->help('Auto-generated upon creation');
        
        // SECTION 2: Subscription Dates
        $form->divider('2. Subscription Dates');
        
        $form->date('start_date', __('Start Date'))
            ->default(date('Y-m-d'))
            ->rules('required')
            ->required()
            ->help('Date when the subscription begins');
        
        $form->date('end_date', __('End Date'))
            ->rules('required')
            ->required()
            ->help('Date when the subscription ends (auto-calculated based on program duration)');
        
        $form->date('next_billing_date', __('Next Billing Date'))
            ->rules('required')
            ->required()
            ->help('Date of the next premium payment');
        
        // SECTION 3: Coverage Dates
        $form->divider('3. Coverage Dates');
        
        $form->date('coverage_start_date', __('Coverage Start Date'))
            ->help('Date when coverage becomes active (usually after first payment)');
        
        $form->date('coverage_end_date', __('Coverage End Date'))
            ->help('Date when coverage ends');
        
        // SECTION 4: Financial Information
        $form->divider('4. Financial Information');
        
        $form->decimal('premium_amount', __('Premium Amount (UGX)'))
            ->readonly()
            ->help('Auto-filled from selected program');
        
        $form->decimal('total_expected', __('Total Expected (UGX)'))
            ->readonly()
            ->help('Total amount expected for entire subscription period');
        
        $form->decimal('total_paid', __('Total Paid (UGX)'))
            ->readonly()
            ->help('Total amount paid so far');
        
        $form->decimal('total_balance', __('Balance (UGX)'))
            ->readonly()
            ->help('Remaining balance to be paid');
        
        $form->number('payments_completed', __('Payments Completed'))
            ->readonly()
            ->help('Number of payments completed');
        
        $form->number('payments_pending', __('Payments Pending'))
            ->readonly()
            ->help('Number of payments still pending');
        
        // SECTION 5: Beneficiaries
        $form->divider('5. Beneficiaries Information');
        
        $form->textarea('beneficiaries', __('Beneficiaries'))
            ->rows(4)
            ->help('Enter beneficiary details (Name, Relationship, Contact)');
        
        // SECTION 6: Status Management
        $form->divider('6. Status Management');
        
        $form->select('status', __('Subscription Status'))
            ->options([
                'active' => 'Active - Subscription is active',
                'suspended' => 'Suspended - Temporarily disabled',
                'cancelled' => 'Cancelled - Permanently cancelled',
                'expired' => 'Expired - Subscription period ended',
            ])
            ->default('active')
            ->rules('required')
            ->required()
            ->help('Current status of the subscription');
        
        $form->select('payment_status', __('Payment Status'))
            ->options([
                'paid' => 'Paid - All payments up to date',
                'pending' => 'Pending - Payment expected',
                'overdue' => 'Overdue - Payment is late',
            ])
            ->default('pending')
            ->rules('required')
            ->required()
            ->help('Current payment status');
        
        $form->select('coverage_status', __('Coverage Status'))
            ->options([
                'active' => 'Active - Coverage is active',
                'suspended' => 'Suspended - Coverage temporarily suspended',
                'cancelled' => 'Cancelled - Coverage permanently cancelled',
            ])
            ->default('active')
            ->rules('required')
            ->required()
            ->help('Current coverage status');
        
        // SECTION 7: Suspension & Cancellation Details
        $form->divider('7. Suspension & Cancellation Details');
        
        $form->date('suspended_date', __('Suspended Date'))
            ->help('Date when subscription was suspended (if applicable)');
        
        $form->textarea('suspension_reason', __('Suspension Reason'))
            ->rows(2)
            ->help('Reason for suspension');
        
        $form->date('cancelled_date', __('Cancelled Date'))
            ->help('Date when subscription was cancelled (if applicable)');
        
        $form->textarea('cancellation_reason', __('Cancellation Reason'))
            ->rows(2)
            ->help('Reason for cancellation');
        
        // SECTION 8: Additional Notes
        $form->divider('8. Additional Notes');
        
        $form->textarea('notes', __('Notes'))
            ->rows(3)
            ->help('Any additional notes or comments');
        
        $form->hidden('created_by')->default(auth()->id());
        $form->hidden('updated_by')->default(auth()->id());

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();
        
        return $form;
    }
}
