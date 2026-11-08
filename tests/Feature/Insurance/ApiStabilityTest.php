<?php

namespace Tests\Feature\Insurance;

use App\Models\AccountPin;
use App\Models\AccountTransaction;
use App\Models\Disbursement;
use App\Models\MultipleOrder;
use App\Models\OrderedItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\UniversalPayment;
use App\Models\User;
use App\Services\PesapalApiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Access rules and money integrity across the API, with the requests the installed apps send.
 * Pesapal is mocked; SMS and the Pesapal client refuse to run in the testing environment.
 */
class ApiStabilityTest extends InsuranceTestCase
{
    private function member(array $attrs = [])
    {
        return $this->makeUser(array_merge(['is_dtehm_member' => 'Yes', 'is_stockist' => 'No'], $attrs));
    }

    private function credit($user, float $amount): void
    {
        AccountTransaction::create([
            'user_id' => $user->id, 'amount' => $amount, 'source' => 'deposit',
            'description' => 'test credit', 'transaction_date' => now(), 'created_by_id' => $user->id,
        ]);
    }

    private function balance($user): float
    {
        return (float) AccountTransaction::where('user_id', $user->id)->sum('amount');
    }

    private function product(): Product
    {
        return Product::query()->where('price_1', '>', 0)->where('in_stock', 'Yes')->firstOrFail();
    }

    /** Exactly the cart body ModernCartController builds. */
    private function cart($buyer, $sponsor, $shop, array $extra = []): array
    {
        return $extra + [
            'user_id' => $buyer->id,
            'items' => [['product_id' => $this->product()->id, 'product_name' => 'x', 'product_price' => '1', 'quantity' => 1, 'subtotal' => '1']],
            'subtotal' => '1', 'delivery_fee' => '0', 'total_amount' => '1', 'currency' => 'UGX',
            'payment_method' => 'pesapal', 'delivery_method' => 'pickup', 'customer_name' => 'a b',
            'customer_email' => '', 'customer_phone' => $buyer->phone_number,
            'sponsor_id' => (string) $sponsor->id, 'stockist_id' => (string) $shop->id, 'is_paid_by_admin' => '0',
        ];
    }

    private function orderFor($buyer): MultipleOrder
    {
        $id = $this->json('POST', '/api/multiple-orders/create', $this->cart($buyer, $this->member(), $this->member(['is_stockist' => 'Yes'])), $this->headersFor($buyer))
            ->assertOk()->json('data.id');

        return MultipleOrder::findOrFail($id);
    }

    /** @dataProvider guestRefused */
    public function test_a_guest_is_refused(string $method, string $uri): void
    {
        $this->json($method, $uri, ['user' => '1', 'user_id' => '1', 'id' => '1'])->assertStatus(401);
    }

    public function guestRefused(): array
    {
        return [
            ['POST', '/api/delete-account'], ['POST', '/api/password-change'], ['POST', '/api/account-verification'],
            ['POST', '/api/become-vendor'], ['POST', '/api/post-media-upload'], ['POST', '/api/product-create'],
            ['POST', '/api/products-delete'], ['POST', '/api/images-delete'], ['GET', '/api/api/account-pins?is_not_private=1'],
            ['POST', '/api/api/users'], ['POST', '/api/dashboard/fix-project-shares'], ['GET', '/api/dashboard/debug-projects'],
            ['GET', '/api/user-accounts/users-list'], ['GET', '/api/ajax/member-hierarchy/DTEHM001'],
            ['POST', '/api/multiple-orders/create'], ['GET', '/api/multiple-orders/user/1'], ['POST', '/api/multiple-orders/1/checkout-credit-balance'],
            ['POST', '/api/product-purchase/initialize'], ['POST', '/api/product-purchase/confirm'], ['GET', '/api/product-purchase/1'],
            ['POST', '/api/transactions'], ['GET', '/api/transactions/stats'], ['GET', '/api/transactions/user/1/balance'],
            ['DELETE', '/api/transactions/1'], ['POST', '/api/investment-transactions'], ['GET', '/api/investment-transactions'],
            ['POST', '/api/disbursements'], ['DELETE', '/api/disbursements/1'], ['POST', '/api/pesapal/register-ipn'], ['GET', '/api/pesapal/config'],
            ['POST', '/api/orders/confirm-payment'],
        ];
    }

