<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UniversalPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_reference',
        'payment_type',
        'payment_category',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'payment_items',
        'items_count',
        'amount',
        'currency',
        'description',
        'payment_gateway',
        'payment_method',
        'payment_account',
        'status',
        'payment_status_code',
        'status_message',
        'pesapal_order_tracking_id',
        'pesapal_merchant_reference',
        'pesapal_redirect_url',
        'pesapal_callback_url',
        'pesapal_notification_id',
        'pesapal_status_code',
        'pesapal_response',
        'confirmation_code',
        'payment_date',
        'confirmed_at',
        'items_processed',
        'items_processed_at',
        'processing_notes',
        'processed_by',
        'last_ipn_at',
        'ipn_count',
        'last_status_check',
        'refund_amount',
        'refunded_at',
        'refund_reason',
        'metadata',
        'ip_address',
        'user_agent',
        'error_message',
        'retry_count',
        'last_retry_at',
        'created_by',
        'updated_by',
        'project_id',
        'number_of_shares',
        'paid_by_admin',
        'admin_payment_note',
        'marked_paid_by',
        'marked_paid_at',
    ];

    protected $casts = [
        'payment_items' => 'array',
        'metadata' => 'array',
        'pesapal_response' => 'array',
        'items_processed' => 'boolean',
        'paid_by_admin' => 'boolean',
        'payment_date' => 'datetime',
        'confirmed_at' => 'datetime',
        'items_processed_at' => 'datetime',
        'last_ipn_at' => 'datetime',
        'last_status_check' => 'datetime',
        'refunded_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'marked_paid_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate payment reference
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_reference)) {
                $payment->payment_reference = self::generatePaymentReference();
            }

            if (empty($payment->currency)) {
                $payment->currency = 'UGX';
            }

            if (empty($payment->status)) {
                $payment->status = 'PENDING';
            }
        });
    }

    /**
     * Generate unique payment reference
     */
    public static function generatePaymentReference()
    {
        return 'UNI-PAY-' . time() . '-' . strtoupper(Str::random(6));
    }

    /**
     * Allowed item types a client may ask to pay for through /universal-payments/initialize.
     */
    public const CLIENT_ITEM_TYPES = ['insurance_subscription_payment', 'insurance_months', 'membership', 'project_share', 'project_share_purchase'];

    /**
     * Rebuild the payment items from the database so the client cannot choose what it pays.
     * - ownership is enforced (admins may pay on behalf of a member)
     * - amounts come from the premium row / membership fee / share price, never from the request
     * - membership type is derived from the amount, client metadata is discarded
     *
     * @throws \DomainException with a user-readable message when an item is not payable
     */
    public static function prepareItemsForClient(User $payer, array $items, bool $actorIsAdmin = false): array
    {
        $prepared = [];
        $seen = [];
        $insurance = [];

        foreach ($items as $item) {
            $type = (string) ($item['type'] ?? '');
            $id = (int) ($item['id'] ?? 0);
            $requested = round((float) ($item['amount'] ?? 0), 2);

            if (!in_array($type, self::CLIENT_ITEM_TYPES, true)) {
                throw new \DomainException('This payment type is not supported.');
            }
            $key = $type . ':' . $id;
            if ($type !== 'membership' && isset($seen[$key])) {
                throw new \DomainException('The same item was selected twice.');
            }
            $seen[$key] = true;

            switch ($type) {
                case 'insurance_subscription_payment':
                case 'insurance_months':
                    // Collected per policy below, then rebuilt as that policy's N oldest unpaid months.
                    if ($type === 'insurance_months') {
                        $sub = InsuranceSubscription::find($id);
                        $months = max(1, (int) ($item['quantity'] ?? 1));
                    } else {
                        $row = InsuranceSubscriptionPayment::find($id);
                        if (!$row) {
                            throw new \DomainException('Insurance premium not found.');
                        }
                        $sub = InsuranceSubscription::find($row->insurance_subscription_id);
                        $months = 1;
                    }
                    if (!$sub) {
                        throw new \DomainException('Insurance policy not found.');
                    }
                    if ((int) $sub->user_id !== (int) $payer->id) {
                        throw new \DomainException('This insurance policy does not belong to the selected member.');
                    }
                    if (in_array($sub->status, ['Cancelled', 'Expired'], true)) {
                        throw new \DomainException('This insurance subscription is no longer active.');
                    }
                    $insurance[$sub->id]['sub'] = $sub;
                    $insurance[$sub->id]['months'] = ($insurance[$sub->id]['months'] ?? 0) + $months;
                    break;

                case 'membership':
                    $config = SystemConfiguration::getInstance();
                    $dtehmFee = (float) ($config->dtehm_membership_fee ?: 76000);
                    $dipFee = (float) ($config->dip_membership_fee ?: 20000);
                    $options = [
                        'dtehm' => [$dtehmFee, true, false],
                        'dip' => [$dipFee, false, true],
                        'both' => [$dtehmFee + $dipFee, true, true],
                    ];
                    $match = null;
                    foreach ($options as $opt) {
                        if (abs($opt[0] - $requested) < 1) {
                            $match = $opt;
                            break;
                        }
                    }
                    if (!$match) {
                        throw new \DomainException('Invalid membership amount. Expected UGX ' . number_format($dtehmFee, 0) . ', UGX ' . number_format($dipFee, 0) . ' or UGX ' . number_format($dtehmFee + $dipFee, 0) . '.');
                    }
                    if ($match[1] && $payer->is_dtehm_member === 'Yes') {
                        throw new \DomainException('You are already a DTEHM member.');
                    }
                    if ($match[2] && $payer->is_dip_member === 'Yes') {
                        throw new \DomainException('You are already a DIP member.');
                    }
                    $prepared[] = [
                        'type' => 'membership',
                        'id' => $payer->id,
                        'amount' => $match[0],
                        'name' => 'Membership',
                        'metadata' => [
                            'is_dtehm_member' => $match[1] ? 'Yes' : 'No',
                            'is_dip_member' => $match[2] ? 'Yes' : 'No',
                        ],
                    ];
                    break;

                case 'project_share':
                case 'project_share_purchase':
                    $project = Project::find($id);
                    if (!$project || (float) $project->share_price <= 0) {
                        throw new \DomainException('Project not found or has no share price.');
                    }
                    $shares = (int) ($item['quantity'] ?? 0);
                    if ($shares <= 0) {
                        $shares = (int) round($requested / (float) $project->share_price);
                    }
                    if ($shares <= 0) {
                        throw new \DomainException('Please choose at least one share.');
                    }
                    $prepared[] = [
                        'type' => $type,
                        'id' => $project->id,
                        'quantity' => $shares,
                        'amount' => round($shares * (float) $project->share_price, 2),
                        'name' => $project->title ?? 'Project shares',
                    ];
                    break;
            }
        }

        foreach ($insurance as $subId => $group) {
            $open = $group['sub']->openPaymentsOldestFirst();
            if ($open->isEmpty()) {
                throw new \DomainException('This policy has no unpaid months.');
            }
            $months = min($group['months'], $open->count());
            $rows = $open->take($months);

            // Online payments always settle whole months (late fee and any earlier part-payment included).
            // The amount the app sends is ignored: installed app builds send the bare premium, which used to
            // leave a late fee owing as a confusing "part paid" month. Part-payments are recorded by the office.
            foreach ($rows as $r) {
                $prepared[] = [
                    'type' => 'insurance_subscription_payment',
                    'id' => $r->id,
                    'amount' => max(0, round((float) $r->total_amount - (float) $r->paid_amount, 2)),
                    'name' => ($r->description ?: 'Insurance premium') . ((float) $r->penalty_amount > 0 ? ' (incl. late fee)' : ''),
                ];
            }
        }

        if (empty($prepared)) {
            throw new \DomainException('No payable items were selected.');
        }

        return $prepared;
    }

    /**
     * Create a new payment
     */
    public static function createPayment(array $data)
    {
        try {
            // Calculate total from payment items if not provided
            if (!isset($data['amount']) && isset($data['payment_items'])) {
                $data['amount'] = array_sum(array_column($data['payment_items'], 'amount'));
            }

            // Set items count
            if (isset($data['payment_items'])) {
                $data['items_count'] = count($data['payment_items']);
            }

            // Create payment
            $payment = self::create($data);

            Log::info('Universal payment created', [
                'payment_id' => $payment->id,
                'reference' => $payment->payment_reference,
                'amount' => $payment->amount,
                'items' => $payment->items_count,
            ]);

            return $payment;
        } catch (\Exception $e) {
            Log::error('Failed to create universal payment', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /** Item types this class knows how to apply. Anything else (e.g. product orders) has its own module. */
    public const PROCESSABLE_TYPES = ['insurance_subscription_payment', 'insurance_transaction', 'order', 'project_share', 'project_share_purchase', 'membership'];

    /**
     * True when every item can be applied by processPaymentItems(). Product purchases, and old
     * rows whose items carry no type, are handled by their own code and must not be touched here.
     */
    public function usesUniversalProcessing(): bool
    {
        $items = $this->payment_items;
        if (!is_array($items) || empty($items)) {
            return false;
        }
        foreach ($items as $item) {
            $type = is_array($item) ? ($item['type'] ?? ($item['item_type'] ?? '')) : '';
            if (!in_array($type, self::PROCESSABLE_TYPES, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Process payment items - Mark individual items as paid
     * This is called after payment is confirmed as COMPLETED
     */
    public function processPaymentItems()
    {
        if (!$this->usesUniversalProcessing()) {
            return ['success' => false, 'message' => 'This payment is applied by its own module, not here.'];
        }

        // Check if already processed (FIRST SAFEGUARD)
        if ($this->items_processed) {
            Log::info('Payment items already processed', [
                'payment_id' => $this->id,
                'reference' => $this->payment_reference,
            ]);
            return ['success' => true, 'message' => 'Items already processed'];
        }

        // Use database transaction for atomicity (SECOND SAFEGUARD)
        return \DB::transaction(function () {
            // Double-check inside transaction under a ROW LOCK (THIRD SAFEGUARD - concurrent
            // IPN / callback / status-poll requests queue here and the later ones see items_processed)
            $locked = self::lockForUpdate()->find($this->id);
            if ($locked) {
                $this->setRawAttributes($locked->getAttributes(), true);
            }
            if (!$this->isCompleted()) {
                throw new \DomainException('Payment is not completed, items cannot be processed.');
            }
            if ($this->items_processed) {
                Log::info('Payment items already processed (detected in transaction)', [
                    'payment_id' => $this->id,
                    'reference' => $this->payment_reference,
                ]);
                return ['success' => true, 'message' => 'Items already processed'];
            }

            try {
                $processedItems = [];
                $failedItems = [];

                // Process each payment item
                foreach ($this->payment_items as $item) {
                    try {
                        $result = $this->processIndividualItem($item);
                        if ($result['success']) {
                            $processedItems[] = $item;
                        } else {
                            $failedItems[] = ['item' => $item, 'error' => $result['message']];
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to process individual item', [
                            'payment_id' => $this->id,
                            'item' => $item,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        $failedItems[] = ['item' => $item, 'error' => $e->getMessage()];
                    }
                }

                // Mark as processed if all items succeeded
                if (empty($failedItems)) {
                    $this->update([
                        'items_processed' => true,
                        'items_processed_at' => now(),
                        'processing_notes' => 'All ' . count($processedItems) . ' items processed successfully',
                    ]);

                    Log::info('🎉 ============================================', []);
                    Log::info('🎉 ALL PAYMENT ITEMS PROCESSED SUCCESSFULLY', [
                        'universal_payment_id' => $this->id,
                        'payment_reference' => $this->payment_reference,
                        'payment_type' => $this->payment_type,
                        'total_amount' => $this->total_amount,
                        'items_count' => count($processedItems),
                        'items_processed_at' => now()->toDateTimeString(),
                        'customer_name' => $this->customer_name,
                        'customer_phone' => $this->customer_phone,
                    ]);
                    Log::info('🎉 ============================================', []);

                    return [
                        'success' => true,
                        'message' => 'All items processed successfully',
                        'processed' => count($processedItems),
                        'failed' => 0,
                        'payment_reference' => $this->payment_reference,
                    ];
                } else {
                    // Partial success - DO NOT mark as processed (FOURTH SAFEGUARD)
                    $this->update([
                        'processing_notes' => 'Processed ' . count($processedItems) . ' items, ' . count($failedItems) . ' failed',
                    ]);

                    Log::warning('Some payment items failed to process', [
                        'payment_id' => $this->id,
                        'reference' => $this->payment_reference,
                        'processed' => count($processedItems),
                        'failed' => count($failedItems),
                        'errors' => $failedItems,
                    ]);

                    // Rollback transaction for partial failures
                    throw new \Exception('Some items failed to process: ' . json_encode($failedItems));
                }
            } catch (\Exception $e) {
                Log::error('Failed to process payment items', [
                    'payment_id' => $this->id,
                    'reference' => $this->payment_reference,
                    'error' => $e->getMessage(),
                ]);

                throw $e; // Re-throw to rollback transaction
            }
        });
    }

    /**
     * Process individual payment item based on type
     */
    protected function processIndividualItem(array $item)
    {
        // Some creators (e.g. share purchases) name the keys item_type / item_id
        $item['type'] = $item['type'] ?? ($item['item_type'] ?? '');
        $item['id'] = $item['id'] ?? ($item['item_id'] ?? 0);

        switch ($item['type']) {
            case 'insurance_subscription_payment':
                return $this->processInsuranceSubscriptionPayment($item['id'], $item);

            case 'insurance_transaction':
                return $this->processInsuranceTransaction($item['id'], $item);

            case 'order':
                return $this->processOrder($item['id'], $item);

            case 'project_share':
            case 'project_share_purchase': // Support both naming conventions
                return $this->processProjectSharePurchase($item['id'], $item);

            case 'membership':
                return $this->processMembershipPayment($item['id'], $item);

            default:
                Log::warning('Unknown payment item type', [
                    'type' => $item['type'],
                    'item' => $item,
                ]);
                return [
                    'success' => false,
                    'message' => 'Unknown payment item type: ' . $item['type'],
                ];
        }
    }

    /**
     * Process Insurance Subscription Payment
     * Mark payment as paid and cascade to subscription & program
     * CRITICAL: This only runs if UniversalPayment status is COMPLETED
     */
    protected function processInsuranceSubscriptionPayment($paymentId, array $item)
    {
        try {
            $row = \App\Models\InsuranceSubscriptionPayment::find($paymentId);
            if (!$row) {
                Log::error('Insurance premium not found', ['payment_id' => $paymentId, 'universal_payment_id' => $this->id]);
                return ['success' => false, 'message' => 'Payment not found'];
            }

            $money = round((float) $item['amount'], 2);
            if ($money <= 0) {
                return ['success' => true, 'message' => 'Nothing to apply'];
            }

            // The money goes to the policy's OLDEST unpaid months, starting with the oldest - normally the
            // very month(s) chosen at checkout; if someone settled those meanwhile it moves to the next one.
            $open = \App\Models\InsuranceSubscriptionPayment::where('insurance_subscription_id', $row->insurance_subscription_id)
                ->whereIn('payment_status', ['Pending', 'Overdue', 'Partial'])
                ->orderBy('due_date')->orderBy('id')
                ->lockForUpdate()
                ->get();

            $applied = [];
            foreach ($open as $target) {
                if ($money <= 0.009) {
                    break;
                }
                $owed = round((float) $target->total_amount - (float) $target->paid_amount, 2);
                if ($owed <= 0) {
                    continue;
                }
                $take = min($owed, $money);
                $newPaid = round((float) $target->paid_amount + $take, 2);

                $target->update([
                    'paid_amount' => $newPaid,
                    'payment_status' => $newPaid + 0.009 >= (float) $target->total_amount ? 'Paid' : 'Partial',
                    'payment_date' => now()->toDateString(),
                    'payment_method' => $this->payment_method ?: 'Online (Pesapal)',
                    'payment_reference' => $this->payment_reference,
                    'transaction_id' => $this->pesapal_order_tracking_id,
                ]);

                $money = round($money - $take, 2);
                $applied[] = $target->period_name;
            }

            if ($money > 0.009) {
                // Policy fully paid already: keep a visible note so the office can refund / credit it.
                $this->update(['processing_notes' => trim(($this->processing_notes ? $this->processing_notes . ' | ' : '') .
                    'UGX ' . number_format($money, 0) . ' could not be applied (policy #' . $row->insurance_subscription_id . ' has no unpaid months) - refund or credit needed')]);
                Log::warning('Insurance overpayment', ['universal_payment_id' => $this->id, 'left' => $money]);
            }

            Log::info('Insurance premium payment applied', ['universal_payment_id' => $this->id, 'months' => $applied]);

            return ['success' => true, 'message' => 'Applied to: ' . implode(', ', $applied), 'months' => $applied];
        } catch (\Exception $e) {
            Log::error('Failed to process insurance subscription payment', [
                'insurance_payment_id' => $paymentId,
                'universal_payment_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Process Insurance Transaction
     * Mark transaction as completed
     */
    protected function processInsuranceTransaction($transactionId, array $item)
    {
        try {
            $transaction = \App\Models\InsuranceTransaction::find($transactionId);

            if (!$transaction) {
                return ['success' => false, 'message' => 'Transaction not found'];
            }

            $transaction->update([
                'payment_status' => 'COMPLETED',
                'payment_confirmation' => $this->payment_reference,
            ]);

            Log::info('Insurance transaction processed', [
                'transaction_id' => $transactionId,
                'amount' => $item['amount'],
                'universal_payment_ref' => $this->payment_reference,
            ]);

            return ['success' => true, 'message' => 'Transaction marked as COMPLETED'];
        } catch (\Exception $e) {
            Log::error('Failed to process insurance transaction', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Process Ecommerce Order
     * Mark order as paid
     */
    protected function processOrder($orderId, array $item)
    {
        try {
            $order = \App\Models\Order::find($orderId);

            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }

            $order->update([
                'payment_status' => 'PAID',
                'payment_confirmation' => 'Yes',
                'order_state' => 'PROCESSING',
            ]);

            Log::info('Order processed', [
                'order_id' => $orderId,
                'amount' => $item['amount'],
                'universal_payment_ref' => $this->payment_reference,
            ]);

            return ['success' => true, 'message' => 'Order marked as PAID'];
        } catch (\Exception $e) {
            Log::error('Failed to process order', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted()
    {
        // `status` is the single source of truth. (It used to also accept a raw gateway code of 1,
        // which let a payment rejected for an amount mismatch still be treated as paid.)
        return $this->status === 'COMPLETED';
    }

    /**
     * Check if payment is pending
     */
    public function isPending()
    {
        return $this->status === 'PENDING' || 
               $this->status === 'PROCESSING' ||
               $this->payment_status_code === '0' ||
               $this->pesapal_status_code === '0' ||
               empty($this->status);
    }

    /**
     * Check if payment failed
     */
    public function isFailed()
    {
        return in_array($this->status, ['FAILED', 'INVALID', 'CANCELLED']);
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor()
    {
        return match($this->status) {
            'COMPLETED' => 'green',
            'PENDING', 'PROCESSING' => 'orange',
            'FAILED', 'INVALID', 'CANCELLED' => 'red',
            default => 'gray',
        };
    }

    /**
     * Process project share purchase
     * Create share record and transaction record
     */
    protected function processProjectSharePurchase($projectId, array $item)
    {
        try {
            $project = Project::find($projectId);

            if (!$project) {
                Log::error('Project not found', [
                    'project_id' => $projectId,
                    'universal_payment_id' => $this->id,
                ]);
                return ['success' => false, 'message' => 'Project not found'];
            }

            Log::info('Processing project share purchase', [
                'universal_payment_id' => $this->id,
                'project_id' => $projectId,
                'amount' => $item['amount'],
                'share_price' => $project->share_price,
                'universal_payment_status' => $this->status,
            ]);

            // SAFEGUARD 1: Check if already processed (prevent duplicate shares by payment_id)
            $existingShare = ProjectShare::where('payment_id', $this->id)->first();
            if ($existingShare) {
                Log::info('✅ Share already created for this payment', [
                    'payment_id' => $this->id,
                    'share_id' => $existingShare->id,
                    'project_id' => $projectId,
                    'shares' => $existingShare->number_of_shares,
                ]);
                return [
                    'success' => true,
                    'message' => 'Share already created',
                    'share_id' => $existingShare->id,
                ];
            }

            // SAFEGUARD 2: Validate project has share_price
            if (!$project->share_price || $project->share_price <= 0) {
                Log::error('Project has invalid share price', [
                    'project_id' => $projectId,
                    'share_price' => $project->share_price,
                ]);
                return ['success' => false, 'message' => 'Project has invalid share price'];
            }

            // Calculate number of shares from amount and price
            // Shares are derived from the amount actually paid; a client-supplied quantity is only
            // accepted when it matches the amount exactly.
            $numberOfShares = (int) round(floatval($item['amount']) / floatval($project->share_price));
            if (abs($numberOfShares * floatval($project->share_price) - floatval($item['amount'])) > 1) {
                return ['success' => false, 'message' => 'Amount does not match a whole number of shares'];
            }

            // SAFEGUARD 3: Validate number of shares
            if ($numberOfShares <= 0) {
                Log::error('Invalid number of shares calculated', [
                    'project_id' => $projectId,
                    'amount' => $item['amount'],
                    'share_price' => $project->share_price,
                    'calculated_shares' => $numberOfShares,
                ]);
                return ['success' => false, 'message' => 'Invalid number of shares calculated'];
            }

            // SAFEGUARD 4: Check if project has enough available shares
            if (isset($project->total_shares) && isset($project->shares_sold)) {
                $availableShares = $project->total_shares - $project->shares_sold;
                if ($numberOfShares > $availableShares) {
                    Log::error('Not enough shares available', [
                        'project_id' => $projectId,
                        'requested' => $numberOfShares,
                        'available' => $availableShares,
                    ]);
                    return ['success' => false, 'message' => 'Not enough shares available'];
                }
            }

            // CRITICAL: Create share record (links to payment for duplicate prevention)
            $shareData = [
                'project_id' => $projectId,
                'investor_id' => $this->user_id,
                'purchase_date' => now(),
                'number_of_shares' => $numberOfShares,
                'total_amount_paid' => floatval($item['amount']),
                'share_price_at_purchase' => floatval($project->share_price),
                'payment_id' => $this->id, // CRITICAL: Links to universal payment for duplicate prevention
            ];

            Log::info('💾 Creating share record', [
                'share_data' => $shareData,
            ]);

            $share = ProjectShare::create($shareData);

            Log::info('✅ Share record created', [
                'share_id' => $share->id,
                'project_id' => $projectId,
                'shares' => $numberOfShares,
                'investor_id' => $this->user_id,
            ]);

            // Create transaction record (income from share purchase)
            $transactionData = [
                'project_id' => $projectId,
                'amount' => floatval($item['amount']),
                'transaction_date' => now(),
                'created_by_id' => $this->user_id,
                'description' => "Share purchase by {$this->customer_name} - {$numberOfShares} shares",
                'type' => 'income',
                'source' => 'share_purchase',
                'related_share_id' => $share->id,
            ];

            Log::info('💾 Creating transaction record', [
                'transaction_data' => $transactionData,
            ]);

            $transaction = ProjectTransaction::create($transactionData);

            Log::info('✅ Transaction record created', [
                'transaction_id' => $transaction->id,
                'amount' => $item['amount'],
            ]);

            // Update project computed fields (shares_sold, etc.)
            Log::info('🔄 Updating project computed fields', [
                'project_id' => $projectId,
            ]);
            $project->updateComputedFields();

            Log::info('✅ Project share purchase processed successfully', [
                'universal_payment_id' => $this->id,
                'universal_payment_ref' => $this->payment_reference,
                'share_id' => $share->id,
                'transaction_id' => $transaction->id,
                'project_id' => $projectId,
                'investor_id' => $this->user_id,
                'shares' => $numberOfShares,
                'amount' => $item['amount'],
                'share_price' => $project->share_price,
            ]);

            return [
                'success' => true,
                'message' => 'Share purchase processed successfully',
                'share_id' => $share->id,
                'transaction_id' => $transaction->id,
                'shares_purchased' => $numberOfShares,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to process project share purchase', [
                'universal_payment_id' => $this->id,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process share purchase: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process Membership Payment
     * Creates DtehmMembership records and updates User model with DTEHM/DIP membership
     * Supports: Single DTEHM, Single DIP, or Both DTEHM + DIP payments
     */
    protected function processMembershipPayment($itemId, array $item)
    {
        try {
            Log::info('🎫 Processing membership payment', [
                'universal_payment_id' => $this->id,
                'universal_payment_ref' => $this->payment_reference,
                'user_id' => $this->user_id,
                'amount' => $item['amount'],
                'item_metadata' => $item['metadata'] ?? null,
            ]);

            // SAFEGUARD 1: Check if membership already exists for this universal payment
            $existingMembership = \App\Models\DtehmMembership::where('universal_payment_id', $this->id)->first();
            
            if ($existingMembership) {
                Log::info('✅ Membership already exists for this universal payment', [
                    'membership_id' => $existingMembership->id,
                    'universal_payment_id' => $this->id,
                    'reference' => $this->payment_reference,
                ]);
                return ['success' => true, 'message' => 'Membership already processed'];
            }

            // Get user
            $user = \App\Models\User::find($this->user_id);
            if (!$user) {
                Log::error('❌ User not found', [
                    'user_id' => $this->user_id,
                    'universal_payment_id' => $this->id,
                ]);
                return ['success' => false, 'message' => 'User not found'];
            }

            // Get dynamic membership fees from system configuration
            $config = \App\Models\SystemConfiguration::getInstance();
            $dtehmFee = (float) ($config->dtehm_membership_fee ?: 76000);
            $dipFee = (float) ($config->dip_membership_fee ?: 20000);
            $currency = $config->currency ?? 'UGX';

            Log::info('💰 Dynamic membership fees loaded', [
                'dtehm_fee' => $dtehmFee,
                'dip_fee' => $dipFee,
                'currency' => $currency,
            ]);

            // Determine membership type(s) from the amount paid. Client-supplied metadata is NOT trusted.
            $amount = floatval($item['amount']);
            $isDtehmPayment = false;
            $isDipPayment = false;

            if (abs($amount - ($dtehmFee + $dipFee)) < 1) {
                $isDtehmPayment = true;
                $isDipPayment = true;
            } elseif (abs($amount - $dtehmFee) < 1) {
                $isDtehmPayment = true;
            } elseif (abs($amount - $dipFee) < 1) {
                $isDipPayment = true;
            } else {
                Log::error('Membership amount does not match any fee', ['amount' => $amount, 'dtehm_fee' => $dtehmFee, 'dip_fee' => $dipFee]);
                return ['success' => false, 'message' => 'Membership amount does not match a membership fee'];
            }

            Log::info('🔍 Membership type determined', [
                'is_dtehm' => $isDtehmPayment,
                'is_dip' => $isDipPayment,
                'amount_paid' => $amount,
            ]);

            // Create DtehmMembership record
            $membershipTypes = [];
            if ($isDtehmPayment) $membershipTypes[] = 'DTEHM';
            if ($isDipPayment) $membershipTypes[] = 'DIP';

            $membershipData = [
                'user_id' => $this->user_id,
                'universal_payment_id' => $this->id,
                'payment_reference' => $this->payment_reference,
                'amount' => $amount,
                'status' => 'CONFIRMED',
                'payment_method' => 'PESAPAL', // Must match ENUM: CASH, MOBILE_MONEY, BANK_TRANSFER, PESAPAL
                'payment_phone_number' => $this->customer_phone,
                'payment_account_number' => $this->payment_account,
                'payment_date' => $this->payment_date ?? now(),
                'confirmed_at' => now(),
                'membership_type' => 'DTEHM', // Must match ENUM: only 'DTEHM' allowed
                'expiry_date' => null,
                'notes' => $this->description ?? ('Membership payment: ' . implode(' + ', $membershipTypes) . ' (' . $amount . ' ' . $currency . ')'),
                'pesapal_merchant_reference' => $this->pesapal_merchant_reference,
                'pesapal_tracking_id' => $this->pesapal_order_tracking_id,
                'created_by' => $this->user_id,
                'confirmed_by' => $this->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            Log::info('💾 Creating DtehmMembership record', [
                'membership_data' => $membershipData,
            ]);

            $membership = \App\Models\DtehmMembership::create($membershipData);

            Log::info('✅ DtehmMembership record created', [
                'membership_id' => $membership->id,
                'membership_types' => implode(', ', $membershipTypes),
                'amount' => $membership->amount,
                'status' => $membership->status,
            ]);

            // Update User model with membership flags
            $updateData = [];
            
            // Always set the main membership fields (required for hasValidMembership())
            $updateData['is_membership_paid'] = 1;
            $updateData['membership_paid_at'] = now();
            $updateData['membership_type'] = 'LIFE'; // DTEHM/DIP are LIFE memberships
            
            // CRITICAL: Process DTEHM membership if paid
            if ($isDtehmPayment) {
                $updateData['is_dtehm_member'] = 'Yes';
                $updateData['dtehm_membership_paid_at'] = now();
                $updateData['dtehm_membership_paid_date'] = now();
                $updateData['dtehm_membership_is_paid'] = 'Yes';
                $updateData['dtehm_membership_paid_amount'] = $dtehmFee;
                
                Log::info('✅ DTEHM membership will be activated', [
                    'user_id' => $user->id,
                    'previous_status' => $user->is_dtehm_member,
                    'new_status' => 'Yes',
                ]);
                
            }
            
            // CRITICAL: Process DIP membership if paid
            if ($isDipPayment) {
                $updateData['is_dip_member'] = 'Yes';
                
                Log::info('✅ DIP membership will be activated', [
                    'user_id' => $user->id,
                    'previous_status' => $user->is_dip_member,
                    'new_status' => 'Yes',
                ]);
                // NOTE: There are NO dip_member_id or dip_membership_paid_at columns in users table
                // DIP membership is tracked only via is_dip_member field and dtehm_memberships table
            }
            
            // Log both memberships being processed if applicable
            if ($isDtehmPayment && $isDipPayment) {
                Log::info('🎉 PROCESSING BOTH MEMBERSHIPS TOGETHER', [
                    'user_id' => $user->id,
                    'amount_paid' => $amount,
                    'dtehm_fee' => $dtehmFee,
                    'dip_fee' => $dipFee,
                    'total_expected' => $dtehmFee + $dipFee,
                ]);
            }

            if (!empty($updateData)) {
                Log::info('🔄 Updating user with data', [
                    'user_id' => $user->id,
                    'update_data' => $updateData,
                    'before_dtehm' => $user->is_dtehm_member,
                    'before_dip' => $user->is_dip_member,
                ]);
                
                // Use DB::table to bypass model events that might be interfering
                $affectedRows = \DB::table('users')
                    ->where('id', $user->id)
                    ->update($updateData);
                
                Log::info('🔄 Direct DB update result', [
                    'affected_rows' => $affectedRows,
                    'update_data' => $updateData,
                ]);
                
                if ($isDtehmPayment) {
                    // Same DTEHM### generator the User model uses (race-safe)
                    \App\Models\User::ensureDtehmMemberId($user->id);
                }

                $user->refresh(); // Refresh model from database
                
                Log::info('✅ User model updated with membership info', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'is_dtehm_member' => $user->is_dtehm_member,
                    'is_dip_member' => $user->is_dip_member,
                    'dtehm_member_id' => $user->dtehm_member_id ?? 'N/A',
                ]);
            }

            Log::info('🎉 ============================================', []);
            Log::info('🎉 MEMBERSHIP PAYMENT PROCESSED SUCCESSFULLY', [
                'universal_payment_id' => $this->id,
                'payment_reference' => $this->payment_reference,
                'dtehm_membership_id' => $membership->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'amount_paid' => $amount,
                'membership_types' => implode(', ', $membershipTypes),
                'is_dtehm_member' => $isDtehmPayment ? 'Yes' : 'No',
                'is_dip_member' => $isDipPayment ? 'Yes' : 'No',
            ]);
            Log::info('🎉 ============================================', []);

            return [
                'success' => true,
                'message' => 'Membership payment processed successfully',
                'dtehm_membership_id' => $membership->id,
                'user_id' => $user->id,
                'membership_types' => implode(', ', $membershipTypes),
            ];
        } catch (\Exception $e) {
            Log::error('❌ Failed to process membership payment', [
                'universal_payment_id' => $this->id,
                'user_id' => $this->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process membership payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function membershipPayment()
    {
        return $this->hasOne(\App\Models\MembershipPayment::class, 'universal_payment_id');
    }

    public function orderedItems()
    {
        return $this->hasMany(\App\Models\OrderedItem::class, 'universal_payment_id');
    }

    /**
     * Helper Methods
     */
    public function isSharePurchase()
    {
        return !is_null($this->project_id) && !is_null($this->number_of_shares);
    }

    public function isMembershipPayment()
    {
        return $this->payment_type === 'membership';
    }

    public function isProductPurchase()
    {
        return $this->payment_type === 'product';
    }
}
