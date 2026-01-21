<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Warehouse\Controllers'],function() {
		Route::group(['prefix' => 'warehouse'],function(){
			Route::get('/', 'WarehouseController@index')->name('warehouse');
			Route::get('quantity', 'WarehouseController@quantity')->name('quantity');
			Route::get('revenue', 'WarehouseController@revenue')->name('revenue');
		});
		// Legacy export/import goods routes removed in favor of Inventory v2 APIs.
		// If frontend still calls old URLs, respond 410 Gone to surface migration quickly.
		Route::any('export-goods/{any?}', fn() => abort(410))->where('any', '.*');
		Route::any('import-goods/{any?}', fn() => abort(410))->where('any', '.*');
	});
});