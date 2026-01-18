<?php
/**
 * User Order API Test Script
 * 
 * Usage: php test_user_order_api.php
 * 
 * Note: Requires member authentication (session cookie)
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
 * Make HTTP request with session support
 */
function makeRequest($method, $url, $headers = [], $body = null, $cookies = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . '/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . '/cookies.txt');
    
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
                if (count($result['data']['data']) > 0) {
                    $first = $result['data']['data'][0];
                    echo "  First Order Code: " . ($first['code'] ?? 'N/A') . "\n";
                }
            } else {
                echo "  Data: Available\n";
                if (isset($result['data']['data']['code'])) {
                    echo "  Order Code: {$result['data']['data']['code']}\n";
                }
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

echo "{$blue}=== User Order API Test Suite ==={$reset}\n\n";

// Test 1: GET /api/v1/orders (without authentication)
echo "{$yellow}Test 1: GET /api/v1/orders (Without Authentication){$reset}\n";
$headers = ['Accept: application/json'];
$result = makeRequest('GET', "{$baseUrl}/api/v1/orders", $headers);
printResult("GET /api/v1/orders (no auth)", $result, 401);

// Test 2: GET /api/v1/orders/{code} (without authentication)
echo "{$yellow}Test 2: GET /api/v1/orders/1680426297 (Without Authentication){$reset}\n";
$result = makeRequest('GET', "{$baseUrl}/api/v1/orders/1680426297", $headers);
printResult("GET /api/v1/orders/{code} (no auth)", $result, 401);

echo "{$yellow}Note: To test authenticated endpoints, you need to:{$reset}\n";
echo "  1. Login to website first to get session cookie\n";
echo "  2. Use browser developer tools to copy session cookie\n";
echo "  3. Add cookie to curl request or use Postman\n\n";

echo "{$blue}=== Test Complete ==={$reset}\n";
echo "\n{$yellow}Next Steps:{$reset}\n";
echo "1. Login to {$baseUrl}/login (member account)\n";
echo "2. Copy session cookie from browser\n";
echo "3. Test with Postman or curl:\n";
echo "   curl -X GET \"{$baseUrl}/api/v1/orders\" \\\n";
echo "     -H \"Cookie: laravel_session=YOUR_SESSION_COOKIE\" \\\n";
echo "     -H \"Accept: application/json\"\n";
