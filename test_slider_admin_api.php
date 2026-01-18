<?php
/**
 * Test Script for Slider Admin API Endpoints (with Authentication)
 * 
 * Usage: php test_slider_admin_api.php
 * 
 * This script tests authenticated admin endpoints
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// Base URL
$baseUrl = 'http://lica.test';
$adminApiBaseUrl = $baseUrl . '/admin/api';

// Colors for terminal output
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$blue = "\033[34m";
$reset = "\033[0m";

echo "\n{$blue}=== SLIDER ADMIN API TESTING (Authenticated) ==={$reset}\n\n";

/**
 * Get API token for testing
 */
function getApiToken() {
    try {
        // Try to get first admin user
        $user = DB::table('users')->first();
        
        if (!$user) {
            echo "{$red}Error: No user found in database{$reset}\n";
            return null;
        }
        
        // Create token using Laravel Passport or Sanctum
        // For Passport:
        if (class_exists('Laravel\Passport\Passport')) {
            $token = $user->createToken('test-token')->accessToken;
        } else {
            // For Sanctum or simple token
            $token = 'Bearer ' . base64_encode($user->id . '|' . time());
        }
        
        return $token;
    } catch (\Exception $e) {
        echo "{$red}Error getting token: {$e->getMessage()}{$reset}\n";
        return null;
    }
}

/**
 * Make HTTP request
 */
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response,
        'error' => $error
    ];
}

/**
 * Print test result
 */
function printResult($testName, $result, $expectedCode = 200) {
    global $green, $red, $yellow, $reset;
    
    $success = $result['code'] === $expectedCode;
    $color = $success ? $green : $red;
    $status = $success ? '✓ PASS' : '✗ FAIL';
    
    echo "{$color}{$status}{$reset} - {$testName}\n";
    echo "  HTTP Code: {$result['code']} (Expected: {$expectedCode})\n";
    
    if ($result['error']) {
        echo "  {$red}Error: {$result['error']}{$reset}\n";
    }
    
    $body = json_decode($result['body'], true);
    if ($body) {
        if (isset($body['success'])) {
            echo "  Success: " . ($body['success'] ? 'true' : 'false') . "\n";
        }
        if (isset($body['message'])) {
            echo "  Message: {$body['message']}\n";
        }
        if (isset($body['errors'])) {
            echo "  {$red}Validation Errors:{$reset}\n";
            foreach ($body['errors'] as $field => $messages) {
                echo "    - {$field}: " . implode(', ', $messages) . "\n";
            }
        }
        if (isset($body['data'])) {
            if (is_array($body['data']) && isset($body['data'][0])) {
                $count = count($body['data']);
                echo "  Data Count: {$count}\n";
                if ($count > 0) {
                    echo "  First Item ID: " . ($body['data'][0]['id'] ?? 'N/A') . "\n";
                }
            } elseif (isset($body['data']['id'])) {
                echo "  Slider ID: {$body['data']['id']}\n";
                echo "  Slider Name: " . ($body['data']['name'] ?? 'N/A') . "\n";
            }
        }
        if (isset($body['pagination'])) {
            echo "  Total: {$body['pagination']['total']}\n";
            echo "  Current Page: {$body['pagination']['current_page']}\n";
        }
    } else {
        echo "  Response: " . substr($result['body'], 0, 200) . "\n";
    }
    
    echo "\n";
    
    return ['success' => $success, 'data' => $body];
}

// Get authentication token
echo "{$yellow}Getting authentication token...{$reset}\n";
$token = getApiToken();

if (!$token) {
    echo "{$red}Failed to get authentication token. Exiting.{$reset}\n";
    exit(1);
}

$headers = [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
];

// ============================================
// TEST 1: Admin API - Get sliders list
// ============================================
echo "{$yellow}[TEST 1] Admin API - Get sliders list{$reset}\n";
$result = makeRequest($adminApiBaseUrl . '/sliders', 'GET', null, $headers);
$test1 = printResult('GET /admin/api/sliders', $result, 200);

// ============================================
// TEST 2: Admin API - Get sliders with filters
// ============================================
echo "{$yellow}[TEST 2] Admin API - Get sliders with filters{$reset}\n";
$result = makeRequest($adminApiBaseUrl . '/sliders?status=1&display=desktop&limit=5', 'GET', null, $headers);
$test2 = printResult('GET /admin/api/sliders?status=1&display=desktop&limit=5', $result, 200);

// ============================================
// TEST 3: Admin API - Get single slider
// ============================================
echo "{$yellow}[TEST 3] Admin API - Get single slider{$reset}\n";
// Get first slider ID from previous test
$sliderId = null;
if ($test1['success'] && isset($test1['data']['data'][0]['id'])) {
    $sliderId = $test1['data']['data'][0]['id'];
} else {
    // Try to get any slider ID from database
    $slider = DB::table('medias')->where('type', 'slider')->first();
    $sliderId = $slider ? $slider->id : 1;
}

