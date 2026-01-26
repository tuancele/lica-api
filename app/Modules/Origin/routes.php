<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\Origin\Controllers'], function () {
        Route::group(['prefix' => 'origin'], function () {
            Route::get('/', 'OriginController@index')->name('origin');
            Route::get('create', 'OriginController@create')->name('origin.create');
            Route::get('edit/{id}', 'OriginController@edit')->name('origin.edit');
            Route::post('create', 'OriginController@store')->name('origin.store');
            Route::post('edit', 'OriginController@update')->name('origin.update');
            Route::post('delete', 'OriginController@delete')->name('origin.delete');
            Route::post('status', 'OriginController@status')->name('origin.status');
            Route::post('action', 'OriginController@action')->name('origin.action');
            Route::post('sort', 'OriginController@sort')->name('origin.sort');
        });
    });
});
