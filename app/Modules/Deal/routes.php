<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Deal\Controllers'],function() {
		Route::group(['prefix' => 'deal'],function(){
			Route::get('/', 'DealController@index')->name('deal');
	        Route::get('create', 'DealController@create')->name('deal.create');
	        Route::get('edit/{id}', 'DealController@edit')->name('deal.edit');
			Route::get('view/{id}', 'DealController@view')->name('deal.view');
	        Route::post('create', 'DealController@store')->name('deal.store');
	        Route::post('edit', 'DealController@update')->name('deal.update');
	        Route::post('delete','DealController@delete')->name('deal.delete');
	        Route::post('status','DealController@status')->name('deal.status');
	        Route::post('action','DealController@action')->name('deal.action');
	        Route::post('sort','DealController@sort')->name('deal.sort');
	        Route::post('chose-product','DealController@choseProduct');
	        Route::post('load-product','DealController@loadProduct');
	        Route::post('del-product','DealController@delProduct');
	        Route::get('show-product','DealController@showProduct');
	        
	        Route::post('load-product2','DealController@loadProduct2');
	        Route::post('chose-product2','DealController@choseProduct2');
	        Route::post('del-product2','DealController@delProduct2');

	        Route::post('add-session','DealController@addSession');
	        Route::post('del-session','DealController@delSession');
	        Route::post('add-session2','DealController@addSession2');
	        Route::post('del-session2','DealController@delSession2');
		});
	});
});