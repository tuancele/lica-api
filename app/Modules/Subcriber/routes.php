<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\Subcriber\Controllers'], function () {
        Route::group(['prefix' => 'subcriber'], function () {
            Route::get('/', 'SubcriberController@index')->name('subcriber');
            Route::post('delete', 'SubcriberController@delete')->name('subcriber.delete');
        });
    });
});
