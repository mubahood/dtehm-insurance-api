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
        $grid->disableBatchActions();
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
        $grid->model()->orderBy('id', 'asc');
        // $grid->column('id', __('ID'))->sortable();
        $grid->column('feature_photo', __('Photo'))->lightbox(['width' => 200, 'height' => 200]); 
        $grid->column('name', __('Product Name'))->sortable();
        $grid->column('price_1', __('Price (UGX)'))->display(function ($price) {
            return 'UGX ' . number_format($price, 0);
        })->sortable();
  /*       $grid->column('category', __('Category'))->display(function ($category) {
            $c = \App\Models\ProductCategory::find($category);
            return $c ? $c->category : '-';
        })->sortable(); */
        $grid->column('sell', __('Action'))->display(function () {
            $url = admin_url('ordered-items/create?product_id=' . $this->id);
            return '<a href="' . $url . '" class="btn btn-sm btn-success" style="padding: 4px 12px;"><i class="fa fa-shopping-cart"></i> Buy this product</a>';
        });
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
        $form->decimal('points', __('Points'))
            ->required()
            ->rules('numeric|min:0')->placeholder('Enter product points')->help('Points awarded for purchasing this product');

        $form->quill('description', __('Description'))->placeholder('Enter product description');
        $form->image('feature_photo', __('Feature Photo'))->uniqueName()->help('Upload main product image');
        $cats = \App\Models\ProductCategory::all();


        $form->hidden('category', __('Category'))
            ->default(function () use ($cats) {
                $firstCat = $cats->first();
                return $firstCat ? $firstCat->id : null;
            });
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
