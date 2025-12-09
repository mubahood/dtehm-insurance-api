<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\AccountTransaction;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
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

        
        $grid->column('stockist_area', __('Stockist Area'))
            ->display(function ($area) {
                return $area ?: '<span style="color: #999;">Not Set</span>';
            })
            ->sortable()->width(150);

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

        // Monthly Members
        $grid->column('monthly_members', 'Monthly Members')->expand(function ($model) {
            $comments = [];
            $startDate = now()->subDays(30);
            $members = User::where('parent_1', $model->id)
                ->where('created_at', '>=', $startDate)
                ->get();
            foreach ($members as $member) {
                $comments[] = [
                    'DTEHM ID' => $member->id,
                    'Name' =>  $member->first_name . ' ' . $member->last_name,
                    'Contact' => $member->phone_number,
                ];
            }
            return new Table(['DTEHM ID', 'Name', 'Contact'], $comments);
        });

        // Weekly Members
        $grid->column('weekly_members', 'Weekly Members')->expand(function ($model) {
            $startDate = now()->subDays(7);
            $comments = [];
            $members = User::where('parent_1', $model->id)
                ->where('created_at', '>=', $startDate)
                ->get();
            foreach ($members as $member) {
                $comments[] = [
                    'DTEHM ID' => $member->id,
                    'Name' =>  $member->first_name . ' ' . $member->last_name,
                    'Contact' => $member->phone_number,
                ];
            }
            return new Table(['DTEHM ID', 'Name', 'Contact'], $comments);
        });

        // All Time Members
        $grid->column('all_time_members', 'All Time Members')->expand(function ($model) {
            $comments = [];
            $members = User::where('parent_1', $model->id)
                ->get();
            foreach ($members as $member) {
                $comments[] = [
                    'DTEHM ID' => $member->id,
                    'Name' =>  $member->first_name . ' ' . $member->last_name,
                    'Contact' => $member->phone_number,
                ];
            }
            return new Table(['DTEHM ID', 'Name', 'Contact'], $comments);
        });

        // Sales as Sponsor
        $grid->column('products_as_sponsor', 'Sales as Sponsor')->expand(function ($model) {
            $comments = [];
            $products = \App\Models\OrderedItem::where('sponsor_user_id', $model->id)
                ->with('pro')
                ->orderBy('created_at', 'desc')
                ->get();
            
            foreach ($products as $product) {
                $productName = $product->pro ? $product->pro->name : 'Product #' . $product->product;
                $comments[] = [
                    'Order ID' => $product->order,
                    'Product' => $productName,
                    'Qty' => $product->qty,
                    'Amount' => 'UGX ' . number_format($product->subtotal, 0),
                    'Date' => date('d M Y', strtotime($product->created_at)),
                ];
            }
            return new Table(['Order ID', 'Product', 'Qty', 'Amount', 'Date'], $comments);
        });

        // Sales as Stockist
        $grid->column('products_as_stockist', 'Sales as Stockist')->expand(function ($model) {
            $comments = [];
            $products = \App\Models\OrderedItem::where('stockist_user_id', $model->id)
                ->with('pro')
                ->orderBy('created_at', 'desc')
                ->get();
            
            foreach ($products as $product) {
                $productName = $product->pro ? $product->pro->name : 'Product #' . $product->product;
                $comments[] = [
                    'Order ID' => $product->order,
                    'Product' => $productName,
                    'Qty' => $product->qty,
                    'Amount' => 'UGX ' . number_format($product->subtotal, 0),
                    'Commission' => 'UGX ' . number_format($product->commission_stockist ?? 0, 0),
                    'Date' => date('d M Y', strtotime($product->created_at)),
                ];
            }
            return new Table(['Order ID', 'Product', 'Qty', 'Amount', 'Commission', 'Date'], $comments);
        });

        $grid->disableActions();

        $grid->quickSearch('first_name', 'last_name', 'phone_number', 'business_name', 'dtehm_member_id', 'stockist_area')
            ->placeholder('Search by name, phone, ID, or area');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('first_name', 'First Name');
            $filter->like('last_name', 'Last Name');
            $filter->like('phone_number', 'Phone Number');
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
