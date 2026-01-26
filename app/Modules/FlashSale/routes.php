<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\FlashSale\Controllers'], function () {
        Route::group(['prefix' => 'flashsale'], function () {
            Route::get('/', 'FlashSaleController@index')->name('flashsale');
            Route::get('create', 'FlashSaleController@create')->name('flashsale.create');
            Route::get('edit/{id}', 'FlashSaleController@edit')->name('flashsale.edit');
            Route::post('create', 'FlashSaleController@store')->name('flashsale.store');
            Route::post('edit', 'FlashSaleController@update')->name('flashsale.update');
            Route::post('delete', 'FlashSaleController@delete')->name('flashsale.delete');
            Route::post('status', 'FlashSaleController@status')->name('flashsale.status');
            Route::post('action', 'FlashSaleController@action')->name('flashsale.action');
            Route::post('sort', 'FlashSaleController@sort')->name('flashsale.sort');
            Route::post('chose-product', 'FlashSaleController@choseProduct')->name('flashsale.chose_product');
            Route::post('search-product', 'FlashSaleController@searchProduct')->name('flashsale.search_product');
        });
    });
});
