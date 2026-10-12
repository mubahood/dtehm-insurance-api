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

    $router->resource('deliveries', DeliveryController::class);
    $router->resource('dtehm-memberships', DtehmMembershipController::class);
    $router->resource('dip-members', 'DipMemberController');
    $router->resource('dtehm-members', 'DtehmMemberController');
    $router->resource('product-categories', ProductCategoryController::class);
    $router->resource('ordered-items', OrderedItemController::class);


    $router->resource('delivery-addresses', DeliveryAddressController::class);
    $router->resource('deliveries', DeliveryController::class);
    $router->resource('product-categories', ProductCategoryController::class);
    $router->resource('products', ProductController::class);
    $router->resource('product-orders', ProductOrderController::class);
    
    // Multiple Orders (Cart Checkout) - Admin can view and manage
    $router->resource('multiple-orders', MultipleOrderController::class);
    $router->get('multiple-orders/{id}/convert', 'MultipleOrderController@convert')->name('multiple-orders.convert');

    // ========================================
    // INVESTMENT MANAGEMENT - Admin Only (Financial Operations)
    // ========================================
    $router->group(['middleware' => 'admin.only'], function ($router) {
        $router->resource('projects', ProjectController::class);
        $router->resource('project-shares', ProjectShareController::class);
        $router->resource('project-transactions', ProjectTransactionController::class);
        $router->resource('disbursements', DisbursementController::class);
        $router->resource('account-transactions', AccountTransactionController::class);

        // Pool withdrawal records
        $router->resource('shareholder-withdrawals', ShareholderWithdrawalController::class);
        $router->resource('award-withdrawals', AwardWithdrawalController::class);

        // Withdraw Requests Management
        // NOTE: Specific routes MUST come before resource route to avoid conflicts
        $router->get('withdraw-requests/pdf-pending', 'WithdrawRequestController@generatePendingPDF')->name('withdraw-requests.pdf-pending');
        $router->match(['get', 'post'], 'withdraw-requests/{id}/approve', 'WithdrawRequestController@approve')->name('withdraw-requests.approve');
        $router->match(['get', 'post'], 'withdraw-requests/{id}/reject', 'WithdrawRequestController@reject')->name('withdraw-requests.reject');
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
    // NOTE: generate-description MUST come before the resource route (specific before dynamic)
    $router->get('products/{id}/generate-description', 'ProductController@generateDescription')->name('products.generate-description');
    $router->resource('products', ProductController::class);
    //     $router->resource('orders', OrderController::class); // Commented - controller doesn't exist

    // ========================================
    // MEMBERSHIP MANAGEMENT - Admin Only
    // ========================================
    $router->group(['middleware' => 'admin.only'], function ($router) {
        $router->resource('membership-payments', MembershipPaymentController::class);
        $router->get('membership-payments/{id}/confirm', 'MembershipPaymentController@confirm')->name('membership-payments.confirm');
        
        // Member payment initiation routes
        $router->get('membership-payment/initiate/{user_id}', 'MembershipPaymentController@initiatePayment')->name('membership-payment.initiate');
        $router->post('membership-payment/process/{user_id}', 'MembershipPaymentController@processPayment')->name('membership-payment.process');
        $router->get('membership-payment/callback/{payment_id}', 'MembershipPaymentController@paymentCallback')->name('membership-payment.callback');
    });

    // ========================================
    // SYSTEM MANAGEMENT - Admin Only
    // ========================================
    $router->group(['middleware' => 'admin.only'], function ($router) {
        $router->resource('system-configurations', SystemConfigurationController::class);
        $router->resource('pesapal-payments', UniversalPaymentController::class);
    });

    // Users - Managers can view, only Admins can create/edit/delete
    // IMPORTANT: Specific routes (create, edit) MUST come BEFORE dynamic routes ({id})
    $router->get('users', 'UserController@index')->name('users.index');
    
    $router->group(['middleware' => 'admin.only'], function ($router) {
        $router->get('users/create', 'UserController@create')->name('users.create');
        $router->post('users', 'UserController@store')->name('users.store');
        $router->get('users/{id}/edit', 'UserController@edit')->name('users.edit');
        $router->put('users/{id}', 'UserController@update')->name('users.update');
        $router->delete('users/{id}', 'UserController@destroy')->name('users.destroy');
        $router->get('users/{id}/assign-dtehm-id', 'UserController@assignDtehmId')->name('users.assign-dtehm-id');
    });
    
    // Dynamic route MUST come last to avoid matching 'create', 'edit', etc.
    $router->get('users/{id}', 'UserController@show')->name('users.show');

    // User Hierarchy & Network - View only for all admin users
    $router->resource('user-hierarchy', UserHierarchyController::class)->only(['index', 'show']); // read-only view

    // Stockists - View only listing, no create/edit/delete
    $router->get('stockists', 'StockistController@index')->name('stockists.index');

    // ========================================
    // ANALYTICS DASHBOARDS
    // ========================================
    $router->get('shops-dashboard', 'ShopsDashboardController@index')->name('shops-dashboard');

    // Member search used by select boxes (DTEHM membership form)
    $router->get('api/users', 'DtehmMembershipController@searchUsers')->name('api.users-search');

    // Insurance Control Center
    $router->get('insurance-center', 'InsuranceCenterController@index')->name('insurance-center');
    $router->get('insurance-center/policy/{id}', 'InsuranceCenterController@policy')->name('insurance-center.policy');
    $router->post('insurance-center/policy/{id}/pay', 'InsuranceCenterController@pay')->name('insurance-center.pay');
    $router->post('insurance-center/policy/{id}/status', 'InsuranceCenterController@status')->name('insurance-center.status');
    $router->post('insurance-center/month/{id}/waive', 'InsuranceCenterController@waive')->name('insurance-center.waive');
    $router->post('insurance-center/month/{id}/remove-fee', 'InsuranceCenterController@removeLateFee')->name('insurance-center.remove-fee');
    $router->post('insurance-center/program/{id}/enrolment', 'InsuranceCenterController@setEnrolment')->name('insurance-center.enrolment');
    $router->post('insurance-center/tool/{name}', 'InsuranceCenterController@tool')->name('insurance-center.tool');
    $router->post('insurance-center/payment/{id}/recheck', 'InsuranceCenterController@recheck')->name('insurance-center.recheck');
    $router->get('awards-dashboard', 'AwardsDashboardController@index')->name('awards-dashboard');
    $router->get('shareholders-dashboard', 'ShareholdersDashboardController@index')->name('shareholders-dashboard');
});
