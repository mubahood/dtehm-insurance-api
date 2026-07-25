<?php

namespace App\Admin\Controllers;

use App\Models\AccountTransaction;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AccountTransactionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Account Transactions';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AccountTransaction());
        
        $grid->model()->orderBy('transaction_date', 'desc');

        $grid->quickSearch('description')->placeholder('Search by description');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->equal('user_id', 'User')
                ->select(User::pluck('name', 'id'));

            $filter->equal('source', 'Source')
                ->select([
                    'disbursement' => 'Disbursement',
                    'withdrawal' => 'Withdrawal',
                    'deposit' => 'Deposit',
                ]);

            $filter->between('transaction_date', 'Date')->date();

            // Shop filter — narrows to transactions belonging to members of a specific shop
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

        $grid->export(function ($export) {
            $export->filename('Account Transactions');
            $export->column('id', 'ID');
            $export->column('created_at', 'Date');
            $export->column('user.name', 'User');
            $export->column('amount', 'Amount');
            $export->column('source', 'Source');
            $export->column('description', 'Description');
            $export->column('transaction_date', 'Transaction Date');
        });

        $grid->column('id', __('ID'))->sortable();

        $grid->column('user.name', __('User'))
            ->sortable();

        // Shop column
        $grid->column('user.shop.name', __('Shop'))
            ->display(function () {
                $shopName = optional(optional($this->user)->shop)->name;
                return $shopName
                    ? "<span style='background:#00c0ef;color:#fff;font-size:10px;padding:2px 7px;border-radius:3px;'>{$shopName}</span>"
                    : '<span style="color:#ccc;font-size:11px;">—</span>';
            })
            ->width(140);
        
        
        $grid->column('amount', __('Amount'))
            ->display(function ($amount) {
                $prefix = $amount >= 0 ? '+' : '';
                return $prefix . 'UGX ' . number_format($amount, 0);
            })
            ->sortable();
        
        $grid->column('source', __('Source'))
            ->label([
                'disbursement' => 'primary',
                'withdrawal' => 'danger',
                'deposit' => 'success',
            ])
            ->sortable();
        
        $grid->column('description', __('Description'))
            ->display(function ($desc) {
                return \Illuminate\Support\Str::limit($desc, 220);
            });
        
        $grid->column('transaction_date', __('Date'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })
            ->sortable();
        
        $grid->column('creator.name', __('Created By'));
        
        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('d M Y, H:i', strtotime($date));
            })
            ->sortable();

        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableView();
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
        $show = new Show(AccountTransaction::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('user.name', __('User'));
        $show->field('user.phone_number', __('Phone'));
        $show->field('user.email', __('Email'));
        
        $show->field('amount', __('Amount'))->as(function ($amount) {
            $prefix = $amount >= 0 ? '+' : '';
            return $prefix . 'UGX ' . number_format($amount, 0);
        });
        
        $show->field('source', __('Source'));
        $show->field('description', __('Description'));
        $show->field('transaction_date', __('Transaction Date'));
        
        $show->field('relatedDisbursement.project.title', __('Related Project'));
        
        $show->field('creator.name', __('Created By'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AccountTransaction());

        $form->select('user_id', __('User'))
            ->options(User::pluck('name', 'id'))
            ->rules('required');
        
        $form->decimal('amount', __('Amount (UGX)'))
            ->rules('required|numeric')
            ->help('Positive for deposit/disbursement, negative for withdrawal');
        
        $form->select('source', __('Source'))
            ->options([
                'deposit' => 'Deposit',
                'withdrawal' => 'Withdrawal',
                'disbursement' => 'Disbursement',
            ])
            ->rules('required');
        
        $form->textarea('description', __('Description'))
            ->rules('required')
            ->rows(3);
        
        $form->date('transaction_date', __('Transaction Date'))
            ->default(date('Y-m-d'))
            ->rules('required');
        
        $form->hidden('created_by_id')->default(auth()->id());

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();

        return $form;
    }
}
