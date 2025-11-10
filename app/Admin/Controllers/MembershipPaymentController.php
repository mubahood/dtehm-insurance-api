<?php

namespace App\Admin\Controllers;

use App\Models\MembershipPayment;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class MembershipPaymentController extends AdminController
{
    protected $title = 'Membership Payments';

    protected function grid()
    {
        $grid = new Grid(new MembershipPayment());
        
        $grid->model()->orderBy('id', 'desc');
        $grid->disableExport();
        
        $grid->quickSearch('payment_reference')->placeholder('Search by payment reference');
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('user_id', 'User')
                ->select(User::where('user_type', 'insurance_user')->pluck('name', 'id'));
            $filter->equal('status', 'Status')->select([
                'PENDING' => 'Pending',
                'CONFIRMED' => 'Confirmed',
                'FAILED' => 'Failed',
                'REFUNDED' => 'Refunded',
            ]);
            $filter->equal('payment_method', 'Payment Method')->select([
                'CASH' => 'Cash',
                'MOBILE_MONEY' => 'Mobile Money',
                'BANK_TRANSFER' => 'Bank Transfer',
                'PESAPAL' => 'Pesapal',
            ]);
            $filter->equal('membership_type', 'Membership Type')->select([
                'LIFE' => 'Life',
                'ANNUAL' => 'Annual',
                'MONTHLY' => 'Monthly',
            ]);
            $filter->between('payment_date', 'Payment Date')->date();
            $filter->between('created_at', 'Created Date')->date();
        });

        $grid->column('id', __('ID'))
            ->sortable()
            ->width(60);
        
        $grid->column('payment_reference', __('Reference'))
            ->display(function ($ref) {
                return '<span style="color: #05179F; font-weight: 600;">' . $ref . '</span>';
            })
            ->sortable()
            ->width(180);
        
        $grid->column('user.name', __('User'))
            ->sortable()
            ->width(150);
        
        $grid->column('amount', __('Amount'))
            ->display(function ($amount) {
                return '<span style="color: #05179F; font-weight: 600;">UGX ' . number_format($amount, 0) . '</span>';
            })
            ->sortable()
            ->width(120);
        
        $grid->column('membership_type', __('Type'))
            ->label([
                'LIFE' => 'success',
                'ANNUAL' => 'warning',
                'MONTHLY' => 'info',
            ])
            ->sortable()
            ->width(90);
        
        $grid->column('payment_method', __('Method'))
            ->label([
                'CASH' => 'default',
                'MOBILE_MONEY' => 'primary',
                'BANK_TRANSFER' => 'info',
                'PESAPAL' => 'success',
            ])
            ->sortable()
            ->width(120);
        
        $grid->column('status', __('Status'))
            ->label([
                'PENDING' => 'warning',
                'CONFIRMED' => 'success',
                'FAILED' => 'danger',
                'REFUNDED' => 'default',
            ])
            ->editable('select', [
                'PENDING' => 'Pending',
                'CONFIRMED' => 'Confirmed',
                'FAILED' => 'Failed',
                'REFUNDED' => 'Refunded',
            ])
            ->sortable()
            ->width(100);
        
        $grid->column('payment_date', __('Payment Date'))
            ->display(function ($date) {
                return $date ? date('M d, Y', strtotime($date)) : '-';
            })
            ->sortable()
            ->width(110);
        
        $grid->column('confirmed_at', __('Confirmed'))
            ->display(function ($date) {
                if (!$date) return '<span style="color: #6c757d;">-</span>';
                return '<span style="color: #28a745;">' . date('M d, Y', strtotime($date)) . '</span>';
            })
            ->sortable()
            ->width(110);
        
        $grid->column('expiry_date', __('Expiry'))
            ->display(function ($date) {
                if (!$date) return '<span style="color: #28a745;">NEVER</span>';
                $timestamp = strtotime($date);
                $today = strtotime('today');
                $color = $timestamp < $today ? '#dc3545' : '#28a745';
                return '<span style="color: ' . $color . ';">' . date('M d, Y', $timestamp) . '</span>';
            })
            ->sortable()
            ->width(110);

        // Action buttons
        $grid->actions(function ($actions) {
            // Add custom confirm button if pending
            if ($this->status == 'PENDING') {
                $actions->prepend('<a href="' . route('admin.membership-payments.confirm', $this->id) . '" class="btn btn-xs btn-success" onclick="return confirm(\'Confirm this membership payment?\')"><i class="fa fa-check"></i> Confirm</a>');
            }
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(MembershipPayment::findOrFail($id));
        
        $show->field('id', __('ID'));
        $show->field('payment_reference', __('Payment Reference'));
        $show->field('user.name', __('User'));
        $show->field('amount', __('Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        $show->field('membership_type', __('Membership Type'));
        $show->field('status', __('Status'));
        $show->field('payment_method', __('Payment Method'));
        $show->field('payment_phone_number', __('Payment Phone'));
        $show->field('payment_account_number', __('Payment Account'));
        $show->field('payment_date', __('Payment Date'));
        $show->field('confirmed_at', __('Confirmed At'));
        $show->field('expiry_date', __('Expiry Date'));
        $show->field('description', __('Description'));
        $show->field('notes', __('Notes'));
        $show->field('receipt_photo', __('Receipt Photo'))->image();
        $show->field('confirmation_code', __('Confirmation Code'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));
        
        return $show;
    }

    protected function form()
    {
        $form = new Form(new MembershipPayment());
        
        // SECTION 1: User & Payment Information
        $form->divider('1. User & Payment Information');
        
        $form->select('user_id', __('User'))
            ->options(User::where('user_type', 'insurance_user')->pluck('name', 'id'))
            ->rules('required')
            ->required()
            ->help('Select the user making the membership payment');
        
        $form->text('payment_reference', __('Payment Reference'))
            ->readonly()
            ->help('Auto-generated upon creation');
        
        $form->decimal('amount', __('Amount (UGX)'))
            ->default(MembershipPayment::DEFAULT_AMOUNT)
            ->rules('required|numeric|min:0')
            ->required()
            ->help('Membership payment amount (default: UGX 20,000)');
        
        // SECTION 2: Membership Details
        $form->divider('2. Membership Details');
        
        $form->select('membership_type', __('Membership Type'))
            ->options([
                'LIFE' => 'Life (One-time payment, never expires)',
                'ANNUAL' => 'Annual (Valid for 1 year)',
                'MONTHLY' => 'Monthly (Valid for 1 month)',
            ])
            ->default('LIFE')
            ->rules('required')
            ->required()
            ->help('Type of membership');
        
        $form->date('expiry_date', __('Expiry Date'))
            ->help('Leave blank for LIFE membership. Auto-calculated for ANNUAL/MONTHLY on confirmation');
        
        // SECTION 3: Payment Method Details
        $form->divider('3. Payment Method Details');
        
        $form->select('payment_method', __('Payment Method'))
            ->options([
                'CASH' => 'Cash',
                'MOBILE_MONEY' => 'Mobile Money',
                'BANK_TRANSFER' => 'Bank Transfer',
                'PESAPAL' => 'Pesapal (Online)',
            ])
            ->default('CASH')
            ->help('How the payment was made');
        
        $form->text('payment_phone_number', __('Payment Phone Number'))
            ->help('Mobile money number (if applicable)');
        
        $form->text('payment_account_number', __('Payment Account Number'))
            ->help('Bank account or transaction reference');
        
        $form->date('payment_date', __('Payment Date'))
            ->default(date('Y-m-d'))
            ->rules('required')
            ->required()
            ->help('Date when payment was made');
        
        // SECTION 4: Payment Status
        $form->divider('4. Payment Status');
        
        $form->select('status', __('Payment Status'))
            ->options([
                'PENDING' => 'Pending - Awaiting confirmation',
                'CONFIRMED' => 'Confirmed - Payment verified',
                'FAILED' => 'Failed - Payment unsuccessful',
                'REFUNDED' => 'Refunded - Payment returned',
            ])
            ->default('PENDING')
            ->rules('required')
            ->required()
            ->help('Current status of the payment');
        
        $form->datetime('confirmed_at', __('Confirmed At'))
            ->help('Date and time when payment was confirmed (auto-set on confirmation)');
        
        $form->text('confirmation_code', __('Confirmation Code'))
            ->help('Payment confirmation code (if applicable)');
        
        // SECTION 5: Receipt & Documentation
        $form->divider('5. Receipt & Documentation');
        
        $form->image('receipt_photo', __('Receipt Photo'))
            ->move('membership/receipts')
            ->uniqueName()
            ->help('Upload payment receipt (for cash/bank transfer)');
        
        $form->textarea('description', __('Description'))
            ->rows(2)
            ->help('Brief description of the payment');
        
        $form->textarea('notes', __('Additional Notes'))
            ->rows(3)
            ->help('Any additional notes or comments');
        
        // SECTION 6: Payment Gateway Integration
        $form->divider('6. Payment Gateway Integration (Optional)');
        
        $form->text('pesapal_order_tracking_id', __('Pesapal Order Tracking ID'))
            ->help('Pesapal order tracking ID (for Pesapal payments)');
        
        $form->text('pesapal_merchant_reference', __('Pesapal Merchant Reference'))
            ->help('Pesapal merchant reference');
        
        $form->hidden('created_by')->default(auth()->id());
        $form->hidden('updated_by')->default(auth()->id());

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();
        
        // When saving, if status changed to CONFIRMED, trigger confirmation
        $form->saving(function (Form $form) {
            if ($form->status == 'CONFIRMED' && $form->model()->status != 'CONFIRMED') {
                // Will be confirmed after save
            }
        });
        
        $form->saved(function (Form $form) {
            if ($form->model()->status == 'CONFIRMED' && !$form->model()->confirmed_at) {
                $form->model()->confirm(auth()->id());
            }
        });
        
        return $form;
    }
    
    /**
     * Confirm membership payment
     */
    public function confirm($id)
    {
        try {
            $payment = MembershipPayment::findOrFail($id);
            
            if ($payment->status == MembershipPayment::STATUS_CONFIRMED) {
                admin_toastr('Payment already confirmed', 'warning');
                return back();
            }
            
            $payment->confirm(auth()->id());
            
            admin_toastr('Membership payment confirmed successfully', 'success');
            return back();
            
        } catch (\Exception $e) {
            admin_toastr('Error: ' . $e->getMessage(), 'error');
            return back();
        }
    }
}
