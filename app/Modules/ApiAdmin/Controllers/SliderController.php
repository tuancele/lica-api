<?php

declare(strict_types=1);
namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Slider\SliderResource;
use App\Modules\Slider\Models\Slider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Slider API Controller for Admin
 * 
 * Handles all slider management API endpoints following RESTful standards
 */
class SliderController extends Controller
{
    /**
     * Get paginated list of sliders with filters
     * 
     * GET /admin/api/sliders
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Prepare filters from query parameters
            $filters = [];
            
            if ($request->has('status') && $request->status !== '') {
                $filters['status'] = $request->status;
            }
            
            if ($request->has('display') && $request->display !== '') {
                $filters['display'] = $request->display;
            }
            
            if ($request->has('keyword') && $request->keyword !== '') {
                $filters['keyword'] = $request->keyword;
            }
            
            // Get pagination parameters
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            // Build query
            $query = Slider::where('type', 'slider');
            
            // Apply filters
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            if (isset($filters['display'])) {
                $query->where('display', $filters['display']);
            }
            
            if (isset($filters['keyword'])) {
                $query->where('name', 'like', '%' . $filters['keyword'] . '%');
            }
            
            // Order by name ASC (matching old controller behavior)
            $query->orderBy('name', 'asc');
            
            // Paginate results
            $sliders = $query->paginate($perPage);
            
            // Format response using SliderResource
            $formattedSliders = SliderResource::collection($sliders->items());
            
            return response()->json([
                'success' => true,
                'data' => $formattedSliders,
                'pagination' => [
                    'current_page' => $sliders->currentPage(),
                    'per_page' => $sliders->perPage(),
                    'total' => $sliders->total(),
                    'last_page' => $sliders->lastPage(),
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('获取slider列表失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '获取slider列表失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get single slider details
     * 
     * GET /admin/api/sliders/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Find slider by ID and type
            $slider = Slider::where('type', 'slider')
                ->with('user')
                ->find($id);
            
            if (!$slider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slider không tồn tại'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => new SliderResource($slider),
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('获取slider详情失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'slider_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '获取slider详情失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Create a new slider
     * 
     * POST /admin/api/sliders
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'link' => 'nullable|string|url',
                'image' => 'nullable|string',
                'display' => 'required|string|in:desktop,mobile',
                'status' => 'required|string|in:0,1',
            ], [
                'name.required' => 'Tiêu đề không được bỏ trống.',
                'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
                'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
                'link.url' => 'Liên kết không hợp lệ',
                'display.required' => 'Màn hình không được bỏ trống',
                'display.in' => 'Màn hình phải là desktop hoặc mobile',
                'status.required' => 'Trạng thái không được bỏ trống',
                'status.in' => 'Trạng thái phải là 0 hoặc 1',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Create slider
            $slider = Slider::create([
                'name' => $request->name,
                'link' => $request->link,
                'image' => $request->image,
                'display' => $request->display,
                'type' => 'slider',
                'status' => $request->status,
                'user_id' => Auth::id(),
            ]);
            
            // Clear cache
            Cache::forget('home_sliders_v1');
            Cache::forget('home_sliderms_v1');
            
            return response()->json([
                'success' => true,
                'message' => 'Tạo slider thành công',
                'data' => new SliderResource($slider->load('user')),
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('创建slider失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '创建slider失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Update a slider
     * 
     * PUT /admin/api/sliders/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Find slider by ID and type
            $slider = Slider::where('type', 'slider')->find($id);
            
            if (!$slider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slider không tồn tại'
                ], 404);
            }
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:250',
                'link' => 'nullable|string|url',
                'image' => 'nullable|string',
                'display' => 'required|string|in:desktop,mobile',
                'status' => 'required|string|in:0,1',
            ], [
                'name.required' => 'Tiêu đề không được bỏ trống.',
                'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
                'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
                'link.url' => 'Liên kết không hợp lệ',
                'display.required' => 'Màn hình không được bỏ trống',
                'display.in' => 'Màn hình phải là desktop hoặc mobile',
                'status.required' => 'Trạng thái không được bỏ trống',
                'status.in' => 'Trạng thái phải là 0 hoặc 1',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update slider
            $slider->update([
                'name' => $request->name,
                'link' => $request->link,
                'image' => $request->image,
                'display' => $request->display,
                'status' => $request->status,
                'user_id' => Auth::id(),
            ]);
            
            // Clear cache
            Cache::forget('home_sliders_v1');
            Cache::forget('home_sliderms_v1');
            
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật slider thành công',
                'data' => new SliderResource($slider->load('user')),
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('更新slider失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'slider_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '更新slider失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Delete a slider
     * 
     * DELETE /admin/api/sliders/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Find slider by ID and type
            $slider = Slider::where('type', 'slider')->find($id);
            
            if (!$slider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slider không tồn tại'
                ], 404);
            }
            
            // Delete slider
            $slider->delete();
            
            // Clear cache
            Cache::forget('home_sliders_v1');
            Cache::forget('home_sliderms_v1');
            
            return response()->json([
                'success' => true,
                'message' => 'Xóa slider thành công'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('删除slider失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'slider_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '删除slider失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Update slider status
     * 
     * PATCH /admin/api/sliders/{id}/status
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            // Find slider by ID and type
            $slider = Slider::where('type', 'slider')->find($id);
            
            if (!$slider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slider không tồn tại'
                ], 404);
            }
            
            // Validate status parameter
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:0,1',
            ], [
                'status.required' => 'Trạng thái không được bỏ trống',
                'status.in' => 'Trạng thái phải là 0 hoặc 1',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update status
            $slider->update([
                'status' => $request->status,
            ]);
            
            // Clear cache
            Cache::forget('home_sliders_v1');
            Cache::forget('home_sliderms_v1');
            
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công',
                'data' => new SliderResource($slider->load('user')),
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('更新slider状态失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'slider_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '更新slider状态失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }
}
