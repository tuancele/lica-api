<?php

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

// 数据分析API（用于AI训练和分析）
Route::prefix('analytics')->group(function () {
    Route::get('/user-history', 'Api\AnalyticsController@getUserHistory');
    Route::get('/user-preferences', 'Api\AnalyticsController@getUserPreferences');
    Route::get('/export-ai', 'Api\AnalyticsController@exportForAI');
    Route::get('/product-ingredients', 'Api\AnalyticsController@getProductIngredientAnalysis');
});
