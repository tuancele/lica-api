<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StopCrawlJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:stop {--all : Stop all running crawl jobs} {--crawl-id= : Stop specific crawl by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop running dictionary ingredient crawl jobs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $stopAll = $this->option('all');
        $crawlId = $this->option('crawl-id');

        if (! $stopAll && ! $crawlId) {
            $this->error('Please specify --all to stop all jobs or --crawl-id=<id> to stop a specific job');

            return Command::FAILURE;
        }

        if ($stopAll) {
            return $this->stopAllJobs();
        }

        if ($crawlId) {
            return $this->stopSpecificJob($crawlId);
        }

        return Command::SUCCESS;
    }

    private function stopAllJobs(): int
    {
        $this->info('Searching for running crawl jobs...');

        // Search for all crawl job keys in cache
        // Note: This is a simplified approach. In production, you might want to maintain
        // a list of active crawl IDs in a separate cache key or database table.
        $stopped = 0;
        $notFound = 0;
        $alreadyStopped = 0;

        // Try to find crawl jobs by pattern
        // Since Laravel Cache doesn't support pattern matching directly,
        // we'll need to check common patterns or maintain a registry
        // For now, we'll use a workaround: check localStorage or maintain a list

        // Alternative: Use Redis SCAN if available, or maintain a registry
        $this->warn('Note: To stop all jobs, you may need to specify crawl IDs manually.');
        $this->info('To stop a specific job, use: php artisan crawl:stop --crawl-id=<crawl-id>');

        // If you have a way to list all crawl IDs, iterate through them
        // For now, we'll provide instructions

        $this->info('To find running crawl jobs, check the cache keys matching pattern: dictionary_ingredient_crawl_job:*');
        $this->info('Or check the frontend at /admin/dictionary/ingredient/crawl to see active crawls');

        return Command::SUCCESS;
    }

    private function stopSpecificJob(string $crawlId): int
    {
        $this->info("Stopping crawl job: {$crawlId}");

        $key = 'dictionary_ingredient_crawl_job:'.$crawlId;
        $state = Cache::get($key);

        if (! is_array($state)) {
            $this->error("Crawl job not found: {$crawlId}");

            return Command::FAILURE;
        }

        // Check if already done or cancelled
        if (! empty($state['done']) || ! empty($state['cancelled'])) {
            $status = $state['status'] ?? 'unknown';
            $this->warn("Crawl job already stopped. Status: {$status}");

            return Command::SUCCESS;
        }

        // Mark as cancelled
        $state['cancelled'] = true;
        $state['status'] = 'cancelling';
        $state['updated_at'] = time();
        Cache::put($key, $state, now()->addHours(6));

        $processed = $state['processed'] ?? 0;
        $total = $state['total'] ?? 0;
        $offset = $state['offset'] ?? 0;

        $this->info('✓ Crawl job cancelled successfully');
        $this->line("  Crawl ID: {$crawlId}");
        $this->line("  Offset: {$offset}");
        $this->line("  Progress: {$processed}/{$total}");

        Log::info('DictionaryIngredientCrawlJob cancelled via command', [
            'crawl_id' => $crawlId,
            'offset' => $offset,
            'processed' => $processed,
            'total' => $total,
        ]);

        return Command::SUCCESS;
    }
}
