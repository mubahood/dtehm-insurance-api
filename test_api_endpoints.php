<?php
/**
 * API ENDPOINT TESTING SCRIPT
 * Test insurance user creation and update endpoints with dummy data
 * 
 * Run this script from terminal:
 * php test_api_endpoints.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n========================================\n";
echo "TESTING API ENDPOINTS\n";
echo "========================================\n\n";

// Get a valid DTEHM sponsor for testing
$sponsor = \App\Models\User::where('is_dtehm_member', 'Yes')
    ->whereNotNull('dtehm_member_id')
    ->first();

if (!$sponsor) {
    echo "❌ ERROR: No DTEHM sponsors found in database\n";
    echo "Please create at least one DTEHM member first.\n\n";
    exit(1);
}

echo "✅ Found valid sponsor:\n";
echo "   - ID: {$sponsor->id}\n";
echo "   - Name: {$sponsor->name}\n";
echo "   - DTEHM ID: {$sponsor->dtehm_member_id}\n\n";

// Test Data
$testData = [
    'first_name' => 'Test',
    'last_name' => 'User' . rand(1000, 9999),
    'phone_number' => '0700' . rand(100000, 999999),
    'sex' => 'Male',
    'password' => 'test123',
    'email' => 'test' . rand(1000, 9999) . '@example.com',
    'dob' => '1990-01-15',
    'address' => 'Test Address, Kampala',
    'is_dtehm_member' => 'Yes',
    'is_dip_member' => 'Yes',
    'sponsor_id' => $sponsor->id, // Using user ID as mobile app does
    'stockist_area' => 'Kampala',
];

echo "========================================\n";
echo "TEST 1: Create Insurance User\n";
echo "========================================\n";
echo "Test Data:\n";
foreach ($testData as $key => $value) {
    if ($key !== 'password') {
        echo "   - {$key}: {$value}\n";
    }
}
echo "\n";

try {
    // Simulate HTTP request
    $request = \Illuminate\Http\Request::create('/api/insurance-users', 'POST', $testData);
    
    $controller = new \App\Http\Controllers\ApiResurceController();
    $response = $controller->insurance_user_create($request);
    
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() == 201 || $response->getStatusCode() == 200) {
        echo "✅ User created successfully!\n";
        echo "   - User ID: {$responseData['data']['id']}\n";
        echo "   - Name: {$responseData['data']['name']}\n";
        echo "   - Sponsor ID (saved): {$responseData['data']['sponsor_id']}\n";
        
        // Verify sponsor_id is DTEHM ID, not user ID
        if ($responseData['data']['sponsor_id'] === $sponsor->dtehm_member_id) {
            echo "   ✅ Sponsor ID correctly set to DTEHM ID: {$sponsor->dtehm_member_id}\n";
        } else {
            echo "   ❌ ERROR: Sponsor ID should be DTEHM ID ({$sponsor->dtehm_member_id}) but got: {$responseData['data']['sponsor_id']}\n";
        }
        
        // Check if user was saved to database
        $createdUser = \App\Models\User::find($responseData['data']['id']);
        if ($createdUser) {
            echo "   ✅ User found in database\n";
            echo "   - sponsor_id: {$createdUser->sponsor_id}\n";
            echo "   - parent_1: {$createdUser->parent_1}\n";
            
            if ($createdUser->parent_1 == $sponsor->id) {
                echo "   ✅ parent_1 correctly set to sponsor user ID: {$sponsor->id}\n";
            } else {
                echo "   ❌ ERROR: parent_1 should be {$sponsor->id} but got: {$createdUser->parent_1}\n";
            }
            
            // Check memberships
            $dtehmMembership = \App\Models\DtehmMembership::where('user_id', $createdUser->id)
                ->where('status', 'CONFIRMED')
                ->first();
                
            if ($dtehmMembership) {
                echo "   ✅ DTEHM Membership auto-created (ID: {$dtehmMembership->id}, Amount: {$dtehmMembership->amount})\n";
            } else {
                echo "   ❌ DTEHM Membership not created\n";
            }
            
            $dipMembership = \App\Models\MembershipPayment::where('user_id', $createdUser->id)
                ->where('status', 'CONFIRMED')
                ->first();
                
            if ($dipMembership) {
                echo "   ✅ DIP Membership auto-created (ID: {$dipMembership->id}, Amount: {$dipMembership->amount})\n";
            } else {
                echo "   ❌ DIP Membership not created\n";
            }
            
            $testUserId = $createdUser->id;
        }
    } else {
        echo "❌ Failed to create user\n";
        echo "   Status: {$response->getStatusCode()}\n";
        echo "   Response: {$response->getContent()}\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "❌ Exception: {$e->getMessage()}\n";
    echo "   Trace: {$e->getTraceAsString()}\n\n";
    exit(1);
}

echo "\n========================================\n";
echo "TEST 2: Update Insurance User\n";
echo "========================================\n";

if (!isset($testUserId)) {
    echo "❌ No user ID from previous test\n";
    exit(1);
}

$updateData = [
    'first_name' => 'Updated',
    'last_name' => 'TestUser',
    'email' => 'updated' . rand(1000, 9999) . '@example.com',
];

echo "Updating user {$testUserId} with:\n";
foreach ($updateData as $key => $value) {
    echo "   - {$key}: {$value}\n";
}
echo "\n";

try {
    $request = \Illuminate\Http\Request::create("/api/insurance-users/{$testUserId}", 'PUT', $updateData);
    
    $controller = new \App\Http\Controllers\ApiResurceController();
    $response = $controller->insurance_user_update($request, $testUserId);
    
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() == 200) {
        echo "✅ User updated successfully!\n";
        echo "   - Name: {$responseData['data']['name']}\n";
        echo "   - Email: {$responseData['data']['email']}\n";
    } else {
        echo "❌ Failed to update user\n";
        echo "   Status: {$response->getStatusCode()}\n";
        echo "   Response: {$response->getContent()}\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: {$e->getMessage()}\n\n";
}

echo "\n========================================\n";
echo "TEST 3: Invalid Sponsor Test\n";
echo "========================================\n";

$invalidSponsorData = [
    'first_name' => 'Test',
    'last_name' => 'InvalidSponsor',
    'phone_number' => '0700' . rand(100000, 999999),
    'sex' => 'Female',
    'password' => 'test123',
    'sponsor_id' => 99999, // Non-existent ID
];

echo "Attempting to create user with invalid sponsor ID: 99999\n\n";

try {
    $request = \Illuminate\Http\Request::create('/api/insurance-users', 'POST', $invalidSponsorData);
    
    $controller = new \App\Http\Controllers\ApiResurceController();
    $response = $controller->insurance_user_create($request);
    
    $responseData = json_decode($response->getContent(), true);
    
    echo "Status: {$response->getStatusCode()}\n";
    echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response->getStatusCode() == 400) {
        echo "✅ Correctly rejected invalid sponsor\n";
        echo "   Message: {$responseData['message']}\n";
    } else if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
        echo "❌ CRITICAL ERROR: Should have rejected invalid sponsor but created user!\n";
        echo "   User ID: " . ($responseData['data']['id'] ?? 'unknown') . "\n";
        // Cleanup this wrongly created user
        if (isset($responseData['data']['id'])) {
            $wrongUser = \App\Models\User::find($responseData['data']['id']);
            if ($wrongUser) {
                $wrongUser->forceDelete();
                echo "   Cleaned up wrongly created user\n";
            }
        }
    } else {
        echo "❌ Unexpected status code: {$response->getStatusCode()}\n";
    }
} catch (\Exception $e) {
    echo "Exception: {$e->getMessage()}\n";
    if (strpos($e->getMessage(), 'sponsor') !== false || strpos($e->getMessage(), 'Sponsor') !== false) {
        echo "✅ Exception correctly mentions sponsor validation\n";
    }
}

echo "\n========================================\n";
echo "TEST 4: Missing Sponsor Test\n";
echo "========================================\n";

$noSponsorData = [
    'first_name' => 'Test',
    'last_name' => 'NoSponsor',
    'phone_number' => '0700' . rand(100000, 999999),
    'sex' => 'Male',
    'password' => 'test123',
    // No sponsor_id provided
];

echo "Attempting to create user without sponsor\n\n";

try {
    $request = \Illuminate\Http\Request::create('/api/insurance-users', 'POST', $noSponsorData);
    
    $controller = new \App\Http\Controllers\ApiResurceController();
    $response = $controller->insurance_user_create($request);
    
    $responseData = json_decode($response->getContent(), true);
    
    echo "Status: {$response->getStatusCode()}\n";
    echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response->getStatusCode() == 400 || $response->getStatusCode() == 422) {
        echo "✅ Correctly rejected missing sponsor\n";
        echo "   Message: {$responseData['message']}\n";
    } else if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
        echo "❌ CRITICAL ERROR: Should have rejected missing sponsor but created user!\n";
        echo "   User ID: " . ($responseData['data']['id'] ?? 'unknown') . "\n";
        // Cleanup this wrongly created user
        if (isset($responseData['data']['id'])) {
            $wrongUser = \App\Models\User::find($responseData['data']['id']);
            if ($wrongUser) {
                $wrongUser->forceDelete();
                echo "   Cleaned up wrongly created user\n";
            }
        }
    } else {
        echo "❌ Unexpected status code: {$response->getStatusCode()}\n";
    }
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "✅ Validation exception thrown correctly\n";
    echo "   Errors: " . json_encode($e->errors(), JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "Exception: {$e->getMessage()}\n";
    if (strpos($e->getMessage(), 'sponsor') !== false || strpos($e->getMessage(), 'Sponsor') !== false) {
        echo "✅ Exception correctly mentions sponsor validation\n";
    }
}

echo "\n========================================\n";
echo "TESTING COMPLETE\n";
echo "========================================\n\n";

// Cleanup test data
if (isset($createdUser)) {
    echo "Cleaning up test data...\n";
    
    // Delete memberships
    \App\Models\DtehmMembership::where('user_id', $createdUser->id)->delete();
    \App\Models\MembershipPayment::where('user_id', $createdUser->id)->delete();
    
    // Delete user
    $createdUser->forceDelete();
    
    echo "✅ Test data cleaned up\n\n";
}

echo "Done!\n\n";
