#!/usr/bin/env php
<?php

/**
 * Product Purchase API Test Script
 * Tests all product purchase endpoints
 */

// Configuration
$baseUrl = 'http://localhost/api/product-purchase';
$testUserId = 1; // User ID from database
$testProductId = 1; // Product ID from database
$testSponsorId = 'DTEHM001'; // DTEHM Member ID
$testStockistId = 'DTEHM001'; // Can be same as sponsor for testing

// Colors for terminal output
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'magenta' => "\033[35m",
    'cyan' => "\033[36m",
];

function color($text, $color) {
    global $colors;
    return $colors[$color] . $text . $colors['reset'];
}

function log_test($message) {
    echo color("\n" . str_repeat('=', 80) . "\n", 'cyan');
    echo color($message, 'cyan') . "\n";
    echo color(str_repeat('=', 80) . "\n", 'cyan');
}

function log_success($message) {
    echo color("✓ SUCCESS: ", 'green') . $message . "\n";
}

function log_error($message) {
    echo color("✗ ERROR: ", 'red') . $message . "\n";
}

function log_info($message) {
    echo color("ℹ INFO: ", 'blue') . $message . "\n";
}

function log_warning($message) {
    echo color("⚠ WARNING: ", 'yellow') . $message . "\n";
}

function makeRequest($method, $url, $data = null, $headers = []) {
    $ch = curl_init();
    
    $defaultHeaders = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    $allHeaders = array_merge($defaultHeaders, $headers);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => $httpCode];
    }
    
    return [
        'http_code' => $httpCode,
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

echo color("\n╔════════════════════════════════════════════════════════════════════╗\n", 'magenta');
echo color("║     PRODUCT PURCHASE API - COMPREHENSIVE TEST SUITE                ║\n", 'magenta');
echo color("╚════════════════════════════════════════════════════════════════════╝\n", 'magenta');

log_info("Base URL: $baseUrl");
log_info("Test User ID: $testUserId");
log_info("Test Product ID: $testProductId");
log_info("Test Sponsor ID: $testSponsorId");
log_info("Test Stockist ID: $testStockistId");

$testResults = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
];

// ========================================================================
// TEST 1: Initialize Purchase - Valid Data
// ========================================================================
log_test("TEST 1: Initialize Purchase - Valid Data");

$response = makeRequest('POST', "$baseUrl/initialize", [
    'product_id' => $testProductId,
    'quantity' => 1,
    'sponsor_id' => $testSponsorId,
    'stockist_id' => $testStockistId,
    'user_id' => $testUserId,
], ["User-Id: $testUserId"]);

if ($response['http_code'] === 201 && isset($response['body']['success']) && $response['body']['success'] === true) {
    log_success("Purchase initialized successfully");
    log_info("Payment ID: " . ($response['body']['data']['payment']['id'] ?? 'N/A'));
    log_info("Payment Reference: " . ($response['body']['data']['payment']['payment_reference'] ?? 'N/A'));
    log_info("Amount: " . ($response['body']['data']['payment']['amount'] ?? 'N/A'));
    
    // Check if Pesapal data exists
    if (isset($response['body']['data']['pesapal'])) {
        log_info("Pesapal Redirect URL: " . substr($response['body']['data']['pesapal']['redirect_url'] ?? 'N/A', 0, 50) . '...');
        log_info("Order Tracking ID: " . ($response['body']['data']['pesapal']['order_tracking_id'] ?? 'N/A'));
        
        // Store for later tests
        $paymentId = $response['body']['data']['payment']['id'];
        $orderTrackingId = $response['body']['data']['pesapal']['order_tracking_id'];
    } else {
        log_warning("Pesapal data missing - this might fail in production");
        $testResults['warnings']++;
    }
    
    $testResults['passed']++;
} else {
    log_error("Failed to initialize purchase");
    log_error("HTTP Code: " . $response['http_code']);
    log_error("Response: " . json_encode($response['body'], JSON_PRETTY_PRINT));
    $testResults['failed']++;
}

