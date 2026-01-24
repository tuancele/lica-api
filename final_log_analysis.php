<?php
/**
 * Final comprehensive log analysis
 */

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Log file not found\n";
    exit(1);
}

$content = file_get_contents($logFile);
$lines = explode("\n", $content);

echo "=== FINAL COMPREHENSIVE LOG ANALYSIS ===\n\n";

// Parse all log entries with complete JSON
$allEntries = [];
$currentEntry = null;
$jsonBuffer = '';
$inJson = false;
$braceCount = 0;

foreach ($lines as $lineNum => $line) {
    if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?\[CHECKOUT_CALCULATION\] (.*?)(?:\s*\|\s*Context:)?(.*)/', $line, $matches)) {
        // Save previous
        if ($currentEntry !== null) {
            if (!empty($jsonBuffer)) {
                $jsonData = json_decode($jsonBuffer, true);
                if ($jsonData !== null) {
                    $currentEntry['context'] = $jsonData;
                }
            }
            $allEntries[] = $currentEntry;
        }
        
        $currentEntry = [
            'timestamp' => $matches[1],
            'message' => trim($matches[2]),
            'line' => $lineNum + 1
        ];
        
        $jsonStart = strpos($line, '{');
        if ($jsonStart !== false) {
            $jsonBuffer = substr($line, $jsonStart);
            $inJson = true;
            $braceCount = substr_count($jsonBuffer, '{') - substr_count($jsonBuffer, '}');
        } else {
            $jsonBuffer = '';
            $inJson = false;
        }
    } elseif ($inJson && $currentEntry !== null) {
        $jsonBuffer .= "\n" . trim($line);
        $braceCount += substr_count($line, '{') - substr_count($line, '}');
        if ($braceCount === 0) {
            $inJson = false;
        }
    }
}

if ($currentEntry !== null) {
    if (!empty($jsonBuffer)) {
        $jsonData = json_decode($jsonBuffer, true);
        if ($jsonData !== null) {
            $currentEntry['context'] = $jsonData;
        }
    }
    $allEntries[] = $currentEntry;
}

echo "Total entries: " . count($allEntries) . "\n\n";

// Find cases with order voucher AND their corresponding Step 4
echo "=== ANALYZING CASES WITH ORDER VOUCHER ===\n\n";

for ($i = 0; $i < count($allEntries); $i++) {
    $entry = $allEntries[$i];
    
    // Check if this is a calculation input with order voucher
    if (strpos($entry['message'], 'CALLING CartPriceCalculator') !== false &&
        isset($entry['context']['orderVoucher']) &&
        isset($entry['context']['orderVoucher']['value']) &&
        $entry['context']['orderVoucher']['value'] > 0) {
        
        echo "--- Voucher Case Found ---\n";
        echo "Time: {$entry['timestamp']}\n";
        echo "Line: {$entry['line']}\n";
        
        $ctx = $entry['context'];
        $subtotal = 0;
        if (isset($ctx['items']) && is_array($ctx['items'])) {
            foreach ($ctx['items'] as $item) {
                $subtotal += $item['subtotal'] ?? 0;
            }
        }
        $shippingFee = $ctx['shippingFee'] ?? 0;
        $orderVoucherValue = $ctx['orderVoucher']['value'] ?? 0;
        
        echo "Input:\n";
        echo "  Subtotal: $subtotal\n";
        echo "  Shipping Fee: $shippingFee\n";
        echo "  Order Voucher: $orderVoucherValue\n";
        echo "  Expected Total: ($subtotal - 0 - $orderVoucherValue) + $shippingFee = " . (($subtotal - $orderVoucherValue) + $shippingFee) . "\n\n";
        
        // Find corresponding Step 4
        for ($j = $i + 1; $j < min($i + 10, count($allEntries)); $j++) {
            if (strpos($allEntries[$j]['message'], 'Step 4') !== false) {
                $step4 = $allEntries[$j];
                echo "Corresponding Step 4 (line {$step4['line']}):\n";
                
                if ($step4['context']) {
                    $s4ctx = $step4['context'];
                    echo "  Subtotal: " . ($s4ctx['subtotal'] ?? 'N/A') . "\n";
                    echo "  Item Discount: " . ($s4ctx['itemDiscount'] ?? 'N/A') . "\n";
                    echo "  Order Discount: " . ($s4ctx['orderDiscount'] ?? 'N/A') . "\n";
                    echo "  Shipping Fee: " . ($s4ctx['shippingFee'] ?? 'N/A') . "\n";
                    echo "  Formula: " . ($s4ctx['calculation'] ?? 'N/A') . "\n";
                    echo "  Total Final: " . ($s4ctx['totalFinal'] ?? 'N/A') . "\n";
                    
                    // Verify
                    $s4subtotal = $s4ctx['subtotal'] ?? 0;
                    $s4itemDiscount = $s4ctx['itemDiscount'] ?? 0;
                    $s4orderDiscount = $s4ctx['orderDiscount'] ?? 0;
                    $s4shippingFee = $s4ctx['shippingFee'] ?? 0;
                    $expected = ($s4subtotal - $s4itemDiscount - $s4orderDiscount) + $s4shippingFee;
                    $got = $s4ctx['totalFinal'] ?? 0;
                    
                    echo "\n  Verification:\n";
                    echo "    Expected: ($s4subtotal - $s4itemDiscount - $s4orderDiscount) + $s4shippingFee = $expected\n";
                    echo "    Got: $got\n";
                    
                    if (abs($expected - $got) > 1) {
                        echo "    ❌ MISMATCH! Difference: " . abs($expected - $got) . "\n";
                    } else {
                        echo "    ✅ Calculation correct\n";
                    }
                    
                    // Check if shipping fee matches input
                    if ($shippingFee != $s4shippingFee) {
                        echo "    ⚠️  Shipping fee mismatch! Input: $shippingFee, Step 4: $s4shippingFee\n";
                    }
                }
                echo "\n";
                break;
            }
        }
    }
}

// Check all shipping fee debug logs
echo "=== SHIPPING FEE DEBUG SUMMARY ===\n";
$shippingLogs = [];
foreach ($allEntries as $entry) {
    if (strpos($entry['message'], 'SHIPPING FEE DEBUG') !== false && isset($entry['context'])) {
        $shippingLogs[] = $entry;
    }
}

echo "Total shipping fee debug logs: " . count($shippingLogs) . "\n";

$shippingFees = [];
foreach ($shippingLogs as $log) {
    $fee = $log['context']['Final shippingFee used'] ?? 0;
    $shippingFees[$fee] = ($shippingFees[$fee] ?? 0) + 1;
}

echo "Shipping fee distribution:\n";
foreach ($shippingFees as $fee => $count) {
    echo "  $fee: $count times\n";
}

if (empty($shippingFees)) {
    echo "\n⚠️  CRITICAL: No shipping fee debug logs found!\n";
} elseif (max(array_keys($shippingFees)) == 0) {
    echo "\n⚠️  CRITICAL: All shipping fees are 0!\n";
    echo "Cannot debug the issue without shipping fee > 0.\n";
    echo "Please test with shipping fee > 0 (select address or set manually).\n";
}

// Latest log entry
echo "\n=== LATEST LOG ENTRY ===\n";
if (!empty($allEntries)) {
    $latest = end($allEntries);
    echo "Time: {$latest['timestamp']}\n";
    echo "Message: {$latest['message']}\n";
    echo "Line: {$latest['line']}\n";
    if ($latest['context']) {
        echo "Context:\n";
        echo json_encode($latest['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
}

echo "\n=== END ===\n";

