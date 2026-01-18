<?php
/**
 * Order API Test Script
 * 
 * Usage: php test_order_api.php
 * 
 * Note: Admin API endpoints require authentication token
 * Set $apiToken variable below with your Bearer token
 */

// Configuration
$baseUrl = 'http://lica.test'; // Change to your domain
$apiToken = ''; // Set your Bearer token here for Admin API tests

// Colors for terminal output
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$blue = "\033[34m";
$reset = "\033[0m";

/**
 * Make HTTP request
 */
function makeRequest($method, $url, $headers = [], $body = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    if ($body && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response,
        'error' => $error,
        'data' => json_decode($response, true)
    ];
}

/**
 * Print test result
 */
function printResult($testName, $result, $expectedCode = 200) {
    global $green, $red, $yellow, $reset;
    
    $status = $result['code'] == $expectedCode ? "{$green}✓ PASS{$reset}" : "{$red}✗ FAIL{$reset}";
    echo "{$testName}: {$status}\n";
    echo "  HTTP Code: {$result['code']} (Expected: {$expectedCode})\n";
    
    if ($result['data']) {
        if (isset($result['data']['success'])) {
            echo "  Success: " . ($result['data']['success'] ? 'true' : 'false') . "\n";
        }
        if (isset($result['data']['message'])) {
            echo "  Message: {$result['data']['message']}\n";
        }
        if (isset($result['data']['data']) && is_array($result['data']['data'])) {
            if (isset($result['data']['data'][0])) {
                echo "  Data Count: " . count($result['data']['data']) . " items\n";
            } else {
                echo "  Data: Available\n";
            }
        }
        if (isset($result['data']['pagination'])) {
            echo "  Pagination: Page {$result['data']['pagination']['current_page']} of {$result['data']['pagination']['last_page']}\n";
        }
    }
    
    if ($result['error']) {
        echo "  {$red}Error: {$result['error']}{$reset}\n";
    }
    
    echo "\n";
}

echo "{$blue}=== Order API Test Suite ==={$reset}\n\n";

// Test 1: GET /admin/api/orders - List orders
echo "{$yellow}Test 1: GET /admin/api/orders (List Orders){$reset}\n";
$headers = [];
if ($apiToken) {
    $headers[] = "Authorization: Bearer {$apiToken}";
}
$result = makeRequest('GET', "{$baseUrl}/admin/api/orders", $headers);
printResult("GET /admin/api/orders", $result);

// Test 2: GET /admin/api/orders with filters
echo "{$yellow}Test 2: GET /admin/api/orders?status=0&limit=5{$reset}\n";
$result = makeRequest('GET', "{$baseUrl}/admin/api/orders?status=0&limit=5", $headers);
printResult("GET /admin/api/orders?status=0&limit=5", $result);

// Test 3: GET /admin/api/orders/{id} - Order detail
echo "{$yellow}Test 3: GET /admin/api/orders/1 (Order Detail){$reset}\n";
$result = makeRequest('GET', "{$baseUrl}/admin/api/orders/1", $headers);
printResult("GET /admin/api/orders/1", $result);

// Test 4: GET /admin/api/orders/{id} - Non-existent order
echo "{$yellow}Test 4: GET /admin/api/orders/99999 (Non-existent){$reset}\n";
$result = makeRequest('GET', "{$baseUrl}/admin/api/orders/99999", $headers);
printResult("GET /admin/api/orders/99999", $result, 404);

// Test 5: PATCH /admin/api/orders/{id}/status - Update status
if ($apiToken) {
    echo "{$yellow}Test 5: PATCH /admin/api/orders/1/status (Update Status){$reset}\n";
    $headers[] = "Content-Type: application/json";
    $result = makeRequest('PATCH', "{$baseUrl}/admin/api/orders/1/status", $headers, [
        'status' => '1'
    ]);
    printResult("PATCH /admin/api/orders/1/status", $result);
    
    // Test 6: PATCH /admin/api/orders/{id}/status - Cancel order
    echo "{$yellow}Test 6: PATCH /admin/api/orders/1/status (Cancel Order){$reset}\n";
    $result = makeRequest('PATCH', "{$baseUrl}/admin/api/orders/1/status", $headers, [
        'status' => '4'
    ]);
    printResult("PATCH /admin/api/orders/1/status (Cancel)", $result);
    
    // Test 7: PUT /admin/api/orders/{id} - Update order info
    echo "{$yellow}Test 7: PUT /admin/api/orders/1 (Update Order Info){$reset}\n";
    $result = makeRequest('PUT', "{$baseUrl}/admin/api/orders/1", $headers, [
        'name' => 'Test Customer',
        'phone' => '0123456789'
    ]);
    printResult("PUT /admin/api/orders/1", $result);
    
    // Test 8: PUT /admin/api/orders/{id} - Update cancelled order (should fail)
    echo "{$yellow}Test 8: PUT /admin/api/orders/1 (Update Cancelled Order - Should Fail){$reset}\n";
    $result = makeRequest('PUT', "{$baseUrl}/admin/api/orders/1", $headers, [
        'name' => 'New Name'
    ]);
    printResult("PUT /admin/api/orders/1 (Cancelled)", $result, 400);
} else {
    echo "{$yellow}Skipping authenticated tests (no API token provided){$reset}\n";
    echo "Set \$apiToken variable to test authenticated endpoints\n\n";
}

echo "{$blue}=== Test Complete ==={$reset}\n";
