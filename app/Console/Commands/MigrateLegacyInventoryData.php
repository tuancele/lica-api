<?php

declare(strict_types=1);
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\WarehouseV2;
use App\Models\InventoryStock;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Models\StockMovement;

class MigrateLegacyInventoryData extends Command
{
    protected $signature = 'inventory:migrate-legacy-data {--force : Force migration even if data exists}';
    protected $description = 'Migrate data from old warehouse system to new inventory system';

    public function handle(): int
    {
        if (!$this->option('force') && WarehouseV2::count() > 0) {
            $this->error('Data already exists. Use --force to overwrite.');
            return 1;
        }

        $this->info('Starting migration...');

        DB::transaction(function () {
            // 1. Create default warehouse
            $this->info('Creating default warehouse...');
            $warehouse = WarehouseV2::firstOrCreate(
                ['code' => 'MAIN'],
                ['name' => 'Kho chính', 'is_default' => true, 'is_active' => true]
            );

            // 2. Calculate current stock for each variant
            $this->info('Migrating stock data...');
            $variants = DB::table('product_warehouse')
                ->select('variant_id')
                ->groupBy('variant_id')
                ->get();

            $bar = $this->output->createProgressBar($variants->count());

            foreach ($variants as $v) {
                $import = DB::table('product_warehouse')
                    ->where('variant_id', $v->variant_id)
                    ->where('type', 'import')
                    ->sum('qty');

                $export = DB::table('product_warehouse')
                    ->where('variant_id', $v->variant_id)
                    ->where('type', 'export')
                    ->sum('qty');

                $avgCost = DB::table('product_warehouse')
                    ->where('variant_id', $v->variant_id)
                    ->where('type', 'import')
                    ->avg('price') ?? 0;

                InventoryStock::updateOrCreate(
                    ['warehouse_id' => $warehouse->id, 'variant_id' => $v->variant_id],
                    [
                        'physical_stock' => max(0, $import - $export),
                        'reserved_stock' => 0,
                        'average_cost' => $avgCost,
                        'low_stock_threshold' => 10,
                    ]
                );

                // Create initial movement
                StockMovement::create([
                    'warehouse_id' => $warehouse->id,
                    'variant_id' => $v->variant_id,
                    'movement_type' => 'initial',
                    'quantity' => max(0, $import - $export),
                    'physical_before' => 0,
                    'physical_after' => max(0, $import - $export),
                    'reserved_before' => 0,
                    'reserved_after' => 0,
                    'available_before' => 0,
                    'available_after' => max(0, $import - $export),
                    'reason' => 'Migration from legacy system',
                ]);

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            // 3. Migrate receipts
            $this->info('Migrating receipts...');
            $oldReceipts = DB::table('warehouse')->orderBy('id')->get();

            foreach ($oldReceipts as $old) {
                $receipt = StockReceipt::create([
                    'receipt_code' => ($old->type === 'import' ? 'IMP' : 'EXP') . '-LEGACY-' . str_pad($old->id, 6, '0', STR_PAD_LEFT),
                    'type' => $old->type,
                    'status' => 'completed',
                    'to_warehouse_id' => $old->type === 'import' ? $warehouse->id : null,
                    'from_warehouse_id' => $old->type === 'export' ? $warehouse->id : null,
                    'subject' => $old->subject ?? 'Migrated from legacy',
                    'content' => $old->content,
                    'created_by' => $old->user_id ?? 1,
                    'created_at' => $old->created_at,
                    'completed_at' => $old->created_at,
                ]);

                $oldItems = DB::table('product_warehouse')
                    ->where('warehouse_id', $old->id)
                    ->get();

                foreach ($oldItems as $item) {
                    StockReceiptItem::create([
                        'receipt_id' => $receipt->id,
                        'variant_id' => $item->variant_id,
                        'quantity' => $item->qty,
                        'unit_price' => $item->price ?? 0,
                    ]);
                }

                $receipt->recalculateTotals();
            }
        });

        $this->info('Migration completed successfully!');
        $this->info('Stock records: ' . InventoryStock::count());
        $this->info('Receipts: ' . StockReceipt::count());

        return 0;
    }
}
