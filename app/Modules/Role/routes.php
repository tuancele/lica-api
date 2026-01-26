<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Role\Controllers'],function() {
		Route::group(['prefix' => 'role'],function(){
	        Route::get('/', 'RoleController@index');
	        Route::get('create', 'RoleController@create');
	        Route::get('edit/{id}', 'RoleController@edit');
	        Route::post('create', 'RoleController@store');
	        Route::post('edit', 'RoleController@update');
	        Route::post('delete','RoleController@delete');
	        Route::post('status','RoleController@status');
	        Route::post('action','RoleController@action');
		});
	});
});