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
    // SYSTEM MANAGEMENT
    // ========================================
    $router->resource('users', UserController::class);
    
    // OneSignal Push Notifications - Enhanced
    $router->resource('notifications', NotificationController::class);
    $router->post('notifications/quick-send', 'NotificationController@quickSend')->name('notifications.quick-send');
    $router->post('notifications/test-connection', 'NotificationController@testConnection')->name('notifications.test-connection');
    $router->post('notifications/{id}/send', 'NotificationController@send')->name('notifications.send');
    $router->post('notifications/{id}/cancel', 'NotificationController@cancel')->name('notifications.cancel');
    $router->get('onesignal-devices', 'NotificationController@devices')->name('onesignal.devices');
    $router->post('onesignal/sync-devices', 'NotificationController@syncDevices')->name('onesignal.sync-devices');
    $router->post('onesignal/test-notification', 'NotificationController@sendTestNotification')->name('onesignal.test-notification');
    $router->get('notifications/analytics', 'NotificationController@analytics')->name('notifications.analytics');
    $router->get('notifications/{id}/analytics', 'NotificationController@notificationAnalytics')->name('notifications.single-analytics');
    $router->get('notifications/templates', 'NotificationController@templates')->name('notifications.templates');
    $router->post('notifications/{id}/schedule', 'NotificationController@schedule')->name('notifications.schedule');
    
    $router->resource('system-configurations', SystemConfigurationController::class);
});
