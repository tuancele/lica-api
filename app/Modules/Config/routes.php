<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Config\Controllers'],function() {
		Route::group(['prefix' => 'config'],function(){
			Route::get('/', 'ConfigController@index');
	        Route::post('update', 'ConfigController@update');
            Route::get('tool-sync-r2', 'ConfigController@toolSyncR2');
            Route::match(['get', 'post'], 'sync-r2-process', 'ConfigController@syncR2Process');
            Route::post('start-sync-background', 'ConfigController@startSyncBackground');
            Route::post('stop-sync-background', 'ConfigController@stopSyncBackground');
            Route::get('get-sync-status', 'ConfigController@getSyncStatus');
		});
	});
});