<?php

namespace App\Console\Commands;

use App\Models\InventoryStock;
use App\Models\WarehouseV2;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncInventoryStocks extends Command
{
    protected $signature = 'inventory:sync-stocks
        {--warehouse_id= : Target warehouse_id (default: default warehouse)}
        {--chunk=1000 : Chunk size for scanning variants}
        {--dry-run : Show counts only, do not write}
        {--force : Write changes without confirmation}';

    protected $description = 'Ensure inventory_stocks rows exist for all variants (creates missing rows with zero stock).';

    public function handle(): int
    {
        $warehouseId = $this->option('warehouse_id') ? (int) $this->option('warehouse_id') : null;
        $warehouse = $warehouseId ? WarehouseV2::find($warehouseId) : WarehouseV2::getDefault();

        if (!$warehouse) {
            $this->error('Warehouse not found. Please create a default warehouse or pass --warehouse_id.');
            return 1;
        }

        $chunk = max(100, (int) ($this->option('chunk') ?? 1000));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $totalVariants = (int) DB::table('variants')->count();
        $existingStocks = (int) InventoryStock::where('warehouse_id', $warehouse->id)->count();

        $this->info('Target warehouse: ' . $warehouse->id . ' (' . ($warehouse->code ?? 'N/A') . ')');
        $this->info('Total variants: ' . $totalVariants);
        $this->info('Existing inventory_stocks rows (warehouse): ' . $existingStocks);

        if (!$dryRun && !$force) {
            $confirm = $this->confirm('Create missing inventory_stocks rows with zero stock?', true);
            if (!$confirm) {
                $this->comment('Cancelled.');
                return 0;
            }
        }

        $missing = 0;

        DB::table('variants')
            ->select('id')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($warehouse, $dryRun, &$missing) {
                $variantIds = $rows->pluck('id')->map(fn($v) => (int) $v)->all();
                if (empty($variantIds)) {
                    return;
                }

                $existing = InventoryStock::where('warehouse_id', $warehouse->id)
                    ->whereIn('variant_id', $variantIds)
                    ->pluck('variant_id')
                    ->map(fn($v) => (int) $v)
                    ->all();

                $existingMap = array_fill_keys($existing, true);
                $toInsert = [];

                foreach ($variantIds as $variantId) {
                    if (isset($existingMap[$variantId])) {
                        continue;
                    }
                    $missing++;
                    if ($dryRun) {
                        continue;
                    }
                    $toInsert[] = [
                        'warehouse_id' => $warehouse->id,
                        'variant_id' => $variantId,
                        'physical_stock' => 0,
                        'reserved_stock' => 0,
                        'flash_sale_hold' => 0,
                        'deal_hold' => 0,
                        'low_stock_threshold' => 10,
                        'reorder_point' => 20,
                        'average_cost' => 0,
                        'last_cost' => 0,
                        'location_code' => null,
                        'last_stock_check' => null,
                        'last_movement_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!$dryRun && !empty($toInsert)) {
                    InventoryStock::insert($toInsert);
                }
            });

        $this->info('Missing rows detected: ' . $missing);

        if ($dryRun) {
            $this->comment('Dry-run mode: no changes were written.');
            return 0;
        }

        $this->info('Sync completed.');
        $this->info('New inventory_stocks rows (warehouse): ' . InventoryStock::where('warehouse_id', $warehouse->id)->count());

        return 0;
    }
}


