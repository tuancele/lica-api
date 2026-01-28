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
    Route::get('/', [App\Http\Controllers\Api\RecommendationController::class, 'getRecommendations']);
    Route::post('/track', [App\Http\Controllers\Api\RecommendationController::class, 'trackBehavior']);
    Route::get('/history', [App\Http\Controllers\Api\RecommendationController::class, 'getViewHistory']);
});

// 数据分析API（用于AI训练和分析）
Route::prefix('analytics')->group(function () {
    Route::get('/user-history', [App\Http\Controllers\Api\AnalyticsController::class, 'getUserHistory']);
    Route::get('/user-preferences', [App\Http\Controllers\Api\AnalyticsController::class, 'getUserPreferences']);
    Route::get('/export-ai', [App\Http\Controllers\Api\AnalyticsController::class, 'exportForAI']);
    Route::get('/product-ingredients', [App\Http\Controllers\Api\AnalyticsController::class, 'getProductIngredientAnalysis']);
});

// 分类API - RESTful 标准
Route::prefix('categories')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\CategoryController::class, 'index']); // GET /api/categories
    Route::get('/featured', [App\Http\Controllers\Api\CategoryController::class, 'getFeaturedCategories']); // GET /api/categories/featured
    Route::get('/hierarchical', [App\Http\Controllers\Api\CategoryController::class, 'hierarchical']); // GET /api/categories/hierarchical
    Route::get('/{id}', [App\Http\Controllers\Api\CategoryController::class, 'show']); // GET /api/categories/{id}
});

// 产品API - 用于首页和公开页面
Route::prefix('products')->group(function () {
    Route::get('/top-selling', [App\Http\Controllers\Api\ProductController::class, 'getTopSelling']); // GET /api/products/top-selling
    Route::get('/by-category/{id}', [App\Http\Controllers\Api\ProductController::class, 'getByCategory']); // GET /api/products/by-category/{id}
    Route::get('/flash-sale', [App\Http\Controllers\Api\ProductController::class, 'getFlashSale']); // GET /api/products/flash-sale
    Route::get('/{slug}/detail', [App\Http\Controllers\Api\ProductController::class, 'getDetailBySlug']); // GET /api/products/{slug}/detail
    Route::get('/{id}/price-info', [App\Http\Controllers\Api\ProductController::class, 'getPriceInfo']); // GET /api/products/{id}/price-info
});

// Brand API V1 - RESTful 标准
Route::prefix('v1/brands')->group(function () {
    Route::get('/featured', [App\Http\Controllers\Api\V1\BrandController::class, 'getFeatured']); // GET /api/v1/brands/featured (for home page)
    Route::get('/', [App\Http\Controllers\Api\V1\BrandController::class, 'index']); // GET /api/v1/brands
    Route::get('/options', [App\Http\Controllers\Api\V1\BrandController::class, 'options']); // GET /api/v1/brands/options (for select options)
    Route::get('/{slug}', [App\Http\Controllers\Api\V1\BrandController::class, 'show']); // GET /api/v1/brands/{slug}
    Route::get('/{slug}/products', [App\Http\Controllers\Api\V1\BrandController::class, 'getProducts']); // GET /api/v1/brands/{slug}/products
    Route::get('/{slug}/products/available', [App\Http\Controllers\Api\V1\BrandController::class, 'getAvailableProducts']); // GET /api/v1/brands/{slug}/products/available
    Route::get('/{slug}/products/out-of-stock', [App\Http\Controllers\Api\V1\BrandController::class, 'getOutOfStockProducts']); // GET /api/v1/brands/{slug}/products/out-of-stock
});

// Origin API V1 - RESTful 标准
Route::prefix('v1/origins')->group(function () {
    Route::get('/options', [App\Http\Controllers\Api\V1\OriginController::class, 'options']); // GET /api/v1/origins/options (for select options)
});

// Media API V1 - upload to Cloudflare R2
Route::prefix('v1/media')->group(function () {
    Route::post('/upload', [App\Http\Controllers\Api\V1\MediaController::class, 'upload']); // POST /api/v1/media/upload
});

