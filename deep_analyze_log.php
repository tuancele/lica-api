<?php
/**
 * Deep analyze Laravel log - extract complete JSON from multi-line logs
 */

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Log file not found\n";
    exit(1);
}

$content = file_get_contents($logFile);
$lines = explode("\n", $content);

echo "=== DEEP ANALYSIS OF LARAVEL LOG ===\n";
echo "Total lines: " . count($lines) . "\n\n";

// Find all complete log entries
$logEntries = [];
$currentEntry = null;
$jsonBuffer = '';
$inJson = false;
$braceCount = 0;

foreach ($lines as $lineNum => $line) {
    // Check if this is a new log entry
    if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?\[CHECKOUT_CALCULATION\] (.*?)(?:\s*\|\s*Context:)?(.*)/', $line, $matches)) {
        // Save previous entry
        if ($currentEntry !== null && !empty($jsonBuffer)) {
            $jsonData = json_decode($jsonBuffer, true);
            if ($jsonData !== null) {
                $currentEntry['context'] = $jsonData;
            }
            $logEntries[] = $currentEntry;
        }
        
        // Start new entry
        $currentEntry = [
            'timestamp' => $matches[1],
            'message' => trim($matches[2]),
            'line' => $lineNum + 1
        ];
        
        // Check if JSON starts on this line
        $jsonStart = strpos($line, '{');
        if ($jsonStart !== false) {
            $jsonBuffer = substr($line, $jsonStart);
            $inJson = true;
            $braceCount = substr_count($jsonBuffer, '{') - substr_count($jsonBuffer, '}');
        } else {
            $jsonBuffer = '';
            $inJson = false;
            $braceCount = 0;
        }
    } elseif ($inJson && $currentEntry !== null) {
        // Continue collecting JSON
        $jsonBuffer .= "\n" . trim($line);
        $braceCount += substr_count($line, '{') - substr_count($line, '}');
        
        // Check if JSON is complete
        if ($braceCount === 0 && strpos($line, '}') !== false) {
            $inJson = false;
        }
    }
}

// Save last entry
if ($currentEntry !== null) {
    if (!empty($jsonBuffer)) {
        $jsonData = json_decode($jsonBuffer, true);
        if ($jsonData !== null) {
            $currentEntry['context'] = $jsonData;
        }
    }
    $logEntries[] = $currentEntry;
}

echo "Found " . count($logEntries) . " complete log entries\n\n";

// Analyze cases with order voucher
echo "=== CASES WITH ORDER VOUCHER ===\n";
$voucherCases = [];
foreach ($logEntries as $entry) {
    if (isset($entry['context']['orderVoucher']) && 
        isset($entry['context']['orderVoucher']['value']) && 
        $entry['context']['orderVoucher']['value'] > 0) {
        $voucherCases[] = $entry;
    }
}

