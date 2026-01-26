<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Showroom\Controllers'],function() {
		Route::group(['prefix' => 'showroom'],function(){
			Route::get('/', 'ShowroomController@index');
	        Route::get('create', 'ShowroomController@create');
	        Route::get('edit/{id}', 'ShowroomController@edit');
	        Route::post('create', 'ShowroomController@store');
	        Route::post('edit', 'ShowroomController@update');
	        Route::post('delete','ShowroomController@delete');
	        Route::post('status','ShowroomController@status');
	        Route::post('action','ShowroomController@action');
	        Route::post('sort','ShowroomController@sort');
		});
	});
});