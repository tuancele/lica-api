<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Color\Controllers'],function() {
		Route::group(['prefix' => 'color'],function(){
			Route::get('/', 'ColorController@index')->name('color');
	        Route::get('create', 'ColorController@create')->name('color.create');
	        Route::get('edit/{id}', 'ColorController@edit')->name('color.edit');
	        Route::post('create', 'ColorController@store')->name('color.store');
	        Route::post('edit', 'ColorController@update')->name('color.update');
	        Route::post('delete','ColorController@delete')->name('color.delete');
	        Route::post('status','ColorController@status')->name('color.status');
	        Route::post('action','ColorController@action')->name('color.action');
	        Route::post('sort','ColorController@sort')->name('color.sort');
		});
	});
});