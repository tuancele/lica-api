<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\Tag\Controllers'], function () {
        Route::group(['prefix' => 'tag'], function () {
            Route::get('/', 'TagController@index');
            Route::get('get', 'TagController@getData');
            Route::get('create', 'TagController@create');
            Route::get('edit/{id}', 'TagController@edit');
            Route::post('create', 'TagController@store');
            Route::post('edit', 'TagController@update');
            Route::post('delete', 'TagController@delete');
            Route::post('status', 'TagController@status');
            Route::post('action', 'TagController@action');
        });
    });
});
