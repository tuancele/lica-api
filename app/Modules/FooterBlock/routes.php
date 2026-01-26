<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\FooterBlock\Controllers'], function () {
        Route::group(['prefix' => 'footer-block'], function () {
            Route::get('/', 'FooterBlockController@index');
            Route::get('create', 'FooterBlockController@create');
            Route::get('edit/{id}', 'FooterBlockController@edit');
            Route::post('create', 'FooterBlockController@store');
            Route::post('edit', 'FooterBlockController@update');
            Route::post('delete', 'FooterBlockController@delete');
            Route::post('status', 'FooterBlockController@status');
            Route::post('action', 'FooterBlockController@action');
        });
    });
});
