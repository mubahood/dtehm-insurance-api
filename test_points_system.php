<?php

/**
 * Points System Testing Script
 * 
 * This script tests the complete points reward system:
 * 1. Products have points value
 * 2. When product is sold, sponsor earns points
 * 3. Points are tracked in ordered_items.points_earned
 * 4. Sponsor's total_points accumulates
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\User;
use App\Models\OrderedItem;

echo "\n";
echo "==============================================\n";
echo "    POINTS SYSTEM FUNCTIONALITY TEST\n";
echo "==============================================\n\n";

try {
    // Step 1: Find or create a test product with points
    echo "STEP 1: Setting up test product\n";
    echo "----------------------------------------------\n";
    
    $product = Product::where('name', 'LIKE', '%Test%')->first();
    
    if (!$product) {
        // Try to get any product
        $product = Product::first();
    }
    
    if (!$product) {
        throw new Exception("No products found in database. Please create at least one product.");
    }
    
    // Set points value for testing (e.g., 5 points)
    $testPoints = 5;
    $product->points = $testPoints;
    $product->save();
    
    echo "✓ Product selected: {$product->name}\n";
    echo "✓ Product ID: {$product->id}\n";
    echo "✓ Product points set to: {$testPoints}\n\n";
    
    // Step 2: Find an active DTEHM member as sponsor
    echo "STEP 2: Setting up test sponsor\n";
    echo "----------------------------------------------\n";
    
    $sponsor = User::where('is_dtehm_member', 'Yes')
                   ->whereNotNull('dtehm_member_id')
                   ->first();
    
    if (!$sponsor) {
        throw new Exception("No active DTEHM members found. Please create at least one DTEHM member.");
    }
    
    // Record sponsor's points before sale
    $pointsBefore = $sponsor->total_points ?? 0;
    
    echo "✓ Sponsor selected: {$sponsor->name}\n";
    echo "✓ Sponsor DTEHM ID: {$sponsor->dtehm_member_id}\n";
    echo "✓ Sponsor total points BEFORE sale: {$pointsBefore}\n\n";
    
    // Step 3: Find an active stockist
    echo "STEP 3: Setting up test stockist\n";
    echo "----------------------------------------------\n";
    
    $stockist = User::where('is_dtehm_member', 'Yes')
                    ->whereNotNull('dtehm_member_id')
                    ->where('id', '!=', $sponsor->id)
                    ->first();
    
    if (!$stockist) {
        // Use same sponsor as stockist if no other member available
        $stockist = $sponsor;
        echo "⚠ Using sponsor as stockist (no other member available)\n";
    }
    
    echo "✓ Stockist selected: {$stockist->name}\n";
    echo "✓ Stockist DTEHM ID: {$stockist->dtehm_member_id}\n\n";
    
    // Step 4: Create an OrderedItem (sale)
    echo "STEP 4: Creating sale transaction\n";
    echo "----------------------------------------------\n";
    
    $quantity = 2; // Test with 2 items
    $expectedPoints = $testPoints * $quantity;
    
    echo "Creating OrderedItem with:\n";
    echo "  - Product: {$product->name}\n";
    echo "  - Quantity: {$quantity}\n";
    echo "  - Expected points: {$expectedPoints} ({$testPoints} × {$quantity})\n\n";
    
    $orderedItem = new OrderedItem();
    $orderedItem->product = $product->id;
    $orderedItem->sponsor_id = $sponsor->dtehm_member_id;
    $orderedItem->stockist_id = $stockist->dtehm_member_id;
    $orderedItem->qty = $quantity;
    $orderedItem->unit_price = $product->price_1;
    $orderedItem->item_is_paid = 'Yes';
    $orderedItem->item_paid_date = now();
    $orderedItem->item_paid_amount = $product->price_1 * $quantity;
    
    // Save will trigger saving hook (calculates points_earned)
    // and created hook (updates sponsor's total_points)
    $orderedItem->save();
    
    echo "✓ OrderedItem created successfully\n";
    echo "✓ OrderedItem ID: {$orderedItem->id}\n\n";
    
    // Step 5: Verify points calculation
    echo "STEP 5: Verifying points calculation\n";
    echo "==============================================\n";
    
    // Refresh the ordered item to get calculated values
    $orderedItem->refresh();
    
    echo "Points earned on OrderedItem:\n";
    echo "  - Expected: {$expectedPoints}\n";
    echo "  - Actual: {$orderedItem->points_earned}\n";
    
    if ($orderedItem->points_earned == $expectedPoints) {
        echo "  ✓ PASS: Points calculated correctly!\n\n";
    } else {
        echo "  ✗ FAIL: Points mismatch!\n\n";
        throw new Exception("Points calculation failed");
    }
    
    // Step 6: Verify sponsor's total points updated
    echo "STEP 6: Verifying sponsor's total points\n";
    echo "==============================================\n";
    
    // Refresh sponsor to get updated total_points
    $sponsor->refresh();
    $pointsAfter = $sponsor->total_points ?? 0;
    $pointsAdded = $pointsAfter - $pointsBefore;
    
    echo "Sponsor's total points:\n";
    echo "  - Before sale: {$pointsBefore}\n";
    echo "  - After sale: {$pointsAfter}\n";
    echo "  - Points added: {$pointsAdded}\n";
    echo "  - Expected added: {$expectedPoints}\n";
    
    if ($pointsAdded == $expectedPoints) {
        echo "  ✓ PASS: Sponsor's total points updated correctly!\n\n";
    } else {
        echo "  ✗ FAIL: Sponsor's points not updated correctly!\n\n";
        throw new Exception("Sponsor points update failed");
    }
    
    // Final Summary
    echo "==============================================\n";
    echo "           TEST RESULTS SUMMARY\n";
    echo "==============================================\n\n";
    
    echo "✓ Product points system: WORKING\n";
    echo "✓ Points calculation: WORKING\n";
    echo "✓ Sponsor points accumulation: WORKING\n";
    echo "✓ Database fields: CONFIGURED\n";
    echo "✓ Model hooks: FUNCTIONAL\n\n";
    
    echo "==============================================\n";
    echo "      ALL TESTS PASSED SUCCESSFULLY! ✓\n";
    echo "==============================================\n\n";
    
    echo "Test Details:\n";
    echo "  - Product: {$product->name} (ID: {$product->id})\n";
    echo "  - Product points: {$testPoints}\n";
    echo "  - Quantity sold: {$quantity}\n";
    echo "  - Points earned: {$orderedItem->points_earned}\n";
    echo "  - Sponsor: {$sponsor->name} ({$sponsor->dtehm_member_id})\n";
    echo "  - Sponsor total points: {$pointsAfter}\n";
    echo "  - OrderedItem ID: {$orderedItem->id}\n\n";
    
    echo "The points system is ready for production use!\n\n";
    
} catch (\Exception $e) {
    echo "\n";
    echo "==============================================\n";
    echo "              TEST FAILED! ✗\n";
    echo "==============================================\n\n";
    echo "Error: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}\n";
    echo "Line: {$e->getLine()}\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
