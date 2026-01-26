<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserAnalyticsService
{
    /**
     * 获取用户IP地址的详细信息.
     */
    public function getIpInfo(string $ip): array
    {
        $cacheKey = "ip_info:{$ip}";

        return Cache::remember($cacheKey, 86400, function () use ($ip) {
            try {
                $response = @file_get_contents("http://ip-api.com/json/{$ip}?lang=vi");

                if ($response) {
                    $data = json_decode($response, true);
                    if ($data && ($data['status'] ?? '') === 'success') {
                        return [
                            'country' => $data['country'] ?? null,
                            'region' => $data['regionName'] ?? null,
                            'city' => $data['city'] ?? null,
                            'lat' => $data['lat'] ?? null,
                            'lon' => $data['lon'] ?? null,
                            'timezone' => $data['timezone'] ?? null,
                            'isp' => $data['isp'] ?? null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get IP info: '.$e->getMessage());
            }

            return [];
        });
    }

    /**
     * 解析User Agent获取设备信息.
     */
    public function parseUserAgent(?string $userAgent): array
    {
        if (! $userAgent) {
            return [
                'device_type' => 'unknown',
                'browser' => 'unknown',
                'os' => 'unknown',
            ];
        }

        $deviceType = 'desktop';
        $browser = 'unknown';
        $os = 'unknown';

        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $userAgent)) {
            $deviceType = 'tablet';
        } elseif (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
            $deviceType = 'mobile';
        }

        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'Opera';
        }

        if (preg_match('/Windows NT/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            $os = 'iOS';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
        ];
    }

    /**
     * 获取会话统计信息.
     */
    public function getSessionStats(string $sessionId): array
    {
        $cacheKey = "session_stats:{$sessionId}";

        return Cache::remember($cacheKey, 3600, function () use ($sessionId) {
            $behaviors = \App\Modules\Recommendation\Models\UserBehavior::where('session_id', $sessionId)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($behaviors->isEmpty()) {
                return [
                    'page_views' => 1,
                    'start_time' => now(),
                    'products_viewed' => [],
                ];
            }

            return [
                'page_views' => $behaviors->count(),
                'start_time' => $behaviors->first()->created_at,
                'products_viewed' => $behaviors->pluck('product_id')->unique()->toArray(),
            ];
        });
    }
}
