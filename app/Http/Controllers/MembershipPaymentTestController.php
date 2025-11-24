<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UniversalPayment;
use App\Models\SystemConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class MembershipPaymentTestController extends Controller
{
    /**
     * Test membership payment scenarios
     * POST /api/test-membership-payment
     * 
     * Scenarios:
     * 1. Single DTEHM payment
     * 2. Single DIP payment
     * 3. Both DTEHM + DIP payment
     */
    public function testMembershipPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'scenario' => 'required|in:dtehm_only,dip_only,both',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::find($request->user_id);
            $config = SystemConfiguration::getInstance();
            
            // Get dynamic fees
            $dtehmFee = (float) ($config->dtehm_membership_fee ?? 76000);
            $dipFee = (float) ($config->dip_membership_fee ?? 20000);
            $currency = $config->currency ?? 'UGX';

            // Determine payment amount and type based on scenario
            $scenario = $request->scenario;
            $amount = 0;
            $isDtehmMember = false;
            $isDipMember = false;
            $description = '';

            switch ($scenario) {
                case 'dtehm_only':
                    $amount = $dtehmFee;
                    $isDtehmMember = true;
                    $description = 'DTEHM Membership Payment';
                    break;
                    
                case 'dip_only':
                    $amount = $dipFee;
                    $isDipMember = true;
                    $description = 'DIP Membership Payment';
                    break;
                    
                case 'both':
                    $amount = $dtehmFee + $dipFee;
                    $isDtehmMember = true;
                    $isDipMember = true;
                    $description = 'DTEHM + DIP Membership Payment';
                    break;
            }

            Log::info('🧪 Testing membership payment', [
                'scenario' => $scenario,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'amount' => $amount,
                'currency' => $currency,
                'is_dtehm_member' => $isDtehmMember,
                'is_dip_member' => $isDipMember,
            ]);

            // Create UniversalPayment record
            $paymentData = [
                'payment_type' => 'MEMBERSHIP',
                'payment_category' => 'membership_fee',
                'user_id' => $user->id,
                'customer_name' => $user->name,
                'customer_phone' => $user->phone ?? $user->phone_number,
                'customer_email' => $user->email,
                'payment_items' => [
                    [
                        'type' => 'membership',
                        'id' => $user->id,
                        'amount' => $amount,
                        'description' => $description,
                        'metadata' => [
                            'is_dtehm_member' => $isDtehmMember ? 'Yes' : 'No',
                            'is_dip_member' => $isDipMember ? 'Yes' : 'No',
                            'scenario' => $scenario,
                        ],
                    ],
                ],
                'description' => $description,
                'payment_gateway' => 'test',
                'payment_method' => 'test',
                'currency' => $currency,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_by' => $user->id,
            ];

            $payment = UniversalPayment::createPayment($paymentData);

            Log::info('✅ UniversalPayment created', [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'amount' => $payment->amount,
            ]);

            // Simulate successful payment (mark as COMPLETED)
            $payment->update([
                'status' => 'COMPLETED',
                'payment_status_code' => '1',
                'status_message' => 'Payment completed successfully (TEST)',
                'payment_date' => now(),
                'confirmed_at' => now(),
            ]);

            Log::info('💳 Payment marked as COMPLETED', [
                'payment_id' => $payment->id,
            ]);

            // Process payment items (this will trigger membership creation)
            $processingResult = $payment->processPaymentItems();

            Log::info('🎉 Payment items processed', [
                'result' => $processingResult,
            ]);

            // Refresh user to get updated membership status
            $user->refresh();

            // Get created membership record(s)
            $membershipRecords = \App\Models\DtehmMembership::where('user_id', $user->id)
                ->where('universal_payment_id', $payment->id)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Membership payment test completed successfully',
                'data' => [
                    'scenario' => $scenario,
                    'payment' => [
                        'id' => $payment->id,
                        'reference' => $payment->payment_reference,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status,
                        'items_processed' => $payment->items_processed,
                    ],
                    'fees_used' => [
                        'dtehm_fee' => $dtehmFee,
                        'dip_fee' => $dipFee,
                        'currency' => $currency,
                    ],
                    'user_after' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'is_dtehm_member' => $user->is_dtehm_member,
                        'is_dip_member' => $user->is_dip_member,
                        'dtehm_member_id' => $user->dtehm_member_id,
                        'dtehm_membership_paid_at' => $user->dtehm_membership_paid_at,
                        'dtehm_membership_paid_date' => $user->dtehm_membership_paid_date,
                        'dtehm_membership_is_paid' => $user->dtehm_membership_is_paid,
                    ],
                    'membership_records' => $membershipRecords->map(function($m) {
                        return [
                            'id' => $m->id,
                            'membership_type' => $m->membership_type,
                            'amount' => $m->amount,
                            'status' => $m->status,
                            'payment_date' => $m->payment_date,
                            'notes' => $m->notes,
                        ];
                    }),
                    'processing_result' => $processingResult,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Membership payment test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Get membership payment summary for a user
     * GET /api/membership-summary/{user_id}
     */
    public function getMembershipSummary($userId)
    {
        try {
            $user = User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $config = SystemConfiguration::getInstance();
            
            // Get all membership payments for this user
            $memberships = \App\Models\DtehmMembership::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone ?? $user->phone_number,
                        'email' => $user->email,
                        'is_dtehm_member' => $user->is_dtehm_member,
                        'is_dip_member' => $user->is_dip_member,
                        'dtehm_member_id' => $user->dtehm_member_id,
                        'dip_member_id' => $user->dip_member_id,
                        'dtehm_membership_paid_at' => $user->dtehm_membership_paid_at,
                        'dip_membership_paid_at' => $user->dip_membership_paid_at,
                    ],
                    'current_fees' => [
                        'dtehm_fee' => $config->dtehm_membership_fee ?? 76000,
                        'dip_fee' => $config->dip_membership_fee ?? 20000,
                        'both_fee' => ($config->dtehm_membership_fee ?? 76000) + ($config->dip_membership_fee ?? 20000),
                        'currency' => $config->currency ?? 'UGX',
                    ],
                    'memberships' => $memberships->map(function($m) {
                        return [
                            'id' => $m->id,
                            'payment_reference' => $m->payment_reference,
                            'amount_paid' => $m->amount_paid,
                            'currency' => $m->currency,
                            'membership_types' => $m->membership_types,
                            'is_dtehm_member' => $m->is_dtehm_member,
                            'is_dip_member' => $m->is_dip_member,
                            'payment_status' => $m->payment_status,
                            'payment_date' => $m->payment_date,
                            'confirmed_at' => $m->confirmed_at,
                        ];
                    }),
                    'membership_count' => $memberships->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get membership summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
