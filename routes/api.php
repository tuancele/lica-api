<?php

declare(strict_types=1);
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// 推荐系统API
Route::prefix('recommendations')->group(function () {
    Route::get('/', 'Api\RecommendationController@getRecommendations');
    Route::post('/track', 'Api\RecommendationController@trackBehavior');
    Route::get('/history', 'Api\RecommendationController@getViewHistory');
});

// 推荐系统API
Route::prefix('recommendations')->group(function () {
    Route::get('/', 'Api\RecommendationController@getRecommendations');
    Route::post('/track', 'Api\RecommendationController@trackBehavior');
    Route::get('/history', 'Api\RecommendationController@getViewHistory');
});

// 数据分析API（用于AI训练和分析）
Route::prefix('analytics')->group(function () {
    Route::get('/user-history', 'Api\AnalyticsController@getUserHistory');
    Route::get('/user-preferences', 'Api\AnalyticsController@getUserPreferences');
    Route::get('/export-ai', 'Api\AnalyticsController@exportForAI');
    Route::get('/product-ingredients', 'Api\AnalyticsController@getProductIngredientAnalysis');
});

// 分类API - RESTful 标准
Route::prefix('categories')->group(function () {
    Route::get('/', 'Api\CategoryController@index'); // GET /api/categories
    Route::get('/featured', 'Api\CategoryController@getFeaturedCategories'); // GET /api/categories/featured
    Route::get('/hierarchical', 'Api\CategoryController@hierarchical'); // GET /api/categories/hierarchical
    Route::get('/{id}', 'Api\CategoryController@show'); // GET /api/categories/{id}
});

// 产品API - 用于首页和公开页面
Route::prefix('products')->group(function () {
    Route::get('/top-selling', 'Api\ProductController@getTopSelling'); // GET /api/products/top-selling
    Route::get('/by-category/{id}', 'Api\ProductController@getByCategory'); // GET /api/products/by-category/{id}
    Route::get('/flash-sale', 'Api\ProductController@getFlashSale'); // GET /api/products/flash-sale
    Route::get('/{slug}/detail', 'Api\ProductController@getDetailBySlug'); // GET /api/products/{slug}/detail
    Route::get('/{id}/price-info', 'Api\ProductController@getPriceInfo'); // GET /api/products/{id}/price-info
});

// Brand API V1 - RESTful 标准
Route::prefix('v1/brands')->namespace('Api\V1')->group(function () {
    Route::get('/featured', 'BrandController@getFeatured'); // GET /api/v1/brands/featured (for home page)
    Route::get('/', 'BrandController@index'); // GET /api/v1/brands
    Route::get('/options', 'BrandController@options'); // GET /api/v1/brands/options (for select options)
    Route::get('/{slug}', 'BrandController@show'); // GET /api/v1/brands/{slug}
    Route::get('/{slug}/products', 'BrandController@getProducts'); // GET /api/v1/brands/{slug}/products
    Route::get('/{slug}/products/available', 'BrandController@getAvailableProducts'); // GET /api/v1/brands/{slug}/products/available
    Route::get('/{slug}/products/out-of-stock', 'BrandController@getOutOfStockProducts'); // GET /api/v1/brands/{slug}/products/out-of-stock
});

// Origin API V1 - RESTful 标准
Route::prefix('v1/origins')->namespace('Api\V1')->group(function () {
    Route::get('/options', 'OriginController@options'); // GET /api/v1/origins/options (for select options)
});

// Media API V1 - upload to Cloudflare R2
Route::prefix('v1/media')->namespace('Api\V1')->group(function () {
    Route::post('/upload', 'MediaController@upload'); // POST /api/v1/media/upload
});

// Flash Sale API V1 - RESTful 标准
Route::prefix('v1/flash-sales')->namespace('Api\V1')->group(function () {
    Route::get('/active', 'FlashSaleController@getActive'); // GET /api/v1/flash-sales/active
    Route::get('/{id}/products', 'FlashSaleController@getProducts'); // GET /api/v1/flash-sales/{id}/products
});

// Product API V1 - RESTful 标准
Route::prefix('v1/products')->namespace('Api\V1')->group(function () {
    Route::get('/{slug}', 'ProductController@show'); // GET /api/v1/products/{slug}
});

// Deal Shock API V1 - bundles for frontend
Route::prefix('v1/deals')->namespace('Api\V1')->group(function () {
    Route::get('/active-bundles', 'DealController@getActiveBundles'); // GET /api/v1/deals/active-bundles
    Route::get('/{id}/bundle', 'DealController@showBundle'); // GET /api/v1/deals/{id}/bundle
});

// Cart API V1 - RESTful 标准
// IMPORTANT: These routes need session support, so we use web middleware group
// This ensures session sharing between web and API routes
Route::prefix('v1/cart')->namespace('Api\V1')->middleware('web')->group(function () {
    Route::get('/', 'CartController@index'); // GET /api/v1/cart
    Route::get('/gio-hang', 'CartController@getCartPage'); // GET /api/v1/cart/gio-hang - Full cart page data
    Route::post('/items', 'CartController@addItem'); // POST /api/v1/cart/items
    Route::put('/items/{variant_id}', 'CartController@updateItem'); // PUT /api/v1/cart/items/{variant_id}
    Route::delete('/items/{variant_id}', 'CartController@removeItem'); // DELETE /api/v1/cart/items/{variant_id}
    Route::post('/coupon/apply', 'CartController@applyCoupon'); // POST /api/v1/cart/coupon/apply
    Route::delete('/coupon', 'CartController@removeCoupon'); // DELETE /api/v1/cart/coupon
    Route::post('/shipping-fee', 'CartController@calculateShippingFee'); // POST /api/v1/cart/shipping-fee
    Route::post('/checkout', 'CartController@checkout'); // POST /api/v1/cart/checkout
});

// Slider API V1 - RESTful 标准
Route::prefix('v1/sliders')->namespace('Api\V1')->group(function () {
    Route::get('/', 'SliderController@index'); // GET /api/v1/sliders
});

// Order API V1 - RESTful 标准 (User Orders)
// Requires member authentication
Route::prefix('v1/orders')->namespace('Api\V1')->middleware(['web', 'auth:member'])->group(function () {
    Route::get('/', 'OrderController@index'); // GET /api/v1/orders
    Route::get('/{code}', 'OrderController@show'); // GET /api/v1/orders/{code}
});

// Price & Order Processing API
Route::prefix('price')->group(function () {
    Route::get('/{productId}', 'OrderProcessingController@getPrice'); // GET /api/price/{productId}
    Route::post('/calculate', 'OrderProcessingController@calculatePrice'); // POST /api/price/calculate
});

Route::prefix('orders')->group(function () {
    Route::post('/process', 'OrderProcessingController@processOrder'); // POST /api/orders/process
});

require __DIR__.'/api_inventory.php';
