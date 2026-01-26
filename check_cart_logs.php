<?php

/**
 * Cart Logs Checker.
 *
 * Tự động kiểm tra logs từ Laravel để debug cart issues
 *
 * Usage: php check_cart_logs.php [--tail=50] [--filter=CART]
 */

// Try multiple possible log file locations
$logDir = __DIR__.'/storage/logs';
$possibleLogFiles = [
    $logDir.'/laravel.log',
    $logDir.'/laravel-'.date('Y-m-d').'.log',
];

$logFile = null;
foreach ($possibleLogFiles as $file) {
    if (file_exists($file)) {
        $logFile = $file;
        break;
    }
}

// If still not found, try to find any laravel log file
if (! $logFile && is_dir($logDir)) {
    $files = glob($logDir.'/laravel*.log');
    if (! empty($files)) {
        // Get the most recent one
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $logFile = $files[0];
    }
}
$tail = 50;
$filter = 'CART';

// Parse command line arguments
if ($argc > 1) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--tail=') === 0) {
            $tail = (int) substr($arg, 7);
        }
        if (strpos($arg, '--filter=') === 0) {
            $filter = substr($arg, 9);
        }
    }
}

if (! $logFile || ! file_exists($logFile)) {
    echo "❌ Log file not found!\n";
    echo "📁 Tried locations:\n";
    foreach ($possibleLogFiles as $file) {
        echo "   - $file\n";
    }
    if (is_dir($logDir)) {
        echo "   - Searching in: $logDir\n";
    }
    echo "\n💡 Make sure you have performed at least one cart operation to generate logs.\n";
    exit(1);
}

echo "📋 Checking Cart Logs...\n";
echo "📁 Log file: $logFile\n";
echo "🔍 Filter: $filter\n";
echo "📊 Tail: $tail lines\n";
echo str_repeat('=', 80)."\n\n";

// Read log file
$lines = file($logFile);
$totalLines = count($lines);

// Get last N lines
$startLine = max(0, $totalLines - $tail);
$relevantLines = array_slice($lines, $startLine);

// Filter and display
$found = false;
$currentLog = [];

foreach ($relevantLines as $line) {
    // Check if line contains filter
    if (stripos($line, $filter) !== false || stripos($line, 'CartService') !== false || stripos($line, 'CartController') !== false) {
        $found = true;
        $currentLog[] = $line;

        // If line starts with date, it's a new log entry
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            // Display previous log if exists
            if (count($currentLog) > 1) {
                echo implode('', array_slice($currentLog, 0, -1));
            }
            $currentLog = [$line];
        }
    }
}

// Display last log
if (! empty($currentLog)) {
    echo implode('', $currentLog);
}

if (! $found) {
    echo "⚠️  No logs found with filter '$filter' in last $tail lines\n";
    echo "\n💡 Try:\n";
    echo "   - Increase tail: php check_cart_logs.php --tail=200\n";
    echo "   - Check all logs: php check_cart_logs.php --filter=\n";
}

echo "\n".str_repeat('=', 80)."\n";
echo "✅ Done!\n";
