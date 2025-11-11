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
    
    // Withdraw Requests Management
    // NOTE: Specific routes MUST come before resource route to avoid conflicts
    $router->get('withdraw-requests/pdf-pending', 'WithdrawRequestController@generatePendingPDF')->name('withdraw-requests.pdf-pending');
    $router->get('withdraw-requests/{id}/approve', 'WithdrawRequestController@approve')->name('withdraw-requests.approve');
    $router->get('withdraw-requests/{id}/reject', 'WithdrawRequestController@reject')->name('withdraw-requests.reject');
    $router->resource('withdraw-requests', WithdrawRequestController::class);

    // ========================================
    // INSURANCE MANAGEMENT
    // ========================================
    $router->resource('insurance-programs', InsuranceProgramController::class);
    $router->resource('insurance-subscriptions', InsuranceSubscriptionController::class);
    $router->resource('insurance-subscription-payments', InsuranceSubscriptionPaymentController::class);
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
    $router->resource('pesapal-payments', UniversalPaymentController::class);
});
