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
    
    // Get products sold by this user in last 30 days (directly from ordered_items)
    $productsSold = DB::table('ordered_items')
        ->where('dtehm_seller_id', $user->id)
        ->where('created_at', '>=', $thirtyDaysAgo)
        ->where('item_is_paid', 'Yes')
        ->count();
    
    echo "Products sold in last 30 days (paid items): {$productsSold}\n";
    echo "Maintenance warning: " . ($productsSold < 2 ? "YES ⚠️" : "NO ✅") . "\n\n";
    
    // Show all items by this seller
    $allItems = DB::table('ordered_items')
        ->where('dtehm_seller_id', $user->id)
        ->get(['id', 'product', 'qty', 'item_is_paid', 'created_at']);
    
    if ($allItems->count() > 0) {
        echo "All items sold by this user:\n";
        foreach ($allItems as $item) {
            $paid = $item->item_is_paid == 'Yes' ? '✅ PAID' : '❌ UNPAID';
            echo "  - Item #{$item->id}: Product {$item->product}, Qty: {$item->qty}, {$paid} ({$item->created_at})\n";
        }
    } else {
        echo "No items found for this seller\n";
    }
    
    echo "\n";
    
    // Show recent orders
    $recentOrders = DB::table('orders')
        ->where('dtehm_seller_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get(['id', 'receipt_number', 'order_state', 'created_at']);
    
    if ($recentOrders->count() > 0) {
        echo "Recent orders:\n";
        foreach ($recentOrders as $order) {
            $state = ['Pending', 'Processing', 'Completed', 'Cancelled', 'Failed'][$order->order_state] ?? 'Unknown';
            echo "  - Order {$order->receipt_number}: {$state} ({$order->created_at})\n";
        }
    } else {
        echo "No orders found for this user\n";
    }
    
} else {
    echo "❌ User not found\n";
}
