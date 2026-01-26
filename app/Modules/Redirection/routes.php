<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Redirection\Controllers'],function() {
		Route::group(['prefix' => 'redirection'],function(){
			Route::get('/', 'RedirectionController@index');
	        Route::get('create', 'RedirectionController@create');
	        Route::get('edit/{id}', 'RedirectionController@edit');
	        Route::post('create', 'RedirectionController@store');
	        Route::post('edit', 'RedirectionController@update');
	        Route::post('delete','RedirectionController@delete');
	        Route::post('status','RedirectionController@status');
	        Route::post('action','RedirectionController@action');
		});
	});
});