<?php

namespace Tests\Feature\Insurance;

use App\Models\InsuranceProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Base for insurance / membership / payment tests.
 * Runs inside a DB transaction on the *_test database and refuses to run anywhere else.
 */
abstract class InsuranceTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $name = DB::connection()->getDatabaseName();
        if (!str_ends_with($name, '_test')) {
            $this->fail("Refusing to run tests against database '{$name}' (must end with _test).");
        }
        config(['auth.strict_api' => true]);
    }

    protected function makeUser(array $attrs = []): User
    {
        static $n = 0;
        $n++;
        $phone = '07' . str_pad((string) (70000000 + $n + random_int(0, 9999) * 1000), 8, '0', STR_PAD_LEFT);
        $user = new User();
        $user->first_name = 'Test';
        $user->last_name = 'User' . $n . random_int(100, 999);
        $user->name = $user->first_name . ' ' . $user->last_name;
        $user->phone_number = $phone;
        $user->username = $phone;
        $user->email = "t{$n}" . random_int(1000, 9999) . '@example.test';
        $user->password = bcrypt('secret123');
        $user->user_type = 'Customer';
        $user->status = 'Active';
        $user->sex = 'Male';
        $user->is_dtehm_member = 'No';
        $user->is_dip_member = 'No';
        foreach ($attrs as $k => $v) {
            $user->$k = $v;
        }
        $user->save();
        return $user->fresh();
    }

    protected function makeAdmin(): User
    {
        return $this->makeUser(['user_type' => 'Admin']);
    }

    protected function headersFor(User $user): array
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];
    }

    protected function makeProgram(array $attrs = []): InsuranceProgram
    {
        return InsuranceProgram::create(array_merge([
            'name' => 'Test Cover ' . uniqid(),
            'description' => 'test',
            'coverage_amount' => 1000000,
            'premium_amount' => 10000,
            'billing_frequency' => 'Monthly',
            'billing_day' => 5,
            'duration_months' => 3,
            'status' => 'Active',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ], $attrs));
    }
}
