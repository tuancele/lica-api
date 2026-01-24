<?php
/**
 * Parse Laravel log file and extract CHECKOUT_CALCULATION logs with full JSON context
 */

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Log file not found: $logFile\n";
    exit(1);
}

echo "=== PARSING LARAVEL LOG FILE ===\n";
echo "File: $logFile\n\n";

// Read entire file
$content = file_get_contents($logFile);
$lines = explode("\n", $content);

echo "Total lines: " . count($lines) . "\n\n";

// Find all CHECKOUT_CALCULATION logs
$logs = [];
$currentLog = null;
$currentContext = '';
$inJson = false;
$jsonLines = [];

foreach ($lines as $lineNum => $line) {
    // Check if line contains CHECKOUT_CALCULATION
    if (strpos($line, 'CHECKOUT_CALCULATION') !== false) {
        // Save previous log if exists
        if ($currentLog !== null) {
            // Try to parse JSON context
            if (!empty($jsonLines)) {
                $jsonString = implode("\n", $jsonLines);
                $jsonData = json_decode($jsonString, true);
                if ($jsonData !== null) {
                    $currentLog['context'] = $jsonData;
                } else {
                    $currentLog['context_raw'] = $jsonString;
                }
            }
            $logs[] = $currentLog;
        }
        
        // Extract timestamp and message
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?\[CHECKOUT_CALCULATION\] (.*?)(?:\s*\|\s*Context:)?(.*)/', $line, $matches)) {
            $currentLog = [
                'timestamp' => $matches[1],
                'message' => trim($matches[2]),
                'line' => $lineNum + 1,
                'context' => null
            ];
            
            // Check if JSON starts on this line
            $jsonStart = strpos($line, '{');
            if ($jsonStart !== false) {
                $inJson = true;
                $jsonLines = [substr($line, $jsonStart)];
            } else {
                $inJson = false;
                $jsonLines = [];
            }
        }
    } elseif ($inJson && $currentLog !== null) {
        // Continue collecting JSON
        $jsonLines[] = $line;
        
        // Check if JSON ends
        if (strpos($line, '}') !== false && substr_count(implode('', $jsonLines), '{') === substr_count(implode('', $jsonLines), '}')) {
            $inJson = false;
        }
    }
}

// Save last log
if ($currentLog !== null) {
    if (!empty($jsonLines)) {
        $jsonString = implode("\n", $jsonLines);
        $jsonData = json_decode($jsonString, true);
        if ($jsonData !== null) {
            $currentLog['context'] = $jsonData;
        } else {
            $currentLog['context_raw'] = $jsonString;
        }
    }
    $logs[] = $currentLog;
}

echo "Found " . count($logs) . " CHECKOUT_CALCULATION logs\n\n";

// Group by message type
$grouped = [];
foreach ($logs as $log) {
    $key = $log['message'];
    if (!isset($grouped[$key])) {
        $grouped[$key] = [];
    }
    $grouped[$key][] = $log;
}

// Display analysis
foreach ($grouped as $messageType => $logGroup) {
    echo "=== " . strtoupper($messageType) . " ===\n";
    echo "Count: " . count($logGroup) . "\n";
    
    // Show latest
    $latest = end($logGroup);
    echo "Latest: {$latest['timestamp']}\n";
    
    if ($latest['context']) {
        echo "Context:\n";
        echo json_encode($latest['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        // Special analysis for SHIPPING FEE DEBUG
        if ($messageType === 'SHIPPING FEE DEBUG - All Sources') {
            $ctx = $latest['context'];
            echo "\n--- SHIPPING FEE ANALYSIS ---\n";
            echo "Input raw: " . ($ctx["input[name=\"feeShip\"] raw"] ?? 'N/A') . "\n";
            echo "Input parsed: " . ($ctx["input[name=\"feeShip\"] parsed"] ?? 'N/A') . "\n";
            echo "Final used: " . ($ctx['Final shippingFee used'] ?? 'N/A') . "\n";
            
            $raw = $ctx["input[name=\"feeShip\"] raw"] ?? '';
            $parsed = $ctx["input[name=\"feeShip\"] parsed"] ?? 0;
            
            if (is_string($raw) && (strpos($raw, ',') !== false || strpos($raw, '.') !== false)) {
                $expected = (int) preg_replace('/[^\d]/', '', $raw);
                if ($parsed != $expected) {
                    echo "⚠️  PARSE ERROR! Expected: $expected, Got: $parsed\n";
                } else {
                    echo "✅ Parse correct\n";
                }
            }
        }
        
        // Special analysis for Step 4
        if (strpos($messageType, 'Step 4') !== false) {
            $ctx = $latest['context'];
            echo "\n--- CALCULATION ANALYSIS ---\n";
            echo "Formula: " . ($ctx['calculation'] ?? 'N/A') . "\n";
            
            $subtotal = $ctx['subtotal'] ?? 0;
            $itemDiscount = $ctx['itemDiscount'] ?? 0;
            $orderDiscount = $ctx['orderDiscount'] ?? 0;
            $shippingFee = $ctx['shippingFee'] ?? 0;
            $expected = ($subtotal - $itemDiscount - $orderDiscount) + $shippingFee;
            $got = $ctx['totalFinal'] ?? 0;
            
            echo "Expected: ($subtotal - $itemDiscount - $orderDiscount) + $shippingFee = $expected\n";
            echo "Got: $got\n";
            
            if (abs($expected - $got) > 1) {
                echo "❌ MISMATCH! Difference: " . abs($expected - $got) . "\n";
            } else {
                echo "✅ Calculation correct\n";
            }
        }
    }
    
    echo "\n";
}

// Check for TOTAL MISMATCH
if (isset($grouped['❌ TOTAL MISMATCH!'])) {
    echo "=== ❌ ERRORS FOUND ===\n";
    foreach ($grouped['❌ TOTAL MISMATCH!'] as $error) {
        echo "Time: {$error['timestamp']}\n";
        if ($error['context']) {
            $ctx = $error['context'];
            if (isset($ctx['BREAKDOWN'])) {
                $bd = $ctx['BREAKDOWN'];
                echo "  Missing: " . ($bd['Missing'] ?? 'N/A') . "\n";
                echo "  Expected: " . ($bd['Expected'] ?? 'N/A') . "\n";
                echo "  Got: " . ($bd['Got'] ?? 'N/A') . "\n";
            }
        }
        echo "\n";
    }
} else {
    echo "=== ✅ NO TOTAL MISMATCH ERRORS ===\n\n";
}

echo "=== END ===\n";

