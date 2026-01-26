<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\Selling\Controllers'], function () {
        Route::group(['prefix' => 'selling'], function () {
            Route::get('/', 'SellingController@index');
            Route::get('create', 'SellingController@create');
            Route::get('edit/{id}', 'SellingController@edit');
            Route::post('create', 'SellingController@store');
            Route::post('edit', 'SellingController@update');
            Route::post('delete', 'SellingController@delete');
            Route::post('status', 'SellingController@status');
            Route::post('action', 'SellingController@action');
            Route::post('sort', 'SellingController@sort');
        });
    });
});