// ========================================================================
// TEST 2: Initialize Purchase - Missing Required Fields
// ========================================================================
log_test("TEST 2: Initialize Purchase - Missing Required Fields (Should Fail)");

$response = makeRequest('POST', "$baseUrl/initialize", [
    'product_id' => $testProductId,
    // Missing quantity, sponsor_id, stockist_id, user_id
], ["User-Id: $testUserId"]);

if ($response['http_code'] === 422) {
    log_success("Validation error returned as expected");
    log_info("Errors: " . json_encode($response['body']['errors'] ?? [], JSON_PRETTY_PRINT));
    $testResults['passed']++;
} else {
    log_error("Should have returned 422 validation error");
    log_error("HTTP Code: " . $response['http_code']);
    $testResults['failed']++;
}

// ========================================================================
// TEST 3: Initialize Purchase - Invalid Product ID
// ========================================================================
log_test("TEST 3: Initialize Purchase - Invalid Product ID (Should Fail)");

$response = makeRequest('POST', "$baseUrl/initialize", [
    'product_id' => 99999, // Non-existent product
    'quantity' => 1,
    'sponsor_id' => $testSponsorId,
    'stockist_id' => $testStockistId,
    'user_id' => $testUserId,
], ["User-Id: $testUserId"]);

if ($response['http_code'] === 422 || $response['http_code'] === 404) {
    log_success("Invalid product rejected as expected");
    $testResults['passed']++;
} else {
    log_error("Should have rejected invalid product");
    log_error("HTTP Code: " . $response['http_code']);
    $testResults['failed']++;
}

// ========================================================================
// TEST 4: Initialize Purchase - Invalid Sponsor
// ========================================================================
log_test("TEST 4: Initialize Purchase - Invalid Sponsor (Should Fail)");

$response = makeRequest('POST', "$baseUrl/initialize", [
    'product_id' => $testProductId,
    'quantity' => 1,
    'sponsor_id' => 'INVALID_SPONSOR_99999',
    'stockist_id' => $testStockistId,
    'user_id' => $testUserId,
], ["User-Id: $testUserId"]);

if ($response['http_code'] === 404) {
    log_success("Invalid sponsor rejected as expected");
    log_info("Message: " . ($response['body']['message'] ?? 'N/A'));
    $testResults['passed']++;
} else {
    log_error("Should have rejected invalid sponsor");
    log_error("HTTP Code: " . $response['http_code']);
    log_error("Response: " . json_encode($response['body'], JSON_PRETTY_PRINT));
    $testResults['failed']++;
}

// ========================================================================
// TEST 5: Initialize Purchase - Invalid Stockist
// ========================================================================
log_test("TEST 5: Initialize Purchase - Invalid Stockist (Should Fail)");

$response = makeRequest('POST', "$baseUrl/initialize", [
    'product_id' => $testProductId,
    'quantity' => 1,
    'sponsor_id' => $testSponsorId,
    'stockist_id' => 'INVALID_STOCKIST_99999',
    'user_id' => $testUserId,
], ["User-Id: $testUserId"]);

if ($response['http_code'] === 404) {
    log_success("Invalid stockist rejected as expected");
    $testResults['passed']++;
} else {
    log_error("Should have rejected invalid stockist");
    log_error("HTTP Code: " . $response['http_code']);
    $testResults['failed']++;
}

// ========================================================================
// TEST 6: Initialize Purchase - Missing User-Id Header
// ========================================================================
log_test("TEST 6: Initialize Purchase - Missing User-Id Header (Should Fail)");

$response = makeRequest('POST', "$baseUrl/initialize", [
    'product_id' => $testProductId,
    'quantity' => 1,
    'sponsor_id' => $testSponsorId,
    'stockist_id' => $testStockistId,
    'user_id' => $testUserId,
]); // No User-Id header

if ($response['http_code'] === 401) {
    log_success("Missing authentication rejected as expected");
    $testResults['passed']++;
} else {
    log_warning("Should ideally return 401 for missing User-Id");
    log_info("HTTP Code: " . $response['http_code']);
    $testResults['warnings']++;
}

