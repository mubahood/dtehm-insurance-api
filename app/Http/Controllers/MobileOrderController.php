<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\Product;
use App\Models\Administrator;
use App\Models\AccountTransaction;
use App\Models\User;
use Carbon\Carbon;

class MobileOrderController extends Controller
{
    /**
     * Calculate commission preview before placing order
     * POST /api/orders/calculate-commission
     * 
     * Request: {
     *   "product_id": 1,
     *   "quantity": 1,
     *   "sponsor_id": "DTEHM20250001",
     *   "stockist_id": "DTEHM20250002"
     * }
     */
    public function calculateCommission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'sponsor_id' => 'required|string',
            'stockist_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        $product = Product::find($request->product_id);
        $totalAmount = $product->price * $request->quantity;
        
        // Find sponsor
        $sponsor = Administrator::where('business_name', $request->sponsor_id)
            ->orWhere('dtehm_member_id', $request->sponsor_id)
            ->first();
        
        if (!$sponsor) {
            return response()->json([
                'code' => 0,
                'message' => 'Sponsor not found'
            ], 404);
        }
        
        // Find stockist
        $stockist = Administrator::where('business_name', $request->stockist_id)
            ->orWhere('dtehm_member_id', $request->stockist_id)
            ->first();
        
        if (!$stockist) {
            return response()->json([
                'code' => 0,
                'message' => 'Stockist not found'
            ], 404);
        }
        
        // Build hierarchy from sponsor
        $hierarchy = $this->buildHierarchy($sponsor);
        
        // Calculate commissions
        $commissions = $this->calculateCommissions($totalAmount, $stockist, $hierarchy);
        
