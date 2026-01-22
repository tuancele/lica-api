<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use App\Modules\Config\Models\Config;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Bind Repository Interfaces
        $this->app->bind(
            \App\Repositories\Product\ProductRepositoryInterface::class,
            \App\Repositories\Product\ProductRepository::class
        );

        // Bind Service Interfaces
        $this->app->bind(
            \App\Services\Image\ImageServiceInterface::class,
            \App\Services\Image\ImageService::class
        );

        $this->app->bind(
            \App\Services\Product\ProductServiceInterface::class,
            \App\Services\Product\ProductService::class
        );

        $this->app->bind(
            \App\Services\Warehouse\WarehouseServiceInterface::class,
            \App\Services\Warehouse\WarehouseService::class
        );

        $this->app->singleton(
            \App\Services\Warehouse\StockReceiptService::class,
            function ($app) {
                return new \App\Services\Warehouse\StockReceiptService(
                    $app->make(\App\Services\Inventory\Contracts\InventoryServiceInterface::class),
                    $app->make(\App\Services\Warehouse\WarehouseServiceInterface::class)
                );
            }
        );

        $this->app->bind(
            \App\Services\Promotion\ProductStockValidatorInterface::class,
            \App\Services\Promotion\ProductStockValidator::class
        );

        $this->app->bind(
            \App\Services\Pricing\PriceEngineServiceInterface::class,
            \App\Services\Pricing\PriceEngineService::class
        );

        $this->app->bind(
            \App\Services\Inventory\InventoryServiceInterface::class,
            \App\Services\Inventory\InventoryService::class
        );

        // Bind Inventory v2 contract to the same implementation (compat layer).
        $this->app->bind(
            \App\Services\Inventory\Contracts\InventoryServiceInterface::class,
            \App\Services\Inventory\InventoryService::class
        );
    }
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::defaultView('pagination.default');
        
        // Load Warehouse helper functions
        if (file_exists($helperPath = app_path('Modules/Warehouse/Helpers/helper.php'))) {
            require_once $helperPath;
        }

        // Register Flash Sale Observers
        if (class_exists(\App\Modules\FlashSale\Models\FlashSale::class)) {
            \App\Modules\FlashSale\Models\FlashSale::observe(\App\Modules\FlashSale\Observers\FlashSaleObserver::class);
        }
        
        if (class_exists(\App\Modules\FlashSale\Models\ProductSale::class)) {
            \App\Modules\FlashSale\Models\ProductSale::observe(\App\Modules\FlashSale\Observers\ProductSaleObserver::class);
        }
    }
}