$result = makeRequest($adminApiBaseUrl . '/sliders/' . $sliderId, 'GET', null, $headers);
$test3 = printResult('GET /admin/api/sliders/' . $sliderId, $result, 200);

// ============================================
// TEST 4: Admin API - Create slider
// ============================================
echo "{$yellow}[TEST 4] Admin API - Create new slider{$reset}\n";
$newSlider = [
    'name' => 'Test Slider ' . date('Y-m-d H:i:s'),
    'link' => 'https://example.com/test',
    'image' => 'uploads/sliders/test-' . time() . '.jpg',
    'display' => 'desktop',
    'status' => '1'
];
$result = makeRequest($adminApiBaseUrl . '/sliders', 'POST', $newSlider, $headers);
$test4 = printResult('POST /admin/api/sliders', $result, 201);

$createdId = null;
if ($test4['success'] && isset($test4['data']['data']['id'])) {
    $createdId = $test4['data']['data']['id'];
    echo "  {$green}Created Slider ID: {$createdId}{$reset}\n\n";
}

// ============================================
// TEST 5: Admin API - Update slider
// ============================================
if ($createdId) {
    echo "{$yellow}[TEST 5] Admin API - Update slider{$reset}\n";
    $updateData = [
        'name' => 'Updated Test Slider ' . date('H:i:s'),
        'link' => 'https://example.com/updated',
        'image' => 'uploads/sliders/test-updated-' . time() . '.jpg',
        'display' => 'mobile',
        'status' => '1'
    ];
    $result = makeRequest($adminApiBaseUrl . '/sliders/' . $createdId, 'PUT', $updateData, $headers);
    $test5 = printResult('PUT /admin/api/sliders/' . $createdId, $result, 200);
} else {
    echo "{$yellow}[TEST 5] Admin API - Update slider{$reset}\n";
    echo "  {$red}SKIPPED - No slider created in previous test{$reset}\n\n";
}

// ============================================
// TEST 6: Admin API - Update status
// ============================================
if ($createdId) {
    echo "{$yellow}[TEST 6] Admin API - Update slider status{$reset}\n";
    $statusData = ['status' => '0'];
    $result = makeRequest($adminApiBaseUrl . '/sliders/' . $createdId . '/status', 'PATCH', $statusData, $headers);
    $test6 = printResult('PATCH /admin/api/sliders/' . $createdId . '/status', $result, 200);
} else {
    echo "{$yellow}[TEST 6] Admin API - Update slider status{$reset}\n";
    echo "  {$red}SKIPPED - No slider created{$reset}\n\n";
}

// ============================================
// TEST 7: Admin API - Validation test (invalid data)
// ============================================
echo "{$yellow}[TEST 7] Admin API - Validation test (invalid data){$reset}\n";
$invalidData = [
    'name' => '', // Empty name should fail
    'display' => 'invalid', // Invalid display value
    'status' => '2' // Invalid status value
];
$result = makeRequest($adminApiBaseUrl . '/sliders', 'POST', $invalidData, $headers);
$test7 = printResult('POST /admin/api/sliders (invalid data)', $result, 422);

// ============================================
// TEST 8: Admin API - Delete slider
// ============================================
if ($createdId) {
    echo "{$yellow}[TEST 8] Admin API - Delete slider{$reset}\n";
    $result = makeRequest($adminApiBaseUrl . '/sliders/' . $createdId, 'DELETE', null, $headers);
    $test8 = printResult('DELETE /admin/api/sliders/' . $createdId, $result, 200);
} else {
    echo "{$yellow}[TEST 8] Admin API - Delete slider{$reset}\n";
    echo "  {$red}SKIPPED - No slider created{$reset}\n\n";
}

// ============================================
// TEST 9: Admin API - Get non-existent slider
// ============================================
echo "{$yellow}[TEST 9] Admin API - Get non-existent slider{$reset}\n";
$result = makeRequest($adminApiBaseUrl . '/sliders/99999', 'GET', null, $headers);
$test9 = printResult('GET /admin/api/sliders/99999', $result, 404);

// Summary
echo "\n{$blue}=== TEST SUMMARY ==={$reset}\n";
$tests = [$test1, $test2, $test3, $test4, $test7, $test9];
if ($createdId) {
    $tests[] = $test5;
    $tests[] = $test6;
    $tests[] = $test8;
}

$passed = 0;
$failed = 0;
foreach ($tests as $test) {
    if (isset($test['success'])) {
        if ($test['success']) {
            $passed++;
        } else {
            $failed++;
        }
    }
}

echo "{$green}Passed: {$passed}{$reset}\n";
echo "{$red}Failed: {$failed}{$reset}\n";
echo "\n";
