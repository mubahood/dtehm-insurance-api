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
        
        $grid->column('dtehm_member_id', __('DTEHM ID'))
            ->display(function ($dtehmId) {
                if ($dtehmId) {
                    return "<span class='label label-success' style='font-size: 10px;'>$dtehmId</span>";
                }
                return "<span class='text-muted'>-</span>";
            })->width(110);
        
        $grid->column('downline_count', __('Total Downline'))
            ->display(function () {
                $total = $this->getTotalDownlineCount();
                return $total > 0 ? "<span class='badge bg-blue'>$total</span>" : '0';
            })->width(80);
        
        // Generation columns (Gen 1 to Gen 10)
        for ($i = 1; $i <= 10; $i++) {
            $grid->column("gen{$i}_count", __("Gen $i"))
                ->display(function () use ($i) {
                    $count = $this->getGenerationCount($i);
                    if ($count > 0) {
                        return "<span class='badge bg-green'>$count</span>";
                    }
                    return "<span class='text-muted'>0</span>";
                })->width(60);
        }
        
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
