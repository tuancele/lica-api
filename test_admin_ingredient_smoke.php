<?php

$base = 'http://localhost'; // adjust if needed
$token = getenv('API_TOKEN') ?: '';

function request($method, $url, $body = null, $headers = [])
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($body) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) {
        throw new Exception($err);
    }
    return json_decode($res, true);
}

try {
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    echo "1) Create ingredient...\n";
    $payload = [
        'name' => 'Smoke Test Ingredient',
        'slug' => 'smoke-test-ingredient-' . time(),
        'status' => 1,
        'description' => 'desc',
    ];
    $create = request('POST', $base . '/admin/api/ingredients', json_encode($payload), $headers);
    print_r($create);

    $id = $create['data']['id'] ?? null;
    if (!$id) {
        throw new Exception('No ID returned');
    }

    echo "2) Update status...\n";
    $status = request('PATCH', $base . '/admin/api/ingredients/' . $id . '/status', json_encode(['status' => 0]), $headers);
    print_r($status);

    echo "3) Crawl summary...\n";
    $summary = request('GET', $base . '/admin/api/ingredients/crawl/summary', null, $headers);
    print_r($summary);

    echo "Done.\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
