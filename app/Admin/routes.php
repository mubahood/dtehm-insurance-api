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
    // DASHBOARD - Accessible to all authenticated admin users
    // ========================================
    $router->get('/', 'HomeController@index')->name('home');

    // ========================================
    // INVESTMENT MANAGEMENT - Admin Only (Financial Operations)
    // ========================================
    $router->group(['middleware' => 'admin.only'], function ($router) {
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
    });

    // ========================================
    // INSURANCE MANAGEMENT - Read access for managers, full access for admin
    // ========================================
    $router->resource('insurance-programs', InsuranceProgramController::class);
    $router->resource('insurance-subscriptions', InsuranceSubscriptionController::class);
    
    // Admin only - Payment operations
    $router->group(['middleware' => 'admin.only'], function ($router) {
        $router->resource('insurance-subscription-payments', InsuranceSubscriptionPaymentController::class);
        $router->resource('insurance-transactions', InsuranceTransactionController::class);
    });

    // ========================================
    // MEDICAL SERVICES - Read access for managers, full access for admin
    // ========================================
    $router->resource('medical-service-requests', MedicalServiceRequestController::class);

    // ========================================
    // E-COMMERCE - Read access for managers, full access for admin
    // ========================================
    $router->resource('products', ProductController::class);
    $router->resource('orders', OrderController::class);

    // ========================================
    // MEMBERSHIP MANAGEMENT - Admin Only
    // ========================================
    $router->group(['middleware' => 'admin.only'], function ($router) {
        $router->resource('membership-payments', MembershipPaymentController::class);
        $router->get('membership-payments/{id}/confirm', 'MembershipPaymentController@confirm')->name('membership-payments.confirm');
    });

    // ========================================
    // SYSTEM MANAGEMENT - Admin Only
    // ========================================
    $router->group(['middleware' => 'admin.only'], function ($router) {
        $router->resource('system-configurations', SystemConfigurationController::class);
        $router->resource('pesapal-payments', UniversalPaymentController::class);
    });
    
    // Users - Managers can view, only Admins can create/edit/delete
    $router->get('users', 'UserController@index')->name('users.index');
    $router->get('users/{id}', 'UserController@show')->name('users.show');
    $router->group(['middleware' => 'admin.only'], function ($router) {
        $router->get('users/create', 'UserController@create')->name('users.create');
        $router->post('users', 'UserController@store')->name('users.store');
        $router->get('users/{id}/edit', 'UserController@edit')->name('users.edit');
        $router->put('users/{id}', 'UserController@update')->name('users.update');
        $router->delete('users/{id}', 'UserController@destroy')->name('users.destroy');
    });

    // User Hierarchy & Network - View only for all admin users
    $router->resource('user-hierarchy', UserHierarchyController::class);
});
