<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Delivery\Models\Delivery;
use App\Traits\Sendmail;

class ShopeeController extends Controller
{
    use Sendmail;
    private $model;
    private $controller = 'delivery';
    private $view = 'Delivery';

    public function __construct(Delivery $model)
    {
        $this->model = $model;
    }

    public function generateSignature($randNum, $timestamp)
    {
        $appid = getConfig('shopee_app_id');
        $appsecret = '"'.getConfig('shopee_app_secret').'"';
        $payload = json_encode([
            'user_id' => getConfig('shopee_user_id'),
            'user_secret' => '"'.getConfig('shopee_user_id').'"',
            'service_type' => 1,
        ]);
        $string = sprintf('%d_%d_%d_%s', $appid, $timestamp, $randNum, $payload);
        $sig = hash_hmac('sha256', $string, $appsecret);

        return $sig;
    }

    public function getVerify()
    {
        $body = [
            'user_id' => getConfig('shopee_user_id'),
            'user_secret' => '"'.getConfig('shopee_user_id').'"',
            'service_type' => 1,
        ];
        $random = 123;
        $timestamp = strtotime(date('Y-m-d H:i:s'));
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => getConfig('shopee_link').'/open/api/v1/order/get_pickup_time',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                'app-id: 100161',
                'check-sign: '.$this->generateSignature($random, $timestamp),
                'timestamp: '.$timestamp,
                'random-num: '.$random,
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response);
        echo $timestamp.'<br/>';
        echo $this->generateSignature($random, $timestamp).'<br/>';
        print_r($result);
    }

    public function creatOrder()
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => getConfig('ghtk_url').'/services/shipment/order',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $order,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Token: '.getConfig('ghtk_token'),
                'Content-Length: '.strlen($order),
            ],
        ]);
        $response = curl_exec($curl);
        echo $response;
        curl_close($curl);
    }
}
