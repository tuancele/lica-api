<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Website\Controllers'],function() {
		Route::group(['prefix' => 'themes'],function(){
	        Route::get('footer', 'ThemesController@footer');
	        Route::post('footer', 'ThemesController@postFooter');
	        Route::get('header', 'ThemesController@header');
	        Route::post('header', 'ThemesController@postHeader');
	        Route::get('home', 'ThemesController@home');
	        Route::post('home', 'ThemesController@postHome');
		});
	});
});