<?php
/**
 * Analyze real-time calculation issues - race conditions and data timing
 */

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Log file not found\n";
    exit(1);
}

$content = file_get_contents($logFile);
$lines = explode("\n", $content);

echo "=== ANALYZING REAL-TIME CALCULATION ISSUES ===\n\n";

// Parse all log entries
$allEntries = [];
$currentEntry = null;
$jsonBuffer = '';
$inJson = false;
$braceCount = 0;

foreach ($lines as $lineNum => $line) {
    if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?\[CHECKOUT_CALCULATION\] (.*?)(?:\s*\|\s*Context:)?(.*)/', $line, $matches)) {
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

// Find cases with order voucher 50000 AND shipping fee > 0
echo "=== FINDING CASES WITH ORDER VOUCHER 50,000 + SHIPPING FEE > 0 ===\n\n";

$problematicCases = [];

for ($i = 0; $i < count($allEntries); $i++) {
    $entry = $allEntries[$i];
    
    // Check if this is a calculation input with order voucher 50000
    if (strpos($entry['message'], 'CALLING CartPriceCalculator') !== false &&
        isset($entry['context']['orderVoucher']) &&
        isset($entry['context']['orderVoucher']['value']) &&
        $entry['context']['orderVoucher']['value'] == 50000 &&
        isset($entry['context']['shippingFee']) &&
        $entry['context']['shippingFee'] > 0) {
        
        $ctx = $entry['context'];
        $subtotal = 0;
        if (isset($ctx['items']) && is_array($ctx['items'])) {
            foreach ($ctx['items'] as $item) {
                $subtotal += $item['subtotal'] ?? 0;
            }
        }
        $shippingFee = $ctx['shippingFee'] ?? 0;
        $orderVoucherValue = $ctx['orderVoucher']['value'] ?? 0;
        
        // Find corresponding Step 4
        $step4Found = false;
        for ($j = $i + 1; $j < min($i + 10, count($allEntries)); $j++) {
            if (strpos($allEntries[$j]['message'], 'Step 4') !== false) {
                $step4 = $allEntries[$j];
                if ($step4['context']) {
                    $s4ctx = $step4['context'];
                    $s4subtotal = $s4ctx['subtotal'] ?? 0;
                    $s4itemDiscount = $s4ctx['itemDiscount'] ?? 0;
                    $s4orderDiscount = $s4ctx['orderDiscount'] ?? 0;
                    $s4shippingFee = $s4ctx['shippingFee'] ?? 0;
                    $s4totalFinal = $s4ctx['totalFinal'] ?? 0;
                    
                    $expected = ($s4subtotal - $s4itemDiscount - $s4orderDiscount) + $s4shippingFee;
                    
                    if (abs($expected - $s4totalFinal) > 1) {
                        $problematicCases[] = [
                            'input' => $entry,
                            'step4' => $step4,
                            'subtotal' => $s4subtotal,
                            'orderDiscount' => $s4orderDiscount,
                            'shippingFee' => $s4shippingFee,
                            'expected' => $expected,
                            'got' => $s4totalFinal,
                            'difference' => abs($expected - $s4totalFinal)
                        ];
                    }
                }
                $step4Found = true;
                break;
            }
        }
        
        if (!$step4Found) {
            echo "⚠️  Case found but no Step 4:\n";
            echo "  Time: {$entry['timestamp']}\n";
            echo "  Subtotal: $subtotal\n";
            echo "  Shipping Fee: $shippingFee\n";
            echo "  Order Voucher: $orderVoucherValue\n";
            echo "\n";
        }
    }
}

if (empty($problematicCases)) {
    echo "✅ No problematic cases found with order voucher 50,000 + shipping fee > 0\n\n";
} else {
    echo "Found " . count($problematicCases) . " problematic cases:\n\n";
    foreach ($problematicCases as $index => $case) {
        echo "--- Case #" . ($index + 1) . " ---\n";
        echo "Time: {$case['input']['timestamp']}\n";
        echo "Subtotal: " . number_format($case['subtotal']) . "đ\n";
        echo "Order Discount: " . number_format($case['orderDiscount']) . "đ\n";
        echo "Shipping Fee: " . number_format($case['shippingFee']) . "đ\n";
        echo "Expected: " . number_format($case['expected']) . "đ\n";
        echo "Got: " . number_format($case['got']) . "đ\n";
        echo "Difference: " . number_format($case['difference']) . "đ\n";
        echo "\n";
    }
}

// Find cases with subtotal around 3,500,000
echo "=== FINDING CASES WITH SUBTOTAL ~3,500,000 ===\n\n";

$subtotal3500000Cases = [];

foreach ($allEntries as $entry) {
    if (strpos($entry['message'], 'Step 4') !== false && 
        isset($entry['context']['subtotal']) &&
        $entry['context']['subtotal'] >= 3400000 &&
        $entry['context']['subtotal'] <= 3600000) {
        
        $ctx = $entry['context'];
        $subtotal = $ctx['subtotal'] ?? 0;
        $orderDiscount = $ctx['orderDiscount'] ?? 0;
        $shippingFee = $ctx['shippingFee'] ?? 0;
        $totalFinal = $ctx['totalFinal'] ?? 0;
        
        $expected = ($subtotal - $ctx['itemDiscount'] - $orderDiscount) + $shippingFee;
        
        if (abs($expected - $totalFinal) > 1) {
            $subtotal3500000Cases[] = [
                'entry' => $entry,
                'subtotal' => $subtotal,
                'orderDiscount' => $orderDiscount,
                'shippingFee' => $shippingFee,
                'expected' => $expected,
                'got' => $totalFinal,
                'difference' => abs($expected - $totalFinal)
            ];
        }
    }
}

if (empty($subtotal3500000Cases)) {
    echo "✅ No problematic cases found with subtotal ~3,500,000\n\n";
} else {
    echo "Found " . count($subtotal3500000Cases) . " cases:\n\n";
    foreach ($subtotal3500000Cases as $index => $case) {
        echo "--- Case #" . ($index + 1) . " ---\n";
        echo "Time: {$case['entry']['timestamp']}\n";
        echo "Subtotal: " . number_format($case['subtotal']) . "đ\n";
        echo "Order Discount: " . number_format($case['orderDiscount']) . "đ\n";
        echo "Shipping Fee: " . number_format($case['shippingFee']) . "đ\n";
        echo "Expected: " . number_format($case['expected']) . "đ\n";
        echo "Got: " . number_format($case['got']) . "đ\n";
        echo "Difference: " . number_format($case['difference']) . "đ\n";
        echo "Formula: " . ($case['entry']['context']['calculation'] ?? 'N/A') . "\n";
        echo "\n";
    }
}

// Check for timing issues - multiple calculations in short time
echo "=== CHECKING FOR TIMING/RACE CONDITION ISSUES ===\n\n";

$recentCalculations = [];
foreach ($allEntries as $entry) {
    if (strpos($entry['message'], 'CALLING CartPriceCalculator') !== false && 
        isset($entry['context']['shippingFee']) &&
        $entry['context']['shippingFee'] > 0) {
        $recentCalculations[] = $entry;
    }
}

if (count($recentCalculations) > 0) {
    echo "Found " . count($recentCalculations) . " calculations with shipping fee > 0\n";
    echo "Latest 5:\n\n";
    
    $latest = array_slice($recentCalculations, -5);
    foreach ($latest as $calc) {
        $ctx = $calc['context'];
        $subtotal = 0;
        if (isset($ctx['items']) && is_array($ctx['items'])) {
            foreach ($ctx['items'] as $item) {
                $subtotal += $item['subtotal'] ?? 0;
            }
        }
        echo "Time: {$calc['timestamp']}\n";
        echo "  Subtotal: " . number_format($subtotal) . "đ\n";
        echo "  Shipping Fee: " . number_format($ctx['shippingFee'] ?? 0) . "đ\n";
        echo "  Order Voucher: " . (isset($ctx['orderVoucher']['value']) ? number_format($ctx['orderVoucher']['value']) . "đ" : "None") . "\n";
        echo "\n";
    }
}

echo "=== END ===\n";

