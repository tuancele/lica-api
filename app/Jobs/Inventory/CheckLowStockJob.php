<?php

declare(strict_types=1);

namespace App\Jobs\Inventory;

use App\Models\InventoryStock;
use App\Models\StockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class CheckLowStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(): void
    {
        InventoryStock::lowStock()->each(function ($stock) {
            StockAlert::createIfNotExists(
                $stock->warehouse_id,
                $stock->variant_id,
                'low_stock',
                $stock->physical_stock,
                $stock->low_stock_threshold
            );
        });
    }
}
