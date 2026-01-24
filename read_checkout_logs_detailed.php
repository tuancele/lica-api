<?php
/**
 * Script to read and analyze checkout calculation logs with full JSON context
 */

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Log file not found: $logFile\n";
    exit(1);
}

// Read last 1000 lines
$lines = file($logFile);
$totalLines = count($lines);
$startLine = max(0, $totalLines - 1000);
$relevantLines = array_slice($lines, $startLine);

echo "=== CHECKOUT CALCULATION LOGS - DETAILED ANALYSIS ===\n";
echo "Reading from line $startLine to $totalLines\n\n";

$foundLogs = [];
$currentLog = null;
$currentContext = '';

foreach ($relevantLines as $lineNum => $line) {
    // Check if line contains our log marker
    if (strpos($line, 'CHECKOUT_CALCULATION') !== false) {
        // Extract timestamp and message
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?\[CHECKOUT_CALCULATION\] (.*?)(?:\s*\|\s*Context:)?(.*)/', $line, $matches)) {
            $timestamp = $matches[1];
            $message = trim($matches[2]);
            $contextPart = isset($matches[3]) ? trim($matches[3]) : '';
            
            // Try to extract JSON context (may span multiple lines)
            $jsonData = null;
            if (strpos($contextPart, '{') !== false) {
                // JSON starts on this line
                $jsonStart = strpos($line, '{');
                $jsonString = substr($line, $jsonStart);
                
                // Try to parse JSON (may be incomplete on this line)
                $jsonData = json_decode($jsonString, true);
                
                // If JSON is incomplete, try to read next lines
                if ($jsonData === null && json_last_error() === JSON_ERROR_SYNTAX) {
                    // Look ahead for complete JSON
                    $jsonString = $jsonString;
                    for ($i = $lineNum + 1; $i < min($lineNum + 20, count($relevantLines)); $i++) {
                        $jsonString .= "\n" . trim($relevantLines[$i]);
                        $jsonData = json_decode($jsonString, true);
                        if ($jsonData !== null) {
                            break;
                        }
                    }
                }
            }
            
            $foundLogs[] = [
                'timestamp' => $timestamp,
                'message' => $message,
                'context' => $jsonData,
                'raw_line' => trim($line),
                'line' => $startLine + $lineNum + 1
            ];
        }
    }
}

// Group by message type
$groupedLogs = [];
foreach ($foundLogs as $log) {
    $key = $log['message'];
    if (!isset($groupedLogs[$key])) {
        $groupedLogs[$key] = [];
    }
    $groupedLogs[$key][] = $log;
}

// Display logs grouped by type
echo "Found " . count($foundLogs) . " log entries:\n\n";

