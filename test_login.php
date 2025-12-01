<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "========== TESTING LOGIN WITH MULTIPLE IDENTIFIERS ==========\n";

// Get a test user with all identifiers
$user = App\Models\User::whereNotNull('dtehm_member_id')
    ->whereNotNull('business_name')
    ->whereNotNull('phone_number')
    ->whereNotNull('email')
    ->first();

if (!$user) {
    echo "No suitable test user found\n";
    exit;
}

echo "Test User: {$user->name} (ID: {$user->id})\n";
echo "DTEHM ID: {$user->dtehm_member_id}\n";
echo "DIP ID: {$user->business_name}\n";
echo "Phone: {$user->phone_number}\n";
echo "Email: {$user->email}\n";
echo "Username: " . ($user->username ?? 'N/A') . "\n";

// Reset password to known value for testing
$testPassword = '123456';
$user->password = password_hash($testPassword, PASSWORD_DEFAULT);
$user->save();
echo "\nPassword set to: {$testPassword}\n";

echo "\n--- Testing Login Methods ---\n";

$identifiers = [
    'DTEHM ID' => $user->dtehm_member_id,
    'DIP ID' => $user->business_name,
    'Phone (full)' => $user->phone_number,
    'Phone (without +256)' => str_replace('+256', '0', $user->phone_number),
    'Email' => $user->email,
];

if ($user->username) {
    $identifiers['Username'] = $user->username;
}

foreach ($identifiers as $type => $identifier) {
    if (empty($identifier)) continue;
    
    echo "\n{$type}: {$identifier}\n";
    
    $request = Illuminate\Http\Request::create('/api/login', 'POST', [
        'username' => $identifier,
        'password' => $testPassword,
    ]);
    
    $controller = new App\Http\Controllers\ApiAuthController();
    $response = $controller->login($request);
    $data = json_decode($response->getContent());
    
    if ($data->code == 1) {
        echo "✅ SUCCESS - Token: " . substr($data->data->token, 0, 20) . "...\n";
    } else {
        echo "❌ FAILED - {$data->message}\n";
    }
}

echo "\n========== TEST COMPLETE ==========\n";
