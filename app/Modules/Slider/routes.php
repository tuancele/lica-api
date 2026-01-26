<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\Slider\Controllers'], function () {
        Route::group(['prefix' => 'slider'], function () {
            Route::get('/', 'SliderController@index')->name('slider');
            Route::get('create', 'SliderController@create')->name('slider.create');
            Route::get('edit/{id}', 'SliderController@edit')->name('slider.edit');
            Route::post('create', 'SliderController@store')->name('slider.store');
            Route::post('edit', 'SliderController@update')->name('slider.update');
            Route::post('delete', 'SliderController@delete')->name('slider.delete');
            Route::post('status', 'SliderController@status')->name('slider.status');
            Route::post('action', 'SliderController@action')->name('slider.action');
            Route::post('sort', 'SliderController@sort')->name('slider.sort');
        });
    });
});
