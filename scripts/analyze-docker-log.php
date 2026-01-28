<?php

/**
 * Analyze Docker build log from C:\laragon\www\lica\docker-build-log
 */

echo "========================================\n";
echo "Docker Build Log Analyzer\n";
echo "========================================\n\n";

$logDir = __DIR__ . '/../docker-build-log';
$logFile = null;

// Find log file
if (is_dir($logDir)) {
    $files = glob($logDir . '/*.log');
    if (!empty($files)) {
        $logFile = $files[0];
    } else {
        // Check subdirectories
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($logDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'log') {
                $logFile = $file->getPathname();
                break;
            }
        }
    }
}

// Also check ZIP file
if (!$logFile && file_exists(__DIR__ . '/../docker-build-log.zip')) {
    echo "Found ZIP file, extracting...\n";
    $zip = new ZipArchive();
    if ($zip->open(__DIR__ . '/../docker-build-log.zip') === TRUE) {
        $extractDir = __DIR__ . '/../docker-build-log-extracted';
        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0755, true);
        }
        $zip->extractTo($extractDir);
        $zip->close();
        
        $files = glob($extractDir . '/**/*.log');
        if (!empty($files)) {
            $logFile = $files[0];
        }
    }
}

if (!$logFile) {
    die("❌ No log file found in docker-build-log directory or ZIP\n");
}

echo "✅ Found log file: {$logFile}\n";
echo "Size: " . filesize($logFile) . " bytes\n\n";

// Read log
$content = file_get_contents($logFile);
$lines = explode("\n", $content);

echo "========================================\n";
echo "Log Statistics\n";
echo "========================================\n";
echo "Total lines: " . count($lines) . "\n";
echo "File size: " . strlen($content) . " bytes\n\n";

// Analyze errors
$errors = [];
$warnings = [];
$buildSteps = [];
$currentStep = '';

foreach ($lines as $lineNum => $line) {
    $lineTrimmed = trim($line);
    if (empty($lineTrimmed)) continue;
    
    // Detect build steps
    if (preg_match('/^Step (\d+)\/(\d+)/', $line, $matches)) {
        $currentStep = "Step {$matches[1]}/{$matches[2]}";
        $buildSteps[] = [
            'step' => $currentStep,
            'line' => $lineNum + 1,
            'content' => $lineTrimmed,
        ];
    }
    
    // Detect errors
    if (preg_match('/error|failed|fatal|exception|cannot|unable/i', $line) && 
        !preg_match('/warning|deprecated/i', $line)) {
        $errors[] = [
            'line' => $lineNum + 1,
            'step' => $currentStep,
            'content' => $lineTrimmed,
        ];
    }
    
    // Detect warnings
    if (preg_match('/warning|deprecated|notice/i', $line)) {
        $warnings[] = [
            'line' => $lineNum + 1,
            'step' => $currentStep,
            'content' => $lineTrimmed,
        ];
    }
}

echo "Build steps: " . count($buildSteps) . "\n";
echo "Errors: " . count($errors) . "\n";
echo "Warnings: " . count($warnings) . "\n\n";

// Show build steps
if (!empty($buildSteps)) {
    echo "========================================\n";
    echo "Build Steps\n";
    echo "========================================\n";
    foreach ($buildSteps as $step) {
        echo "Line {$step['line']}: {$step['step']}\n";
    }
    echo "\n";
}

// Show errors
if (!empty($errors)) {
    echo "========================================\n";
    echo "Errors Found\n";
    echo "========================================\n\n";
    
    foreach (array_slice($errors, 0, 30) as $error) {
        echo "[Line {$error['line']}]";
        if ($error['step']) {
            echo " [{$error['step']}]";
        }
        echo "\n";
        echo "  {$error['content']}\n\n";
    }
    
    if (count($errors) > 30) {
        echo "... (" . (count($errors) - 30) . " more errors)\n\n";
    }
} else {
    echo "✅ No errors found!\n\n";
}

