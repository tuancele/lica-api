<?php
/**
 * Order Admin API Test Script
 * 
 * Test all Order Admin API endpoints
 * 
 * Usage: php test_order_admin_api.php
 * 
 * Note: Requires authentication token
 */

$adminBaseUrl = 'http://lica.test/admin/api';

// Authentication token (you need to get this from login)
$authToken = 'YOUR_AUTH_TOKEN_HERE'; // Replace with actual token

// Test data
$testOrderId = 1; // Change this to a valid order ID

echo "=== Order Admin API Test Script ===\n\n";

if ($authToken === 'YOUR_AUTH_TOKEN_HERE') {
    echo "WARNING: Please set a valid authentication token!\n";
    echo "You can get the token by logging in through the API.\n\n";
}

// Helper function to make HTTP requests
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
        'Content-Type: application/json',
        'Accept: application/json',
    ], $headers));
    
    if ($method === 'POST' || $method === 'PUT') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true),
        'raw' => $response,
    ];
}

// Test 1: GET /admin/api/orders (List orders)
echo "Test 1: GET /admin/api/orders (List orders)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$adminBaseUrl}/orders", 'GET', null, [
    'Authorization: Bearer ' . $authToken,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 2: GET /admin/api/orders?status=0 (Filter by status)
echo "Test 2: GET /admin/api/orders?status=0 (Filter by status)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$adminBaseUrl}/orders?status=0", 'GET', null, [
    'Authorization: Bearer ' . $authToken,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 3: GET /admin/api/orders?keyword=123 (Search by keyword)
echo "Test 3: GET /admin/api/orders?keyword=123 (Search by keyword)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$adminBaseUrl}/orders?keyword=123", 'GET', null, [
    'Authorization: Bearer ' . $authToken,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 4: GET /admin/api/orders?date_from=2024-01-01&date_to=2024-12-31 (Filter by date)
echo "Test 4: GET /admin/api/orders?date_from=2024-01-01&date_to=2024-12-31 (Filter by date)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$adminBaseUrl}/orders?date_from=2024-01-01&date_to=2024-12-31", 'GET', null, [
    'Authorization: Bearer ' . $authToken,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 5: GET /admin/api/orders?page=1&limit=5 (Pagination)
echo "Test 5: GET /admin/api/orders?page=1&limit=5 (Pagination)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$adminBaseUrl}/orders?page=1&limit=5", 'GET', null, [
    'Authorization: Bearer ' . $authToken,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 6: GET /admin/api/orders/{id} (Get order detail)
echo "Test 6: GET /admin/api/orders/{$testOrderId} (Get order detail)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$adminBaseUrl}/orders/{$testOrderId}", 'GET', null, [
    'Authorization: Bearer ' . $authToken,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 7: PUT /admin/api/orders/{id}/status (Update order status)
echo "Test 7: PUT /admin/api/orders/{$testOrderId}/status (Update order status)\n";
echo "----------------------------------------\n";
echo "NOTE: This test will update the order status. Comment out if you don't want to modify data.\n\n";
/*
$result = makeRequest("{$adminBaseUrl}/orders/{$testOrderId}/status", 'PUT', [
    'status' => '1', // 0=Chờ xử lý, 1=Đã xác nhận, 2=Đã giao hàng, 3=Hoàn thành, 4=Đã hủy
], [
    'Authorization: Bearer ' . $authToken,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
*/

// Test Error Cases
echo "=== Error Cases ===\n\n";

// Test 8: GET /admin/api/orders/{id} (Invalid order ID)
echo "Test 8: GET /admin/api/orders/999999 (Invalid order ID)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$adminBaseUrl}/orders/999999", 'GET', null, [
    'Authorization: Bearer ' . $authToken,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 9: PUT /admin/api/orders/{id}/status (Invalid status)
echo "Test 9: PUT /admin/api/orders/{$testOrderId}/status (Invalid status)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$adminBaseUrl}/orders/{$testOrderId}/status", 'PUT', [
    'status' => '99', // Invalid status
], [
    'Authorization: Bearer ' . $authToken,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 10: GET /admin/api/orders (Without authentication)
echo "Test 10: GET /admin/api/orders (Without authentication)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$adminBaseUrl}/orders");
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== Test Complete ===\n";
