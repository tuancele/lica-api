<?php
/**
 * Analyze specific case: subtotal 1,400,000 + shipping 28,000 = should be 1,428,000 but got 1,228,000
 */

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Log file not found\n";
    exit(1);
}

$content = file_get_contents($logFile);
$lines = explode("\n", $content);

echo "=== ANALYZING CASE: 1,400,000 + 28,000 = 1,228,000 (WRONG!) ===\n\n";

// Find all log entries
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

// Find entries with shipping fee 28000
echo "=== FINDING ENTRIES WITH SHIPPING FEE 28,000 ===\n";
$shipping28000Entries = [];
foreach ($allEntries as $entry) {
    if (isset($entry['context']['Final shippingFee used']) && 
        $entry['context']['Final shippingFee used'] == 28000) {
        $shipping28000Entries[] = $entry;
    }
}

echo "Found " . count($shipping28000Entries) . " entries with shipping fee 28,000\n\n";

// For each shipping fee entry, find corresponding Step 4
foreach ($shipping28000Entries as $shippingEntry) {
    echo "--- Shipping Fee Entry (Line {$shippingEntry['line']}) ---\n";
    echo "Time: {$shippingEntry['timestamp']}\n";
    if ($shippingEntry['context']) {
        $ctx = $shippingEntry['context'];
        echo "Input raw: " . ($ctx["input[name=\"feeShip\"] raw"] ?? 'N/A') . "\n";
        echo "Input parsed: " . ($ctx["input[name=\"feeShip\"] parsed"] ?? 'N/A') . "\n";
        echo "Final used: " . ($ctx['Final shippingFee used'] ?? 'N/A') . "\n";
    }
    echo "\n";
    
    // Find Step 4 after this entry
    $foundStep4 = false;
    for ($i = 0; $i < count($allEntries); $i++) {
        if ($allEntries[$i]['line'] > $shippingEntry['line'] && 
            strpos($allEntries[$i]['message'], 'Step 4') !== false) {
            
            $step4 = $allEntries[$i];
            echo "  → Step 4 (Line {$step4['line']}):\n";
            
            if ($step4['context']) {
                $s4ctx = $step4['context'];
                $subtotal = $s4ctx['subtotal'] ?? 0;
                $itemDiscount = $s4ctx['itemDiscount'] ?? 0;
                $orderDiscount = $s4ctx['orderDiscount'] ?? 0;
                $shippingFee = $s4ctx['shippingFee'] ?? 0;
                $totalFinal = $s4ctx['totalFinal'] ?? 0;
                
                echo "    Subtotal: $subtotal\n";
                echo "    Item Discount: $itemDiscount\n";
                echo "    Order Discount: $orderDiscount\n";
                echo "    Shipping Fee: $shippingFee\n";
                echo "    Total Final: $totalFinal\n";
                echo "    Formula: " . ($s4ctx['calculation'] ?? 'N/A') . "\n";
                
                // Verify
                $expected = ($subtotal - $itemDiscount - $orderDiscount) + $shippingFee;
                echo "\n    Verification:\n";
                echo "      Expected: ($subtotal - $itemDiscount - $orderDiscount) + $shippingFee = $expected\n";
                echo "      Got: $totalFinal\n";
                
                if (abs($expected - $totalFinal) > 1) {
                    echo "      ❌ MISMATCH! Difference: " . abs($expected - $totalFinal) . "\n";
                    
                    // Check if this matches the user's case
                    if ($subtotal == 1400000 && $shippingFee == 28000) {
                        echo "\n      🎯 THIS IS THE USER'S CASE!\n";
                        echo "      Expected: 1,400,000 + 28,000 = 1,428,000\n";
                        echo "      Got: $totalFinal\n";
                        echo "      Missing: " . (1428000 - $totalFinal) . "\n";
                    }
                } else {
                    echo "      ✅ Calculation correct\n";
                }
            }
            
            $foundStep4 = true;
            break;
        }
    }
    
    if (!$foundStep4) {
        echo "  ⚠️  No Step 4 found after this entry\n";
    }
    echo "\n";
}

// Also find all Step 4 entries with subtotal 1400000
echo "=== FINDING STEP 4 ENTRIES WITH SUBTOTAL 1,400,000 ===\n";
$step4_1400000 = [];
foreach ($allEntries as $entry) {
    if (strpos($entry['message'], 'Step 4') !== false && 
        isset($entry['context']['subtotal']) && 
        $entry['context']['subtotal'] == 1400000) {
        $step4_1400000[] = $entry;
    }
}

echo "Found " . count($step4_1400000) . " Step 4 entries with subtotal 1,400,000\n\n";

foreach ($step4_1400000 as $step4) {
    echo "--- Step 4 (Line {$step4['line']}) ---\n";
    echo "Time: {$step4['timestamp']}\n";
    if ($step4['context']) {
        $ctx = $step4['context'];
        echo "Subtotal: " . ($ctx['subtotal'] ?? 'N/A') . "\n";
        echo "Shipping Fee: " . ($ctx['shippingFee'] ?? 'N/A') . "\n";
        echo "Total Final: " . ($ctx['totalFinal'] ?? 'N/A') . "\n";
        echo "Formula: " . ($ctx['calculation'] ?? 'N/A') . "\n";
        
        $shippingFee = $ctx['shippingFee'] ?? 0;
        if ($shippingFee > 0) {
            echo "\n⚠️  This has shipping fee > 0!\n";
        }
    }
    echo "\n";
}

echo "=== END ===\n";