        return response()->json([
            'code' => 1,
            'message' => 'Commission calculated successfully',
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $request->quantity,
                    'total' => $totalAmount,
                ],
                'sponsor' => [
                    'id' => $sponsor->id,
                    'name' => $sponsor->name,
                    'member_id' => $sponsor->dtehm_member_id ?? $sponsor->business_name,
                ],
                'stockist' => [
                    'id' => $stockist->id,
                    'name' => $stockist->name,
                    'member_id' => $stockist->dtehm_member_id ?? $stockist->business_name,
                ],
                'commissions' => $commissions['breakdown'],
                'summary' => [
                    'product_price' => $totalAmount,
                    'stockist_commission' => $commissions['stockist_total'],
                    'network_commission' => $commissions['network_total'],
                    'total_commission' => $commissions['total'],
                    'balance' => $commissions['balance'],
                    'commission_percentage' => $commissions['percentage'],
                ]
            ]
        ]);
    }
    
    /**
     * Create order
     * POST /api/orders/create
     * 
     * Request: {
     *   "product_id": 1,
     *   "quantity": 1,
     *   "sponsor_id": "DTEHM20250001",
     *   "stockist_id": "DTEHM20250002",
     *   "payment_method": "pesapal",
     *   "callback_url": "https://..."
     * }
     */
    public function createOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'sponsor_id' => 'required|string',
            'stockist_id' => 'required|string',
            'payment_method' => 'required|in:pesapal,mobile_money,bank',
            'callback_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        // Get user ID from headers (same method as other endpoints)
        $user_id = 0;
        if ($request->header('User-Id')) {
            $user_id = (int) $request->header('User-Id');
        } elseif ($request->header('user_id')) {
            $user_id = (int) $request->header('user_id');
        } elseif ($request->input('user_id')) {
            $user_id = (int) $request->input('user_id');
        }

        if ($user_id < 1) {
            return response()->json([
                'code' => 0,
                'message' => 'User ID is required'
            ], 401);
        }

        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'code' => 0,
                'message' => 'User not found'
            ], 404);
        }
 

        $product = Product::find($request->product_id);
        
        // Check stock
        if (isset($product->stock_quantity) && $product->stock_quantity < $request->quantity) {
            return response()->json([
                'code' => 0,
                'message' => 'Insufficient stock. Available: ' . $product->stock_quantity
            ], 400);
        }
        
        $totalAmount = $product->price * $request->quantity;
        
        // Find sponsor and stockist
        $sponsor = Administrator::where('business_name', $request->sponsor_id)
            ->orWhere('dtehm_member_id', $request->sponsor_id)
            ->first();
        
        $stockist = Administrator::where('business_name', $request->stockist_id)
            ->orWhere('dtehm_member_id', $request->stockist_id)
            ->first();
        
        if (!$sponsor || !$stockist) {
            return response()->json([
                'code' => 0,
                'message' => 'Invalid sponsor or stockist ID'
            ], 400);
        }
        
        // Create Order
        $order = new Order();
        $order->created_by_id = $user->id;
        $order->customer_name = $user->name;
        $order->customer_address = $user->address ?? '';
        $order->customer_phone_number_1 = $user->phone_number_1 ?? $user->phone_number;
        $order->customer_phone_number_2 = $user->phone_number_2 ?? '';
        $order->order_total = $totalAmount;
        $order->payment_method = $request->payment_method;
        $order->status = 'Pending Payment';
        $order->created_at = Carbon::now();
        $order->save();
        
        // Create OrderedItem
        $orderedItem = new OrderedItem();
        $orderedItem->order_id = $order->id;
        $orderedItem->product = $product->id;
        $orderedItem->sponsor_id = $request->sponsor_id;
        $orderedItem->stockist_id = $request->stockist_id;
        $orderedItem->sponsor_user_id = $sponsor->id;
        $orderedItem->stockist_user_id = $stockist->id;
        $orderedItem->amount = $totalAmount;
        $orderedItem->quantity = $request->quantity;
        $orderedItem->created_at = Carbon::now();
        $orderedItem->save();
        
        // Initialize payment if Pesapal
        if ($request->payment_method == 'pesapal') {
            $pesapalController = new PesapalController();
            $pesapalRequest = new Request([
                'amount' => $totalAmount,
                'description' => 'Product Order #' . $order->id . ' - ' . $product->name,
                'callback_url' => $request->callback_url ?? url('/api/orders/payment-callback'),
                'cancellation_url' => $request->callback_url ?? url('/api/orders/payment-cancelled'),
                'billing_address' => [
                    'email_address' => $user->email,
                    'phone_number' => $user->phone_number_1 ?? $user->phone_number,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ]
            ]);
            
            $pesapalResponse = $pesapalController->initializePayment($pesapalRequest);
            
            // Update order with Pesapal tracking ID
            if (isset($pesapalResponse['order_tracking_id'])) {
                $order->pesapal_tracking_id = $pesapalResponse['order_tracking_id'];
                $order->save();
            }
            
            return response()->json([
                'code' => 1,
                'message' => 'Order created successfully. Please complete payment.',
                'data' => [
                    'order_id' => $order->id,
                    'ordered_item_id' => $orderedItem->id,
                    'total_amount' => $totalAmount,
                    'payment_url' => $pesapalResponse['redirect_url'] ?? null,
                    'tracking_id' => $pesapalResponse['order_tracking_id'] ?? null,
                ]
            ]);
        }
        
        return response()->json([
            'code' => 1,
            'message' => 'Order created successfully. Please complete payment.',
            'data' => [
                'order_id' => $order->id,
                'ordered_item_id' => $orderedItem->id,
                'total_amount' => $totalAmount,
            ]
        ]);
    }
    
    /**
     * Confirm order payment and process commissions
     * POST /api/orders/confirm-payment
     * 
     * Request: {
     *   "order_id": 123,
     *   "transaction_reference": "PESAPAL123",
     *   "status": "success"
     * }
     */
    public function confirmPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'transaction_reference' => 'required|string',
            'status' => 'required|in:success,failed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        $order = Order::find($request->order_id);
        $orderedItem = OrderedItem::where('order_id', $order->id)->first();
        
        if ($request->status == 'failed') {
            $order->status = 'Payment Failed';
            $order->save();
            
            return response()->json([
                'code' => 0,
                'message' => 'Payment failed. Please try again.'
            ], 400);
        }
        
        // Update order status
        $order->status = 'Paid';
        $order->payment_reference = $request->transaction_reference;
        $order->paid_at = Carbon::now();
        $order->save();
        
        // Get product
        $product = Product::find($orderedItem->product);
        
        // Update stock
        if (isset($product->stock_quantity)) {
            $product->stock_quantity -= $orderedItem->quantity;
            $product->save();
        }
        
        // Get sponsor and stockist
        $sponsor = Administrator::find($orderedItem->sponsor_user_id);
        $stockist = Administrator::find($orderedItem->stockist_user_id);
        
        // Build hierarchy from sponsor
        $hierarchy = $this->buildHierarchy($sponsor);
        
        // Calculate and create commissions
        $commissions = $this->calculateCommissions($orderedItem->amount, $stockist, $hierarchy);
        $this->createCommissionTransactions($orderedItem, $commissions);
        
        return response()->json([
            'code' => 1,
            'message' => 'Payment confirmed successfully. Commissions have been distributed.',
            'data' => [
                'order_id' => $order->id,
                'status' => $order->status,
                'commissions_created' => count($commissions['breakdown']),
                'total_commission' => $commissions['total'],
            ]
        ]);
    }
    
    /**
     * Get user's orders
     * GET /api/orders/my-orders
     * Query params: page, per_page, status
     */
    public function myOrders(Request $request)
    {
        // Get user ID from headers
        $user_id = 0;
        if ($request->header('User-Id')) {
            $user_id = (int) $request->header('User-Id');
        } elseif ($request->header('user_id')) {
            $user_id = (int) $request->header('user_id');
        } elseif ($request->input('user_id')) {
            $user_id = (int) $request->input('user_id');
        }

        if ($user_id < 1) {
            return response()->json([
                'code' => 0,
                'message' => 'User ID is required'
            ], 401);
        }

        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'code' => 0,
                'message' => 'User not found'
            ], 404);
        }

        $query = Order::where('created_by_id', $user->id);
        
        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
        
        // Sort by latest
        $query->orderBy('created_at', 'desc');
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $orders = $query->paginate($perPage);
        
        // Format response
        $formatted = $orders->map(function($order) {
            $orderedItems = OrderedItem::where('order_id', $order->id)->get();
            
            $items = $orderedItems->map(function($item) {
                $product = Product::find($item->product);
                return [
                    'product_name' => $product->name ?? 'Unknown',
                    'product_image' => $product->feature_photo ?? null,
                    'quantity' => $item->quantity,
                    'amount' => $item->amount,
                ];
            });
            
            return [
                'id' => $order->id,
                'order_date' => $order->created_at,
                'total_amount' => $order->order_total,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'items' => $items,
            ];
        });
        
        return response()->json([
            'code' => 1,
            'data' => [
                'orders' => $formatted,
                'pagination' => [
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                ]
            ]
        ]);
    }
    
    /**
     * Get order details
     * GET /api/orders/detail/{id}
     */
    public function orderDetail(Request $request, $id)
    {
        // Get user ID from headers
        $user_id = 0;
        if ($request->header('User-Id')) {
            $user_id = (int) $request->header('User-Id');
        } elseif ($request->header('user_id')) {
            $user_id = (int) $request->header('user_id');
        } elseif ($request->input('user_id')) {
            $user_id = (int) $request->input('user_id');
        }

        if ($user_id < 1) {
            return response()->json([
                'code' => 0,
                'message' => 'User ID is required'
            ], 401);
        }

        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'code' => 0,
                'message' => 'User not found'
            ], 404);
        }

        $order = Order::find($id);
        
        if (!$order || $order->created_by_id != $user->id) {
            return response()->json([
                'code' => 0,
                'message' => 'Order not found'
            ], 404);
        }
        
        $orderedItems = OrderedItem::where('order_id', $order->id)->get();
        
        $items = $orderedItems->map(function($item) {
            $product = Product::find($item->product);
            $sponsor = Administrator::find($item->sponsor_user_id);
            $stockist = Administrator::find($item->stockist_user_id);
            
            return [
                'id' => $item->id,
                'product' => [
                    'id' => $product->id ?? null,
                    'name' => $product->name ?? 'Unknown',
                    'image' => $product->feature_photo ?? null,
                    'price' => $product->price ?? 0,
                ],
                'quantity' => $item->quantity,
                'amount' => $item->amount,
                'sponsor' => [
                    'name' => $sponsor->name ?? 'N/A',
                    'member_id' => $sponsor->dtehm_member_id ?? $sponsor->business_name ?? 'N/A',
                ],
                'stockist' => [
                    'name' => $stockist->name ?? 'N/A',
                    'member_id' => $stockist->dtehm_member_id ?? $stockist->business_name ?? 'N/A',
                ],
            ];
        });
        
        return response()->json([
            'code' => 1,
            'data' => [
                'id' => $order->id,
                'order_date' => $order->created_at,
                'total_amount' => $order->order_total,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'payment_reference' => $order->payment_reference,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone_number_1,
                'customer_address' => $order->customer_address,
                'items' => $items,
            ]
        ]);
    }
    
    /**
     * Helper: Build hierarchy from member
     */
    private function buildHierarchy($member)
    {
        $hierarchy = ['member' => $member];
        
        $levels = ['parent_1', 'parent_2', 'parent_3', 'parent_4', 'parent_5', 
                   'parent_6', 'parent_7', 'parent_8', 'parent_9', 'parent_10'];
        
        $currentMember = $member;
        foreach ($levels as $level) {
            if (!empty($currentMember->sponsor_id)) {
                $parent = Administrator::where('business_name', $currentMember->sponsor_id)
                    ->orWhere('dtehm_member_id', $currentMember->sponsor_id)
                    ->first();
                
                if ($parent) {
                    $hierarchy[$level] = $parent;
                    $currentMember = $parent;
                } else {
                    break;
                }
            } else {
                break;
            }
        }
        
        return $hierarchy;
    }
    
    /**
     * Helper: Calculate commissions
     */
    private function calculateCommissions($amount, $stockist, $hierarchy)
    {
        $commissionRates = [
            'stockist' => 8,
            'gn1' => 3,
            'gn2' => 2.5,
            'gn3' => 2,
            'gn4' => 1.5,
            'gn5' => 1,
            'gn6' => 0.8,
            'gn7' => 0.6,
            'gn8' => 0.5,
            'gn9' => 0.4,
            'gn10' => 0.2,
        ];
        
        $breakdown = [];
        $stockistTotal = 0;
        $networkTotal = 0;
        
        // Stockist commission
        $stockistCommission = ($amount * $commissionRates['stockist']) / 100;
        $breakdown[] = [
            'level' => 'Stockist',
            'rate' => $commissionRates['stockist'],
            'amount' => $stockistCommission,
            'member' => [
                'id' => $stockist->id,
                'name' => $stockist->name,
                'member_id' => $stockist->dtehm_member_id ?? $stockist->business_name,
            ]
        ];
        $stockistTotal = $stockistCommission;
        
        // Network commissions (Gn1-Gn10)
        $levels = ['parent_1' => 'gn1', 'parent_2' => 'gn2', 'parent_3' => 'gn3', 
                   'parent_4' => 'gn4', 'parent_5' => 'gn5', 'parent_6' => 'gn6',
                   'parent_7' => 'gn7', 'parent_8' => 'gn8', 'parent_9' => 'gn9', 
                   'parent_10' => 'gn10'];
        
        foreach ($levels as $parentKey => $gnLevel) {
            if (isset($hierarchy[$parentKey])) {
                $member = $hierarchy[$parentKey];
                $commission = ($amount * $commissionRates[$gnLevel]) / 100;
                
                $breakdown[] = [
                    'level' => strtoupper($gnLevel),
                    'rate' => $commissionRates[$gnLevel],
                    'amount' => $commission,
                    'member' => [
                        'id' => $member->id,
                        'name' => $member->name,
                        'member_id' => $member->dtehm_member_id ?? $member->business_name,
                    ]
                ];
                
                $networkTotal += $commission;
            }
        }
        
        $total = $stockistTotal + $networkTotal;
        $balance = $amount - $total;
        $percentage = ($total / $amount) * 100;
        
        return [
            'breakdown' => $breakdown,
            'stockist_total' => $stockistTotal,
            'network_total' => $networkTotal,
            'total' => $total,
            'balance' => $balance,
            'percentage' => round($percentage, 2),
        ];
    }
    
    /**
     * Helper: Create commission transactions
     */
    private function createCommissionTransactions($orderedItem, $commissions)
    {
        foreach ($commissions['breakdown'] as $commission) {
            $transaction = new AccountTransaction();
            $transaction->user_id = $commission['member']['id'];
            $transaction->type = 'commission';
            $transaction->amount = $commission['amount'];
            $transaction->description = $commission['level'] . ' Commission from Order #' . $orderedItem->order_id;
            $transaction->reference_type = 'order';
            $transaction->reference_id = $orderedItem->id;
            $transaction->status = 'completed';
            $transaction->created_at = Carbon::now();
            $transaction->save();
            
            // Update member balance
            $member = Administrator::find($commission['member']['id']);
            if ($member) {
                $member->balance = ($member->balance ?? 0) + $commission['amount'];
                $member->save();
            }
        }
    }
}
