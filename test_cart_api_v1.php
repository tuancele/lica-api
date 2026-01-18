<?php
/**
 * Cart API V1 Test Script
 * 
 * Test all Cart API endpoints
 * 
 * Usage: php test_cart_api_v1.php
 */

$baseUrl = 'http://lica.test/api/v1';
$adminBaseUrl = 'http://lica.test/admin/api';

// Test data
$testVariantId = 1; // Change this to a valid variant ID
$testProductId = 1; // Change this to a valid product ID
$testCouponCode = 'SALE10'; // Change this to a valid coupon code

echo "=== Cart API V1 Test Script ===\n\n";

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

// Test 1: GET /api/v1/cart (Empty cart)
echo "Test 1: GET /api/v1/cart (Empty cart)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart");
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 2: POST /api/v1/cart/items (Add single item)
echo "Test 2: POST /api/v1/cart/items (Add single item)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart/items", 'POST', [
    'variant_id' => $testVariantId,
    'qty' => 2,
    'is_deal' => false,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 3: GET /api/v1/cart (Cart with items)
echo "Test 3: GET /api/v1/cart (Cart with items)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart");
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 4: POST /api/v1/cart/items (Add combo - multiple items)
echo "Test 4: POST /api/v1/cart/items (Add combo)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart/items", 'POST', [
    'combo' => [
        [
            'variant_id' => $testVariantId,
            'qty' => 1,
            'is_deal' => false,
        ],
        // Add more items if needed
    ],
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 5: PUT /api/v1/cart/items/{variant_id} (Update quantity)
echo "Test 5: PUT /api/v1/cart/items/{$testVariantId} (Update quantity)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart/items/{$testVariantId}", 'PUT', [
    'qty' => 3,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 6: POST /api/v1/cart/coupon/apply (Apply coupon)
echo "Test 6: POST /api/v1/cart/coupon/apply (Apply coupon)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart/coupon/apply", 'POST', [
    'code' => $testCouponCode,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 7: GET /api/v1/cart (Cart with coupon)
echo "Test 7: GET /api/v1/cart (Cart with coupon)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart");
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 8: POST /api/v1/cart/shipping-fee (Calculate shipping fee)
echo "Test 8: POST /api/v1/cart/shipping-fee (Calculate shipping fee)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart/shipping-fee", 'POST', [
    'province_id' => 1, // Hà Nội
    'district_id' => 1,
    'ward_id' => 1,
    'address' => '123 Đường ABC',
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 9: DELETE /api/v1/cart/coupon (Remove coupon)
echo "Test 9: DELETE /api/v1/cart/coupon (Remove coupon)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart/coupon", 'DELETE');
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 10: POST /api/v1/cart/checkout (Checkout - Commented out to avoid creating real orders)
echo "Test 10: POST /api/v1/cart/checkout (Checkout)\n";
echo "----------------------------------------\n";
echo "NOTE: This test is commented out to avoid creating real orders.\n";
echo "Uncomment to test checkout functionality.\n\n";
/*
$result = makeRequest("{$baseUrl}/cart/checkout", 'POST', [
    'full_name' => 'Nguyễn Văn A',
    'phone' => '0123456789',
    'email' => 'test@example.com',
    'address' => '123 Đường ABC',
    'province_id' => 1,
    'district_id' => 1,
    'ward_id' => 1,
    'remark' => 'Test order',
    'shipping_fee' => 30000,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
*/

// Test 11: DELETE /api/v1/cart/items/{variant_id} (Remove item)
echo "Test 11: DELETE /api/v1/cart/items/{$testVariantId} (Remove item)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart/items/{$testVariantId}", 'DELETE');
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 12: GET /api/v1/cart (Final cart state)
echo "Test 12: GET /api/v1/cart (Final cart state)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart");
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test Error Cases
echo "=== Error Cases ===\n\n";

// Test 13: POST /api/v1/cart/items (Invalid variant_id)
echo "Test 13: POST /api/v1/cart/items (Invalid variant_id)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart/items", 'POST', [
    'variant_id' => 999999,
    'qty' => 1,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 14: POST /api/v1/cart/items (Invalid qty)
echo "Test 14: POST /api/v1/cart/items (Invalid qty)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart/items", 'POST', [
    'variant_id' => $testVariantId,
    'qty' => 0,
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 15: POST /api/v1/cart/coupon/apply (Invalid coupon)
echo "Test 15: POST /api/v1/cart/coupon/apply (Invalid coupon)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart/coupon/apply", 'POST', [
    'code' => 'INVALID_CODE',
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 16: POST /api/v1/cart/shipping-fee (Missing required fields)
echo "Test 16: POST /api/v1/cart/shipping-fee (Missing required fields)\n";
echo "----------------------------------------\n";
$result = makeRequest("{$baseUrl}/cart/shipping-fee", 'POST', [
    'province_id' => 1,
    // Missing district_id, ward_id
]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== Test Complete ===\n";
