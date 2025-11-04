<?php

namespace App\Admin\Controllers;

use App\Models\Project;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ProjectController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Investment Projects';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Project());
        
        $grid->model()->orderBy('id', 'desc');
        $grid->disableExport();
        
        $grid->quickSearch('title')->placeholder('Search by project title');
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('title', 'Project Title');
          
            $filter->equal('status', 'Status')->select([
                'ongoing' => 'Ongoing',
                'completed' => 'Completed',
                'on_hold' => 'On Hold',
            ]);
            $filter->between('created_at', 'Created Date')->datetime();
        });

        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('image', __('Image'))
            ->lightbox(['width' => 50, 'height' => 50]);
        
        $grid->column('title', __('Title'))
            ->display(function ($title) {
                return \Illuminate\Support\Str::limit($title, 40);
            })
            ->sortable();
 
        
        $grid->column('status', __('Status'))
            ->label([
                'pending' => 'warning',
                'ongoing' => 'success',
                'completed' => 'info',
                'cancelled' => 'danger',
            ])
            ->sortable();
        
        $grid->column('share_price', __('Share Price'))
            ->display(function ($price) {
                return 'UGX ' . number_format($price, 0);
            })
            ->sortable();
        
        $grid->column('shares_sold', __('Shares Sold'))
            ->display(function () {
                return $this->shares_sold . ' / ' . $this->total_shares;
            })
            ->sortable();
        
        $grid->column('total_investment', __('Investment'))
            ->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            })
            ->sortable();
        
        $grid->column('total_profits', __('Profits'))
            ->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            })
            ->sortable();
        
        $grid->column('start_date', __('Start Date'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })
            ->sortable();
        
        $grid->column('end_date', __('End Date'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })
            ->sortable();
        
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
        $show = new Show(Project::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('image', __('Image'))->image();
        $show->field('title', __('Title'));
        $show->field('description', __('Description'))->unescape();
        $show->field('status', __('Status'));
        
        $show->field('share_price', __('Share Price'))->as(function ($price) {
            return 'UGX ' . number_format($price, 0);
        });
        
        $show->field('total_shares', __('Total Shares'));
        $show->field('shares_sold', __('Shares Sold'));
        
        $show->field('available_shares', __('Available Shares'))->as(function () {
            return $this->total_shares - $this->shares_sold;
        });
        
        $show->field('total_investment', __('Total Investment'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        
        $show->field('total_returns', __('Total Returns'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        
        $show->field('total_expenses', __('Total Expenses'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        
        $show->field('total_profits', __('Total Profits'))->as(function ($profit) {
            return 'UGX ' . number_format($profit, 0);
        });
        
        $show->field('start_date', __('Start Date'));
        $show->field('end_date', __('End Date'));
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
        $form = new Form(new Project());
 
        $form->text('title', __('Title'))
            ->rules('required|max:255');
        
        $form->textarea('description', __('Description'))
            ->rules('required')
            ->rows(5);
        
        $form->select('status', __('Status'))
            ->options([
                'ongoing' => 'Ongoing',
                'completed' => 'Completed',
                'on_hold' => 'On Hold',
            ])
            ->default('ongoing')
            ->rules('required');
        
        $form->decimal('share_price', __('Price per Share (UGX)'))
            ->rules('required|numeric|min:0')
            ->help('Amount in UGX');
        
        $form->decimal('total_shares', __('Total Shares'))
            ->rules('required|integer|min:1')
            ->help('Total number of shares available');
        
        $form->date('start_date', __('Start Date'))
            ->rules('required');
        
        $form->date('end_date', __('End Date'))
            ->rules('required');
        
        $form->image('image', __('Project Image'))
            ->move('projects')
            ->uniqueName();
        
        // Display readonly calculated fields on edit
        if ($form->isEditing()) {
            $form->display('shares_sold', __('Shares Sold'))->with(function ($value) {
                return $value . ' / ' . $this->total_shares;
            });
            
            $form->display('total_investment', __('Total Investment (UGX)'))->with(function ($value) {
                return 'UGX ' . number_format($value, 0);
            });
            
            $form->display('total_returns', __('Total Returns (UGX)'))->with(function ($value) {
                return 'UGX ' . number_format($value, 0);
            });
            
            $form->display('total_expenses', __('Total Expenses (UGX)'))->with(function ($value) {
                return 'UGX ' . number_format($value, 0);
            });
            
            $form->display('total_profits', __('Total Profits (UGX)'))->with(function ($value) {
                return 'UGX ' . number_format($value, 0);
            });
        }

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();

        return $form;
    }
}
