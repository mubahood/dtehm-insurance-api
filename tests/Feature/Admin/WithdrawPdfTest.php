<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\WithdrawRequest;
use Tests\Feature\Insurance\InsuranceTestCase;

class WithdrawPdfTest extends InsuranceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $admin = $this->makeAdmin();
        $roleId = \DB::table('admin_roles')->where('slug', 'admin')->value('id');
        \DB::table('admin_role_users')->insert(['role_id' => $roleId, 'user_id' => $admin->id]);
        $this->actingAs($admin->fresh(), 'admin');
    }

    private function pendingRequest(): WithdrawRequest
    {
        $u = $this->makeUser();
        return WithdrawRequest::create(['user_id' => $u->id, 'amount' => 25000, 'account_balance_before' => 30000, 'status' => 'pending',
            'payment_method' => 'mobile_money', 'payment_phone_number' => '0700000001']);
    }

    public function test_pdf_is_a_file_download_not_an_inline_page()
    {
        $this->pendingRequest();
        $res = $this->get('/withdraw-requests/pdf-pending');
        $res->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $res->headers->get('Content-Type'));
        $this->assertMatchesRegularExpression('/^attachment; filename="?DTEHM-pending-withdrawals-\d{4}-\d{2}-\d{2}-\d{4}\.pdf"?$/', $res->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_pdf_works_with_nothing_pending()
    {
        WithdrawRequest::where('status', 'pending')->update(['status' => 'rejected']);
        $res = $this->get('/withdraw-requests/pdf-pending')->assertStatus(200);
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_button_is_everywhere_with_the_live_count()
    {
        $wr = $this->pendingRequest();
        $count = WithdrawRequest::where('status', 'pending')->count();
        $needle = 'data-wr-pdf="1"';

        foreach (['/', '/users', '/withdraw-requests', '/withdraw-requests/' . $wr->id, '/withdraw-pins', '/insurance-center'] as $page) {
            $html = $this->get($page)->assertStatus(200)->getContent();
            $this->assertStringContainsString($needle, $html, "PDF button missing on $page");
            $this->assertStringContainsString('download="DTEHM-pending-withdrawals-', $html, "download attribute missing on $page");
            $this->assertStringContainsString('<span class="wr-pdf-count" title="Pending requests">' . $count . '</span>', $html, "count wrong on $page");
        }
        $this->get('/')->assertSee('Withdraw requests waiting')->assertSee('Review &amp; approve', false);
        $this->assertSame(1, substr_count($this->get('/users')->getContent(), 'class="wr-pdf-nav"'), 'top-bar button must appear once');
    }
}
