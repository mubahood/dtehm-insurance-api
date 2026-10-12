<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponds;
use App\Models\InsuranceProgram;
use App\Models\InsuranceSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Member-facing insurance: one call returns everything the app's Insurance screens show,
 * calculated on the server so every screen shows the same months and amounts.
 */
class MyInsuranceController extends Controller
{
    use ApiResponds;

    /** GET /api/insurance/my */
    public function my(Request $request)
    {
        try {
            $me = $this->actor($request);

            $policies = InsuranceSubscription::with('insuranceProgram')
                ->where('user_id', $me->id)
                ->orderByRaw("FIELD(status, 'Active', 'Suspended', 'Pending', 'Expired', 'Cancelled')")
                ->orderByDesc('start_date')
                ->get()
                ->map(fn ($p) => $p->summary(false))
                ->values();

            $activeProgramIds = InsuranceSubscription::where('user_id', $me->id)
                ->where('status', 'Active')->pluck('insurance_program_id')->all();

            $programs = InsuranceProgram::where('status', 'Active')->orderBy('premium_amount')->get()
                ->map(fn ($program) => $this->offer($program, in_array($program->id, $activeProgramIds)))
                ->values();

            $open = $policies->whereIn('status', ['Active', 'Suspended']);

            return $this->ok('OK', [
                'policies' => $policies,
                'programs' => $programs,
                'totals' => [
                    'active_policies' => $open->count(),
                    'due_now_amount' => round($open->sum('due_now_amount'), 2),
                    'paid_amount' => round($policies->sum('paid_amount'), 2),
                    'balance' => round($open->sum('balance'), 2),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Could not load your insurance. Please try again.');
        }
    }

    /** GET /api/insurance/policies/{id} - one policy with every month */
    public function policy(Request $request, $id)
    {
        try {
            $policy = InsuranceSubscription::with('insuranceProgram')->findOrFail($id);
            if (!$this->canAccessUser($this->actor($request), $policy->user_id)) {
                return $this->forbidden();
            }
            return $this->ok('OK', $policy->summary(true));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Could not load this policy.');
        }
    }

    /** What joining a plan TODAY looks like: months, dates and money, from the same schedule the server will create. */
    private function offer(InsuranceProgram $program, bool $alreadyMember): array
    {
        $enrol = $program->enrollmentStatus();
        $schedule = InsuranceSubscription::buildSchedule(Carbon::today(), $program);
        $periods = $schedule['periods'];
        $label = function ($date) use ($program) {
            $d = Carbon::parse($date);
            return $program->billing_frequency === 'Weekly' ? 'Week of ' . $d->format('j M Y') : $d->format('M Y');
        };

        return [
            'id' => $program->id,
            'name' => $program->name,
            'description' => $program->description,
            'benefits' => is_array($program->benefits) ? array_values($program->benefits) : [],
            'premium_amount' => (float) $program->premium_amount,
            'billing_frequency' => $program->billing_frequency,
            'duration_months' => (int) $program->duration_months,
            'coverage_amount' => (float) $program->coverage_amount,
            'grace_period_days' => (int) $program->grace_period_days,
            'late_fee' => (float) $program->late_payment_penalty,
            'late_fee_type' => $program->penalty_type,
            'enrollment_open' => $enrol['open'] && !$alreadyMember,
            'closed_reason' => $alreadyMember ? 'You already have this cover.' : $enrol['reason'],
            'already_member' => $alreadyMember,
            'preview' => [
                'start_date' => $schedule['start_date'],
                'end_date' => $schedule['end_date'],
                'payments' => count($periods),
                'first_month' => $periods ? $label($periods[0]['start']) : null,
                'last_month' => $periods ? $label(end($periods)['start']) : null,
                'total_amount' => round(count($periods) * (float) $program->premium_amount, 2),
                'first_payment_due' => $schedule['start_date'],
            ],
        ];
    }
}
