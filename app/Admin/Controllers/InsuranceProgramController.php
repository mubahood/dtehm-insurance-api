<?php

namespace App\Admin\Controllers;

use App\Models\InsuranceProgram;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class InsuranceProgramController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Insurance Programs';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new InsuranceProgram());
        
        $grid->model()->orderBy('id', 'desc');
        $grid->disableExport();
        
        $grid->quickSearch('name')->placeholder('Search by program name');
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('name', 'Program Name');
            $filter->equal('status', 'Status')->select([
                'Active' => 'Active',
                'Inactive' => 'Inactive',
                'Suspended' => 'Suspended',
            ]);
        });

        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('name', __('Program Name'))
            ->sortable()
            ->editable();
        
        $grid->column('premium_amount', __('Premium'))
            ->display(function ($premium) {
                return 'UGX ' . number_format($premium, 0);
            })
            ->sortable();
        
        $grid->column('billing_frequency', __('Billing'))
            ->sortable();
        
        $grid->column('coverage_amount', __('Coverage'))
            ->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            })
            ->sortable();
        
        $grid->column('duration_months', __('Duration'))
            ->display(function ($months) {
                return $months . ' months';
            })
            ->sortable();
        
        $grid->column('status', __('Status'))
            ->label([
                'Active' => 'success',
                'Inactive' => 'danger',
                'Suspended' => 'warning',
            ])
            ->editable('select', [
                'Active' => 'Active',
                'Inactive' => 'Inactive',
                'Suspended' => 'Suspended',
            ])
            ->sortable();
        
        $grid->column('subscribers', __('Subscribers'))
            ->display(function () {
                return $this->subscriptions()->count();
            });
        
        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('d M Y, H:i', strtotime($date));
            })
            ->sortable();

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
        $show = new Show(InsuranceProgram::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Program Name'));
        $show->field('description', __('Description'));
        
        $show->field('premium_amount', __('Premium'))->as(function ($premium) {
            return 'UGX ' . number_format($premium, 0);
        });
        
        $show->field('billing_frequency', __('Billing Frequency'));
        $show->field('billing_day', __('Billing Day'));
        
        $show->field('coverage_amount', __('Coverage Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        
        $show->field('duration_months', __('Duration (months)'));
        $show->field('grace_period_days', __('Grace Period (days)'));
        $show->field('late_payment_penalty', __('Late Payment Penalty'));
        $show->field('penalty_type', __('Penalty Type'));
        $show->field('min_age', __('Minimum Age'));
        $show->field('max_age', __('Maximum Age'));
        $show->field('requirements', __('Requirements'))->unescape();
        $show->field('benefits', __('Benefits'))->unescape();
        $show->field('status', __('Status'));
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
        $form = new Form(new InsuranceProgram());

        $form->text('name', __('Program Name'))
            ->rules('required|max:255');
        
        $form->textarea('description', __('Description'))
            ->rules('required')
            ->rows(4);
        
        $form->decimal('premium_amount', __('Premium Amount (UGX)'))
            ->rules('required|numeric|min:0');
        
        $form->select('billing_frequency', __('Billing Frequency'))
            ->options([
                'Weekly' => 'Weekly',
                'Monthly' => 'Monthly',
                'Quarterly' => 'Quarterly',
                'Annually' => 'Annually',
            ])
            ->default('Monthly')
            ->rules('required');
        
        $form->decimal('billing_day', __('Billing Day'))
            ->rules('nullable|integer|min:1|max:31')
            ->help('Day of month for billing (1-31)');
        
        $form->decimal('coverage_amount', __('Coverage Amount (UGX)'))
            ->rules('required|numeric|min:0');
        
        $form->decimal('duration_months', __('Duration (months)'))
            ->rules('required|integer|min:1')
            ->default(12);
        
        $form->decimal('grace_period_days', __('Grace Period (days)'))
            ->rules('nullable|integer|min:0')
            ->default(7);
        
        $form->decimal('late_payment_penalty', __('Late Payment Penalty'))
            ->rules('nullable|numeric|min:0');
        
        $form->select('penalty_type', __('Penalty Type'))
            ->options([
                'Fixed' => 'Fixed Amount',
                'Percentage' => 'Percentage',
            ])
            ->default('Fixed');
        
        $form->decimal('min_age', __('Minimum Age'))
            ->rules('nullable|integer|min:0')
            ->default(18);
        
        $form->decimal('max_age', __('Maximum Age'))
            ->rules('nullable|integer|min:0')
            ->default(65);
        
        $form->textarea('requirements', __('Requirements'))
            ->help('Enter program requirements')
            ->rows(3);
        
        $form->textarea('benefits', __('Benefits'))
            ->help('Enter program benefits')
            ->rows(5);
        
        $form->image('icon', __('Program Icon'))
            ->move('insurance/icons')
            ->uniqueName();
        
        $form->color('color', __('Brand Color'))
            ->default('#1890ff');
        
        $form->date('start_date', __('Start Date'));
        $form->date('end_date', __('End Date'));
        
        $form->textarea('terms_and_conditions', __('Terms and Conditions'))
            ->rows(5);
        
        $form->select('status', __('Status'))
            ->options([
                'Active' => 'Active',
                'Inactive' => 'Inactive',
                'Suspended' => 'Suspended',
            ])
            ->default('Active')
            ->rules('required');
        
        $form->hidden('created_by')->default(auth()->id());
        $form->hidden('updated_by')->default(auth()->id());

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();

        return $form;
    }
}
