<?php

namespace Tests\Feature\Admin;

use App\Admin\Actions\Withdraw\BatchApproveWithdraw;
use App\Models\AccountTransaction;
use App\Models\User;
use App\Models\WithdrawRequest;
use Tests\Feature\Insurance\InsuranceTestCase;

class WithdrawApprovalTest extends InsuranceTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->makeAdmin();
        $roleId = \DB::table('admin_roles')->where('slug', 'admin')->value('id');
        \DB::table('admin_role_users')->insert(['role_id' => $roleId, 'user_id' => $this->admin->id]);
        $this->actingAs($this->admin->fresh(), 'admin');
    }

    private function memberWithBalance(float $balance): User
    {
        $u = $this->makeUser();
        AccountTransaction::create(['user_id' => $u->id, 'amount' => $balance, 'transaction_date' => now(), 'description' => 'test credit', 'source' => 'deposit', 'created_by_id' => $this->admin->id]);
        return $u;
    }

    private function request(User $u, float $amount): WithdrawRequest
    {
        return WithdrawRequest::create(['user_id' => $u->id, 'amount' => $amount, 'account_balance_before' => $u->calculateAccountBalance(),
            'status' => 'pending', 'payment_method' => 'mobile_money', 'payment_phone_number' => '0700000000']);
    }

    public function test_approve_deducts_once_even_if_clicked_twice()
    {
        $u = $this->memberWithBalance(50000);
        $wr = $this->request($u, 20000);

        $this->assertTrue($wr->approve($this->admin, 'MOMO-123')['success']);
        $second = WithdrawRequest::find($wr->id)->approve($this->admin);   // second admin / double click
        $this->assertFalse($second['success']);
        $this->assertStringContainsString('already approved', $second['message']);

        $this->assertEquals(30000, (float) $u->calculateAccountBalance());
        $this->assertEquals(1, AccountTransaction::where('user_id', $u->id)->where('source', 'withdrawal')->count());
        $this->assertEquals('MOMO-123', $wr->fresh()->admin_note);
    }

    public function test_insufficient_balance_at_approval_time_is_refused()
    {
        $u = $this->memberWithBalance(10000);
        $wr = $this->request($u, 8000);
        AccountTransaction::create(['user_id' => $u->id, 'amount' => -5000, 'transaction_date' => now(), 'description' => 'spent', 'source' => 'withdrawal', 'created_by_id' => $this->admin->id]);

        $r = $wr->approve($this->admin);
        $this->assertFalse($r['success']);
        $this->assertStringContainsString('Insufficient balance', $r['message']);
        $this->assertEquals('pending', $wr->fresh()->status);
    }

    public function test_reject_needs_a_reason_and_deducts_nothing()
    {
        $u = $this->memberWithBalance(50000);
        $wr = $this->request($u, 20000);
        $this->assertFalse($wr->reject($this->admin, '  ')['success']);
        $this->assertTrue($wr->reject($this->admin, 'Wrong phone number')['success']);
        $this->assertEquals('rejected', $wr->fresh()->status);
        $this->assertEquals(50000, (float) $u->calculateAccountBalance());
        $this->assertFalse(WithdrawRequest::find($wr->id)->approve($this->admin)['success'], 'a rejected request cannot be approved later');
    }

    public function test_admin_routes_post_only_and_page_renders()
    {
        $u = $this->memberWithBalance(50000);
        $wr = $this->request($u, 20000);

        $this->get('/withdraw-requests')->assertStatus(200)->assertSee('Waiting for approval')->assertSee('Approve');
        $this->get('/withdraw-requests?tab=pending')->assertStatus(200);
        $this->get('/withdraw-requests/' . $wr->id)->assertStatus(200)->assertSee('Payout reference');

        $this->get('/withdraw-requests/' . $wr->id . '/approve')->assertRedirect();
        $this->assertEquals('pending', $wr->fresh()->status, 'a GET link must never approve');

        $this->post('/withdraw-requests/' . $wr->id . '/approve', ['note' => 'REF9'])->assertRedirect();
        $this->assertEquals('approved', $wr->fresh()->status);
        $this->assertEquals(30000, (float) $u->calculateAccountBalance());

        $wr2 = $this->request($u, 1000);
        $this->post('/withdraw-requests/' . $wr2->id . '/reject', ['reason' => ''])->assertRedirect();
        $this->assertEquals('pending', $wr2->fresh()->status);
        $this->post('/withdraw-requests/' . $wr2->id . '/reject', ['reason' => 'Duplicate'])->assertRedirect();
        $this->assertEquals('rejected', $wr2->fresh()->status);
    }

    public function test_batch_approve_processes_each_and_reports_problems()
    {
        $rich = $this->memberWithBalance(50000);
        $poor = $this->memberWithBalance(1000);
        $a = $this->request($rich, 10000);
        $b = $this->request($poor, 5000);

        (new BatchApproveWithdraw())->handle(WithdrawRequest::whereIn('id', [$a->id, $b->id])->get());
        $this->assertEquals('approved', $a->fresh()->status);
        $this->assertEquals('pending', $b->fresh()->status);
    }

    public function test_member_cannot_create_two_pending_requests()
    {
        $u = $this->memberWithBalance(50000);
        \App\Models\AccountPin::where('user_id', $u->id)->delete();
        $this->request($u, 5000);
        $h = $this->headersFor($u);
        $r = $this->postJson('/api/withdraw-requests', ['amount' => 5000, 'payment_method' => 'mobile_money', 'payment_phone_number' => '0700000000', 'pin' => '0000'], $h);
        $this->assertEquals(1, WithdrawRequest::where('user_id', $u->id)->where('status', 'pending')->count());
        $this->assertEquals(0, (int) ($r->json('code') ?? 0));
    }
}
