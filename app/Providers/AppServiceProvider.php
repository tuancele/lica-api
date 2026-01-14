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
    }
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::defaultView('pagination.default');
    }
}
