<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\GroupShowroom\Controllers'], function () {
        Route::group(['prefix' => 'groupshowroom'], function () {
            Route::get('/', 'GroupShowroomController@index');
            Route::get('create', 'GroupShowroomController@create');
            Route::get('edit/{id}', 'GroupShowroomController@edit');
            Route::post('create', 'GroupShowroomController@store');
            Route::post('edit', 'GroupShowroomController@update');
            Route::post('delete', 'GroupShowroomController@delete');
            Route::post('status', 'GroupShowroomController@status');
            Route::post('action', 'GroupShowroomController@action');
            Route::post('sort', 'GroupShowroomController@sort');
        });
    });
});
