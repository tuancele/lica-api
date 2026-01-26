<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\Brand\Controllers'], function () {
        Route::group(['prefix' => 'brand'], function () {
            Route::get('/', 'BrandController@index')->name('brand');
            Route::get('create', 'BrandController@create')->name('brand.create');
            Route::get('edit/{id}', 'BrandController@edit')->name('brand.edit');
            Route::post('create', 'BrandController@store')->name('brand.store');
            Route::post('edit', 'BrandController@update')->name('brand.update');
            Route::post('delete', 'BrandController@delete')->name('brand.delete');
            Route::post('status', 'BrandController@status')->name('brand.status');
            Route::post('action', 'BrandController@action')->name('brand.action');
            Route::post('upload', 'BrandController@upload')->name('brand.upload');
        });
    });
});