    /** @dataProvider adminOnly */
    public function test_a_member_cannot_use_admin_routes(string $method, string $uri): void
    {
        $this->json($method, $uri, [], $this->headersFor($this->member()))->assertStatus(403);
    }

    public function adminOnly(): array
    {
        return [
            ['GET', '/api/api/users'], ['POST', '/api/api/users'], ['DELETE', '/api/insurance-users/1'],
            ['GET', '/api/user-accounts/users-list'], ['PUT', '/api/projects/1'], ['DELETE', '/api/projects/1'],
            ['POST', '/api/projects/transactions'], ['POST', '/api/disbursements'], ['POST', '/api/investment-transactions'],
            ['PUT', '/api/transactions/1'], ['DELETE', '/api/transactions/1'], ['GET', '/api/admin/pesapal/analytics'],
            ['POST', '/api/pesapal/register-ipn'], ['POST', '/api/orders/confirm-payment'], ['POST', '/api/dashboard/fix-project-shares'],
        ];
    }

    public function test_public_screens_stay_public(): void
    {
        foreach (['products', 'products-1', 'categories', 'manifest', 'config/check/maintenance', 'config/membership/fees', 'app-version/check', 'insurance-users?is_not_private=1'] as $uri) {
            $this->json('GET', "/api/$uri")->assertOk();
        }
    }

    public function test_the_admin_panel_commission_calculator_needs_no_token(): void
    {
        $this->json('POST', '/api/ajax/calculate-commissions', [])->assertStatus(400);
        $this->assertSame([], $this->json('GET', '/api/ajax?model=User&search_by_1=password&q=$2y')->assertOk()->json('data'));
        $this->assertSame([], $this->json('GET', '/api/ajax?model=NoSuchModel&search_by_1=name')->assertOk()->json('data'));
        $this->json('GET', '/api/ajax?model=User&search_by_1=name&search_by_2=id&q=a')->assertOk()->assertJsonStructure(['data']);
    }

    public function test_delete_account_only_ever_deletes_the_caller(): void
    {
        $victim = $this->member();
        $caller = $this->member();

        $this->json('POST', '/api/delete-account', ['user' => (string) $victim->id], $this->headersFor($caller))
            ->assertOk()->assertJsonPath('code', 1);

        $this->assertSame('Active', $victim->fresh()->status);
        $this->assertSame('Deleted', $caller->fresh()->status);
    }

    public function test_a_member_cannot_mark_their_own_order_as_paid(): void
    {
        $buyer = $this->member();
        $victim = $this->member();

        $id = $this->json('POST', '/api/multiple-orders/create', $this->cart($buyer, $this->member(), $this->member(), [
            'is_paid_by_admin' => '1', 'user_id' => $victim->id,
        ]), $this->headersFor($buyer))->assertOk()->assertJsonPath('code', 1)->json('data.id');

        $order = MultipleOrder::findOrFail($id);
        $this->assertSame('PENDING', $order->payment_status);
        $this->assertFalse((bool) $order->is_paid_by_admin);
        $this->assertSame($buyer->id, (int) $order->user_id, 'an order is always for the member who placed it');
    }

    public function test_an_admin_can_still_record_a_cash_sale(): void
    {
        $buyer = $this->member();
        $sponsor = $this->member();
        $shop = $this->member(['is_stockist' => 'Yes']);
        $before = OrderedItem::count();

        $this->json('POST', '/api/multiple-orders/create', $this->cart($buyer, $sponsor, $shop, ['is_paid_by_admin' => '1', 'admin_payment_note' => 'Cash']), $this->headersFor($this->makeAdmin()))
            ->assertOk()->assertJsonPath('data.admin_bypass', true)->assertJsonPath('data.payment_status', 'COMPLETED');

        $this->assertSame($before + 1, OrderedItem::count());
    }