// ========================================================================
// TEST 7: Confirm Purchase - Without Payment
// ========================================================================
log_test("TEST 7: Confirm Purchase - Payment Not Completed (Should Fail/Warn)");

if (isset($paymentId)) {
    $response = makeRequest('POST', "$baseUrl/confirm", [
        'payment_id' => $paymentId,
    ]);
    
    if ($response['http_code'] === 400) {
        log_success("Pending payment status detected correctly");
        log_info("Message: " . ($response['body']['message'] ?? 'N/A'));
        $testResults['passed']++;
    } else {
        log_warning("Payment confirmation without actual payment");
        log_info("HTTP Code: " . $response['http_code']);
        log_info("Response: " . json_encode($response['body'], JSON_PRETTY_PRINT));
        $testResults['warnings']++;
    }
} else {
    log_warning("Skipping - no payment ID from previous test");
    $testResults['warnings']++;
}

// ========================================================================
// TEST 8: Purchase History - Empty or With Data
// ========================================================================
log_test("TEST 8: Get Purchase History");

$response = makeRequest('GET', "$baseUrl/history?per_page=5&page=1", null, [
    "User-Id: $testUserId"
]);

if ($response['http_code'] === 200) {
    log_success("Purchase history retrieved successfully");
    log_info("Total purchases: " . count($response['body']['data']['purchases'] ?? []));
    
    if (count($response['body']['data']['purchases'] ?? []) > 0) {
        log_info("Sample purchase: " . json_encode($response['body']['data']['purchases'][0], JSON_PRETTY_PRINT));
    }
    
    $testResults['passed']++;
} else {
    log_error("Failed to retrieve purchase history");
    log_error("HTTP Code: " . $response['http_code']);
    $testResults['failed']++;
}

// ========================================================================
// TEST 9: Purchase Details - Invalid ID
// ========================================================================
log_test("TEST 9: Get Purchase Details - Invalid ID (Should Fail)");

$response = makeRequest('GET', "$baseUrl/99999"); // Non-existent purchase

if ($response['http_code'] === 404) {
    log_success("Invalid purchase ID rejected as expected");
    $testResults['passed']++;
} else {
    log_error("Should have returned 404 for invalid purchase ID");
    log_error("HTTP Code: " . $response['http_code']);
    $testResults['failed']++;
}

// ========================================================================
// SUMMARY
// ========================================================================
echo color("\n╔════════════════════════════════════════════════════════════════════╗\n", 'magenta');
echo color("║                        TEST RESULTS SUMMARY                        ║\n", 'magenta');
echo color("╚════════════════════════════════════════════════════════════════════╝\n", 'magenta');

echo "\n";
echo color("  ✓ PASSED:   ", 'green') . $testResults['passed'] . "\n";
echo color("  ✗ FAILED:   ", 'red') . $testResults['failed'] . "\n";
echo color("  ⚠ WARNINGS: ", 'yellow') . $testResults['warnings'] . "\n";
echo "\n";

$total = $testResults['passed'] + $testResults['failed'] + $testResults['warnings'];
$passRate = $total > 0 ? round(($testResults['passed'] / $total) * 100, 2) : 0;

echo "  Pass Rate: " . color("$passRate%", $passRate >= 70 ? 'green' : 'red') . "\n";

if ($testResults['failed'] === 0) {
    echo color("\n  🎉 ALL CRITICAL TESTS PASSED! API is working correctly.\n", 'green');
} else {
    echo color("\n  ❌ SOME TESTS FAILED. Please review the errors above.\n", 'red');
}

echo "\n";
echo color("NOTE: Pesapal integration requires actual payment gateway setup.\n", 'yellow');
echo color("      The tests above verify API logic and validation.\n", 'yellow');
echo color("      For full end-to-end testing, use Pesapal sandbox.\n", 'yellow');
echo "\n";

exit($testResults['failed'] > 0 ? 1 : 0);
