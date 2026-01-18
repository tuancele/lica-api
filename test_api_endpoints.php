<?php

/**
 * API Endpoints Testing Script
 * 
 * This script tests all Product API endpoints
 * Run: php test_api_endpoints.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Base URL for API
$baseUrl = 'http://localhost/admin/api';
$token = ''; // API token if needed

// Test results
$results = [];

/**
 * Make HTTP request
 */
function makeRequest($method, $url, $data = null, $token = null) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true) ?: $response
    ];
}

/**
 * Test endpoint
 */
function testEndpoint($name, $method, $url, $data = null, $expectedCode = 200) {
    global $baseUrl, $token, $results;
    
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "Testing: $name\n";
    echo "Method: $method\n";
    echo "URL: $url\n";
    if ($data) {
        echo "Data: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo str_repeat('-', 80) . "\n";
    
    $response = makeRequest($method, $baseUrl . $url, $data, $token);
    
    $success = $response['code'] == $expectedCode;
    $status = $success ? '✓ PASS' : '✗ FAIL';
    
    echo "Expected HTTP Code: $expectedCode\n";
    echo "Actual HTTP Code: {$response['code']}\n";
    echo "Status: $status\n";
    echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    $results[] = [
        'name' => $name,
        'method' => $method,
        'url' => $url,
        'expected' => $expectedCode,
        'actual' => $response['code'],
        'success' => $success,
        'response' => $response['body']
    ];
    
    return $response;
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    PRODUCT API ENDPOINTS TESTING                              ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n";

// ============================================================================
// 1. GET /admin/api/products - List products
// ============================================================================
testEndpoint(
    '1. Get Products List',
    'GET',
    '/products?limit=5',
    null,
    200
);

// ============================================================================
// 2. GET /admin/api/products/{id} - Get single product
// ============================================================================
// First, get a product ID from the list
$listResponse = makeRequest('GET', $baseUrl . '/products?limit=1', null, $token);
$productId = null;

if (isset($listResponse['body']['data'][0]['id'])) {
    $productId = $listResponse['body']['data'][0]['id'];
    testEndpoint(
        '2. Get Product Details',
        'GET',
        "/products/{$productId}",
        null,
        200
    );
} else {
    echo "\n⚠ Skipping product details test - no products found\n";
}

// ============================================================================
// 3. POST /admin/api/products - Create product
// ============================================================================
$newProduct = [
    'name' => 'Test Product ' . time(),
    'slug' => 'test-product-' . time(),
    'description' => 'This is a test product',
    'status' => '1',
    'price' => 100000,
    'sale' => 80000,
    'sku' => 'TEST-SKU-' . time(),
    'has_variants' => 0,
    'stock_qty' => 100,
    'weight' => 500
];

$createResponse = testEndpoint(
    '3. Create Product',
    'POST',
    '/products',
    $newProduct,
    201
);

$createdProductId = null;
if (isset($createResponse['body']['data']['id'])) {
    $createdProductId = $createResponse['body']['data']['id'];
}

// ============================================================================
// 4. PUT /admin/api/products/{id} - Update product
// ============================================================================
if ($createdProductId) {
    $updateData = [
        'id' => $createdProductId,
        'name' => 'Updated Test Product ' . time(),
        'slug' => 'updated-test-product-' . time(),
        'description' => 'This is an updated test product',
        'status' => '1'
    ];
    
    testEndpoint(
        '4. Update Product',
        'PUT',
        "/products/{$createdProductId}",
        $updateData,
        200
    );
}

// ============================================================================
// 5. PATCH /admin/api/products/{id}/status - Update status
// ============================================================================
if ($createdProductId) {
    testEndpoint(
        '5. Update Product Status',
        'PATCH',
        "/products/{$createdProductId}/status",
        ['status' => '0'],
        200
    );
}

// ============================================================================
// 6. POST /admin/api/products/bulk-action - Bulk action
// ============================================================================
if ($createdProductId) {
    testEndpoint(
        '6. Bulk Action (Show)',
        'POST',
        '/products/bulk-action',
        [
            'checklist' => [$createdProductId],
            'action' => 1
        ],
        200
    );
}

// ============================================================================
// 7. PATCH /admin/api/products/sort - Update sort
// ============================================================================
if ($createdProductId) {
    testEndpoint(
        '7. Update Product Sort',
        'PATCH',
        '/products/sort',
        [
            'sort' => [
                (string)$createdProductId => 10
            ]
        ],
        200
    );
}

// ============================================================================
// 8. GET /admin/api/products/{id}/variants - Get variants
// ============================================================================
if ($createdProductId) {
    testEndpoint(
        '8. Get Product Variants',
        'GET',
        "/products/{$createdProductId}/variants",
        null,
        200
    );
}

// ============================================================================
// 9. POST /admin/api/products/{id}/variants - Create variant
// ============================================================================
if ($createdProductId) {
    $newVariant = [
        'sku' => 'TEST-VARIANT-' . time(),
        'product_id' => $createdProductId,
        'price' => 120000,
        'sale' => 100000,
        'stock' => 50,
        'weight' => 600
    ];
    
    $createVariantResponse = testEndpoint(
        '9. Create Variant',
        'POST',
        "/products/{$createdProductId}/variants",
        $newVariant,
        201
    );
    
    $createdVariantId = null;
    if (isset($createVariantResponse['body']['data']['id'])) {
        $createdVariantId = $createVariantResponse['body']['data']['id'];
        
        // ============================================================================
        // 10. GET /admin/api/products/{id}/variants/{code} - Get variant
        // ============================================================================
        testEndpoint(
            '10. Get Variant Details',
            'GET',
            "/products/{$createdProductId}/variants/{$createdVariantId}",
            null,
            200
        );
        
        // ============================================================================
        // 11. PUT /admin/api/products/{id}/variants/{code} - Update variant
        // ============================================================================
        testEndpoint(
            '11. Update Variant',
            'PUT',
            "/products/{$createdProductId}/variants/{$createdVariantId}",
            [
                'price' => 130000,
                'sale' => 110000
            ],
            200
        );
        
        // ============================================================================
        // 12. DELETE /admin/api/products/{id}/variants/{code} - Delete variant
        // ============================================================================
        testEndpoint(
            '12. Delete Variant',
            'DELETE',
            "/products/{$createdProductId}/variants/{$createdVariantId}",
            null,
            200
        );
    }
}

// ============================================================================
// 13. DELETE /admin/api/products/{id} - Delete product
// ============================================================================
if ($createdProductId) {
    testEndpoint(
        '13. Delete Product',
        'DELETE',
        "/products/{$createdProductId}",
        null,
        200
    );
}

// ============================================================================
// Test Summary
// ============================================================================
echo "\n" . str_repeat('=', 80) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat('=', 80) . "\n";

$total = count($results);
$passed = count(array_filter($results, fn($r) => $r['success']));
$failed = $total - $passed;

echo "Total Tests: $total\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if ($failed > 0) {
    echo "\nFailed Tests:\n";
    foreach ($results as $result) {
        if (!$result['success']) {
            echo "  - {$result['name']}: Expected {$result['expected']}, Got {$result['actual']}\n";
        }
    }
}

echo "\n" . str_repeat('=', 80) . "\n";
