<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Warehouse\Controllers'],function() {
		Route::group(['prefix' => 'warehouse'],function(){
			Route::get('/', 'WarehouseController@index')->name('warehouse');
			Route::get('quantity', 'WarehouseController@quantity')->name('quantity');
			Route::get('revenue', 'WarehouseController@revenue')->name('revenue');
		});
		Route::group(['prefix' => 'export-goods'],function(){
			Route::get('/', 'EgoodsController@index')->name('exportgoods');
			Route::get('create', 'EgoodsController@create');
			Route::get('edit/{id}', 'EgoodsController@edit');
			Route::post('create', 'EgoodsController@store');
			Route::post('show', 'EgoodsController@show');
			Route::get('print/{id}', 'EgoodsController@print');
			Route::post('edit', 'EgoodsController@update');
			Route::post('delete','EgoodsController@delete');
			Route::post('status','EgoodsController@status');
			Route::post('action','EgoodsController@action');
			Route::post('getPrice','EgoodsController@getPrice');
			Route::post('checkTotal','EgoodsController@checkTotal');
			Route::get('getVariant/{id}','EgoodsController@getVariant');
			Route::get('getVariants/{productId}','EgoodsController@getVariants');
			Route::get('getVariantStock/{variantId}','EgoodsController@getVariantStock');
			Route::get('searchProducts','EgoodsController@searchProducts');
			Route::get('loadAdd','EgoodsController@loadAdd');
		});
		Route::group(['prefix' => 'import-goods'],function(){
			Route::get('/', 'IgoodsController@index')->name('importgoods');
			Route::get('create', 'IgoodsController@create');
			Route::get('edit/{id}', 'IgoodsController@edit');
			Route::post('create', 'IgoodsController@store');
			Route::post('show', 'IgoodsController@show');
			Route::get('print/{id}', 'IgoodsController@print');
			Route::post('edit', 'IgoodsController@update');
			Route::post('delete','IgoodsController@delete');
			Route::post('status','IgoodsController@status');
			Route::post('action','IgoodsController@action');
			Route::get('getVariants/{productId}','IgoodsController@getVariants');
			Route::get('getVariantStock/{variantId}','IgoodsController@getVariantStock');
			Route::get('searchProducts','IgoodsController@searchProducts');
			Route::get('loadAdd','IgoodsController@loadAdd');
			
			Route::group(['prefix' => 'product'],function(){
				Route::get('/', 'IgoodsController@product');
				Route::post('action','IgoodsController@actionProduct');
				Route::post('export','IgoodsController@exportProduct');
				Route::post('delete','IgoodsController@deleteProduct');
			});
		});
		
		// Warehouse API endpoints for web (session authentication)
		Route::group(['prefix' => 'api/warehouse'],function(){
			Route::get('variants/{variantId}/stock', 'WarehouseController@getVariantStockWeb');
		});
	});
});