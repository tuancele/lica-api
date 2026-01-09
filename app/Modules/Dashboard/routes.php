<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Dashboard\Controllers'],function() {
		Route::get('/', 'DashboardController@index');
		Route::group(['prefix' => 'dashboard'],function(){
	        Route::get('/', 'DashboardController@index');
			Route::get('orders', 'DashboardController@order');
			Route::get('activities', 'DashboardController@activities');
			Route::post('donchuagiao', 'DashboardController@donchuagiao');
			Route::post('danggiao', 'DashboardController@danggiao');
			Route::post('chuathanhtoan', 'DashboardController@chuathanhtoan');
			Route::post('load', 'DashboardController@load');
			Route::post('loadchart', 'DashboardController@loadchart');
		});
	});
});