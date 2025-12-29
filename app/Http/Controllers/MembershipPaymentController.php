<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Administrator;
use App\Models\DtehmMembership;
use App\Models\MembershipPayment;
use App\Models\AccountTransaction;
use Carbon\Carbon;

class MembershipPaymentController extends Controller
{
    /**
     * Initiate membership payment
     * POST /api/membership/initiate-payment
     * 
     * Request: {
     *   "user_id": 123,
     *   "payment_method": "pesapal",
     *   "callback_url": "https://..."
     * }
     */
    public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:admin_users,id',
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

        $user = Administrator::find($request->user_id);
        
        // Calculate payment amount
        $amount = 0;
        $types = [];
        
        if ($user->is_dtehm_member == 'Yes') {
            $amount += 76000;
            $types[] = 'DTEHM';
        }
        
        if ($user->is_dip_member == 'Yes') {
            $amount += 20000;
            $types[] = 'DIP';
        }
        
        if ($amount == 0) {
            return response()->json([
                'code' => 0,
                'message' => 'No membership payment required for this user.'
            ], 400);
        }
        
        // Create membership payment record
        $payment = new MembershipPayment();
        $payment->user_id = $user->id;
        $payment->amount = $amount;
        $payment->payment_method = $request->payment_method;
        $payment->status = 'pending';
        $payment->membership_types = implode(',', $types);
        $payment->created_at = Carbon::now();
        $payment->save();
        
        // For Pesapal integration
        if ($request->payment_method == 'pesapal') {
            // Initialize Pesapal payment
            $pesapalController = new PesapalController();
            $pesapalRequest = new Request([
                'amount' => $amount,
                'description' => 'DTEHM Membership Payment - ' . implode(' & ', $types),
                'callback_url' => $request->callback_url ?? url('/api/membership/payment-callback'),
                'cancellation_url' => $request->callback_url ?? url('/api/membership/payment-cancelled'),
                'billing_address' => [
                    'email_address' => $user->email,
                    'phone_number' => $user->phone_number_1 ?? $user->phone_number,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ]
            ]);
            
            $pesapalResponse = $pesapalController->initializePayment($pesapalRequest);
            
            // Update payment with Pesapal tracking ID
            if (isset($pesapalResponse['order_tracking_id'])) {
                $payment->pesapal_tracking_id = $pesapalResponse['order_tracking_id'];
                $payment->save();
            }
            
            return response()->json([
                'code' => 1,
                'message' => 'Payment initialized successfully',
                'data' => [
                    'payment_id' => $payment->id,
                    'amount' => $amount,
                    'membership_types' => $types,
                    'payment_url' => $pesapalResponse['redirect_url'] ?? null,
                    'tracking_id' => $pesapalResponse['order_tracking_id'] ?? null,
                ]
            ]);
        }
        
        // For other payment methods
        return response()->json([
            'code' => 1,
            'message' => 'Payment record created. Please complete payment.',
            'data' => [
                'payment_id' => $payment->id,
                'amount' => $amount,
                'membership_types' => $types,
            ]
        ]);
    }
    
    /**
     * Confirm membership payment and activate user
     * POST /api/membership/confirm-payment
     * 
     * Request: {
     *   "payment_id": 123,
     *   "transaction_reference": "PESAPAL123",
     *   "status": "success"
     * }
     */
    public function confirmPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|exists:membership_payments,id',
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

        $payment = MembershipPayment::find($request->payment_id);
        $user = Administrator::find($payment->user_id);
        
        if ($request->status == 'failed') {
            $payment->status = 'failed';
            $payment->transaction_reference = $request->transaction_reference;
            $payment->save();
            
            return response()->json([
                'code' => 0,
                'message' => 'Payment failed. Please try again.'
            ], 400);
        }
        
        // Update payment status
        $payment->status = 'completed';
        $payment->transaction_reference = $request->transaction_reference;
        $payment->paid_at = Carbon::now();
        $payment->save();
        
        // Generate DTEHM Member ID if needed
        if ($user->is_dtehm_member == 'Yes' && empty($user->dtehm_member_id)) {
            $currentYear = date('Y'); // Get current year dynamically
            $latestMember = Administrator::where('dtehm_member_id', 'LIKE', 'DTEHM' . $currentYear . '%')
                ->orderBy('dtehm_member_id', 'desc')
                ->first();
            
            if ($latestMember) {
                $lastNumber = (int) substr($latestMember->dtehm_member_id, -4);
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }
            
            $user->dtehm_member_id = 'DTEHM' . $currentYear . $newNumber;
        }
        
        // Generate DIP Member ID if needed (business_name)
        if ($user->is_dip_member == 'Yes' && empty($user->business_name)) {
            $latestDipMember = Administrator::where('business_name', 'LIKE', 'DIP%')
                ->orderBy('business_name', 'desc')
                ->first();
            
            if ($latestDipMember) {
                $lastNumber = (int) substr($latestDipMember->business_name, 3);
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }
            
            $user->business_name = 'DIP' . $newNumber;
        }
        
        // Activate user
        $user->status = 'Active';
        $user->membership_payment_status = 'Paid';
        $user->save();
        
        // Create DTEHM membership record
        if ($user->is_dtehm_member == 'Yes') {
            $membership = new DtehmMembership();
            $membership->user_id = $user->id;
            $membership->dtehm_member_id = $user->dtehm_member_id;
            $membership->membership_type = 'Standard';
            $membership->status = 'Active';
            $membership->joined_at = Carbon::now();
            $membership->save();
        }
        
        // Create sponsor commission (10,000 UGX for DTEHM referral)
        if ($user->is_dtehm_member == 'Yes' && !empty($user->sponsor_id)) {
            $sponsor = Administrator::where('business_name', $user->sponsor_id)
                ->orWhere('dtehm_member_id', $user->sponsor_id)
                ->first();
            
            if ($sponsor) {
                $transaction = new AccountTransaction();
                $transaction->user_id = $sponsor->id;
                $transaction->type = 'commission';
                $transaction->amount = 10000;
                $transaction->description = 'DTEHM Membership Referral Commission for ' . $user->name;
                $transaction->reference_type = 'membership';
                $transaction->reference_id = $payment->id;
                $transaction->status = 'completed';
                $transaction->created_at = Carbon::now();
                $transaction->save();
                
                // Update sponsor balance
                $sponsor->balance = ($sponsor->balance ?? 0) + 10000;
                $sponsor->save();
            }
        }
        
        return response()->json([
            'code' => 1,
            'message' => 'Payment confirmed successfully. Your account is now active.',
            'data' => [
                'user' => $user,
                'dtehm_member_id' => $user->dtehm_member_id,
                'dip_member_id' => $user->business_name,
                'membership_status' => 'Active',
                'sponsor_commission_created' => !empty($user->sponsor_id),
            ]
        ]);
    }
    
    /**
     * Check payment status
     * GET /api/membership/payment-status/{payment_id}
     */
    public function checkPaymentStatus($payment_id)
    {
        $payment = MembershipPayment::find($payment_id);
        
        if (!$payment) {
            return response()->json([
                'code' => 0,
                'message' => 'Payment not found'
            ], 404);
        }
        
        return response()->json([
            'code' => 1,
            'data' => [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'membership_types' => explode(',', $payment->membership_types),
                'transaction_reference' => $payment->transaction_reference,
                'created_at' => $payment->created_at,
                'paid_at' => $payment->paid_at,
            ]
        ]);
    }
}
