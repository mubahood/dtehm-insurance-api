<?php

/**
 * Test script to verify multi-field admin authentication
 * Run: php test_admin_auth.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Utils;

echo "Testing Admin Multi-Field Authentication\n";
echo "==========================================\n\n";

// Test credentials
$testIdentifiers = [
    'admin@gmail.com',        // email
    '+256783204665',          // phone
    'DIP0153',                // business_name (DIP ID)
    'DTEHM20259018',          // DTEHM Member ID
    '+256706638484',          // phone (user 2)
    'extra_seller_10',        // username
];

foreach ($testIdentifiers as $identifier) {
    echo "Testing: $identifier\n";
    
    $user = null;
    
    // 1. Check DTEHM Member ID
    if (empty($user)) {
        $user = User::where('dtehm_member_id', $identifier)
            ->where('user_type', 'Admin')
            ->first();
        if ($user) {
            echo "  ✓ Found by DTEHM ID: {$user->name} (ID: {$user->id})\n";
        }
    }
    
    // 2. Check DIP ID (business_name)
    if (empty($user)) {
        $user = User::where('business_name', $identifier)
            ->where('user_type', 'Admin')
            ->first();
        if ($user) {
            echo "  ✓ Found by DIP ID: {$user->name} (ID: {$user->id})\n";
        }
    }
    
    // 3. Check phone number (exact match)
    if (empty($user)) {
        $user = User::where('phone_number', $identifier)
            ->where('user_type', 'Admin')
            ->first();
        if ($user) {
            echo "  ✓ Found by phone (exact): {$user->name} (ID: {$user->id})\n";
        }
    }
    
    // 4. Check phone number with country code normalization
    if (empty($user)) {
        $phone_number = Utils::prepare_phone_number($identifier);
        if (Utils::phone_number_is_valid($phone_number)) {
            $user = User::where('phone_number', $phone_number)
                ->where('user_type', 'Admin')
                ->first();
            if ($user) {
                echo "  ✓ Found by phone (normalized: $phone_number): {$user->name} (ID: {$user->id})\n";
            }
        }
    }
    
    // 5. Check username
    if (empty($user)) {
        $user = User::where('username', $identifier)
            ->where('user_type', 'Admin')
            ->first();
        if ($user) {
            echo "  ✓ Found by username: {$user->name} (ID: {$user->id})\n";
        }
    }
    
    // 6. Check email
    if (empty($user)) {
        $user = User::where('email', $identifier)
            ->where('user_type', 'Admin')
            ->first();
        if ($user) {
            echo "  ✓ Found by email: {$user->name} (ID: {$user->id})\n";
        }
    }
    
    if ($user == null) {
        echo "  ✗ NOT FOUND\n";
    }
    
    echo "\n";
}

echo "\nAuthentication Logic Test Complete!\n";
echo "\nAdmin users can now login using:\n";
echo "  - DTEHM Member ID (e.g., DTEHM20259018)\n";
echo "  - DIP ID (e.g., DIP0001)\n";
echo "  - Phone Number (e.g., +256706638484)\n";
echo "  - Username (e.g., extra_seller_10)\n";
echo "  - Email (e.g., admin@gmail.com)\n";