// 1. Shipping Fee Debug
if (isset($groupedLogs['SHIPPING FEE DEBUG - All Sources'])) {
    echo "=== SHIPPING FEE DEBUG LOGS ===\n";
    $shippingLogs = $groupedLogs['SHIPPING FEE DEBUG - All Sources'];
    $latestShipping = end($shippingLogs);
    echo "Latest: {$latestShipping['timestamp']}\n";
    if ($latestShipping['context']) {
        echo "Context:\n";
        echo json_encode($latestShipping['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        // Analysis
        $ctx = $latestShipping['context'];
        echo "\n--- Analysis ---\n";
        echo "Input raw: " . ($ctx["input[name=\"feeShip\"] raw"] ?? 'N/A') . "\n";
        echo "Input parsed: " . ($ctx["input[name=\"feeShip\"] parsed"] ?? 'N/A') . "\n";
        echo "Final shippingFee used: " . ($ctx['Final shippingFee used'] ?? 'N/A') . "\n";
        
        $raw = $ctx["input[name=\"feeShip\"] raw"] ?? '';
        $parsed = $ctx["input[name=\"feeShip\"] parsed"] ?? 0;
        $final = $ctx['Final shippingFee used'] ?? 0;
        
        if (is_string($raw) && strpos($raw, ',') !== false && $parsed < 1000) {
            echo "⚠️  WARNING: Possible parse error! Raw contains comma but parsed value is too small.\n";
        }
    }
    echo "\n";
}

// 2. Calculation Input
if (isset($groupedLogs['CALLING CartPriceCalculator.calculateTotal'])) {
    echo "=== CALCULATION INPUT LOGS ===\n";
    $calcLogs = $groupedLogs['CALLING CartPriceCalculator.calculateTotal'];
    $latestCalc = end($calcLogs);
    echo "Latest: {$latestCalc['timestamp']}\n";
    if ($latestCalc['context']) {
        echo "Context:\n";
        echo json_encode($latestCalc['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        // Analysis
        $ctx = $latestCalc['context'];
        echo "\n--- Analysis ---\n";
        echo "Items count: " . ($ctx['itemsCount'] ?? 'N/A') . "\n";
        echo "Shipping fee: " . ($ctx['shippingFee'] ?? 'N/A') . "\n";
        if (isset($ctx['orderVoucher']) && $ctx['orderVoucher']) {
            echo "Order voucher value: " . ($ctx['orderVoucher']['value'] ?? 'N/A') . "\n";
        }
        
        // Calculate expected subtotal
        if (isset($ctx['items']) && is_array($ctx['items'])) {
            $subtotal = 0;
            foreach ($ctx['items'] as $item) {
                $subtotal += $item['subtotal'] ?? 0;
            }
            echo "Calculated subtotal from items: $subtotal\n";
        }
    }
    echo "\n";
}

// 3. Step 4 Calculation
if (isset($groupedLogs['CartPriceCalculator Step 4 - Final total calculation'])) {
    echo "=== STEP 4 CALCULATION LOGS ===\n";
    $step4Logs = $groupedLogs['CartPriceCalculator Step 4 - Final total calculation'];
    $latestStep4 = end($step4Logs);
    echo "Latest: {$latestStep4['timestamp']}\n";
    if ($latestStep4['context']) {
        echo "Context:\n";
        echo json_encode($latestStep4['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        // Analysis
        $ctx = $latestStep4['context'];
        echo "\n--- Analysis ---\n";
        echo "Formula: " . ($ctx['calculation'] ?? 'N/A') . "\n";
        echo "Total before max: " . ($ctx['totalBeforeMax'] ?? 'N/A') . "\n";
        echo "Total final: " . ($ctx['totalFinal'] ?? 'N/A') . "\n";
        
        // Verify calculation
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
            echo "✅ Calculation matches\n";
        }
    }
    echo "\n";
}

// 4. Total Mismatch Errors
if (isset($groupedLogs['❌ TOTAL MISMATCH!'])) {
    echo "=== ❌ TOTAL MISMATCH ERRORS ===\n";
    foreach ($groupedLogs['❌ TOTAL MISMATCH!'] as $errorLog) {
        echo "Time: {$errorLog['timestamp']}\n";
        if ($errorLog['context']) {
            echo json_encode($errorLog['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
        echo "\n";
    }
} else {
    echo "=== ✅ NO TOTAL MISMATCH ERRORS FOUND ===\n\n";
}

// 5. Summary
echo "=== SUMMARY ===\n";
echo "Total logs: " . count($foundLogs) . "\n";
echo "Shipping Fee Debug: " . (isset($groupedLogs['SHIPPING FEE DEBUG - All Sources']) ? count($groupedLogs['SHIPPING FEE DEBUG - All Sources']) : 0) . "\n";
echo "Calculation Inputs: " . (isset($groupedLogs['CALLING CartPriceCalculator.calculateTotal']) ? count($groupedLogs['CALLING CartPriceCalculator.calculateTotal']) : 0) . "\n";
echo "Step 4 Calculations: " . (isset($groupedLogs['CartPriceCalculator Step 4 - Final total calculation']) ? count($groupedLogs['CartPriceCalculator Step 4 - Final total calculation']) : 0) . "\n";
echo "Total Mismatch Errors: " . (isset($groupedLogs['❌ TOTAL MISMATCH!']) ? count($groupedLogs['❌ TOTAL MISMATCH!']) : 0) . "\n";

echo "\n=== END ===\n";

