<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\AccountTransaction;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class StockistController extends AdminController
{
    protected $title = 'Stockists';

    protected function grid()
    {
        $grid = new Grid(new User());
        
        $grid->model()->where('is_stockist', 'Yes')->orderBy('id', 'desc');
        
        $grid->disableBatchActions();
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });

        $grid->column('id', __('ID'))->sortable()->width(60)->style('font-weight: bold; color: #05179F;');
        $grid->column('avatar', __('Photo'))->lightbox(['width' => 50, 'height' => 50])->width(60);
        
        $grid->column('name', __('Full Name'))
            ->display(function () {
                return '<div style="line-height: 1.3;">
                    <strong>' . trim($this->first_name . ' ' . $this->last_name) . '</strong>
                    <br><small style="color: #666;">' . $this->phone_number . '</small>
                </div>';
            })
            ->sortable()->width(180);

        $grid->column('sex', __('Gender'))->label(['Male' => 'info', 'Female' => 'danger'])->width(80);
        $grid->column('business_name', __('DIP ID'))->label('primary')->sortable()->width(120);
        $grid->column('dtehm_member_id', __('DTEHM ID'))->label('success')->sortable()->width(120);
        
        $grid->column('stockist_area', __('Stockist Area'))
            ->display(function ($area) {
                return $area ?: '<span style="color: #999;">Not Set</span>';
            })
            ->sortable()->width(150);

        $grid->column('is_dip_member', __('DIP'))->label(['Yes' => 'success', 'No' => 'default'])->width(70);
        $grid->column('is_dtehm_member', __('DTEHM'))->label(['Yes' => 'success', 'No' => 'default'])->width(70);

        $grid->column('total_commission', __('Total Commission'))
            ->display(function () {
                $total = AccountTransaction::where('user_id', $this->id)
                    ->where('amount', '>', 0)
                    ->where('source', 'LIKE', '%commission%')
                    ->sum('amount');
                return '<strong style="color: #28a745;">UGX ' . number_format($total, 0) . '</strong>';
            })->width(150);

        $grid->column('total_withdrawn', __('Total Withdrawn'))
            ->display(function () {
                $withdrawn = AccountTransaction::where('user_id', $this->id)
                    ->where('amount', '<', 0)
                    ->where('source', 'LIKE', '%withdrawal%')
                    ->sum('amount');
                return '<strong style="color: #dc3545;">UGX ' . number_format(abs($withdrawn), 0) . '</strong>';
            })->width(150);

        $grid->column('current_balance', __('Current Balance'))
            ->display(function () {
                $balance = AccountTransaction::where('user_id', $this->id)->sum('amount');
                $color = $balance >= 0 ? '#28a745' : '#dc3545';
                return '<strong style="color: ' . $color . '; font-size: 14px;">UGX ' . number_format($balance, 0) . '</strong>';
            })->width(150);

        $grid->column('created_at', __('Registered'))
            ->display(function ($created_at) {
                return date('d M Y', strtotime($created_at));
            })->sortable()->width(120);

        $grid->quickSearch('first_name', 'last_name', 'phone_number', 'business_name', 'dtehm_member_id', 'stockist_area')
            ->placeholder('Search by name, phone, ID, or area');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('first_name', 'First Name');
            $filter->like('last_name', 'Last Name');
            $filter->like('phone_number', 'Phone Number');
            $filter->like('business_name', 'DIP ID');
            $filter->like('dtehm_member_id', 'DTEHM ID');
            $filter->like('stockist_area', 'Stockist Area');
            $filter->equal('sex', 'Gender')->radio(['' => 'All', 'Male' => 'Male', 'Female' => 'Female']);
            $filter->equal('is_dtehm_member', 'DTEHM Member')->radio(['' => 'All', 'Yes' => 'Yes', 'No' => 'No']);
            $filter->equal('is_dip_member', 'DIP Member')->radio(['' => 'All', 'Yes' => 'Yes', 'No' => 'No']);
            $filter->between('created_at', 'Registered Date')->date();
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));
        $show->field('id', __('ID'));
        $show->field('first_name', __('First Name'));
        $show->field('last_name', __('Last Name'));
        $show->field('phone_number', __('Phone Number'));
        $show->field('stockist_area', __('Stockist Area'));
        $show->field('created_at', __('Created at'));
        return $show;
    }
}