    public function test_only_the_buyer_can_spend_their_balance(): void
    {
        $buyer = $this->member();
        $this->credit($buyer, 5000000);
        $order = $this->orderFor($buyer);
        $stranger = $this->member();

        foreach (["/api/multiple-orders/{$order->id}/checkout-credit-balance", "/api/multiple-orders/{$order->id}/cancel", "/api/multiple-orders/{$order->id}/convert"] as $uri) {
            $this->json('POST', $uri, [], $this->headersFor($stranger))->assertStatus(403);
        }
        $this->json('GET', "/api/multiple-orders/{$order->id}", [], $this->headersFor($stranger))->assertStatus(403);
        $this->json('GET', "/api/multiple-orders/user/{$buyer->id}", [], $this->headersFor($stranger))->assertStatus(403);
        $this->assertSame(5000000.0, $this->balance($buyer));

        $this->json('GET', "/api/multiple-orders/user/{$buyer->id}", [], $this->headersFor($buyer))->assertOk();
        $this->json('GET', "/api/multiple-orders/{$order->id}", [], $this->headersFor($buyer))->assertOk();
        $this->json('GET', "/api/multiple-orders/{$order->id}", [], $this->headersFor(User::find($order->sponsor_user_id)))->assertOk();
    }

    public function test_paying_from_balance_twice_charges_and_pays_commission_once(): void
    {
        $buyer = $this->member();
        $this->credit($buyer, 5000000);
        $order = $this->orderFor($buyer);
        $items = OrderedItem::count();
        $headers = $this->headersFor($buyer);

        $this->json('POST', "/api/multiple-orders/{$order->id}/checkout-credit-balance", [], $headers)
            ->assertOk()->assertJsonPath('data.converted_to_sales', true);
        $this->json('POST', "/api/multiple-orders/{$order->id}/checkout-credit-balance", [], $headers)->assertStatus(400);
        $this->json('POST', "/api/multiple-orders/{$order->id}/convert", [], $headers);

        $this->assertSame(5000000.0 - (float) $order->total_amount, $this->balance($buyer));
        $this->assertSame($items + 1, OrderedItem::count());
    }

    public function test_a_status_poll_cannot_turn_a_paid_order_back_to_unpaid(): void
    {
        $order = $this->orderFor($this->member());
        $order->update(['payment_status' => 'COMPLETED', 'conversion_status' => 'COMPLETED']);

        (new \App\Services\MultipleOrderPesapalService())->updatePaymentStatus($order, ['status_code' => 0]);

        $this->assertSame('COMPLETED', $order->fresh()->payment_status);
    }

    private function mockPesapal(string $status = 'Completed'): void
    {
        $this->mock(PesapalApiClient::class, function ($m) use ($status) {
            $m->shouldReceive('initializePayment')->andReturnUsing(fn () => [
                'success' => true, 'order_tracking_id' => 'trk-' . uniqid(), 'redirect_url' => 'https://pay.example.test/x', 'notification_id' => 'ipn',
            ]);
            $m->shouldReceive('checkPaymentStatus')->andReturn(['success' => true, 'data' => ['payment_status_description' => $status, 'status_code' => 1]]);
        });
    }

    public function test_a_direct_purchase_confirms_once_and_answers_success(): void
    {
        $this->mockPesapal();
        $buyer = $this->member();
        $sponsor = $this->member();
        $shop = $this->member(['is_stockist' => 'Yes']);
        $headers = $this->headersFor($buyer);
        $items = OrderedItem::count();

        $payment = $this->json('POST', '/api/product-purchase/initialize', [
            'product_id' => $this->product()->id, 'quantity' => 1, 'sponsor_id' => (string) $sponsor->id,
            'stockist_id' => (string) $shop->id, 'user_id' => $buyer->id, 'is_paid_by_admin' => true,
        ], $headers)->assertStatus(201)->json('data.payment');
        $this->assertSame('PROCESSING', UniversalPayment::find($payment['id'])->status, 'a member cannot pay with the admin flag');

        $this->json('POST', '/api/product-purchase/confirm', ['payment_id' => $payment['id']], $this->headersFor($this->member()))->assertStatus(403);

        // Used to be a 500 after the sale had already gone through, so the app reported a failure
        $this->json('POST', '/api/product-purchase/confirm', ['payment_id' => $payment['id']], $headers)->assertOk()->assertJsonPath('code', 1);
        $this->json('POST', '/api/product-purchase/confirm', ['payment_id' => $payment['id']], $headers)->assertOk()->assertJsonPath('data.already_processed', true);

        $this->assertSame($items + 1, OrderedItem::count());
    }

