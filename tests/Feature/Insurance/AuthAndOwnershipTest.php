<?php

namespace Tests\Feature\Insurance;

use App\Models\InsuranceSubscription;
use App\Models\InsuranceSubscriptionPayment;

class AuthAndOwnershipTest extends InsuranceTestCase
{
    /** @dataProvider protectedRoutes */
    public function test_routes_reject_anonymous_callers($method, $uri)
    {
        $this->json($method, $uri)->assertStatus(401);
    }

    public function protectedRoutes(): array
    {
        return [
            ['GET', '/api/insurance-programs'],
            ['GET', '/api/insurance-subscriptions'],
            ['POST', '/api/insurance-subscriptions'],
            ['GET', '/api/insurance-subscription-payments'],
            ['POST', '/api/insurance-subscription-payments/1/pay'],
            ['PUT', '/api/insurance-subscription-payments/1'],
            ['GET', '/api/medical-service-requests'],
            ['POST', '/api/medical-service-requests'],
            ['POST', '/api/universal-payments/initialize'],
            ['GET', '/api/universal-payments'],
            ['GET', '/api/universal-payments/status/1'],
            ['POST', '/api/universal-payments/1/process'],
            ['GET', '/api/membership-status'],
            ['POST', '/api/membership-payment/confirm'],
            ['POST', '/api/membership/confirm-payment'],
        ];
    }

    public function test_spoofed_user_id_header_is_not_accepted()
    {
        $admin = $this->makeAdmin();
        $this->json('GET', '/api/insurance-subscription-payments/stats', [], ['User-Id' => $admin->id])->assertStatus(401);
        $this->json('GET', '/api/insurance-programs', [], ['User-Id' => $admin->id])->assertStatus(401);
    }

    public function test_garbage_token_is_rejected()
    {
        $this->json('GET', '/api/insurance-programs', [], ['Authorization' => 'Bearer not.a.token'])->assertStatus(401);
    }

    public function test_member_cannot_use_admin_routes()
    {
        $member = $this->makeUser();
        $h = $this->headersFor($member);
        $this->json('GET', '/api/insurance-subscription-payments/stats', [], $h)->assertStatus(403);
        $this->json('POST', '/api/insurance-subscription-payments/1/pay', ['paid_amount' => 5, 'payment_method' => 'Cash'], $h)->assertStatus(403);
        $this->json('PUT', '/api/insurance-subscription-payments/1', ['payment_status' => 'Waived'], $h)->assertStatus(403);
        $this->json('POST', '/api/insurance-programs', ['name' => 'x'], $h)->assertStatus(403);
        $this->json('POST', '/api/universal-payments/1/process', [], $h)->assertStatus(403);
        $this->json('POST', '/api/membership-payment/confirm', ['payment_id' => 1], $h)->assertStatus(403);
        $this->json('POST', '/api/membership/confirm-payment', [], $h)->assertStatus(403);
    }

    public function test_member_cannot_see_or_cancel_another_members_subscription()
    {
        $program = $this->makeProgram();
        $owner = $this->makeUser();
        $other = $this->makeUser();

        $sub = $this->json('POST', '/api/insurance-subscriptions', [
            'insurance_program_id' => $program->id, 'start_date' => now()->toDateString(),
        ], $this->headersFor($owner))->assertStatus(201)->json('data');

        $h = $this->headersFor($other);
        $this->json('GET', '/api/insurance-subscriptions/' . $sub['id'], [], $h)->assertStatus(403);
        $this->json('POST', '/api/insurance-subscriptions/' . $sub['id'] . '/cancel', ['cancellation_reason' => 'x'], $h)->assertStatus(403);
        $this->json('GET', '/api/insurance-subscriptions/user/' . $owner->id, [], $h)->assertStatus(403);
        $this->json('GET', '/api/insurance-subscription-payments/user/' . $owner->id, [], $h)->assertStatus(403);

        $list = $this->json('GET', '/api/insurance-subscriptions', [], $h)->assertStatus(200)->json('data');
        $this->assertCount(0, $list);
    }

    public function test_member_cannot_enrol_someone_else_and_ids_come_from_token()
    {
        $program = $this->makeProgram();
        $me = $this->makeUser();
        $victim = $this->makeUser();

        $this->json('POST', '/api/insurance-subscriptions', [
            'user_id' => $victim->id, 'insurance_program_id' => $program->id, 'start_date' => now()->toDateString(),
        ], $this->headersFor($me))->assertStatus(403);

        $res = $this->json('POST', '/api/insurance-subscriptions', [
            'insurance_program_id' => $program->id, 'start_date' => now()->toDateString(),
            // mass-assignment attempts must be ignored
            'status' => 'Pending', 'total_paid' => 999999, 'premium_amount' => 1, 'policy_number' => 'HACK',
        ], $this->headersFor($me))->assertStatus(201);

        $sub = InsuranceSubscription::find($res->json('data.id'));
        $this->assertEquals($me->id, $sub->user_id);
        $this->assertEquals('Active', $sub->status);
        $this->assertEquals(10000, (float) $sub->premium_amount);
        $this->assertNotEquals('HACK', $sub->policy_number);
        $this->assertEquals(0, (float) $sub->total_paid);
    }

