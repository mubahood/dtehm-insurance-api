<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MultipleOrderPesapalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MultipleOrderPesapalController extends Controller
{
    protected $pesapalService;

    public function __construct(MultipleOrderPesapalService $pesapalService)
    {
        $this->pesapalService = $pesapalService;
    }

    /**
     * Handle Pesapal IPN callback for MultipleOrder
     * POST/GET /api/pesapal/multiple-order-ipn
     */
    public function ipnCallback(Request $request)
    {
        try {
            Log::info('MultipleOrder Pesapal IPN: Callback received', $request->all());

            $orderTrackingId = $request->get('OrderTrackingId');
            $merchantReference = $request->get('OrderMerchantReference');

            if (!$orderTrackingId) {
                Log::warning('MultipleOrder Pesapal IPN: Missing OrderTrackingId');
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid callback parameters'
                ], 400);
            }

            // Process IPN callback
            $result = $this->pesapalService->processIpnCallback($orderTrackingId, $merchantReference);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'order_tracking_id' => $orderTrackingId,
                        'payment_status' => $result['payment_status'] ?? null
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 500);

        } catch (\Exception $e) {
            Log::error('MultipleOrder Pesapal IPN: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'IPN processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle payment callback/redirect from Pesapal
     * GET /api/pesapal/multiple-order-callback
     */
    public function paymentCallback(Request $request)
    {
        try {
            Log::info('MultipleOrder Pesapal: Payment callback received', $request->all());

            $orderTrackingId = $request->get('OrderTrackingId');
            $merchantReference = $request->get('OrderMerchantReference');

            if (!$orderTrackingId) {
                Log::warning('MultipleOrder Pesapal: Missing OrderTrackingId in callback');
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid callback parameters'
                    ], 400);
                }
                
                // Redirect to frontend error page
                return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/payment/error?message=Invalid+callback');
            }

            // Process callback (same as IPN)
            $result = $this->pesapalService->processIpnCallback($orderTrackingId, $merchantReference);

            $multipleOrder = $result['multiple_order'] ?? null;

            // Prepare response data
            $responseData = [
                'order_tracking_id' => $orderTrackingId,
                'merchant_reference' => $merchantReference,
                'payment_status' => $result['payment_status'] ?? 'UNKNOWN',
                'multiple_order_id' => $multipleOrder ? $multipleOrder->id : null,
                'success' => $result['success']
            ];

            // For API requests, return JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'data' => $responseData
                ]);
            }

            // For web requests, redirect to frontend with status
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $redirectUrl = $frontendUrl . '/payment/' . ($result['success'] ? 'success' : 'failed');
            $redirectUrl .= '?order_id=' . ($multipleOrder ? $multipleOrder->id : '');
            $redirectUrl .= '&tracking_id=' . urlencode($orderTrackingId);
            $redirectUrl .= '&status=' . urlencode($result['payment_status'] ?? 'UNKNOWN');

            return redirect($redirectUrl);

        } catch (\Exception $e) {
            Log::error('MultipleOrder Pesapal: Callback exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Callback processing failed: ' . $e->getMessage()
                ], 500);
            }

            // Redirect to error page
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect($frontendUrl . '/payment/error?message=' . urlencode($e->getMessage()));
        }
    }
}
