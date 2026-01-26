<?php

declare(strict_types=1);

namespace App\Themes\website\Controllers;

use App\Http\Controllers\Controller;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\DeliveryCategory;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;

class FacebookController extends Controller
{
    public function index()
    {
        $access_token = '';
        $pixel_id = '';

        $api = Api::init(null, null, $access_token);
        $api->setLogger(new CurlLogger);

        $user_data = (new UserData)
            ->setEmails(['joe@eg.com'])
            ->setPhones(['12345678901', '14251234567'])
            ->setClientIpAddress($_SERVER['REMOTE_ADDR'])
            ->setClientUserAgent($_SERVER['HTTP_USER_AGENT']);

        $content = (new Content)
            ->setProductId('product123')
            ->setQuantity(1)
            ->setDeliveryCategory(DeliveryCategory::HOME_DELIVERY);

        $custom_data = (new CustomData)
            ->setContents([$content])
            ->setCurrency('usd')
            ->setValue(123.45);

        $event = (new Event)
            ->setEventName('Purchase')
            ->setEventTime(time())
            ->setEventSourceUrl('http://jaspers-market.com/product/123')
            ->setUserData($user_data)
            ->setCustomData($custom_data)
            ->setActionSource(ActionSource::WEBSITE);

        $events = [];
        array_push($events, $event);

        $request = (new EventRequest($pixel_id))
            ->setEvents($events);
        $response = $request->execute();
        print_r($response);
    }
}
