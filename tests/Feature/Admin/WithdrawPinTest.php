<?php

namespace Tests\Feature\Admin;

use App\Models\AccountPin;
use App\Models\AccountPinLog;
use App\Models\User;
use Tests\Feature\Insurance\InsuranceTestCase;

class WithdrawPinTest extends InsuranceTestCase
{
    private User $admin;
    private array $sms = [];
    private bool $smsWorks = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->makeAdmin();
        $roleId = \DB::table('admin_roles')->where('slug', 'admin')->value('id');
        \DB::table('admin_role_users')->insert(['role_id' => $roleId, 'user_id' => $this->admin->id]);
        $this->actingAs($this->admin->fresh(), 'admin');
        $this->app->instance('withdraw-pin.sms', function ($phone, $msg) {
            $this->sms[] = [$phone, $msg];
            return $this->smsWorks;
        });
    }

    private function pinOf(User $u): ?AccountPin
    {
        return AccountPin::where('user_id', $u->id)->first();
    }

    public function test_weak_pin_rules()
    {
        foreach (['0000', '1111', '1234', '4321', '7890', '9876', '0123', '123', '12a4', '2025'] as $p) {
            $this->assertTrue(AccountPin::isWeak($p), "$p should be weak");
        }
        foreach (['4817', '3095', '7261'] as $p) {
            $this->assertFalse(AccountPin::isWeak($p), "$p should be fine");
        }
        for ($i = 0; $i < 50; $i++) {
            $g = AccountPin::generatePin();
            $this->assertMatchesRegularExpression('/^\d{4}$/', $g);
            $this->assertFalse(AccountPin::isWeak($g));
        }
    }

    public function test_pages_open()
    {
        $u = $this->makeUser();
        $this->get('/withdraw-pins')->assertStatus(200)->assertSee('Locked out now');
        $this->get('/withdraw-pins?q=' . urlencode($u->last_name))->assertStatus(200)->assertSee('Manage PIN');
        $this->get('/withdraw-pins/' . $u->id)->assertStatus(200)->assertSee('Generate a new PIN');
        $this->get('/withdraw-pins/99999999')->assertStatus(404);
    }

    public function test_set_specific_pin_works_for_the_member_and_is_logged()
    {
        $u = $this->makeUser();
        $this->post("/withdraw-pins/{$u->id}/set", ['pin' => '4817', 'pin_confirmation' => '4817', 'send_sms' => 1, 'note' => 'at the desk'])->assertRedirect();

        $this->assertTrue($this->pinOf($u)->verifyPin('4817')['success'], 'member can use the new PIN');
        $this->assertNotEquals('4817', $this->pinOf($u)->pin_hash, 'stored hashed');
        $this->assertStringContainsString('4817', end($this->sms)[1]);

        $log = AccountPinLog::where('user_id', $u->id)->orderByDesc('id')->first();
        $this->assertEquals('admin_set', $log->action);
        $this->assertEquals('admin', $log->actor_type);
        $this->assertEquals($this->admin->id, $log->actor_id);
        $this->assertEquals('at the desk', $log->note);
        $this->assertTrue($log->sms_sent);
    }

    public function test_set_without_sms_still_warns_member_but_never_sends_the_pin()
    {
        $u = $this->makeUser();
        $this->post("/withdraw-pins/{$u->id}/set", ['pin' => '3095', 'pin_confirmation' => '3095'])->assertRedirect();
        $this->assertTrue($this->pinOf($u)->verifyPin('3095')['success']);
        $this->assertCount(1, $this->sms);
        $this->assertStringNotContainsString('3095', $this->sms[0][1]);
    }

    public function test_bad_input_changes_nothing()
    {
        $u = $this->makeUser();
        $this->post("/withdraw-pins/{$u->id}/set", ['pin' => '1234', 'pin_confirmation' => '1234']);
        $this->post("/withdraw-pins/{$u->id}/set", ['pin' => '4817', 'pin_confirmation' => '4818']);
        $this->post("/withdraw-pins/{$u->id}/set", ['pin' => '48', 'pin_confirmation' => '48']);
        $this->assertNull($this->pinOf($u));
        $this->assertCount(0, $this->sms);
    }

    public function test_generate_replaces_pin_sends_it_and_shows_it_once()
    {
        $u = $this->makeUser();
        AccountPin::create(['user_id' => $u->id, 'pin_hash' => bcrypt('4817'), 'failed_attempts' => 5, 'locked_until' => now()->addMinutes(20)]);

        $this->post("/withdraw-pins/{$u->id}/generate", ['note' => 'forgot'])->assertRedirect()->assertSessionHas('generated_pin');
        $flash = session('generated_pin');
        $this->assertTrue($this->pinOf($u)->verifyPin($flash['pin'])['success'], 'generated PIN works and the lock is cleared');
        $this->assertFalse($this->pinOf($u)->verifyPin('4817')['success'], 'old PIN no longer works');
        $this->assertStringContainsString($flash['pin'], end($this->sms)[1]);

        $this->get('/withdraw-pins/' . $u->id)->assertSee($flash['pin']);   // shown once...
        $this->get('/withdraw-pins/' . $u->id)->assertDontSee('It will not be shown again'); // ...then gone
    }

    public function test_generate_when_sms_fails_tells_admin()
    {
        $this->smsWorks = false;
        $u = $this->makeUser();
        $this->post("/withdraw-pins/{$u->id}/generate")->assertRedirect();
        $this->assertFalse(AccountPinLog::where('user_id', $u->id)->orderByDesc('id')->first()->sms_sent);
        $this->get('/withdraw-pins/' . $u->id)->assertSee('SMS failed');
    }

    public function test_unlock_and_remove()
    {
        $u = $this->makeUser();
        $pin = AccountPin::create(['user_id' => $u->id, 'pin_hash' => bcrypt('4817')]);
        for ($i = 0; $i < 5; $i++) {
            $pin->fresh()->verifyPin('0000');
        }
        $this->assertTrue($this->pinOf($u)->isLocked());
        $this->assertTrue(AccountPinLog::where('user_id', $u->id)->where('action', 'locked')->exists(), 'lock is recorded');
        $this->get('/withdraw-pins')->assertSee($u->name);

        $this->post("/withdraw-pins/{$u->id}/unlock")->assertRedirect();
        $this->assertFalse($this->pinOf($u)->isLocked());
        $this->assertTrue($this->pinOf($u)->verifyPin('4817')['success']);

        $this->post("/withdraw-pins/{$u->id}/remove")->assertRedirect();
        $this->assertNull($this->pinOf($u));
        $this->assertEquals('admin_removed', AccountPinLog::where('user_id', $u->id)->orderByDesc('id')->value('action'));
        $this->post("/withdraw-pins/{$u->id}/remove")->assertRedirect(); // nothing to remove: no error page
    }

    public function test_member_changes_are_logged_as_member()
    {
        $u = $this->makeUser();
        $pin = AccountPin::create(['user_id' => $u->id, 'pin_hash' => bcrypt('4817')]);
        $pin->setPin('3095');
        $actions = AccountPinLog::where('user_id', $u->id)->orderBy('id')->pluck('actor_type', 'action')->all();
        $this->assertEquals(['created' => 'member', 'changed' => 'member'], $actions);
    }

    public function test_users_list_shows_pin_status_with_link()
    {
        $u = $this->makeUser();
        AccountPin::create(['user_id' => $u->id, 'pin_hash' => bcrypt('4817')]);
        $this->get('/users?id=' . $u->id)->assertStatus(200)->assertSee('withdraw-pins/' . $u->id, false);
    }
}
