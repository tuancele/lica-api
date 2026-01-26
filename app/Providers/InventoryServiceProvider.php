<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Inventory\Contracts\InventoryServiceInterface;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/inventory.php', 'inventory');

        $this->app->singleton(InventoryServiceInterface::class, InventoryService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/inventory.php' => config_path('inventory.php'),
            ], 'inventory-config');
        }
    }
}
