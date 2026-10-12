<?php

namespace Tests\Feature\Admin;

use App\Models\InsuranceSubscription;
use App\Models\InsuranceSubscriptionPayment;
use Tests\Feature\Insurance\InsuranceTestCase;

class InsuranceCenterTest extends InsuranceTestCase
{
    private function adminSession()
    {
        $admin = $this->makeAdmin();
        $roleId = \DB::table('admin_roles')->where('slug', 'administrator')->value('id')
            ?? \DB::table('admin_roles')->where('slug', 'admin')->value('id');
        \DB::table('admin_role_users')->insert(['role_id' => $roleId, 'user_id' => $admin->id]);
        $this->actingAs($admin->fresh(), 'admin');
        return $admin;
    }

    private function policy(int $months = 3): InsuranceSubscription
    {
        $program = $this->makeProgram(['duration_months' => $months, 'premium_amount' => 10000, 'grace_period_days' => 0, 'late_payment_penalty' => 2000, 'penalty_type' => 'Fixed']);
        return InsuranceSubscription::create(['user_id' => $this->makeUser()->id, 'insurance_program_id' => $program->id, 'start_date' => now()->toDateString(), 'status' => 'Active', 'payment_status' => 'Current', 'coverage_status' => 'Active']);
    }

    public function test_pages_open_for_admin()
    {
        $p = $this->policy();
        $this->adminSession();
        $this->get('/insurance-center')->assertStatus(200)->assertSee('Insurance Control Center');
        $this->get('/insurance-center?state=all&q=' . urlencode($p->policy_number))->assertStatus(200)->assertSee($p->policy_number);
        $this->get('/insurance-center/policy/' . $p->id)->assertStatus(200)->assertSee('Record payment');
        // (Accounts without an admin role are stopped by laravel-admin's permission layer, which exit()s -
        //  not testable in-process; the controller's own isAdmin() check is a second layer.)
    }

    public function test_record_payment_fills_oldest_months_first_and_refuses_overpayment()
    {
        $p = $this->policy(3);
        $this->adminSession();
        $this->from('/insurance-center')->post('/insurance-center/policy/' . $p->id . '/pay', ['amount' => 15000, 'method' => 'Cash', 'reference' => 'RCPT-1'])->assertRedirect('/insurance-center');
        $rows = InsuranceSubscriptionPayment::where('insurance_subscription_id', $p->id)->orderBy('due_date')->get();
        $this->assertEquals(['Paid', 'Partial', 'Pending'], $rows->pluck('payment_status')->all());
        $this->assertEquals(5000, (float) $rows[1]->paid_amount);
        $this->assertEquals('RCPT-1', $rows[0]->payment_reference);

        $this->from('/insurance-center')->post('/insurance-center/policy/' . $p->id . '/pay', ['amount' => 999999, 'method' => 'Cash']);
        $this->assertEquals(15000, (float) $p->fresh()->total_paid, 'overpayment refused');
    }

    public function test_waive_remove_fee_and_status_actions()
    {
        $p = $this->policy(3);
        $rows = InsuranceSubscriptionPayment::where('insurance_subscription_id', $p->id)->orderBy('due_date')->get();
        $rows[0]->forceFill(['due_date' => now()->subDays(5)->toDateString()])->saveQuietly();
        $this->artisan('insurance:maintain');
        $this->assertEquals(2000, (float) $rows[0]->fresh()->penalty_amount);
        $this->adminSession();

        $this->from('/x')->post('/insurance-center/month/' . $rows[0]->id . '/remove-fee');
        $this->assertEquals(0, (float) $rows[0]->fresh()->penalty_amount);
        $this->assertEquals(10000, (float) $rows[0]->fresh()->total_amount);

        $this->from('/x')->post('/insurance-center/month/' . $rows[2]->id . '/waive');
        $this->assertEquals('Waived', $rows[2]->fresh()->payment_status);
        $this->assertEquals(20000, (float) $p->fresh()->total_expected);

        $this->from('/x')->post('/insurance-center/policy/' . $p->id . '/status', ['action' => 'suspend', 'reason' => 'test']);
        $this->assertEquals('Suspended', $p->fresh()->status);
        $this->from('/x')->post('/insurance-center/policy/' . $p->id . '/status', ['action' => 'activate']);
        $this->assertEquals('Active', $p->fresh()->status);
        $this->from('/x')->post('/insurance-center/policy/' . $p->id . '/status', ['action' => 'cancel', 'reason' => 'left']);
        $this->assertEquals('Cancelled', $p->fresh()->status);
    }

    public function test_tools_run_and_show_output()
    {
        $this->adminSession();
        $this->from('/insurance-center')->post('/insurance-center/tool/repair-preview')->assertRedirect('/insurance-center')->assertSessionHas('tool_output');
        $this->from('/insurance-center')->post('/insurance-center/tool/maintain')->assertSessionHas('tool_output');
        $this->post('/insurance-center/tool/drop-everything')->assertStatus(404);
    }

    public function test_member_search_for_select_boxes()
    {
        $this->adminSession();
        $u = $this->makeUser(['first_name' => 'Zebedayo']);
        $res = $this->get('/api/users?q=Zebedayo')->assertStatus(200);
        $this->assertSame($u->id, $res->json('data.0.id'));
        $this->assertStringContainsString('Zebedayo', $res->json('data.0.text'));
    }
}