if (empty($voucherCases)) {
    echo "No cases found\n\n";
} else {
    echo "Found " . count($voucherCases) . " cases:\n\n";
    foreach ($voucherCases as $index => $case) {
        echo "--- Case #" . ($index + 1) . " ---\n";
        echo "Time: {$case['timestamp']}\n";
        echo "Message: {$case['message']}\n";
        echo "Line: {$case['line']}\n";
        if ($case['context']) {
            $ctx = $case['context'];
            echo "Context:\n";
            echo json_encode($ctx, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            
            if (isset($ctx['items']) && is_array($ctx['items'])) {
                $subtotal = 0;
                foreach ($ctx['items'] as $item) {
                    $subtotal += $item['subtotal'] ?? 0;
                }
                echo "\nAnalysis:\n";
                echo "  Items count: " . ($ctx['itemsCount'] ?? 'N/A') . "\n";
                echo "  Calculated subtotal: $subtotal\n";
                echo "  Shipping fee: " . ($ctx['shippingFee'] ?? 0) . "\n";
                if (isset($ctx['orderVoucher'])) {
                    echo "  Order voucher: " . ($ctx['orderVoucher']['value'] ?? 'N/A') . "\n";
                }
            }
        }
        echo "\n";
    }
}

// Find corresponding Step 4 for voucher cases
if (!empty($voucherCases)) {
    echo "=== CORRESPONDING STEP 4 CALCULATIONS ===\n";
    foreach ($voucherCases as $vCase) {
        $vLine = $vCase['line'];
        // Look for Step 4 after this entry
        for ($i = 0; $i < count($logEntries); $i++) {
            if ($logEntries[$i]['line'] > $vLine && 
                strpos($logEntries[$i]['message'], 'Step 4') !== false) {
                echo "Step 4 for voucher case (line {$vLine}):\n";
                $step4 = $logEntries[$i];
                if ($step4['context']) {
                    $ctx = $step4['context'];
                    echo json_encode($ctx, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                    
                    // Verify
                    $subtotal = $ctx['subtotal'] ?? 0;
                    $itemDiscount = $ctx['itemDiscount'] ?? 0;
                    $orderDiscount = $ctx['orderDiscount'] ?? 0;
                    $shippingFee = $ctx['shippingFee'] ?? 0;
                    $expected = ($subtotal - $itemDiscount - $orderDiscount) + $shippingFee;
                    $got = $ctx['totalFinal'] ?? 0;
                    
                    echo "\nVerification:\n";
                    echo "  Formula: ($subtotal - $itemDiscount - $orderDiscount) + $shippingFee\n";
                    echo "  Expected: $expected\n";
                    echo "  Got: $got\n";
                    
                    if (abs($expected - $got) > 1) {
                        echo "  ❌ MISMATCH! Difference: " . abs($expected - $got) . "\n";
                    } else {
                        echo "  ✅ Correct\n";
                    }
                }
                echo "\n";
                break;
            }
        }
    }
}

// Analyze cases with shipping fee > 0
echo "=== CASES WITH SHIPPING FEE > 0 ===\n";
$shippingCases = [];
foreach ($logEntries as $entry) {
    if (strpos($entry['message'], 'SHIPPING FEE DEBUG') !== false && 
        isset($entry['context']['Final shippingFee used']) && 
        $entry['context']['Final shippingFee used'] > 0) {
        $shippingCases[] = $entry;
    }
}

if (empty($shippingCases)) {
    echo "⚠️  NO CASES FOUND WITH SHIPPING FEE > 0\n";
    echo "All shipping fees in logs are 0.\n";
    echo "To debug the issue, please:\n";
    echo "1. Select an address to get shipping fee\n";
    echo "2. Or manually set shipping fee in input\n";
    echo "3. Then check logs again\n\n";
} else {
    echo "Found " . count($shippingCases) . " cases:\n\n";
    foreach ($shippingCases as $index => $case) {
        echo "--- Case #" . ($index + 1) . " ---\n";
        echo "Time: {$case['timestamp']}\n";
        echo "Line: {$case['line']}\n";
        if ($case['context']) {
            $ctx = $case['context'];
            echo json_encode($ctx, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            
            // Check for parse errors
            $raw = $ctx["input[name=\"feeShip\"] raw"] ?? '';
            $parsed = $ctx["input[name=\"feeShip\"] parsed"] ?? 0;
            $final = $ctx['Final shippingFee used'] ?? 0;
            
            echo "\nParse Analysis:\n";
            echo "  Raw: '$raw'\n";
            echo "  Parsed: $parsed\n";
            echo "  Final: $final\n";
            
            if (is_string($raw) && (strpos($raw, ',') !== false || strpos($raw, '.') !== false)) {
                $expected = (int) preg_replace('/[^\d]/', '', $raw);
                if ($parsed != $expected) {
                    echo "  ⚠️  PARSE ERROR! Expected: $expected, Got: $parsed\n";
                }
            }
        }
        echo "\n";
    }
}

// Summary
echo "=== SUMMARY ===\n";
echo "Total log entries: " . count($logEntries) . "\n";
echo "Cases with order voucher: " . count($voucherCases) . "\n";
echo "Cases with shipping fee > 0: " . count($shippingCases) . "\n";

// Check for TOTAL MISMATCH
$mismatchCases = [];
foreach ($logEntries as $entry) {
    if (strpos($entry['message'], 'TOTAL MISMATCH') !== false) {
        $mismatchCases[] = $entry;
    }
}

echo "Total mismatch errors: " . count($mismatchCases) . "\n";

if (!empty($mismatchCases)) {
    echo "\n=== ❌ TOTAL MISMATCH ERRORS ===\n";
    foreach ($mismatchCases as $error) {
        echo "Time: {$error['timestamp']}\n";
        if ($error['context']) {
            echo json_encode($error['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
        echo "\n";
    }
} else {
    echo "\n✅ No total mismatch errors found\n";
}

echo "\n=== END ===\n";

