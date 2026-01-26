<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['namespace' => 'App\Themes\Website\Controllers'], function () {
        try {
            $redirect = App\Modules\Redirection\Models\Redirection::where([['status', '1'], ['link_from', url()->current()]])->orderBy('created_at', 'desc')->first();
            if (isset($redirect) && ! empty($redirect)) {
                header('Location: '.$redirect->link_to, true, $redirect->type);
                exit();
            }
        } catch (\Exception $e) {
            // Ignore DB errors in routes
        }
        Route::get('/', 'HomeController@index')->name('home');

        // ===== DEBUG ROUTE (TẠM THỜI - XÓA SAU KHI DEBUG) =====
        Route::get('debug/cart-session', function () {
            $cart = session()->get('cart');
            $items = [];

            if ($cart && isset($cart->items)) {
                foreach ($cart->items as $variantId => $item) {
                    $items[$variantId] = [
                        'qty' => $item['qty'] ?? 0,
                        'price' => $item['price'] ?? 0,
                        'is_deal' => $item['is_deal'] ?? 0,
                        'product_id' => $item['item']->product_id ?? null,
                    ];
                }
            }

            return response()->json([
                'session_id' => session()->getId(),
                'has_cart' => session()->has('cart'),
                'total_qty' => $cart->totalQty ?? 0,
                'total_price' => $cart->totalPrice ?? 0,
                'items' => $items,
            ]);
        });
        // ===== END DEBUG ROUTE =====

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

        Route::group(['prefix' => 'cart'], function () {
            Route::get('get', 'CartControllerV2@get')->name('cart.get');
            Route::get('gio-hang', 'CartControllerV2@index')->name('cart.index');
            Route::get('gio-hang.json', 'CartControllerV2@getCartJson')->name('cart.v2.json');
            Route::post('items', 'CartControllerV2@addItem')->name('cart.v2.add');
            Route::post('add-to-cart', 'CartControllerV2@addCart')->name('cart.add');
            Route::put('items/{variant_id}', 'CartControllerV2@updateItem')->name('cart.v2.update');
            Route::post('update-cart', 'CartControllerV2@updateCart')->name('cart.update');
            Route::delete('items/{variant_id}', 'CartControllerV2@removeItem')->name('cart.v2.remove');
            Route::post('del-item-cart', 'CartControllerV2@delCart')->name('cart.del');
        });

        Route::group(['prefix' => 'cart'], function () {
            Route::get('thanh-toan', 'CheckoutControllerV2@index')->name('cart.payment');
            Route::post('thanh-toan', 'CheckoutControllerV2@checkout')->name('cart.checkout');
            Route::get('dat-hang-thanh-cong', 'CheckoutControllerV2@result')->name('cart.success');
            Route::get('loadPromotion', 'CheckoutControllerV2@loadPromotion')->name('cart.loadPromotion');
            Route::post('applyCoupon', 'CheckoutControllerV2@applyCoupon')->name('cart.applyCoupon');
            Route::post('cancelCoupon', 'CheckoutControllerV2@removeCoupon')->name('cart.cancelCoupon');
            Route::post('fee-ship', 'CheckoutControllerV2@calculateShippingFee')->name('cart.feeship');
            Route::get('search-location', 'CheckoutControllerV2@searchLocation')->name('cart.searchLocation');
        });

        Route::group(['prefix' => 'checkout'], function () {
            Route::get('thanh-toan', 'CheckoutControllerV2@index')->name('checkout.v2.index');
            Route::post('thanh-toan', 'CheckoutControllerV2@checkout')->name('checkout.v2.checkout');
            Route::get('dat-hang-thanh-cong', 'CheckoutControllerV2@result')->name('checkout.v2.result');
            Route::post('coupon/apply', 'CheckoutControllerV2@applyCoupon')->name('checkout.v2.applyCoupon');
            Route::post('coupon/remove', 'CheckoutControllerV2@removeCoupon')->name('checkout.v2.removeCoupon');
            Route::post('shipping-fee', 'CheckoutControllerV2@calculateShippingFee')->name('checkout.v2.shippingFee');
            Route::get('search-location', 'CheckoutControllerV2@searchLocation')->name('checkout.v2.searchLocation');
        });

        Route::get('redirect/{provider}', 'LoginController@redirect')->name('login.social');
        Route::get('callback/{provider}', 'LoginController@callback')->name('callback.social');
        Route::post('loginGoogle', 'LoginController@loginGoogle')->name('loginGoogle');

        Route::post('login', 'LoginController@login')->name('member.login');
        Route::post('forgot', 'LoginController@forgot')->name('member.forgot');
        Route::post('register', 'LoginController@register')->name('member.register');
        Route::get('account/activation/{token}', 'LoginController@activation')->name('member.activation');
        Route::group(['prefix' => 'account', 'middleware' => 'member'], function () {
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

        Route::group(['prefix' => 'wishlist', 'middleware' => 'member'], function () {
            Route::get('get', 'WishlistController@get')->name('wishlist.get');
            Route::post('add', 'WishlistController@add')->name('wishlist.add');
            Route::post('remove', 'WishlistController@remove')->name('wishlist.remove');
            Route::get('remove-all', 'WishlistController@removeAll')->name('wishlist.remove.all');
        });

        Route::group(['prefix' => 'review'], function () {
            Route::post('add-review', 'ReviewController@addReview')->name('review.add');
            Route::get('load/{id}', 'ReviewController@getReview');
        });
        Route::group(['prefix' => 'contact'], function () {
            Route::post('subcribe', 'ContactController@subcribe');
            Route::post('contact', 'ContactController@contact');
        });

        // Route chi tiết sản phẩm (ưu tiên bắt slug sản phẩm trước)
        Route::get('{slug}', 'ProductController@show')->name('product.show');

        // Route fallback cho các loại post khác (trang, blog, taxonomy...)
        Route::get('{url}', 'HomeController@post');
    });
});
