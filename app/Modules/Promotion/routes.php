<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Promotion\Controllers'],function() {
		Route::group(['prefix' => 'promotion'],function(){
			Route::get('/', 'PromotionController@index')->name('promotion.index');
	        Route::get('create', 'PromotionController@create')->name('promotion.create');
	        Route::get('edit/{id}', 'PromotionController@edit')->name('promotion.edit');
	        Route::post('create', 'PromotionController@store')->name('promotion.store');
	        Route::post('edit', 'PromotionController@update')->name('promotion.update');
	        Route::post('delete','PromotionController@delete')->name('promotion.delete');
	        Route::post('status','PromotionController@status')->name('promotion.status');
	        Route::post('action','PromotionController@action')->name('promotion.action');
	        Route::post('sort','PromotionController@sort')->name('promotion.sort');
		});
	});
});