// Show warnings (first 10)
if (!empty($warnings)) {
    echo "========================================\n";
    echo "Warnings (first 10)\n";
    echo "========================================\n\n";
    
    foreach (array_slice($warnings, 0, 10) as $warning) {
        echo "[Line {$warning['line']}] {$warning['content']}\n";
    }
    
    if (count($warnings) > 10) {
        echo "\n... (" . (count($warnings) - 10) . " more warnings)\n";
    }
    echo "\n";
}

// Analyze common issues
echo "========================================\n";
echo "Common Issues Analysis\n";
echo "========================================\n\n";

$issuePatterns = [
    'Missing Package' => '/package.*not found|E: Unable to locate package|No package.*available/i',
    'Permission Denied' => '/permission denied|cannot open|access denied|chmod.*failed/i',
    'Network Error' => '/network|timeout|connection refused|failed to fetch/i',
    'Composer Error' => '/composer.*error|composer.*failed|Your requirements could not be resolved/i',
    'PHP Extension' => '/extension.*not found|php.*extension|configure.*failed/i',
    'Redis Error' => '/redis.*error|pecl.*redis|redis extension/i',
    'Disk Space' => '/no space|disk full|No space left/i',
    'Docker Layer' => '/failed to solve|failed to export|layer.*failed/i',
];

$foundIssues = [];
foreach ($lines as $lineNum => $line) {
    foreach ($issuePatterns as $issue => $pattern) {
        if (preg_match($pattern, $line)) {
            if (!isset($foundIssues[$issue])) {
                $foundIssues[$issue] = [];
            }
            $foundIssues[$issue][] = [
                'line' => $lineNum + 1,
                'content' => trim($line),
            ];
        }
    }
}

if (!empty($foundIssues)) {
    foreach ($foundIssues as $issue => $matches) {
        echo "⚠️  {$issue} (" . count($matches) . " occurrence(s)):\n";
        foreach (array_slice($matches, 0, 3) as $match) {
            echo "   Line {$match['line']}: " . substr($match['content'], 0, 100) . "\n";
        }
        if (count($matches) > 3) {
            echo "   ... (" . (count($matches) - 3) . " more)\n";
        }
        echo "\n";
    }
} else {
    echo "✅ No common issues detected\n\n";
}

// Show last 50 lines (usually contains the actual error)
echo "========================================\n";
echo "Last 50 Lines (Most Recent)\n";
echo "========================================\n\n";
$lastLines = array_slice($lines, -50);
foreach ($lastLines as $lineNum => $line) {
    $actualLineNum = count($lines) - 50 + $lineNum + 1;
    echo "[{$actualLineNum}] {$line}\n";
}

// Save analysis
$analysisFile = __DIR__ . '/../storage/logs/docker-build-analysis.log';
$analysisDir = dirname($analysisFile);
if (!is_dir($analysisDir)) {
    mkdir($analysisDir, 0755, true);
}

$analysis = [];
$analysis[] = "Docker Build Log Analysis";
$analysis[] = "Generated: " . date('Y-m-d H:i:s');
$analysis[] = "Log file: {$logFile}";
$analysis[] = "";
$analysis[] = "Statistics:";
$analysis[] = "  Total lines: " . count($lines);
$analysis[] = "  Build steps: " . count($buildSteps);
$analysis[] = "  Errors: " . count($errors);
$analysis[] = "  Warnings: " . count($warnings);
$analysis[] = "";
$analysis[] = "Errors:";
foreach ($errors as $error) {
    $analysis[] = "Line {$error['line']} [{$error['step']}]: {$error['content']}";
}

file_put_contents($analysisFile, implode("\n", $analysis));

echo "\n";
echo "========================================\n";
echo "Analysis Complete\n";
echo "========================================\n";
echo "Full log: {$logFile}\n";
echo "Analysis saved: {$analysisFile}\n";

