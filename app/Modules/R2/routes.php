<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\R2\Controllers'],function() {
        Route::post('r2/upload', 'R2Controller@upload')->name('r2.upload');
        Route::post('r2/upload-video', 'R2Controller@uploadVideo')->name('r2.uploadVideo');
        Route::get('r2/tool-sync', 'R2Controller@toolSyncR2')->name('r2.toolSync');
        Route::post('r2/start-sync-background', 'R2Controller@startSyncBackground')->name('r2.startSync');
        Route::get('r2/stop-sync-background', 'R2Controller@stopSyncBackground')->name('r2.stopSync');
        Route::get('r2/get-sync-status', 'R2Controller@getSyncStatus')->name('r2.status');
        Route::match(['get', 'post'], 'r2/sync-process', 'R2Controller@syncR2Process')->name('r2.process');
	});
});
