<?php

namespace App\Services;

use App\Models\User;
use App\Models\OrderedItem;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CommissionService
{
    /**
     * Commission rates for each level (in percentage)
     * 
     * Commission Structure:
     * 1. Stockist: 7% - The person who stocks/distributes the product
     * 2. Sponsor: 8% - The person who sold the product (dtehm_user_id)
     * 3. Network (GN1-GN10): Parent hierarchy of the SPONSOR
     */
    const COMMISSION_RATES = [
        'stockist' => 7.0,   // 7% - Stockist commission
        'sponsor' => 8.0,    // 8% - Sponsor (seller) commission
        'parent_1' => 3.0,   // 3% - GN1
        'parent_2' => 2.5,   // 2.5% - GN2
        'parent_3' => 2.0,   // 2.0% - GN3
        'parent_4' => 1.5,   // 1.5% - GN4
        'parent_5' => 1.0,   // 1.0% - GN5
        'parent_6' => 0.8,   // 0.8% - GN6
        'parent_7' => 0.6,   // 0.6% - GN7
        'parent_8' => 0.5,   // 0.5% - GN8
        'parent_9' => 0.4,   // 0.4% - GN9
        'parent_10' => 0.2,  // 0.2% - GN10
    ];

    /**
     * Process commissions for an ordered item
     * 
     * @param OrderedItem $orderedItem
     * @return array Result with success status and details
     */
    public function processCommission(OrderedItem $orderedItem)
    {
        // CRITICAL VALIDATION: Check if commission already processed
        if ($orderedItem->commission_is_processed === 'Yes') {
            Log::info("Commission already processed - skipping", [
                'item_id' => $orderedItem->id,
                'processed_date' => $orderedItem->commission_processed_date,
            ]);
            return [
                'success' => false,
                'message' => 'Commission already processed for this item',
                'item_id' => $orderedItem->id,
            ];
        }

        // CRITICAL VALIDATION: Ensure DTEHM seller exists
        if ($orderedItem->has_detehm_seller !== 'Yes' || empty($orderedItem->dtehm_user_id)) {
            Log::warning("No DTEHM seller for item - skipping commission", [
                'item_id' => $orderedItem->id,
                'has_detehm_seller' => $orderedItem->has_detehm_seller,
                'dtehm_user_id' => $orderedItem->dtehm_user_id,
            ]);
            return [
                'success' => false,
                'message' => 'No DTEHM seller associated with this item',
                'item_id' => $orderedItem->id,
            ];
        }
        
        // CRITICAL VALIDATION: Ensure item has valid subtotal
        $itemSubtotal = floatval($orderedItem->subtotal ?? $orderedItem->item_paid_amount ?? 0);
        if ($itemSubtotal <= 0) {
            throw new Exception("Invalid item subtotal: {$itemSubtotal} for OrderedItem ID: {$orderedItem->id}");
        } 
        
/* 
    "id" => 4
    "created_at" => "2025-12-05 00:52:35"
    "updated_at" => "2025-12-05 09:06:14"
    "order" => null
    "product" => "18"
    "sponsor_id" => "DIP0046"
    "stockist_id" => "DIP0046"
    "qty" => "1"
    "amount" => "35000"
    "unit_price" => "35000.00"
    "subtotal" => "35000.00"
    "item_is_paid" => "No"
    "item_paid_date" => null
    "item_paid_amount" => null
    "dtehm_seller_id" => null
    "dtehm_user_id" => null
    "stockist_user_id" => 47
    "sponsor_user_id" => 47
    "commission_is_processed" => "No"
    "commission_processed_date" => null
    "total_commission_amount" => null
    "balance_after_commission" => null
    "commission_seller" => null
    "commission_stockist" => "0.00"
    "commission_parent_1" => null
    "commission_parent_2" => null
    "commission_parent_3" => null
    "commission_parent_4" => null
    "commission_parent_5" => null
    "commission_parent_6" => null
    "commission_parent_7" => null
    "commission_parent_8" => null
    "commission_parent_9" => null
    "commission_parent_10" => null
    "parent_1_user_id" => null
    "parent_2_user_id" => null
    "parent_3_user_id" => null
    "parent_4_user_id" => null
    "parent_5_user_id" => null
    "parent_6_user_id" => null
    "parent_7_user_id" => null
    "parent_8_user_id" => null
    "parent_9_user_id" => null
    "parent_10_user_id" => null
    "color" => "......."
    "size" => null
*/


        // Begin transaction for data integrity
        DB::beginTransaction();

        try {
            // CRITICAL VALIDATION: Ensure seller exists in database
            $seller = User::find($orderedItem->dtehm_user_id);
            if (!$seller) {
                throw new Exception("CRITICAL: Seller user not found in database. ID: {$orderedItem->dtehm_user_id}, OrderedItem ID: {$orderedItem->id}");
            }
            
            // CRITICAL VALIDATION: Ensure seller is active DTEHM member
            if ($seller->is_dtehm_member !== 'Yes') {
                throw new Exception("CRITICAL: Seller is not an active DTEHM member. Seller ID: {$seller->id}, OrderedItem ID: {$orderedItem->id}");
            }

            Log::info("============ STARTING COMMISSION PROCESSING ============", [
                'item_id' => $orderedItem->id,
                'seller_id' => $seller->id,
                'seller_name' => $seller->name,
                'seller_dip_id' => $seller->business_name,
                'seller_dtehm_id' => $seller->dtehm_member_id,
                'subtotal' => $itemSubtotal,
                'stockist_id' => $orderedItem->stockist_user_id,
            ]);

            $commissionsProcessed = [];
            $totalCommissionAmount = 0;

            // 1. Process STOCKIST commission (7%) if stockist exists
            if (!empty($orderedItem->stockist_user_id)) {
                $stockist = User::find($orderedItem->stockist_user_id);
                if (!$stockist) {
                    throw new Exception("CRITICAL: Stockist user not found. ID: {$orderedItem->stockist_user_id}, OrderedItem ID: {$orderedItem->id}");
                }
                
                // Validate stockist is active DTEHM member
                if ($stockist->is_dtehm_member !== 'Yes') {
                    Log::warning("Stockist is not DTEHM member - skipping stockist commission", [
                        'stockist_id' => $stockist->id,
                        'is_dtehm_member' => $stockist->is_dtehm_member,
                    ]);
                } else {
                    $stockistCommission = $this->calculateCommission($itemSubtotal, self::COMMISSION_RATES['stockist']);
                    
                    if ($stockistCommission <= 0) {
                        throw new Exception("CRITICAL: Invalid stockist commission amount: {$stockistCommission}");
                    }
                    
                    $stockistTransaction = $this->createCommissionTransaction(
                        $stockist,
                        $stockistCommission,
                        $orderedItem,
                        'Stockist',
                        self::COMMISSION_RATES['stockist']
                    );

                    if ($stockistTransaction) {
                        $orderedItem->commission_stockist = $stockistCommission;
                        $totalCommissionAmount += $stockistCommission;
                        $commissionsProcessed[] = [
                            'level' => 'stockist',
                            'user_id' => $stockist->id,
                            'user_name' => $stockist->name,
                            'amount' => $stockistCommission,
                        ];
                        Log::info("✓ Stockist commission created", [
                            'user_id' => $stockist->id,
                            'user_name' => $stockist->name,
                            'amount' => $stockistCommission,
                            'transaction_id' => $stockistTransaction->id,
                        ]);
                    }
                }
            }

            // 2. Process SPONSOR commission (8%) - The seller gets 8%
            $sponsorCommission = $this->calculateCommission($itemSubtotal, self::COMMISSION_RATES['sponsor']);
            
            if ($sponsorCommission <= 0) {
                throw new Exception("CRITICAL: Invalid sponsor commission amount: {$sponsorCommission}");
            }
            
            $sponsorTransaction = $this->createCommissionTransaction(
                $seller,
                $sponsorCommission,
                $orderedItem,
                'Sponsor',
                self::COMMISSION_RATES['sponsor']
            );

            if ($sponsorTransaction) {
                $orderedItem->commission_seller = $sponsorCommission; // Store in commission_seller field
                $totalCommissionAmount += $sponsorCommission;
                $commissionsProcessed[] = [
                    'level' => 'sponsor',
                    'user_id' => $seller->id,
                    'user_name' => $seller->name,
                    'amount' => $sponsorCommission,
                ];
                Log::info("✓ Sponsor commission created", [
                    'user_id' => $seller->id,
                    'user_name' => $seller->name,
                    'amount' => $sponsorCommission,
                    'transaction_id' => $sponsorTransaction->id,
                ]);
            }

            // 3. Process NETWORK commissions (GN1 to GN10) - These are the SPONSOR's parent hierarchy
            for ($level = 1; $level <= 10; $level++) {
                $parentField = "parent_{$level}";
                $parentUserId = $seller->$parentField;

                // Store parent user ID in ordered_item for tracking
                $orderedItem->{"parent_{$level}_user_id"} = $parentUserId;

                if (empty($parentUserId)) {
                    Log::info("No parent at level {$level}, skipping");
                    continue;
                }

                $parentUser = User::find($parentUserId);

                if (!$parentUser) {
                    Log::warning("Parent user not found", [
                        'level' => $level,
                        'parent_id' => $parentUserId,
                    ]);
                    continue;
                }

                // Validate parent is active DTEHM member
                if ($parentUser->is_dtehm_member !== 'Yes') {
                    Log::warning("Parent Level {$level} is not DTEHM member - skipping commission", [
                        'parent_id' => $parentUser->id,
                        'parent_name' => $parentUser->name,
                        'is_dtehm_member' => $parentUser->is_dtehm_member,
                    ]);
                    continue;
                }

                $commissionRate = self::COMMISSION_RATES["parent_{$level}"];
                $commissionAmount = $this->calculateCommission($itemSubtotal, $commissionRate);
                
                if ($commissionAmount <= 0) {
                    Log::warning("Skipping Parent Level {$level} - zero commission amount", [
                        'parent_id' => $parentUser->id,
                        'rate' => $commissionRate,
                    ]);
                    continue;
                }

                $parentTransaction = $this->createCommissionTransaction(
                    $parentUser,
                    $commissionAmount,
                    $orderedItem,
                    "Parent Level {$level}",
                    $commissionRate
                );

                if ($parentTransaction) {
                    $orderedItem->{"commission_parent_{$level}"} = $commissionAmount;
                    $totalCommissionAmount += $commissionAmount;
                    $commissionsProcessed[] = [
                        'level' => "parent_{$level}",
                        'user_id' => $parentUser->id,
                        'user_name' => $parentUser->name,
                        'amount' => $commissionAmount,
                    ];
                    Log::info("✓ Parent Level {$level} commission created", [
                        'user_id' => $parentUser->id,
                        'user_name' => $parentUser->name,
                        'amount' => $commissionAmount,
                        'transaction_id' => $parentTransaction->id,
                    ]);
                }
            }

            // CRITICAL: Use direct DB update to avoid triggering observers/events and deadlock loops
            DB::table('ordered_items')
                ->where('id', $orderedItem->id)
                ->update([
                    'commission_is_processed' => 'Yes',
                    'commission_processed_date' => now(),
                    'total_commission_amount' => $totalCommissionAmount,
                    'balance_after_commission' => $itemSubtotal - $totalCommissionAmount,
                    'updated_at' => now(),
                ]);

            // CRITICAL: Verify at least sponsor commission was created
            if ($totalCommissionAmount <= 0) {
                throw new Exception("CRITICAL: No commissions were created. This should not happen if seller is valid DTEHM member.");
            }

            DB::commit();

            Log::info("============ COMMISSION PROCESSING COMPLETED SUCCESSFULLY ============", [
                'item_id' => $orderedItem->id,
                'total_commission' => $totalCommissionAmount,
                'balance_after_commission' => $itemSubtotal - $totalCommissionAmount,
                'beneficiaries' => count($commissionsProcessed),
                'commissions_breakdown' => $commissionsProcessed,
            ]);

            return [
                'success' => true,
                'message' => 'Commission processed successfully',
                'item_id' => $orderedItem->id,
                'total_commission' => $totalCommissionAmount,
                'beneficiaries' => count($commissionsProcessed),
                'commissions' => $commissionsProcessed,
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error("============ COMMISSION PROCESSING FAILED ============", [
                'item_id' => $orderedItem->id,
                'seller_id' => $orderedItem->dtehm_user_id ?? 'N/A',
                'subtotal' => $itemSubtotal ?? 0,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Commission processing failed: ' . $e->getMessage(),
                'item_id' => $orderedItem->id,
                'error_details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate commission amount
     * 
     * @param float $amount
     * @param float $percentage
     * @return float
     */
    private function calculateCommission($amount, $percentage)
    {
        return round(($amount * $percentage) / 100, 2);
    }

    /**
     * Create account transaction for commission
     * 
     * @param User $user
     * @param float $amount
     * @param OrderedItem $orderedItem
     * @param string $level
     * @param float $percentage
     * @return AccountTransaction|null
     */
    private function createCommissionTransaction(User $user, $amount, OrderedItem $orderedItem, $level, $percentage)
    {
        try {
            // CRITICAL: Check for duplicate commission first
            $commissionType = 'product_commission_' . strtolower(str_replace(' ', '_', $level));
            
            $existingTransaction = AccountTransaction::where('user_id', $user->id)
                ->where('commission_type', $commissionType)
                ->where('commission_reference_id', $orderedItem->id)
                ->first();
            
            if ($existingTransaction) {
                Log::warning("Duplicate commission detected - skipping", [
                    'user_id' => $user->id,
                    'ordered_item_id' => $orderedItem->id,
                    'commission_type' => $commissionType,
                    'existing_transaction_id' => $existingTransaction->id,
                    'level' => $level,
                ]);
                return $existingTransaction; // Return existing to prevent errors
            }
            
            $balanceBefore = $user->calculateAccountBalance();
            
            // Get product information for better narration
            $product = $orderedItem->pro;
            $productName = $product ? $product->name : "Product #{$orderedItem->product}";
            $quantity = $orderedItem->qty ?? 1;
            
            // Build comprehensive transaction description
            $description = "MLM Commission - {$level}\n";
            $description .= "Product: {$productName}\n";
            $description .= "Quantity: {$quantity} unit(s)\n";
            $description .= "Sale Amount: UGX " . number_format($orderedItem->subtotal, 2) . "\n";
            $description .= "Commission Rate: {$percentage}%\n";
            $description .= "Commission Earned: UGX " . number_format($amount, 2) . "\n";
            $description .= "Sale Reference: #{$orderedItem->id}\n";
            $description .= "Processed: " . now()->format('d M Y, H:i');

            // Use AccountTransaction model (not batch insert)
            $transaction = AccountTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'transaction_date' => now(),
                'description' => $description,
                'source' => 'product_commission', // Product sale commission
                'created_by_id' => $orderedItem->dtehm_user_id, // Seller ID
                'commission_type' => $commissionType,
                'commission_reference_id' => $orderedItem->id,
                'commission_amount' => $amount,
            ]);

            Log::info("Commission transaction created", [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'commission_type' => $commissionType,
                'ordered_item_id' => $orderedItem->id,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
            ]);

            return $transaction;

        } catch (Exception $e) {
            Log::error("Failed to create commission transaction", [
                'user_id' => $user->id,
                'amount' => $amount,
                'ordered_item_id' => $orderedItem->id,
                'level' => $level,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

 

    /**
     * Get commission summary for a user
     * 
     * @param int $userId
     * @return array
     */
    public function getUserCommissionSummary($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found',
            ];
        }

        // Get all commission transactions
        $commissions = AccountTransaction::where('user_id', $userId)
            ->where('source', 'product_commission')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalEarned = $commissions->sum('amount');
        $transactionCount = $commissions->count();

        // Get commission breakdown by level (from description)
        $byLevel = [
            'seller' => 0,
            'parent_levels' => array_fill(1, 10, 0),
        ];

        foreach ($commissions as $commission) {
            if (str_contains($commission->description, 'Level: Seller')) {
                $byLevel['seller'] += $commission->amount;
            } else {
                for ($i = 1; $i <= 10; $i++) {
                    if (str_contains($commission->description, "Level: Parent Level {$i}")) {
                        $byLevel['parent_levels'][$i] += $commission->amount;
                        break;
                    }
                }
            }
        }

        return [
            'success' => true,
            'user_id' => $userId,
            'user_name' => $user->name,
            'total_earned' => $totalEarned,
            'transaction_count' => $transactionCount,
            'breakdown' => $byLevel,
            'recent_transactions' => $commissions->take(10),
        ];
    }
}
