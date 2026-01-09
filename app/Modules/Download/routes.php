<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Download\Controllers'],function() {
		Route::group(['prefix' => 'download'],function(){
			Route::get('/', 'DownloadController@index');
	        Route::get('get', 'DownloadController@getData');
	        Route::get('create', 'DownloadController@create');
	        Route::get('edit/{id}', 'DownloadController@edit');
	        Route::post('create', 'DownloadController@store');
	        Route::post('edit', 'DownloadController@update');
	        Route::post('delete','DownloadController@delete');
	        Route::post('status','DownloadController@status');
	        Route::post('action','DownloadController@action');
		});
	});
});