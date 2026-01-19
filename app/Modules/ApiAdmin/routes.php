<?php

/**
 * API Admin Routes
 * 
 * All routes are prefixed with 'admin/api' and use 'api' middleware group
 * for JSON responses and API authentication
 */
Route::group([
    'middleware' => ['api', 'auth:api'],
    'prefix' => 'admin/api',
    'namespace' => 'App\Modules\ApiAdmin\Controllers'
], function () {
    
    // Product Management Routes
    Route::prefix('products')->group(function () {
        // List products with pagination and filters
        Route::get('/', 'ProductController@index');
        
        // Single product operations
        Route::get('/{id}', 'ProductController@show');
        Route::post('/', 'ProductController@store');
        Route::put('/{id}', 'ProductController@update');
        Route::delete('/{id}', 'ProductController@destroy');
        
        // Status update
        Route::patch('/{id}/status', 'ProductController@updateStatus');
        
        // Bulk operations
        Route::post('/bulk-action', 'ProductController@bulkAction');
        
        // Sort update
        Route::patch('/sort', 'ProductController@updateSort');
        
        // Variant management (nested routes)
        Route::prefix('{id}/variants')->group(function () {
            Route::get('/', 'ProductController@getVariants');
            Route::get('/{code}', 'ProductController@getVariant');
            Route::post('/', 'ProductController@createVariant');
            Route::put('/{code}', 'ProductController@updateVariant');
            Route::delete('/{code}', 'ProductController@deleteVariant');
        });
    });

    // Flash Sale Management Routes
    Route::prefix('flash-sales')->group(function () {
        // List Flash Sales with pagination and filters
        Route::get('/', 'FlashSaleController@index');
        
        // Single Flash Sale operations
        Route::get('/{id}', 'FlashSaleController@show');
        Route::post('/', 'FlashSaleController@store');
        Route::put('/{id}', 'FlashSaleController@update');
        Route::delete('/{id}', 'FlashSaleController@destroy');
        
        // Status update
        Route::post('/{id}/status', 'FlashSaleController@updateStatus');
        
        // Search products
        Route::post('/search-products', 'FlashSaleController@searchProducts');
    });

    // Order Management Routes
    Route::prefix('orders')->group(function () {
        // List orders with pagination and filters
        Route::get('/', 'OrderController@index');
        
        // Single order operations
        Route::get('/{id}', 'OrderController@show');
        Route::put('/{id}', 'OrderController@update');
        
        // Status update
        Route::patch('/{id}/status', 'OrderController@updateStatus');
    });

    // Slider Management Routes
    Route::prefix('sliders')->group(function () {
        // List sliders with pagination and filters
        Route::get('/', 'SliderController@index');
        
        // Single slider operations
        Route::get('/{id}', 'SliderController@show');
        Route::post('/', 'SliderController@store');
        Route::put('/{id}', 'SliderController@update');
        Route::delete('/{id}', 'SliderController@destroy');
        
        // Status update
        Route::patch('/{id}/status', 'SliderController@updateStatus');
    });

    // Deal Management Routes
    Route::prefix('deals')->group(function () {
        // List deals with pagination and filters
        Route::get('/', 'DealController@index');
        
        // Single deal operations
        Route::get('/{id}', 'DealController@show');
        Route::post('/', 'DealController@store');
        Route::put('/{id}', 'DealController@update');
        Route::delete('/{id}', 'DealController@destroy');
        
        // Status update
        Route::patch('/{id}/status', 'DealController@updateStatus');
    });

    // Warehouse Management Routes (V1)
    Route::prefix('v1/warehouse')->group(function () {
        // Inventory Management
        Route::get('/inventory', 'WarehouseController@getInventory');
        Route::get('/inventory/{variantId}', 'WarehouseController@getVariantInventory');
        
        // Import Receipts Management
        Route::prefix('import-receipts')->group(function () {
            Route::get('/', 'WarehouseController@getImportReceipts');
            Route::get('/{id}', 'WarehouseController@getImportReceipt');
            Route::post('/', 'WarehouseController@createImportReceipt');
            Route::put('/{id}', 'WarehouseController@updateImportReceipt');
            Route::delete('/{id}', 'WarehouseController@deleteImportReceipt');
            Route::get('/{id}/print', 'WarehouseController@getImportReceiptPrint');
        });
        
        // Export Receipts Management
        Route::prefix('export-receipts')->group(function () {
            Route::get('/', 'WarehouseController@getExportReceipts');
            Route::get('/{id}', 'WarehouseController@getExportReceipt');
            Route::post('/', 'WarehouseController@createExportReceipt');
            Route::put('/{id}', 'WarehouseController@updateExportReceipt');
            Route::delete('/{id}', 'WarehouseController@deleteExportReceipt');
            Route::get('/{id}/print', 'WarehouseController@getExportReceiptPrint');
        });
        
        // Supporting Endpoints
        Route::get('/products/search', 'WarehouseController@searchProducts');
        Route::get('/products/{productId}/variants', 'WarehouseController@getProductVariants');
        Route::get('/variants/{variantId}/stock', 'WarehouseController@getVariantStock');
        Route::get('/variants/{variantId}/price', 'WarehouseController@getVariantPrice');
        
        // Statistics
        Route::prefix('statistics')->group(function () {
            Route::get('/quantity', 'WarehouseController@getQuantityStatistics');
            Route::get('/revenue', 'WarehouseController@getRevenueStatistics');
            Route::get('/summary', 'WarehouseController@getSummaryStatistics');
        });
    });
});
