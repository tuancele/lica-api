<?php

/**
 * Simple script to fetch GitHub CI/CD logs
 */

$token = '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';

echo "========================================\n";
echo "Fetch GitHub CI/CD Logs\n";
echo "========================================\n\n";

// Get repository
$remoteUrl = trim(shell_exec('git config --get remote.origin.url 2>&1'));
if (preg_match('/(?:github\.com[/:]|git@github\.com:)([^\/]+)\/([^\/\.]+)/', $remoteUrl, $matches)) {
    $repo = $matches[1] . '/' . rtrim($matches[2], '.git');
} else {
    die("❌ Could not detect repository\n");
}

echo "Repository: {$repo}\n\n";

// Fetch workflow runs
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

echo "Latest Workflow Runs:\n";
echo "========================================\n\n";

foreach (array_slice($runs, 0, 3) as $run) {
    $runId = $run['id'];
    $conclusion = $run['conclusion'] ?? 'unknown';
    $status = $run['status'] ?? 'unknown';
    $createdAt = $run['created_at'];
    $htmlUrl = $run['html_url'];
    $workflowName = $run['name'] ?? 'Unknown';
    
    $icon = ($conclusion === 'success') ? '✅' : (($conclusion === 'failure') ? '❌' : '⚠️');
    
    echo "{$icon} Run #{$runId} - {$workflowName}\n";
    echo "   Status: {$conclusion}\n";
    echo "   Created: {$createdAt}\n";
    echo "   URL: {$htmlUrl}\n\n";
    
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
            
            foreach ($jobList as $job) {
                $jobName = $job['name'] ?? 'Unknown';
                $jobConclusion = $job['conclusion'] ?? 'unknown';
                
                if ($jobConclusion !== 'success') {
                    echo "   Failed Job: {$jobName}\n";
                    
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
                            $logFile = __DIR__ . '/../storage/logs/github-ci-' . $runId . '-' . $job['id'] . '.log';
                            $logDir = dirname($logFile);
                            if (!is_dir($logDir)) {
                                mkdir($logDir, 0755, true);
                            }
                            file_put_contents($logFile, $logsResponse);
                            echo "      Logs saved: " . basename($logFile) . "\n";
                            
                            // Check for errors
                            if (stripos($logsResponse, 'bootstrap/cache') !== false) {
                                echo "      ⚠️  bootstrap/cache error found\n";
                            }
                        }
                    }
                }
            }
        }
    }
}

echo "\n========================================\n";
echo "Check GitHub Actions:\n";
echo "https://github.com/{$repo}/actions\n";
echo "\n";

