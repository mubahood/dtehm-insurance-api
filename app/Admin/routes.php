<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    // ========================================
    // DASHBOARD
    // ========================================
    $router->get('/', 'HomeController@index')->name('home');

    // ========================================
    // INVESTMENT MANAGEMENT
    // ========================================
    $router->resource('projects', ProjectController::class);
    $router->resource('project-shares', ProjectShareController::class);
    $router->resource('project-transactions', ProjectTransactionController::class);
    $router->resource('disbursements', DisbursementController::class);
    $router->resource('account-transactions', AccountTransactionController::class);

    // ========================================
    // INSURANCE MANAGEMENT
    // ========================================
    $router->resource('insurance-programs', InsuranceProgramController::class);
    $router->resource('insurance-subscriptions', InsuranceSubscriptionController::class);
    $router->resource('insurance-transactions', InsuranceTransactionController::class);

    // ========================================
    // MEDICAL SERVICES
    // ========================================
    $router->resource('medical-service-requests', MedicalServiceRequestController::class);

    // ========================================
    // E-COMMERCE
    // ========================================
    $router->resource('products', ProductController::class);
    $router->resource('orders', OrderController::class);

    // ========================================
    // MEMBERSHIP MANAGEMENT
    // ========================================
    $router->resource('membership-payments', MembershipPaymentController::class);
    $router->get('membership-payments/{id}/confirm', 'MembershipPaymentController@confirm')->name('membership-payments.confirm');

    // ========================================
    // SYSTEM MANAGEMENT
    // ========================================
    $router->resource('users', UserController::class);
    
    $router->resource('system-configurations', SystemConfigurationController::class);
});
