<?php

declare(strict_types=1);
Route::group(['middleware' => 'web'], function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'admin', 'namespace' => 'App\Modules\Marketing\Controllers'], function () {
        Route::group(['prefix' => 'marketing'], function () {
            Route::get('campaign', 'MarketingCampaignController@index')->name('marketing.campaign.index');
            Route::get('campaign/create', 'MarketingCampaignController@create')->name('marketing.campaign.create');
            Route::post('campaign/create', 'MarketingCampaignController@store')->name('marketing.campaign.store');
            Route::get('campaign/edit/{id}', 'MarketingCampaignController@edit')->name('marketing.campaign.edit');
            Route::post('campaign/edit', 'MarketingCampaignController@update')->name('marketing.campaign.update');
            Route::post('campaign/delete', 'MarketingCampaignController@delete')->name('marketing.campaign.delete');
            Route::post('campaign/status', 'MarketingCampaignController@status')->name('marketing.campaign.status');
            Route::post('campaign/action', 'MarketingCampaignController@action')->name('marketing.campaign.action');

            // Ajax routes
            Route::post('campaign/load-product', 'MarketingCampaignController@loadProduct')->name('marketing.campaign.load_product');
            Route::post('campaign/search-product', 'MarketingCampaignController@searchProduct')->name('marketing.campaign.search_product');
        });
    });
});
