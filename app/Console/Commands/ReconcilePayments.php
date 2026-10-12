<?php

namespace App\Console\Commands;

use App\Models\UniversalPayment;
use App\Services\PesapalService;
use App\Services\UniversalPaymentReconciler;
use Illuminate\Console\Command;

/**
 * Safety net: finishes payments whose IPN / callback / app poll never arrived
 * (member paid then closed the app, network dropped, etc.).
 */
class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile {--days=3 : How far back to look}';
    protected $description = 'Re-check open Pesapal payments and apply any that were paid but not yet processed';

    public function handle(UniversalPaymentReconciler $reconciler, PesapalService $pesapal): int
    {
        $payments = UniversalPayment::whereNotNull('pesapal_order_tracking_id')
            ->where('items_processed', false)
            ->where(function ($q) {
                $q->whereIn('status', ['PENDING', 'PROCESSING', 'COMPLETED']);
            })
            ->where('created_at', '>=', now()->subDays((int) $this->option('days')))
            ->where(function ($q) {
                // don't hammer Pesapal: skip ones checked in the last 2 minutes
                $q->whereNull('last_status_check')->orWhere('last_status_check', '<', now()->subMinutes(2));
            })
            ->orderBy('id')
            ->limit(200)
            ->get();

        $applied = 0;
        $payments = $payments->filter(fn ($p) => $p->usesUniversalProcessing())->values();
        foreach ($payments as $payment) {
            $r = $reconciler->sync($payment, $pesapal);
            if ($r['processed']) {
                $applied++;
                $this->line("Applied payment #{$payment->id} ({$payment->payment_reference})");
            }
        }

        $this->info('Checked ' . $payments->count() . ', applied ' . $applied);
        return 0;
    }
}
