<?php 
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Menu\Controllers'],function() {
		Route::group(['prefix' => 'menu'],function(){
			Route::get('/', 'MenuController@index');
	        Route::get('create', 'MenuController@create');
	        Route::get('edit/{id}', 'MenuController@edit');
	        Route::post('create', 'MenuController@store');
	        Route::post('edit', 'MenuController@update');
	        Route::post('delete','MenuController@delete');
	        Route::post('status','MenuController@status');
	        Route::post('action','MenuController@action');
	        Route::post('showurl', 'MenuController@showurl');
	        Route::post('tree','MenuController@tree');
	        Route::post('add-link','MenuController@addLink');
	        Route::post('delete-link','MenuController@deleteLink');
	        Route::get('edit-link/{id}', 'MenuController@editLink');
	        Route::post('edit-link', 'MenuController@postLink');
		});
	});
});
?>