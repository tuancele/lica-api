<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Video\Controllers'],function() {
		Route::group(['prefix' => 'video'],function(){
			Route::get('/', 'VideoController@index');
	        Route::get('get', 'VideoController@getData');
	        Route::get('create', 'VideoController@create');
	        Route::get('edit/{id}', 'VideoController@edit');
	        Route::post('create', 'VideoController@store');
	        Route::post('edit', 'VideoController@update');
	        Route::post('delete','VideoController@delete');
	        Route::post('status','VideoController@status');
	        Route::post('action','VideoController@action');
		});
	});
});