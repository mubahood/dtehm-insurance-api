<?php

namespace App\Admin\Controllers;

use App\Models\Disbursement;
use App\Models\Project;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DisbursementController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Disbursements';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Disbursement());
        
        $grid->model()->orderBy('disbursement_date', 'desc');
        $grid->disableExport();
        
        $grid->quickSearch('description')->placeholder('Search by description');
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            $filter->equal('project_id', 'Project')
                ->select(Project::pluck('title', 'id'));
            
            $filter->between('disbursement_date', 'Date')->date();
        });

        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('project.title', __('Project'))
            ->display(function ($title) {
                return \Illuminate\Support\Str::limit($title, 35);
            })
            ->sortable();
        
        $grid->column('amount', __('Amount'))
            ->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            })
            ->sortable();
        
        $grid->column('disbursement_date', __('Date'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })
            ->sortable();
        
        $grid->column('description', __('Description'))
            ->display(function ($desc) {
                return \Illuminate\Support\Str::limit($desc, 50);
            });
        
        $grid->column('creator.name', __('Created By'));
        
        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('d M Y, H:i', strtotime($date));
            })
            ->sortable();
        
        $grid->column('investors', __('Investors'))
            ->display(function () {
                $count = $this->accountTransactions()->count();
                return $count . ' investor' . ($count != 1 ? 's' : '');
            });

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
        $show = new Show(Disbursement::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('project.title', __('Project'));
        
        $show->field('amount', __('Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        
        $show->field('disbursement_date', __('Disbursement Date'));
        $show->field('description', __('Description'));
        $show->field('creator.name', __('Created By'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));
        
        // Show account transactions created from this disbursement
        $show->accountTransactions('Investor Distributions', function ($accountTransactions) {
            $accountTransactions->resource('/admin/account-transactions');
            
            $accountTransactions->column('user.name', __('Investor'));
            $accountTransactions->column('amount', __('Amount Received'))->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            });
            $accountTransactions->column('transaction_date', __('Date'));
            
            $accountTransactions->disableCreateButton();
            $accountTransactions->disableActions();
            $accountTransactions->disableFilter();
            $accountTransactions->disablePagination();
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Disbursement());

        $form->select('project_id', __('Project'))
            ->options(Project::whereIn('status', ['ongoing', 'completed'])->pluck('title', 'id'))
            ->rules('required')
            ->help('Select project to disburse profits from');
        
        $form->decimal('amount', __('Amount (UGX)'))
            ->rules('required|numeric|min:0')
            ->help('Total amount to distribute proportionally to investors');
        
        $form->date('disbursement_date', __('Disbursement Date'))
            ->default(date('Y-m-d'))
            ->rules('required');
        
        $form->textarea('description', __('Description'))
            ->rules('required')
            ->rows(3)
            ->placeholder('e.g., Q1 profit distribution, Project returns, etc.');
        
        $form->hidden('created_by_id')->default(auth()->id());

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();
        $form->disableEditingCheck();

        return $form;
    }
}
