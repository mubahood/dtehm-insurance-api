<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MultipleOrder;
use App\Models\Product;
use App\Services\MultipleOrderPesapalService;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MultipleOrderController extends Controller
{
    use ApiResponser;

    protected $pesapalService;

    public function __construct(MultipleOrderPesapalService $pesapalService)
    {
        $this->pesapalService = $pesapalService;
    }

    /**
     * Create a new multiple order (cart checkout)
     * POST /api/multiple-orders/create
     */
    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'nullable|integer',
                'sponsor_id' => 'required|string',
                'stockist_id' => 'required|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.color' => 'nullable|string',
                'items.*.size' => 'nullable|string',
                'delivery_fee' => 'nullable|numeric|min:0',
                'delivery_method' => 'nullable|string|in:delivery,pickup',
                'delivery_address' => 'nullable|string',
                'customer_phone' => 'nullable|string',
                'customer_email' => 'nullable|email',
                'customer_notes' => 'nullable|string',
                'is_paid_by_admin' => 'nullable|string|in:0,1',
                'admin_payment_note' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->error(
                    'Validation failed: ' . $validator->errors()->first(),
                    400
                );
            }

            // Process items and calculate totals
            $items = [];
            $subtotal = 0;

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    return $this->error(
                        'Product not found: ' . $item['product_id'],
                        404
                    );
                }

                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $product->price_1;
                $itemSubtotal = $unitPrice * $quantity;
                $subtotal += $itemSubtotal;

                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'color' => $item['color'] ?? null,
                    'size' => $item['size'] ?? null,
                    'product_image' => $product->feature_photo ?? null,
                    'points' => $product->points ?? 1
                ];
            }

            $deliveryFee = (float) ($request->delivery_fee ?? 0);
            $totalAmount = $subtotal + $deliveryFee;

            // Check if admin bypass (cash payment)
            $isPaidByAdmin = $request->is_paid_by_admin === '1';
            $adminPaymentNote = $request->admin_payment_note;

            // Create MultipleOrder
            $multipleOrder = MultipleOrder::create([
                'user_id' => $request->user_id,
                'sponsor_id' => $request->sponsor_id,
                'stockist_id' => $request->stockist_id,
                'items_json' => json_encode($items),
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount,
                'currency' => 'UGX',
                'delivery_method' => $request->delivery_method ?? 'delivery',
                'delivery_address' => $request->delivery_address,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'customer_notes' => $request->customer_notes,
                'payment_status' => $isPaidByAdmin ? 'COMPLETED' : 'PENDING',
                'conversion_status' => $isPaidByAdmin ? 'PROCESSING' : 'PENDING',
                'status' => 'active',
                'is_paid_by_admin' => $isPaidByAdmin,
                'admin_payment_note' => $adminPaymentNote,
                'paid_at' => $isPaidByAdmin ? now() : null
            ]);

            Log::info("MultipleOrder created successfully", [
                'id' => $multipleOrder->id,
                'item_count' => count($items),
                'total_amount' => $totalAmount,
                'is_paid_by_admin' => $isPaidByAdmin
            ]);

            // If admin marked as paid, convert to sales immediately
            if ($isPaidByAdmin) {
                try {
                    $conversionResult = $this->convertToSales($multipleOrder);
                    
                    if (!$conversionResult['success']) {
                        Log::warning('Admin bypass: Order created but conversion failed', [
                            'order_id' => $multipleOrder->id,
                            'error' => $conversionResult['message']
                        ]);
                    } else {
                        Log::info('Admin bypass: Order converted to sales successfully', [
                            'order_id' => $multipleOrder->id,
                            'sales_count' => count($conversionResult['sales'] ?? [])
                        ]);
                    }

                    return $this->success(
                        [
                            'id' => $multipleOrder->id,
                            'subtotal' => $multipleOrder->subtotal,
                            'delivery_fee' => $multipleOrder->delivery_fee,
                            'total_amount' => $multipleOrder->total_amount,
                            'currency' => $multipleOrder->currency,
                            'payment_status' => $multipleOrder->payment_status,
                            'items' => $items,
                            'admin_bypass' => true,
                            'converted_to_sales' => $conversionResult['success'],
                            'sales' => $conversionResult['sales'] ?? [],
                            'created_at' => $multipleOrder->created_at->toDateTimeString()
                        ],
                        'Multiple order created and processed successfully (Admin Bypass)'
                    );
                } catch (\Exception $e) {
                    Log::error('Admin bypass conversion error', [
                        'order_id' => $multipleOrder->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $this->success(
                [
                    'id' => $multipleOrder->id,
                    'subtotal' => $multipleOrder->subtotal,
                    'delivery_fee' => $multipleOrder->delivery_fee,
                    'total_amount' => $multipleOrder->total_amount,
                    'currency' => $multipleOrder->currency,
                    'payment_status' => $multipleOrder->payment_status,
                    'items' => $items,
                    'admin_bypass' => false,
                    'created_at' => $multipleOrder->created_at->toDateTimeString()
                ],
                'Multiple order created successfully'
            );

        } catch (\Exception $e) {
            Log::error('MultipleOrder creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error(
                'Failed to create order: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Initialize payment for a multiple order
     * POST /api/multiple-orders/{id}/initialize-payment
     */
    public function initializePayment(Request $request, $id)
    {
        try {
            $multipleOrder = MultipleOrder::find($id);

            if (!$multipleOrder) {
                return response()->json([
                    'code' => 0,
                    'status' => 404,
                    'message' => 'Multiple order not found',
                    'data' => null
                ], 404);
            }

            // Check if already paid
            if ($multipleOrder->isPaymentCompleted()) {
                return response()->json([
                    'code' => 0,
                    'status' => 400,
                    'message' => 'This order has already been paid',
                    'data' => null
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'callback_url' => 'nullable|url',
                'notification_id' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'status' => 400,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 400);
            }

            // Get or register IPN URL if notification_id not provided
            $notificationId = $request->notification_id;
            if (!$notificationId) {
                $ipnResult = $this->pesapalService->getOrRegisterIpnUrl();
                $notificationId = $ipnResult['ipn_id'] ?? null;
                
                if (!$notificationId) {
                    throw new \Exception('Failed to register IPN URL');
                }
            }

            // Initialize payment
            $result = $this->pesapalService->initializePayment(
                $multipleOrder,
                $notificationId,
                $request->callback_url
            );

            if ($result['success']) {
                return response()->json([
                    'code' => 1,
                    'status' => 200,
                    'message' => 'Payment initialized successfully',
                    'data' => $result['data']
                ]);
            }

            return response()->json([
                'code' => 0,
                'status' => 500,
                'message' => $result['error'],
                'data' => null
            ], 500);

        } catch (\Exception $e) {
            Log::error('MultipleOrder payment initialization failed', [
                'multiple_order_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'code' => 0,
                'status' => 500,
                'message' => 'Payment initialization failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Check payment status
     * GET /api/multiple-orders/{id}/payment-status
     */
    public function checkPaymentStatus($id)
    {
        try {
            $multipleOrder = MultipleOrder::find($id);

            if (!$multipleOrder) {
                return response()->json([
                    'code' => 0,
                    'status' => 404,
                    'message' => 'Multiple order not found',
                    'data' => null
                ], 404);
            }

            // Check status from Pesapal
            $result = $this->pesapalService->checkPaymentStatus($multipleOrder);

            // Refresh model to get latest data
            $multipleOrder->refresh();

            return response()->json([
                'code' => 1,
                'status' => 200,
                'message' => 'Payment status retrieved successfully',
                'data' => [
                    'multiple_order_id' => $multipleOrder->id,
                    'payment_status' => $multipleOrder->payment_status,
                    'payment_completed_at' => $multipleOrder->payment_completed_at,
                    'pesapal_status_code' => $multipleOrder->pesapal_status_code,
                    'pesapal_payment_method' => $multipleOrder->pesapal_payment_method,
                    'pesapal_confirmation_code' => $multipleOrder->pesapal_confirmation_code,
                    'conversion_status' => $multipleOrder->conversion_status,
                    'converted_at' => $multipleOrder->converted_at,
                    'pesapal_response' => $result['success'] ? $result['status'] : null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('MultipleOrder status check failed', [
                'multiple_order_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'code' => 0,
                'status' => 500,
                'message' => 'Status check failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get multiple order details
     * GET /api/multiple-orders/{id}
     */
    public function show($id)
    {
        try {
            $multipleOrder = MultipleOrder::with(['user', 'sponsor', 'stockist'])->find($id);

            if (!$multipleOrder) {
                return response()->json([
                    'code' => 0,
                    'status' => 404,
                    'message' => 'Multiple order not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'code' => 1,
                'status' => 200,
                'message' => 'Multiple order retrieved successfully',
                'data' => [
                    'multiple_order' => [
                        'id' => $multipleOrder->id,
                        'user_id' => $multipleOrder->user_id,
                        'sponsor_id' => $multipleOrder->sponsor_id,
                        'sponsor_name' => $multipleOrder->sponsor ? $multipleOrder->sponsor->first_name . ' ' . $multipleOrder->sponsor->last_name : null,
                        'stockist_id' => $multipleOrder->stockist_id,
                        'stockist_name' => $multipleOrder->stockist ? $multipleOrder->stockist->business_name : null,
                        'items' => $multipleOrder->getItems(),
                        'subtotal' => $multipleOrder->subtotal,
                        'delivery_fee' => $multipleOrder->delivery_fee,
                        'total_amount' => $multipleOrder->total_amount,
                        'currency' => $multipleOrder->currency,
                        'payment_status' => $multipleOrder->payment_status,
                        'payment_completed_at' => $multipleOrder->payment_completed_at,
                        'pesapal_redirect_url' => $multipleOrder->pesapal_redirect_url,
                        'pesapal_order_tracking_id' => $multipleOrder->pesapal_order_tracking_id,
                        'conversion_status' => $multipleOrder->conversion_status,
                        'converted_at' => $multipleOrder->converted_at,
                        'delivery_method' => $multipleOrder->delivery_method,
                        'delivery_address' => $multipleOrder->delivery_address,
                        'customer_phone' => $multipleOrder->customer_phone,
                        'customer_email' => $multipleOrder->customer_email,
                        'customer_notes' => $multipleOrder->customer_notes,
                        'status' => $multipleOrder->status,
                        'created_at' => $multipleOrder->created_at->toDateTimeString(),
                        'updated_at' => $multipleOrder->updated_at->toDateTimeString()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('MultipleOrder retrieval failed', [
                'multiple_order_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'code' => 0,
                'status' => 500,
                'message' => 'Failed to retrieve order: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get user's multiple orders
     * GET /api/multiple-orders/user/{userId}
     */
    public function userOrders($userId)
    {
        try {
            $orders = MultipleOrder::where('user_id', $userId)
                ->orWhere('sponsor_user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedOrders = $orders->map(function($order) {
                return [
                    'id' => $order->id,
                    'items_count' => count($order->getItems()),
                    'total_amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'payment_status' => $order->payment_status,
                    'conversion_status' => $order->conversion_status,
                    'created_at' => $order->created_at->toDateTimeString()
                ];
            });

            return response()->json([
                'code' => 1,
                'status' => 200,
                'message' => 'User orders retrieved successfully',
                'data' => [
                    'orders' => $formattedOrders,
                    'total_count' => $orders->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('User orders retrieval failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'code' => 0,
                'status' => 500,
                'message' => 'Failed to retrieve orders: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Manually trigger conversion to OrderedItems
     * POST /api/multiple-orders/{id}/convert
     */
    public function convertToOrderedItems($id)
    {
        try {
            $multipleOrder = MultipleOrder::find($id);

            if (!$multipleOrder) {
                return response()->json([
                    'code' => 0,
                    'status' => 404,
                    'message' => 'Multiple order not found',
                    'data' => null
                ], 404);
            }

            $result = $multipleOrder->convertToOrderedItems();

            if ($result['success']) {
                return response()->json([
                    'code' => 1,
                    'status' => 200,
                    'message' => $result['message'],
                    'data' => [
                        'ordered_items' => $result['ordered_items'],
                        'errors' => $result['errors'] ?? []
                    ]
                ]);
            }

            return response()->json([
                'code' => 0,
                'status' => 400,
                'message' => $result['message'],
                'data' => null
            ], 400);

        } catch (\Exception $e) {
            Log::error('MultipleOrder conversion failed', [
                'multiple_order_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'code' => 0,
                'status' => 500,
                'message' => 'Conversion failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Internal helper to convert MultipleOrder to sales (OrderedItems)
     * Used for admin bypass (cash payment)
     */
    protected function convertToSales(MultipleOrder $multipleOrder)
    {
        try {
            $result = $multipleOrder->convertToOrderedItems();
            
            return [
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Conversion completed',
                'sales' => $result['ordered_items'] ?? [],
                'errors' => $result['errors'] ?? []
            ];
        } catch (\Exception $e) {
            Log::error('convertToSales error', [
                'order_id' => $multipleOrder->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Conversion failed: ' . $e->getMessage(),
                'sales' => [],
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Cancel a multiple order
     * POST /api/multiple-orders/{id}/cancel
     */
    public function cancel($id)
    {
        try {
            $multipleOrder = MultipleOrder::find($id);

            if (!$multipleOrder) {
                return response()->json([
                    'code' => 0,
                    'status' => 404,
                    'message' => 'Multiple order not found',
                    'data' => null
                ], 404);
            }

            if ($multipleOrder->isPaymentCompleted()) {
                return response()->json([
                    'code' => 0,
                    'status' => 400,
                    'message' => 'Cannot cancel a paid order',
                    'data' => null
                ], 400);
            }

            $multipleOrder->update([
                'status' => 'cancelled',
                'payment_status' => 'CANCELLED'
            ]);

            Log::info("MultipleOrder #{$id} cancelled");

            return response()->json([
                'code' => 1,
                'status' => 200,
                'message' => 'Order cancelled successfully',
                'data' => null
            ]);

        } catch (\Exception $e) {
            Log::error('MultipleOrder cancellation failed', [
                'multiple_order_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'code' => 0,
                'status' => 500,
                'message' => 'Cancellation failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
