<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class OrderedItem extends Model
{
    use HasFactory;

    protected $table = 'ordered_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order',
        'product',
        'qty',
        'amount',
        'unit_price',
        'subtotal',
        'color',
        'size',
        // New fields for sponsor and stockist
        'sponsor_id',
        'stockist_id',
        'sponsor_user_id',
        'stockist_user_id',
        'commission_stockist',
        // Payment tracking
        'item_is_paid',
        'item_paid_date',
        'item_paid_amount',
        // DTEHM Seller
        'has_detehm_seller',
        'dtehm_seller_id',
        'dtehm_user_id',
        // Commission processing
        'commission_is_processed',
        'commission_processed_date',
        'total_commission_amount',
        'balance_after_commission',
        'commission_seller',
        'commission_parent_1',
        'commission_parent_2',
        'commission_parent_3',
        'commission_parent_4',
        'commission_parent_5',
        'commission_parent_6',
        'commission_parent_7',
        'commission_parent_8',
        'commission_parent_9',
        'commission_parent_10',
        'parent_1_user_id',
        'parent_2_user_id',
        'parent_3_user_id',
        'parent_4_user_id',
        'parent_5_user_id',
        'parent_6_user_id',
        'parent_7_user_id',
        'parent_8_user_id',
        'parent_9_user_id',
        'parent_10_user_id',
        // Points system
        'points_earned',
    ];

    /**
     * Boot method - automatically set prices when saving
     */
    protected static function boot()
    {
        parent::boot();

        //creating 
        static::creating(function ($item) {
            $item->commission_is_processed = 'No';
        });
        static::saving(function ($item) {
            // Validate and resolve sponsor
            if (empty($item->sponsor_id)) {
                throw new \Exception("Sponsor ID is required");
            }

            $sponsor = User::where('dtehm_member_id', $item->sponsor_id)
                ->orWhere('business_name', $item->sponsor_id)
                ->first();
            
            if ($sponsor == null) {
                throw new \Exception("Sponsor not found for ID: {$item->sponsor_id}. Please verify the sponsor ID is correct.");
            }

            if ($sponsor->is_dtehm_member !== 'Yes') {
                throw new \Exception("Sponsor {$item->sponsor_id} is not an active DTEHM member");
            }

            // Validate and resolve stockist
            if (empty($item->stockist_id)) {
                throw new \Exception("Stockist ID is required");
            }

            $stockist = User::where('dtehm_member_id', $item->stockist_id)
                ->orWhere('business_name', $item->stockist_id)
                ->first();

            if ($stockist == null) {
                throw new \Exception("Stockist not found for ID: {$item->stockist_id}. Please verify the stockist ID is correct.");
            }

            if ($stockist->is_dtehm_member !== 'Yes') {
                throw new \Exception("Stockist {$item->stockist_id} is not an active DTEHM member");
            }

            $item->has_detehm_seller = 'Yes';
            $item->stockist_user_id = $stockist->id;
            $item->sponsor_user_id = $sponsor->id;
            $item->dtehm_user_id = $sponsor->id;

            // Validate product
            if (empty($item->product)) {
                throw new \Exception("Product ID is required");
            }

            $product = Product::find($item->product);
            if (!$product) {
                throw new \Exception("Product not found for ID: {$item->product}");
            }

            // Set unit price from product if not provided
            if (empty($item->unit_price) || $item->unit_price == 0) {
                $item->unit_price = $product->price_1;
            }

            // Ensure unit_price is numeric and valid
            $item->unit_price = floatval($item->unit_price ?? 0);
            if ($item->unit_price <= 0) {
                throw new \Exception("Invalid product price: {$item->unit_price}");
            }

            // Ensure quantity is numeric and valid
            $quantity = floatval($item->qty ?? 1);
            if ($quantity <= 0) {
                $quantity = 1; // Default to 1 if invalid
            }
            $item->qty = $quantity;

            // Calculate subtotal
            $item->subtotal = $item->unit_price * $quantity;

            // Set amount for backward compatibility
            $item->amount = $item->unit_price;

            // Calculate points earned (product points * quantity)
            $productPoints = $product->points ?? 1; // Default to 1 if not set
            $item->points_earned = $productPoints * $quantity;
        });

        // Auto-process commission when item is created (all sales are already paid)
        static::created(function ($item) {
            self::do_process_commission($item);
            
            // Update sponsor's total points
            if ($item->sponsor_user_id && $item->points_earned > 0) {
                User::where('id', $item->sponsor_user_id)
                    ->increment('total_points', $item->points_earned);
                
                \Log::info("Points awarded to sponsor", [
                    'sponsor_user_id' => $item->sponsor_user_id,
                    'points_earned' => $item->points_earned,
                    'ordered_item_id' => $item->id
                ]);
            }
        });
        static::updated(function ($item) {
            self::do_process_commission($item);
        });
    }


    /**
     * Process commission for this ordered item
     * Automatically called after item is created/updated
     * 
     * @param OrderedItem $model
     * @return void
     */
    public static function do_process_commission($model)
    {
        // Skip if already processed
        if ($model->commission_is_processed == 'Yes') {
            Log::info("Commission already processed for OrderedItem #{$model->id}");
            return;
        }

        // Skip if no DTEHM seller
        if ($model->has_detehm_seller !== 'Yes' || empty($model->dtehm_user_id)) {
            Log::info("No DTEHM seller for OrderedItem #{$model->id}, skipping commission");
            return;
        }

        try {
            Log::info("Processing commission for OrderedItem #{$model->id}");
            
            $commissionService = new \App\Services\CommissionService();
            $result = $commissionService->processCommission($model);

            if ($result['success']) {
                Log::info("Commission auto-processed successfully for OrderedItem #{$model->id}", [
                    'total_commission' => $result['total_commission'] ?? 0,
                    'beneficiaries' => $result['beneficiaries'] ?? 0,
                ]);
            } else {
                Log::warning("Commission auto-processing returned failure for OrderedItem #{$model->id}", [
                    'message' => $result['message'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Commission auto-processing exception for OrderedItem #{$model->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't throw - allow the sale to complete even if commission fails
        }
    }

    /**
     * Belongs to Product
     */
    public function pro()
    {
        return $this->belongsTo(Product::class, 'product')->withDefault([
            'name' => 'Product Not Found',
            'price_1' => 0,
            'feature_photo' => null,
        ]);
    }

    /**
     * Belongs to UniversalPayment
     */
    public function payment()
    {
        return $this->belongsTo(\App\Models\UniversalPayment::class, 'universal_payment_id');
    }

    /**
     * Belongs to User (the purchaser)
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'sponsor_user_id');
    }
}
