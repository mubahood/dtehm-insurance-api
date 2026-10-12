<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponds;
use App\Models\InsuranceProgram;
use App\Models\InsuranceSubscription;
use App\Models\InsuranceSubscriptionPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Insurance subscriptions.
 * Routes are behind auth.jwt; admin-only actions are behind api.admin (see routes/api.php).
 * Members can only ever read/create/cancel their OWN subscriptions.
 */
class InsuranceSubscriptionController extends Controller
{
    use ApiResponds;

    private const USER_COLUMNS = 'user:id,name,first_name,last_name,phone_number,email,business_name,dtehm_member_id,avatar';

    private const SORTABLE = ['created_at', 'start_date', 'end_date', 'status', 'premium_amount', 'policy_number', 'id'];

    public function index(Request $request)
    {
        try {
            $actor = $this->actor($request);
            if (!$actor) {
                return $this->fail('User not authenticated', 401);
            }

            $query = InsuranceSubscription::with([self::USER_COLUMNS, 'insuranceProgram']);

            if (!$this->actorIsAdmin($actor)) {
                $query->where('user_id', $actor->id);
            } elseif ($request->filled('user_id')) {
                $query->where('user_id', (int) $request->user_id);
            }

            foreach (['insurance_program_id', 'status', 'payment_status', 'coverage_status'] as $filter) {
                if ($request->filled($filter)) {
                    $query->where($filter, $request->get($filter));
                }
            }
            if ($request->filled('policy_number')) {
                $query->where('policy_number', 'like', '%' . $request->policy_number . '%');
            }

            $query->orderBy($this->sortColumn($request, self::SORTABLE), $this->sortDirection($request));
            $page = $query->paginate($this->perPage($request));

            return $this->ok('Insurance subscriptions retrieved successfully.', $page->items(), 200, [
                'pagination' => [
                    'total' => $page->total(),
                    'per_page' => $page->perPage(),
                    'current_page' => $page->currentPage(),
                    'last_page' => $page->lastPage(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to retrieve insurance subscriptions.');
        }
    }

    public function store(Request $request)
    {
        try {
            $actor = $this->actor($request);
            if (!$actor) {
                return $this->fail('User not authenticated', 401);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'nullable|exists:users,id',
                'insurance_program_id' => 'required|exists:insurance_programs,id',
                'start_date' => 'required|date',
                'beneficiaries' => 'nullable|json',
                'notes' => 'nullable|string|max:2000',
            ]);
            if ($validator->fails()) {
                return $this->validationFailed($validator);
            }

            // Members can only enrol themselves. Admins may enrol someone else.
            $ownerId = $actor->id;
            if ($request->filled('user_id') && (int) $request->user_id !== (int) $actor->id) {
                if (!$this->actorIsAdmin($actor)) {
                    return $this->forbidden('You can only enrol yourself.');
                }
                $ownerId = (int) $request->user_id;
            }

            $program = InsuranceProgram::findOrFail($request->insurance_program_id);
            if (!$program->isAvailableForEnrollment()) {
                return $this->fail('This insurance program is not currently available for enrollment.', 400);
            }

            $start = date('Y-m-d', strtotime($request->start_date));
            if ($start < date('Y-m-d', strtotime('-1 day'))) {
                return $this->fail('Start date cannot be in the past.', 422);
            }
            $end = date('Y-m-d', strtotime($start . ' + ' . (int) $program->duration_months . ' months'));

            // One transaction + row lock on the user: two parallel taps cannot both pass the
            // duplicate check, and a failure while generating payment rows rolls everything back.
            $subscription = DB::transaction(function () use ($request, $ownerId, $program, $start, $end, $actor) {
                User::where('id', $ownerId)->lockForUpdate()->first();

                return InsuranceSubscription::create([
                    'user_id' => $ownerId,
                    'insurance_program_id' => $program->id,
                    'start_date' => $start,
                    'end_date' => $end,
                    'coverage_start_date' => $start,
                    'coverage_end_date' => $end,
                    'premium_amount' => $program->premium_amount,
                    'status' => 'Active',
                    'payment_status' => 'Current',
                    'coverage_status' => 'Active',
                    'beneficiaries' => $request->beneficiaries,
                    'notes' => $request->notes,
                    'created_by' => $actor->id,
                ]);
            });

            return $this->ok(
                'Insurance subscription created successfully.',
                $subscription->load([self::USER_COLUMNS, 'insuranceProgram']),
                201
            );
        } catch (\Throwable $e) {
            // Model validation throws plain exceptions with user-readable text for these cases only.
            if (str_contains($e->getMessage(), 'already has an active')) {
                return $this->fail($e->getMessage(), 400, ['error' => $e->getMessage()]);
            }
            return $this->serverError($e, 'Could not create the subscription. Please try again.');
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $actor = $this->actor($request);
            $subscription = InsuranceSubscription::with([
                self::USER_COLUMNS,
                'insuranceProgram',
                'payments' => function ($q) {
                    $q->orderBy('due_date', 'asc');
                },
            ])->findOrFail($id);

            if (!$this->canAccessUser($actor, $subscription->user_id)) {
                return $this->forbidden();
            }

            return $this->ok('Insurance subscription retrieved successfully.', $subscription);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to retrieve the subscription.');
        }
    }

    /** Admin only. Only descriptive / status fields can change; money and ownership cannot. */
    public function update(Request $request, $id)
    {
        try {
            $subscription = InsuranceSubscription::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|in:Active,Suspended,Cancelled,Expired,Pending',
                'payment_status' => 'sometimes|in:Current,Late,Defaulted',
                'coverage_status' => 'sometimes|in:Active,Suspended,Terminated',
                'beneficiaries' => 'nullable|json',
                'notes' => 'nullable|string|max:2000',
            ]);
            if ($validator->fails()) {
                return $this->validationFailed($validator);
            }

            $data = $request->only(['status', 'payment_status', 'coverage_status', 'beneficiaries', 'notes']);
            $data['updated_by'] = $this->actor($request)->id;
            $subscription->update($data);

            return $this->ok('Insurance subscription updated successfully.', $subscription->fresh()->load([self::USER_COLUMNS, 'insuranceProgram']));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to update the subscription.');
        }
    }

    /** Admin only. Refuses to delete a subscription that already has money paid against it. */
    public function destroy($id)
    {
        try {
            $subscription = InsuranceSubscription::findOrFail($id);

            $hasPaid = InsuranceSubscriptionPayment::where('insurance_subscription_id', $subscription->id)
                ->where('paid_amount', '>', 0)->exists();
            if ($hasPaid) {
                return $this->fail('This subscription has payments recorded. Cancel it instead of deleting it.', 409);
            }

            $subscription->delete();
            return $this->ok('Insurance subscription deleted successfully.');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to delete the subscription.');
        }
    }

    public function suspend(Request $request, $id)
    {
        try {
            $subscription = InsuranceSubscription::findOrFail($id);
            $validator = Validator::make($request->all(), ['suspension_reason' => 'required|string|max:1000']);
            if ($validator->fails()) {
                return $this->validationFailed($validator);
            }
            if ($subscription->status !== 'Active') {
                return $this->fail('Only active subscriptions can be suspended.', 400);
            }

            $subscription->update([
                'status' => 'Suspended',
                'coverage_status' => 'Suspended',
                'suspended_date' => now(),
                'suspension_reason' => $request->suspension_reason,
            ]);

            return $this->ok('Insurance subscription suspended successfully.', $subscription->fresh());
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to suspend the subscription.');
        }
    }

    public function activate($id)
    {
        try {
            $subscription = InsuranceSubscription::findOrFail($id);
            if ($subscription->status !== 'Suspended') {
                return $this->fail('Only suspended subscriptions can be activated.', 400);
            }

            $subscription->update([
                'status' => 'Active',
                'coverage_status' => 'Active',
                'suspended_date' => null,
                'suspension_reason' => null,
            ]);

            return $this->ok('Insurance subscription activated successfully.', $subscription->fresh());
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to activate the subscription.');
        }
    }

    /**
     * Owner or admin. Stops future billing: unpaid rows are Waived so they no longer accrue
     * penalties or count as owed. Paid / partially paid history is kept untouched.
     */
    public function cancel(Request $request, $id)
    {
        try {
            $actor = $this->actor($request);
            $subscription = InsuranceSubscription::findOrFail($id);

            if (!$this->canAccessUser($actor, $subscription->user_id)) {
                return $this->forbidden();
            }

            $validator = Validator::make($request->all(), ['cancellation_reason' => 'required|string|max:1000']);
            if ($validator->fails()) {
                return $this->validationFailed($validator);
            }
            if (in_array($subscription->status, ['Cancelled', 'Expired'], true)) {
                return $this->fail('This subscription is already ' . strtolower($subscription->status) . '.', 400);
            }

            DB::transaction(function () use ($subscription, $request) {
                InsuranceSubscriptionPayment::where('insurance_subscription_id', $subscription->id)
                    ->whereIn('payment_status', ['Pending', 'Overdue'])
                    ->where('paid_amount', '<=', 0)
                    ->get()
                    ->each(function ($p) {
                        $p->update(['payment_status' => 'Waived', 'notes' => trim(($p->notes ? $p->notes . ' | ' : '') . 'Waived: subscription cancelled')]);
                    });

                $subscription->update([
                    'status' => 'Cancelled',
                    'coverage_status' => 'Terminated',
                    'cancelled_date' => now(),
                    'cancellation_reason' => $request->cancellation_reason,
                ]);
            });

            return $this->ok('Insurance subscription cancelled successfully.', $subscription->fresh());
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to cancel the subscription.');
        }
    }

    public function getUserSubscription(Request $request, $userId)
    {
        try {
            if (!$this->canAccessUser($this->actor($request), $userId)) {
                return $this->forbidden();
            }

            $subscription = InsuranceSubscription::with([
                'insuranceProgram',
                'payments' => function ($q) {
                    $q->orderBy('due_date', 'desc')->limit(10);
                },
            ])->where('user_id', $userId)->where('status', 'Active')->first();

            if (!$subscription) {
                return $this->fail('No active subscription found for this user.', 404);
            }

            return $this->ok('User subscription retrieved successfully.', $subscription);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to retrieve the subscription.');
        }
    }
}
