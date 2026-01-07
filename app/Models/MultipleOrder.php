<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MultipleOrder extends Model
{
    use HasFactory;

    protected $table = 'multiple_orders';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'sponsor_id',
        'sponsor_user_id',
        'stockist_id',
        'stockist_user_id',
        'items_json',
        'subtotal',
        'delivery_fee',
        'total_amount',
        'currency',
        'payment_status',
        'payment_completed_at',
        'pesapal_order_tracking_id',
        'pesapal_merchant_reference',
        'pesapal_redirect_url',
        'pesapal_callback_url',
        'pesapal_notification_id',
        'pesapal_status',
        'pesapal_status_code',
        'pesapal_payment_method',
        'pesapal_confirmation_code',
        'pesapal_payment_account',
        'pesapal_response',
        'pesapal_last_check',
        'conversion_status',
        'converted_at',
        'conversion_result',
        'conversion_error',
        'customer_notes',
        'delivery_method',
        'delivery_address',
        'customer_phone',
        'customer_email',
        'ip_address',
        'user_agent',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_completed_at' => 'datetime',
        'pesapal_last_check' => 'datetime',
        'converted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method - automatically handle sponsor/stockist validation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($multipleOrder) {
            // Set default statuses
            $multipleOrder->payment_status = $multipleOrder->payment_status ?? 'PENDING';
            $multipleOrder->conversion_status = $multipleOrder->conversion_status ?? 'PENDING';
            $multipleOrder->status = $multipleOrder->status ?? 'active';
            $multipleOrder->currency = $multipleOrder->currency ?? 'UGX';
            
            // Capture request metadata
            if (request()) {
                $multipleOrder->ip_address = $multipleOrder->ip_address ?? request()->ip();
                $multipleOrder->user_agent = $multipleOrder->user_agent ?? request()->userAgent();
            }
        });

        static::saving(function ($multipleOrder) {
            // Validate and resolve sponsor if provided
            if (!empty($multipleOrder->sponsor_id)) {
                $sponsor = User::where('id', $multipleOrder->sponsor_id)
                    ->orWhere('dtehm_member_id', $multipleOrder->sponsor_id)
                    ->orWhere('business_name', $multipleOrder->sponsor_id)
                    ->orWhere('username', $multipleOrder->sponsor_id)
                    ->first();

                if (!$sponsor) {
                    throw new \Exception("Sponsor not found for ID: {$multipleOrder->sponsor_id}");
                }

                if ($sponsor->is_dtehm_member !== 'Yes') {
                    throw new \Exception("Sponsor {$multipleOrder->sponsor_id} is not an active DTEHM member");
                }

                $multipleOrder->sponsor_user_id = $sponsor->id;
            }

            // Validate and resolve stockist if provided
            if (!empty($multipleOrder->stockist_id)) {
                $stockist = User::where('id', $multipleOrder->stockist_id)
                    ->orWhere('dtehm_member_id', $multipleOrder->stockist_id)
                    ->orWhere('business_name', $multipleOrder->stockist_id)
                    ->orWhere('username', $multipleOrder->stockist_id)
                    ->first();

                if (!$stockist) {
                    throw new \Exception("Stockist not found for ID: {$multipleOrder->stockist_id}");
                }

                if ($stockist->is_dtehm_member !== 'Yes') {
                    throw new \Exception("Stockist {$multipleOrder->stockist_id} is not an active DTEHM member");
                }

                $multipleOrder->stockist_user_id = $stockist->id;
            }

            // Validate items_json
            if (!empty($multipleOrder->items_json)) {
                $items = json_decode($multipleOrder->items_json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Invalid JSON format for items_json");
                }
                
                if (empty($items) || !is_array($items)) {
                    throw new \Exception("items_json must contain at least one item");
                }
            }
        });
    }

    /**
     * Get items as array
     */
    public function getItems()
    {
        if (empty($this->items_json)) {
            return [];
        }

        $items = json_decode($this->items_json, true);
        return is_array($items) ? $items : [];
    }

    /**
     * Set items from array
     */
    public function setItems(array $items)
    {
        $this->items_json = json_encode($items);
        return $this;
    }

    /**
     * Get Pesapal response as array
     */
    public function getPesapalResponse()
    {
        if (empty($this->pesapal_response)) {
            return null;
        }

        $response = json_decode($this->pesapal_response, true);
        return is_array($response) ? $response : null;
    }

    /**
     * Get conversion result as array
     */
    public function getConversionResult()
    {
        if (empty($this->conversion_result)) {
            return null;
        }

        $result = json_decode($this->conversion_result, true);
        return is_array($result) ? $result : null;
    }

    /**
     * Check if payment is completed
     */
    public function isPaymentCompleted()
    {
        return $this->payment_status === 'COMPLETED';
    }

    /**
     * Check if payment is pending
     */
    public function isPaymentPending()
    {
        return in_array($this->payment_status, ['PENDING', 'PROCESSING']);
    }

    /**
     * Check if payment failed
     */
    public function isPaymentFailed()
    {
        return in_array($this->payment_status, ['FAILED', 'CANCELLED']);
    }

    /**
     * Check if already converted to OrderedItems
     */
    public function isConverted()
    {
        return $this->conversion_status === 'COMPLETED';
    }

    /**
     * Check if conversion is pending
     */
    public function isConversionPending()
    {
        return $this->conversion_status === 'PENDING';
    }

    /**
     * Convert this MultipleOrder to real OrderedItem records
     * Only if payment is completed and not already converted
     * 
     * @return array ['success' => bool, 'message' => string, 'ordered_items' => array]
     */
    public function convertToOrderedItems()
    {
        try {
            // Check if payment is completed
            if (!$this->isPaymentCompleted()) {
                return [
                    'success' => false,
                    'message' => 'Payment must be completed before conversion',
                    'ordered_items' => []
                ];
            }

            // Check if already converted
            if ($this->isConverted()) {
                return [
                    'success' => false,
                    'message' => 'This order has already been converted to OrderedItems',
                    'ordered_items' => []
                ];
            }

            // Validate we have required data
            if (empty($this->sponsor_user_id)) {
                throw new \Exception('Sponsor user ID is required for conversion');
            }

            if (empty($this->stockist_user_id)) {
                throw new \Exception('Stockist user ID is required for conversion');
            }

            $items = $this->getItems();
            if (empty($items)) {
                throw new \Exception('No items found to convert');
            }

            // Update conversion status to PROCESSING
            $this->update([
                'conversion_status' => 'PROCESSING'
            ]);

            Log::info("MultipleOrder #{$this->id}: Starting conversion to OrderedItems", [
                'item_count' => count($items),
                'sponsor_user_id' => $this->sponsor_user_id,
                'stockist_user_id' => $this->stockist_user_id
            ]);

            $orderedItems = [];
            $errors = [];

            // Begin transaction for data integrity
            DB::beginTransaction();

            try {
                foreach ($items as $index => $item) {
                    try {
                        // Create OrderedItem
                        $orderedItem = OrderedItem::create([
                            'product' => $item['product_id'],
                            'qty' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'subtotal' => $item['subtotal'],
                            'amount' => $item['unit_price'], // For backward compatibility
                            'sponsor_id' => $this->sponsor_id,
                            'stockist_id' => $this->stockist_id,
                            'sponsor_user_id' => $this->sponsor_user_id,
                            'stockist_user_id' => $this->stockist_user_id,
                            'color' => $item['color'] ?? null,
                            'size' => $item['size'] ?? null,
                            'item_is_paid' => 'Yes',
                            'item_paid_date' => $this->payment_completed_at,
                            'item_paid_amount' => $item['subtotal'],
                            // Link to this multiple order via a comment or custom field
                            // For now, we track it through timestamps
                        ]);

                        $orderedItems[] = [
                            'id' => $orderedItem->id,
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity'],
                            'subtotal' => $item['subtotal']
                        ];

                        Log::info("MultipleOrder #{$this->id}: Created OrderedItem #{$orderedItem->id}", [
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity']
                        ]);

                    } catch (\Exception $e) {
                        $errors[] = [
                            'item_index' => $index,
                            'product_id' => $item['product_id'] ?? 'unknown',
                            'error' => $e->getMessage()
                        ];
                        
                        Log::error("MultipleOrder #{$this->id}: Failed to create OrderedItem for item #{$index}", [
                            'error' => $e->getMessage(),
                            'item' => $item
                        ]);
                    }
                }

                // If all items failed, rollback and throw
                if (empty($orderedItems) && !empty($errors)) {
                    throw new \Exception('All items failed to convert: ' . json_encode($errors));
                }

                // Update conversion status to COMPLETED
                $this->update([
                    'conversion_status' => 'COMPLETED',
                    'converted_at' => now(),
                    'conversion_result' => json_encode([
                        'success' => true,
                        'ordered_items_created' => count($orderedItems),
                        'items' => $orderedItems,
                        'errors' => $errors,
                        'converted_at' => now()->toDateTimeString()
                    ])
                ]);

                DB::commit();

                Log::info("MultipleOrder #{$this->id}: Conversion completed successfully", [
                    'ordered_items_created' => count($orderedItems),
                    'errors' => count($errors)
                ]);

                return [
                    'success' => true,
                    'message' => count($orderedItems) . ' OrderedItem(s) created successfully' . 
                                 (count($errors) > 0 ? ' with ' . count($errors) . ' errors' : ''),
                    'ordered_items' => $orderedItems,
                    'errors' => $errors
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            // Update conversion status to FAILED
            $this->update([
                'conversion_status' => 'FAILED',
                'conversion_error' => $e->getMessage()
            ]);

            Log::error("MultipleOrder #{$this->id}: Conversion failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Conversion failed: ' . $e->getMessage(),
                'ordered_items' => []
            ];
        }
    }

    /**
     * Generate unique merchant reference for Pesapal
     */
    public function generateMerchantReference()
    {
        return 'MO_' . $this->id . '_' . time();
    }

    /**
     * Relationships
     */

    /**
     * Belongs to User (the purchaser)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Belongs to User (sponsor)
     */
    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_user_id');
    }

    /**
     * Belongs to User (stockist)
     */
    public function stockist()
    {
        return $this->belongsTo(User::class, 'stockist_user_id');
    }

    /**
     * Get OrderedItems created from this MultipleOrder
     * Note: This is a soft relationship based on timestamps
     */
    public function orderedItems()
    {
        if (!$this->isConverted() || !$this->converted_at) {
            return collect([]);
        }

        // Get OrderedItems created around the same time as conversion
        // This is a best-effort match since we don't have a direct foreign key
        return OrderedItem::where('sponsor_user_id', $this->sponsor_user_id)
            ->where('stockist_user_id', $this->stockist_user_id)
            ->whereBetween('created_at', [
                $this->converted_at->subMinutes(5),
                $this->converted_at->addMinutes(5)
            ])
            ->where('item_is_paid', 'Yes')
            ->get();
    }
}
