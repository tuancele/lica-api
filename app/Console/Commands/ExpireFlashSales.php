<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\FlashSale\Models\FlashSale;
use App\Services\FlashSale\FlashSaleStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Expire Flash Sales Command.
 *
 * Finds expired Flash Sales and releases remaining stock back to warehouse
 */
class ExpireFlashSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flashsale:expire 
                            {--dry-run : Run without making changes}
                            {--force : Force expire even if status is not active}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire Flash Sales that have ended and release remaining stock to warehouse';

    /**
     * Execute the console command.
     */
    public function handle(FlashSaleStockService $stockService): int
    {
        $now = time();
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Scanning for expired Flash Sales...');

        // Find Flash Sales that have ended but are still active
        $query = FlashSale::with('products')
            ->where('end', '<', $now);

        if (! $force) {
            $query->where('status', '1'); // Only active ones
        }

        $expiredFlashSales = $query->get();

        if ($expiredFlashSales->isEmpty()) {
            $this->info('No expired Flash Sales found.');

            return Command::SUCCESS;
        }

        $this->info("Found {$expiredFlashSales->count()} expired Flash Sale(s).");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $totalReleased = 0;
        $processed = 0;
        $failed = 0;

        foreach ($expiredFlashSales as $flashSale) {
            $this->line("Processing Flash Sale #{$flashSale->id}: {$flashSale->name}");

            if ($dryRun) {
                // Calculate what would be released
                $wouldRelease = 0;
                foreach ($flashSale->products as $productSale) {
                    if ($productSale->variant_id) {
                        $remaining = max(0, $productSale->number - $productSale->buy);
                        $wouldRelease += $remaining;
                    }
                }
                $this->info("  Would release {$wouldRelease} units of stock");
                $totalReleased += $wouldRelease;
                $processed++;
                continue;
            }

            try {
                // Release stock for this campaign
                $result = $stockService->revertStockForCampaign($flashSale);

                if ($result['success']) {
                    $totalReleased += $result['total_released'];
                    $this->info("  Released {$result['total_released']} units of stock");

                    // Update status to expired (0 = inactive)
                    $flashSale->update(['status' => '0']);
                    $this->info('  Status updated to inactive');

                    $processed++;
                } else {
                    $this->error("  Failed to release stock: {$result['message']}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("  Exception: {$e->getMessage()}");
                Log::error('[ExpireFlashSales] Error processing Flash Sale', [
                    'flash_sale_id' => $flashSale->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $failed++;
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->info("  Processed: {$processed}");
        $this->info("  Failed: {$failed}");
        $this->info("  Total stock released: {$totalReleased}");

        return Command::SUCCESS;
    }
}
