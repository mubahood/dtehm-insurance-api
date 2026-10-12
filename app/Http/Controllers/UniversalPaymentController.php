<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponds;
use App\Models\UniversalPayment;
use App\Models\User;
use App\Services\PesapalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Universal payments (membership, insurance premiums, project shares) via Pesapal.
 *
 * Security model:
 *  - initialize / status / index / show / process require a JWT (auth.jwt); callback + ipn are public
 *    because Pesapal and the browser call them, but they never trust the request body: the real
 *    status, amount and merchant reference are re-fetched from Pesapal.
 *  - Items and amounts are rebuilt on the server (UniversalPayment::prepareItemsForClient).
 *  - A COMPLETED payment is never downgraded, and items are applied exactly once (row lock).
 *  - Responses never contain exception text.
 */
class UniversalPaymentController extends Controller
{
    use ApiResponds;

    protected $pesapalService;

    /** Fields never sent to clients */
    private const HIDDEN = ['ip_address', 'user_agent', 'pesapal_response'];

    public function __construct(PesapalService $pesapalService)
    {
        $this->pesapalService = $pesapalService;
    }

    private function respond(bool $success, string $message, $data = null, int $http = 200, array $extra = [])
    {
        $body = array_merge(['success' => $success, 'message' => $message], $extra);
        if ($data !== null) {
            $body['data'] = $data;
        }
        return response()->json($body, $http);
    }

    private function publicPayment(UniversalPayment $payment): UniversalPayment
    {
        return $payment->makeHidden(self::HIDDEN);
    }

    /** Load a payment the caller is allowed to see, or return a response to send back. */
    private function authorizedPayment(Request $request, $id)
    {
        $payment = UniversalPayment::find($id);
        if (!$payment) {
            return $this->respond(false, 'Payment not found', null, 404);
        }
        if (!$this->canAccessUser($this->actor($request), $payment->user_id)) {
            return $this->respond(false, 'You are not allowed to access this payment', null, 403);
        }
        return $payment;
    }

