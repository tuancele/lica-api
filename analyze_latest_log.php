<?php
/**
 * Analyze the latest checkout calculation log entry
 */

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Log file not found\n";
    exit(1);
}

// Read last 500 lines
$lines = file($logFile);
$totalLines = count($lines);
$startLine = max(0, $totalLines - 500);
$relevantLines = array_slice($lines, $startLine);

echo "=== ANALYZING LATEST CHECKOUT CALCULATION ===\n\n";

// Find the most recent complete log entry
$latestShippingFee = null;
$latestCalculation = null;
$latestStep4 = null;
$latestMismatch = null;

// Build complete log entries (JSON may span multiple lines)
$currentEntry = '';
$currentType = '';

foreach ($relevantLines as $lineNum => $line) {
    $line = trim($line);
    
    if (strpos($line, 'CHECKOUT_CALCULATION') !== false) {
        // Save previous entry if complete
        if ($currentEntry && $currentType) {
            $entry = json_decode($currentEntry, true);
            if ($entry !== null) {
                switch ($currentType) {
                    case 'SHIPPING':
                        $latestShippingFee = $entry;
                        break;
                    case 'CALCULATION':
                        $latestCalculation = $entry;
                        break;
                    case 'STEP4':
                        $latestStep4 = $entry;
                        break;
                    case 'MISMATCH':
                        $latestMismatch = $entry;
                        break;
                }
            }
        }
        
        // Start new entry
        $currentEntry = $line;
        if (strpos($line, 'SHIPPING FEE DEBUG') !== false) {
            $currentType = 'SHIPPING';
        } elseif (strpos($line, 'CALLING CartPriceCalculator') !== false) {
            $currentType = 'CALCULATION';
        } elseif (strpos($line, 'Step 4') !== false) {
            $currentType = 'STEP4';
        } elseif (strpos($line, 'TOTAL MISMATCH') !== false) {
            $currentType = 'MISMATCH';
        } else {
            $currentType = '';
        }
    } elseif ($currentEntry && (strpos($line, '{') !== false || strpos($line, '}') !== false || strpos($line, '"') !== false)) {
        // Continue building JSON
        $currentEntry .= "\n" . $line;
    }
}

// Display analysis
echo "1. LATEST SHIPPING FEE DEBUG:\n";
if ($latestShippingFee) {
    echo json_encode($latestShippingFee, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Check for parse errors
    $raw = $latestShippingFee["input[name=\"feeShip\"] raw"] ?? '';
    $parsed = $latestShippingFee["input[name=\"feeShip\"] parsed"] ?? 0;
    $final = $latestShippingFee['Final shippingFee used'] ?? 0;
    
    echo "Analysis:\n";
    echo "  Raw value: '$raw'\n";
    echo "  Parsed value: $parsed\n";
    echo "  Final used: $final\n";
    
    if (is_string($raw) && (strpos($raw, ',') !== false || strpos($raw, '.') !== false)) {
        $expectedParsed = (int) preg_replace('/[^\d]/', '', $raw);
        if ($parsed != $expectedParsed) {
            echo "  ⚠️  PARSE ERROR! Expected: $expectedParsed, Got: $parsed\n";
        }
    }
} else {
    echo "  Not found\n";
}

echo "\n2. LATEST CALCULATION INPUT:\n";
if ($latestCalculation) {
    echo json_encode($latestCalculation, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Calculate subtotal
    $subtotal = 0;
    if (isset($latestCalculation['items']) && is_array($latestCalculation['items'])) {
        foreach ($latestCalculation['items'] as $item) {
            $subtotal += $item['subtotal'] ?? 0;
        }
    }
    
    echo "Analysis:\n";
    echo "  Items count: " . ($latestCalculation['itemsCount'] ?? 'N/A') . "\n";
    echo "  Calculated subtotal: $subtotal\n";
    echo "  Shipping fee: " . ($latestCalculation['shippingFee'] ?? 'N/A') . "\n";
    if (isset($latestCalculation['orderVoucher']) && $latestCalculation['orderVoucher']) {
        echo "  Order voucher: " . ($latestCalculation['orderVoucher']['value'] ?? 'N/A') . "\n";
    }
} else {
    echo "  Not found\n";
}

echo "\n3. LATEST STEP 4 CALCULATION:\n";
if ($latestStep4) {
    echo json_encode($latestStep4, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    echo "Analysis:\n";
    echo "  Formula: " . ($latestStep4['calculation'] ?? 'N/A') . "\n";
    
    $subtotal = $latestStep4['subtotal'] ?? 0;
    $itemDiscount = $latestStep4['itemDiscount'] ?? 0;
    $orderDiscount = $latestStep4['orderDiscount'] ?? 0;
    $shippingFee = $latestStep4['shippingFee'] ?? 0;
    $expected = ($subtotal - $itemDiscount - $orderDiscount) + $shippingFee;
    $got = $latestStep4['totalFinal'] ?? 0;
    
    echo "  Expected: ($subtotal - $itemDiscount - $orderDiscount) + $shippingFee = $expected\n";
    echo "  Got: $got\n";
    
    if (abs($expected - $got) > 1) {
        echo "  ❌ MISMATCH! Difference: " . abs($expected - $got) . "\n";
    } else {
        echo "  ✅ Calculation correct\n";
    }
} else {
    echo "  Not found\n";
}

echo "\n4. LATEST TOTAL MISMATCH ERROR:\n";
if ($latestMismatch) {
    echo json_encode($latestMismatch, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "  ✅ No mismatch errors found\n";
}

echo "\n=== END ===\n";

