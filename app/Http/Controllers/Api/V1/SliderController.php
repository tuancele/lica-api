<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Slider\SliderResource;
use App\Modules\Slider\Models\Slider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Slider API Controller V1
 * 
 * RESTful API endpoints for public slider access
 * Base URL: /api/v1/sliders
 */
class SliderController extends Controller
{
    /**
     * Get list of active sliders
     * 
     * GET /api/v1/sliders
     * 
     * Query Parameters:
     * - display (string, optional): Filter by device type (desktop/mobile)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $display = $request->get('display');

            // Query sliders: only active (status = 1) and type = 'slider'
            $query = Slider::where('type', 'slider')
                ->where('status', '1');

            // Filter by display device if provided
            if (!empty($display) && in_array($display, ['desktop', 'mobile'])) {
                $query->where('display', $display);
            }

            // Order by sort ASC, then created_at DESC
            $query->orderByRaw('COALESCE(sort, 0) ASC')
                ->orderBy('created_at', 'DESC');

            // Get all active sliders (no pagination for public API)
            $sliders = $query->get();

            // Format response with SliderResource
            $formattedSliders = SliderResource::collection($sliders);

            return response()->json([
                'success' => true,
                'data' => $formattedSliders,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get sliders list failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'display' => $display ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách slider thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }
}
