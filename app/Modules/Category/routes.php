<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\Category\Controllers'], function () {
        Route::group(['prefix' => 'category'], function () {
            Route::get('/', 'CategoryController@index')->name('category');
            Route::get('create', 'CategoryController@create')->name('category.create');
            Route::get('edit/{id}', 'CategoryController@edit')->name('category.edit');
            Route::post('create', 'CategoryController@store')->name('category.store');
            Route::post('edit', 'CategoryController@update')->name('category.update');
            Route::post('delete', 'CategoryController@delete')->name('category.delete');
            Route::post('status', 'CategoryController@status')->name('category.status');
            Route::post('action', 'CategoryController@action')->name('category.action');
            Route::get('sort', 'CategoryController@sort')->name('category.sort');
            Route::post('tree', 'CategoryController@tree')->name('category.tree');
        });
    });
});
