<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test if business_name field exists and has data
$user = \App\Models\User::where('dtehm_member_id', '!=', null)
    ->whereNotNull('dtehm_member_id')
    ->first();

if ($user) {
    echo "✅ Found user with DTEHM membership\n";
    echo "User ID: {$user->id}\n";
    echo "Name: {$user->name}\n";
    echo "DTEHM Member ID: {$user->dtehm_member_id}\n";
    echo "Business Name (DIP ID): " . ($user->business_name ?? 'NULL') . "\n";
    echo "is_dtehm_member: {$user->is_dtehm_member}\n";
    echo "is_dip_member: {$user->is_dip_member}\n\n";
    
    // Check if business_name column exists
    $columns = DB::select("SHOW COLUMNS FROM users LIKE 'business_name'");
    if (!empty($columns)) {
        echo "✅ business_name column exists in users table\n";
    } else {
        echo "❌ business_name column does NOT exist in users table\n";
    }
    
    // Check how many users have business_name
    $count = \App\Models\User::whereNotNull('business_name')
        ->where('business_name', '!=', '')
        ->count();
    echo "\nUsers with business_name set: {$count}\n";
    
} else {
    echo "❌ No users with DTEHM membership found\n";
}