// Flash Sale API V1 - RESTful 标准
Route::prefix('v1/flash-sales')->group(function () {
    Route::get('/active', [App\Http\Controllers\Api\V1\FlashSaleController::class, 'getActive']); // GET /api/v1/flash-sales/active
    Route::get('/{id}/products', [App\Http\Controllers\Api\V1\FlashSaleController::class, 'getProducts']); // GET /api/v1/flash-sales/{id}/products
});

// Product API V1 - RESTful 标准
Route::prefix('v1/products')->group(function () {
    Route::get('/{slug}', [App\Http\Controllers\Api\V1\ProductController::class, 'show']); // GET /api/v1/products/{slug}
});

// Deal Shock API V1 - bundles for frontend
Route::prefix('v1/deals')->group(function () {
    Route::get('/active-bundles', [App\Http\Controllers\Api\V1\DealController::class, 'getActiveBundles']); // GET /api/v1/deals/active-bundles
    Route::get('/{id}/bundle', [App\Http\Controllers\Api\V1\DealController::class, 'showBundle']); // GET /api/v1/deals/{id}/bundle
});

// Cart API V1 - RESTful 标准
// IMPORTANT: These routes need session support, so we use web middleware group
// This ensures session sharing between web and API routes
Route::prefix('v1/cart')->middleware('web')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\V1\CartController::class, 'index']); // GET /api/v1/cart
    Route::get('/gio-hang', [App\Http\Controllers\Api\V1\CartController::class, 'getCartPage']); // GET /api/v1/cart/gio-hang - Full cart page data
    Route::post('/items', [App\Http\Controllers\Api\V1\CartController::class, 'addItem']); // POST /api/v1/cart/items
    Route::put('/items/{variant_id}', [App\Http\Controllers\Api\V1\CartController::class, 'updateItem']); // PUT /api/v1/cart/items/{variant_id}
    Route::delete('/items/{variant_id}', [App\Http\Controllers\Api\V1\CartController::class, 'removeItem']); // DELETE /api/v1/cart/items/{variant_id}
    Route::post('/coupon/apply', [App\Http\Controllers\Api\V1\CartController::class, 'applyCoupon']); // POST /api/v1/cart/coupon/apply
    Route::delete('/coupon', [App\Http\Controllers\Api\V1\CartController::class, 'removeCoupon']); // DELETE /api/v1/cart/coupon
    Route::post('/shipping-fee', [App\Http\Controllers\Api\V1\CartController::class, 'calculateShippingFee']); // POST /api/v1/cart/shipping-fee
    Route::post('/checkout', [App\Http\Controllers\Api\V1\CartController::class, 'checkout']); // POST /api/v1/cart/checkout
});

// Slider API V1 - RESTful 标准
Route::prefix('v1/sliders')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\V1\SliderController::class, 'index']); // GET /api/v1/sliders
});

// Order API V1 - RESTful 标准 (User Orders)
// Requires member authentication
Route::prefix('v1/orders')->middleware(['web', 'auth:member'])->group(function () {
    Route::get('/', [App\Http\Controllers\Api\V1\OrderController::class, 'index']); // GET /api/v1/orders
    Route::get('/{code}', [App\Http\Controllers\Api\V1\OrderController::class, 'show']); // GET /api/v1/orders/{code}
});

// Price & Order Processing API
Route::prefix('price')->group(function () {
    Route::get('/{productId}', [App\Http\Controllers\OrderProcessingController::class, 'getPrice']); // GET /api/price/{productId}
    Route::post('/calculate', [App\Http\Controllers\OrderProcessingController::class, 'calculatePrice']); // POST /api/price/calculate
});

Route::prefix('orders')->group(function () {
    Route::post('/process', [App\Http\Controllers\OrderProcessingController::class, 'processOrder']); // POST /api/orders/process
});

require __DIR__.'/api_inventory.php';
