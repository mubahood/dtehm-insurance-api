<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Models\UniversalPayment;
use App\Models\OrderedItem;
use App\Services\PesapalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductPurchaseController extends Controller
{
    protected $pesapalService;

    public function __construct(PesapalService $pesapalService)
    {
        $this->pesapalService = $pesapalService;
    }

    /**
     * Initialize product purchase (create pending payment)
     * POST /api/product-purchase/initialize
     * 
     * Request: {
     *   "product_id": 1,
     *   "quantity": 1,
     *   "sponsor_id": "DTEHM20250001",
     *   "stockist_id": "DTEHM20250002",
     *   "user_id": 123,
     *   "callback_url": "optional custom callback",
     *   "is_paid_by_admin": false,
     *   "admin_payment_note": "Payment already received via bank transfer"
     * }
     */
    public function initialize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'sponsor_id' => 'required|string',
            'stockist_id' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'callback_url' => 'nullable|url',
            'is_paid_by_admin' => 'nullable',
            'admin_payment_note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get user ID from request or header
            $userId = $request->user_id ?? $this->getUserIdFromHeader($request);
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'User authentication required'
                ], 401);
            }

            // Get and validate product
            $product = Product::find($request->product_id);
            if (!$product) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Product not found'
                ], 404);
            }

            // Check stock availability
            if ($product->in_stock !== 'Yes') {
                return response()->json([
                    'code' => 0,
                    'message' => 'Product is currently out of stock'
                ], 400);
            }

            // Validate sponsor
            $sponsor = User::where('dtehm_member_id', $request->sponsor_id)
                ->orWhere('business_name', $request->sponsor_id)
                ->first();

            if (!$sponsor) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Sponsor not found. Please verify the Sponsor ID.'
                ], 404);
            }

            if ($sponsor->is_dtehm_member !== 'Yes') {
                return response()->json([
                    'code' => 0,
                    'message' => 'Sponsor must be an active DTEHM member'
                ], 400);
            }

            // Validate stockist
            $stockist = User::where('dtehm_member_id', $request->stockist_id)
                ->orWhere('business_name', $request->stockist_id)
                ->first();

            if (!$stockist) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Stockist not found. Please verify the Stockist ID.'
                ], 404);
            }

            if ($stockist->is_dtehm_member !== 'Yes') {
                return response()->json([
                    'code' => 0,
                    'message' => 'Stockist must be an active DTEHM member'
                ], 400);
            }

            // Calculate total amount
            $unitPrice = floatval($product->price_1 ?? $product->price ?? 0);
            $quantity = intval($request->quantity);
            $totalAmount = $unitPrice * $quantity;

            if ($totalAmount <= 0) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Invalid product price'
                ], 400);
            }

            // Get user
            $user = User::find($userId);

            // Create universal payment record
            $isPaidByAdmin = $request->boolean('is_paid_by_admin', false);
            $adminPaymentNote = $request->admin_payment_note;
            
            $paymentData = [
                'payment_type' => 'product',
                'payment_category' => 'e-commerce',
                'user_id' => $userId,
                'customer_name' => $user->name ?? $user->first_name . ' ' . $user->last_name,
                'customer_email' => $user->email ?? '',
                'customer_phone' => $user->phone_number ?? $user->phone ?? '',
                'payment_items' => [
                    [
                        'type' => 'product',
                        'id' => $product->id,
                        'name' => $product->name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'amount' => $totalAmount,
                        'sponsor_id' => $request->sponsor_id,
                        'stockist_id' => $request->stockist_id,
                        'sponsor_user_id' => $sponsor->id,
                        'stockist_user_id' => $stockist->id,
                    ]
                ],
                'items_count' => 1,
                'amount' => $totalAmount,
                'currency' => 'UGX',
                'description' => "Purchase of {$quantity}x {$product->name}",
                'payment_gateway' => $isPaidByAdmin ? 'admin_bypass' : 'pesapal',
                'payment_method' => $isPaidByAdmin ? 'cash_or_other' : 'mobile_money',
                'status' => $isPaidByAdmin ? 'COMPLETED' : 'PENDING',
                'paid_by_admin' => $isPaidByAdmin,
                'admin_payment_note' => $adminPaymentNote,
                'marked_paid_by' => $isPaidByAdmin ? $userId : null,
                'marked_paid_at' => $isPaidByAdmin ? now() : null,
                'payment_date' => $isPaidByAdmin ? now() : null,
                'confirmed_at' => $isPaidByAdmin ? now() : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_by' => $userId,
                'metadata' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'sponsor_id' => $request->sponsor_id,
                    'stockist_id' => $request->stockist_id,
                    'purchase_type' => 'direct_mobile_app',
                    'admin_bypass' => $isPaidByAdmin,
                ]
            ];

            $payment = UniversalPayment::create($paymentData);

            Log::info('Product purchase initialized', [
                'payment_id' => $payment->id,
                'product_id' => $product->id,
                'user_id' => $userId,
                'amount' => $totalAmount,
                'paid_by_admin' => $isPaidByAdmin,
            ]);

            // If admin marked as paid, process the sale immediately
            if ($isPaidByAdmin) {
                $processResult = $this->processProductPurchase($payment);
                
                if ($processResult['code'] != 1) {
                    return response()->json([
                        'code' => 0,
                        'message' => 'Payment created but failed to process sale',
                        'error' => $processResult['message'] ?? 'Unknown error'
                    ], 500);
                }

                return response()->json([
                    'code' => 1,
                    'message' => 'Product purchase completed successfully (Admin Bypass)',
                    'data' => [
                        'payment' => [
                            'id' => $payment->id,
                            'payment_reference' => $payment->payment_reference,
                            'amount' => $payment->amount,
                            'currency' => $payment->currency,
                            'status' => $payment->status,
                            'paid_by_admin' => true,
                        ],
                        'product' => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'total' => $totalAmount,
                        ],
                        'ordered_items' => $processResult['data']['ordered_items'] ?? [],
                        'admin_bypass' => true,
                    ]
                ], 201);
            }

            // Regular flow: Initialize Pesapal payment
            $callbackUrl = $request->callback_url ?? url('/api/product-purchase/pesapal/callback');
            $pesapalResponse = $this->initializePesapalPayment($payment, $callbackUrl);

            if ($pesapalResponse['code'] != 1) {
                // Delete the payment record if Pesapal initialization fails
                $payment->delete();
                
                return response()->json([
                    'code' => 0,
                    'message' => 'Failed to initialize payment gateway',
                    'error' => $pesapalResponse['message'] ?? 'Unknown error'
                ], 500);
            }

            return response()->json([
                'code' => 1,
                'message' => 'Product purchase initialized successfully',
                'data' => [
                    'payment' => [
                        'id' => $payment->id,
                        'payment_reference' => $payment->payment_reference,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status,
                    ],
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total' => $totalAmount,
                    ],
                    'pesapal' => $pesapalResponse['data'],
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Product purchase initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 0,
                'message' => 'Failed to initialize product purchase',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialize Pesapal payment
     */
    protected function initializePesapalPayment(UniversalPayment $payment, $callbackUrl = null)
    {
        try {
            // Generate unique merchant reference
            $merchantReference = 'PRODUCT_' . $payment->id . '_' . time();

            // Default callback URL
            if (!$callbackUrl) {
                $callbackUrl = url('/api/product-purchase/pesapal/callback');
            }

            // Prepare payment data for Pesapal
            $paymentData = [
                'order_id' => $payment->id,
                'merchant_reference' => $merchantReference,
                'amount' => (float) $payment->amount,
                'currency' => \App\Config\PesapalProductionConfig::getCurrency(),
                'description' => $payment->description,
                'customer_name' => $payment->customer_name,
                'customer_email' => $payment->customer_email ?: 'customer@dtehm.com',
                'customer_phone' => $payment->customer_phone,
                'customer_address' => $payment->customer_address ?? '',
                'callback_url' => $callbackUrl
            ];

            Log::info('Pesapal: Initializing product purchase payment', [
                'payment_id' => $payment->id,
                'merchant_reference' => $merchantReference,
                'amount' => $payment->amount
            ]);

            // Use Pesapal API client
            $apiClient = app(\App\Services\PesapalApiClient::class);
            $response = $apiClient->initializePayment($paymentData);

            if (!isset($response['success']) || !$response['success']) {
                throw new \Exception($response['message'] ?? 'Payment initialization failed');
            }

            // Create Pesapal transaction record
            \App\Models\PesapalTransaction::create([
                'order_id' => $payment->id,
                'order_tracking_id' => $response['order_tracking_id'],
                'merchant_reference' => $merchantReference,
                'amount' => (float) $payment->amount,
                'currency' => \App\Config\PesapalProductionConfig::getCurrency(),
                'status' => 'PENDING',
                'redirect_url' => $response['redirect_url'],
                'callback_url' => $callbackUrl,
                'notification_id' => $response['notification_id'] ?? null,
                'description' => $paymentData['description'],
            ]);

            // Update payment with Pesapal data
            $payment->update([
                'pesapal_order_tracking_id' => $response['order_tracking_id'],
                'pesapal_merchant_reference' => $merchantReference,
                'pesapal_redirect_url' => $response['redirect_url'],
                'pesapal_callback_url' => $callbackUrl,
                'status' => 'PROCESSING',
            ]);

            return [
                'code' => 1,
                'data' => [
                    'order_tracking_id' => $response['order_tracking_id'],
                    'redirect_url' => $response['redirect_url'],
                    'merchant_reference' => $merchantReference,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Pesapal initialization failed for product purchase', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'code' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Confirm product purchase after payment (called by Pesapal IPN or manual check)
     * POST /api/product-purchase/confirm
     * 
     * Request: {
     *   "payment_id": 123,
     *   "order_tracking_id": "xxx-xxx-xxx"
     * }
     */
    public function confirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'nullable|exists:universal_payments,id',
            'order_tracking_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find payment by ID or tracking ID
            $payment = null;
            if ($request->payment_id) {
                $payment = UniversalPayment::find($request->payment_id);
            } elseif ($request->order_tracking_id) {
                $payment = UniversalPayment::where('pesapal_order_tracking_id', $request->order_tracking_id)->first();
            }

            if (!$payment) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Payment record not found'
                ], 404);
            }

            // Check if already processed
            if ($payment->items_processed) {
                return response()->json([
                    'code' => 1,
                    'message' => 'Payment already processed',
                    'data' => [
                        'payment' => $payment,
                        'already_processed' => true,
                    ]
                ]);
            }

            // Verify payment status with Pesapal
            $apiClient = app(\App\Services\PesapalApiClient::class);
            $statusResponse = $apiClient->checkPaymentStatus($payment->pesapal_order_tracking_id);

            if (!$statusResponse['success']) {
                Log::warning('Failed to verify payment status', [
                    'payment_id' => $payment->id,
                    'tracking_id' => $payment->pesapal_order_tracking_id,
                ]);
            }

            $pesapalStatus = $statusResponse['data']['payment_status_description'] ?? 'UNKNOWN';
            
            // Update payment status
            $payment->update([
                'pesapal_status_code' => $statusResponse['data']['status_code'] ?? null,
                'pesapal_response' => $statusResponse['data'] ?? [],
                'last_status_check' => now(),
            ]);

            // Check if payment is completed
            if ($pesapalStatus !== 'Completed') {
                return response()->json([
                    'code' => 0,
                    'message' => "Payment not completed. Status: {$pesapalStatus}",
                    'data' => [
                        'payment_status' => $pesapalStatus,
                        'payment' => $payment,
                    ]
                ], 400);
            }

            // Process the product purchase
            $result = $this->processProductPurchase($payment);

            if ($result['success']) {
                return response()->json([
                    'code' => 1,
                    'message' => 'Product purchase confirmed successfully',
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'code' => 0,
                    'message' => $result['message'] ?? 'Failed to process purchase',
                    'error' => $result['error'] ?? null
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Product purchase confirmation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 0,
                'message' => 'Failed to confirm product purchase',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process product purchase (create OrderedItem after payment confirmation)
     */
    protected function processProductPurchase(UniversalPayment $payment)
    {
        try {
            if ($payment->payment_type !== 'product') {
                throw new \Exception('Not a product payment');
            }

            if ($payment->items_processed) {
                return [
                    'code' => 1,
                    'message' => 'Already processed',
                    'data' => ['already_processed' => true]
                ];
            }

            $processedItems = [];

            DB::transaction(function () use ($payment, &$processedItems) {
                // Update payment status first
                $payment->update([
                    'status' => 'COMPLETED',
                    'payment_date' => now(),
                    'confirmed_at' => now(),
                ]);

                // Process each payment item
                foreach ($payment->payment_items as $item) {
                    $product = Product::find($item['id']);
                    
                    if (!$product) {
                        Log::warning('Product not found during processing', [
                            'product_id' => $item['id'],
                            'payment_id' => $payment->id,
                        ]);
                        continue;
                    }

                    // Create OrderedItem (official sale record)
                    $orderedItem = OrderedItem::create([
                        'order' => null, // Direct product purchase (not from cart/order)
                        'product' => $product->id,
                        'qty' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'amount' => $item['unit_price'],
                        'subtotal' => $item['amount'],
                        'sponsor_id' => $item['sponsor_id'],
                        'stockist_id' => $item['stockist_id'],
                        'sponsor_user_id' => $item['sponsor_user_id'],
                        'stockist_user_id' => $item['stockist_user_id'],
                        'item_is_paid' => 'Yes',
                        'item_paid_date' => now(),
                        'item_paid_amount' => $item['amount'],
                        'universal_payment_id' => $payment->id,
                        'has_detehm_seller' => 'Yes',
                        'dtehm_seller_id' => $item['sponsor_id'],
                        'dtehm_user_id' => $item['sponsor_user_id'],
                        'points_earned' => $product->points ?? 0,
                    ]);

                    // Note: Stock management can be added later if needed
                    // For now, products are marked as in_stock='Yes' or 'No'

                    $processedItems[] = [
                        'ordered_item_id' => $orderedItem->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $item['quantity'],
                        'amount' => $item['amount'],
                    ];

                    Log::info('OrderedItem created for product purchase', [
                        'ordered_item_id' => $orderedItem->id,
                        'payment_id' => $payment->id,
                        'product_id' => $product->id,
                    ]);
                }

                // Mark payment as processed
                $payment->update([
                    'items_processed' => true,
                    'items_processed_at' => now(),
                    'processing_notes' => 'Product purchase processed successfully. ' . count($processedItems) . ' item(s) created.',
                ]);
            });

            Log::info('Product purchase processed successfully', [
                'payment_id' => $payment->id,
                'items_count' => count($processedItems),
            ]);

            return [
                'code' => 1,
                'message' => 'Product purchase processed successfully',
                'data' => [
                    'payment' => $payment->fresh(),
                    'ordered_items' => $processedItems,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to process product purchase', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'code' => 0,
                'message' => 'Failed to process product purchase',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Pesapal IPN callback handler
     * POST /api/product-purchase/pesapal/ipn
     */
    public function pesapalIPN(Request $request)
    {
        Log::info('Pesapal IPN received for product purchase', $request->all());

        try {
            $orderTrackingId = $request->input('OrderTrackingId');
            
            if (!$orderTrackingId) {
                return response()->json(['status' => 'error', 'message' => 'Missing OrderTrackingId'], 400);
            }

            // Find payment
            $payment = UniversalPayment::where('pesapal_order_tracking_id', $orderTrackingId)->first();
            
            if (!$payment) {
                Log::warning('Payment not found for IPN', ['tracking_id' => $orderTrackingId]);
                return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
            }

            // Update IPN count
            $payment->update([
                'ipn_count' => ($payment->ipn_count ?? 0) + 1,
                'last_ipn_at' => now(),
            ]);

            // Verify and process payment
            $confirmRequest = new Request(['order_tracking_id' => $orderTrackingId]);
            $this->confirm($confirmRequest);

            return response()->json(['status' => 'success', 'message' => 'IPN processed']);

        } catch (\Exception $e) {
            Log::error('Pesapal IPN processing failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'IPN processing failed'], 500);
        }
    }

    /**
     * Pesapal callback handler (redirect after payment)
     * GET /api/product-purchase/pesapal/callback
     */
    public function pesapalCallback(Request $request)
    {
        $orderTrackingId = $request->input('OrderTrackingId');
        
        Log::info('Pesapal callback received for product purchase', [
            'tracking_id' => $orderTrackingId,
            'query_params' => $request->all(),
        ]);

        // Return simple success page
        return view('pesapal-callback-success', [
            'order_tracking_id' => $orderTrackingId,
            'message' => 'Payment processing... Please wait.',
        ]);
    }

    /**
     * Get user's product purchase history
     * GET /api/product-purchase/history
     */
    public function history(Request $request)
    {
        try {
            $userId = $this->getUserIdFromHeader($request);
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'User authentication required'
                ], 401);
            }

            // Get ALL ordered items where user is involved as:
            // 1. Sponsor (buyer)
            // 2. Stockist (seller)
            // 3. Payment user (created the payment)
            // Only show PAID items
            $purchases = OrderedItem::where(function($q) use ($userId) {
                    // User is sponsor (buyer)
                    $q->where('sponsor_user_id', $userId)
                      // OR user is stockist (seller)
                      ->orWhere('stockist_user_id', $userId)
                      // OR user created the payment
                      ->orWhereHas('payment', function($pq) use ($userId) {
                          $pq->where('user_id', $userId);
                      });
                })
                ->where('item_is_paid', 'Yes') // Only paid items
                ->with(['pro', 'payment'])
                ->orderBy('created_at', 'desc')
                ->get();

            $formatted = $purchases->map(function($item) use ($userId) {
                // Determine user's role in this purchase
                $userRole = 'buyer'; // default
                if ($item->sponsor_user_id == $userId) {
                    $userRole = 'sponsor'; // User bought/sponsored this
                }
                if ($item->stockist_user_id == $userId) {
                    $userRole = $userRole === 'sponsor' ? 'sponsor_and_stockist' : 'stockist';
                }

                return [
                    'id' => $item->id,
                    'order_number' => $item->order,
                    'product' => [
                        'id' => $item->product,
                        'name' => $item->pro->name ?? 'Unknown Product',
                        'image' => $item->pro->feature_photo ?? null,
                    ],
                    'quantity' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'total_amount' => $item->subtotal,
                    'sponsor_id' => $item->sponsor_id,
                    'stockist_id' => $item->stockist_id,
                    'sponsor_user_id' => $item->sponsor_user_id,
                    'stockist_user_id' => $item->stockist_user_id,
                    'user_role' => $userRole, // User's role in this transaction
                    'payment_status' => $item->item_is_paid === 'Yes' ? 'PAID' : 'PENDING',
                    'paid_at' => $item->item_paid_date,
                    'created_at' => $item->created_at,
                    'commission_processed' => $item->commission_is_processed,
                    'points_earned' => $item->points_earned,
                ];
            });

            return response()->json([
                'code' => 1,
                'message' => 'Purchase history retrieved successfully',
                'data' => $formatted,
                'total' => $formatted->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve purchase history', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve purchase history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single purchase details
     * GET /api/product-purchase/{id}
     */
    public function details($id)
    {
        try {
            $orderedItem = OrderedItem::with(['pro', 'payment'])->find($id);
            
            if (!$orderedItem) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Purchase not found'
                ], 404);
            }

            // Get sponsor and stockist user details
            $sponsor = User::find($orderedItem->sponsor_user_id);
            $stockist = User::find($orderedItem->stockist_user_id);

            $data = [
                'id' => $orderedItem->id,
                'order_number' => $orderedItem->order,
                
                // Product details
                'product' => [
                    'id' => $orderedItem->product,
                    'name' => $orderedItem->pro->name ?? 'Unknown Product',
                    'description' => $orderedItem->pro->description ?? '',
                    'image' => $orderedItem->pro->feature_photo ?? null,
                    'price' => $orderedItem->pro->price_1 ?? 0,
                ],
                
                // Purchase details
                'quantity' => $orderedItem->qty,
                'unit_price' => $orderedItem->unit_price,
                'total_amount' => $orderedItem->subtotal,
                
                // Sponsor (Buyer) details
                'sponsor' => [
                    'id' => $orderedItem->sponsor_id,
                    'user_id' => $orderedItem->sponsor_user_id,
                    'name' => $sponsor ? $sponsor->name : 'Unknown',
                    'phone' => $sponsor ? $sponsor->phone_number : '',
                ],
                
                // Stockist (Seller) details
                'stockist' => [
                    'id' => $orderedItem->stockist_id,
                    'user_id' => $orderedItem->stockist_user_id,
                    'name' => $stockist ? $stockist->name : 'Unknown',
                    'phone' => $stockist ? $stockist->phone_number : '',
                ],
                
                // Payment details
                'payment' => $orderedItem->payment ? [
                    'id' => $orderedItem->payment->id,
                    'reference' => $orderedItem->payment->payment_reference,
                    'status' => $orderedItem->payment->status,
                    'payment_method' => $orderedItem->payment->payment_method,
                    'payment_gateway' => $orderedItem->payment->payment_gateway,
                    'paid_by_admin' => $orderedItem->payment->paid_by_admin ?? false,
                    'admin_note' => $orderedItem->payment->admin_payment_note ?? null,
                ] : null,
                
                // Commission details
                'commission' => [
                    'stockist' => $orderedItem->commission_stockist ?? 0,
                    'seller' => $orderedItem->commission_seller ?? 0,
                    'total' => $orderedItem->total_commission_amount ?? 0,
                    'processed' => $orderedItem->commission_is_processed === 'Yes',
                    'processed_date' => $orderedItem->commission_processed_date,
                ],
                
                // Points
                'points_earned' => $orderedItem->points_earned ?? 0,
                
                // Status
                'payment_status' => $orderedItem->item_is_paid === 'Yes' ? 'PAID' : 'PENDING',
                'paid_at' => $orderedItem->item_paid_date,
                'created_at' => $orderedItem->created_at,
                'updated_at' => $orderedItem->updated_at,
            ];

            return response()->json([
                'code' => 1,
                'message' => 'Purchase details retrieved successfully',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve purchase details', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve purchase details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user ID from request header
     */
    protected function getUserIdFromHeader(Request $request)
    {
        $userId = 0;
        
        if ($request->header('User-Id')) {
            $userId = (int) $request->header('User-Id');
        } elseif ($request->header('user_id')) {
            $userId = (int) $request->header('user_id');
        } elseif ($request->input('user_id')) {
            $userId = (int) $request->input('user_id');
        }

        return $userId > 0 ? $userId : null;
    }
}
