<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponds;
use App\Models\InsuranceSubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Insurance premium payment rows.
 * Members: read their own rows only. Admin-only (api.admin): update, markAsPaid, stats, overdue.
 * Online payments are recorded by UniversalPayment after Pesapal confirms - never by the client.
 */
class InsuranceSubscriptionPaymentController extends Controller
{
    use ApiResponds;

    private const SORTABLE = ['due_date', 'created_at', 'payment_status', 'amount', 'paid_amount', 'days_overdue', 'id'];

    private const WITH = ['insuranceSubscription.insuranceProgram', 'user:id,name,first_name,last_name,phone_number,email,business_name,dtehm_member_id'];

    private function paged($payments, $message)
    {
        return $this->ok($message, $payments->items(), 200, [
            'pagination' => [
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    private function applyCommonFilters($query, Request $request)
    {
        foreach (['insurance_subscription_id', 'insurance_program_id', 'payment_status', 'billing_frequency', 'year', 'month_number'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->get($f));
            }
        }
        if ($request->filled('due_date_from')) {
            $query->where('due_date', '>=', $request->due_date_from);
        }
        if ($request->filled('due_date_to')) {
            $query->where('due_date', '<=', $request->due_date_to);
        }
        return $query;
    }

    public function index(Request $request)
    {
        try {
            $actor = $this->actor($request);
            if (!$actor) {
                return $this->fail('User not authenticated', 401);
            }

            $query = InsuranceSubscriptionPayment::with(self::WITH);

            if (!$this->actorIsAdmin($actor)) {
                $query->where('user_id', $actor->id);
            } elseif ($request->filled('user_id')) {
                $query->where('user_id', (int) $request->user_id);
            }

            // The subscription filter applies to everyone (members used to get ALL their
            // subscriptions' rows back, so they could pick months of the wrong policy).
            $this->applyCommonFilters($query, $request);

            $query->orderBy($this->sortColumn($request, self::SORTABLE, 'due_date'), $this->sortDirection($request));

            return $this->paged($query->paginate($this->perPage($request)), 'Insurance subscription payments retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to retrieve payments.');
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $payment = InsuranceSubscriptionPayment::with(self::WITH)->findOrFail($id);

            if (!$this->canAccessUser($this->actor($request), $payment->user_id)) {
                return $this->forbidden('Access denied. You can only view your own payments.');
            }

            return $this->ok('Insurance subscription payment retrieved successfully.', $payment);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to retrieve the payment.');
        }
    }

    /**
     * Admin only. Notes / reference fields only - amounts and status change through markAsPaid
     * (cash received) or via a confirmed online payment, so the ledger cannot be edited freely.
     */
    public function update(Request $request, $id)
    {
        try {
            $payment = InsuranceSubscriptionPayment::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'payment_status' => 'sometimes|in:Waived',
                'payment_method' => 'nullable|string|max:100',
                'payment_reference' => 'nullable|string|max:255',
                'transaction_id' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:2000',
            ]);
            if ($validator->fails()) {
                return $this->validationFailed($validator);
            }

            $data = $request->only(['payment_status', 'payment_method', 'payment_reference', 'transaction_id', 'notes']);
            if (($data['payment_status'] ?? null) === 'Waived' && $payment->payment_status === 'Paid') {
                return $this->fail('A paid premium cannot be waived.', 400);
            }
            $data['updated_by'] = $this->actor($request)->id;
            $payment->update($data);

            return $this->ok('Insurance subscription payment updated successfully.', $payment->fresh()->load(self::WITH));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to update the payment.');
        }
    }

    /**
     * Admin only: record cash / manual money received.
     * Amount must be > 0 and cannot exceed what is still owed. Paying the full balance marks the row
     * Paid; less marks it Partial. Locked + transactional so a double click cannot double-record.
     */
    public function markAsPaid(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'paid_amount' => 'required|numeric|gt:0',
                'payment_method' => 'required|string|max:100',
                'payment_reference' => 'nullable|string|max:255',
                'transaction_id' => 'nullable|string|max:255',
            ]);
            if ($validator->fails()) {
                return $this->validationFailed($validator);
            }
            $admin = $this->actor($request);

