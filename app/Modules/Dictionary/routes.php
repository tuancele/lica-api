<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin/dictionary','middleware' => 'admin','namespace' => 'App\Modules\Dictionary\Controllers'],function() {
	Route::group(['prefix' => 'category'],function(){
			Route::get('/', 'CategoryController@index')->name('dictionary.category');
	        Route::get('create', 'CategoryController@create')->name('dictionary.category.create');
	        Route::get('edit/{id}', 'CategoryController@edit')->name('dictionary.category.edit');
	        Route::post('update', 'CategoryController@update')->name('dictionary.category.update');
	        Route::post('delete', 'CategoryController@delete')->name('dictionary.category.delete');
	        Route::post('status', 'CategoryController@status')->name('dictionary.category.status');
	        Route::post('sort', 'CategoryController@sort')->name('dictionary.category.sort');
	        Route::post('action', 'CategoryController@action')->name('dictionary.category.action');
		});
		Route::group(['prefix' => 'benefit'],function(){
			Route::get('/', 'BenefitController@index')->name('dictionary.benefit');
	        Route::get('create', 'BenefitController@create')->name('dictionary.benefit.create');
	        Route::get('edit/{id}', 'BenefitController@edit')->name('dictionary.benefit.edit');
	        Route::post('update', 'BenefitController@update')->name('dictionary.benefit.update');
	        Route::post('delete', 'BenefitController@delete')->name('dictionary.benefit.delete');
	        Route::post('status', 'BenefitController@status')->name('dictionary.benefit.status');
	        Route::post('sort', 'BenefitController@sort')->name('dictionary.benefit.sort');
	        Route::post('action', 'BenefitController@action')->name('dictionary.benefit.action');
		});
		Route::group(['prefix' => 'rate'],function(){
			Route::get('/', 'RateController@index')->name('dictionary.rate');
	        Route::get('create', 'RateController@create')->name('dictionary.rate.create');
	        Route::get('edit/{id}', 'RateController@edit')->name('dictionary.rate.edit');
	        Route::post('update', 'RateController@update')->name('dictionary.rate.update');
	        Route::post('delete', 'RateController@delete')->name('dictionary.rate.delete');
	        Route::post('status', 'RateController@status')->name('dictionary.rate.status');
	        Route::post('sort', 'RateController@sort')->name('dictionary.rate.sort');
	        Route::post('action', 'RateController@action')->name('dictionary.rate.action');
		});
		Route::group(['prefix' => 'ingredient'],function(){
			Route::get('/', 'IngredientController@index')->name('dictionary.ingredient');
	        Route::get('create', 'IngredientController@create')->name('dictionary.ingredient.create');
	        Route::get('edit/{id}', 'IngredientController@edit')->name('dictionary.ingredient.edit');
	        Route::get('crawl','IngredientController@crawl')->name('dictionary.ingredient.crawl');
	        Route::post('crawl/start','IngredientController@crawlStart')->name('dictionary.ingredient.crawl.start');
	        Route::post('crawl/step','IngredientController@crawlStep')->name('dictionary.ingredient.crawl.step');
	        Route::get('crawl/status','IngredientController@crawlStatus')->name('dictionary.ingredient.crawl.status');
	        Route::get('updateIngredient','IngredientController@updateIngredient');
	        Route::post('getData','IngredientController@getData')->name('dictionary.ingredient.get');
		});
	});
});

// Public Dictionary APIs (no admin auth)
Route::group([
	'middleware' => ['api'],
	'prefix' => 'api/dictionary',
	'namespace' => 'App\Modules\Dictionary\Controllers',
], function () {
	Route::get('/ingredients', 'IngredientController@publicList');
});