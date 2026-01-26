<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\Order\Controllers'], function () {
        Route::group(['prefix' => 'order'], function () {
            Route::get('/', 'OrderController@index')->name('order');
            Route::get('view/{code}', 'OrderController@view');
            Route::post('edit', 'OrderController@postUpdate');
            Route::post('delete', 'OrderController@delete')->name('order.delete');
        });
    });
});
