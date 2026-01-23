<?php

namespace App\Console\Commands;

use App\Modules\Product\Models\Variant;
use App\Services\Gmc\GmcSyncService;
use Illuminate\Console\Command;

class GmcSyncProducts extends Command
{
    protected $signature = 'gmc:sync-products
        {--variant_id=* : Variant IDs to sync}
        {--dry-run : Do not call Google API}';

    protected $description = 'Sync variants to Google Merchant Center (GMC)';

    public function handle(GmcSyncService $syncService): int
    {
        $variantIds = array_values(array_unique(array_map('intval', (array) $this->option('variant_id'))));
        $dryRun = (bool) $this->option('dry-run');

        if (count($variantIds) === 0) {
            $this->error('No variant_id provided.');
            return self::INVALID;
        }

        foreach ($variantIds as $variantId) {
            $variant = Variant::with(['product.brand'])->find($variantId);
            if (!$variant || !$variant->product) {
                $this->warn("Variant not found: {$variantId}");
                continue;
            }

            $sync = $syncService->syncVariant($variant, $dryRun);
            $sent = $sync['sent'] ? 'yes' : 'no';
            $this->info("Variant {$variantId} offer_id={$sync['offer_id']} sent={$sent}");
        }

        return self::SUCCESS;
    }
}



