<?php

namespace App\Admin\Controllers;

use App\Models\AccountPin;
use App\Models\DtehmMembership;
use App\Models\MembershipPayment;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DtehmMemberController extends AdminController
{
    protected $title = 'DTEHM Members';

    // ─────────────────────────────────────────────────────────────────────────
    // GRID
    // ─────────────────────────────────────────────────────────────────────────
    protected function grid()
    {
        $grid = new Grid(new User());

        $grid->model()
            ->where('is_dtehm_member', 'Yes')
            ->with('accountPin')
            ->orderBy('dtehm_member_id', 'asc');

        $grid->column('id', __('ID'))->sortable()->width(60);

        $grid->column('dtehm_member_id', __('DTEHM ID'))
            ->display(function ($dtehmId) {
                return $dtehmId
                    ? "<span class='label label-success' style='font-size:12px;letter-spacing:1px;'>{$dtehmId}</span>"
                    : '<span class="text-muted">—</span>';
            })
            ->sortable()->width(120);

        $grid->column('name', __('Member Name'))
            ->display(function () {
                $name = trim($this->first_name . ' ' . $this->last_name);
                $shopHtml = '';
                if ($this->shop_id) {
                    $shop = \App\Models\User::find($this->shop_id);
                    if ($shop) {
                        $shopHtml = "<br><span style='background:#00c0ef;color:#fff;font-size:10px;padding:1px 6px;border-radius:3px;'>"
                            . "<i class='fa fa-building' style='margin-right:3px;'></i>"
                            . e($shop->name)
                            . "</span>";
                    }
                }
                return "<strong>{$name}</strong><br><small class='text-muted'>{$this->phone_number}</small>{$shopHtml}";
            })->width(220);

        // $grid->column('sex', __('Gender'))
        //     ->label(['Male' => 'info', 'Female' => 'danger'])
        //     ->width(80);

        $grid->column('sponsor_id', __('Sponsor'))
            ->display(function () {
                if (empty($this->sponsor_id)) {
                    return '<span class="text-muted">—</span>';
                }
                $sponsor = User::where('dtehm_member_id', $this->sponsor_id)
                    ->orWhere('business_name', $this->sponsor_id)
                    ->first();
                $name = $sponsor ? "<br><small>{$sponsor->first_name} {$sponsor->last_name}</small>" : '';
                return "<span class='label label-primary'>{$this->sponsor_id}</span>{$name}";
            })->width(150);

        $grid->column('is_dip_member', __('Also DIP?'))
            ->label(['Yes' => 'info', 'No' => 'default'])
            ->width(90);

        $grid->column('dtehm_payment_status', __('DTEHM Payment'))
            ->display(function () {
                $payment = DtehmMembership::where('user_id', $this->id)
                    ->orderBy('id', 'desc')->first();
                if (!$payment) {
                    return "<span class='label label-default'>No Record</span>";
                }
                $map = ['CONFIRMED' => 'success', 'PENDING' => 'warning', 'FAILED' => 'danger', 'REFUNDED' => 'secondary'];
                $color = $map[$payment->status] ?? 'default';
                return "<span class='label label-{$color}'>{$payment->status}</span><br>
                        <small>UGX " . number_format($payment->amount, 0) . "</small>";
            })->width(140);

        $grid->column('dtehm_member_membership_date', __('Member Since'))
            ->display(function ($d) {
                return $d ? date('M d, Y', strtotime($d)) : '—';
            })->sortable()->width(120);

        $grid->column('account_balance', __('Balance (UGX)'))
            ->display(function ($v) {
                return number_format($v ?? 0, 0);
            })->sortable()->width(120);

        // ── Withdraw PIN status ──────────────────────────────────────────────
        $grid->column('admin_withdraw_pin', __('Withdraw PIN'))
            ->display(function () {
                $pin = $this->accountPin;
                if (!$pin) {
                    return "<span class='label label-default'>Not Set</span>";
                }
                if ($pin->isLocked()) {
                    $until = $pin->locked_until ? date('d/m H:i', strtotime($pin->locked_until)) : '—';
                    return "<span class='label label-danger' title='Locked until {$until}'>Locked</span>";
                }
                if ($pin->failed_attempts > 0) {
                    return "<span class='label label-warning' title='{$pin->failed_attempts} failed attempt(s)'>{$pin->failed_attempts} fails</span>";
                }
                $changed = $pin->last_changed_at ? date('d M Y', strtotime($pin->last_changed_at)) : '—';
                return "<span class='label label-success' title='Last changed: {$changed}'>Set ✓</span>";
            })
            ->width(100);

        // Shop inline-edit pencil (compact — shop name shown inside Member Name column above)
        $shopOptions = User::where('is_stockist', 'Yes')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn($u) => [$u->id => $u->name . ($u->dtehm_member_id ? " ({$u->dtehm_member_id})" : '')])
            ->toArray();

        $grid->column('shop_id', __('Shop'))
            ->display(function ($shopId) {
                return '<span class="text-muted" style="font-size:11px;">' . ($shopId ? '✓' : '—') . '</span>';
            })
            ->editable('select', [0 => '— None —'] + $shopOptions)
            ->width(50);

        $grid->column('total_points', __('Points'))
            ->display(function ($v) {
                return number_format($v ?? 0, 0);
            })->sortable()->width(80);

        $grid->column('created_at', __('Registered'))
            ->display(function ($d) {
                return $d ? date('M d, Y', strtotime($d)) : '—';
            })->sortable()->width(110);

        $grid->quickSearch('first_name', 'last_name', 'phone_number', 'dtehm_member_id')
            ->placeholder('Search by name, phone, or DTEHM ID');

        // Build shop options for filter
        $shopFilterOptions = User::where('is_stockist', 'Yes')->orderBy('name')
            ->get()->mapWithKeys(fn($u) => [$u->id => $u->name])->toArray();

        $grid->filter(function ($filter) use ($shopFilterOptions) {
            $filter->disableIdFilter();
            $filter->like('sponsor_id', 'Sponsor ID');
            $filter->equal('is_dip_member', 'Also DIP?')->select(['Yes' => 'Yes', 'No' => 'No']);
            $filter->equal('shop_id', 'Shop')->select(['' => 'All Shops'] + $shopFilterOptions);
            $filter->between('dtehm_member_membership_date', 'Member Since')->date();
            $filter->between('created_at', 'Registration Date')->date();
        });

        $grid->export(function ($export) {
            $export->filename('DTEHM_Members_' . date('Y-m-d'));
            $export->except(['deleted_at']);
        });

        $grid->actions(function ($actions) {
            $actions->disableDelete();
        });

        $grid->disableBatchActions();

        return $grid;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DETAIL
    // ─────────────────────────────────────────────────────────────────────────
    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->panel()->tools(function ($tools) {
            $tools->disableDelete();
        });

        $show->divider('DTEHM Member Identity');
        $show->field('dtehm_member_id', __('DTEHM ID'));
        $show->field('business_name', __('DIP ID'))->as(function ($v) { return $v ?: 'N/A'; });
        $show->field('id', __('System ID'));

        $show->divider('Personal Information');
        $show->field('first_name', __('First Name'));
        $show->field('last_name', __('Last Name'));
        $show->field('sex', __('Gender'));
        $show->field('phone_number', __('Phone Number'));
        $show->field('email', __('Email'));
        $show->field('address', __('Address'));
        $show->field('tribe', __('Tribe'));
        $show->field('country', __('Country'));

        $show->divider('Membership');
        $show->field('is_dtehm_member', __('DTEHM Member'));
        $show->field('is_dip_member', __('DIP Member'));
        $show->field('sponsor_id', __('Sponsor ID'));
        $show->field('dtehm_member_membership_date', __('Member Since'));
        $show->field('dtehm_membership_is_paid', __('Membership Paid?'));
        $show->field('account_balance', __('Balance (UGX)'))->as(function ($v) { return 'UGX ' . number_format($v ?? 0, 0); });
        $show->field('total_points', __('Points'));

        $show->divider('DTEHM Membership Payments (76,000 UGX)');
        $show->field('id', __('DTEHM Payments'))->as(function ($userId) {
            $payments = DtehmMembership::where('user_id', $userId)->orderBy('id', 'desc')->get();
            if ($payments->isEmpty()) return '<em>No DTEHM payment records</em>';
            $rows = '';
            foreach ($payments as $p) {
                $map = ['CONFIRMED' => 'success', 'PENDING' => 'warning', 'FAILED' => 'danger'];
                $color = $map[$p->status] ?? 'default';
                $rows .= "<tr>
                    <td>{$p->id}</td>
                    <td>{$p->payment_reference}</td>
                    <td>UGX " . number_format($p->amount, 0) . "</td>
                    <td><span class='label label-{$color}'>{$p->status}</span></td>
                    <td>{$p->payment_method}</td>
                    <td>" . ($p->payment_date ? date('M d, Y', strtotime($p->payment_date)) : '—') . "</td>
                </tr>";
            }
            return "<table class='table table-bordered table-sm' style='margin:0'>
                <thead><tr><th>ID</th><th>Reference</th><th>Amount</th><th>Status</th><th>Method</th><th>Date</th></tr></thead>
                <tbody>{$rows}</tbody>
            </table>";
        });

        $show->divider('DIP Membership Payments (20,000 UGX)');
        $show->field('id', __('DIP Payments'))->as(function ($userId) {
            $payments = MembershipPayment::where('user_id', $userId)->orderBy('id', 'desc')->get();
            if ($payments->isEmpty()) return '<em>No DIP payment records</em>';
            $rows = '';
            foreach ($payments as $p) {
                $map = ['CONFIRMED' => 'success', 'PENDING' => 'warning', 'FAILED' => 'danger'];
                $color = $map[$p->status] ?? 'default';
                $rows .= "<tr>
                    <td>{$p->id}</td>
                    <td>{$p->payment_reference}</td>
                    <td>UGX " . number_format($p->amount, 0) . "</td>
                    <td><span class='label label-{$color}'>{$p->status}</span></td>
                    <td>{$p->payment_method}</td>
                    <td>" . ($p->payment_date ? date('M d, Y', strtotime($p->payment_date)) : '—') . "</td>
                </tr>";
            }
            return "<table class='table table-bordered table-sm' style='margin:0'>
                <thead><tr><th>ID</th><th>Reference</th><th>Amount</th><th>Status</th><th>Method</th><th>Date</th></tr></thead>
                <tbody>{$rows}</tbody>
            </table>";
        });

        $show->divider('Audit');
        $show->field('created_at', __('Registered At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FORM
    // ─────────────────────────────────────────────────────────────────────────
    protected function form()
    {
        $form = new Form(new User());
        $form->disableCreatingCheck();
        $form->disableViewCheck();
        $form->disableReset();

        // ── CREATE ──────────────────────────────────────────────────────────
        if ($form->isCreating()) {

            $form->html('
                <div class="alert alert-success" style="background:#1a7e3b;color:#fff;border:none;">
                    <h4 style="margin:0 0 6px;"><i class="fa fa-id-card"></i> &nbsp;New DTEHM Member Registration</h4>
                    <p style="margin:0;">A DTEHM ID (<strong>DTEHM001</strong>, <strong>DTEHM002</strong>…) is auto-generated on save. &nbsp;
                    Membership fee: <strong>UGX 76,000</strong></p>
                </div>
            ');

            // Force DTEHM — always
            $form->hidden('is_dtehm_member')->value('Yes');
            $form->hidden('user_type')->value('Customer');
            $form->hidden('status')->value('Active');
            $form->hidden('country')->value('Uganda');

            // ── Personal Info ──────────────────────────────────────────────
            $form->divider('Personal Information');

            $form->row(function ($row) {
                $row->width(3)->text('first_name', 'First Name')
                    ->rules('required|min:2')
                    ->required()
                    ->placeholder('e.g. John');

                $row->width(3)->text('last_name', 'Last Name')
                    ->rules('required|min:2')
                    ->required()
                    ->placeholder('e.g. Doe');

                $row->width(3)->radio('sex', 'Gender')
                    ->options(['Male' => 'Male', 'Female' => 'Female'])
                    ->rules('required')
                    ->default('Male');

                $row->width(3)->text('phone_number', 'Phone Number')
                    ->rules('required|min:10|max:15')
                    ->required()
                    ->placeholder('e.g. 0701234567')
                    ->help('Must be unique. Used as login username.');
            });

            $form->row(function ($row) {
                $row->width(6)->text('email', 'Email (Optional)')
                    ->rules('nullable|email')
                    ->placeholder('member@example.com');

                $row->width(6)->text('address', 'Home Address (Optional)')
                    ->placeholder('e.g. Kasese Town, Uganda');
            });

            // ── Membership & Sponsor ───────────────────────────────────────
            $form->divider('Membership & Sponsor');

            $form->html('
                <div class="alert alert-info" style="margin-bottom:10px;">
                    <i class="fa fa-info-circle"></i>
                    <strong>DTEHM Member</strong> is auto-set to <code>Yes</code>.<br>
                    Sponsor must be an existing <strong>DTEHM member</strong> in the system.
                </div>
            ');

            $form->row(function ($row) {
                $sponsors = [];
                foreach (
                    User::where('is_dtehm_member', 'Yes')
                        ->whereNotNull('dtehm_member_id')
                        ->orderBy('dtehm_member_id', 'asc')
                        ->get() as $s
                ) {
                    $sponsors[$s->dtehm_member_id] = $s->dtehm_member_id . ' — ' . $s->first_name . ' ' . $s->last_name;
                }

                $row->width(4)->select('sponsor_id', 'Sponsor ID')
                    ->options($sponsors)
                    ->rules('required')
                    ->required()
                    ->help('Must be an existing DTEHM member');

                $row->width(4)->select('is_dip_member', 'Also DIP Member?')
                    ->options(['No' => 'No', 'Yes' => 'Yes'])
                    ->default('No')
                    ->rules('required')
                    ->help('Select Yes if member also pays DIP fee (UGX 20,000)');

                $row->width(4)->select('shop_id', 'Assigned Shop')
                    ->options(function () {
                        $opts = [0 => '— None —'];
                        foreach (User::where('is_stockist', 'Yes')->orderBy('name')->get() as $u) {
                            $label = $u->name;
                            if ($u->dtehm_member_id) $label .= " ({$u->dtehm_member_id})";
                            $opts[$u->id] = $label;
                        }
                        return $opts;
                    })
                    ->help('Optional — assign to a stockist shop');
            });

            // ── Payment ────────────────────────────────────────────────────
            $form->divider('Payment Information');

            $form->html('
                <div class="alert alert-warning">
                    <i class="fa fa-money"></i>
                    <strong>DTEHM Membership Fee: UGX 76,000</strong><br>
                    Choose whether the member has already paid or needs to pay later.
                </div>
            ');

            $form->radio('payment_status', 'Has Member Paid?')
                ->options([
                    'paid'     => '✅  Yes — Member has already paid (UGX 76,000). Create CONFIRMED record.',
                    'not_paid' => '⏳  No — Redirect to payment page after registration.',
                ])
                ->default('paid')
                ->rules('required')
                ->required();

            // ── Login Credentials ──────────────────────────────────────────
            $form->divider('Login Credentials');

            $form->html('
                <div class="alert alert-secondary">
                    <i class="fa fa-lock"></i>
                    Set a password for this member\'s mobile app login.
                    <br><small>Username is auto-set to their phone number.</small>
                </div>
            ');

            $form->row(function ($row) {
                $row->width(6)->password('password', 'Password')
                    ->rules('required|min:6|confirmed')
                    ->required()
                    ->placeholder('Min. 6 characters');

                $row->width(6)->password('password_confirmation', 'Confirm Password')
                    ->rules('required|min:6')
                    ->required()
                    ->placeholder('Re-enter password');
            });

        } else {
            // ── EDIT ─────────────────────────────────────────────────────────

            // ── Standalone fields FIRST (before any rows) — these always populate from model ──

            // Build shop options with STRING keys (model returns shop_id as string — must match)
            $shopOptionsRaw = User::where('is_stockist', 'Yes')
                ->orderBy('name')
                ->get();

            $shopOptions = ['' => '— None —'];
            foreach ($shopOptionsRaw as $u) {
                $label = $u->name;
                if ($u->dtehm_member_id) $label .= " ({$u->dtehm_member_id})";
                $shopOptions[(string)$u->id] = $label;
            }

            // Current shop for JS pre-selection fallback
            $currentShopId = (string)($form->model()->shop_id ?? '');

            $form->select('shop_id', 'Assigned Shop')
                ->options($shopOptions)
                ->help('Assign this member to a stockist shop. Changes trigger commission linkage automatically.');

            // JS fallback: force select2 to show the correct value after it initialises
            if ($currentShopId) {
                $form->html('
                    <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        setTimeout(function () {
                            var el = $("select[name=\'shop_id\']");
                            if (el.length && el.val() != "' . $currentShopId . '") {
                                el.val("' . $currentShopId . '").trigger("change");
                            }
                        }, 300);
                    });
                    </script>
                ');
            }

            $form->display('dtehm_member_id', 'DTEHM ID')->help('Auto-generated — cannot be changed');

            $form->html('
                <div class="alert alert-info" style="margin-bottom:10px;">
                    <i class="fa fa-id-card"></i>
                    <strong>Editing DTEHM Member</strong> &nbsp;—&nbsp;
                    DTEHM ID is auto-managed and cannot be changed here.
                </div>
            ');

            $form->divider('Personal Information');

            $form->row(function ($row) {
                $row->width(3)->text('first_name', 'First Name')->rules('required|min:2');
                $row->width(3)->text('last_name', 'Last Name')->rules('required|min:2');
                $row->width(3)->text('phone_number', 'Phone Number')->rules('required|min:10|max:15');
                $row->width(3)->radio('sex', 'Gender')
                    ->options(['Male' => 'Male', 'Female' => 'Female'])
                    ->default('Male');
            });

            $form->row(function ($row) {
                $row->width(6)->text('email', 'Email')->rules('nullable|email');
                $row->width(6)->text('address', 'Home Address');
            });

            $form->divider('Membership');

            $form->row(function ($row) {
                $row->width(4)->select('is_dtehm_member', 'DTEHM Member?')
                    ->options(['Yes' => 'Yes', 'No' => 'No'])
                    ->default('Yes')
                    ->rules('required')
                    ->help('Should remain Yes for records in this section');

                $row->width(4)->select('is_dip_member', 'Also DIP Member?')
                    ->options(['No' => 'No', 'Yes' => 'Yes'])
                    ->default('No');

                $row->width(4)->select('is_stockist', 'Is Stockist?')
                    ->options(['No' => 'No', 'Yes' => 'Yes'])
                    ->default('No');
            });

            $form->divider('Account Balances');
            $form->row(function ($row) {
                // account_balance is a computed accessor (no DB column) — display only, never submitted
                $row->width(6)->display('account_balance', 'Balance (UGX)')
                    ->with(fn($v) => 'UGX ' . number_format($v ?? 0, 0));
                $row->width(6)->number('total_points', 'Total Points');
            });

            $form->divider('Change Password (Optional)');

            $form->row(function ($row) {
                $row->width(12)->radio('change_password_toggle', 'Change Password?')
                    ->options([
                        'No'  => 'No — Keep current password',
                        'Yes' => 'Yes — Set new password',
                    ])
                    ->default('No')
                    ->when('Yes', function (Form $f) {
                        $f->row(function ($row) {
                            $row->width(6)->password('password', 'New Password');
                            $row->width(6)->password('password_confirmation', 'Confirm New Password');
                        });
                    });
            });

            // ── Withdraw PIN Management ──────────────────────────────────────
            // NOTE: Must use $form->row() — standalone fields after rows are silently skipped.
            $pinRecord = $form->model()->accountPin;

            if ($pinRecord) {
                $locked         = $pinRecord->isLocked();
                $failedAttempts = $pinRecord->failed_attempts;
                $lastChanged    = $pinRecord->last_changed_at ? date('d M Y H:i', strtotime($pinRecord->last_changed_at)) : 'Unknown';
                $lockedUntil    = $locked && $pinRecord->locked_until ? date('d M Y H:i', strtotime($pinRecord->locked_until)) : null;

                if ($locked) {
                    $pinStatusHtml = "
                    <div class='alert alert-danger' style='margin:0;'>
                        <i class='fa fa-lock'></i>
                        <strong>PIN is LOCKED</strong> — locked until <strong>{$lockedUntil}</strong>
                        due to {$failedAttempts} failed attempt(s). Set a new PIN below to unlock immediately.
                    </div>";
                } elseif ($failedAttempts > 0) {
                    $pinStatusHtml = "
                    <div class='alert alert-warning' style='margin:0;'>
                        <i class='fa fa-exclamation-triangle'></i>
                        <strong>PIN is set</strong> but has <strong>{$failedAttempts} failed attempt(s)</strong>.
                        Last changed: {$lastChanged}. Set a new PIN below to also clear failed attempts.
                    </div>";
                } else {
                    $pinStatusHtml = "
                    <div class='alert alert-success' style='margin:0;'>
                        <i class='fa fa-check-circle'></i>
                        <strong>PIN is set and active.</strong> Last changed: {$lastChanged}.
                        Failed attempts: 0.
                    </div>";
                }
            } else {
                $pinStatusHtml = "
                <div class='alert alert-default' style='margin:0;background:#f4f6f9;border:1px solid #d2d6de;'>
                    <i class='fa fa-info-circle'></i>
                    <strong>No withdraw PIN is set</strong> for this member yet.
                    Enter a 4-digit PIN below to create one.
                </div>";
            }

            $form->row(function ($row) {
                $row->width(12)->html('<hr style="margin:20px 0 10px;"><h4 style="color:#3c8dbc;"><i class="fa fa-key"></i> Withdraw PIN Management</h4><p class="text-muted" style="margin:0 0 10px;">PIN is stored as a one-way hash — the actual digits cannot be retrieved. You can only set a new PIN.</p>');
            });

            $form->row(function ($row) use ($pinStatusHtml) {
                $row->width(12)->html($pinStatusHtml);
            });

            $form->row(function ($row) {
                $row->width(4)->text('new_pin', 'New Withdraw PIN')
                    ->placeholder('Enter 4-digit PIN')
                    ->help('Leave blank to keep existing PIN. Must be exactly 4 digits (0–9).');

                $row->width(4)->radio('unlock_pin_toggle', 'Clear Failed Attempts?')
                    ->options(['No' => 'No', 'Yes' => 'Yes — Reset lock & failed count'])
                    ->default('No')
                    ->help('Forces unlock even without setting a new PIN.');

                $row->width(4)->html('
                    <div style="padding-top:22px;">
                        <small class="text-muted">
                            <i class="fa fa-info-circle"></i>
                            Setting a new PIN automatically clears all failed attempts and removes any lock.
                        </small>
                    </div>
                ');
            });

            // Must be in form body (not inside saving callback) — laravel-admin prepares data before saving fires.
            // account_balance has no DB column (it's a computed accessor from transactions) so must also be ignored.
            $form->ignore(['new_pin', 'unlock_pin_toggle', 'account_balance']);
        }

        // ── SAVING ──────────────────────────────────────────────────────────
        $form->saving(function (Form $form) {
            try {
                if ($form->isCreating()) {

                    // 1. Force DTEHM membership flag
                    $form->is_dtehm_member = 'Yes';

                    // 2. Validate phone uniqueness
                    if (empty($form->phone_number)) {
                        admin_error('Validation Error', 'Phone number is required.');
                        return back()->withInput();
                    }
                    $phoneExists = User::where('phone_number', trim($form->phone_number))->exists();
                    if ($phoneExists) {
                        admin_error('Duplicate Phone', "Phone number '{$form->phone_number}' is already registered.");
                        return back()->withInput();
                    }

                    // 3. Validate email uniqueness (if provided)
                    if (!empty($form->email)) {
                        $emailExists = User::where('email', trim($form->email))->exists();
                        if ($emailExists) {
                            admin_error('Duplicate Email', "Email '{$form->email}' is already registered.");
                            return back()->withInput();
                        }
                    }

                    // 4. Validate & resolve sponsor
                    if (empty($form->sponsor_id)) {
                        admin_error('Sponsor Required', 'A valid Sponsor ID is required to register a DTEHM member.');
                        return back()->withInput();
                    }

                    $sponsor = User::where('dtehm_member_id', $form->sponsor_id)->first()
                        ?? User::where('business_name', $form->sponsor_id)->first()
                        ?? User::find($form->sponsor_id);

                    if (!$sponsor) {
                        admin_error('Sponsor Not Found', "Sponsor ID '{$form->sponsor_id}' does not match any member.");
                        return back()->withInput();
                    }

                    if ($sponsor->is_dtehm_member !== 'Yes') {
                        admin_error('Invalid Sponsor', "{$sponsor->name} is not a DTEHM member and cannot be a sponsor.");
                        return back()->withInput();
                    }

                    // Store canonical DTEHM ID string, not DB integer
                    $form->sponsor_id = $sponsor->dtehm_member_id;
                    $form->parent_1   = $sponsor->id;

                    // 5. Auto-fill required fields
                    $form->username         = trim($form->phone_number);
                    $form->name             = trim($form->first_name . ' ' . $form->last_name);
                    $form->user_type        = 'Customer';
                    $form->status           = 'Active';
                    $form->country          = $form->country ?: 'Uganda';
                    $form->registered_by_id = \Admin::user()->id;

                    // 6. Pre-mark DTEHM membership fields so generateDtehmMemberId() triggers
                    $form->dtehm_membership_is_paid     = 'Yes';
                    $form->dtehm_membership_paid_date   = now();
                    $form->dtehm_membership_paid_amount = 76000;
                    $form->dtehm_member_membership_date = now();

                    // 7. Hash password
                    if (!empty($form->password)) {
                        $form->password = bcrypt($form->password);
                    }

                    // 8. If also DIP member, pre-mark DIP fields
                    if ($form->is_dip_member === 'Yes') {
                        // business_name (DIP ID) auto-generated by User::generateDipId()
                    }

                } else {
                    // ── EDITING ───────────────────────────────────────────

                    // Always keep is_dtehm_member = 'Yes'
                    if (empty($form->is_dtehm_member)) {
                        $form->is_dtehm_member = 'Yes';
                    }

                    // Rebuild full name
                    if ($form->first_name && $form->last_name) {
                        $form->name = trim($form->first_name . ' ' . $form->last_name);
                    }

                    // Password handling
                    if ($form->change_password_toggle === 'Yes' && !empty($form->password)) {
                        $form->password = bcrypt($form->password);
                    } else {
                        $form->ignore(['password', 'password_confirmation', 'change_password_toggle']);
                    }

                    // Prevent sponsor change on edit (restore original)
                    $original = User::find($form->model()->id);
                    if ($original) {
                        $form->sponsor_id = $original->sponsor_id;
                        $form->parent_1   = $original->parent_1;
                    }

                    // Normalize shop_id: empty string from '— None —' option → null
                    if ($form->shop_id === '' || $form->shop_id === '0' || $form->shop_id === 0) {
                        $form->shop_id = null;
                    }

                    // ── Withdraw PIN handling ────────────────────────────────
                    // Read directly from request — $form->new_pin returns null because
                    // new_pin is in the ignore list (ignore removes it from form data).
                    $newPin       = trim(request()->input('new_pin', ''));
                    $unlockToggle = request()->input('unlock_pin_toggle', 'No');

                    // Validate PIN if provided
                    if ($newPin !== '') {
                        if (!preg_match('/^\d{4}$/', $newPin)) {
                            admin_error('Invalid PIN', 'Withdraw PIN must be exactly 4 digits (0–9).');
                            return back()->withInput();
                        }
                    }

                    // Apply PIN changes after saving (handled in saved() below via request)
                    // We store the intent in request data so saved() can act on it
                    request()->merge([
                        '_pin_new'    => $newPin,
                        '_pin_unlock' => $unlockToggle,
                    ]);

                    // If newly toggled to DIP member, mark DIP fields
                    if ($form->is_dip_member === 'Yes' && optional($form->model())->is_dip_member !== 'Yes') {
                        // business_name (DIP ID) auto-generated by User::generateDipId()
                    }
                }

            } catch (\Exception $e) {
                \Log::error('DtehmMemberController saving error', ['error' => $e->getMessage()]);
                admin_error('Save Error', $e->getMessage());
                return false;
            }
        });

        // ── SAVED ────────────────────────────────────────────────────────────
        $form->saved(function (Form $savedForm) {
            $admin = \Admin::user();
            $user  = User::find($savedForm->model()->id);

            if (!$user) {
                return;
            }

            // ── Withdraw PIN post-save ────────────────────────────────────────
            $newPin      = request()->input('_pin_new', '');
            $pinUnlock   = request()->input('_pin_unlock', 'No');

            if ($newPin !== '') {
                // setPin() hashes, resets failed_attempts and locked_until
                $pin = $user->accountPin ?? new AccountPin(['user_id' => $user->id]);
                $pin->user_id = $user->id;
                $pin->setPin($newPin);
            } elseif ($pinUnlock === 'Yes') {
                // Unlock only — don't change the PIN hash
                $pin = $user->accountPin;
                if ($pin) {
                    $pin->failed_attempts = 0;
                    $pin->locked_until    = null;
                    $pin->save();
                }
            }

            $paymentStatus = request()->input('payment_status', 'paid');
            $needsPayment  = ($paymentStatus === 'not_paid');

            try {
                $created = [];
                $total   = 0;

                // ── DTEHM membership record ────────────────────────────────
                if ($user->is_dtehm_member === 'Yes') {
                    $total += 76000;

                    if (!$needsPayment) {
                        $existing = DtehmMembership::where('user_id', $user->id)
                            ->where('status', 'CONFIRMED')
                            ->first();

                        if (!$existing) {
                            $dtehm = DtehmMembership::create([
                                'user_id'          => $user->id,
                                'amount'           => 76000,
                                'status'           => 'CONFIRMED',
                                'payment_method'   => 'CASH',
                                'registered_by_id' => $admin->id,
                                'created_by'       => $admin->id,
                                'confirmed_by'     => $admin->id,
                                'confirmed_at'     => now(),
                                'payment_date'     => now(),
                                'description'      => 'Paid during DTEHM registration by admin ' . $admin->username,
                            ]);

                            $user->dtehm_membership_paid_at     = now();
                            $user->dtehm_membership_amount      = 76000;
                            $user->dtehm_membership_payment_id  = $dtehm->id;
                            $user->dtehm_membership_is_paid     = 'Yes';
                            $user->dtehm_membership_paid_date   = now();
                            $user->dtehm_member_membership_date = now();
                            $user->saveQuietly();

                            $created[] = 'DTEHM membership (UGX 76,000) confirmed';
                        }
                    }
                }

                // ── DIP membership record (if also DIP member) ─────────────
                if ($user->is_dip_member === 'Yes') {
                    $total += 20000;

                    if (!$needsPayment) {
                        $existing = MembershipPayment::where('user_id', $user->id)
                            ->where('status', 'CONFIRMED')
                            ->first();

                        if (!$existing) {
                            MembershipPayment::create([
                                'user_id'          => $user->id,
                                'amount'           => 20000,
                                'membership_type'  => 'LIFE',
                                'status'           => 'CONFIRMED',
                                'payment_method'   => 'CASH',
                                'created_by'       => $admin->id,
                                'updated_by'       => $admin->id,
                                'confirmed_by'     => $admin->id,
                                'confirmed_at'     => now(),
                                'payment_date'     => now(),
                                'registered_by_id' => $admin->id,
                                'description'      => 'Paid during DTEHM registration by admin ' . $admin->username,
                            ]);
                            $created[] = 'DIP membership (UGX 20,000) confirmed';
                        }
                    }
                }

                if ($needsPayment && $total > 0) {
                    session([
                        'pending_member_payment_user_id'  => $user->id,
                        'pending_member_payment_amount'   => $total,
                        'pending_member_payment_is_dtehm' => true,
                        'pending_member_payment_is_dip'   => $user->is_dip_member === 'Yes',
                    ]);
                    admin_toastr('DTEHM Member registered. Redirecting to payment...', 'success');
                    return redirect(admin_url('membership-payment/initiate/' . $user->id));
                }

                $msg = $created
                    ? 'DTEHM Member saved. ' . implode(' and ', $created) . '.'
                    : 'DTEHM Member saved successfully.';

                admin_toastr($msg, 'success');

            } catch (\Exception $e) {
                \Log::error('DtehmMember saved hook error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                admin_toastr('Member saved but membership record failed: ' . $e->getMessage(), 'error');
            }
        });

        // Ignore virtual/confirmation fields — never write to DB
        $form->ignore(['payment_status', 'password_confirmation', 'change_password_toggle']);

        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
