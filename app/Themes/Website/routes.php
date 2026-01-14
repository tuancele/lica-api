<?php
	Route::group(['middleware' => 'web'], function () {
		Route::group(['namespace' => 'App\Themes\Website\Controllers'],function() {
			try {
				$redirect = App\Modules\Redirection\Models\Redirection::where([['status','1'],['link_from',url()->current()]])->orderBy('created_at','desc')->first();
				if(isset($redirect) && !empty($redirect)){
					header("Location: ".$redirect->link_to, true, $redirect->type);
					exit();
				}
			} catch (\Exception $e) {
				// Ignore DB errors in routes
			}
			Route::get('/', 'HomeController@index')->name('home');

			Route::get('testCrawl', 'HomeController@testCrawl');

			Route::post('ajax-search', 'HomeController@ajaxSearch');
			Route::post('ajax-search-suggestions', 'HomeController@ajaxSearchSuggestions');
			Route::post('ajax-remove-recent-search', 'HomeController@ajaxRemoveRecentSearch');
			Route::post('load-filter', 'HomeController@ajaxFilter');
			Route::post('load-sort', 'HomeController@ajaxSort');
			Route::post('ajax/post-subcriber', 'HomeController@subcriber');
			Route::post('ajax/get-owl', 'HomeController@loadOwl');
			Route::get('tu-khoa/{url}', 'HomeController@tag');
			Route::get('xuat-xu/{url}', 'HomeController@origin');
			Route::get('thuong-hieu/{url}', 'BrandController@index')->name('home.brand');
			Route::get('tim-kiem', 'HomeController@search');
			Route::post('product/quickview', 'HomeController@quickView')->name('quickView');
			Route::get('ingredient/{slug}', 'HomeController@getIngredient');
			Route::get('getPrice', 'HomeController@getPrice');
			Route::get('getSize', 'HomeController@getSize');
			Route::post('promotion', 'HomeController@getPromotion')->name('promotion');
			Route::post('load-ingredient', 'HomeController@loadIngredient')->name('loadIngredient');
			Route::get('skindeep/ingredients/{slug}', 'HomeController@ingredient');
			Route::get('skindeep/search', 'HomeController@searchIngredient');
			Route::get('ingredient-dictionary/{slug}', 'HomeController@detailIngredient');
			Route::post('ajax/filter-ingredient', 'HomeController@filterIngredient');
			Route::post('ajax/sort-ingredient', 'HomeController@sortIngredient');
			Route::post('ajax/search-ingredient', 'HomeController@sIngredient');
			Route::post('ajax/clear-filter', 'HomeController@clearFilter');
			Route::post('tracking/postTracking', 'HomeController@postTracking')->name('postTracking');
			
			Route::group(['prefix' => 'cart'],function(){
				Route::get('get', 'CartController@get')->name('cart.get');
				Route::get('gio-hang', 'CartController@index')->name('cart.index');
				Route::get('thanh-toan', 'CartController@checkout')->name('cart.payment');
				Route::post('thanh-toan', 'CartController@postCheckout')->name('cart.checkout');
				Route::get('dat-hang-thanh-cong', 'CartController@result')->name('cart.success');
			    Route::post('add-to-cart', 'CartController@addCart')->name('cart.add');
			    Route::post('del-item-cart', 'CartController@delCart')->name('cart.del');
			    Route::post('update-cart', 'CartController@updateCart')->name('cart.update');
			    Route::post('fee-ship', 'CartController@feeShip')->name('cart.feeship');
			    Route::post('choseAddress', 'CartController@choseAddress')->name('cart.choseAddress');
			    Route::get('search-location', 'CartController@searchLocation')->name('cart.searchLocation');
			    Route::get('loadPromotion', 'CartController@loadPromotion')->name('cart.loadPromotion');
			    Route::post('applyCoupon', 'CartController@applyCoupon')->name('cart.applyCoupon');
			    Route::post('cancelCoupon', 'CartController@cancelCoupon')->name('cart.cancelCoupon');
			    Route::get('load-district/{id}', 'CartController@loadDistrict');
			    Route::get('load-ward/{id}', 'CartController@loadWard');
			});

			Route::get('redirect/{provider}', 'LoginController@redirect')->name('login.social');
			Route::get('callback/{provider}', 'LoginController@callback')->name('callback.social');
			Route::post('loginGoogle', 'LoginController@loginGoogle')->name('loginGoogle');

			Route::post('login', 'LoginController@login')->name('member.login');
			Route::post('forgot', 'LoginController@forgot')->name('member.forgot');
			Route::post('register', 'LoginController@register')->name('member.register');
			Route::get('account/activation/{token}', 'LoginController@activation')->name('member.activation');
			Route::group(['prefix' => 'account','middleware' => 'member'],function(){
				Route::get('profile', 'MemberController@profile')->name('account.profile');
				Route::post('profile-update', 'MemberController@update')->name('account.profile.update');
				Route::get('orders', 'MemberController@orders')->name('account.orders');
				Route::get('order/{code}', 'MemberController@order')->name('account.order');
				Route::get('address', 'MemberController@address')->name('account.address');
				Route::post('store-address', 'MemberController@storeAddress')->name('account.address.store');
				Route::post('edit-address', 'MemberController@editAddress')->name('account.address.edit');
				Route::post('update-address', 'MemberController@updateAddress')->name('account.address.update');
				Route::post('delete-address', 'MemberController@deleteAddress')->name('account.address.delete');
				Route::get('logout', 'MemberController@logout')->name('account.logout');
				Route::get('password', 'MemberController@password')->name('account.password');
				Route::post('update-password', 'MemberController@updatePassword')->name('account.password.update');
				Route::post('add-promotion', 'MemberController@addPromotion')->name('account.addpromotion');
				Route::get('promotion', 'MemberController@promotion')->name('account.promotion');
			});

			Route::group(['prefix' => 'wishlist','middleware' => 'member'],function(){
				Route::get('get', 'WishlistController@get')->name('wishlist.get');
				Route::post('add', 'WishlistController@add')->name('wishlist.add');
				Route::post('remove', 'WishlistController@remove')->name('wishlist.remove');
				Route::get('remove-all', 'WishlistController@removeAll')->name('wishlist.remove.all');
			});

			Route::group(['prefix' => 'review'],function(){
				Route::post('add-review', 'ReviewController@addReview')->name('review.add');
				Route::get('load/{id}', 'ReviewController@getReview');
			});
			Route::group(['prefix' => 'contact'],function(){
				Route::post('subcribe', 'ContactController@subcribe');
				Route::post('contact', 'ContactController@contact');
			});

			Route::get('district/{id}', 'CartController@district');
			Route::get('ward/{id}', 'CartController@ward');

			// Route chi tiết sản phẩm (ưu tiên bắt slug sản phẩm trước)
            Route::get('{slug}', 'ProductController@show')->name('product.show');

			// Route fallback cho các loại post khác (trang, blog, taxonomy...)
			Route::get('{url}','HomeController@post');
		});
	});
?>