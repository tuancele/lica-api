<?php

/**
 * Auto push to GitHub and fetch CI/CD logs
 */

echo "========================================\n";
echo "Auto Push & Fetch GitHub Logs\n";
echo "========================================\n\n";

$token = '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';
$repoDir = __DIR__ . '/..';

chdir($repoDir);

// Step 1: Get repository info
echo "[1/6] Getting repository info...\n";
$remoteUrl = trim(shell_exec('git config --get remote.origin.url 2>&1'));
if (preg_match('/(?:github\.com[/:]|git@github\.com:)([^\/]+)\/([^\/\.]+)/', $remoteUrl, $matches)) {
    $repo = $matches[1] . '/' . rtrim($matches[2], '.git');
    echo "  Repository: {$repo}\n";
} else {
    die("❌ Could not detect repository\n");
}

$branch = trim(shell_exec('git branch --show-current 2>&1'));
echo "  Branch: {$branch}\n\n";

// Step 2: Check git status
echo "[2/6] Checking git status...\n";
$status = shell_exec('git status --short 2>&1');
if (empty(trim($status))) {
    echo "  ℹ️  No changes to commit\n";
} else {
    echo $status . "\n";
}

// Step 3: Stage and commit
echo "[3/6] Staging and committing changes...\n";
shell_exec('git add Dockerfile .dockerignore 2>&1');

$statusAfter = shell_exec('git status --short 2>&1');
if (!empty(trim($statusAfter))) {
    $commitOutput = shell_exec('git commit -m "Fix Docker build: Create bootstrap/cache directory before chmod" 2>&1');
    echo $commitOutput . "\n";
} else {
    echo "  ℹ️  No changes to commit (already committed)\n";
}

// Step 4: Push to GitHub
echo "[4/6] Pushing to GitHub...\n";
$pushOutput = shell_exec("git push origin {$branch} 2>&1");
echo $pushOutput . "\n";

if (strpos($pushOutput, 'error') !== false || strpos($pushOutput, 'fatal') !== false) {
    echo "  ⚠️  Push may have issues. Trying to fix...\n";
    
    // Try pull first
    echo "  → Pulling latest changes...\n";
    shell_exec('git pull --rebase origin ' . $branch . ' 2>&1');
    
    // Try push again
    echo "  → Pushing again...\n";
    $pushOutput2 = shell_exec("git push origin {$branch} 2>&1");
    echo $pushOutput2 . "\n";
    
    if (strpos($pushOutput2, 'error') !== false || strpos($pushOutput2, 'fatal') !== false) {
        echo "  ❌ Push failed. Please check manually.\n";
        exit(1);
    }
}

echo "  ✅ Pushed successfully\n\n";

// Step 5: Wait for CI/CD
echo "[5/6] Waiting 60 seconds for CI/CD to start and complete...\n";
for ($i = 60; $i > 0; $i--) {
    echo "\r  Waiting... {$i}s remaining  ";
    sleep(1);
}
echo "\n\n";

// Step 6: Fetch logs from GitHub
echo "[6/6] Fetching CI/CD logs from GitHub...\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/{$repo}/actions/runs?per_page=5&status=completed");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: token ' . $token,
    'Accept: application/vnd.github.v3+json',
    'User-Agent: Laravel-CI-Auto-Fix',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ HTTP Error {$httpCode}: " . substr($response, 0, 200) . "\n");
}

$data = json_decode($response, true);
$runs = $data['workflow_runs'] ?? [];

if (empty($runs)) {
    echo "ℹ️  No workflow runs found yet. Please check later.\n";
    exit(0);
}

// Display latest runs
echo "========================================\n";
echo "Latest Workflow Runs\n";
echo "========================================\n\n";

foreach (array_slice($runs, 0, 3) as $index => $run) {
    $runId = $run['id'];
    $conclusion = $run['conclusion'] ?? 'unknown';
    $status = $run['status'] ?? 'unknown';
    $createdAt = $run['created_at'];
    $htmlUrl = $run['html_url'];
    $workflowName = $run['name'] ?? 'Unknown';
    
    $statusIcon = ($conclusion === 'success') ? '✅' : (($conclusion === 'failure') ? '❌' : '⚠️');
    
    echo "Run #{$runId} - {$workflowName}\n";
    echo "  Status: {$statusIcon} {$conclusion}\n";
    echo "  Created: {$createdAt}\n";
    echo "  URL: {$htmlUrl}\n";
    
    if ($conclusion === 'failure') {
        // Get jobs for failed run
        $jobsCh = curl_init();
        curl_setopt($jobsCh, CURLOPT_URL, "https://api.github.com/repos/{$repo}/actions/runs/{$runId}/jobs");
        curl_setopt($jobsCh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($jobsCh, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $token,
            'Accept: application/vnd.github.v3+json',
            'User-Agent: Laravel-CI-Auto-Fix',
        ]);
        curl_setopt($jobsCh, CURLOPT_TIMEOUT, 30);
        
        $jobsResponse = curl_exec($jobsCh);
        $jobsHttpCode = curl_getinfo($jobsCh, CURLINFO_HTTP_CODE);
        curl_close($jobsCh);
        
        if ($jobsHttpCode === 200) {
            $jobsData = json_decode($jobsResponse, true);
            $jobList = $jobsData['jobs'] ?? [];
            
            echo "  Failed Jobs:\n";
            foreach ($jobList as $job) {
                $jobName = $job['name'] ?? 'Unknown';
                $jobConclusion = $job['conclusion'] ?? 'unknown';
                
                if ($jobConclusion !== 'success') {
                    echo "    - {$jobName}: {$jobConclusion}\n";
                    
                    // Try to get logs
                    if (isset($job['id'])) {
                        $logsCh = curl_init();
                        curl_setopt($logsCh, CURLOPT_URL, "https://api.github.com/repos/{$repo}/actions/jobs/{$job['id']}/logs");
                        curl_setopt($logsCh, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($logsCh, CURLOPT_HTTPHEADER, [
                            'Authorization: token ' . $token,
                            'Accept: application/vnd.github.v3+json',
                            'User-Agent: Laravel-CI-Auto-Fix',
                        ]);
                        curl_setopt($logsCh, CURLOPT_TIMEOUT, 30);
                        
                        $logsResponse = curl_exec($logsCh);
                        $logsHttpCode = curl_getinfo($logsCh, CURLINFO_HTTP_CODE);
                        curl_close($logsCh);
                        
                        if ($logsHttpCode === 200 && !empty($logsResponse)) {
                            // Check for common errors
                            if (stripos($logsResponse, 'bootstrap/cache') !== false && 
                                stripos($logsResponse, 'cannot access') !== false) {
                                echo "      → Error: bootstrap/cache directory not found\n";
                            }
                            
                            if (stripos($logsResponse, 'composer') !== false && 
                                stripos($logsResponse, 'error') !== false) {
                                echo "      → Error: Composer error detected\n";
                            }
                            
                            // Save logs
                            $logFile = __DIR__ . '/../storage/logs/github-ci-run-' . $runId . '.log';
                            $logDir = dirname($logFile);
                            if (!is_dir($logDir)) {
                                mkdir($logDir, 0755, true);
                            }
                            file_put_contents($logFile, $logsResponse);
                            echo "      → Logs saved: {$logFile}\n";
                        }
                    }
                }
            }
        }
    }
    
    echo "\n";
}

echo "========================================\n";
echo "Summary\n";
echo "========================================\n";
echo "✅ Push completed\n";
echo "✅ Logs fetched\n";
echo "\nCheck GitHub Actions:\n";
echo "https://github.com/{$repo}/actions\n";
echo "\n";

