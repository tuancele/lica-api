<?php
/**
 * Analyze specific calculation case with order voucher and shipping fee
 */

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Log file not found\n";
    exit(1);
}

$content = file_get_contents($logFile);
$lines = explode("\n", $content);

echo "=== ANALYZING SPECIFIC CALCULATION CASES ===\n\n";

// Find logs with order voucher
$casesWithVoucher = [];
$casesWithShipping = [];

foreach ($lines as $lineNum => $line) {
    // Case 1: Has order voucher
    if (strpos($line, 'CHECKOUT_CALCULATION') !== false && strpos($line, 'orderVoucher') !== false) {
        // Try to extract JSON
        $jsonStart = strpos($line, '{');
        if ($jsonStart !== false) {
            $jsonString = substr($line, $jsonStart);
            // Try to get complete JSON (may span multiple lines)
            for ($i = $lineNum + 1; $i < min($lineNum + 10, count($lines)); $i++) {
                $jsonString .= "\n" . trim($lines[$i]);
                $jsonData = json_decode($jsonString, true);
                if ($jsonData !== null) {
                    if (isset($jsonData['orderVoucher']) && isset($jsonData['orderVoucher']['value']) && $jsonData['orderVoucher']['value'] > 0) {
                        $casesWithVoucher[] = [
                            'line' => $lineNum + 1,
                            'data' => $jsonData,
                            'timestamp' => preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $m) ? $m[1] : 'N/A'
                        ];
                    }
                    break;
                }
            }
        }
    }
    
    // Case 2: Has shipping fee > 0
    if (strpos($line, 'SHIPPING FEE DEBUG') !== false) {
        $jsonStart = strpos($line, '{');
        if ($jsonStart !== false) {
            $jsonString = substr($line, $jsonStart);
            for ($i = $lineNum + 1; $i < min($lineNum + 10, count($lines)); $i++) {
                $jsonString .= "\n" . trim($lines[$i]);
                $jsonData = json_decode($jsonString, true);
                if ($jsonData !== null) {
                    $shippingFee = $jsonData['Final shippingFee used'] ?? 0;
                    if ($shippingFee > 0) {
                        $casesWithShipping[] = [
                            'line' => $lineNum + 1,
                            'data' => $jsonData,
                            'timestamp' => preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $m) ? $m[1] : 'N/A'
                        ];
                    }
                    break;
                }
            }
        }
    }
}

echo "=== CASES WITH ORDER VOUCHER ===\n";
if (empty($casesWithVoucher)) {
    echo "No cases found with order voucher\n\n";
} else {
    echo "Found " . count($casesWithVoucher) . " cases:\n\n";
    foreach ($casesWithVoucher as $index => $case) {
        echo "Case #" . ($index + 1) . " - Line {$case['line']} - {$case['timestamp']}\n";
        $data = $case['data'];
        echo "  Items count: " . ($data['itemsCount'] ?? 'N/A') . "\n";
        if (isset($data['items']) && is_array($data['items'])) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += $item['subtotal'] ?? 0;
            }
            echo "  Calculated subtotal: $subtotal\n";
        }
        echo "  Shipping fee: " . ($data['shippingFee'] ?? 0) . "\n";
        if (isset($data['orderVoucher'])) {
            echo "  Order voucher value: " . ($data['orderVoucher']['value'] ?? 'N/A') . "\n";
        }
        echo "\n";
    }
}

echo "=== CASES WITH SHIPPING FEE > 0 ===\n";
if (empty($casesWithShipping)) {
    echo "⚠️  NO CASES FOUND WITH SHIPPING FEE > 0\n";
    echo "This means the bug cannot be reproduced from current logs.\n";
    echo "Please test again with shipping fee > 0 (e.g., select address).\n\n";
} else {
    echo "Found " . count($casesWithShipping) . " cases:\n\n";
    foreach ($casesWithShipping as $index => $case) {
        echo "Case #" . ($index + 1) . " - Line {$case['line']} - {$case['timestamp']}\n";
        $data = $case['data'];
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    }
}

// Find corresponding Step 4 calculation for cases with voucher
if (!empty($casesWithVoucher)) {
    echo "=== CORRESPONDING STEP 4 CALCULATIONS ===\n";
    foreach ($casesWithVoucher as $voucherCase) {
        $voucherLine = $voucherCase['line'];
        // Look for Step 4 calculation after this line
        for ($i = $voucherLine; $i < min($voucherLine + 50, count($lines)); $i++) {
            if (strpos($lines[$i], 'Step 4') !== false) {
                $jsonStart = strpos($lines[$i], '{');
                if ($jsonStart !== false) {
                    $jsonString = substr($lines[$i], $jsonStart);
                    for ($j = $i + 1; $j < min($i + 10, count($lines)); $j++) {
                        $jsonString .= "\n" . trim($lines[$j]);
                        $jsonData = json_decode($jsonString, true);
                        if ($jsonData !== null) {
                            echo "Step 4 for voucher case (line $voucherLine):\n";
                            echo json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                            
                            // Verify calculation
                            $subtotal = $jsonData['subtotal'] ?? 0;
                            $itemDiscount = $jsonData['itemDiscount'] ?? 0;
                            $orderDiscount = $jsonData['orderDiscount'] ?? 0;
                            $shippingFee = $jsonData['shippingFee'] ?? 0;
                            $expected = ($subtotal - $itemDiscount - $orderDiscount) + $shippingFee;
                            $got = $jsonData['totalFinal'] ?? 0;
                            
                            echo "\nVerification:\n";
                            echo "  Expected: ($subtotal - $itemDiscount - $orderDiscount) + $shippingFee = $expected\n";
                            echo "  Got: $got\n";
                            
                            if (abs($expected - $got) > 1) {
                                echo "  ❌ MISMATCH! Difference: " . abs($expected - $got) . "\n";
                            } else {
                                echo "  ✅ Correct\n";
                            }
                            echo "\n";
                            break 2;
                        }
                    }
                }
            }
        }
    }
}

echo "=== END ===\n";

