<?php
/**
 * Simple Order API Test
 */

$baseUrl = 'http://lica.test';

// Test 1: Simple GET request
echo "Testing GET /admin/api/orders...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/admin/api/orders");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
if ($error) {
    echo "Error: {$error}\n";
}
echo "Response:\n";
echo substr($response, 0, 500) . "\n";
