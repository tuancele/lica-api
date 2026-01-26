<?php

declare(strict_types=1);

namespace App\Themes\Website\Models;

use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\DeliveryCategory;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use Illuminate\Support\Facades\Log;

class Facebook
{
    /**
     * Send event to Facebook CAPI.
     */
    public static function track(array $data)
    {
        // Check if tracking is enabled
        if (! getConfig('facebook_status')) {
            return;
        }

        $accessToken = getConfig('facebook_access_token');
        $pixelId = getConfig('facebook_pixel_id');

        if (empty($accessToken) || empty($pixelId)) {
            return;
        }

        try {
            // Initialize API
            $api = Api::init(null, null, $accessToken);
            // Do not attach CurlLogger to avoid fwrite() bad file descriptor issues in some environments
            // $api->setLogger(new CurlLogger());

            // Prepare User Data
            $userData = new UserData;

            // Client User Agent and IP
            $userData->setClientIpAddress($_SERVER['REMOTE_ADDR'] ?? null)
                ->setClientUserAgent($_SERVER['HTTP_USER_AGENT'] ?? null);

            // Cookies (FBC & FBP)
            if (isset($_COOKIE['_fbc'])) {
                $userData->setFbc($_COOKIE['_fbc']);
            }
            if (isset($_COOKIE['_fbp'])) {
                $userData->setFbp($_COOKIE['_fbp']);
            }

            // User Info (Email/Phone)
            $member = auth()->guard('member')->user();
            if ($member) {
                if (! empty($member->email)) {
                    $userData->setEmails([strtolower(trim($member->email))]);
                }
                if (! empty($member->phone)) {
                    $userData->setPhones([preg_replace('/[^0-9]/', '', $member->phone)]);
                }
            } else {
                if (! empty($data['email'])) {
                    $userData->setEmails([strtolower(trim($data['email']))]);
                }
                if (! empty($data['phone'])) {
                    $userData->setPhones([preg_replace('/[^0-9]/', '', $data['phone'])]);
                }
            }

            // Content
            $content = (new Content)
                ->setProductId($data['product_id'] ?? null)
                ->setQuantity(1)
                ->setDeliveryCategory(DeliveryCategory::HOME_DELIVERY);

            // Custom Data
            $customData = (new CustomData)
                ->setContents([$content])
                ->setCurrency('VND');

            if (isset($data['price'])) {
                $customData->setValue((float) $data['price']);
            }

            // Event
            $event = (new Event)
                ->setEventName($data['event'] ?? 'ViewContent')
                ->setEventTime(time())
                ->setEventSourceUrl($data['url'] ?? url()->current())
                ->setUserData($userData)
                ->setCustomData($customData)
                ->setActionSource(ActionSource::WEBSITE);

            // Execute Request
            $request = (new EventRequest($pixelId))->setEvents([$event]);
            $request->execute();
        } catch (\Exception $e) {
            Log::error('Facebook CAPI Error: '.$e->getMessage());
        }
    }
}
