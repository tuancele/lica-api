<?php

/**
 * Push to GitHub and fetch CI/CD logs
 */

echo "========================================\n";
echo "Push & Fetch GitHub Logs\n";
echo "========================================\n\n";

$token = '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';
$repoDir = __DIR__ . '/..';

chdir($repoDir);

// Get repository info
echo "[1/5] Getting repository info...\n";
$remoteUrl = trim(shell_exec('git config --get remote.origin.url 2>&1'));
if (preg_match('/(?:github\.com[/:]|git@github\.com:)([^\/]+)\/([^\/\.]+)/', $remoteUrl, $matches)) {
    $repo = $matches[1] . '/' . rtrim($matches[2], '.git');
    echo "  Repository: {$repo}\n";
} else {
    die("❌ Could not detect repository\n");
}

$branch = trim(shell_exec('git branch --show-current 2>&1'));
echo "  Branch: {$branch}\n\n";

// Stage and commit
echo "[2/5] Staging and committing changes...\n";
shell_exec('git add Dockerfile .dockerignore 2>&1');

$statusAfter = shell_exec('git status --short 2>&1');
if (!empty(trim($statusAfter))) {
    $commitOutput = shell_exec('git commit -m "Fix Docker build: Create bootstrap/cache directory before chmod" 2>&1');
    echo $commitOutput . "\n";
} else {
    echo "  ℹ️  No changes to commit\n";
}

// Push
echo "[3/5] Pushing to GitHub...\n";
$pushOutput = shell_exec("git push origin {$branch} 2>&1");
echo $pushOutput . "\n";

if (strpos($pushOutput, 'error') !== false || strpos($pushOutput, 'fatal') !== false) {
    echo "  ⚠️  Push failed. Trying to fix...\n";
    shell_exec('git pull --rebase origin ' . $branch . ' 2>&1');
    $pushOutput2 = shell_exec("git push origin {$branch} 2>&1");
    echo $pushOutput2 . "\n";
} else {
    echo "  ✅ Pushed successfully\n";
}

// Wait for CI/CD
echo "\n[4/5] Waiting 60 seconds for CI/CD...\n";
for ($i = 60; $i > 0; $i--) {
    echo "\r  {$i}s remaining  ";
    sleep(1);
}
echo "\n\n";

// Fetch logs
echo "[5/5] Fetching CI/CD logs...\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/{$repo}/actions/runs?per_page=5");
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
    die("❌ HTTP Error {$httpCode}\n");
}

$data = json_decode($response, true);
$runs = $data['workflow_runs'] ?? [];

if (empty($runs)) {
    echo "ℹ️  No workflow runs found\n";
    exit(0);
}

echo "========================================\n";
echo "Latest Workflow Runs\n";
echo "========================================\n\n";

foreach (array_slice($runs, 0, 3) as $run) {
    $runId = $run['id'];
    $conclusion = $run['conclusion'] ?? 'unknown';
    $createdAt = $run['created_at'];
    $htmlUrl = $run['html_url'];
    $workflowName = $run['name'] ?? 'Unknown';
    
    $icon = ($conclusion === 'success') ? '✅' : (($conclusion === 'failure') ? '❌' : '⚠️');
    
    echo "{$icon} Run #{$runId} - {$workflowName}\n";
    echo "   Status: {$conclusion}\n";
    echo "   Created: {$createdAt}\n";
    echo "   URL: {$htmlUrl}\n";
    
    if ($conclusion === 'failure') {
        // Get jobs
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
            
            echo "   Failed Jobs:\n";
            foreach ($jobList as $job) {
                $jobName = $job['name'] ?? 'Unknown';
                $jobConclusion = $job['conclusion'] ?? 'unknown';
                
                if ($jobConclusion !== 'success') {
                    echo "      - {$jobName}: {$jobConclusion}\n";
                    
                    // Get logs
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
                            // Save logs
                            $logDir = __DIR__ . '/../storage/logs';
                            if (!is_dir($logDir)) {
                                mkdir($logDir, 0755, true);
                            }
                            $logFile = $logDir . '/github-ci-' . $runId . '-' . $job['id'] . '.log';
                            file_put_contents($logFile, $logsResponse);
                            echo "         Logs saved: " . basename($logFile) . "\n";
                            
                            // Check for errors
                            if (stripos($logsResponse, 'bootstrap/cache') !== false) {
                                echo "         ⚠️  bootstrap/cache error found\n";
                            }
                            if (stripos($logsResponse, 'composer') !== false && stripos($logsResponse, 'error') !== false) {
                                echo "         ⚠️  Composer error found\n";
                            }
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

