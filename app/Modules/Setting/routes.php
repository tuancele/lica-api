<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\Setting\Controllers'], function () {
        Route::group(['prefix' => 'setting'], function () {
            Route::get('/', 'SettingController@index');
            Route::post('update', 'SettingController@update');
        });
    });
});
