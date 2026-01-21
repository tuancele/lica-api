<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Warehouse\Controllers'],function() {
		Route::group(['prefix' => 'warehouse'],function(){
			Route::get('/', 'WarehouseController@index')->name('warehouse');
			Route::get('quantity', 'WarehouseController@quantity')->name('quantity');
			Route::get('revenue', 'WarehouseController@revenue')->name('revenue');
		});
		// Legacy export/import goods routes: redirect to new Warehouse V2 UI.
		Route::get('export-goods/{any?}', function () {
			return redirect()->route('warehouse');
		})->where('any', '.*');

		Route::get('import-goods/{any?}', function () {
			return redirect()->route('warehouse');
		})->where('any', '.*');
	});
});