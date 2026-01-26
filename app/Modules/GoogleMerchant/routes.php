<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\GoogleMerchant\Controllers'], function () {
        Route::group(['prefix' => 'google-merchant'], function () {
            Route::get('/', 'GoogleMerchantController@index')->name('google-merchant.index');
            Route::post('sync', 'GoogleMerchantController@sync')->name('google-merchant.sync');
            Route::get('status', 'GoogleMerchantController@getStatus')->name('google-merchant.status');
            Route::post('batch-status', 'GoogleMerchantController@batchStatus')->name('google-merchant.batch-status');
        });
    });
});
