<?php

namespace Tests\Feature\Insurance;

use App\Models\InsuranceSubscription;
use App\Models\InsuranceSubscriptionPayment;
use App\Models\UniversalPayment;
use App\Models\User;
use App\Services\PesapalApiClient;
use App\Services\PesapalService;

/**
 * Runs against a copy of the real production data (DB_DATABASE=dtehm_live_test) and uses REAL accounts
 * and rows. Everything is rolled back. Skips itself when the data it needs is not there.
 */
class LiveDataScenariosTest extends InsuranceTestCase
{
    protected array $remote = [];

    protected function realSubscription(): InsuranceSubscription
    {
        $sub = InsuranceSubscription::where('status', 'Active')->whereHas('payments')->first();
        if (!$sub || !User::find($sub->user_id)) {
            $this->markTestSkipped('No real active subscription in this database.');
        }
        return $sub;
    }

    public function test_real_members_see_exactly_their_own_policies_and_premiums()
    {
        $subs = InsuranceSubscription::where('status', 'Active')->get();
        if ($subs->count() < 2) {
            $this->markTestSkipped('Need at least 2 real subscriptions');
        }
        foreach ($subs->unique('user_id') as $sub) {
            $owner = User::find($sub->user_id);
            if (!$owner || $owner->isAdmin()) {
                continue; // admins/managers legitimately see every policy
            }
            $h = $this->headersFor($owner);

            $mine = $this->getJson('/api/insurance-subscriptions?per_page=100', $h)->assertStatus(200)->json('data');
            $this->assertEqualsCanonicalizing(
                InsuranceSubscription::where('user_id', $owner->id)->pluck('id')->all(),
                array_column($mine, 'id'),
                "member {$owner->id} must see exactly their own policies"
            );

            $rows = $this->getJson('/api/insurance-subscription-payments?per_page=100', $h)->assertStatus(200)->json('data');
            $this->assertEquals(
                InsuranceSubscriptionPayment::where('user_id', $owner->id)->count(),
                count($rows)
            );
            foreach ($rows as $r) {
                $this->assertEquals($owner->id, $r['user_id']);
            }

            foreach ($subs->where('user_id', '!=', $owner->id)->take(2) as $foreign) {
                $this->getJson('/api/insurance-subscriptions/' . $foreign->id, $h)->assertStatus(403);
            }
        }
    }

    public function test_real_managers_get_admin_access_and_plain_members_do_not()
    {
        $managerId = \DB::table('admin_role_users')->join('admin_roles', 'admin_roles.id', '=', 'admin_role_users.role_id')
            ->whereIn('admin_roles.slug', ['admin', 'manager'])->value('admin_role_users.user_id');
        $sub = $this->realSubscription();
        if (!$managerId) {
            $this->markTestSkipped('No real manager');
        }
        $manager = User::find($managerId);
        $this->getJson('/api/insurance-subscription-payments/stats', $this->headersFor($manager))->assertStatus(200);
        $this->getJson('/api/insurance-subscriptions?per_page=100', $this->headersFor($manager))->assertStatus(200);

        $plain = User::find($sub->user_id);
        if (!$plain->isAdmin()) {
            $this->getJson('/api/insurance-subscription-payments/stats', $this->headersFor($plain))->assertStatus(403);
        }
    }

    public function test_paying_a_real_overdue_premium_includes_its_penalty_and_settles_it()
    {
        $row = InsuranceSubscriptionPayment::where('payment_status', 'Overdue')->where('penalty_amount', '>', 0)->first();
        if (!$row) {
            $this->markTestSkipped('No real overdue premium with a penalty');
        }
        $owner = User::find($row->user_id);
        $owed = round((float) $row->total_amount - (float) $row->paid_amount, 2);
        $this->assertGreaterThan((float) $row->amount, $owed, 'penalty must be part of what is owed');

        $this->mock(PesapalApiClient::class, function ($m) {
            $m->shouldReceive('initializePayment')->andReturn(['success' => true, 'order_tracking_id' => 'trk-live-' . uniqid(), 'redirect_url' => 'https://pay.example.test/x', 'notification_id' => 'n']);
        });
        $test = $this;
        $this->mock(PesapalService::class, function ($m) use ($test) {
            $m->shouldReceive('getTransactionStatus')->andReturnUsing(fn () => $test->remote);
        });

        $h = $this->headersFor($owner);
        // paying only the base premium (what the old app sent) is a part-payment, never a silent "Paid"
        $pid = $this->postJson('/api/universal-payments/initialize', [
            'payment_type' => 'insurance_subscription_payment', 'payment_gateway' => 'pesapal',
            'payment_items' => [['type' => 'insurance_subscription_payment', 'id' => $row->id, 'amount' => $owed]],
        ], $h)->assertStatus(201)->json('data.payment.id');

        $this->remote = ['status_code' => 1, 'payment_status_description' => 'Completed', 'amount' => $owed, 'currency' => 'UGX'];
        $this->getJson('/api/universal-payments/status/' . $pid, $h)->assertStatus(200)->assertJsonPath('data.items_processed', true);

        $row->refresh();
        $this->assertEquals('Paid', $row->payment_status);
        $this->assertEquals($owed, (float) $row->paid_amount);

        $sub = InsuranceSubscription::find($row->insurance_subscription_id);
        $this->assertEquals(
            round((float) InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->where('payment_status', '!=', 'Waived')->sum('amount') -
                (float) InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->where('payment_status', '!=', 'Waived')->sum('paid_amount'), 2),
            (float) $sub->total_balance
        );
        $this->assertTrue((float) UniversalPayment::find($pid)->amount === $owed);
    }

    public function test_every_real_member_id_is_unique_and_in_the_current_format()
    {
        $bad = \DB::table('users')->whereNotNull('dtehm_member_id')->where('dtehm_member_id', '!=', '')
            ->whereRaw("dtehm_member_id NOT REGEXP '^DTEHM[0-9]{3,4}$'")->count();
        $this->assertEquals(0, $bad);
        $dupes = \DB::table('users')->whereNotNull('dtehm_member_id')->select('dtehm_member_id')->groupBy('dtehm_member_id')->havingRaw('COUNT(*) > 1')->count();
        $this->assertEquals(0, $dupes);
    }
}
