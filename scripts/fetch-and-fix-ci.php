<?php

/**
 * Fetch CI/CD logs and auto-fix errors
 */

$token = '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';

echo "========================================\n";
echo "CI/CD Auto Fix - Fetch Logs and Fix\n";
echo "========================================\n\n";

// Get repository
$gitRemote = shell_exec('git config --get remote.origin.url 2>&1');
if ($gitRemote && preg_match('/(?:github\.com[/:]|git@github\.com:)([^\/]+)\/([^\/\.]+)/', $gitRemote, $matches)) {
    $repo = $matches[1] . '/' . rtrim($matches[2], '.git');
} else {
    die("❌ Could not detect repository\n");
}

echo "Repository: {$repo}\n\n";

// Use cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/{$repo}/actions/runs?per_page=5&status=completed");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: token ' . $token,
    'Accept: application/vnd.github.v3+json',
    'User-Agent: Laravel-CI-Auto-Fix',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "📥 Fetching workflow runs...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ HTTP Error {$httpCode}: " . substr($response, 0, 200) . "\n");
}

$data = json_decode($response, true);
$runs = $data['workflow_runs'] ?? [];

if (empty($runs)) {
    echo "ℹ️  No workflow runs found.\n";
    exit(0);
}

$latestRun = $runs[0];
$runId = $latestRun['id'];
$conclusion = $latestRun['conclusion'] ?? 'unknown';
$createdAt = $latestRun['created_at'];
$htmlUrl = $latestRun['html_url'];

echo "Latest run: #{$runId} - {$conclusion}\n";
echo "Created: {$createdAt}\n";
echo "URL: {$htmlUrl}\n\n";

if ($conclusion === 'success') {
    echo "✅ Build successful! No errors.\n";
    exit(0);
}

// Get jobs
echo "🔍 Analyzing failed jobs...\n";
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

if ($jobsHttpCode !== 200) {
    die("❌ Error fetching jobs: HTTP {$jobsHttpCode}\n");
}

$jobsData = json_decode($jobsResponse, true);
$jobList = $jobsData['jobs'] ?? [];

$needsFix = false;
$fixMessage = '';

foreach ($jobList as $job) {
    $jobName = $job['name'] ?? 'Unknown';
    $jobConclusion = $job['conclusion'] ?? 'unknown';

    if ($jobConclusion === 'success') {
        continue;
    }

    echo "\n  Job: {$jobName} - {$jobConclusion}\n";

    // Get logs for Docker build job
    if (stripos($jobName, 'docker') !== false || stripos($jobName, 'build') !== false) {
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

            if ($logsHttpCode === 200) {
                $logs = $logsResponse;
                
                // Check for bootstrap/cache error
                if (stripos($logs, 'bootstrap/cache') !== false && 
                    stripos($logs, 'cannot access') !== false) {
                    echo "    ❌ Docker bootstrap/cache error found\n";
                    $needsFix = true;
                    $fixMessage = 'docker_bootstrap_cache';
                }
            }
        }
    }
}

// Auto-fix
if ($needsFix && $fixMessage === 'docker_bootstrap_cache') {
    echo "\n🔧 Auto-fixing Dockerfile...\n";
    
    $dockerfilePath = __DIR__ . '/../Dockerfile';
    if (!file_exists($dockerfilePath)) {
        echo "❌ Dockerfile not found\n";
        exit(1);
    }
    
    $content = file_get_contents($dockerfilePath);
    
    // Check if already fixed
    if (strpos($content, 'mkdir -p /var/www/html/bootstrap/cache') !== false) {
        echo "  ℹ️  Dockerfile already fixed\n";
    } else {
        // Fix the RUN command
        $pattern = '/(RUN chown -R www-data:www-data \/var\/www\/html\s+&&\s+chmod -R 755 \/var\/www\/html\/storage\s+&&\s+chmod -R 755 \/var\/www\/html\/bootstrap\/cache)/s';
        
        $replacement = 'RUN chown -R www-data:www-data /var/www/html \\
    && mkdir -p /var/www/html/storage/framework/cache \\
    && mkdir -p /var/www/html/storage/framework/sessions \\
    && mkdir -p /var/www/html/storage/framework/views \\
    && mkdir -p /var/www/html/storage/logs \\
    && mkdir -p /var/www/html/bootstrap/cache \\
    && chmod -R 755 /var/www/html/storage \\
    && chmod -R 755 /var/www/html/bootstrap/cache';
        
        $newContent = preg_replace($pattern, $replacement, $content);
        
        if ($newContent !== $content) {
            file_put_contents($dockerfilePath, $newContent);
            echo "  ✅ Dockerfile fixed\n";
            
            // Commit and push
            echo "\n📤 Committing and pushing fix...\n";
            $gitDir = __DIR__ . '/..';
            shell_exec("cd " . escapeshellarg($gitDir) . " && git add Dockerfile 2>&1");
            shell_exec("cd " . escapeshellarg($gitDir) . " && git commit -m \"Auto-fix: Create bootstrap/cache directory before chmod\" 2>&1");
            shell_exec("cd " . escapeshellarg($gitDir) . " && git push 2>&1");
            
            echo "  ✅ Fix pushed to GitHub\n";
            echo "\n⏳ CI/CD will run again automatically...\n";
        } else {
            echo "  ⚠️  Could not apply fix (pattern not found)\n";
        }
    }
} else {
    echo "\n✅ No fixable errors found or errors already fixed\n";
}

echo "\n========================================\n";
echo "Process Complete\n";
echo "========================================\n";

