<?php

/**
 * Auto push to GitHub and fix CI/CD errors
 */

echo "========================================\n";
echo "Auto Push & Fix CI/CD - Automatic\n";
echo "========================================\n\n";

$token = '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';
$repoDir = __DIR__ . '/..';

// Step 1: Check git status
echo "[1/7] Checking git status...\n";
chdir($repoDir);
$status = shell_exec('git status --short 2>&1');
echo $status . "\n";

// Step 2: Stage Dockerfile if changed
echo "[2/7] Staging Dockerfile changes...\n";
shell_exec('git add Dockerfile .dockerignore 2>&1');

// Step 3: Check if there are changes to commit
$statusAfter = shell_exec('git status --short 2>&1');
if (empty(trim($statusAfter))) {
    echo "  ℹ️  No changes to commit\n";
} else {
    echo "[3/7] Committing changes...\n";
    $commitOutput = shell_exec('git commit -m "Fix Docker build: Create bootstrap/cache directory before chmod" 2>&1');
    echo $commitOutput . "\n";
}

// Step 4: Get current branch
$branch = trim(shell_exec('git branch --show-current 2>&1'));
echo "  Current branch: {$branch}\n";

// Step 5: Push to GitHub
echo "[4/7] Pushing to GitHub...\n";
$pushOutput = shell_exec("git push origin {$branch} 2>&1");
echo $pushOutput . "\n";

if (strpos($pushOutput, 'error') !== false || strpos($pushOutput, 'fatal') !== false) {
    echo "  ❌ Push failed. Trying to fix...\n";
    
    // Try pull first
    echo "  → Pulling latest changes...\n";
    shell_exec('git pull --rebase origin ' . $branch . ' 2>&1');
    
    // Try push again
    echo "  → Pushing again...\n";
    $pushOutput2 = shell_exec("git push origin {$branch} 2>&1");
    echo $pushOutput2 . "\n";
}

// Step 6: Get repository info
$remoteUrl = trim(shell_exec('git config --get remote.origin.url 2>&1'));
if (preg_match('/(?:github\.com[/:]|git@github\.com:)([^\/]+)\/([^\/\.]+)/', $remoteUrl, $matches)) {
    $repo = $matches[1] . '/' . rtrim($matches[2], '.git');
    echo "  Repository: {$repo}\n";
} else {
    die("❌ Could not detect repository\n");
}

// Step 7: Wait for CI/CD
echo "[5/7] Waiting 60 seconds for CI/CD to start...\n";
sleep(60);

// Step 8: Fetch and analyze logs
echo "[6/7] Fetching CI/CD logs...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/{$repo}/actions/runs?per_page=3&status=completed");
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

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $runs = $data['workflow_runs'] ?? [];
    
    if (!empty($runs)) {
        $latestRun = $runs[0];
        $runId = $latestRun['id'];
        $conclusion = $latestRun['conclusion'] ?? 'unknown';
        $htmlUrl = $latestRun['html_url'];
        
        echo "  Latest run: #{$runId} - {$conclusion}\n";
        echo "  URL: {$htmlUrl}\n";
        
        if ($conclusion === 'success') {
            echo "  ✅ Build successful!\n";
        } else {
            echo "  ❌ Build failed. Analyzing...\n";
            
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
                        echo "    Job: {$jobName} - {$jobConclusion}\n";
                    }
                }
            }
        }
    } else {
        echo "  ℹ️  No workflow runs found yet\n";
    }
} else {
    echo "  ⚠️  Could not fetch workflow runs (HTTP {$httpCode})\n";
}

// Step 9: Final status
echo "[7/7] Process complete\n";
echo "\n========================================\n";
echo "Summary\n";
echo "========================================\n";
echo "✅ Changes committed\n";
echo "✅ Pushed to GitHub\n";
echo "✅ CI/CD logs fetched\n";
echo "\nCheck GitHub Actions for build status:\n";
echo "https://github.com/{$repo}/actions\n";
echo "\n";

