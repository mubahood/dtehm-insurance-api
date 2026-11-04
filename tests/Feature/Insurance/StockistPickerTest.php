<?php

namespace Tests\Feature\Insurance;

use App\Models\MultipleOrder;
use App\Models\Product;
use Illuminate\Support\Arr;

/**
 * Checkout requires a stockist, and the app's picker keeps only rows with is_stockist = 'Yes'
 * from GET /api/insurance-users?is_not_private=1. Non-admins must get that field, and nothing private.
 */
class StockistPickerTest extends InsuranceTestCase
{
    /** Exactly what the installed apps request for the sponsor and stockist pickers. */
    private const LIST_URL = '/api/insurance-users?is_not_private=1';

    private function member(array $attrs = [])
    {
        return $this->makeUser(array_merge(['is_dtehm_member' => 'Yes', 'is_stockist' => 'No'], $attrs));
    }

    private function row($response, $user): ?array
    {
        return collect($response->json('data'))->firstWhere('id', $user->id);
    }

    public function test_a_member_who_is_not_an_admin_can_see_stockists(): void
    {
        $shop = $this->member(['is_stockist' => 'Yes', 'stockist_area' => 'Kasese']);
        $buyer = $this->member();

        $response = $this->json('GET', self::LIST_URL, [], $this->headersFor($buyer))->assertOk();

        $this->assertSame(1, $response->json('code'));
        $row = $this->row($response, $shop);
        $this->assertNotNull($row);
        $this->assertSame('Yes', $row['is_stockist']);
        $this->assertSame('Kasese', $row['stockist_area']);
    }

    public function test_a_customer_who_is_not_a_dtehm_member_can_also_see_stockists(): void
    {
        $shop = $this->member(['is_stockist' => 'Yes']);
        $customer = $this->makeUser(['is_dtehm_member' => 'No']);

        $response = $this->json('GET', self::LIST_URL, [], $this->headersFor($customer))->assertOk();

        $this->assertSame('Yes', $this->row($response, $shop)['is_stockist']);
    }

    public function test_the_list_stays_public_for_the_sponsor_picker_at_registration(): void
    {
        $shop = $this->member(['is_stockist' => 'Yes']);

        // No token, as on the registration screen; a bad old token must behave the same, not log out.
        foreach ([[], ['Authorization' => 'Bearer not-a-real-token']] as $headers) {
            $response = $this->json('GET', self::LIST_URL, [], $headers)->assertOk();
            $this->assertSame(1, $response->json('code'));
            $this->assertSame('Yes', $this->row($response, $shop)['is_stockist']);
        }

        $response = $this->json('GET', '/api/insurance-users?is_dtehm_member=Yes')->assertOk();
        $this->assertNotNull($this->row($response, $shop));
    }

    public function test_stockist_flag_is_normalised_for_every_stored_value(): void
    {
        $empty = $this->member(['is_stockist' => null]);
        $no = $this->member(['is_stockist' => 'No']);
        $lower = $this->member(['is_stockist' => 'yes']);

        $response = $this->json('GET', self::LIST_URL)->assertOk();

        $this->assertSame('No', $this->row($response, $empty)['is_stockist']);
        $this->assertSame('No', $this->row($response, $no)['is_stockist']);
        $this->assertSame('Yes', $this->row($response, $lower)['is_stockist']);
    }

    public function test_only_active_dtehm_members_are_offered(): void
    {
        $inactive = $this->member(['is_stockist' => 'Yes', 'status' => 'Inactive']);
        $notMember = $this->makeUser(['is_stockist' => 'Yes', 'is_dtehm_member' => 'No']);

        $response = $this->json('GET', self::LIST_URL)->assertOk();

        $this->assertNull($this->row($response, $inactive));
        $this->assertNull($this->row($response, $notMember));
    }

    public function test_no_private_details_reach_non_admins(): void
    {
        $shop = $this->member(['is_stockist' => 'Yes', 'stockist_area' => 'Kasese', 'address' => 'Plot 1']);
        $person = $this->member(['stockist_area' => 'Kisinga', 'address' => 'Plot 2']);

        foreach ([[], $this->headersFor($person)] as $headers) {
            $response = $this->json('GET', self::LIST_URL, [], $headers)->assertOk();
            $shopRow = $this->row($response, $shop);
            $personRow = $this->row($response, $person);

            foreach (['phone_number', 'email', 'password', 'address', 'dob', 'sex', 'sponsor_id', 'business_name', 'remember_token'] as $private) {
                $this->assertArrayNotHasKey($private, $shopRow, "$private leaked");
                $this->assertArrayNotHasKey($private, $personRow, "$private leaked");
            }

            // username is the member ID, never the stored username (often a phone number)
            $this->assertSame($person->dtehm_member_id, $personRow['username']);
            $this->assertNotSame($person->phone_number, $personRow['username']);
            // A member's own area stays private, a stockist's is public
            $this->assertNull($personRow['stockist_area']);
            $this->assertSame('Kasese', $shopRow['stockist_area']);
        }
    }

    public function test_the_full_list_still_needs_a_filter_or_an_admin(): void
    {
        $buyer = $this->member();

        $response = $this->json('GET', '/api/insurance-users', [], $this->headersFor($buyer));

        $this->assertNotSame(1, $response->json('code'));
        $this->assertEmpty($response->json('data'));
    }

    public function test_admins_keep_the_full_view(): void
    {
        $shop = $this->member(['is_stockist' => 'Yes', 'stockist_area' => 'Kasese']);

        $response = $this->json('GET', self::LIST_URL, [], $this->headersFor($this->makeAdmin()))->assertOk();

        $row = $this->row($response, $shop);
        $this->assertSame('Yes', $row['is_stockist']);
        $this->assertSame($shop->phone_number, $row['phone_number']);
        $this->assertSame($shop->username, $row['username']);
        $this->assertArrayHasKey('dtehm_membership_paid', $row);
    }

    /** Pick a stockist from the list as the app does, then place the order with that id. */
    public function test_a_member_can_check_out_with_a_stockist_from_the_list(): void
    {
        $shop = $this->member(['is_stockist' => 'Yes']);
        $sponsor = $this->member();
        $buyer = $this->member();
        $product = Product::query()->where('price_1', '>', 0)->firstOrFail();
        $headers = $this->headersFor($buyer);

        $offered = collect($this->json('GET', self::LIST_URL, [], $headers)->assertOk()->json('data'))
            ->where('is_stockist', 'Yes');
        $this->assertContains($shop->id, $offered->pluck('id')->all());

        $response = $this->json('POST', '/api/multiple-orders/create', [
            'user_id' => $buyer->id,
            'sponsor_id' => (string) $sponsor->id,
            'stockist_id' => (string) $offered->firstWhere('id', $shop->id)['id'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'delivery_method' => 'pickup',
            'is_paid_by_admin' => '0',
        ], $headers)->assertOk();

        $this->assertSame(1, $response->json('code'), (string) $response->json('message'));
        $order = MultipleOrder::latest('id')->first();
        $this->assertSame($shop->id, (int) $order->stockist_user_id);
        $this->assertSame($sponsor->id, (int) $order->sponsor_user_id);
        $this->assertSame($buyer->id, (int) $order->user_id);
        $this->assertSame('PENDING', $order->payment_status);
    }
}
