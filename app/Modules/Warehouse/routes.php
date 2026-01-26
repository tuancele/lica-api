<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
	// Public route to view receipt (no auth required)
	Route::get('receipt/{receiptCode}', 'App\Modules\Warehouse\Controllers\WarehouseAccountingController@publicView')
		->name('warehouse.receipt.public');
	
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Warehouse\Controllers'],function() {
		Route::group(['prefix' => 'warehouse'],function(){
			Route::get('/', 'WarehouseController@index')->name('warehouse');
			Route::get('quantity', 'WarehouseController@quantity')->name('quantity');
			Route::get('revenue', 'WarehouseController@revenue')->name('revenue');
		Route::get('accounting', 'WarehouseAccountingController@index')->name('warehouse.accounting');
		Route::get('accounting/list', 'WarehouseAccountingController@list')->name('warehouse.accounting.list');
		Route::get('accounting/create', 'WarehouseAccountingController@create')->name('warehouse.accounting.create');
		Route::get('accounting/qrcode/{receiptCode}', 'WarehouseAccountingController@qrCode')->name('warehouse.accounting.qrcode');
		Route::post('accounting/number-to-text', 'WarehouseAccountingController@numberToText')->name('warehouse.accounting.number-to-text');
		Route::post('accounting', 'WarehouseAccountingController@store');
		Route::post('accounting/{id}/complete', 'WarehouseAccountingController@complete');
		Route::post('accounting/{id}/void', 'WarehouseAccountingController@void')->name('warehouse.accounting.void');
		Route::get('accounting/{id}/print', 'WarehouseAccountingController@print')->name('warehouse.accounting.print');
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