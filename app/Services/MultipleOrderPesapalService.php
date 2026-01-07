<?php

namespace App\Services;

use App\Models\MultipleOrder;
use App\Models\PesapalIpnLog;
use App\Services\PesapalApiClient;
use Illuminate\Support\Facades\Log;

/**
 * Service class for handling Pesapal payments for MultipleOrder
 * Reuses existing PesapalApiClient for API communication
 */
class MultipleOrderPesapalService
{
    private $apiClient;

    public function __construct()
    {
        $this->apiClient = new PesapalApiClient();
    }

    /**
     * Initialize payment for a MultipleOrder
     * 
     * @param MultipleOrder $multipleOrder
     * @param string|null $notificationId Pesapal IPN notification ID
     * @param string|null $callbackUrl Custom callback URL
     * @return array ['success' => bool, 'data' => array, 'error' => string|null]
     */
    public function initializePayment(MultipleOrder $multipleOrder, $notificationId = null, $callbackUrl = null)
    {
        try {
            // Validate order
            if ($multipleOrder->isPaymentCompleted()) {
                return [
                    'success' => false,
                    'error' => 'This order has already been paid',
                    'data' => null
                ];
            }

            if ($multipleOrder->total_amount <= 0) {
                return [
                    'success' => false,
                    'error' => 'Invalid order amount',
                    'data' => null
                ];
            }

            // Generate unique merchant reference
            $merchantReference = $multipleOrder->generateMerchantReference();

            // Validate amount for current environment
            $amount = (float) $multipleOrder->total_amount;
            \App\Config\PesapalProductionConfig::validateTransactionAmount($amount);

            // Get user information
            $user = $multipleOrder->user ?? $multipleOrder->sponsor;
            $userName = $user ? ($user->first_name . ' ' . $user->last_name) : 'Customer';
            $userEmail = $multipleOrder->customer_email ?? ($user->email ?? 'customer@dtehm.com');
            $userPhone = $multipleOrder->customer_phone ?? ($user->phone_number ?? '');

            // Prepare order data for Pesapal API
            $orderData = [
                'order_id' => $multipleOrder->id, // Pass integer ID for logging
                'merchant_reference' => $merchantReference,
                'amount' => $amount,
                'currency' => $multipleOrder->currency,
                'description' => 'Multiple Order #' . $multipleOrder->id . ' - ' . count($multipleOrder->getItems()) . ' items',
                'customer_name' => $userName,
                'customer_email' => $userEmail,
                'customer_phone' => $userPhone,
                'customer_address' => $multipleOrder->delivery_address ?? '',
                'callback_url' => $callbackUrl ?: env('PESAPAL_CALLBACK_URL')
            ];

            Log::info("MultipleOrder #{$multipleOrder->id}: Initializing Pesapal payment", [
                'merchant_reference' => $merchantReference,
                'amount' => $amount,
                'currency' => $multipleOrder->currency
            ]);

            // Initialize payment through Pesapal API
            $response = $this->apiClient->initializePayment($orderData);

            if ($response['success']) {
                // Update MultipleOrder with Pesapal information
                $multipleOrder->update([
                    'pesapal_order_tracking_id' => $response['order_tracking_id'],
                    'pesapal_merchant_reference' => $merchantReference,
                    'pesapal_redirect_url' => $response['redirect_url'],
                    'pesapal_callback_url' => $orderData['callback_url'],
                    'pesapal_notification_id' => $response['notification_id'] ?? $notificationId,
                    'pesapal_status' => 'PENDING',
                    'pesapal_response' => json_encode($response),
                    'payment_status' => 'PROCESSING',
                    'pesapal_last_check' => now()
                ]);

                Log::info("MultipleOrder #{$multipleOrder->id}: Payment initialized successfully", [
                    'tracking_id' => $response['order_tracking_id'],
                    'redirect_url' => $response['redirect_url']
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'order_tracking_id' => $response['order_tracking_id'],
                        'merchant_reference' => $merchantReference,
                        'redirect_url' => $response['redirect_url'],
                        'multiple_order_id' => $multipleOrder->id
                    ],
                    'error' => null
                ];
            }

            throw new \Exception($response['error'] ?? 'Payment initialization failed');

        } catch (\Exception $e) {
            Log::error("MultipleOrder #{$multipleOrder->id}: Payment initialization failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Check payment status from Pesapal
     * 
     * @param MultipleOrder $multipleOrder
     * @return array ['success' => bool, 'status' => array, 'error' => string|null]
     */
    public function checkPaymentStatus(MultipleOrder $multipleOrder)
    {
        try {
            if (empty($multipleOrder->pesapal_order_tracking_id)) {
                return [
                    'success' => false,
                    'error' => 'No Pesapal tracking ID found for this order',
                    'status' => null
                ];
            }

            Log::info("MultipleOrder #{$multipleOrder->id}: Checking payment status", [
                'tracking_id' => $multipleOrder->pesapal_order_tracking_id
            ]);

            // Get status from Pesapal API
            $response = $this->apiClient->checkPaymentStatus($multipleOrder->pesapal_order_tracking_id);

            if ($response['success']) {
                $statusData = $response['data'];

                // Update order with latest status
                $this->updatePaymentStatus($multipleOrder, $statusData);

                return [
                    'success' => true,
                    'status' => $statusData,
                    'error' => null
                ];
            }

            throw new \Exception($response['error'] ?? 'Status check failed');

        } catch (\Exception $e) {
            Log::error("MultipleOrder #{$multipleOrder->id}: Status check failed", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => null
            ];
        }
    }

    /**
     * Update payment status based on Pesapal response
     * 
     * @param MultipleOrder $multipleOrder
     * @param array $statusData
     * @return void
     */
    public function updatePaymentStatus(MultipleOrder $multipleOrder, array $statusData)
    {
        try {
            // Map Pesapal status code to our payment status
            $statusCode = $statusData['status_code'] ?? 0;
            $paymentStatus = $this->mapPesapalStatusToPaymentStatus($statusCode);

            $updateData = [
                'pesapal_status' => $paymentStatus,
                'pesapal_status_code' => $statusCode,
                'pesapal_payment_method' => $statusData['payment_method'] ?? null,
                'pesapal_confirmation_code' => $statusData['confirmation_code'] ?? null,
                'pesapal_payment_account' => $statusData['payment_account'] ?? null,
                'payment_status' => $paymentStatus,
                'pesapal_last_check' => now()
            ];

            // If payment is completed, set completion timestamp
            if ($paymentStatus === 'COMPLETED' && !$multipleOrder->payment_completed_at) {
                $updateData['payment_completed_at'] = now();

                Log::info("MultipleOrder #{$multipleOrder->id}: Payment COMPLETED", [
                    'amount' => $multipleOrder->total_amount,
                    'confirmation_code' => $statusData['confirmation_code'] ?? null
                ]);
            }

            $multipleOrder->update($updateData);

            // If payment is completed, automatically convert to OrderedItems
            if ($paymentStatus === 'COMPLETED' && $multipleOrder->isConversionPending()) {
                Log::info("MultipleOrder #{$multipleOrder->id}: Triggering automatic conversion to OrderedItems");
                $conversionResult = $multipleOrder->convertToOrderedItems();
                
                if ($conversionResult['success']) {
                    Log::info("MultipleOrder #{$multipleOrder->id}: Automatic conversion successful", [
                        'ordered_items_created' => count($conversionResult['ordered_items'] ?? [])
                    ]);
                } else {
                    Log::error("MultipleOrder #{$multipleOrder->id}: Automatic conversion failed", [
                        'error' => $conversionResult['message']
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("MultipleOrder #{$multipleOrder->id}: Failed to update payment status", [
                'error' => $e->getMessage(),
                'status_data' => $statusData
            ]);

            throw $e;
        }
    }

    /**
     * Process IPN callback for MultipleOrder
     * 
     * @param string $orderTrackingId
     * @param string|null $merchantReference
     * @return array ['success' => bool, 'message' => string]
     */
    public function processIpnCallback($orderTrackingId, $merchantReference = null)
    {
        try {
            Log::info('MultipleOrder Pesapal IPN: Processing callback', [
                'order_tracking_id' => $orderTrackingId,
                'merchant_reference' => $merchantReference
            ]);

            // Create IPN log entry
            $ipnLog = PesapalIpnLog::create([
                'order_tracking_id' => $orderTrackingId,
                'merchant_reference' => $merchantReference,
                'processed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'request_data' => json_encode(request()->all())
            ]);

            // Find the MultipleOrder by tracking ID
            $multipleOrder = MultipleOrder::where('pesapal_order_tracking_id', $orderTrackingId)->first();

            if (!$multipleOrder) {
                Log::warning('MultipleOrder Pesapal IPN: Order not found', [
                    'order_tracking_id' => $orderTrackingId
                ]);

                $ipnLog->update([
                    'status_retrieved' => false,
                    'processing_result' => json_encode(['success' => false, 'message' => 'Order not found'])
                ]);

                return [
                    'success' => false,
                    'message' => 'MultipleOrder not found for tracking ID: ' . $orderTrackingId
                ];
            }

            // Get latest payment status from Pesapal
            $statusResult = $this->checkPaymentStatus($multipleOrder);

            $ipnLog->update([
                'status_retrieved' => $statusResult['success'],
                'status_data' => json_encode($statusResult['status'] ?? []),
                'processing_result' => json_encode($statusResult)
            ]);

            if ($statusResult['success']) {
                Log::info("MultipleOrder #{$multipleOrder->id}: IPN callback processed successfully", [
                    'payment_status' => $multipleOrder->payment_status
                ]);

                return [
                    'success' => true,
                    'message' => 'IPN processed successfully',
                    'multiple_order' => $multipleOrder,
                    'payment_status' => $multipleOrder->payment_status
                ];
            }

            throw new \Exception($statusResult['error'] ?? 'Failed to retrieve payment status');

        } catch (\Exception $e) {
            Log::error('MultipleOrder Pesapal IPN: Processing failed', [
                'order_tracking_id' => $orderTrackingId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'IPN processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get or register IPN URL
     * 
     * @return array ['success' => bool, 'ipn_id' => string, 'error' => string|null]
     */
    public function getOrRegisterIpnUrl()
    {
        try {
            $ipnId = $this->apiClient->registerIpnUrl();
            
            return [
                'success' => true,
                'ipn_id' => $ipnId,
                'url' => env('PESAPAL_IPN_URL'),
                'error' => null
            ];
        } catch (\Exception $e) {
            Log::error('MultipleOrder Pesapal: IPN registration failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'ipn_id' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Map Pesapal status codes to our payment status
     * 
     * @param int $pesapalStatusCode
     * @return string
     */
    private function mapPesapalStatusToPaymentStatus($pesapalStatusCode)
    {
        switch ($pesapalStatusCode) {
            case 0:
                return 'PENDING';
            case 1:
                return 'COMPLETED';
            case 2:
                return 'FAILED';
            case 3:
                return 'REVERSED';
            default:
                return 'PENDING';
        }
    }
}