    public function test_duplicate_enrolment_in_same_program_is_refused()
    {
        $program = $this->makeProgram();
        $me = $this->makeUser();
        $h = $this->headersFor($me);
        $body = ['insurance_program_id' => $program->id, 'start_date' => now()->toDateString()];
        $this->json('POST', '/api/insurance-subscriptions', $body, $h)->assertStatus(201);
        $this->json('POST', '/api/insurance-subscriptions', $body, $h)->assertStatus(400);
        $this->assertEquals(1, InsuranceSubscription::where('user_id', $me->id)->count());
    }

    public function test_enrolment_generates_one_payment_row_per_month_and_totals_are_right()
    {
        $program = $this->makeProgram(['duration_months' => 3]);
        $me = $this->makeUser();
        $res = $this->json('POST', '/api/insurance-subscriptions', [
            'insurance_program_id' => $program->id, 'start_date' => now()->toDateString(),
        ], $this->headersFor($me))->assertStatus(201);

        $sub = InsuranceSubscription::find($res->json('data.id'));
        $this->assertEquals(3, InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->count());
        $this->assertEquals(30000, (float) $sub->total_expected);
        $this->assertEquals(30000, (float) $sub->total_balance);
        $this->assertEquals('Yes', $sub->prepared);
    }

    public function test_payments_filter_by_subscription_applies_to_members_too()
    {
        $me = $this->makeUser();
        $h = $this->headersFor($me);
        $p1 = $this->makeProgram(['name' => 'A ' . uniqid()]);
        $p2 = $this->makeProgram(['name' => 'B ' . uniqid()]);
        $s1 = $this->json('POST', '/api/insurance-subscriptions', ['insurance_program_id' => $p1->id, 'start_date' => now()->toDateString()], $h)->json('data.id');
        $s2 = $this->json('POST', '/api/insurance-subscriptions', ['insurance_program_id' => $p2->id, 'start_date' => now()->toDateString()], $h)->json('data.id');

        $rows = $this->json('GET', '/api/insurance-subscription-payments', ['insurance_subscription_id' => $s2, 'per_page' => 100], $h)->assertStatus(200)->json('data');
        $this->assertNotEmpty($rows);
        foreach ($rows as $r) {
            $this->assertEquals($s2, $r['insurance_subscription_id']);
        }
        $this->assertNotContains($s1, array_column($rows, 'insurance_subscription_id'));
    }

    public function test_sort_injection_and_huge_page_size_are_neutralised()
    {
        $me = $this->makeUser();
        $h = $this->headersFor($me);
        $this->json('GET', '/api/insurance-subscription-payments', ['sort_by' => 'id; DROP TABLE users', 'sort_order' => 'sideways', 'per_page' => 100000], $h)
            ->assertStatus(200)->assertJsonPath('pagination.per_page', 100);
    }

    public function test_errors_never_leak_exception_text()
    {
        $me = $this->makeUser();
        $res = $this->json('GET', '/api/insurance-subscriptions/99999999', [], $this->headersFor($me));
        $res->assertStatus(404);
        $this->assertStringNotContainsStringIgnoringCase('No query results', json_encode($res->json()));
        $this->assertStringNotContainsStringIgnoringCase('SQLSTATE', json_encode($res->json()));
    }

    public function test_public_sponsor_list_exposes_no_contact_details()
    {
        $sponsor = $this->makeUser(['is_dtehm_member' => 'Yes']);
        $res = $this->json('GET', '/api/insurance-users', ['is_dtehm_member' => 'Yes'])->assertStatus(200);
        $rows = $res->json('data');
        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            foreach (['email', 'phone_number', 'dob', 'address', 'sponsor_id'] as $k) {
                $this->assertArrayNotHasKey($k, $row, "public sponsor list leaked {$k}");
            }
        }
        // without the sponsor filter an anonymous caller gets nothing
        $this->json('GET', '/api/insurance-users')->assertJsonPath('code', 0);
    }

    public function test_member_cannot_promote_themselves_or_edit_others()
    {
        $me = $this->makeUser();
        $other = $this->makeUser();
        $h = $this->headersFor($me);

        $this->json('PUT', '/api/insurance-users/' . $other->id, ['first_name' => 'Hacked'], $h)->assertJsonPath('code', 0);
        $this->assertNotEquals('Hacked', $other->fresh()->first_name);

        $this->json('PUT', '/api/insurance-users/' . $me->id, [
            'first_name' => 'Okay', 'user_type' => 'Admin', 'status' => 'Active',
            'is_dtehm_member' => 'Yes', 'is_stockist' => 'Yes',
        ], $h);
        $fresh = $me->fresh();
        $this->assertEquals('Customer', $fresh->user_type);
        $this->assertEquals('No', $fresh->is_dtehm_member);
        $this->assertNotEquals('Yes', $fresh->is_stockist);
    }
}
