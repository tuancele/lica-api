<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Delivery\Controllers'],function() {
		Route::group(['prefix' => 'delivery'],function(){
			Route::group(['prefix' => 'ghtk'],function(){
				Route::get('/', 'GHTKController@index')->name('ghtk');
		        Route::post('create', 'GHTKController@create')->name('ghtk.create');
		        Route::post('getFee', 'GHTKController@getFee')->name('ghtk.fee');
		        Route::post('store', 'GHTKController@store')->name('ghtk.store');
		        Route::get('print/{label}', 'GHTKController@printLabel')->name('ghtk.print');
		        Route::post('cancel', 'GHTKController@cancel')->name('ghtk.cancel');
			});
			// Route::group(['prefix' => 'shopee'],function(){
			// 	Route::get('/', 'ShopeeController@index')->name('shopee');
		 //        Route::get('verify', 'ShopeeController@getVerify')->name('shopee.verify');
		 //        Route::post('getFee', 'ShopeeController@getFee')->name('ghtk.fee');
		 //        Route::post('store', 'ShopeeController@store')->name('ghtk.store');
		 //        Route::get('print/{label}', 'ShopeeController@printLabel')->name('ghtk.print');
		 //        Route::post('cancel', 'ShopeeController@cancel')->name('ghtk.cancel');

		 //        Route::get('testFee', 'GHTKController@testSendmail');
			// });
			Route::get('setting', 'DeliveryController@setting')->name('delivery.setting');
			Route::post('setting', 'DeliveryController@update')->name('delivery.update');
		});
	});
});