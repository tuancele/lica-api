<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\Pick\Controllers'], function () {
        Route::group(['prefix' => 'pick'], function () {
            Route::get('/', 'PickController@index')->name('pick');
            Route::get('create', 'PickController@create')->name('pick.create');
            Route::get('edit/{id}', 'PickController@edit')->name('pick.edit');
            Route::post('create', 'PickController@store')->name('pick.store');
            Route::post('edit', 'PickController@update')->name('pick.update');
            Route::post('delete', 'PickController@delete')->name('pick.delete');
            Route::post('status', 'PickController@status')->name('pick.status');
            Route::post('action', 'PickController@action')->name('pick.action');
            Route::post('sort', 'PickController@sort')->name('pick.sort');

            Route::get('district/{id}', 'PickController@district')->name('pick.district');
            Route::get('ward/{id}', 'PickController@ward')->name('pick.ward');
        });
    });
});
