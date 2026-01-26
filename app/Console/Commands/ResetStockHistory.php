<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetStockHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:reset-history 
                            {--confirm : Skip confirmation prompt}
                            {--dry-run : Show what would be done without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset stock history (TRUNCATE stock_receipts, stock_receipt_items, stock_movements, stock_reservations) and reset all inventory_stocks quantities to 0';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('⚠️  CẢNH BÁO: Lệnh này sẽ XÓA VĨNH VIỄN tất cả dữ liệu lịch sử kho!');
        $this->line('');

        if ($this->option('dry-run')) {
            $this->info('🔍 DRY-RUN MODE: Chỉ hiển thị những gì sẽ được thực hiện');
            $this->showSummary();

            return 0;
        }

        if (! $this->option('confirm')) {
            if (! $this->confirm('Bạn có chắc chắn muốn tiếp tục?', false)) {
                $this->info('Đã hủy.');

                return 0;
            }
        }

        try {
            $this->info('🔄 Đang reset lịch sử kho...');

            // Step 1: Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');

            // Step 2: TRUNCATE history tables (DDL command, cannot be in transaction)
            $this->line('  → Truncating stock_receipts...');
            DB::table('stock_receipts')->truncate();

            $this->line('  → Truncating stock_receipt_items...');
            DB::table('stock_receipt_items')->truncate();

            $this->line('  → Truncating stock_movements...');
            DB::table('stock_movements')->truncate();

            $this->line('  → Truncating stock_reservations...');
            DB::table('stock_reservations')->truncate();

            // Step 3: Reset inventory_stocks quantities (use transaction for this)
            $this->line('  → Resetting inventory_stocks quantities...');
            DB::beginTransaction();
            try {
                $updated = DB::table('inventory_stocks')->update([
                    'physical_stock' => 0,
                    'reserved_stock' => 0,
                    'flash_sale_hold' => 0,
                    'deal_hold' => 0,
                    'average_cost' => 0.00,
                    'last_cost' => 0.00,
                    'last_stock_check' => null,
                    'last_movement_at' => null,
                    'updated_at' => now(),
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            // Step 4: Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            // Verify results
            $summary = $this->getSummary();

            $this->info('✅ Reset thành công!');
            $this->line('');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Records updated', number_format($updated)],
                    ['Total physical_stock', number_format($summary['total_physical'])],
                    ['Total reserved_stock', number_format($summary['total_reserved'])],
                    ['Total flash_sale_hold', number_format($summary['total_flash_sale_hold'])],
                    ['Total deal_hold', number_format($summary['total_deal_hold'])],
                ]
            );

            Log::info('Stock history reset completed', [
                'updated_records' => $updated,
                'summary' => $summary,
            ]);

            return 0;
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            $this->error('❌ Lỗi khi reset: '.$e->getMessage());
            Log::error('Stock history reset failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Show summary of what would be affected.
     */
    private function showSummary(): void
    {
        $summary = $this->getSummary();

        $receiptsCount = DB::table('stock_receipts')->count();
        $receiptItemsCount = DB::table('stock_receipt_items')->count();
        $movementsCount = DB::table('stock_movements')->count();
        $reservationsCount = DB::table('stock_reservations')->count();

        $this->line('');
        $this->info('📊 Tóm tắt dữ liệu sẽ bị xóa:');
        $this->table(
            ['Table', 'Records'],
            [
                ['stock_receipts', number_format($receiptsCount)],
                ['stock_receipt_items', number_format($receiptItemsCount)],
                ['stock_movements', number_format($movementsCount)],
                ['stock_reservations', number_format($reservationsCount)],
            ]
        );

        $this->line('');
        $this->info('📦 Tóm tắt inventory_stocks sẽ được reset:');
        $this->table(
            ['Metric', 'Current Value', 'Will be set to'],
            [
                ['Total records', number_format($summary['total_records']), 'Same'],
                ['Total physical_stock', number_format($summary['total_physical']), '0'],
                ['Total reserved_stock', number_format($summary['total_reserved']), '0'],
                ['Total flash_sale_hold', number_format($summary['total_flash_sale_hold']), '0'],
                ['Total deal_hold', number_format($summary['total_deal_hold']), '0'],
            ]
        );
    }

    /**
     * Get summary of inventory_stocks.
     */
    private function getSummary(): array
    {
        $result = DB::table('inventory_stocks')
            ->selectRaw('
                COUNT(*) as total_records,
                SUM(physical_stock) as total_physical,
                SUM(reserved_stock) as total_reserved,
                SUM(flash_sale_hold) as total_flash_sale_hold,
                SUM(deal_hold) as total_deal_hold
            ')
            ->first();

        if (! $result) {
            return [
                'total_records' => 0,
                'total_physical' => 0,
                'total_reserved' => 0,
                'total_flash_sale_hold' => 0,
                'total_deal_hold' => 0,
            ];
        }

        return [
            'total_records' => (int) ($result->total_records ?? 0),
            'total_physical' => (int) ($result->total_physical ?? 0),
            'total_reserved' => (int) ($result->total_reserved ?? 0),
            'total_flash_sale_hold' => (int) ($result->total_flash_sale_hold ?? 0),
            'total_deal_hold' => (int) ($result->total_deal_hold ?? 0),
        ];
    }
}
