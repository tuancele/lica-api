<?php 
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Ingredient\Controllers'],function() {
		Route::group(['prefix' => 'ingredient'],function(){
            Route::get('getList', 'IngredientController@getList');
            Route::post('getDetail', 'IngredientController@getDetail');
            Route::get('/', 'IngredientController@index')->name('ingredient');
	        Route::get('create', 'IngredientController@create')->name('ingredient.create');
	        Route::get('edit/{id}', 'IngredientController@edit')->name('ingredient.edit');
	        Route::post('create', 'IngredientController@store')->name('ingredient.store');
	        Route::post('edit', 'IngredientController@update')->name('ingredient.update');
	        Route::post('delete','IngredientController@delete')->name('ingredient.delete');
	        Route::post('status','IngredientController@status')->name('ingredient.status');
	        Route::post('action','IngredientController@action')->name('ingredient.action');
		});
	});
});
?>