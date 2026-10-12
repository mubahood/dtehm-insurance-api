<?php

namespace App\Admin\Controllers;

use App\Models\InsuranceProgram;
use App\Models\InsuranceSubscription;
use App\Models\InsuranceSubscriptionPayment;
use App\Models\UniversalPayment;
use App\Models\User;
use App\Services\PesapalService;
use App\Services\UniversalPaymentReconciler;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Insurance Control Center: one page to see who owes what and act on it.
 * Every number comes from InsuranceSubscription::summary(), the same source as the member app.
 */
class InsuranceCenterController extends AdminController
{
    protected $title = 'Insurance Control Center';

    private function staff(): User
    {
        $u = Admin::user() ? User::find(Admin::user()->id) : null;
        abort_unless($u && $u->isAdmin(), 403, 'Admin or manager role required.');
        return $u;
    }

    private function done(string $msg, string $type = 'success')
    {
        admin_toastr($msg, $type, ['timeOut' => 6000]);
        return back();
    }

    public function index(Content $content)
    {
        $this->staff();
        $request = request();
        $q = trim((string) $request->get('q', ''));
        $state = (string) $request->get('state', 'attention');

        $query = InsuranceSubscription::with(['user:id,name,phone_number,dtehm_member_id', 'insuranceProgram'])
            ->whereIn('status', ['Active', 'Suspended']);
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('policy_number', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")->orWhere('phone_number', 'like', "%{$q}%")->orWhere('dtehm_member_id', 'like', "%{$q}%");
                    });
            });
        }
        $rows = $query->get()->map(function ($p) {
            return ['policy' => $p, 's' => $p->summary(false)];
        });

        $all = $rows;
        if ($state === 'attention') {
            $rows = $rows->filter(fn ($r) => in_array($r['s']['state'], ['overdue', 'due'], true));
        } elseif ($state !== 'all') {
            $rows = $rows->filter(fn ($r) => $r['s']['state'] === $state);
        }
        $rows = $rows->sortByDesc(fn ($r) => [$r['s']['overdue_months'], $r['s']['due_now_amount']])->values();

        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $kpi = [
            'active' => $all->count(),
            'due_now' => $all->sum(fn ($r) => $r['s']['due_now_amount']),
            'overdue_members' => $all->filter(fn ($r) => $r['s']['overdue_months'] > 0)->count(),
            'collected_month' => (float) InsuranceSubscriptionPayment::where('payment_date', '>=', $monthStart)->sum('paid_amount'),
            'expected_year' => $all->sum(fn ($r) => $r['s']['balance']),
        ];

        $programs = InsuranceProgram::orderBy('name')->get()->map(fn ($p) => ['p' => $p, 'e' => $p->enrollmentStatus()]);

        $openPayments = UniversalPayment::with('user:id,name,phone_number')
            ->where('items_processed', false)
            ->whereIn('status', ['PENDING', 'PROCESSING', 'COMPLETED'])
            ->whereNotNull('pesapal_order_tracking_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->orderByDesc('id')->limit(30)->get()
            ->filter(fn ($p) => $p->usesUniversalProcessing())->values();

        return $content->title('Insurance Control Center')
            ->description('Who owes what, and one-click actions')
            ->body(view('admin.insurance-center.index', compact('rows', 'kpi', 'programs', 'openPayments', 'q', 'state')));
    }

    public function policy($id, Content $content)
    {
        $this->staff();
        $policy = InsuranceSubscription::with(['user', 'insuranceProgram'])->findOrFail($id);
        $s = $policy->summary(true);
        $deletedMonths = InsuranceSubscriptionPayment::onlyTrashed()->where('insurance_subscription_id', $policy->id)->pluck('period_name');

        return $content->title('Policy ' . $policy->policy_number)
            ->description(optional($policy->user)->name)
            ->body(view('admin.insurance-center.policy', compact('policy', 's', 'deletedMonths')));
    }

    public function pay(Request $request, $id)
    {
        $staff = $this->staff();
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|in:Cash,Mobile Money,Bank Transfer,Cheque,Other',
            'reference' => 'nullable|string|max:255',
        ]);
        try {
            $r = InsuranceSubscription::findOrFail($id)->recordOfficePayment((float) $data['amount'], $data['method'], $data['reference'] ?? null, $staff->id);
            return $this->done('UGX ' . number_format($r['amount'], 0) . ' recorded on ' . implode(', ', $r['months']));
        } catch (\DomainException $e) {
            return $this->done($e->getMessage(), 'error');
        }
    }

    public function status(Request $request, $id)
    {
        $this->staff();
        $data = $request->validate(['action' => 'required|in:suspend,activate,cancel', 'reason' => 'nullable|string|max:1000']);
        $policy = InsuranceSubscription::findOrFail($id);

        if ($data['action'] === 'suspend') {
            if ($policy->status !== 'Active') return $this->done('Only active policies can be suspended.', 'error');
            $policy->update(['status' => 'Suspended', 'coverage_status' => 'Suspended', 'suspended_date' => now(), 'suspension_reason' => $data['reason'] ?: 'Suspended by office']);
            return $this->done('Policy suspended.');
        }
        if ($data['action'] === 'activate') {
            if ($policy->status !== 'Suspended') return $this->done('Only suspended policies can be re-activated.', 'error');
            $policy->update(['status' => 'Active', 'coverage_status' => 'Active', 'suspended_date' => null, 'suspension_reason' => null]);
            return $this->done('Policy re-activated.');
        }
        if (in_array($policy->status, ['Cancelled', 'Expired'], true)) return $this->done('Policy is already ' . strtolower($policy->status) . '.', 'error');
        DB::transaction(function () use ($policy, $data) {
            InsuranceSubscriptionPayment::where('insurance_subscription_id', $policy->id)
                ->whereIn('payment_status', ['Pending', 'Overdue'])->where('paid_amount', '<=', 0)->get()
                ->each(fn ($p) => $p->update(['payment_status' => 'Waived', 'notes' => trim(($p->notes ? $p->notes . ' | ' : '') . 'Waived: policy cancelled')]));
            $policy->update(['status' => 'Cancelled', 'coverage_status' => 'Terminated', 'cancelled_date' => now(), 'cancellation_reason' => $data['reason'] ?: 'Cancelled by office']);
        });
        return $this->done('Policy cancelled; unpaid months will not be charged.');
    }

    public function waive(Request $request, $id)
    {
        $staff = $this->staff();
        $row = InsuranceSubscriptionPayment::findOrFail($id);
        if (!in_array($row->payment_status, ['Pending', 'Overdue'], true) || (float) $row->paid_amount > 0) {
            return $this->done('Only unpaid months can be waived (this one is ' . strtolower($row->payment_status) . ').', 'error');
        }
        $reason = trim((string) $request->get('reason', '')) ?: 'Waived by office';
        $row->update(['payment_status' => 'Waived', 'updated_by' => (string) $staff->id, 'notes' => trim(($row->notes ? $row->notes . ' | ' : '') . $reason)]);
        return $this->done($row->period_name . ' waived.');
    }

    public function removeLateFee($id)
    {
        $staff = $this->staff();
        $row = InsuranceSubscriptionPayment::findOrFail($id);
        if ((float) $row->penalty_amount <= 0 || $row->payment_status === 'Paid') {
            return $this->done('No late fee to remove on this month.', 'error');
        }
        DB::table('insurance_subscription_payments')->where('id', $row->id)->update([
            'penalty_amount' => 0, 'total_amount' => $row->amount, 'updated_by' => (string) $staff->id, 'updated_at' => now(),
            'payment_status' => (float) $row->paid_amount + 0.009 >= (float) $row->amount ? 'Paid' : $row->payment_status,
        ]);
        InsuranceSubscription::find($row->insurance_subscription_id)->updateBalances();
        return $this->done('Late fee removed from ' . $row->period_name . '.');
    }

    public function tool(Request $request, $name)
    {
        $this->staff();
        $map = [
            'maintain' => ['insurance:maintain', []],
            'repair-preview' => ['insurance:repair', []],
            'repair-apply' => ['insurance:repair', ['--apply' => true]],
            'reconcile' => ['payments:reconcile', ['--days' => 30]],
        ];
        abort_unless(isset($map[$name]), 404);
        Artisan::call($map[$name][0], $map[$name][1]);
        $out = trim(Artisan::output());
        return back()->with('tool_output', ['name' => $name, 'output' => $out ?: 'Done.']);
    }

    public function setEnrolment(Request $request, $id)
    {
        $this->staff();
        $program = InsuranceProgram::findOrFail($id);
        $open = $request->get('open') === '1';
        DB::table('insurance_programs')->where('id', $program->id)->update(
            $open ? ['end_date' => null, 'status' => 'Active', 'updated_at' => now()] : ['end_date' => now()->subDay()->toDateString(), 'updated_at' => now()]
        );
        return $this->done($program->name . ($open ? ': enrolment opened.' : ': enrolment closed.'));
    }

    public function recheck($id)
    {
        $this->staff();
        $payment = UniversalPayment::findOrFail($id);
        $r = app(UniversalPaymentReconciler::class)->sync($payment, app(PesapalService::class));
        $msg = 'Payment #' . $payment->id . ': ' . $r['status'] . ($r['processed'] ? ' - applied to the account.' : '');
        return $this->done($r['error'] ? $msg . ' (Pesapal could not be reached, try again)' : $msg, $r['error'] ? 'warning' : 'success');
    }
}