    public function test_another_members_financial_records_stay_private(): void
    {
        $owner = $this->member();
        $this->credit($owner, 1000);
        $row = AccountTransaction::where('user_id', $owner->id)->firstOrFail();
        $stranger = $this->headersFor($this->member());

        $this->assertSame(0, $this->json('GET', "/api/account-transactions/{$row->id}", [], $stranger)->json('code'));
        $this->assertSame(1, $this->json('GET', "/api/account-transactions/{$row->id}", [], $this->headersFor($owner))->json('code'));
        $this->json('GET', "/api/transactions/user/{$owner->id}/balance", [], $stranger)->assertStatus(403);
        $this->json('GET', "/api/transactions/user/{$owner->id}/balance", [], $this->headersFor($owner))->assertOk();

        // The User-Id header no longer identifies anyone
        $this->json('GET', '/api/user/balance', [], ['User-Id' => (string) $owner->id, 'Accept' => 'application/json'])->assertStatus(401);
        $this->json('GET', '/api/product-purchase/history', [], ['User-Id' => (string) $owner->id, 'Accept' => 'application/json'])->assertStatus(401);
        $this->json('GET', '/api/user-roles', [], ['User-Id' => (string) $owner->id, 'Accept' => 'application/json'])->assertStatus(401);
        $this->json('GET', '/api/user-roles', [], $this->headersFor($owner))->assertOk();
    }

    public function test_a_member_ledger_entry_is_always_their_own_and_pending(): void
    {
        $member = $this->member();
        $other = $this->member();

        $this->json('POST', '/api/transactions', [
            'user_id' => $other->id, 'amount' => 9000000, 'type' => 'DEPOSIT', 'status' => 'COMPLETED',
        ], $this->headersFor($member))->assertStatus(201)
            ->assertJsonPath('data.user_id', $member->id)->assertJsonPath('data.status', 'PENDING');
    }

    public function test_the_home_screen_shows_a_member_only_their_own_medical_requests(): void
    {
        $patient = $this->member();
        $this->json('POST', '/api/medical-service-requests', [
            'service_type' => 'consultation', 'urgency_level' => 'normal', 'symptoms_description' => 'A headache for two days now', 'contact_phone' => $patient->phone_number,
        ], $this->headersFor($patient));

        $own = $this->json('GET', '/api/dashboard/main', [], $this->headersFor($patient))->assertOk()->json('data.recent_medical_services');
        $this->assertCount(1, $own);
        $this->assertNotEmpty($own[0]['service_name']);
        $this->assertNotEmpty($own[0]['description']);
        $this->assertSame([], $this->json('GET', '/api/dashboard/main', [], $this->headersFor($this->member()))->assertOk()->json('data.recent_medical_services'));
        $this->assertNotEmpty($this->json('GET', '/api/dashboard/main', [], $this->headersFor($this->makeAdmin()))->assertOk()->json('data.recent_medical_services'));
    }

    public function test_a_password_reset_really_lets_the_member_log_in(): void
    {
        $member = $this->member(['email' => 'reset' . uniqid() . '@example.test', 'intro' => '482913']);

        $this->json('POST', '/api/users/login', [
            'task' => 'reset_password', 'username' => 'x', 'email' => $member->email, 'code' => '482913', 'password' => 'brand-new-pass',
        ])->assertOk()->assertJsonPath('code', 1);

        $this->assertTrue(password_verify('brand-new-pass', User::find($member->id)->password), 'it was hashed twice and could never match');
    }

    public function test_a_bio_is_not_a_password_reset_code(): void
    {
        $member = $this->member(['email' => 'bio' . uniqid() . '@example.test', 'intro' => 'Kampala']);
        $hash = $member->password;

        $this->json('POST', '/api/users/login', [
            'task' => 'reset_password', 'username' => 'x', 'email' => $member->email, 'code' => 'Kampala', 'password' => 'attacker-pass',
        ])->assertJsonPath('code', 0);

        $this->assertSame($hash, User::find($member->id)->password);
    }

