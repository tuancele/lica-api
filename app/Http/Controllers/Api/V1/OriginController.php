<?php

declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Origin\Models\Origin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OriginController extends Controller
{
    /**
     * Get origin options for select inputs
     *
     * GET /api/v1/origins/options
     */
    public function options(Request $request): JsonResponse
    {
        try {
            $origins = Origin::query()
                ->select(['id', 'name'])
                ->where('status', '1')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $origins->map(function ($o) {
                    return [
                        'id' => (int) $o->id,
                        'name' => (string) $o->name,
                    ];
                })->values(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get origin options failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lay danh sach xuat xu that bai',
            ], 500);
        }
    }
}

