<?php

namespace App\Admin\Controllers;

use App\Models\AccountPin;
use App\Models\AccountPinLog;
use App\Models\User;
use App\Models\Utils;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;

/**
 * Withdraw PINs: the office can see every member's PIN status and set, generate (and SMS),
 * unlock or remove a PIN. PINs are stored hashed; a generated PIN is shown once only.
 */
class WithdrawPinController extends AdminController
{
    protected $title = 'Withdraw PINs';

    private function adminId(): int
    {
        return (int) Admin::user()->id;
    }

    /** SMS sender (tests bind 'withdraw-pin.sms' so no real SMS goes out). */
    private function sms(?string $phone, string $message): bool
    {
        if (!$phone) {
            return false;
        }
        try {
            if (app()->bound('withdraw-pin.sms')) {
                return (bool) app('withdraw-pin.sms')($phone, $message);
            }
            $r = Utils::sendSMS($phone, $message);
            return is_object($r) ? !empty($r->success) : !empty($r['success'] ?? false);
        } catch (\Throwable $e) {
            \Log::error('PIN SMS failed: ' . $e->getMessage());
            return false;
        }
    }

    private function back(int $userId, string $msg, string $type = 'success')
    {
        admin_toastr($msg, $type, ['timeOut' => 8000]);
        return redirect(admin_url('withdraw-pins/' . $userId));
    }

    public function index(Content $content)
    {
        $q = trim((string) request('q', ''));
        $results = collect();
        if ($q !== '') {
            $results = User::where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('phone_number', 'like', "%{$q}%")
                    ->orWhere('dtehm_member_id', 'like', "%{$q}%")->orWhere('business_name', 'like', "%{$q}%");
            })->orderBy('name')->limit(30)->get(['id', 'name', 'phone_number', 'dtehm_member_id', 'business_name']);
        }
        $pins = AccountPin::whereIn('user_id', $results->pluck('id'))->get()->keyBy('user_id');

        $locked = AccountPin::with('user:id,name,phone_number,dtehm_member_id')
            ->where('locked_until', '>', now())->orderBy('locked_until')->get();
        $struggling = AccountPin::with('user:id,name,phone_number,dtehm_member_id')
            ->where('failed_attempts', '>', 0)->where(function ($w) {
                $w->whereNull('locked_until')->orWhere('locked_until', '<=', now());
            })->orderByDesc('failed_attempts')->limit(10)->get();
        $activity = AccountPinLog::with(['user:id,name', 'actor:id,name'])->orderByDesc('id')->limit(25)->get();

        $kpi = [
            'with_pin' => AccountPin::count(),
            'locked' => $locked->count(),
            'members' => User::where(function ($w) {
                $w->where('is_dtehm_member', 'Yes')->orWhere('is_dip_member', 'Yes');
            })->count(),
            'changed_30d' => AccountPinLog::where('created_at', '>=', now()->subDays(30))->whereIn('action', ['admin_set', 'admin_generated'])->count(),
        ];

        return $content->title('Withdraw PINs')->description('See, set, reset and unlock members\' withdraw PINs')
            ->body(view('admin.withdraw-pins.index', compact('q', 'results', 'pins', 'locked', 'struggling', 'activity', 'kpi')));
    }

    public function show($userId, Content $content)
    {
        $user = User::findOrFail($userId);
        $pin = AccountPin::where('user_id', $user->id)->first();
        $history = AccountPinLog::with('actor:id,name')->where('user_id', $user->id)->orderByDesc('id')->limit(50)->get();
        $generated = session('generated_pin'); // shown once, right after "Generate"
        if ($generated && (int) ($generated['user_id'] ?? 0) !== (int) $user->id) {
            $generated = null;
        }
        $balance = (float) $user->calculateAccountBalance();
        $pendingWithdrawals = \App\Models\WithdrawRequest::where('user_id', $user->id)->where('status', 'pending')->count();

        return $content->title('Withdraw PIN - ' . $user->name)->description($user->phone_number)
            ->body(view('admin.withdraw-pins.show', compact('user', 'pin', 'history', 'generated', 'balance', 'pendingWithdrawals')));
    }

    /** Set a PIN chosen by the office (e.g. the member is standing at the desk). */
    public function set(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $pin = (string) $request->input('pin', '');
        if (!preg_match('/^\d{4}$/', $pin)) {
            return $this->back($user->id, 'The PIN must be exactly 4 digits.', 'error');
        }
        if ($pin !== (string) $request->input('pin_confirmation', '')) {
            return $this->back($user->id, 'The two PINs do not match.', 'error');
        }
        if (AccountPin::isWeak($pin)) {
            return $this->back($user->id, 'That PIN is too easy to guess (like 1234 or 0000). Choose another.', 'error');
        }

        AccountPin::adminSet($user->id, $pin, $this->adminId(), 'admin_set', $this->note($request));
        $sent = null;
        if ($request->boolean('send_sms')) {
            $sent = $this->sms($user->phone_number, "DTEHM: Your withdraw PIN has been set by the office. Your PIN is {$pin}. Do not share it with anyone.");
            $this->markLastLogSms($user->id, $sent);
        } else {
            $this->sms($user->phone_number, 'DTEHM: Your withdraw PIN was changed by the office. If you did not ask for this, contact DTEHM immediately.');
        }
        return $this->back($user->id, 'PIN set for ' . $user->name . '.' . ($sent === null ? '' : ($sent ? ' The PIN was sent by SMS.' : ' The SMS could not be sent - tell the member the PIN directly.')), $sent === false ? 'warning' : 'success');
    }

    /** Generate a random safe PIN, SMS it, and show it once on screen. */
    public function generate(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $pin = AccountPin::generatePin();
        AccountPin::adminSet($user->id, $pin, $this->adminId(), 'admin_generated', $this->note($request));
        $sent = $this->sms($user->phone_number, "DTEHM: Your new withdraw PIN is {$pin}. Do not share it with anyone. You can change it in the app (My Account > PIN).");
        $this->markLastLogSms($user->id, $sent);

        session()->flash('generated_pin', ['user_id' => $user->id, 'pin' => $pin, 'sms' => $sent]);
        return $this->back($user->id, $sent ? 'New PIN generated and sent to ' . $user->phone_number . '.' : 'New PIN generated, but the SMS failed - read it to the member (shown once below).', $sent ? 'success' : 'warning');
    }

    public function unlock(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $pin = AccountPin::adminUnlock($user->id, $this->adminId(), $this->note($request));
        return $pin
            ? $this->back($user->id, 'PIN unlocked. ' . $user->name . ' can try again now.')
            : $this->back($user->id, 'This member has no PIN to unlock.', 'error');
    }

    public function remove(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        if (!AccountPin::adminRemove($user->id, $this->adminId(), $this->note($request))) {
            return $this->back($user->id, 'This member has no PIN.', 'error');
        }
        $this->sms($user->phone_number, 'DTEHM: Your withdraw PIN was reset by the office. Open the app to create a new PIN before your next withdrawal.');
        return $this->back($user->id, 'PIN removed. The app will ask ' . $user->name . ' to create a new one.');
    }

    private function note(Request $request): ?string
    {
        $n = trim((string) $request->input('note', ''));
        return $n === '' ? null : mb_substr($n, 0, 500);
    }

    private function markLastLogSms(int $userId, bool $sent): void
    {
        AccountPinLog::where('user_id', $userId)->orderByDesc('id')->limit(1)->update(['sms_sent' => $sent]);
    }
}
