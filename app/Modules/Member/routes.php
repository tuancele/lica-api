<?php
Route::group(['middleware' => 'web'], function () {
	Route::group(['prefix' => 'admin','middleware' => 'admin','namespace' => 'App\Modules\Member\Controllers'],function() {
		Route::group(['prefix' => 'member'],function(){
			Route::get('/', 'MemberController@index')->name('member');
	        Route::get('create', 'MemberController@create')->name('member.create');
	        Route::get('edit/{id}', 'MemberController@edit')->name('member.edit');
	        Route::post('create', 'MemberController@store')->name('member.store');
	        Route::post('edit', 'MemberController@update')->name('member.update');
	        Route::post('delete','MemberController@delete')->name('member.delete');
	        Route::post('status','MemberController@status')->name('member.status');
	        Route::post('action','MemberController@action')->name('member.action');
	        Route::get('view/{id}','MemberController@view')->name('member.view');
	        Route::post('add-address','MemberController@addAddress')->name('member.addAddress');
	        Route::post('del-address','MemberController@delAddress')->name('member.delAddress');
	        Route::post('get_editaddress','MemberController@get_editaddress')->name('member.get_editaddress');
	        Route::post('get_addaddress','MemberController@get_addaddress')->name('member.get_addaddress');
	        Route::post('edit-address','MemberController@editAddress')->name('member.editAddress');
	        Route::post('edit-password','MemberController@editPassword')->name('member.editPassword');

	        Route::get('district/{id}','MemberController@district')->name('member.district');
	        Route::get('ward/{id}','MemberController@ward')->name('member.ward');
		});
	});
});