            $payment = DB::transaction(function () use ($request, $id, $admin) {
                $payment = InsuranceSubscriptionPayment::lockForUpdate()->findOrFail($id);

                if (in_array($payment->payment_status, ['Paid', 'Waived'], true)) {
                    throw new \DomainException('This premium is already ' . strtolower($payment->payment_status) . '.');
                }

                $owed = round((float) $payment->total_amount - (float) $payment->paid_amount, 2);
                $amount = round((float) $request->paid_amount, 2);
                if ($amount > $owed + 0.009) {
                    throw new \DomainException('Amount exceeds the outstanding balance of UGX ' . number_format($owed, 0) . '.');
                }

                $newPaid = round((float) $payment->paid_amount + $amount, 2);
                $payment->update([
                    'paid_amount' => $newPaid,
                    'payment_status' => $newPaid + 0.009 >= (float) $payment->total_amount ? 'Paid' : 'Partial',
                    'payment_date' => now()->toDateString(),
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $request->payment_reference ?: $payment->payment_reference,
                    'transaction_id' => $request->transaction_id ?: $payment->transaction_id,
                    'paid_by' => (string) $admin->id,
                    'updated_by' => (string) $admin->id,
                ]);

                return $payment;
            });

            return $this->ok('Payment recorded successfully.', $payment->fresh()->load(self::WITH));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to record the payment.');
        }
    }

    /** Admin only. */
    public function getOverdue(Request $request)
    {
        try {
            $query = InsuranceSubscriptionPayment::with(self::WITH)->where('payment_status', 'Overdue');
            foreach (['user_id', 'insurance_program_id'] as $f) {
                if ($request->filled($f)) {
                    $query->where($f, $request->get($f));
                }
            }
            $query->orderBy('days_overdue', 'desc');

            return $this->paged($query->paginate($this->perPage($request)), 'Overdue payments retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to retrieve overdue payments.');
        }
    }

    public function getUserPayments(Request $request, $userId)
    {
        try {
            if (!$this->canAccessUser($this->actor($request), $userId)) {
                return $this->forbidden();
            }

            $query = InsuranceSubscriptionPayment::with(['insuranceSubscription.insuranceProgram'])->where('user_id', $userId);
            $this->applyCommonFilters($query, $request);
            $query->orderBy($this->sortColumn($request, self::SORTABLE, 'due_date'), $this->sortDirection($request));

            return $this->paged($query->paginate($this->perPage($request)), 'User payments retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to retrieve user payments.');
        }
    }

    /** Admin only. */
    public function stats(Request $request)
    {
        try {
            $query = InsuranceSubscriptionPayment::query();
            foreach (['user_id', 'insurance_subscription_id', 'insurance_program_id'] as $f) {
                if ($request->filled($f)) {
                    $query->where($f, $request->get($f));
                }
            }

            $counted = (clone $query)->where('payment_status', '!=', 'Waived');
            $totalAmount = (clone $counted)->sum('total_amount');
            $totalPaid = (clone $counted)->sum('paid_amount');

            return $this->ok('Payment statistics retrieved successfully.', [
                'total_payments' => (clone $query)->count(),
                'paid_payments' => (clone $query)->where('payment_status', 'Paid')->count(),
                'pending_payments' => (clone $query)->where('payment_status', 'Pending')->count(),
                'overdue_payments' => (clone $query)->where('payment_status', 'Overdue')->count(),
                'partial_payments' => (clone $query)->where('payment_status', 'Partial')->count(),
                'waived_payments' => (clone $query)->where('payment_status', 'Waived')->count(),
                'total_amount' => $totalAmount,
                'total_paid' => $totalPaid,
                'total_penalty' => (clone $counted)->sum('penalty_amount'),
                'total_balance' => max(0, $totalAmount - $totalPaid),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to retrieve statistics.');
        }
    }
}
