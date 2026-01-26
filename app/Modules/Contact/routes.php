<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\Contact\Controllers'], function () {
        Route::group(['prefix' => 'contact'], function () {
            Route::get('/', 'ContactController@index');
            Route::get('view/{id}', 'ContactController@view');
            Route::post('delete', 'ContactController@delete');
            Route::post('action', 'ContactController@action');
        });
    });
});
