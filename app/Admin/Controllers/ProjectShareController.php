<?php

namespace App\Admin\Controllers;

use App\Models\ProjectShare;
use App\Models\Project;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ProjectShareController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Project Shares (Investments)';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ProjectShare());

        $grid->model()->orderBy('id', 'desc');
        $grid->disableExport();
        $grid->disableCreateButton();

        $grid->quickSearch('id')->placeholder('Search by ID');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->equal('project_id', 'Project')
                ->select(Project::pluck('title', 'id'));

            $filter->equal('investor_id', 'Investor')
                ->select(User::pluck('name', 'id'));

            $filter->between('purchase_date', 'Purchase Date')->date();
        });

        $grid->column('id', __('ID'))->sortable();

        $grid->column('investor.name', __('Investor'))
            ->sortable();

        $grid->column('investor.phone_number', __('Phone'));

        $grid->column('project.title', __('Project'))
            ->display(function ($title) {
                return \Illuminate\Support\Str::limit($title, 30);
            })
            ->sortable();

        $grid->column('number_of_shares', __('Shares'))
            ->sortable();

        $grid->column('share_price_at_purchase', __('Price/Share'))
            ->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            });

        $grid->column('total_amount_paid', __('Total Paid'))
            ->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            })
            ->sortable();

        $grid->column('purchase_date', __('Date'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })
            ->sortable();

        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('d M Y, H:i', strtotime($date));
            })
            ->sortable();

        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
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
        $show = new Show(ProjectShare::findOrFail($id));

        $show->field('id', __('ID'));

        $show->field('investor.name', __('Investor'));
        $show->field('investor.phone_number', __('Phone'));
        $show->field('investor.email', __('Email'));

        $show->field('project.title', __('Project'));
        $show->field('project.status', __('Project Status'));

        $show->field('number_of_shares', __('Number of Shares'));

        $show->field('amount_per_share', __('Amount per Share'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });

        $show->field('total_amount_paid', __('Total Amount Paid'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });

        $show->field('payment_status', __('Payment Status'));
        $show->field('purchase_date', __('Purchase Date'));
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
        $form = new Form(new ProjectShare());

        $form->display('id', __('ID'));

        $form->select('investor_id', __('Investor'))
            ->options(User::pluck('name', 'id'))
            ->rules('required');

        $form->select('project_id', __('Project'))
            ->options(Project::pluck('title', 'id'))
            ->rules('required');

        $form->decimal('number_of_shares', __('Number of Shares'))
            ->rules('required|integer|min:1');

     

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();

        return $form;
    }
}
