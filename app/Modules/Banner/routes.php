<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Banner\Controllers'],function() {
		Route::group(['prefix' => 'banner'],function(){
			Route::get('/', 'BannerController@index');
	        Route::get('create', 'BannerController@create');
	        Route::get('edit/{id}', 'BannerController@edit');
	        Route::post('create', 'BannerController@store');
	        Route::post('edit', 'BannerController@update');
	        Route::post('delete','BannerController@delete');
	        Route::post('status','BannerController@status');
	        Route::post('action','BannerController@action');
	        Route::post('sort','BannerController@sort');
		});
	});
});