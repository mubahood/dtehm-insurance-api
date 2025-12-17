<?php

/**
 * Test network tree API with user_id parameter
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Administrator;

echo "=== Testing Network Tree API with user_id parameter ===\n\n";

// Find a user with downline (has other users who have them as sponsor)
$userWithDownline = Administrator::whereNotNull('dtehm_member_id')
    ->orWhereNotNull('business_name')
    ->first();

if (!$userWithDownline) {
    echo "❌ No users found\n";
    exit;
}

echo "✅ Found test user: {$userWithDownline->name} (ID: {$userWithDownline->id})\n";
echo "   DTEHM ID: {$userWithDownline->dtehm_member_id}\n";
echo "   Phone: {$userWithDownline->phone_number}\n\n";

// Count their direct referrals
$membershipId = $userWithDownline->dtehm_member_id ?? $userWithDownline->business_name;
$directReferrals = Administrator::where('sponsor_id', $userWithDownline->dtehm_member_id)
    ->orWhere('sponsor_id', $userWithDownline->business_name)
    ->get();

echo "   Direct Referrals: " . $directReferrals->count() . "\n\n";

if ($directReferrals->count() > 0) {
    echo "First 3 direct referrals:\n";
    foreach ($directReferrals->take(3) as $referral) {
        echo "   - {$referral->name} (ID: {$referral->id}, Phone: {$referral->phone_number})\n";
    }
}

echo "\n=== Test Complete ===\n";
