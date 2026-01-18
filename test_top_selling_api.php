<?php
/**
 * Top Selling Products API Test Script
 * 
 * Usage: php test_top_selling_api.php
 */

// Configuration
$baseUrl = 'http://lica.test';

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
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
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
        if (isset($result['data']['count'])) {
            echo "  Count: {$result['data']['count']} products\n";
        }
        if (isset($result['data']['data']) && is_array($result['data']['data'])) {
            echo "  Products: " . count($result['data']['data']) . " items\n";
            if (count($result['data']['data']) > 0) {
                $first = $result['data']['data'][0];
                echo "  First Product:\n";
                echo "    - ID: " . ($first['id'] ?? 'N/A') . "\n";
                echo "    - Name: " . ($first['name'] ?? 'N/A') . "\n";
                echo "    - Total Sold: " . ($first['total_sold'] ?? 'N/A') . "\n";
                echo "    - Total Sold This Month: " . ($first['total_sold_month'] ?? 'N/A') . "\n";
                echo "    - Price: " . ($first['price'] ?? 'N/A') . "\n";
                echo "    - Sale: " . ($first['sale'] ?? 'N/A') . "\n";
            }
        }
    }
    
    if ($result['error']) {
        echo "  {$red}Error: {$result['error']}{$reset}\n";
    }
    
    echo "\n";
}

echo "{$blue}=== Top Selling Products API Test Suite ==={$reset}\n\n";

// Test 1: GET /api/products/top-selling (default limit)
echo "{$yellow}Test 1: GET /api/products/top-selling (default limit){$reset}\n";
$headers = ['Accept: application/json'];
$result = makeRequest('GET', "{$baseUrl}/api/products/top-selling", $headers);
printResult("GET /api/products/top-selling", $result, 200);

// Test 2: GET /api/products/top-selling?limit=5
echo "{$yellow}Test 2: GET /api/products/top-selling?limit=5{$reset}\n";
$result = makeRequest('GET', "{$baseUrl}/api/products/top-selling?limit=5", $headers);
printResult("GET /api/products/top-selling?limit=5", $result, 200);

// Test 3: GET /api/products/top-selling?limit=20
echo "{$yellow}Test 3: GET /api/products/top-selling?limit=20{$reset}\n";
$result = makeRequest('GET', "{$baseUrl}/api/products/top-selling?limit=20", $headers);
printResult("GET /api/products/top-selling?limit=20", $result, 200);

// Test 4: Verify response structure
echo "{$yellow}Test 4: Verify Response Structure{$reset}\n";
$result = makeRequest('GET', "{$baseUrl}/api/products/top-selling?limit=1", $headers);
if ($result['code'] == 200 && isset($result['data']['data'][0])) {
    $product = $result['data']['data'][0];
    $requiredFields = ['id', 'name', 'slug', 'image', 'price', 'total_sold', 'total_sold_month'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($product[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (empty($missingFields)) {
        echo "  {$green}✓ All required fields present{$reset}\n";
    } else {
        echo "  {$red}✗ Missing fields: " . implode(', ', $missingFields) . "{$reset}\n";
    }
} else {
    echo "  {$red}✗ Cannot verify structure - API returned error{$reset}\n";
}

echo "\n{$blue}=== Test Complete ==={$reset}\n";
echo "\n{$yellow}Note:{$reset}\n";
echo "- API tính toán dựa trên tất cả đơn hàng (trừ đơn hàng đã hủy)\n";
echo "- total_sold: Tổng số lượng đã bán từ tất cả đơn hàng\n";
echo "- total_sold_month: Số lượng đã bán trong tháng hiện tại\n";
