<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','namespace' => 'App\Modules\User\Controllers'],function(){
		Route::get('login', 'LoginController@index');
		Route::post('login', 'LoginController@postLogin');
		Route::get('logout', 'LoginController@logout');
	});
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\User\Controllers'],function() {
		Route::group(['prefix' => 'user'],function(){
			Route::get('/', 'UserController@index');
			Route::get('create', 'UserController@create');
			Route::get('edit/{id}', 'UserController@edit');
			Route::post('create', 'UserController@store');
			Route::post('edit', 'UserController@update');
			Route::post('delete','UserController@delete');
			Route::post('status','UserController@status');
			Route::post('checkemail','UserController@checkemail');
			Route::post('checkemailedit','UserController@checkemailedit');
			Route::get('change/{id}', 'UserController@change');
			Route::post('changepass','UserController@changepass'); 
	        
		});
	});
});