<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Page\Controllers'],function() {
		Route::group(['prefix' => 'page'],function(){
			Route::get('/', 'PageController@index')->name('page');
	        Route::get('create', 'PageController@create')->name('page.create');
	        Route::get('edit/{id}', 'PageController@edit')->name('page.edit');
	        Route::post('create', 'PageController@store')->name('page.store');
	        Route::post('edit', 'PageController@update')->name('page.update');
	        Route::post('delete','PageController@delete')->name('page.delete');
	        Route::post('status','PageController@status')->name('page.status');
	        Route::post('action','PageController@action')->name('page.action');
		});
	});
});