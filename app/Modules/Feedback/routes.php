<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Feedback\Controllers'],function() {
		Route::group(['prefix' => 'feedback'],function(){
			Route::get('/', 'FeedbackController@index');
	        Route::get('create', 'FeedbackController@create');
	        Route::get('edit/{id}', 'FeedbackController@edit');
	        Route::post('create', 'FeedbackController@store');
	        Route::post('edit', 'FeedbackController@update');
	        Route::post('delete','FeedbackController@delete');
	        Route::post('status','FeedbackController@status');
	        Route::post('action','FeedbackController@action');
		});
	});
});