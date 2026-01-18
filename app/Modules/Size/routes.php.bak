<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Size\Controllers'],function() {
		Route::group(['prefix' => 'size'],function(){
			Route::get('/', 'SizeController@index')->name('size');
	        Route::get('create', 'SizeController@create')->name('size.create');
	        Route::get('edit/{id}', 'SizeController@edit')->name('size.edit');
	        Route::post('create', 'SizeController@store')->name('size.store');
	        Route::post('edit', 'SizeController@update')->name('size.update');
	        Route::post('delete','SizeController@delete')->name('size.delete');
	        Route::post('status','SizeController@status')->name('size.status');
	        Route::post('action','SizeController@action')->name('size.action');
	        Route::post('sort','SizeController@sort')->name('size.sort');
		});
	});
});