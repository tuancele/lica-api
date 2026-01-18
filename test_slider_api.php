<?php
/**
 * Test Script for Slider API Endpoints
 * 
 * Usage: php test_slider_api.php
 */

// Base URL - adjust if needed
$baseUrl = 'http://lica.test';
$apiBaseUrl = $baseUrl . '/api/v1';
$adminApiBaseUrl = $baseUrl . '/admin/api';

// Colors for terminal output
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$blue = "\033[34m";
$reset = "\033[0m";

echo "\n{$blue}=== SLIDER API TESTING ==={$reset}\n\n";

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
        if (isset($body['data']) && is_array($body['data'])) {
            $count = count($body['data']);
            echo "  Data Count: {$count}\n";
            if ($count > 0 && isset($body['data'][0])) {
                echo "  First Item ID: " . ($body['data'][0]['id'] ?? 'N/A') . "\n";
            }
        }
        if (isset($body['pagination'])) {
            echo "  Total: {$body['pagination']['total']}\n";
        }
    }
    
    echo "\n";
    
    return $success;
}

// ============================================
// TEST 1: Public API - Get all sliders
// ============================================
echo "{$yellow}[TEST 1] Public API - Get all active sliders{$reset}\n";
$result = makeRequest($apiBaseUrl . '/sliders');
printResult('GET /api/v1/sliders', $result, 200);

// ============================================
// TEST 2: Public API - Get desktop sliders
// ============================================
echo "{$yellow}[TEST 2] Public API - Get desktop sliders{$reset}\n";
$result = makeRequest($apiBaseUrl . '/sliders?display=desktop');
printResult('GET /api/v1/sliders?display=desktop', $result, 200);

// ============================================
// TEST 3: Public API - Get mobile sliders
// ============================================
echo "{$yellow}[TEST 3] Public API - Get mobile sliders{$reset}\n";
$result = makeRequest($apiBaseUrl . '/sliders?display=mobile');
printResult('GET /api/v1/sliders?display=mobile', $result, 200);

// ============================================
// TEST 4: Admin API - Get sliders list (without auth - should fail)
// ============================================
echo "{$yellow}[TEST 4] Admin API - Get sliders list (no auth){$reset}\n";
$result = makeRequest($adminApiBaseUrl . '/sliders');
printResult('GET /admin/api/sliders (no auth)', $result, 401);

// ============================================
// TEST 5: Admin API - Get sliders list (with auth token)
// ============================================
echo "{$yellow}[TEST 5] Admin API - Get sliders list (with auth){$reset}\n";
echo "  {$yellow}Note: This requires a valid API token. Please provide your token:{$reset}\n";
echo "  {$yellow}You can get token by: php artisan tinker{$reset}\n";
echo "  {$yellow}Then: \$user = App\\User::first(); \$token = \$user->createToken('test')->accessToken;{$reset}\n";
echo "  {$yellow}Or use existing token from your admin session{$reset}\n\n";

// Uncomment and add your token to test authenticated endpoints
/*
$token = 'YOUR_API_TOKEN_HERE';
$headers = [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
];

$result = makeRequest($adminApiBaseUrl . '/sliders', 'GET', null, $headers);
printResult('GET /admin/api/sliders (with auth)', $result, 200);

// ============================================
// TEST 6: Admin API - Get single slider
// ============================================
echo "{$yellow}[TEST 6] Admin API - Get single slider{$reset}\n";
$result = makeRequest($adminApiBaseUrl . '/sliders/1', 'GET', null, $headers);
printResult('GET /admin/api/sliders/1', $result, 200);

// ============================================
// TEST 7: Admin API - Create slider
// ============================================
echo "{$yellow}[TEST 7] Admin API - Create new slider{$reset}\n";
$newSlider = [
    'name' => 'Test Slider ' . date('Y-m-d H:i:s'),
    'link' => 'https://example.com',
    'image' => 'uploads/sliders/test.jpg',
    'display' => 'desktop',
    'status' => '1'
];
$result = makeRequest($adminApiBaseUrl . '/sliders', 'POST', $newSlider, $headers);
printResult('POST /admin/api/sliders', $result, 201);

// Get the created slider ID
$responseData = json_decode($result['body'], true);
$createdId = $responseData['data']['id'] ?? null;

if ($createdId) {
    // ============================================
    // TEST 8: Admin API - Update slider
    // ============================================
    echo "{$yellow}[TEST 8] Admin API - Update slider{$reset}\n";
    $updateData = [
        'name' => 'Updated Test Slider',
        'link' => 'https://example.com/updated',
        'image' => 'uploads/sliders/test-updated.jpg',
        'display' => 'mobile',
        'status' => '1'
    ];
    $result = makeRequest($adminApiBaseUrl . '/sliders/' . $createdId, 'PUT', $updateData, $headers);
    printResult('PUT /admin/api/sliders/' . $createdId, $result, 200);
    
    // ============================================
    // TEST 9: Admin API - Update status
    // ============================================
    echo "{$yellow}[TEST 9] Admin API - Update slider status{$reset}\n";
    $statusData = ['status' => '0'];
    $result = makeRequest($adminApiBaseUrl . '/sliders/' . $createdId . '/status', 'PATCH', $statusData, $headers);
    printResult('PATCH /admin/api/sliders/' . $createdId . '/status', $result, 200);
    
    // ============================================
    // TEST 10: Admin API - Delete slider
    // ============================================
    echo "{$yellow}[TEST 10] Admin API - Delete slider{$reset}\n";
    $result = makeRequest($adminApiBaseUrl . '/sliders/' . $createdId, 'DELETE', null, $headers);
    printResult('DELETE /admin/api/sliders/' . $createdId, $result, 200);
}
*/

echo "\n{$blue}=== TESTING COMPLETE ==={$reset}\n";
echo "\n{$yellow}Note: To test authenticated endpoints, uncomment the code in TEST 5-10{$reset}\n";
echo "{$yellow}and provide a valid API token.{$reset}\n\n";
