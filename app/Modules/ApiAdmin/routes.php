<?php

/**
 * API Admin Routes
 * 
 * All routes are prefixed with 'admin/api' and use 'api' middleware group
 * for JSON responses and API authentication
 */
Route::group([
    // Use web + auth so Admin API can be called from backend panel with session cookie
    'middleware' => ['web', 'auth'],
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
            
            // Variant packaging dimensions
            Route::get('/{code}/packaging', 'ProductController@getVariantPackaging');
            Route::put('/{code}/packaging', 'ProductController@updateVariantPackaging');
        });
        
        // Product packaging dimensions
        Route::get('/{id}/packaging', 'ProductController@getProductPackaging');
        Route::put('/{id}/packaging', 'ProductController@updateProductPackaging');
    });

    // Brand Management Routes
    Route::prefix('brands')->group(function () {
        // List brands with pagination and filters
        Route::get('/', 'BrandController@index');
        
        // Single brand operations
        Route::get('/{id}', 'BrandController@show');
        Route::post('/', 'BrandController@store');
        Route::put('/{id}', 'BrandController@update');
        Route::delete('/{id}', 'BrandController@destroy');
        
        // Status update
        Route::patch('/{id}/status', 'BrandController@updateStatus');
        
        // Bulk operations
        Route::post('/bulk-action', 'BrandController@bulkAction');
        
        // Upload images
        Route::post('/upload', 'BrandController@upload');
    });

    // Category Management Routes
    Route::prefix('categories')->group(function () {
        // List categories with pagination and filters
        Route::get('/', 'CategoryController@index');
        
        // Single category operations
        Route::get('/{id}', 'CategoryController@show');
        Route::post('/', 'CategoryController@store');
        Route::put('/{id}', 'CategoryController@update');
        Route::delete('/{id}', 'CategoryController@destroy');
        
        // Status update
        Route::patch('/{id}/status', 'CategoryController@updateStatus');
        
        // Bulk operations
        Route::post('/bulk-action', 'CategoryController@bulkAction');
        
        // Sort and tree operations
        Route::patch('/sort', 'CategoryController@updateSort');
        Route::post('/tree', 'CategoryController@updateTree');
    });

    // Origin Management Routes
    Route::prefix('origins')->group(function () {
        Route::get('/', 'OriginController@index');
        Route::get('/{id}', 'OriginController@show');
        Route::post('/', 'OriginController@store');
        Route::put('/{id}', 'OriginController@update');
        Route::delete('/{id}', 'OriginController@destroy');
        Route::patch('/{id}/status', 'OriginController@updateStatus');
        Route::post('/bulk-action', 'OriginController@bulkAction');
        Route::post('/sort', 'OriginController@updateSort');
    });

    // Banner Management Routes
    Route::prefix('banners')->group(function () {
        Route::get('/', 'BannerController@index');
        Route::get('/{id}', 'BannerController@show');
        Route::post('/', 'BannerController@store');
        Route::put('/{id}', 'BannerController@update');
        Route::delete('/{id}', 'BannerController@destroy');
        Route::patch('/{id}/status', 'BannerController@updateStatus');
        Route::post('/bulk-action', 'BannerController@bulkAction');
        Route::post('/sort', 'BannerController@updateSort');
    });

    // Page Management Routes
    Route::prefix('pages')->group(function () {
        Route::get('/', 'PageController@index');
        Route::get('/{id}', 'PageController@show');
        Route::post('/', 'PageController@store');
        Route::put('/{id}', 'PageController@update');
        Route::delete('/{id}', 'PageController@destroy');
        Route::patch('/{id}/status', 'PageController@updateStatus');
        Route::post('/bulk-action', 'PageController@bulkAction');
    });

    // Marketing Campaign Management Routes
    Route::prefix('marketing/campaigns')->group(function () {
        Route::get('/', 'MarketingCampaignController@index');
        Route::get('/{id}', 'MarketingCampaignController@show');
        Route::post('/', 'MarketingCampaignController@store');
        Route::put('/{id}', 'MarketingCampaignController@update');
        Route::delete('/{id}', 'MarketingCampaignController@destroy');
        Route::patch('/{id}/status', 'MarketingCampaignController@updateStatus');
        Route::post('/{id}/products', 'MarketingCampaignController@addProducts');
        Route::delete('/{id}/products/{productId}', 'MarketingCampaignController@removeProduct');
        Route::post('/search-products', 'MarketingCampaignController@searchProducts');
    });

    // Promotion Management Routes
    Route::prefix('promotions')->group(function () {
        Route::get('/', 'PromotionController@index');
        Route::get('/{id}', 'PromotionController@show');
        Route::post('/', 'PromotionController@store');
        Route::put('/{id}', 'PromotionController@update');
        Route::delete('/{id}', 'PromotionController@destroy');
        Route::patch('/{id}/status', 'PromotionController@updateStatus');
        Route::post('/bulk-action', 'PromotionController@bulkAction');
        Route::post('/sort', 'PromotionController@updateSort');
    });

    // User Management Routes
    Route::prefix('users')->group(function () {
        Route::get('/', 'UserController@index');
        Route::get('/{id}', 'UserController@show');
        Route::post('/', 'UserController@store');
        Route::put('/{id}', 'UserController@update');
        Route::delete('/{id}', 'UserController@destroy');
        Route::patch('/{id}/status', 'UserController@updateStatus');
        Route::post('/{id}/change-password', 'UserController@changePassword');
        Route::post('/check-email', 'UserController@checkEmail');
    });

    // Member Management Routes
    Route::prefix('members')->group(function () {
        Route::get('/', 'MemberController@index');
        Route::get('/{id}', 'MemberController@show');
        Route::post('/', 'MemberController@store');
        Route::put('/{id}', 'MemberController@update');
        Route::delete('/{id}', 'MemberController@destroy');
        Route::patch('/{id}/status', 'MemberController@updateStatus');
        Route::post('/{id}/addresses', 'MemberController@addAddress');
        Route::put('/{id}/addresses/{addressId}', 'MemberController@updateAddress');
        Route::delete('/{id}/addresses/{addressId}', 'MemberController@deleteAddress');
        Route::post('/{id}/change-password', 'MemberController@changePassword');
    });

    // Pick (Warehouse Location) Management Routes
    Route::prefix('picks')->group(function () {
        Route::get('/', 'PickController@index');
        Route::get('/{id}', 'PickController@show');
        Route::post('/', 'PickController@store');
        Route::put('/{id}', 'PickController@update');
        Route::delete('/{id}', 'PickController@destroy');
        Route::patch('/{id}/status', 'PickController@updateStatus');
        Route::post('/bulk-action', 'PickController@bulkAction');
        Route::post('/sort', 'PickController@updateSort');
        Route::get('/{id}/district/{districtId}', 'PickController@getDistrict');
        Route::get('/{id}/ward/{wardId}', 'PickController@getWard');
    });

    // Role & Permission Management Routes
    Route::prefix('roles')->group(function () {
        Route::get('/', 'RoleController@index');
        Route::get('/{id}', 'RoleController@show');
        Route::post('/', 'RoleController@store');
        Route::put('/{id}', 'RoleController@update');
        Route::delete('/{id}', 'RoleController@destroy');
        Route::post('/{id}/permissions', 'RoleController@assignPermissions');
    });
    Route::get('/permissions', 'RoleController@getPermissions');

    // Setting Management Routes
    Route::prefix('settings')->group(function () {
        Route::get('/', 'SettingController@index');
        Route::get('/{key}', 'SettingController@show');
        Route::put('/', 'SettingController@update');
        Route::put('/{key}', 'SettingController@updateSetting');
    });

    // Contact Management Routes
    Route::prefix('contacts')->group(function () {
        Route::get('/', 'ContactController@index');
        Route::get('/{id}', 'ContactController@show');
        Route::delete('/{id}', 'ContactController@destroy');
        Route::patch('/{id}/status', 'ContactController@updateStatus');
    });

    // Feedback Management Routes
    Route::prefix('feedbacks')->group(function () {
        Route::get('/', 'FeedbackController@index');
        Route::get('/{id}', 'FeedbackController@show');
        Route::delete('/{id}', 'FeedbackController@destroy');
        Route::patch('/{id}/status', 'FeedbackController@updateStatus');
    });

    // Subscriber Management Routes
    Route::prefix('subscribers')->group(function () {
        Route::get('/', 'SubscriberController@index');
        Route::post('/', 'SubscriberController@store');
        Route::delete('/{id}', 'SubscriberController@destroy');
        Route::post('/export', 'SubscriberController@export');
    });

    // Tag Management Routes
    Route::prefix('tags')->group(function () {
        Route::get('/', 'TagController@index');
        Route::post('/', 'TagController@store');
        Route::put('/{id}', 'TagController@update');
        Route::delete('/{id}', 'TagController@destroy');
    });

    // Post Management Routes
    Route::prefix('posts')->group(function () {
        Route::get('/', 'PostController@index');
        Route::get('/{id}', 'PostController@show');
        Route::post('/', 'PostController@store');
        Route::put('/{id}', 'PostController@update');
        Route::delete('/{id}', 'PostController@destroy');
        Route::patch('/{id}/status', 'PostController@updateStatus');
    });

    // Video Management Routes
    Route::prefix('videos')->group(function () {
        Route::get('/', 'VideoController@index');
        Route::get('/{id}', 'VideoController@show');
        Route::post('/', 'VideoController@store');
        Route::put('/{id}', 'VideoController@update');
        Route::delete('/{id}', 'VideoController@destroy');
        Route::patch('/{id}/status', 'VideoController@updateStatus');
    });

    // Rate Management Routes
    Route::prefix('rates')->group(function () {
        Route::get('/', 'RateController@index');
        Route::get('/{id}', 'RateController@show');
        Route::delete('/{id}', 'RateController@destroy');
        Route::patch('/{id}/status', 'RateController@updateStatus');
    });

    // Dashboard Routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/statistics', 'DashboardController@statistics');
        Route::get('/charts', 'DashboardController@charts');
        Route::get('/recent-orders', 'DashboardController@recentOrders');
        Route::get('/top-products', 'DashboardController@topProducts');
    });

    // Showroom Management Routes
    Route::prefix('showrooms')->group(function () {
        Route::get('/', 'ShowroomController@index');
        Route::get('/{id}', 'ShowroomController@show');
        Route::post('/', 'ShowroomController@store');
        Route::put('/{id}', 'ShowroomController@update');
        Route::delete('/{id}', 'ShowroomController@destroy');
    });

    // Menu Management Routes
    Route::prefix('menus')->group(function () {
        Route::get('/', 'MenuController@index');
        Route::get('/{id}', 'MenuController@show');
        Route::post('/', 'MenuController@store');
        Route::put('/{id}', 'MenuController@update');
        Route::delete('/{id}', 'MenuController@destroy');
        Route::post('/sort', 'MenuController@updateSort');
    });

    // Footer Block Management Routes
    Route::prefix('footer-blocks')->group(function () {
        Route::get('/', 'FooterBlockController@index');
        Route::get('/{id}', 'FooterBlockController@show');
        Route::post('/', 'FooterBlockController@store');
        Route::put('/{id}', 'FooterBlockController@update');
        Route::delete('/{id}', 'FooterBlockController@destroy');
    });

    // Redirection Management Routes
    Route::prefix('redirections')->group(function () {
        Route::get('/', 'RedirectionController@index');
        Route::get('/{id}', 'RedirectionController@show');
        Route::post('/', 'RedirectionController@store');
        Route::put('/{id}', 'RedirectionController@update');
        Route::delete('/{id}', 'RedirectionController@destroy');
    });

    // Selling Management Routes
    Route::prefix('sellings')->group(function () {
        Route::get('/', 'SellingController@index');
        Route::get('/{id}', 'SellingController@show');
    });

    // Search Management Routes
    Route::prefix('search')->group(function () {
        Route::get('/logs', 'SearchController@logs');
        Route::get('/analytics', 'SearchController@analytics');
    });

    // Download Management Routes
    Route::prefix('downloads')->group(function () {
        Route::get('/', 'DownloadController@index');
        Route::get('/{id}', 'DownloadController@show');
        Route::post('/', 'DownloadController@store');
        Route::put('/{id}', 'DownloadController@update');
        Route::delete('/{id}', 'DownloadController@destroy');
    });

    // Config Management Routes
    Route::prefix('configs')->group(function () {
        Route::get('/', 'ConfigController@index');
        Route::get('/{id}', 'ConfigController@show');
        Route::post('/', 'ConfigController@store');
        Route::put('/{id}', 'ConfigController@update');
        Route::delete('/{id}', 'ConfigController@destroy');
    });

    // Compare Management Routes
    Route::prefix('compares')->group(function () {
        Route::get('/', 'CompareController@index');
        Route::get('/{id}', 'CompareController@show');
    });

    // Google Merchant Center (GMC)
    Route::prefix('gmc')->group(function () {
        Route::get('/products/preview', 'GmcController@preview');
        Route::post('/products/sync', 'GmcController@sync');
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

    // Ingredient Dictionary Management Routes
    Route::prefix('ingredients')->group(function () {
        Route::get('/', 'IngredientController@index');
        Route::get('/crawl/summary', 'IngredientController@crawlSummary');
        Route::post('/crawl/run', 'IngredientController@crawlRun');
        Route::get('/{id}', 'IngredientController@show');
        Route::post('/', 'IngredientController@store');
        Route::put('/{id}', 'IngredientController@update');
        Route::delete('/{id}', 'IngredientController@destroy');
        Route::patch('/{id}/status', 'IngredientController@updateStatus');
        Route::post('/bulk-action', 'IngredientController@bulkAction');
    });

    // Ingredient Category Management
    Route::prefix('ingredient-categories')->group(function () {
        Route::get('/', 'IngredientController@listCategories');
        Route::post('/', 'IngredientController@storeCategory');
        Route::put('/{id}', 'IngredientController@updateCategory');
        Route::delete('/{id}', 'IngredientController@deleteCategory');
        Route::patch('/{id}/status', 'IngredientController@statusCategory');
        Route::post('/bulk-action', 'IngredientController@bulkCategory');
    });

    // Ingredient Benefit Management
    Route::prefix('ingredient-benefits')->group(function () {
        Route::get('/', 'IngredientController@listBenefits');
        Route::post('/', 'IngredientController@storeBenefit');
        Route::put('/{id}', 'IngredientController@updateBenefit');
        Route::delete('/{id}', 'IngredientController@deleteBenefit');
        Route::patch('/{id}/status', 'IngredientController@statusBenefit');
        Route::post('/bulk-action', 'IngredientController@bulkBenefit');
    });

    // Ingredient Rate Management
    Route::prefix('ingredient-rates')->group(function () {
        Route::get('/', 'IngredientController@listRates');
        Route::post('/', 'IngredientController@storeRate');
        Route::put('/{id}', 'IngredientController@updateRate');
        Route::delete('/{id}', 'IngredientController@deleteRate');
        Route::patch('/{id}/status', 'IngredientController@statusRate');
        Route::post('/bulk-action', 'IngredientController@bulkRate');
    });

    // Product Taxonomy (Category) Management
    Route::prefix('taxonomies')->group(function () {
        Route::get('/', 'TaxonomyController@index');
        Route::get('/{id}', 'TaxonomyController@show');
        Route::post('/', 'TaxonomyController@store');
        Route::put('/{id}', 'TaxonomyController@update');
        Route::delete('/{id}', 'TaxonomyController@destroy');
        Route::patch('/{id}/status', 'TaxonomyController@updateStatus');
        Route::post('/bulk-action', 'TaxonomyController@bulkAction');
        Route::patch('/sort', 'TaxonomyController@updateSort');
    });

    // Warehouse Management Routes (V1)
    Route::prefix('v1/warehouse')->group(function () {
        // Inventory Management
        Route::get('/inventory', 'WarehouseController@getInventory');
        Route::get('/inventory/{variantId}', 'WarehouseController@getVariantInventory');
        Route::get('/inventory/by-product/{productId}', 'WarehouseController@inventoryByProduct');
        
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

    // Warehouse Accounting Routes (V2)
    Route::prefix('v2/warehouse/accounting')->group(function () {
        // Receipts Management
        Route::prefix('receipts')->group(function () {
            Route::get('/', 'WarehouseAccountingController@index');
            Route::get('/{id}', 'WarehouseAccountingController@show');
            Route::post('/', 'WarehouseAccountingController@store');
            Route::put('/{id}', 'WarehouseAccountingController@update');
            Route::post('/{id}/complete', 'WarehouseAccountingController@complete');
            Route::post('/{id}/void', 'WarehouseAccountingController@void');
        });
        
        // Statistics
        Route::get('/statistics', 'WarehouseAccountingController@statistics');
    });
});
