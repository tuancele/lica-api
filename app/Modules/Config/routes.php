<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\Config\Controllers'], function () {
        Route::group(['prefix' => 'config'], function () {
            Route::get('/', 'ConfigController@index');
            Route::post('update', 'ConfigController@update');
        });
    });
});
