<?php

namespace Database\Seeders;

use App\Models\MultipleOrder;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class MultipleOrderTestSeeder extends Seeder
{
    /**
     * Seed test data for MultipleOrder system
     *
     * @return void
     */
    public function run()
    {
        Log::info("MultipleOrder Test Seeder: Starting...");

        try {
            // Find test users (DTEHM members)
            $sponsor = User::where('is_dtehm_member', 'Yes')
                ->where('parent_1', '!=', null)
                ->first();

            $stockist = User::where('is_dtehm_member', 'Yes')
                ->where('id', '!=', $sponsor->id ?? 0)
                ->first();

            if (!$sponsor || !$stockist) {
                Log::error("MultipleOrder Test Seeder: Could not find test users (DTEHM members)");
                $this->command->error("No DTEHM members found in database. Please create DTEHM members first.");
                return;
            }

            // Find test products
            $products = Product::where('price_1', '>', 0)
                ->limit(3)
                ->get();

            if ($products->count() < 1) {
                Log::error("MultipleOrder Test Seeder: No products found");
                $this->command->error("No products found in database. Please add products first.");
                return;
            }

            Log::info("MultipleOrder Test Seeder: Using sponsor #{$sponsor->id} and stockist #{$stockist->id}");
            Log::info("MultipleOrder Test Seeder: Found {$products->count()} products to use");

            // Create Test Order 1: Pending Payment
            $items1 = [];
            $subtotal1 = 0;
            foreach ($products->take(2) as $product) {
                $quantity = rand(1, 3);
                $unitPrice = (float) $product->price_1;
                $itemSubtotal = $unitPrice * $quantity;
                $subtotal1 += $itemSubtotal;

                $items1[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'color' => null,
                    'size' => null,
                    'product_image' => $product->feature_photo ?? null,
                    'points' => $product->points ?? 1
                ];
            }

            $multipleOrder1 = MultipleOrder::create([
                'user_id' => $sponsor->id,
                'sponsor_id' => $sponsor->dtehm_member_id ?? $sponsor->business_name,
                'stockist_id' => $stockist->dtehm_member_id ?? $stockist->business_name,
                'items_json' => json_encode($items1),
                'subtotal' => $subtotal1,
                'delivery_fee' => 5000,
                'total_amount' => $subtotal1 + 5000,
                'currency' => 'UGX',
                'delivery_method' => 'delivery',
                'delivery_address' => 'Test Address, Kampala, Uganda',
                'customer_phone' => $sponsor->phone_number ?? '0700000000',
                'customer_email' => $sponsor->email ?? 'test@example.com',
                'customer_notes' => 'Test order - Pending payment',
                'payment_status' => 'PENDING',
                'conversion_status' => 'PENDING',
                'status' => 'active'
            ]);

            $this->command->info("✓ Created MultipleOrder #{$multipleOrder1->id} - PENDING payment (UGX " . number_format($multipleOrder1->total_amount, 0) . ")");

            // Create Test Order 2: Completed Payment (for conversion testing)
            $items2 = [];
            $subtotal2 = 0;
            foreach ($products->take(3) as $product) {
                $quantity = rand(1, 2);
                $unitPrice = (float) $product->price_1;
                $itemSubtotal = $unitPrice * $quantity;
                $subtotal2 += $itemSubtotal;

                $items2[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'color' => null,
                    'size' => null,
                    'product_image' => $product->feature_photo ?? null,
                    'points' => $product->points ?? 1
                ];
            }

            $multipleOrder2 = MultipleOrder::create([
                'user_id' => $sponsor->id,
                'sponsor_id' => $sponsor->dtehm_member_id ?? $sponsor->business_name,
                'stockist_id' => $stockist->dtehm_member_id ?? $stockist->business_name,
                'items_json' => json_encode($items2),
                'subtotal' => $subtotal2,
                'delivery_fee' => 3000,
                'total_amount' => $subtotal2 + 3000,
                'currency' => 'UGX',
                'delivery_method' => 'pickup',
                'delivery_address' => null,
                'customer_phone' => $sponsor->phone_number ?? '0700000000',
                'customer_email' => $sponsor->email ?? 'test@example.com',
                'customer_notes' => 'Test order - Ready for conversion',
                'payment_status' => 'COMPLETED',
                'payment_completed_at' => now(),
                'pesapal_confirmation_code' => 'TEST_CONFIRM_' . time(),
                'pesapal_payment_method' => 'Mobile Money',
                'conversion_status' => 'PENDING',
                'status' => 'active'
            ]);

            $this->command->info("✓ Created MultipleOrder #{$multipleOrder2->id} - COMPLETED payment (UGX " . number_format($multipleOrder2->total_amount, 0) . ")");

            // Test conversion for Order 2
            $this->command->info("\n🔄 Testing automatic conversion to OrderedItems...");
            $conversionResult = $multipleOrder2->convertToOrderedItems();

            if ($conversionResult['success']) {
                $this->command->info("✓ Conversion successful! Created " . count($conversionResult['ordered_items']) . " OrderedItem(s)");
                
                foreach ($conversionResult['ordered_items'] as $orderedItem) {
                    $this->command->info("  - OrderedItem #{$orderedItem['id']}: Product #{$orderedItem['product_id']} x{$orderedItem['quantity']} = UGX " . number_format($orderedItem['subtotal'], 0));
                }
            } else {
                $this->command->error("✗ Conversion failed: " . $conversionResult['message']);
            }

            $this->command->info("\n" . str_repeat('=', 60));
            $this->command->info("MultipleOrder Test Data Summary:");
            $this->command->info(str_repeat('=', 60));
            $this->command->info("Sponsor: {$sponsor->name} (#{$sponsor->id})");
            $this->command->info("Stockist: {$stockist->name} (#{$stockist->id})");
            $this->command->info("\nTest Orders Created:");
            $this->command->info("1. Order #{$multipleOrder1->id} - Status: PENDING");
            $this->command->info("   Items: " . count($items1) . ", Total: UGX " . number_format($multipleOrder1->total_amount, 0));
            $this->command->info("\n2. Order #{$multipleOrder2->id} - Status: COMPLETED + CONVERTED");
            $this->command->info("   Items: " . count($items2) . ", Total: UGX " . number_format($multipleOrder2->total_amount, 0));
            $this->command->info("   OrderedItems Created: " . ($conversionResult['success'] ? count($conversionResult['ordered_items']) : 0));
            $this->command->info(str_repeat('=', 60));

            Log::info("MultipleOrder Test Seeder: Completed successfully");

        } catch (\Exception $e) {
            Log::error("MultipleOrder Test Seeder: Exception occurred", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->command->error("Test seeder failed: " . $e->getMessage());
        }
    }
}
