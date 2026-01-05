<?php

use Illuminate\Support\Facades\Schema;

/* $tables = Schema::getColumnListing('products');

echo "<pre>";
print_r(json_encode($tables));
echo "</pre>";
die();

dd($tables); */

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

use App\Models\Utils;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Auth;
use App\Admin\Extensions\Nav\Shortcut;
use App\Admin\Extensions\Nav\Dropdown;
use App\Models\Product;
use Encore\Admin\Form;

//last product
/* $last_product = Product::find(898);
$last_product->name = rand(1,16) . " " . $last_product->name;
$last_product->save();
echo $last_product->name. "<br>";
echo $last_product->price_1;

die();
 */

Utils::system_boot();

Form::init(function (Form $form) {
    // $form->disableEditingCheck();
    // $form->disableCreatingCheck();
    // $form->disableViewCheck();
    // $form->disableReset();
    // $form->disableCreatingCheck();

    $form->tools(function (Form\Tools $tools) {
        $tools->disableDelete();
        $tools->disableView();
    });
});


Encore\Admin\Form::forget(['map', 'editor']);
Admin::css(url('/assets/css/bootstrap.css'));
Admin::css('/assets/css/styles.css');
