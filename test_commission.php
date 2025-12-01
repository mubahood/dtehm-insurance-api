<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "========== TESTING COMMISSION GENERATION ==========\n";

// Find a DTEHM seller with parent hierarchy
$seller = App\Models\User::where('is_dtehm_member', 'Yes')
    ->whereNotNull('parent_1')
    ->first();

if (!$seller) {
    echo "No DTEHM seller found with parent hierarchy\n";
    exit;
}

echo "Seller: {$seller->name} (ID: {$seller->id})\n";
echo "Parent 1: {$seller->parent_1}\n";
echo "Parent 2: " . ($seller->parent_2 ?? 'null') . "\n\n";

// Get a product
$product = App\Models\Product::first();
if (!$product) {
    echo "No product found\n";
    exit;
}

echo "Product: {$product->name} (Price: {$product->price_1})\n\n";

// Create an order
$order = App\Models\Order::create([
    'customer_name' => 'Test Commission Customer',
    'customer_phone_number_1' => '0700000000',
    'customer_address' => 'Test Address',
    'order_total' => $product->price_1,
    'order_status' => 'Completed',
]);

echo "Order created: ID {$order->id}\n";

// Create ordered item with DTEHM seller
$item = App\Models\OrderedItem::create([
    'order' => $order->id,
    'product' => $product->id,
    'qty' => 1,
    'unit_price' => $product->price_1,
    'subtotal' => $product->price_1,
    'has_detehm_seller' => 'Yes',
    'dtehm_user_id' => $seller->id,
    'item_is_paid' => 'No',
]);

echo "Ordered item created: ID {$item->id}\n";
echo "Subtotal: {$item->subtotal}\n\n";

// Check before transactions
$beforeCount = App\Models\AccountTransaction::where('source', 'product_commission')->count();
echo "Product commission transactions before: {$beforeCount}\n\n";

// Mark as paid - this should trigger commission processing
echo "Marking item as PAID...\n";
$item->item_is_paid = 'Yes';
$item->item_paid_date = now();
$item->item_paid_amount = $item->subtotal;
$item->save();

echo "Item marked as paid\n\n";

// Check after transactions
$afterCount = App\Models\AccountTransaction::where('source', 'product_commission')->count();
echo "Product commission transactions after: {$afterCount}\n";
echo "New transactions created: " . ($afterCount - $beforeCount) . "\n\n";

// Reload item to see updated commission fields
$item->refresh();
echo "Commission processed: " . ($item->commission_is_processed ?? 'null') . "\n";
echo "Total commission amount: " . ($item->total_commission_amount ?? 0) . "\n";
echo "Seller commission: " . ($item->commission_seller ?? 0) . "\n";
echo "Parent 1 commission: " . ($item->commission_parent_1 ?? 0) . "\n";
echo "Parent 2 commission: " . ($item->commission_parent_2 ?? 0) . "\n\n";

if ($afterCount > $beforeCount) {
    echo "✅ SUCCESS - Commissions generated!\n";
    $transactions = App\Models\AccountTransaction::where('source', 'product_commission')
        ->latest()
        ->take($afterCount - $beforeCount)
        ->get();
    echo "\nTransaction details:\n";
    foreach ($transactions as $t) {
        $user = App\Models\User::find($t->user_id);
        echo "- {$user->name} (ID: {$t->user_id}): UGX " . number_format($t->amount, 2) . "\n";
    }
} else {
    echo "❌ FAILED - No commissions generated\n";
    echo "Checking logs...\n\n";
    
    $logFile = __DIR__ . '/storage/logs/laravel.log';
    if (file_exists($logFile)) {
        $logs = shell_exec("tail -n 50 {$logFile} | grep -i commission");
        echo $logs;
    }
}

echo "\n========== TEST COMPLETE ==========\n";
