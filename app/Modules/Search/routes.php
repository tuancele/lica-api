<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Search\Controllers'],function() {
		Route::group(['prefix' => 'search'],function(){
			Route::get('/', 'SearchController@index')->name('search');
	        Route::get('create', 'SearchController@create')->name('search.create');
	        Route::get('edit/{id}', 'SearchController@edit')->name('search.edit');
	        Route::post('create', 'SearchController@store')->name('search.store');
	        Route::post('edit', 'SearchController@update')->name('search.update');
	        Route::post('delete','SearchController@delete')->name('search.delete');
	        Route::post('status','SearchController@status')->name('search.status');
	        Route::post('action','SearchController@action')->name('search.action');
	        Route::post('sort','SearchController@sort')->name('search.sort');
		});
	});
});