<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Taxonomy\Controllers'],function() {
		Route::group(['prefix' => 'taxonomy'],function(){
			Route::get('/', 'TaxonomyController@index')->name('taxonomy');
	        Route::get('create', 'TaxonomyController@create')->name('taxonomy.create');
	        Route::get('edit/{id}', 'TaxonomyController@edit')->name('taxonomy.eidt');
	        Route::post('create', 'TaxonomyController@store')->name('taxonomy.store');
	        Route::post('edit', 'TaxonomyController@update')->name('taxonomy.update');
	        Route::post('delete','TaxonomyController@delete')->name('taxonomy.delete');
	        Route::post('status','TaxonomyController@status')->name('taxonomy.status');
	        Route::post('action','TaxonomyController@action')->name('taxonomy.action');
	        Route::get('sort', 'TaxonomyController@sort')->name('taxonomy.sort');
	        Route::post('tree','TaxonomyController@tree')->name('taxonomy.tree');
		});
	});
});