    /**
     * Initialize a new payment (creates payment + initializes gateway)
     * POST /api/universal-payments/initialize
     */
    public function initialize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_type' => 'required|string|max:50',
            'payment_category' => 'nullable|string|max:50',
            'user_id' => 'nullable|exists:users,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'payment_items' => 'required|array|min:1|max:50',
            'payment_items.*.type' => 'required|string',
            'payment_items.*.id' => 'required|integer',
            'payment_items.*.amount' => 'nullable|numeric|min:0',
            'payment_items.*.quantity' => 'nullable|integer|min:1',
            'payment_gateway' => 'required|string|in:pesapal',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->respond(false, $validator->errors()->first() ?: 'Validation error', null, 422, ['errors' => $validator->errors()]);
        }

        try {
            $actor = $this->actor($request);
            $isAdmin = $this->actorIsAdmin($actor);

            // Members always pay as themselves. Admins may pay on behalf of a member.
            $payerId = $actor->id;
            if ($request->filled('user_id') && (int) $request->user_id !== (int) $actor->id) {
                if (!$isAdmin) {
                    return $this->respond(false, 'You can only make payments for your own account', null, 403);
                }
                $payerId = (int) $request->user_id;
            }
            $user = User::find($payerId);
            if (!$user) {
                return $this->respond(false, 'User not found', null, 404);
            }

            // Items and amounts are rebuilt from the database - the client only says WHAT to pay for.
            $items = UniversalPayment::prepareItemsForClient($user, $request->payment_items, $isAdmin);

            // Tapping "Pay" twice must not create two payments: reuse a recent open one for the same items.
            $signature = collect($items)->map(fn ($i) => $i['type'] . ':' . $i['id'] . ':' . (float) $i['amount'])->sort()->implode('|');
            $existing = UniversalPayment::where('user_id', $user->id)
                ->whereIn('status', ['PENDING', 'PROCESSING'])
                ->whereNotNull('pesapal_redirect_url')
                ->where('created_at', '>=', now()->subMinutes(15))
                ->orderByDesc('id')
                ->get()
                ->first(function ($p) use ($signature) {
                    $sig = collect($p->payment_items ?? [])->map(fn ($i) => ($i['type'] ?? '') . ':' . ($i['id'] ?? '') . ':' . (float) ($i['amount'] ?? 0))->sort()->implode('|');
                    return $sig === $signature;
                });
            if ($existing) {
                return $this->respond(true, 'Payment already initialized', [
                    'payment' => $this->publicPayment($existing),
                    'pesapal' => [
                        'order_tracking_id' => $existing->pesapal_order_tracking_id,
                        'redirect_url' => $existing->pesapal_redirect_url,
                        'merchant_reference' => $existing->pesapal_merchant_reference,
                    ],
                ], 200);
            }

            $paymentData = $request->only(['payment_type', 'payment_category', 'customer_address', 'description', 'payment_gateway', 'payment_method']);
            $paymentData['user_id'] = $user->id;
            $paymentData['payment_items'] = $items;
            $paymentData['currency'] = 'UGX';
            $paymentData['customer_name'] = $request->customer_name ?: $user->name;
            $paymentData['customer_phone'] = $request->customer_phone ?: ($user->phone_number ?: '');
            $paymentData['customer_email'] = $request->customer_email ?: $user->email;
            $paymentData['ip_address'] = $request->ip();
            $paymentData['user_agent'] = $request->userAgent();
            $paymentData['created_by'] = $actor->id;

            $payment = UniversalPayment::createPayment($paymentData);

            // The callback URL is always ours; a client-supplied URL could redirect members elsewhere.
            $pesapalResponse = $this->initializePesapalPayment($payment, null);

            if (!$pesapalResponse['success']) {
                return $this->respond(false, 'We could not start the payment with Pesapal. Please try again shortly.', null, 502);
            }

            return $this->respond(true, 'Payment initialized successfully', [
                'payment' => $this->publicPayment($payment->fresh()),
                'pesapal' => $pesapalResponse['data'],
            ], 201);
        } catch (\DomainException $e) {
            return $this->respond(false, $e->getMessage(), null, 422);
        } catch (\Throwable $e) {
            Log::error('Payment initialization failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->respond(false, 'Failed to initialize payment. Please try again.', null, 500);
        }
    }

    /**
     * Initialize Pesapal payment using Pesapal API Client directly
     */
    protected function initializePesapalPayment(UniversalPayment $payment, $callbackUrl = null)
    {
        try {
            // Generate unique merchant reference
            $merchantReference = 'PAYMENT_' . $payment->id . '_' . time();

            // Default callback URL
            if (!$callbackUrl) {
                $callbackUrl = url('/api/universal-payments/callback');
            }

            // Prepare payment data for Pesapal API client
            $paymentData = [
                'order_id' => $payment->id,
                'merchant_reference' => $merchantReference,
                'amount' => (float) $payment->amount,
                'currency' => \App\Config\PesapalProductionConfig::getCurrency(),
                'description' => $payment->description ?: ('Universal Payment #' . $payment->id),
                'customer_name' => $payment->customer_name,
                'customer_email' => $payment->customer_email,
                'customer_phone' => $payment->customer_phone,
                'customer_address' => $payment->customer_address ?? '',
                'callback_url' => $callbackUrl
            ];

            Log::info('Pesapal: Initializing universal payment', [
                'payment_id' => $payment->id,
                'merchant_reference' => $merchantReference,
                'amount' => $payment->amount
            ]);

            // Use Pesapal API client directly (injected from PesapalService)
            $apiClient = app(\App\Services\PesapalApiClient::class);
            $response = $apiClient->initializePayment($paymentData);

            if (!$response['success']) {
                throw new \Exception($response['message'] ?? 'Payment initialization failed');
            }

            // Create Pesapal transaction record
            \App\Models\PesapalTransaction::create([
                'order_id' => $payment->id, // Use payment ID in order_id for now
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
                'success' => true,
                'data' => [
                    'order_tracking_id' => $response['order_tracking_id'],
                    'redirect_url' => $response['redirect_url'],
                    'merchant_reference' => $merchantReference,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Pesapal initialization failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->update([
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment status (also finishes processing if Pesapal says it is paid)
     * GET /api/universal-payments/status/{id}
     */
    public function checkStatus(Request $request, $id)
    {
        try {
            $payment = $this->authorizedPayment($request, $id);
            if (!$payment instanceof UniversalPayment) {
                return $payment;
            }

            if ($payment->payment_gateway === 'pesapal' && $payment->pesapal_order_tracking_id && !$payment->items_processed) {
                try {
                    $pesapalStatus = $this->pesapalService->getTransactionStatus($payment->pesapal_order_tracking_id);
                    if ($pesapalStatus) {
                        $this->updatePaymentFromPesapal($payment, $pesapalStatus);
                    }
                } catch (\Throwable $e) {
                    // Gateway hiccup: report the stored status, the app keeps polling.
                    Log::warning('Pesapal status check failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
                }
            }

            $payment->refresh();

            $processingResult = null;
            $processingError = false;
            if ($payment->isCompleted() && !$payment->items_processed && $payment->usesUniversalProcessing()) {
                try {
                    $processingResult = $payment->processPaymentItems();
                    $payment->refresh();
                } catch (\Throwable $e) {
                    $processingError = true;
                    Log::error('Failed to process payment items', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
                }
            }

            return $this->respond(true, $processingError ? 'Payment received. We are still applying it to your account.' : 'OK', [
                'payment' => $this->publicPayment($payment),
                'is_completed' => $payment->isCompleted(),
                'is_pending' => $payment->isPending(),
                'is_failed' => $payment->isFailed(),
                'items_processed' => (bool) $payment->items_processed,
                'processing_result' => $processingResult,
                'processing_error' => $processingError,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to check payment status', ['payment_id' => $id, 'error' => $e->getMessage()]);
            return $this->respond(false, 'Failed to check payment status', null, 500);
        }
    }

    /**
     * Handle Pesapal callback (browser redirect after paying)
     * GET|POST /api/universal-payments/callback
     */
    public function handleCallback(Request $request)
    {
        try {
            $orderTrackingId = $request->input('OrderTrackingId');
            if (!$orderTrackingId) {
                return $this->respond(false, 'Missing order tracking ID', null, 400);
            }

            $payment = UniversalPayment::where('pesapal_order_tracking_id', $orderTrackingId)->first();
            if (!$payment) {
                Log::warning('Payment not found for callback', ['tracking_id' => $orderTrackingId]);
                return $this->respond(false, 'Payment not found', null, 404);
            }

            $merchantReference = $request->input('OrderMerchantReference');
            if ($merchantReference && $payment->pesapal_merchant_reference && $merchantReference !== $payment->pesapal_merchant_reference) {
                Log::warning('Callback merchant reference mismatch', ['payment_id' => $payment->id]);
                return $this->respond(false, 'Payment reference mismatch', null, 400);
            }

            // Record the status and apply the items right away if it is paid
            app(\App\Services\UniversalPaymentReconciler::class)->sync($payment, $this->pesapalService);

            return $this->respond(true, 'Callback processed', ['payment' => $this->publicPayment($payment->fresh())]);
        } catch (\Throwable $e) {
            Log::error('Callback handling failed', ['error' => $e->getMessage()]);
            return $this->respond(false, 'Failed to process callback', null, 500);
        }
    }

    /**
     * Handle Pesapal IPN. Safe to receive any number of times, in any order.
     * POST /api/universal-payments/ipn
     */
    public function handleIPN(Request $request)
    {
        try {
            $orderTrackingId = $request->input('OrderTrackingId');
            if (!$orderTrackingId) {
                return $this->respond(false, 'Missing order tracking ID', null, 400);
            }

            $payment = UniversalPayment::where('pesapal_order_tracking_id', $orderTrackingId)->first();
            if (!$payment) {
                Log::warning('Payment not found for IPN', ['tracking_id' => $orderTrackingId]);
                return $this->respond(false, 'Payment not found', null, 404);
            }

            // Atomic counter (two simultaneous IPNs must both be counted)
            UniversalPayment::where('id', $payment->id)->update([
                'last_ipn_at' => now(),
                'ipn_count' => DB::raw('COALESCE(ipn_count, 0) + 1'),
            ]);

            // Never trust the IPN body: ask Pesapal for the real status.
            $pesapalStatus = $this->pesapalService->getTransactionStatus($orderTrackingId);
            if ($pesapalStatus) {
                $this->updatePaymentFromPesapal($payment, $pesapalStatus);
                $payment->refresh();

                if ($payment->isCompleted() && !$payment->items_processed) {
                    $payment->processPaymentItems();
                }
            }

            return $this->respond(true, 'IPN processed successfully');
        } catch (\Throwable $e) {
            Log::error('IPN handling failed', ['error' => $e->getMessage()]);
            // 500 so Pesapal retries the IPN later.
            return $this->respond(false, 'Failed to process IPN', null, 500);
        }
    }

    /** Delegates to the shared reconciler so poll / callback / IPN / cron all behave identically. */
    protected function updatePaymentFromPesapal(UniversalPayment $payment, $pesapalStatus)
    {
        app(\App\Services\UniversalPaymentReconciler::class)->applyPesapalStatus($payment, (array) $pesapalStatus);
    }

    /**
     * Manually (re)process the items of a COMPLETED payment - admin only (route has api.admin)
     * POST /api/universal-payments/{id}/process
     */
    public function processItems(Request $request, $id)
    {
        try {
            $actor = $this->actor($request);
            if (!$this->actorIsAdmin($actor)) {
                return $this->respond(false, 'Admin privileges required', null, 403);
            }

            $payment = UniversalPayment::findOrFail($id);
            if (!$payment->isCompleted()) {
                return $this->respond(false, 'Payment is not completed yet', null, 400);
            }

            $result = $payment->processPaymentItems();
            return $this->respond((bool) ($result['success'] ?? false), $result['message'] ?? 'Done', $result);
        } catch (\Throwable $e) {
            Log::error('Failed to process payment items', ['payment_id' => $id, 'error' => $e->getMessage()]);
            return $this->respond(false, 'Failed to process items', null, 500);
        }
    }

    /**
     * GET /api/universal-payments - members see their own payments, admins see everything.
     */
    public function index(Request $request)
    {
        try {
            $actor = $this->actor($request);
            $query = UniversalPayment::orderBy('created_at', 'desc');

            if ($this->actorIsAdmin($actor)) {
                $query->with('user:id,name,phone_number,email');
                if ($request->filled('user_id')) {
                    $query->where('user_id', (int) $request->user_id);
                }
            } else {
                $query->where('user_id', $actor->id);
            }

            foreach (['payment_type', 'status', 'payment_gateway'] as $f) {
                if ($request->filled($f)) {
                    $query->where($f, $request->get($f));
                }
            }

            $payments = $query->paginate($this->perPage($request, 20));
            $payments->getCollection()->transform(fn ($p) => $this->publicPayment($p));

            return $this->respond(true, 'OK', $payments);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch payments', ['error' => $e->getMessage()]);
            return $this->respond(false, 'Failed to fetch payments', null, 500);
        }
    }

    /**
     * GET /api/universal-payments/{id}
     */
    public function show(Request $request, $id)
    {
        $payment = $this->authorizedPayment($request, $id);
        if (!$payment instanceof UniversalPayment) {
            return $payment;
        }
        return $this->respond(true, 'OK', ['payment' => $this->publicPayment($payment->load('user:id,name,phone_number,email'))]);
    }
}
