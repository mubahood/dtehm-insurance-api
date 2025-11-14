<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Facades\Admin;

class UserHierarchyController extends AdminController
{
    protected $title = 'User Hierarchy & Network';

    protected function grid()
    {
        $grid = new Grid(new User());
        $grid->model()->orderBy('id', 'desc');
        
        $grid->disableBatchActions();
        $grid->disableCreateButton();
        $grid->disableExport();
        
        $grid->column('id', __('ID'))->sortable()->width(60);
        $grid->column('avatar', __('Photo'))->lightbox(['width' => 50, 'height' => 50])->width(60);
        
        $grid->column('full_name', __('Full Name'))
            ->display(function () {
                return trim($this->first_name . ' ' . $this->last_name);
            })->sortable()->width(180);
        
        $grid->column('business_name', __('DIP ID'))->sortable()->width(100);
        
        $grid->column('downline_count', __('Total Downline'))
            ->display(function () {
                $total = $this->getTotalDownlineCount();
                return $total > 0 ? "<span class='badge bg-blue'>$total</span>" : '0';
            })->width(100);
        
        $grid->column('gen1_count', __('Gen 1'))
            ->display(function () {
                return $this->getGenerationCount(1);
            })->width(70);
        
        $grid->column('phone_number', __('Phone'))->width(120);
        $grid->column('status', __('Status'))->width(90);
        
        $grid->quickSearch('first_name', 'last_name', 'business_name');
        
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });
        
        return $grid;
    }

    protected function detail($id)
    {
        $user = User::findOrFail($id);
        
        return Admin::content(function (Content $content) use ($user) {
            $content->title('User Network Hierarchy');
            $content->description($user->name);
            $content->body(view('admin.user-hierarchy.tree', compact('user')));
        });
    }
}
