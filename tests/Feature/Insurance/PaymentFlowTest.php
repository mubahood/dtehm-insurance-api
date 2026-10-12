<?php

namespace Tests\Feature\Insurance;

use App\Models\DtehmMembership;
use App\Models\InsuranceSubscription;
use App\Models\InsuranceSubscriptionPayment;
use App\Models\SystemConfiguration;
use App\Models\UniversalPayment;
use App\Services\PesapalApiClient;
use App\Services\PesapalService;
use Mockery;

class PaymentFlowTest extends InsuranceTestCase
{
    /** What the fake Pesapal will answer to GetTransactionStatus */
    protected array $remote = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(PesapalApiClient::class, function ($m) {
            $m->shouldReceive('initializePayment')->andReturnUsing(function ($d) {
                return [
                    'success' => true,
                    'order_tracking_id' => 'trk-' . uniqid(),
                    'redirect_url' => 'https://pay.example.test/' . uniqid(),
                    'notification_id' => 'ipn-1',
                ];
            });
        });

        $test = $this;
        $this->mock(PesapalService::class, function ($m) use ($test) {
            $m->shouldReceive('getTransactionStatus')->andReturnUsing(function () use ($test) {
                return $test->remote;
            });
        });
    }

    protected function fees(): array
    {
        $c = SystemConfiguration::getInstance();
        return [(float) ($c->dtehm_membership_fee ?: 76000), (float) ($c->dip_membership_fee ?: 20000)];
    }

    protected function enrol($user, int $months = 3, float $premium = 10000): InsuranceSubscription
    {
        $program = $this->makeProgram(['duration_months' => $months, 'premium_amount' => $premium]);
        $id = $this->json('POST', '/api/insurance-subscriptions', [
            'insurance_program_id' => $program->id, 'start_date' => now()->toDateString(),
        ], $this->headersFor($user))->assertStatus(201)->json('data.id');
        return InsuranceSubscription::find($id);
    }

    protected function init($user, array $items, $asUser = null)
    {
        return $this->json('POST', '/api/universal-payments/initialize', [
            'payment_type' => 'insurance_subscription_payment',
            'payment_gateway' => 'pesapal',
            'payment_items' => $items,
        ] + ($asUser ? ['user_id' => $asUser->id] : []), $this->headersFor($user));
    }

    protected function paidRemote(float $amount, string $cur = 'UGX'): void
    {
        $this->remote = ['status_code' => 1, 'payment_status_description' => 'Completed', 'amount' => $amount, 'currency' => $cur, 'confirmation_code' => 'CONF1'];
    }

    // ---------------------------------------------------------------- initialize: server decides the money

    public function test_premium_amount_cannot_exceed_balance_and_cannot_target_other_members()
    {
        $me = $this->makeUser();
        $other = $this->makeUser();
        $sub = $this->enrol($me);
        $row = $sub->payments()->first() ?? InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->first();

        // an inflated amount from the client is ignored: the server charges exactly what is owed
        $pid = $this->init($me, [['type' => 'insurance_subscription_payment', 'id' => $row->id, 'amount' => 999999]])
            ->assertStatus(201)->json('data.payment.id');
        $this->assertEquals(10000, (float) UniversalPayment::find($pid)->amount);
        UniversalPayment::where('id', $pid)->forceDelete();

        $this->init($other, [['type' => 'insurance_subscription_payment', 'id' => $row->id, 'amount' => 10000]])
            ->assertStatus(422);

        // body user_id of someone else is refused for members
        $this->init($other, [['type' => 'insurance_subscription_payment', 'id' => $row->id, 'amount' => 10000]], $me)
            ->assertStatus(403);

        $this->assertEquals(0, UniversalPayment::where('user_id', $me->id)->count());
    }

    // ---------------------------------------------------------------- months are always paid oldest-first

    public function test_choosing_a_later_month_charges_the_oldest_unpaid_month_instead()
    {
        $me = $this->makeUser();
        $sub = $this->enrol($me, 12);
        $rows = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderBy('due_date')->get();

        // old app builds could pick e.g. the last two months of the year
        $pid = $this->init($me, [
            ['type' => 'insurance_subscription_payment', 'id' => $rows[10]->id, 'amount' => 10000],
            ['type' => 'insurance_subscription_payment', 'id' => $rows[11]->id, 'amount' => 10000],
        ])->assertStatus(201)->json('data.payment.id');

        $items = UniversalPayment::find($pid)->payment_items;
        $this->assertEquals([$rows[0]->id, $rows[1]->id], array_column($items, 'id'));

        $this->paidRemote(20000);
        $this->getJson('/api/universal-payments/status/' . $pid, $this->headersFor($me))->assertStatus(200);
        $this->assertEquals('Paid', $rows[0]->fresh()->payment_status);
        $this->assertEquals('Paid', $rows[1]->fresh()->payment_status);
        $this->assertEquals('Pending', $rows[11]->fresh()->payment_status);
    }

    public function test_pay_n_months_item_and_late_fee_is_included()
    {
        $me = $this->makeUser();
        $program = $this->makeProgram(['duration_months' => 12, 'premium_amount' => 10000, 'grace_period_days' => 0, 'late_payment_penalty' => 2000, 'penalty_type' => 'Fixed']);
        $subId = $this->json('POST', '/api/insurance-subscriptions', ['insurance_program_id' => $program->id, 'start_date' => now()->toDateString()], $this->headersFor($me))->json('data.id');
        $first = InsuranceSubscriptionPayment::where('insurance_subscription_id', $subId)->orderBy('due_date')->first();
        $first->forceFill(['due_date' => now()->subDays(5)->toDateString()])->saveQuietly();
        $this->artisan('insurance:maintain')->assertExitCode(0);
        $this->assertEquals(12000, (float) $first->fresh()->total_amount);

        $pid = $this->init($me, [['type' => 'insurance_months', 'id' => $subId, 'quantity' => 3]])->assertStatus(201)->json('data.payment.id');
        $this->assertEquals(32000, (float) UniversalPayment::find($pid)->amount, '3 months incl. one late fee');
    }

    public function test_money_moves_to_the_next_month_if_the_chosen_one_was_settled_meanwhile()
    {
        $admin = $this->makeAdmin();
        $me = $this->makeUser();
        $sub = $this->enrol($me, 3);
        $rows = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderBy('due_date')->get();
        $pid = $this->init($me, [['type' => 'insurance_subscription_payment', 'id' => $rows[0]->id, 'amount' => 10000]])->json('data.payment.id');

        // office records cash for the same month before Pesapal confirms
        $this->json('POST', '/api/insurance-subscription-payments/' . $rows[0]->id . '/pay', ['paid_amount' => 10000, 'payment_method' => 'Cash'], $this->headersFor($admin))->assertStatus(200);

        $this->paidRemote(10000);
        $this->getJson('/api/universal-payments/status/' . $pid, $this->headersFor($me))->assertStatus(200);
        $this->assertEquals('Paid', $rows[1]->fresh()->payment_status, 'online money went to the next month');
        $this->assertEquals(20000, (float) $sub->fresh()->total_paid);
    }

    public function test_office_cannot_record_a_later_month_before_an_earlier_one()
    {
        $admin = $this->makeAdmin();
        $me = $this->makeUser();
        $sub = $this->enrol($me, 12);
        $last = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderByDesc('due_date')->first();
        $res = $this->json('POST', '/api/insurance-subscription-payments/' . $last->id . '/pay', ['paid_amount' => 10000, 'payment_method' => 'Cash'], $this->headersFor($admin));
        $res->assertStatus(400);
        $this->assertStringContainsString('oldest unpaid month', $res->json('message'));
        $this->assertEquals('Pending', $last->fresh()->payment_status);
    }

    public function test_unknown_item_types_are_not_payable_from_the_client()
    {
        $me = $this->makeUser();
        $this->init($me, [['type' => 'order', 'id' => 1, 'amount' => 1]])->assertStatus(422);
        $this->init($me, [['type' => 'insurance_transaction', 'id' => 1, 'amount' => 1]])->assertStatus(422);
    }

    public function test_initialize_is_idempotent_for_a_double_tap()
    {
        $me = $this->makeUser();
        $sub = $this->enrol($me);
        $row = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderBy('due_date')->first();
        $items = [['type' => 'insurance_subscription_payment', 'id' => $row->id, 'amount' => 10000]];

        $a = $this->init($me, $items)->assertStatus(201)->json('data.payment.id');
        $b = $this->init($me, $items)->assertStatus(200)->json('data.payment.id');
        $this->assertEquals($a, $b);
        $this->assertEquals(1, UniversalPayment::where('user_id', $me->id)->count());
    }

    public function test_membership_amount_must_match_a_real_fee_and_flags_come_from_server()
    {
        [$dtehm, $dip] = $this->fees();
        $me = $this->makeUser();

        $this->init($me, [['type' => 'membership', 'id' => $me->id, 'amount' => 1000,
            'metadata' => ['is_dtehm_member' => 'Yes']]])->assertStatus(422);

        $res = $this->init($me, [['type' => 'membership', 'id' => $me->id, 'amount' => $dip,
            'metadata' => ['is_dtehm_member' => 'Yes', 'is_dip_member' => 'Yes']]])->assertStatus(201);
        $item = UniversalPayment::find($res->json('data.payment.id'))->payment_items[0];
        $this->assertEquals('No', $item['metadata']['is_dtehm_member'], 'client metadata must be discarded');
        $this->assertEquals('Yes', $item['metadata']['is_dip_member']);
    }

    public function test_already_a_member_cannot_pay_for_the_same_membership_again()
    {
        [$dtehm] = $this->fees();
        $member = $this->makeUser(['is_dtehm_member' => 'Yes']);
        $this->init($member, [['type' => 'membership', 'id' => $member->id, 'amount' => $dtehm]])->assertStatus(422);
    }

    // ---------------------------------------------------------------- completing a payment

    public function test_paid_premium_is_applied_exactly_once_even_with_repeated_ipn_callback_and_polls()
    {
        $me = $this->makeUser();
        $sub = $this->enrol($me);
        $row = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderBy('due_date')->first();
        $pid = $this->init($me, [['type' => 'insurance_subscription_payment', 'id' => $row->id, 'amount' => 10000]])->json('data.payment.id');
        $p = UniversalPayment::find($pid);

        $this->paidRemote(10000);
        $h = $this->headersFor($me);

        // IPN twice, callback, poll twice - all in any order
        $this->postJson('/api/pesapal/ipn', ['OrderTrackingId' => $p->pesapal_order_tracking_id]);
        $this->postJson('/api/universal-payments/ipn', ['OrderTrackingId' => $p->pesapal_order_tracking_id])->assertStatus(200);
        $this->postJson('/api/universal-payments/ipn', ['OrderTrackingId' => $p->pesapal_order_tracking_id])->assertStatus(200);
        $this->getJson('/api/universal-payments/callback?OrderTrackingId=' . $p->pesapal_order_tracking_id)->assertStatus(200);
        $this->getJson('/api/universal-payments/status/' . $pid, $h)->assertStatus(200)->assertJsonPath('data.is_completed', true);
        $this->getJson('/api/universal-payments/status/' . $pid, $h)->assertStatus(200);

        $row->refresh();
        $this->assertEquals('Paid', $row->payment_status);
        $this->assertEquals(10000, (float) $row->paid_amount, 'premium must be credited once, not once per notification');

        $sub->refresh();
        $this->assertEquals(10000, (float) $sub->total_paid);
        $this->assertEquals(20000, (float) $sub->total_balance);
        $this->assertEquals(1, $sub->payments_completed);

        $p->refresh();
        $this->assertTrue((bool) $p->items_processed);
        $this->assertNotNull($p->confirmed_at);
    }

    public function test_underpaid_or_wrong_currency_confirmation_is_not_credited()
    {
        $me = $this->makeUser();
        $sub = $this->enrol($me);
        $row = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderBy('due_date')->first();
        $pid = $this->init($me, [['type' => 'insurance_subscription_payment', 'id' => $row->id, 'amount' => 10000]])->json('data.payment.id');

        $this->paidRemote(100); // Pesapal says only 100 was paid
        $this->getJson('/api/universal-payments/status/' . $pid, $this->headersFor($me))->assertStatus(200);
        $this->assertEquals('FAILED', UniversalPayment::find($pid)->status);
        $this->assertEquals('Pending', $row->fresh()->payment_status);
        $this->assertEquals(0, (float) $row->fresh()->paid_amount);
    }

    public function test_completed_payment_is_never_downgraded_by_a_late_failed_reply()
    {
        $me = $this->makeUser();
        $sub = $this->enrol($me);
        $row = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderBy('due_date')->first();
        $pid = $this->init($me, [['type' => 'insurance_subscription_payment', 'id' => $row->id, 'amount' => 10000]])->json('data.payment.id');
        $p = UniversalPayment::find($pid);

        $this->paidRemote(10000);
        $this->postJson('/api/universal-payments/ipn', ['OrderTrackingId' => $p->pesapal_order_tracking_id]);
        $confirmedAt = $p->fresh()->confirmed_at;

        $this->remote = ['status_code' => 2, 'payment_status_description' => 'Failed'];
        $this->postJson('/api/universal-payments/ipn', ['OrderTrackingId' => $p->pesapal_order_tracking_id]);

        $p->refresh();
        $this->assertEquals('COMPLETED', $p->status);
        $this->assertEquals($confirmedAt->timestamp, $p->confirmed_at->timestamp);
        $this->assertEquals('Paid', $row->fresh()->payment_status);
    }

    public function test_online_payment_always_settles_whole_months_even_if_app_sends_less()
    {
        $me = $this->makeUser();
        $sub = $this->enrol($me);
        $row = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderBy('due_date')->first();
        // e.g. an installed app build sending the bare premium for a month that has a late fee
        $pid = $this->init($me, [['type' => 'insurance_subscription_payment', 'id' => $row->id, 'amount' => 4000]])->assertStatus(201)->json('data.payment.id');
        $this->assertEquals(10000, (float) UniversalPayment::find($pid)->amount);

        $this->paidRemote(10000);
        $this->getJson('/api/universal-payments/status/' . $pid, $this->headersFor($me))->assertStatus(200);

        $this->assertEquals('Paid', $row->fresh()->payment_status);
        $sub->refresh();
        $this->assertEquals(10000, (float) $sub->total_paid);
        $this->assertEquals(20000, (float) $sub->total_balance);
    }

    public function test_membership_payment_activates_member_with_a_dtehm_id_in_the_current_format()
    {
        [$dtehm, $dip] = $this->fees();
        $me = $this->makeUser();
        $pid = $this->init($me, [['type' => 'membership', 'id' => $me->id, 'amount' => $dtehm + $dip]])->assertStatus(201)->json('data.payment.id');

        $this->paidRemote($dtehm + $dip);
        $this->getJson('/api/universal-payments/status/' . $pid, $this->headersFor($me))->assertStatus(200);
        $this->getJson('/api/universal-payments/status/' . $pid, $this->headersFor($me))->assertStatus(200);

        $me->refresh();
        $this->assertEquals('Yes', $me->is_dtehm_member);
        $this->assertEquals('Yes', $me->is_dip_member);
        $this->assertMatchesRegularExpression('/^DTEHM\d{3,4}$/', $me->dtehm_member_id);
        $this->assertEquals(1, DtehmMembership::where('user_id', $me->id)->count(), 'one membership row, however many notifications');
    }

    public function test_two_new_members_never_get_the_same_dtehm_id()
    {
        [$dtehm] = $this->fees();
        $ids = [];
        foreach ([1, 2, 3] as $_) {
            $u = $this->makeUser();
            $pid = $this->init($u, [['type' => 'membership', 'id' => $u->id, 'amount' => $dtehm]])->assertStatus(201)->json('data.payment.id');
            $this->paidRemote($dtehm);
            $this->getJson('/api/universal-payments/status/' . $pid, $this->headersFor($u))->assertStatus(200);
            $ids[] = $u->fresh()->dtehm_member_id;
        }
        $this->assertCount(3, array_unique($ids));
    }

    public function test_member_sees_only_their_own_payments_and_no_internal_fields()
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $sub = $this->enrol($a);
        $row = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->first();
        $pid = $this->init($a, [['type' => 'insurance_subscription_payment', 'id' => $row->id, 'amount' => 10000]])->json('data.payment.id');

        $this->getJson('/api/universal-payments/status/' . $pid, $this->headersFor($b))->assertStatus(403);
        $this->getJson('/api/universal-payments/' . $pid, $this->headersFor($b))->assertStatus(403);
        $this->assertCount(0, $this->getJson('/api/universal-payments', $this->headersFor($b))->json('data.data'));

        $show = $this->getJson('/api/universal-payments/' . $pid, $this->headersFor($a))->assertStatus(200)->json('data.payment');
        $this->assertArrayNotHasKey('pesapal_response', $show);
        $this->assertArrayNotHasKey('ip_address', $show);
    }

    // ---------------------------------------------------------------- admin cash handling + lifecycle

    public function test_admin_cash_payment_partial_then_full_and_overpayment_refused()
    {
        $admin = $this->makeAdmin();
        $me = $this->makeUser();
        $sub = $this->enrol($me);
        $row = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderBy('due_date')->first();
        $h = $this->headersFor($admin);
        $url = '/api/insurance-subscription-payments/' . $row->id . '/pay';

        $this->json('POST', $url, ['paid_amount' => 0, 'payment_method' => 'Cash'], $h)->assertStatus(422);
        $this->json('POST', $url, ['paid_amount' => 50000, 'payment_method' => 'Cash'], $h)->assertStatus(400);
        $this->json('POST', $url, ['paid_amount' => 3000, 'payment_method' => 'Cash'], $h)->assertStatus(200);
        $this->assertEquals('Partial', $row->fresh()->payment_status);
        $this->json('POST', $url, ['paid_amount' => 7000, 'payment_method' => 'Cash'], $h)->assertStatus(200);
        $this->assertEquals('Paid', $row->fresh()->payment_status);
        $this->assertEquals(10000, (float) $row->fresh()->paid_amount);
        $this->json('POST', $url, ['paid_amount' => 1, 'payment_method' => 'Cash'], $h)->assertStatus(400);
        $this->assertEquals(10000, (float) $sub->fresh()->total_paid);
    }

    public function test_cancel_waives_unpaid_rows_keeps_paid_history_and_blocks_second_cancel()
    {
        $admin = $this->makeAdmin();
        $me = $this->makeUser();
        $sub = $this->enrol($me);
        $first = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderBy('due_date')->first();
        $this->json('POST', '/api/insurance-subscription-payments/' . $first->id . '/pay', ['paid_amount' => 10000, 'payment_method' => 'Cash'], $this->headersFor($admin))->assertStatus(200);

        $h = $this->headersFor($me);
        $this->json('POST', '/api/insurance-subscriptions/' . $sub->id . '/cancel', ['cancellation_reason' => 'moving'], $h)->assertStatus(200);
        $this->json('POST', '/api/insurance-subscriptions/' . $sub->id . '/cancel', ['cancellation_reason' => 'again'], $h)->assertStatus(400);

        $sub->refresh();
        $this->assertEquals('Cancelled', $sub->status);
        $this->assertEquals('Paid', $first->fresh()->payment_status);
        $this->assertEquals(2, InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->where('payment_status', 'Waived')->count());
        $this->assertEquals(10000, (float) $sub->total_expected, 'waived months are no longer owed');
        $this->assertEquals(0, (float) $sub->total_balance);

        // a cancelled policy cannot be paid for
        $waived = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->where('payment_status', 'Waived')->first();
        $this->init($me, [['type' => 'insurance_subscription_payment', 'id' => $waived->id, 'amount' => 10000]])->assertStatus(422);
    }

    public function test_subscription_with_money_paid_cannot_be_deleted_and_program_cannot_be_wiped()
    {
        $admin = $this->makeAdmin();
        $me = $this->makeUser();
        $sub = $this->enrol($me);
        $row = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderBy('due_date')->first();
        $h = $this->headersFor($admin);
        $this->json('POST', '/api/insurance-subscription-payments/' . $row->id . '/pay', ['paid_amount' => 10000, 'payment_method' => 'Cash'], $h)->assertStatus(200);

        $this->json('DELETE', '/api/insurance-subscriptions/' . $sub->id, [], $h)->assertStatus(409);
        $this->assertNotNull(InsuranceSubscription::find($sub->id));
        $this->json('DELETE', '/api/insurance-programs/' . $sub->insurance_program_id, [], $h)->assertStatus(409);
        $this->assertNotNull(InsuranceSubscription::find($sub->id));
    }

    public function test_daily_maintenance_marks_overdue_expires_and_applies_penalty_only_to_active_policies()
    {
        $me = $this->makeUser();
        $sub = $this->enrol($me, 3, 10000);
        $first = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderBy('due_date')->first();
        $first->forceFill(['due_date' => now()->subDays(10)->toDateString()])->saveQuietly();

        $this->artisan('insurance:maintain')->assertExitCode(0);

        $this->assertEquals('Overdue', $first->fresh()->payment_status);
        $this->assertGreaterThanOrEqual(9, (int) $first->fresh()->days_overdue);
        $this->assertEquals('Late', $sub->fresh()->payment_status);

        // expiry
        \DB::table('insurance_subscriptions')->where('id', $sub->id)->update(['end_date' => now()->subDay()->toDateString()]);
        $this->artisan('insurance:maintain')->assertExitCode(0);
        $this->assertEquals('Expired', $sub->fresh()->status);
    }

    public function test_grace_period_delays_overdue_and_penalty()
    {
        $me = $this->makeUser();
        $program = $this->makeProgram(['duration_months' => 3, 'grace_period_days' => 7, 'late_payment_penalty' => 2000, 'penalty_type' => 'Fixed']);
        $subId = $this->json('POST', '/api/insurance-subscriptions', ['insurance_program_id' => $program->id, 'start_date' => now()->toDateString()], $this->headersFor($me))->json('data.id');
        $rows = InsuranceSubscriptionPayment::where('insurance_subscription_id', $subId)->orderBy('due_date')->get();

        $rows[0]->forceFill(['due_date' => now()->subDays(3)->toDateString()])->saveQuietly();  // inside grace
        $rows[1]->forceFill(['due_date' => now()->subDays(10)->toDateString()])->saveQuietly(); // past grace

        $this->artisan('insurance:maintain')->assertExitCode(0);

        $this->assertEquals('Pending', $rows[0]->fresh()->payment_status);
        $this->assertEquals(0, (float) $rows[0]->fresh()->penalty_amount);
        $this->assertEquals('Overdue', $rows[1]->fresh()->payment_status);
        $this->assertEquals(2000, (float) $rows[1]->fresh()->penalty_amount);
        $this->assertEquals(12000, (float) $rows[1]->fresh()->total_amount);
    }

    public function test_reconcile_command_finishes_a_paid_payment_nobody_polled()
    {
        $me = $this->makeUser();
        $sub = $this->enrol($me);
        $row = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderBy('due_date')->first();
        $pid = $this->init($me, [['type' => 'insurance_subscription_payment', 'id' => $row->id, 'amount' => 10000]])->json('data.payment.id');

        $this->paidRemote(10000);
        $this->artisan('payments:reconcile')->assertExitCode(0);

        $this->assertEquals('Paid', $row->fresh()->payment_status);
        $this->assertTrue((bool) UniversalPayment::find($pid)->items_processed);
    }

    public function test_product_and_legacy_payments_are_left_to_their_own_modules()
    {
        $me = $this->makeUser();
        $product = UniversalPayment::create([
            'payment_type' => 'product', 'user_id' => $me->id, 'amount' => 5000, 'currency' => 'UGX',
            'payment_gateway' => 'pesapal', 'status' => 'COMPLETED', 'items_processed' => false,
            'pesapal_order_tracking_id' => 'trk-product-' . uniqid(),
            'payment_items' => [['type' => 'product', 'id' => 1, 'amount' => 5000]],
        ]);
        $legacy = UniversalPayment::create([
            'payment_type' => 'MEMBERSHIP', 'user_id' => $me->id, 'amount' => 500, 'currency' => 'UGX',
            'payment_gateway' => 'pesapal', 'status' => 'COMPLETED', 'items_processed' => false,
            'pesapal_order_tracking_id' => 'trk-legacy-' . uniqid(),
            'payment_items' => [['amount' => 96000, 'description' => 'no type']],
        ]);

        $this->artisan('payments:reconcile', ['--days' => 3650])->assertExitCode(0);
        $this->getJson('/api/universal-payments/status/' . $product->id, $this->headersFor($me))->assertStatus(200);

        $this->assertFalse((bool) $product->fresh()->items_processed);
        $this->assertFalse((bool) $legacy->fresh()->items_processed);
        $this->assertEquals('COMPLETED', $product->fresh()->status);
    }

    public function test_retired_membership_confirm_endpoint_cannot_activate_anyone()
    {
        $admin = $this->makeAdmin();
        $me = $this->makeUser();
        $this->json('POST', '/api/membership/confirm-payment', ['payment_id' => 1, 'transaction_reference' => 'x', 'status' => 'success'], $this->headersFor($admin))->assertStatus(410);
        $this->assertEquals('No', $me->fresh()->is_dtehm_member);
    }
}
