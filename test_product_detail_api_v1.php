<?php

/**
 * Product Detail API V1 Testing Script
 * 
 * Tests the new GET /api/v1/products/{slug} endpoint
 * Run: php test_product_detail_api_v1.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Base URL for API V1
$baseUrl = 'http://localhost/api/v1';
$testResults = [];

/**
 * Make HTTP request
 */
function makeRequest($method, $url, $data = null) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true) ?: $response,
        'raw' => $response,
        'error' => $error
    ];
}

/**
 * Test endpoint with detailed output
 */
function testEndpoint($name, $method, $url, $expectedCode = 200) {
    global $baseUrl, $testResults;
    
    echo "\n" . str_repeat('=', 100) . "\n";
    echo "🧪 Testing: $name\n";
    echo "📍 Method: $method\n";
    echo "🔗 URL: $url\n";
    echo str_repeat('-', 100) . "\n";
    
    $response = makeRequest($method, $baseUrl . $url);
    
    $success = $response['code'] == $expectedCode;
    $status = $success ? '✅ PASS' : '❌ FAIL';
    
    echo "Expected HTTP Code: $expectedCode\n";
    echo "Actual HTTP Code: {$response['code']}\n";
    echo "Status: $status\n";
    
    if ($response['error']) {
        echo "⚠️  CURL Error: {$response['error']}\n";
    }
    
    if (is_array($response['body'])) {
        echo "\n📦 Response Structure:\n";
        echo "   - success: " . ($response['body']['success'] ?? 'N/A') . "\n";
        
        if (isset($response['body']['data'])) {
            $data = $response['body']['data'];
            echo "   - data.id: " . ($data['id'] ?? 'N/A') . "\n";
            echo "   - data.name: " . (isset($data['name']) ? substr($data['name'], 0, 50) . '...' : 'N/A') . "\n";
            echo "   - data.slug: " . ($data['slug'] ?? 'N/A') . "\n";
            echo "   - data.has_variants: " . ($data['has_variants'] ?? 'N/A') . "\n";
            echo "   - data.variants_count: " . ($data['variants_count'] ?? 'N/A') . "\n";
            echo "   - data.rating.average: " . ($data['rating']['average'] ?? 'N/A') . "\n";
            echo "   - data.rating.count: " . ($data['rating']['count'] ?? 'N/A') . "\n";
            echo "   - data.total_sold: " . ($data['total_sold'] ?? 'N/A') . "\n";
            echo "   - data.flash_sale: " . (isset($data['flash_sale']) ? 'Yes' : 'No') . "\n";
            echo "   - data.deal: " . (isset($data['deal']) ? 'Yes' : 'No') . "\n";
            echo "   - data.ingredient: " . (isset($data['ingredient']) ? 'Yes' : 'No') . "\n";
            echo "   - data.related_products: " . (isset($data['related_products']) ? count($data['related_products']) . ' items' : 'No') . "\n";
            
            // Check key fields
            $checks = [
                'Has ID' => isset($data['id']),
                'Has Name' => isset($data['name']),
                'Has Slug' => isset($data['slug']),
                'Has Image' => isset($data['image']),
                'Has Gallery' => isset($data['gallery']) && is_array($data['gallery']),
                'Has Brand' => isset($data['brand']),
                'Has Variants' => isset($data['variants']) && is_array($data['variants']),
                'Has Rating' => isset($data['rating']),
            ];
            
            echo "\n✅ Data Validation:\n";
            foreach ($checks as $check => $result) {
                echo "   " . ($result ? '✓' : '✗') . " $check\n";
            }
        }
        
        if (isset($response['body']['message'])) {
            echo "\n💬 Message: {$response['body']['message']}\n";
        }
        
        // Show full response (truncated if too long)
        $responseJson = json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($responseJson) > 2000) {
            echo "\n📄 Response (truncated):\n" . substr($responseJson, 0, 2000) . "...\n";
        } else {
            echo "\n📄 Full Response:\n$responseJson\n";
        }
    } else {
        echo "\n⚠️  Response is not valid JSON:\n";
        echo substr($response['raw'], 0, 500) . "\n";
    }
    
    $testResults[] = [
        'name' => $name,
        'method' => $method,
        'url' => $url,
        'expected' => $expectedCode,
        'actual' => $response['code'],
        'success' => $success,
        'has_data' => isset($response['body']['data']),
    ];
    
    return $response;
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    PRODUCT DETAIL API V1 TESTING                                                    ║\n";
echo "║                    GET /api/v1/products/{slug}                                                      ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════════════════════════╝\n";

// Get a real product slug from database
use App\Modules\Product\Models\Product;
use App\Enums\ProductType;

try {
    $product = Product::where([['status', '1'], ['type', ProductType::PRODUCT->value]])
        ->orderBy('id', 'desc')
        ->first();
    
    if ($product) {
        $testSlug = $product->slug;
        echo "\n📦 Found test product:\n";
        echo "   - ID: {$product->id}\n";
        echo "   - Name: {$product->name}\n";
        echo "   - Slug: $testSlug\n";
        echo "   - Has Variants: " . ($product->has_variants ? 'Yes' : 'No') . "\n";
        
        // Test 1: Valid product slug
        testEndpoint(
            '1. Get Product Detail by Slug (Valid)',
            'GET',
            "/products/$testSlug",
            200
        );
        
        // Test 2: Invalid product slug
        testEndpoint(
            '2. Get Product Detail by Slug (Invalid - 404)',
            'GET',
            '/products/invalid-slug-that-does-not-exist-12345',
            404
        );
        
        // Test 3: Empty slug (should fail)
        testEndpoint(
            '3. Get Product Detail by Slug (Empty)',
            'GET',
            '/products/',
            404
        );
        
    } else {
        echo "\n⚠️  No active products found in database. Using sample slug for testing.\n";
        echo "   Please update the script with a real product slug from your database.\n";
        
        // Test with a sample slug (will likely fail, but shows the structure)
        testEndpoint(
            '1. Get Product Detail by Slug (Sample)',
            'GET',
            '/products/sample-product-slug',
            404
        );
    }
    
} catch (\Exception $e) {
    echo "\n❌ Error connecting to database: " . $e->getMessage() . "\n";
    echo "   Testing with sample slug instead...\n";
    
    testEndpoint(
        '1. Get Product Detail by Slug (Sample)',
        'GET',
        '/products/sample-product-slug',
        404
    );
}

// Test Summary
echo "\n" . str_repeat('=', 100) . "\n";
echo "📊 TEST SUMMARY\n";
echo str_repeat('=', 100) . "\n";

$total = count($testResults);
$passed = count(array_filter($testResults, fn($r) => $r['success']));
$failed = $total - $passed;

echo "Total Tests: $total\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";

if ($failed > 0) {
    echo "\n❌ Failed Tests:\n";
    foreach ($testResults as $result) {
        if (!$result['success']) {
            echo "   - {$result['name']}: Expected HTTP {$result['expected']}, Got {$result['actual']}\n";
        }
    }
}

echo "\n" . str_repeat('=', 100) . "\n";
echo "💡 Tips:\n";
echo "   1. Make sure Laravel application is running (php artisan serve)\n";
echo "   2. Check that the route is registered in routes/api.php\n";
echo "   3. Verify the product slug exists in your database\n";
echo "   4. Check Laravel logs if tests fail: storage/logs/laravel.log\n";
echo str_repeat('=', 100) . "\n";
