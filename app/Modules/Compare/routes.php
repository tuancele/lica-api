<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Compare\Controllers'],function() {
		Route::group(['prefix' => 'compare'],function(){
            Route::get('/', 'CompareController@index')->name('compare');
	        Route::get('create', 'CompareController@create')->name('compare.create');
	        Route::get('edit/{id}', 'CompareController@edit')->name('compare.edit');
	        Route::post('create', 'CompareController@store')->name('compare.store');
	        Route::post('edit', 'CompareController@update')->name('compare.update');
	        Route::post('delete','CompareController@delete')->name('compare.delete');
	        Route::post('status','CompareController@status')->name('compare.status');
	        Route::post('action','CompareController@action')->name('compare.action');
            Route::get('crawl', 'CompareController@crawl')->name('compare.crawl');
            Route::get('postCrawl', 'CompareController@postCrawl')->name('compare.postcrawl');
			Route::get('postProduct', 'CompareController@crawlProduct');

			Route::post('get-brand', 'CompareController@getBrand')->name('compare.getBrand');
			Route::post('get-product', 'CompareController@getProduct')->name('compare.getProduct');
			Route::group(['prefix' => 'store'],function(){
				Route::get('/', 'StoreController@index')->name('compare.store');
				Route::get('edit/{id}', 'StoreController@edit')->name('compare.store.edit');
				Route::post('edit', 'StoreController@update')->name('compare.store.update');
			});
		});
		
	});
});
?>