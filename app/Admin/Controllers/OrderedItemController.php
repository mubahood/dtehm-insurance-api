<?php

namespace App\Admin\Controllers;

use App\Models\OrderedItem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class OrderedItemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'OrderedItem';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new OrderedItem());

        $grid->column('id', __('Id'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->column('order', __('Order'));
        $grid->column('product', __('Product'));
        $grid->column('qty', __('Qty'));
        $grid->column('amount', __('Amount'));
        $grid->column('color', __('Color'));
        $grid->column('size', __('Size'));

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
        $show = new Show(OrderedItem::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('order', __('Order'));
        $show->field('product', __('Product'));
        $show->field('qty', __('Qty'));
        $show->field('amount', __('Amount'));
        $show->field('color', __('Color'));
        $show->field('size', __('Size'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new OrderedItem());

        $form->number('order', __('Order'));
        $form->textarea('product', __('Product'));
        $form->textarea('qty', __('Qty'));
        $form->textarea('amount', __('Amount'));
        $form->textarea('color', __('Color'));
        $form->textarea('size', __('Size'));

        return $form;
    }
}
