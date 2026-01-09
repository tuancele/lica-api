<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Post\Controllers'],function() {
		Route::group(['prefix' => 'post'],function(){
			Route::get('/', 'PostController@index')->name('post');
	        Route::get('create', 'PostController@create')->name('post.create');
	        Route::get('edit/{id}', 'PostController@edit')->name('post.edit');
	        Route::post('create', 'PostController@store')->name('post.store');
	        Route::post('edit', 'PostController@update')->name('post.update');
	        Route::post('delete','PostController@delete')->name('post.delete');
	        Route::post('status','PostController@status')->name('post.status');
	        Route::post('action','PostController@action')->name('post.action');
		});
	});
});