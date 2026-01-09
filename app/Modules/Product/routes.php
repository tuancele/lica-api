<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Product\Controllers'],function() {
		Route::group(['prefix' => 'product'],function(){
			Route::get('/', 'ProductController@index')->name('product');
	        Route::get('create', 'ProductController@create')->name('product.create');
	        Route::get('edit/{id}', 'ProductController@edit')->name('product.edit');
	        Route::post('create', 'ProductController@store')->name('product.store');
	        Route::post('edit', 'ProductController@update')->name('product.update');
	        Route::post('delete','ProductController@delete')->name('product.delete');
	        Route::post('status','ProductController@status')->name('product.status');
	        Route::post('action','ProductController@action')->name('product.action');
			Route::post('sort','ProductController@postSort')->name('product.sort');
	        Route::post('upload','ProductController@upload')->name('product.upload');
	        Route::get('{id}/variant', 'ProductController@variantnew')->name('product.variantnew');
	        Route::get('{id}/variant/{code}', 'ProductController@variant')->name('product.variant');
	        Route::post('editvariant','ProductController@editvariant')->name('product.editvariant');
	        Route::post('createvariant','ProductController@createvariant')->name('product.createvariant');
	        Route::post('delvariant','ProductController@delvariant')->name('product.delvariant');
		});
	});
});