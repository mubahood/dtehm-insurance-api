<?php

namespace App\Admin\Controllers;

use App\Models\MembershipPayment;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DipMemberController extends AdminController
{
    protected $title = 'DIP Members';

    // ─────────────────────────────────────────────────────────────────────────
    // GRID
    // ─────────────────────────────────────────────────────────────────────────
    protected function grid()
    {
        $grid = new Grid(new User());

        $grid->model()
            ->where('is_dip_member', 'Yes')
            ->orderBy('business_name', 'asc');

        $grid->column('id', __('ID'))->sortable()->width(60);

        $grid->column('business_name', __('DIP ID'))
            ->display(function ($dipId) {
                return $dipId
                    ? "<span class='label label-primary' style='font-size:12px;letter-spacing:1px;'>{$dipId}</span>"
                    : '<span class="text-muted">—</span>';
            })
            ->sortable()->width(110);

        $grid->column('name', __('Member Name'))
            ->display(function () {
                $name = trim($this->first_name . ' ' . $this->last_name);
                return "<strong>{$name}</strong><br><small class='text-muted'>{$this->phone_number}</small>";
            })->width(200);

        $grid->column('sex', __('Gender'))
            ->label(['Male' => 'info', 'Female' => 'danger'])
            ->width(80);

        $grid->column('sponsor_id', __('Sponsor'))
            ->display(function () {
                if (empty($this->sponsor_id)) {
                    return '<span class="text-muted">—</span>';
                }
                $sponsor = User::where('dtehm_member_id', $this->sponsor_id)
                    ->orWhere('business_name', $this->sponsor_id)
                    ->first();
                $name = $sponsor ? "<br><small>{$sponsor->first_name} {$sponsor->last_name}</small>" : '';
                return "<span class='label label-success'>{$this->sponsor_id}</span>{$name}";
            })->width(150);

        $grid->column('is_dtehm_member', __('Also DTEHM?'))
            ->label(['Yes' => 'success', 'No' => 'default'])
            ->width(100);

        $grid->column('dip_payment_status', __('DIP Payment'))
            ->display(function () {
                $payment = MembershipPayment::where('user_id', $this->id)
                    ->orderBy('id', 'desc')->first();
                if (!$payment) {
                    return "<span class='label label-default'>No Record</span>";
                }
                $map = ['CONFIRMED' => 'success', 'PENDING' => 'warning', 'FAILED' => 'danger', 'REFUNDED' => 'secondary'];
                $color = $map[$payment->status] ?? 'default';
                return "<span class='label label-{$color}'>{$payment->status}</span><br>
                        <small>UGX " . number_format($payment->amount, 0) . "</small>";
            })->width(130);

        $grid->column('account_balance', __('Balance (UGX)'))
            ->display(function ($v) {
                return number_format($v ?? 0, 0);
            })->sortable()->width(120);

        $grid->column('total_points', __('Points'))
            ->display(function ($v) {
                return number_format($v ?? 0, 0);
            })->sortable()->width(80);

        $grid->column('created_at', __('Registered'))
            ->display(function ($d) {
                return $d ? date('M d, Y', strtotime($d)) : '—';
            })->sortable()->width(110);

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('first_name', 'First Name');
            $filter->like('last_name', 'Last Name');
            $filter->like('phone_number', 'Phone Number');
            $filter->like('business_name', 'DIP ID');
            $filter->like('sponsor_id', 'Sponsor ID');
            $filter->equal('is_dtehm_member', 'Also DTEHM?')->select(['Yes' => 'Yes', 'No' => 'No']);
            $filter->between('created_at', 'Registration Date')->date();
        });

        $grid->export(function ($export) {
            $export->filename('DIP_Members_' . date('Y-m-d'));
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

        $show->divider('DIP Member Identity');
        $show->field('business_name', __('DIP ID'));
        $show->field('dtehm_member_id', __('DTEHM ID'))->as(function ($v) { return $v ?: 'N/A'; });
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
        $show->field('is_dip_member', __('DIP Member'));
        $show->field('is_dtehm_member', __('DTEHM Member'));
        $show->field('sponsor_id', __('Sponsor ID'));
        $show->field('account_balance', __('Balance (UGX)'))->as(function ($v) { return 'UGX ' . number_format($v ?? 0, 0); });
        $show->field('total_points', __('Points'));

        $show->divider('DIP Membership Payments');
        $show->field('id', __('Payments'))->as(function ($userId) {
            $payments = MembershipPayment::where('user_id', $userId)->orderBy('id', 'desc')->get();
            if ($payments->isEmpty()) return '<em>No payment records</em>';
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
                <div class="alert alert-primary" style="background:#1a73e8;color:#fff;border:none;">
                    <h4 style="margin:0 0 6px;"><i class="fa fa-id-card"></i> &nbsp;New DIP Member Registration</h4>
                    <p style="margin:0;">A DIP ID (<strong>DIP001</strong>, <strong>DIP002</strong>…) is auto-generated on save. &nbsp;
                    Membership fee: <strong>UGX 20,000</strong></p>
                </div>
            ');

            // Force DIP — always
            $form->hidden('is_dip_member')->value('Yes');
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
                    <strong>DIP Member</strong> is auto-set to <code>Yes</code>.<br>
                    Sponsor must be an existing <strong>DTEHM member</strong> in the system.
                </div>
            ');

            $form->row(function ($row) {
                // Build sponsor list from DTEHM members only
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
                    ->help('Must be a DTEHM member');

                $row->width(4)->select('is_dtehm_member', 'Also DTEHM Member?')
                    ->options(['No' => 'No', 'Yes' => 'Yes'])
                    ->default('No')
                    ->rules('required')
                    ->help('Select Yes only if member also pays DTEHM fee');

                $row->width(4)->select('stockist_area', 'Center / Area')
                    ->options(function () {
                        return User::where('is_stockist', 'Yes')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(function ($u) {
                                $label = $u->name;
                                if ($u->dtehm_member_id) $label .= " ({$u->dtehm_member_id})";
                                elseif ($u->business_name) $label .= " ({$u->business_name})";
                                return [$u->name => $label];
                            });
                    })
                    ->help('Optional — select member\'s center');
            });

            // ── Payment ────────────────────────────────────────────────────
            $form->divider('Payment Information');

            $form->html('
                <div class="alert alert-warning">
                    <i class="fa fa-money"></i>
                    <strong>DIP Membership Fee: UGX 20,000</strong><br>
                    Choose whether the member has already paid or needs to pay later.
                </div>
            ');

            $form->radio('payment_status', 'Has Member Paid?')
                ->options([
                    'paid'     => '✅  Yes — Member has already paid (UGX 20,000). Create CONFIRMED record.',
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

            $form->html('
                <div class="alert alert-info">
                    <i class="fa fa-id-card"></i>
                    <strong>Editing DIP Member</strong> &nbsp;—&nbsp;
                    DIP ID is auto-managed and cannot be changed here.
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
                $row->width(4)->select('is_dip_member', 'DIP Member?')
                    ->options(['Yes' => 'Yes', 'No' => 'No'])
                    ->default('Yes')
                    ->rules('required')
                    ->help('Should remain Yes for records in this section');

                $row->width(4)->select('is_dtehm_member', 'Also DTEHM Member?')
                    ->options(['No' => 'No', 'Yes' => 'Yes'])
                    ->default('No');

                $row->width(4)->select('is_stockist', 'Is Stockist?')
                    ->options(['No' => 'No', 'Yes' => 'Yes'])
                    ->default('No');
            });

            $form->divider('DIP ID (Read-Only)');
            $form->display('business_name', 'DIP ID')->help('Auto-generated — cannot be changed');

            $form->divider('Account Balances');
            $form->row(function ($row) {
                $row->width(6)->currency('account_balance', 'Balance (UGX)')->symbol('UGX');
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
        }

        // ── SAVING ──────────────────────────────────────────────────────────
        $form->saving(function (Form $form) {
            try {
                if ($form->isCreating()) {

                    // 1. Force DIP membership flag
                    $form->is_dip_member = 'Yes';

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
                        admin_error('Sponsor Required', 'A valid Sponsor ID is required to register a DIP member.');
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

                    // 6. Hash password
                    if (!empty($form->password)) {
                        $form->password = bcrypt($form->password);
                    }

                    // 7. If also DTEHM member, pre-mark fields
                    if ($form->is_dtehm_member === 'Yes') {
                        $form->dtehm_membership_is_paid   = 'Yes';
                        $form->dtehm_membership_paid_date = now();
                        $form->dtehm_membership_paid_amount = 76000;
                        $form->dtehm_member_membership_date = now();
                    }

                } else {
                    // ── EDITING ───────────────────────────────────────────

                    // Always keep is_dip_member = 'Yes' (it's in the form but ensure it)
                    if (empty($form->is_dip_member)) {
                        $form->is_dip_member = 'Yes';
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

                    // If DTEHM membership changed to Yes, mark paid fields
                    if ($form->is_dtehm_member === 'Yes' && optional($form->model())->is_dtehm_member !== 'Yes') {
                        $form->dtehm_membership_is_paid     = 'Yes';
                        $form->dtehm_membership_paid_date   = now();
                        $form->dtehm_membership_paid_amount = 76000;
                        $form->dtehm_member_membership_date = now();
                    }
                }

            } catch (\Exception $e) {
                \Log::error('DipMemberController saving error', ['error' => $e->getMessage()]);
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

            // payment_status is only relevant on create
            $paymentStatus = request()->input('payment_status', 'paid');
            $needsPayment  = ($paymentStatus === 'not_paid');

            try {
                $created  = [];
                $total    = 0;

                // ── DIP membership record ──────────────────────────────────
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
                                'description'      => 'Paid during DIP registration by admin ' . $admin->username,
                            ]);
                            $created[] = 'DIP membership (UGX 20,000) confirmed';
                        }
                    }
                }

                // ── DTEHM membership record (if also DTEHM) ────────────────
                if ($user->is_dtehm_member === 'Yes') {
                    $total += 76000;

                    if (!$needsPayment) {
                        $existing = \App\Models\DtehmMembership::where('user_id', $user->id)
                            ->where('status', 'CONFIRMED')
                            ->first();

                        if (!$existing) {
                            $dtehm = \App\Models\DtehmMembership::create([
                                'user_id'          => $user->id,
                                'amount'           => 76000,
                                'status'           => 'CONFIRMED',
                                'payment_method'   => 'CASH',
                                'registered_by_id' => $admin->id,
                                'created_by'       => $admin->id,
                                'confirmed_by'     => $admin->id,
                                'confirmed_at'     => now(),
                                'payment_date'     => now(),
                                'description'      => 'Paid during DIP registration by admin ' . $admin->username,
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

                if ($needsPayment && $total > 0) {
                    // Redirect to payment page
                    session([
                        'pending_member_payment_user_id'    => $user->id,
                        'pending_member_payment_amount'     => $total,
                        'pending_member_payment_is_dip'     => true,
                        'pending_member_payment_is_dtehm'   => $user->is_dtehm_member === 'Yes',
                    ]);
                    admin_toastr('DIP Member registered. Redirecting to payment...', 'success');
                    return redirect(admin_url('membership-payment/initiate/' . $user->id));
                }

                $msg = $created
                    ? 'DIP Member saved. ' . implode(' and ', $created) . '.'
                    : 'DIP Member saved successfully.';

                admin_toastr($msg, 'success');

            } catch (\Exception $e) {
                \Log::error('DipMember saved hook error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
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
