<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test products sold in last 30 days
$user = \App\Models\User::find(1); // Test with user ID 1

if ($user) {
    echo "Testing products sold for user: {$user->name} (ID: {$user->id})\n\n";
    
    $thirtyDaysAgo = now()->subDays(30);
    
    // Check if tables exist
    try {
        $ordersCount = DB::table('orders')->count();
        echo "✅ Orders table exists with {$ordersCount} orders\n";
        
        $orderedItemsCount = DB::table('ordered_items')->count();
        echo "✅ Ordered items table exists with {$orderedItemsCount} items\n\n";
    } catch (\Exception $e) {
        echo "❌ Error checking tables: {$e->getMessage()}\n";
        exit;
    }
    
    // Get products sold by this user in last 30 days
    $productsSold = DB::table('ordered_items')
        ->join('orders', 'ordered_items.order_id', '=', 'orders.id')
        ->where('orders.dtehm_seller_id', $user->id)
        ->where('orders.created_at', '>=', $thirtyDaysAgo)
        ->whereIn('orders.order_state', [1, 2]) // processing or completed
        ->count();
    
    echo "Products sold in last 30 days: {$productsSold}\n";
    echo "Maintenance warning: " . ($productsSold < 2 ? "YES ⚠️" : "NO ✅") . "\n\n";
    
    // Show recent orders
    $recentOrders = DB::table('orders')
        ->where('dtehm_seller_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get(['id', 'order_code', 'order_state', 'created_at']);
    
    if ($recentOrders->count() > 0) {
        echo "Recent orders:\n";
        foreach ($recentOrders as $order) {
            $state = ['Pending', 'Processing', 'Completed', 'Cancelled', 'Failed'][$order->order_state] ?? 'Unknown';
            echo "  - Order {$order->order_code}: {$state} ({$order->created_at})\n";
        }
    } else {
        echo "No orders found for this user\n";
    }
    
} else {
    echo "❌ User not found\n";
}
