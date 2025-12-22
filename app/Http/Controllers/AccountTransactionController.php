<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\User;
use App\Models\Utils;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountTransactionController extends Controller
{
    /**
     * Get all account transactions with comprehensive filters
     * SECURITY: Regular users can only see their own transactions
     * Admins can view all users' transactions
     */
    public function index(Request $request)
    {
        try {
            // Get current authenticated user
            $currentUser = \App\Models\Utils::get_user($request);
            if (!$currentUser) {
                return Utils::error('Authentication required', 401);
            }

            // Log the request for debugging
            \Log::info('AccountTransaction index called', [
                'user_id' => $currentUser->id,
                'user_name' => $currentUser->name,
                'user_type' => $currentUser->user_type,
                'request_user_id' => $request->input('user_id'),
            ]);

            $query = AccountTransaction::with(['user', 'creator', 'relatedDisbursement']);

            // SECURITY ENFORCEMENT: Filter by user based on permissions
            if ($currentUser->user_type === 'Admin') {
                // Admin can filter by any user or see all
                if ($request->filled('user_id')) {
                    $query->where('user_id', $request->user_id);
                }
                // If admin doesn't specify user_id, don't show all - show their own
                else {
                    $query->where('user_id', $currentUser->id);
                }
            } else {
                // Regular users can ONLY see their own transactions
                $query->where('user_id', $currentUser->id);
                
                // Log attempt if someone tries to access other user's data
                if ($request->filled('user_id') && $request->user_id != $currentUser->id) {
                    \Log::warning('Unauthorized transaction access attempt', [
                        'current_user_id' => $currentUser->id,
                        'requested_user_id' => $request->user_id,
                        'ip' => $request->ip(),
                    ]);
                }
            }

            // Filter by source (withdrawal, deposit, commission, disbursement, etc.)
            if ($request->filled('source')) {
                $query->where('source', $request->source);
            }

            // Filter by transaction type (credit/debit)
            if ($request->filled('type')) {
                if ($request->type === 'credit') {
                    $query->where('amount', '>', 0);
                } elseif ($request->type === 'debit') {
                    $query->where('amount', '<', 0);
                }
            }

            // Filter by date range
            if ($request->filled('start_date')) {
                $query->where('transaction_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->where('transaction_date', '<=', $request->end_date);
            }

            // Filter by amount range
            if ($request->filled('min_amount')) {
                $query->where(DB::raw('ABS(amount)'), '>=', abs($request->min_amount));
            }
            if ($request->filled('max_amount')) {
                $query->where(DB::raw('ABS(amount)'), '<=', abs($request->max_amount));
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('phone_number', 'like', "%{$search}%")
                                ->orWhere('dtehm_member_id', 'like', "%{$search}%");
                        });
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'transaction_date');
            $sortOrder = $request->input('sort_order', 'desc');
            
            // Validate sort column to prevent SQL injection
            $allowedSorts = ['transaction_date', 'amount', 'created_at', 'source'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }
            $query->orderBy('id', 'desc'); // Secondary sort

            // Calculate summary statistics (before pagination)
            $summaryQuery = clone $query;
            
            $totalCredit = (clone $summaryQuery)->where('amount', '>', 0)->sum('amount');
            $totalDebit = abs((clone $summaryQuery)->where('amount', '<', 0)->sum('amount'));
            $netBalance = $totalCredit - $totalDebit;
            $transactionCount = (clone $summaryQuery)->count();
            
            // Get user's overall balance if filtered by user
            $userBalance = null;
            $userName = null;
            
            // Determine which user's data we're showing
            $targetUserId = ($currentUser->user_type === 'Admin' && $request->filled('user_id')) 
                ? $request->user_id 
                : $currentUser->id;
            
            if ($targetUserId) {
                $user = User::find($targetUserId);
                if ($user) {
                    $userBalance = $this->calculateUserBalance($targetUserId);
                    $userName = $user->name;
                }
            }

            $summary = [
                'total_credit' => $totalCredit,
                'total_debit' => $totalDebit,
                'net_balance' => $netBalance,
                'transaction_count' => $transactionCount,
                'formatted_total_credit' => 'UGX ' . number_format($totalCredit, 0),
                'formatted_total_debit' => 'UGX ' . number_format($totalDebit, 0),
                'formatted_net_balance' => 'UGX ' . number_format($netBalance, 0),
                'user_balance' => $userBalance,
                'formatted_user_balance' => $userBalance !== null ? 'UGX ' . number_format($userBalance, 0) : null,
                'user_name' => $userName,
            ];

            // Pagination
            $perPage = $request->input('per_page', 20);
            $perPage = min($perPage, 100); // Max 100 per page
            $transactions = $query->paginate($perPage);

            // Format transactions
            $formattedTransactions = $transactions->map(function ($transaction) {
                return $this->formatTransaction($transaction);
            });

            return Utils::success([
                'transactions' => $formattedTransactions,
                'summary' => $summary,
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'from' => $transactions->firstItem(),
                    'to' => $transactions->lastItem(),
                ],
            ], 'Transactions retrieved successfully');
            
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Utils::error('Failed to retrieve transactions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new account transaction (withdrawal/deposit)
     * Only administrators can create manual transactions
     */
    public function store(Request $request)
    {
        // Verify admin authentication
        $currentUser = \App\Models\Utils::get_user($request);
        if (!$currentUser) {
            return Utils::error('Authentication required', 401);
        }
        
        if ($currentUser->user_type !== 'Admin') {
            return Utils::error('Access denied. Only administrators can create account transactions', 403);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|not_in:0|min:0.01',
            'transaction_date' => 'required|date|before_or_equal:today',
            'description' => 'required|string|max:500',
            'source' => 'required|in:withdrawal,deposit',
        ], [
            'user_id.required' => 'Please select a user',
            'user_id.exists' => 'Selected user not found',
            'amount.required' => 'Amount is required',
            'amount.not_in' => 'Amount must be greater than zero',
            'amount.min' => 'Amount must be at least 0.01',
            'transaction_date.required' => 'Transaction date is required',
            'transaction_date.before_or_equal' => 'Transaction date cannot be in the future',
            'description.required' => 'Description is required',
            'source.required' => 'Transaction type is required',
            'source.in' => 'Invalid transaction type',
        ]);

        if ($validator->fails()) {
            return Utils::error($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        
        try {
            // Get target user
            $targetUser = User::find($request->user_id);
            if (!$targetUser) {
                return Utils::error('User not found', 404);
            }

            // Calculate amount based on transaction type
            $amount = abs($request->amount);
            
            if ($request->source === 'withdrawal') {
                // Make amount negative for withdrawal
                $amount = -$amount;
                
                // Check if user has sufficient balance
                $currentBalance = $this->calculateUserBalance($request->user_id);
                
                if ($currentBalance + $amount < 0) {
                    DB::rollBack();
                    return Utils::error(
                        "Insufficient balance. Current balance: UGX " . number_format($currentBalance, 0) . 
                        ". Requested withdrawal: UGX " . number_format(abs($amount), 0),
                        400
                    );
                }
            }

            // Create transaction
            $transaction = AccountTransaction::create([
                'user_id' => $request->user_id,
                'amount' => $amount,
                'transaction_date' => $request->transaction_date,
                'description' => trim($request->description),
                'source' => $request->source,
                'created_by_id' => $currentUser->id,
            ]);

            // Calculate new balance
            $newBalance = $this->calculateUserBalance($request->user_id);

            DB::commit();

            // Load relationships
            $transaction->load(['user', 'creator']);

            // Log successful transaction
            \Log::info('Account transaction created', [
                'transaction_id' => $transaction->id,
                'user' => $targetUser->name,
                'type' => $request->source,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'created_by' => $currentUser->name,
            ]);

            // Format response with additional info
            $response = $this->formatTransaction($transaction);
            $response['new_balance'] = $newBalance;
            $response['formatted_new_balance'] = 'UGX ' . number_format($newBalance, 0);

            return Utils::success(
                $response,
                ucfirst($request->source) . ' of UGX ' . number_format(abs($amount), 0) . ' processed successfully'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create account transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Utils::error('Failed to create transaction: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get a single account transaction with full details
     */
    public function show(Request $request, $id)
    {
        try {
            $transaction = AccountTransaction::with(['user', 'creator', 'relatedDisbursement'])
                ->findOrFail($id);

            // Calculate user's current balance
            $currentBalance = $this->calculateUserBalance($transaction->user_id);
            
            $response = $this->formatTransaction($transaction);
            $response['user_current_balance'] = $currentBalance;
            $response['formatted_user_balance'] = 'UGX ' . number_format($currentBalance, 0);

            return Utils::success($response, 'Transaction details retrieved successfully');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Utils::error('Transaction not found', 404);
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve transaction', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return Utils::error('Failed to retrieve transaction: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete an account transaction (only administrators can delete manual transactions)
     */
    public function destroy(Request $request, $id)
    {
        // Verify admin authentication
        $currentUser = Utils::get_user_from_request($request);
        if (!$currentUser) {
            return Utils::error('Authentication required', 401);
        }
        
        if ($currentUser->user_type !== 'Admin') {
            return Utils::error('Access denied. Only administrators can delete transactions', 403);
        }

        DB::beginTransaction();
        
        try {
            $transaction = AccountTransaction::findOrFail($id);

            // Prevent deletion of system-generated transactions
            $protectedSources = ['disbursement', 'commission', 'referral'];
            if (in_array($transaction->source, $protectedSources)) {
                return Utils::error(
                    'Cannot delete ' . $transaction->source . ' transactions. These are system-generated and protected.',
                    403
                );
            }

            // Store transaction details for logging
            $transactionDetails = [
                'id' => $transaction->id,
                'user' => $transaction->user->name ?? 'N/A',
                'amount' => $transaction->amount,
                'source' => $transaction->source,
                'description' => $transaction->description,
            ];

            $transaction->delete();

            DB::commit();

            // Log deletion
            \Log::warning('Account transaction deleted', array_merge($transactionDetails, [
                'deleted_by' => $currentUser->name,
                'deleted_by_id' => $currentUser->id,
            ]));

            return Utils::success(null, 'Transaction deleted successfully');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return Utils::error('Transaction not found', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete transaction', [
                'id' => $id,
                'error' => $e->getMessage(),
                'user' => $currentUser->name ?? 'Unknown',
            ]);
            return Utils::error('Failed to delete transaction: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Calculate user's current account balance
     * 
     * @param int|null $userId
     * @return float
     */
    private function calculateUserBalance($userId = null)
    {
        if (!$userId) {
            return 0;
        }

        return (float) AccountTransaction::where('user_id', $userId)->sum('amount');
    }

    /**
     * Format transaction for consistent API response
     * 
     * @param AccountTransaction $transaction
     * @return array
     */
    private function formatTransaction($transaction)
    {
        $isCredit = $transaction->amount >= 0;
        $absoluteAmount = abs($transaction->amount);
        
        // Determine if transaction can be deleted
        $protectedSources = ['disbursement', 'commission', 'referral'];
        $canDelete = !in_array($transaction->source, $protectedSources);

        return [
            'id' => $transaction->id,
            'user_id' => $transaction->user_id,
            'user_name' => $transaction->user->name ?? 'N/A',
            'user_phone' => $transaction->user->phone_number ?? 'N/A',
            'user_member_id' => $transaction->user->dtehm_member_id ?? $transaction->user->business_name ?? 'N/A',
            'amount' => $transaction->amount,
            'absolute_amount' => $absoluteAmount,
            'formatted_amount' => ($isCredit ? '+' : '-') . ' UGX ' . number_format($absoluteAmount, 0),
            'amount_color' => $isCredit ? 'success' : 'danger',
            'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
            'formatted_date' => $transaction->transaction_date->format('d M Y'),
            'full_date' => $transaction->transaction_date->format('l, F j, Y'),
            'description' => $transaction->description ?? '',
            'source' => $transaction->source,
            'source_label' => ucfirst(str_replace('_', ' ', $transaction->source)),
            'type' => $isCredit ? 'credit' : 'debit',
            'type_label' => $isCredit ? 'Credit' : 'Debit',
            'related_disbursement_id' => $transaction->related_disbursement_id,
            'created_by' => $transaction->creator->name ?? 'System',
            'created_by_id' => $transaction->created_by_id,
            'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
            'created_at_human' => $transaction->created_at->diffForHumans(),
            'updated_at' => $transaction->updated_at->format('Y-m-d H:i:s'),
            'can_delete' => $canDelete,
            'is_system_generated' => in_array($transaction->source, $protectedSources),
        ];
    }
    
    /**
     * Get user commissions from mobile app
     * GET /api/user/commissions
     * Query params: page, per_page, type (stockist/network/membership/all)
     */
    public function getUserCommissions(Request $request)
    {
        // Try JWT authentication first (Flutter sends Authorization, Tok, and tok headers)
        $user = null;
        $token = null;
        
        // Check all three token headers that Flutter sends
        if ($request->header('Authorization')) {
            $token = $request->header('Authorization');
        } elseif ($request->header('Tok')) {
            $token = $request->header('Tok');
        } elseif ($request->header('tok')) {
            $token = $request->header('tok');
        }

        if ($token) {
            try {
                // Extract token from "Bearer {token}" format
                $token = str_replace('Bearer ', '', $token);
                $user = auth('api')->setToken($token)->user();
            } catch (\Exception $e) {
                \Log::warning('User Commissions - JWT auth failed: ' . $e->getMessage());
            }
        }
        
        // Fallback to User-Id header if JWT fails
        if ($user == null) {
            $administrator_id = Utils::get_user_id($request);
            $user = Administrator::find($administrator_id);
        }
        
        if (!$user) {
            return response()->json([
                'code' => 0,
                'message' => 'User not found'
            ], 404);
        }
 

        $query = AccountTransaction::where('user_id', $user->id)
            ->where('type', 'commission');
        
        // Filter by commission type
        if ($request->has('commission_type') && $request->commission_type != 'all') {
            $type = $request->commission_type;
            
            if ($type == 'stockist') {
                $query->where('description', 'LIKE', '%Stockist%');
            } elseif ($type == 'network') {
                $query->where('description', 'LIKE', '%GN%');
            } elseif ($type == 'membership') {
                $query->where('description', 'LIKE', '%Membership%');
            }
        }
        
        // Date range filter
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }
        
        // Sort by latest
        $query->orderBy('created_at', 'desc');
        
        // Calculate totals
        $totalCommissions = (clone $query)->sum('amount');
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $commissions = $query->paginate($perPage);
        
        // Format response
        $formatted = $commissions->map(function($transaction) {
            // Extract commission level from description
            $level = 'Other';
            if (strpos($transaction->description, 'Stockist') !== false) {
                $level = 'Stockist';
            } elseif (preg_match('/(GN\d+)/', $transaction->description, $matches)) {
                $level = $matches[1];
            } elseif (strpos($transaction->description, 'Membership') !== false) {
                $level = 'Membership Referral';
            }
            
            // Extract order ID if available
            $orderId = null;
            if (preg_match('/Order #(\d+)/', $transaction->description, $matches)) {
                $orderId = $matches[1];
            }
            
            return [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'level' => $level,
                'description' => $transaction->description,
                'order_id' => $orderId,
                'reference_type' => $transaction->reference_type,
                'reference_id' => $transaction->reference_id,
                'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                'status' => $transaction->status,
            ];
        });
        
        return response()->json([
            'code' => 1,
            'data' => [
                'commissions' => $formatted,
                'summary' => [
                    'total_commissions' => $totalCommissions,
                    'current_balance' => $user->balance ?? 0,
                    'currency' => 'UGX',
                ],
                'pagination' => [
                    'total' => $commissions->total(),
                    'per_page' => $commissions->perPage(),
                    'current_page' => $commissions->currentPage(),
                    'last_page' => $commissions->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Share commission with another user
     * Creates two transactions: debit from sender, credit to receiver
     * POST /api/account-transactions/share-commission
     */
    public function shareCommission(Request $request)
    {
        // Get authenticated user
        $sender = \App\Models\Utils::get_user($request);
        if (!$sender) {
            return Utils::error('Authentication required', 401);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id|different:user',
            'amount' => 'required|numeric|min:100|max:10000000',
            'description' => 'nullable|string|max:500',
        ], [
            'receiver_id.required' => 'Please select a recipient',
            'receiver_id.exists' => 'Recipient not found',
            'receiver_id.different' => 'You cannot send commission to yourself',
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a valid number',
            'amount.min' => 'Minimum amount is UGX 100',
            'amount.max' => 'Maximum amount is UGX 10,000,000',
        ]);

        if ($validator->fails()) {
            return Utils::error($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        
        try {
            // Get receiver
            $receiver = User::find($request->receiver_id);
            if (!$receiver) {
                DB::rollBack();
                return Utils::error('Recipient not found', 404);
            }

            // Validate amount
            $amount = abs($request->amount);
            
            // Check sender's balance
            $senderBalance = $this->calculateUserBalance($sender->id);
            
            if ($senderBalance < $amount) {
                DB::rollBack();
                return Utils::error(
                    "Insufficient balance. Your current balance is UGX " . number_format($senderBalance, 0) . 
                    ". You cannot send UGX " . number_format($amount, 0),
                    400
                );
            }

            // Prepare description
            $description = $request->description 
                ? trim($request->description) 
                : "Commission shared with {$receiver->name}";

            // Create debit transaction for sender
            $senderTransaction = AccountTransaction::create([
                'user_id' => $sender->id,
                'amount' => -$amount,  // Negative for debit
                'transaction_date' => now(),
                'description' => $description,
                'source' => 'commission_share',
                'created_by_id' => $sender->id,
            ]);

            // Create credit transaction for receiver
            $receiverTransaction = AccountTransaction::create([
                'user_id' => $receiver->id,
                'amount' => $amount,  // Positive for credit
                'transaction_date' => now(),
                'description' => "Commission received from {$sender->name}",
                'source' => 'commission_share',
                'created_by_id' => $sender->id,
            ]);

            // Calculate new balances
            $newSenderBalance = $this->calculateUserBalance($sender->id);
            $newReceiverBalance = $this->calculateUserBalance($receiver->id);

            DB::commit();

            // Log successful transaction
            \Log::info('Commission shared successfully', [
                'sender_id' => $sender->id,
                'sender_name' => $sender->name,
                'receiver_id' => $receiver->id,
                'receiver_name' => $receiver->name,
                'amount' => $amount,
                'sender_new_balance' => $newSenderBalance,
                'receiver_new_balance' => $newReceiverBalance,
            ]);

            // Only return the sender's transaction (not the receiver's)
            return Utils::success([
                'transaction' => $this->formatTransaction($senderTransaction),
                'new_balance' => $newSenderBalance,
                'formatted_balance' => 'UGX ' . number_format($newSenderBalance, 0),
                'formatted_amount' => 'UGX ' . number_format($amount, 0),
                'receiver_name' => $receiver->name,
            ], "Successfully sent UGX " . number_format($amount, 0) . " to {$receiver->name}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to share commission', [
                'sender_id' => $sender->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Utils::error('Failed to share commission: ' . $e->getMessage(), 500);
        }
    }
}