    public function test_pin_reset_answers_what_the_app_expects_and_stops_guessing(): void
    {
        $member = $this->member();
        $headers = $this->headersFor($member);

        $this->json('POST', '/api/account-pin/request-reset', [], $headers)->assertOk()->assertJsonPath('code', 200);
        $real = cache()->get('pin_reset_otp_' . $member->id);
        $this->assertNotNull($real);

        $wrong = $real === 111111 ? '222222' : '111111';
        $body = ['otp' => $wrong, 'new_pin' => '4827', 'new_pin_confirmation' => '4827'];
        for ($i = 0; $i < 5; $i++) {
            $this->json('POST', '/api/account-pin/reset-with-otp', $body, $headers)->assertStatus(400);
        }
        $this->assertNull(cache()->get('pin_reset_otp_' . $member->id), 'five wrong tries cancel the code');
    }

    public function test_network_tree_is_for_the_member_their_upline_and_admins(): void
    {
        $upline = $this->member();
        $down = $this->member(['sponsor_id' => $upline->dtehm_member_id]);
        $stranger = $this->member();

        $uri = "/api/user/network-tree?user_id={$down->id}";
        $this->json('GET', $uri)->assertStatus(401);
        $this->json('GET', $uri, [], $this->headersFor($stranger))->assertStatus(403);
        $this->json('GET', $uri, [], $this->headersFor($upline))->assertOk();
        $this->json('GET', $uri, [], $this->headersFor($down))->assertOk();
        $this->json('GET', $uri, [], $this->headersFor($this->makeAdmin()))->assertOk();
        $this->json('GET', '/api/user/network-tree', [], $this->headersFor($stranger))->assertOk();
    }

    public function test_leader_ranks_hide_other_peoples_phone_numbers(): void
    {
        $member = $this->member();

        $this->assertSame('', $this->json('GET', "/api/leader-ranks/{$member->id}")->assertOk()->json('data.user.phone_number'));
        $this->assertSame($member->phone_number, $this->json('GET', "/api/leader-ranks/{$member->id}", [], $this->headersFor($member))->json('data.user.phone_number'));
    }

    public function test_only_the_owner_or_an_admin_can_change_a_product(): void
    {
        $owner = $this->member();
        $product = $this->product();
        $product->user = $owner->id;
        $product->saveQuietly();

        $this->assertSame(0, $this->json('POST', '/api/products-delete', ['id' => $product->id], $this->headersFor($this->member()))->json('code'));
        $this->assertNotNull(Product::find($product->id));
        $this->assertSame(0, $this->json('POST', '/api/product-create', [
            'is_edit' => 'Yes', 'id' => $product->id, 'local_id' => '123456', 'p_type' => 'No', 'price_1' => 1, 'price_2' => 1, 'category' => 1,
        ], $this->headersFor($this->member()))->json('code'));
    }

    public function test_a_second_login_on_the_same_phone_moves_the_device(): void
    {
        $first = $this->member();
        $second = $this->member();
        $playerId = 'player-' . uniqid();
        $body = fn ($u) => ['user_id' => $u->id, 'player_id' => $playerId, 'device_type' => 'mobile', 'app_version' => '1'];

        $this->json('POST', '/api/onesignal/register-device', $body($first))->assertOk()->assertJsonPath('success', true);
        $this->json('POST', '/api/onesignal/register-device', $body($second))->assertOk()->assertJsonPath('success', true);

        $this->assertSame($second->id, (int) DB::table('onesignal_devices')->where('player_id', $playerId)->value('user_id'));
    }

