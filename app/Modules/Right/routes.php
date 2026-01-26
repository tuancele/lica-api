<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Right\Controllers'],function() {
		Route::group(['prefix' => 'right'],function(){
			Route::get('/', 'RightController@index');
	        Route::get('create', 'RightController@create');
	        Route::get('edit/{id}', 'RightController@edit');
	        Route::post('create', 'RightController@store');
	        Route::post('edit', 'RightController@update');
	        Route::post('delete','RightController@delete');
	        Route::post('status','RightController@status');
	        Route::post('action','RightController@action');
	        Route::post('sort','RightController@sort');
		});
	});
});