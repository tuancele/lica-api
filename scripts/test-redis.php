<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "========================================\n";
echo "LICA - Testing Redis Connection\n";
echo "========================================\n\n";

try {
    // Test Cache
    echo "Testing Cache...\n";
    \Illuminate\Support\Facades\Cache::put('test_key', 'test_value', 60);
    $value = \Illuminate\Support\Facades\Cache::get('test_key');

    if ($value === 'test_value') {
        echo "✅ Cache test: PASSED\n";
    } else {
        echo "❌ Cache test: FAILED (expected 'test_value', got: ".var_export($value, true).")\n";
        exit(1);
    }

    // Test Redis Connection
    echo "Testing Redis connection...\n";
    $ping = \Illuminate\Support\Facades\Redis::connection()->ping();

    // Handle both string and Predis response object
    $pingValue = is_object($ping) && method_exists($ping, 'getPayload')
        ? $ping->getPayload()
        : (string) $ping;

    if ($pingValue === 'PONG' || $pingValue === '+PONG' || stripos($pingValue, 'PONG') !== false) {
        echo "✅ Redis ping: PASSED\n";
    } else {
        echo '❌ Redis ping: FAILED (got: '.var_export($ping, true).")\n";
        exit(1);
    }

    // Test Session (if using Redis for sessions)
    echo "Testing Session...\n";
    \Illuminate\Support\Facades\Session::put('test_session', 'session_value');
    $sessionValue = \Illuminate\Support\Facades\Session::get('test_session');

    if ($sessionValue === 'session_value') {
        echo "✅ Session test: PASSED\n";
    } else {
        echo "❌ Session test: FAILED\n";
        exit(1);
    }

    echo "\n========================================\n";
    echo "✅ All Redis tests PASSED!\n";
    echo "========================================\n";
} catch (\Exception $e) {
    echo "\n========================================\n";
    echo "❌ Redis connection test FAILED\n";
    echo "========================================\n";
    echo 'Error: '.$e->getMessage()."\n";
    echo "\nPlease make sure Redis service is running:\n";
    echo "1. Open Laragon\n";
    echo "2. Services -> Start Redis\n";
    echo "Or use: docker-compose up -d redis\n";
    exit(1);
}
