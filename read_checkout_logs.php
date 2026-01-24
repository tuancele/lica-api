<?php
/**
 * Script to read and analyze checkout calculation logs from Laravel log file
 * 
 * Usage: php read_checkout_logs.php [--tail=N] [--grep=pattern]
 */

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Log file not found: $logFile\n";
    exit(1);
}

// Parse command line arguments
$options = getopt('', ['tail:', 'grep:']);
$tail = isset($options['tail']) ? (int)$options['tail'] : 100;
$grep = isset($options['grep']) ? $options['grep'] : 'CHECKOUT_CALCULATION';

// Read log file
$lines = file($logFile);
$totalLines = count($lines);

// Get last N lines
$startLine = max(0, $totalLines - $tail);
$relevantLines = array_slice($lines, $startLine);

echo "=== CHECKOUT CALCULATION LOGS ===\n";
echo "Reading from line $startLine to $totalLines (total: " . count($relevantLines) . " lines)\n";
echo "Filtering by: $grep\n\n";

$foundLogs = [];
$currentLog = null;

foreach ($relevantLines as $lineNum => $line) {
    // Check if line contains our log marker
    if (strpos($line, $grep) !== false) {
        // Extract timestamp and message
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?\[CHECKOUT_CALCULATION\] (.*)/', $line, $matches)) {
            $timestamp = $matches[1];
            $message = $matches[2];
            
            // Try to extract JSON context
            $jsonStart = strpos($line, 'Context:');
            if ($jsonStart !== false) {
                $jsonPart = substr($line, $jsonStart + 8);
                $jsonData = json_decode(trim($jsonPart), true);
                
                $foundLogs[] = [
                    'timestamp' => $timestamp,
                    'message' => $message,
                    'context' => $jsonData,
                    'line' => $startLine + $lineNum + 1
                ];
            } else {
                $foundLogs[] = [
                    'timestamp' => $timestamp,
                    'message' => $message,
                    'context' => null,
                    'line' => $startLine + $lineNum + 1
                ];
            }
        }
    }
}

// Display logs
if (empty($foundLogs)) {
    echo "No logs found matching pattern: $grep\n";
    echo "Make sure you have performed actions on checkout page that trigger calculations.\n";
} else {
    echo "Found " . count($foundLogs) . " log entries:\n\n";
    
    foreach ($foundLogs as $index => $log) {
        echo "--- Log #" . ($index + 1) . " ---\n";
        echo "Time: {$log['timestamp']}\n";
        echo "Message: {$log['message']}\n";
        echo "Line: {$log['line']}\n";
        
        if ($log['context']) {
            echo "Context:\n";
            echo json_encode($log['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        echo "\n";
    }
    
    // Summary analysis
    echo "\n=== SUMMARY ANALYSIS ===\n";
    
    $shippingFeeLogs = array_filter($foundLogs, function($log) {
        return strpos($log['message'], 'SHIPPING FEE DEBUG') !== false;
    });
    
    $totalMismatchLogs = array_filter($foundLogs, function($log) {
        return strpos($log['message'], 'TOTAL MISMATCH') !== false;
    });
    
    $calculationLogs = array_filter($foundLogs, function($log) {
        return strpos($log['message'], 'CartPriceCalculator') !== false;
    });
    
    echo "Shipping Fee Debug Logs: " . count($shippingFeeLogs) . "\n";
    echo "Total Mismatch Errors: " . count($totalMismatchLogs) . "\n";
    echo "Calculation Logs: " . count($calculationLogs) . "\n";
    
    if (!empty($totalMismatchLogs)) {
        echo "\n⚠️  ERRORS FOUND:\n";
        foreach ($totalMismatchLogs as $errorLog) {
            echo "  - {$errorLog['timestamp']}: {$errorLog['message']}\n";
            if ($errorLog['context'] && isset($errorLog['context']['BREAKDOWN'])) {
                $bd = $errorLog['context']['BREAKDOWN'];
                echo "    Missing: " . ($bd['Missing'] ?? 'N/A') . "\n";
                echo "    Expected: " . ($bd['Expected'] ?? 'N/A') . "\n";
                echo "    Got: " . ($bd['Got'] ?? 'N/A') . "\n";
            }
        }
    }
}

echo "\n=== END ===\n";

