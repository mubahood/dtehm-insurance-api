<?php

namespace App\Services;

use App\Models\UniversalPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single place that turns a Pesapal status into a UniversalPayment state and applies the paid
 * items (membership, insurance premium, shares) exactly once.
 *
 * Called from: the app's status poll, the browser callback, Pesapal's IPN, and the scheduled
 * `payments:reconcile` sweep - so a member who pays and closes the app is still credited.
 *
 * Rules: COMPLETED is final; a Pesapal COMPLETED is only accepted when its amount and currency
 * match the amount we requested; confirmed_at / payment_date are set once.
 */
class UniversalPaymentReconciler
{
    /** Apply a raw Pesapal GetTransactionStatus payload to the payment (locked, monotonic). */
    public function applyPesapalStatus(UniversalPayment $payment, array $pesapalStatus): void
    {
        $statusCode = $pesapalStatus['payment_status_code'] ?? null;
        if ($statusCode === null || $statusCode === '') {
            $statusCode = $pesapalStatus['status_code'] ?? null;
        }
        if ($statusCode === '' || $statusCode === null) {
            $statusCode = null;
        }
        $text = strtoupper((string) ($pesapalStatus['payment_status_description'] ?? $pesapalStatus['status'] ?? ''));

        $status = 'PENDING';
        $message = $pesapalStatus['payment_status_description'] ?? $pesapalStatus['description'] ?? null;

        if ((string) $statusCode === '1' || $text === 'COMPLETED') {
            $status = 'COMPLETED';

            $paidAmount = $pesapalStatus['amount'] ?? null;
            $paidCurrency = $pesapalStatus['currency'] ?? null;
            if ($paidAmount !== null && abs((float) $paidAmount - (float) $payment->amount) > 1) {
                Log::critical('Pesapal amount mismatch - payment NOT credited', [
                    'payment_id' => $payment->id, 'expected' => $payment->amount, 'pesapal_amount' => $paidAmount,
                ]);
                $status = 'FAILED';
                $message = 'Amount paid does not match the amount due. Please contact support.';
            } elseif ($paidCurrency && strtoupper($paidCurrency) !== strtoupper($payment->currency ?: 'UGX')) {
                Log::critical('Pesapal currency mismatch - payment NOT credited', ['payment_id' => $payment->id]);
                $status = 'FAILED';
                $message = 'Currency mismatch. Please contact support.';
            }
        } elseif ((string) $statusCode === '2' || in_array($text, ['FAILED', 'INVALID'], true)) {
            $status = 'FAILED';
        }

        DB::transaction(function () use ($payment, $status, $statusCode, $pesapalStatus, $message) {
            $locked = UniversalPayment::lockForUpdate()->find($payment->id);

            // A completed payment is final: late FAILED/PENDING replies must not undo it.
            if ($locked->status === 'COMPLETED' || $locked->items_processed) {
                $locked->update(['last_status_check' => now()]);
                return;
            }

            $locked->update([
                'status' => $status,
                'payment_status_code' => $statusCode,
                'pesapal_status_code' => $statusCode,
                'pesapal_response' => $pesapalStatus,
                'status_message' => $message,
                'confirmation_code' => $pesapalStatus['confirmation_code'] ?? $locked->confirmation_code,
                'payment_date' => $status === 'COMPLETED' ? ($locked->payment_date ?: now()) : $locked->payment_date,
                'confirmed_at' => $status === 'COMPLETED' ? ($locked->confirmed_at ?: now()) : $locked->confirmed_at,
                'last_status_check' => now(),
            ]);
        });

        $payment->refresh();
    }

    /**
     * Ask Pesapal for the real status, record it and, if paid, apply the items.
     * Returns ['status' => ..., 'processed' => bool, 'error' => ?string]. Never throws.
     */
    public function sync(UniversalPayment $payment, PesapalService $pesapal): array
    {
        $out = ['status' => $payment->status, 'processed' => (bool) $payment->items_processed, 'error' => null];

        // Product orders and legacy shapes have their own processing + IPN: leave them completely alone.
        if (!$payment->usesUniversalProcessing()) {
            $out['error'] = 'not_applicable';
            return $out;
        }

        try {
            if ($payment->payment_gateway === 'pesapal' && $payment->pesapal_order_tracking_id && !$payment->items_processed) {
                $remote = $pesapal->getTransactionStatus($payment->pesapal_order_tracking_id);
                if (is_array($remote)) {
                    $this->applyPesapalStatus($payment, $remote);
                }
            }
            $payment->refresh();

            if ($payment->isCompleted() && !$payment->items_processed) {
                $payment->processPaymentItems();
                $payment->refresh();
            }
        } catch (\Throwable $e) {
            Log::error('UniversalPaymentReconciler: sync failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            $out['error'] = 'sync_failed';
        }

        $out['status'] = $payment->status;
        $out['processed'] = (bool) $payment->items_processed;
        return $out;
    }
}
