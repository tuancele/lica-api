<?php

/**
 * Auto fix CI/CD - Test, push, fetch logs, and fix errors automatically
 */

require __DIR__.'/../vendor/autoload.php';

$token = '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';

echo "========================================\n";
echo "Auto CI/CD Fix - Automatic Process\n";
echo "========================================\n\n";

// Get repository
$gitRemote = shell_exec('git config --get remote.origin.url 2>&1');
if ($gitRemote && preg_match('/(?:github\.com[/:]|git@github\.com:)([^\/]+)\/([^\/\.]+)/', $gitRemote, $matches)) {
    $repo = $matches[1] . '/' . rtrim($matches[2], '.git');
} else {
    die("❌ Could not detect repository\n");
}

echo "Repository: {$repo}\n";
echo "Starting automatic fix process...\n\n";

$client = new GuzzleHttp\Client([
    'base_uri' => 'https://api.github.com',
    'headers' => [
        'Authorization' => 'token ' . $token,
        'Accept' => 'application/vnd.github.v3+json',
        'User-Agent' => 'Laravel-CI-Auto-Fix',
    ],
    'timeout' => 30,
]);

$maxAttempts = 5;
$attempt = 0;
$fixed = false;

while ($attempt < $maxAttempts && !$fixed) {
    $attempt++;
    echo "========================================\n";
    echo "Attempt {$attempt}/{$maxAttempts}\n";
    echo "========================================\n\n";

    // Wait for workflow to complete
    if ($attempt > 1) {
        echo "⏳ Waiting 30 seconds for workflow to complete...\n";
        sleep(30);
    }

    // Get latest workflow runs
    echo "📥 Fetching latest workflow runs...\n";
    try {
        $response = $client->get("/repos/{$repo}/actions/runs", [
            'query' => [
                'per_page' => 3,
                'status' => 'completed',
            ],
        ]);

        $runs = json_decode($response->getBody()->getContents(), true);
        $workflowRuns = $runs['workflow_runs'] ?? [];

        if (empty($workflowRuns)) {
            echo "ℹ️  No workflow runs found yet. Waiting...\n";
            continue;
        }

        $latestRun = $workflowRuns[0];
        $runId = $latestRun['id'];
        $conclusion = $latestRun['conclusion'] ?? 'unknown';
        $createdAt = $latestRun['created_at'];
        $htmlUrl = $latestRun['html_url'];

        echo "Latest run: #{$runId} - {$conclusion}\n";
        echo "Created: {$createdAt}\n";
        echo "URL: {$htmlUrl}\n\n";

        if ($conclusion === 'success') {
            echo "✅ Build successful! No errors found.\n";
            $fixed = true;
            break;
        }

        // Get jobs for failed run
        echo "🔍 Analyzing failed jobs...\n";
        $jobsResponse = $client->get("/repos/{$repo}/actions/runs/{$runId}/jobs");
        $jobs = json_decode($jobsResponse->getBody()->getContents(), true);
        $jobList = $jobs['jobs'] ?? [];

        $errors = [];
        foreach ($jobList as $job) {
            $jobName = $job['name'] ?? 'Unknown';
            $jobConclusion = $job['conclusion'] ?? 'unknown';

            if ($jobConclusion === 'success') {
                continue;
            }

            echo "\n  Job: {$jobName} - {$jobConclusion}\n";

            // Get failed steps
            if (isset($job['steps']) && is_array($job['steps'])) {
                foreach ($job['steps'] as $step) {
                    $stepName = $step['name'] ?? 'Unknown';
                    $stepConclusion = $step['conclusion'] ?? 'unknown';

                    if ($stepConclusion === 'failure') {
                        echo "    ❌ Failed: {$stepName}\n";
                        
                        // Try to get logs
                        if (isset($job['id'])) {
                            try {
                                $logsResponse = $client->get("/repos/{$repo}/actions/jobs/{$job['id']}/logs");
                                $logs = $logsResponse->getBody()->getContents();
                                
                                // Check for Docker build errors
                                if (stripos($logs, 'bootstrap/cache') !== false && 
                                    stripos($logs, 'cannot access') !== false) {
                                    $errors[] = 'docker_bootstrap_cache';
                                    echo "      → Docker bootstrap/cache error detected\n";
                                }
                                
                                // Check for other common errors
                                if (stripos($logs, 'composer') !== false && 
                                    stripos($logs, 'error') !== false) {
                                    $errors[] = 'composer_error';
                                    echo "      → Composer error detected\n";
                                }
                            } catch (Exception $e) {
                                // Logs might not be available
                            }
                        }
                    }
                }
            }
        }

        // Fix errors
        if (!empty($errors)) {
            echo "\n🔧 Fixing errors...\n";
            
            $dockerfilePath = __DIR__ . '/../Dockerfile';
            $dockerfileContent = file_get_contents($dockerfilePath);
            $modified = false;

            // Fix bootstrap/cache error
            if (in_array('docker_bootstrap_cache', $errors)) {
                if (strpos($dockerfileContent, 'mkdir -p /var/www/html/bootstrap/cache') === false) {
                    echo "  → Fixing bootstrap/cache issue...\n";
                    
                    $pattern = '/RUN chown -R www-data:www-data \/var\/www\/html[^&]*&& chmod -R 755 \/var\/www\/html\/storage[^&]*&& chmod -R 755 \/var\/www\/html\/bootstrap\/cache/s';
                    
                    $replacement = 'RUN chown -R www-data:www-data /var/www/html \\
    && mkdir -p /var/www/html/storage/framework/cache \\
    && mkdir -p /var/www/html/storage/framework/sessions \\
    && mkdir -p /var/www/html/storage/framework/views \\
    && mkdir -p /var/www/html/storage/logs \\
    && mkdir -p /var/www/html/bootstrap/cache \\
    && chmod -R 755 /var/www/html/storage \\
    && chmod -R 755 /var/www/html/bootstrap/cache';
                    
                    $dockerfileContent = preg_replace($pattern, $replacement, $dockerfileContent);
                    $modified = true;
                }
            }

            if ($modified) {
                file_put_contents($dockerfilePath, $dockerfileContent);
                echo "  ✅ Dockerfile updated\n";
                
                // Commit and push
                echo "\n📤 Committing and pushing fixes...\n";
                shell_exec('cd ' . escapeshellarg(__DIR__ . '/..') . ' && git add Dockerfile 2>&1');
                shell_exec('cd ' . escapeshellarg(__DIR__ . '/..') . ' && git commit -m "Auto-fix: Fix Docker build errors" 2>&1');
                shell_exec('cd ' . escapeshellarg(__DIR__ . '/..') . ' && git push 2>&1');
                
                echo "  ✅ Changes pushed\n";
                echo "\n⏳ Waiting for next workflow run...\n";
            } else {
                echo "  ℹ️  No fixes needed or already fixed\n";
            }
        } else {
            echo "\n✅ No fixable errors found\n";
            $fixed = true;
        }

    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        break;
    }
}

echo "\n========================================\n";
echo "Process Complete\n";
echo "========================================\n";

if ($fixed) {
    echo "✅ CI/CD is now passing or no fixable errors found\n";
} else {
    echo "⚠️  Reached maximum attempts. Please check manually.\n";
}

