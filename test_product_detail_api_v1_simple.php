<?php

/**
 * Product Detail API V1 Testing Script (Simple Version)
 * 
 * Tests the new GET /api/v1/products/{slug} endpoint using HTTP requests
 * 
 * Usage:
 *   1. Start Laravel server: php artisan serve
 *   2. Update $baseUrl if needed
 *   3. Update $testSlug with a real product slug from your database
 *   4. Run: php test_product_detail_api_v1_simple.php
 */

// Configuration
$baseUrl = 'http://localhost:8000/api/v1'; // Adjust if your server runs on different port
$testSlug = 'nuoc-hoa-vung-kin-foellie-bijou-chinh-hang-100'; // Update with real slug

// Test results
$testResults = [];

/**
 * Make HTTP request using curl
 */
function makeRequest($url) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    
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
 * Test endpoint
 */
function testEndpoint($name, $url, $expectedCode = 200) {
    global $testResults;
    
    echo "\n" . str_repeat('=', 100) . "\n";
    echo "🧪 Testing: $name\n";
    echo "🔗 URL: $url\n";
    echo str_repeat('-', 100) . "\n";
    
    $startTime = microtime(true);
    $response = makeRequest($url);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    $success = $response['code'] == $expectedCode;
    $status = $success ? '✅ PASS' : '❌ FAIL';
    
    echo "Expected HTTP Code: $expectedCode\n";
    echo "Actual HTTP Code: {$response['code']}\n";
    echo "Response Time: {$duration}ms\n";
    echo "Status: $status\n";
    
    if ($response['error']) {
        echo "⚠️  CURL Error: {$response['error']}\n";
        echo "   💡 Make sure Laravel server is running: php artisan serve\n";
    }
    
    if (is_array($response['body'])) {
        echo "\n📦 Response Data:\n";
        
        if (isset($response['body']['success'])) {
            echo "   - success: " . ($response['body']['success'] ? 'true' : 'false') . "\n";
        }
        
        if (isset($response['body']['data'])) {
            $data = $response['body']['data'];
            echo "   - data.id: " . ($data['id'] ?? 'N/A') . "\n";
            echo "   - data.name: " . (isset($data['name']) ? substr($data['name'], 0, 60) . '...' : 'N/A') . "\n";
            echo "   - data.slug: " . ($data['slug'] ?? 'N/A') . "\n";
            echo "   - data.has_variants: " . ($data['has_variants'] ?? 'N/A') . "\n";
            echo "   - data.variants_count: " . ($data['variants_count'] ?? 'N/A') . "\n";
            echo "   - data.rating.average: " . ($data['rating']['average'] ?? 'N/A') . "\n";
            echo "   - data.rating.count: " . ($data['rating']['count'] ?? 'N/A') . "\n";
            echo "   - data.total_sold: " . ($data['total_sold'] ?? 'N/A') . "\n";
            echo "   - data.gallery: " . (isset($data['gallery']) ? count($data['gallery']) . ' images' : 'N/A') . "\n";
            echo "   - data.variants: " . (isset($data['variants']) ? count($data['variants']) . ' variants' : 'N/A') . "\n";
            echo "   - data.flash_sale: " . (isset($data['flash_sale']) && $data['flash_sale'] ? 'Active' : 'None') . "\n";
            echo "   - data.deal: " . (isset($data['deal']) && $data['deal'] ? 'Active' : 'None') . "\n";
            echo "   - data.ingredient: " . (isset($data['ingredient']) ? 'Yes' : 'No') . "\n";
            echo "   - data.related_products: " . (isset($data['related_products']) ? count($data['related_products']) . ' products' : 'N/A') . "\n";
            
            // Validate key fields
            $checks = [
                'Has ID' => isset($data['id']),
                'Has Name' => isset($data['name']),
                'Has Slug' => isset($data['slug']),
                'Has Image' => isset($data['image']),
                'Has Gallery Array' => isset($data['gallery']) && is_array($data['gallery']),
                'Has Brand' => isset($data['brand']),
                'Has Variants Array' => isset($data['variants']) && is_array($data['variants']),
                'Has Rating Object' => isset($data['rating']) && is_array($data['rating']),
                'Has Categories Array' => isset($data['categories']) && is_array($data['categories']),
            ];
            
            echo "\n✅ Data Validation:\n";
            $allValid = true;
            foreach ($checks as $check => $result) {
                $icon = $result ? '✓' : '✗';
                echo "   $icon $check\n";
                if (!$result) $allValid = false;
            }
            
            if ($allValid && $success) {
                echo "\n🎉 All validations passed!\n";
            }
        } else if (isset($response['body']['message'])) {
            echo "   Message: {$response['body']['message']}\n";
        }
        
        // Show response (truncated if too long)
        $responseJson = json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($responseJson) > 1500) {
            echo "\n📄 Response (first 1500 chars):\n" . substr($responseJson, 0, 1500) . "...\n";
        } else {
            echo "\n📄 Full Response:\n$responseJson\n";
        }
    } else {
        echo "\n⚠️  Response is not valid JSON:\n";
        echo substr($response['raw'], 0, 500) . "\n";
    }
    
    $testResults[] = [
        'name' => $name,
        'url' => $url,
        'expected' => $expectedCode,
        'actual' => $response['code'],
        'success' => $success,
        'duration' => $duration,
        'has_data' => isset($response['body']['data']),
    ];
    
    return $response;
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    PRODUCT DETAIL API V1 TESTING                                                    ║\n";
echo "║                    GET /api/v1/products/{slug}                                                      ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════════════════════════╝\n";

echo "\n📋 Configuration:\n";
echo "   Base URL: $baseUrl\n";
echo "   Test Slug: $testSlug\n";
echo "\n💡 Make sure Laravel server is running: php artisan serve\n";

// Test 1: Valid product slug
testEndpoint(
    '1. Get Product Detail by Slug (Valid)',
    "$baseUrl/products/$testSlug",
    200
);

// Test 2: Invalid product slug (should return 404)
testEndpoint(
    '2. Get Product Detail by Slug (Invalid - 404 Expected)',
    "$baseUrl/products/invalid-slug-that-does-not-exist-12345",
    404
);

// Test Summary
echo "\n" . str_repeat('=', 100) . "\n";
echo "📊 TEST SUMMARY\n";
echo str_repeat('=', 100) . "\n";

$total = count($testResults);
$passed = count(array_filter($testResults, fn($r) => $r['success']));
$failed = $total - $passed;
$avgDuration = $total > 0 
    ? round(array_sum(array_column($testResults, 'duration')) / $total, 2)
    : 0;

echo "Total Tests: $total\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "⏱️  Average Response Time: {$avgDuration}ms\n";

if ($failed > 0) {
    echo "\n❌ Failed Tests:\n";
    foreach ($testResults as $result) {
        if (!$result['success']) {
            echo "   - {$result['name']}: Expected HTTP {$result['expected']}, Got {$result['actual']}\n";
        }
    }
}

echo "\n" . str_repeat('=', 100) . "\n";
echo "💡 Next Steps:\n";
echo "   1. If tests fail, check:\n";
echo "      - Laravel server is running (php artisan serve)\n";
echo "      - Route is registered in routes/api.php\n";
echo "      - Product slug exists in database\n";
echo "      - Check Laravel logs: storage/logs/laravel.log\n";
echo "   2. To test with different slug, edit \$testSlug in this file\n";
echo "   3. To test manually with curl:\n";
echo "      curl -X GET \"$baseUrl/products/$testSlug\" -H \"Accept: application/json\"\n";
echo str_repeat('=', 100) . "\n";
