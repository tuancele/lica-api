<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Simple healthcheck endpoint for Phase 4 monitoring.
 *
 * URL: GET /api/health
 */
class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => true,
            'db' => false,
            'cache' => false,
        ];

        // DB check
        try {
            DB::connection()->getPdo();
            $checks['db'] = true;
        } catch (\Throwable $e) {
            $checks['db'] = false;
        }

        // Cache check (Redis)
        try {
            $key = 'healthcheck:ping';
            Cache::put($key, 'ok', 5);
            $checks['cache'] = Cache::get($key) === 'ok';
        } catch (\Throwable $e) {
            $checks['cache'] = false;
        }

        $status = $checks['app'] && $checks['db'] && $checks['cache'];

        return response()->json([
            'success' => $status,
            'status' => $status ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $status ? 200 : 503);
    }
}


