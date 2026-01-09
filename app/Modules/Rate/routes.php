<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Rate\Controllers'],function() {
		Route::group(['prefix' => 'rate'],function(){
			Route::get('/', 'RateController@index');
	        Route::get('create', 'RateController@create');
	        Route::get('edit/{id}', 'RateController@edit');
	        Route::post('create', 'RateController@store');
	        Route::post('edit', 'RateController@update');
	        Route::post('delete','RateController@delete');
	        Route::post('status','RateController@status');
	        Route::post('action','RateController@action');
	        Route::post('sort','RateController@sort');
	        Route::post('upload','RateController@upload')->name('rate.upload');
		});
	});
});