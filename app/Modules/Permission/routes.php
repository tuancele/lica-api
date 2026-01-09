<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Permission\Controllers'],function() {
		Route::group(['prefix' => 'permission'],function(){
	        Route::get('/', 'PermissionController@index');
	        Route::get('create', 'PermissionController@create');
	        Route::get('edit/{id}', 'PermissionController@edit');
	        Route::get('parent/{id}', 'PermissionController@parent');
	        Route::post('create', 'PermissionController@store');
	        Route::post('edit', 'PermissionController@update');
	        Route::post('delete','PermissionController@delete');
	        Route::post('sort','PermissionController@sort');
	        Route::post('action','PermissionController@action');
		});
	});
});