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
        
        $grid->column('full_name', __('Name & Phone'))
            ->display(function () {
                $name = trim($this->first_name . ' ' . $this->last_name);
                $phone = $this->phone_number ? '<br><small class="text-muted"><i class="fa fa-phone"></i> ' . $this->phone_number . '</small>' : '';
                return $name . $phone;
            })->sortable()->width(200);
        
        $grid->column('business_name', __('DIP ID'))->sortable()->width(100);
        
        $grid->column('dtehm_member_id', __('DTEHM ID'))
            ->display(function ($dtehmId) {
                if ($dtehmId) {
                    return "<span class='label label-success' style='font-size: 10px;'>$dtehmId</span>";
                }
                return "<span class='text-muted'>-</span>";
            })->width(110);
        
        $grid->column('sponsor_id', __('Sponsor ID'))
            ->display(function ($sponsorId) {
                if (empty($sponsorId)) {
                    return '<span class="text-muted">-</span>';
                }
                return '<span class="label label-primary" style="font-size: 10px;">' . $sponsorId . '</span>';
            })->width(100);
        
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
        
        $grid->column('view_tree', __('View Tree'))
            ->display(function () {
                $url = url('/admin/user-hierarchy/' . $this->id);
                return '<a href="' . $url . '" target="_blank" class="btn btn-xs btn-primary" title="View Network Tree">
                    <i class="fa fa-sitemap"></i> Tree
                </a>';
            })->width(90);
        
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
