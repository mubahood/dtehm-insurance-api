<?php

namespace App\Admin\Controllers;

use App\Models\Product;
use App\Models\Utils;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;

class ProductController extends AdminController
{
    protected $title = 'Products';

    protected function grid()
    {
        $grid = new Grid(new Product());
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableExport();
        $grid->quickSearch('name')->placeholder('Search by product name...');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('name', 'Product Name');
            $cats = \App\Models\ProductCategory::all();
            $filter->equal('category', 'Category')->select($cats->pluck('category', 'id'));
            $filter->between('price_1', 'Price (UGX)');
            $filter->between('created_at', 'Created Date')->datetime();
        });
        $grid->model()->orderBy('id', 'desc');
        $grid->column('id', __('ID'))->sortable();
        $grid->column('feature_photo', __('Photo'))->image('', 60, 60);
        $grid->column('name', __('Product Name'))->sortable()->editable();
        $grid->column('price_1', __('Price (UGX)'))->display(function ($price) {
            return 'UGX ' . number_format($price, 0);
        })->sortable()->editable();
        $grid->column('category', __('Category'))->display(function ($category) {
            $c = \App\Models\ProductCategory::find($category);
            return $c ? $c->category : '-';
        })->sortable();
        $grid->column('description', __('Description'))->display(function ($description) {
            return \Illuminate\Support\Str::limit(strip_tags($description), 50);
        });
        $grid->column('created_at', __('Created'))->display(function ($created_at) {
            return date('M d, Y', strtotime($created_at));
        })->sortable();
        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Product::findOrFail($id));
        $show->field('id', __('ID'));
        $show->field('name', __('Product Name'));
        $show->field('price_1', __('Price (UGX)'));
        $show->field('description', __('Description'));
        $show->field('feature_photo', __('Feature Photo'));
        $show->field('category', __('Category'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));
        return $show;
    }

    protected function form()
    {
        $form = new Form(new Product());
        $form->hidden('local_id')->value(Utils::get_unique_text());
        $form->hidden('currency')->default('UGX');
        $form->hidden('has_colors')->default('No');
        $form->hidden('has_sizes')->default('No');
        $form->hidden('home_section_1')->default('No');
        $form->hidden('home_section_2')->default('No');
        $form->hidden('home_section_3')->default('No');
        $form->hidden('status')->default('active');
        $form->hidden('in_stock')->default('Yes');
        $form->hidden('p_type')->default('product');
        $form->text('name', __('Product Name'))->rules('required|max:255')->placeholder('Enter product name');
        $form->decimal('price_1', __('Price (UGX)'))->rules('required|numeric|min:0')->placeholder('Enter price in UGX')->help('Product selling price');
        $form->quill('description', __('Description'))->rules('required')->placeholder('Enter product description');
        $form->image('feature_photo', __('Feature Photo'))->rules('required')->uniqueName()->help('Upload main product image');
        $cats = \App\Models\ProductCategory::all();
        $form->select('category', __('Category'))->options($cats->pluck('category', 'id'))->rules('required')->placeholder('Select category');
        $form->saving(function (Form $form) {
            $user = Auth::user();
            if ($user) {
                $form->user = $user->id;
            }
            if ($form->isCreating()) {
                $form->date_added = now();
            }
            $form->date_updated = now();
            if (empty($form->price_2)) {
                $form->price_2 = $form->price_1;
            }
            if (empty($form->metric)) {
                $form->metric = 'piece';
            }
            if (empty($form->tags)) {
                $words = explode(' ', strtolower($form->name));
                $form->tags = implode(',', array_slice($words, 0, 5));
            }
        });
        return $form;
    }
}