    public function test_a_disbursement_pays_each_investor_exactly_once(): void
    {
        $admin = $this->makeAdmin();
        $investor = $this->member();
        $project = Project::query()->firstOrFail();
        $share = new ProjectShare();
        $share->forceFill([
            'project_id' => $project->id, 'investor_id' => $investor->id, 'number_of_shares' => 5,
            'total_amount_paid' => 500000, 'share_price_at_purchase' => 100000, 'purchase_date' => now()->toDateString(),
        ])->saveQuietly();
        ProjectShare::where('project_id', $project->id)->where('id', '!=', $share->id)->delete();
        $this->json('POST', '/api/investment-transactions', [
            'project_id' => $project->id, 'amount' => 900000, 'type' => 'income', 'description' => 'test income',
            'transaction_date' => now()->toDateString(), 'user_id' => $admin->id,
        ], $this->headersFor($admin))->assertSuccessful();

        $response = $this->json('POST', '/api/disbursements', [
            'project_id' => $project->id, 'amount' => 1000, 'disbursement_date' => now()->toDateString(), 'description' => 'test', 'user_id' => $admin->id,
        ], $this->headersFor($admin))->assertOk();

        $this->assertSame(1, $response->json('code'), (string) $response->json('message'));
        $this->assertSame(1000.0, $this->balance($investor), 'the controller and the model used to pay it twice');
        $this->assertCount(1, $response->json('data.account_transactions'));
    }

    public function test_uploads_refuse_files_a_server_could_run(): void
    {
        Log::spy();

        $files = ['file' => ['name' => 'shell.PHP', 'type' => 'text/plain', 'tmp_name' => '/nonexistent', 'error' => 0, 'size' => 1]];
        $this->assertSame([], \App\Models\Utils::upload_images_2($files));
        Log::shouldHaveReceived('warning')->with('Upload refused: file type not allowed', ['extension' => 'PHP'])->once();
    }

    public function test_secrets_are_blank_but_the_keys_the_apps_read_are_still_there(): void
    {
        $member = $this->member(['intro' => '482913']);

        $json = \App\Models\Administrator::find($member->id)->toArray();

        $this->assertSame('', $json['password']);
        $this->assertSame('', $json['intro']);
        $this->assertArrayHasKey('remember_token', $json);
    }

    public function test_routes_that_used_to_crash_now_answer_calmly(): void
    {
        $member = $this->member();
        $headers = $this->headersFor($member);

        $this->json('GET', '/api/chat-heads', [], $headers)->assertOk()->assertJsonPath('code', 1)->assertJsonPath('data', []);
        $this->json('POST', '/api/chat-send', [], $headers)->assertStatus(400)->assertJsonPath('code', 0);
        $this->json('GET', '/api/locations')->assertOk();
        $this->json('GET', '/api/delivery_addresses')->assertOk()->assertJsonPath('code', 1);
        $this->json('GET', '/api/pesapal/status/1')->assertStatus(404)->assertJsonPath('code', 0);
        $this->json('GET', '/api/orders/detail/1', [], $headers)->assertStatus(404);
        $this->json('POST', '/api/orders-create', ['items' => '[]', 'delivery' => '{}'], $headers)->assertJsonPath('code', 0);
        $this->json('GET', '/api/user/commissions', [], $headers)->assertOk();
        $this->json('GET', '/api/investments/transactions', [], $headers)->assertOk();
        $this->json('GET', '/api/investments/dashboard', [], $headers)->assertOk();
        $this->json('GET', '/api/projects/shares/my-shares', [], $headers)->assertOk();
    }

    public function test_tests_can_never_reach_real_members_or_the_gateway(): void
    {
        $this->assertFalse(\App\Models\Utils::sendSMS('0700000000', 'hello')->success);

        $this->expectException(\RuntimeException::class);
        (new PesapalApiClient())->authenticate();
    }

    public function test_admin_tools_that_crashed_work_for_admins_and_nobody_else(): void
    {
        $member = $this->member();
        $this->assertSame(0, $this->json('GET', "/api/user-accounts/user-dashboard/{$member->id}", [], $this->headersFor($this->member()))->json('code'));
        $this->assertSame(1, $this->json('GET', "/api/user-accounts/user-dashboard/{$member->id}", [], $this->headersFor($this->makeAdmin()))->json('code'));

        // PIN so the test also proves the existing share flow still works
        $pin = new AccountPin();
        $pin->user_id = $member->id;
        $pin->setPin('4827');
        $receiver = $this->member();
        $this->credit($member, 5000);
        $this->assertSame(1, $this->json('POST', '/api/account-transactions/share-commission', [
            'receiver_id' => $receiver->id, 'amount' => 1000, 'pin' => '4827',
        ], $this->headersFor($member))->json('code'));
        $this->assertSame(4000.0, $this->balance($member));
        $this->assertSame(1000.0, $this->balance($receiver));
    }
}
