<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\CategoryResource;
use App\Modules\Product\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * 获取热门分类列表（用于移动端和首页）
     * 
     * REST API 标准：
     * GET /api/categories/featured
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getFeaturedCategories(Request $request): JsonResponse
    {
        try {
            // Bypass cache for real-time data integrity
            $categories = Product::select('id', 'name', 'slug', 'image')
                ->where([['status', '1'], ['type', 'taxonomy'], ['feature', '1']])
                ->orderBy('sort', 'asc')
                ->get();

            // 使用 Resource 类格式化响应，保持一致性
            $formattedCategories = CategoryResource::collection($categories);

            return response()->json([
                'success' => true,
                'data' => $formattedCategories,
                'count' => $formattedCategories->count(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('获取热门分类失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '获取分类数据失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * 获取所有分类列表
     * 
     * REST API 标准：
     * GET /api/categories
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 50);
            $offset = (int) $request->get('offset', 0);
            $featured = $request->get('featured', false);

            $query = Product::select('id', 'name', 'slug', 'image')
                ->where([['status', '1'], ['type', 'taxonomy']]);

            if ($featured) {
                $query->where('feature', '1');
            }

            $categories = $query->orderBy('sort', 'asc')
                ->skip($offset)
                ->take($limit)
                ->get();

            $total = $query->count();

            $formattedCategories = CategoryResource::collection($categories);

            return response()->json([
                'success' => true,
                'data' => $formattedCategories,
                'count' => $formattedCategories->count(),
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ], 200);
        } catch (\Exception $e) {
            Log::error('获取分类列表失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '获取分类列表失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * 获取单个分类详情
     * 
     * REST API 标准：
     * GET /api/categories/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = Product::select('id', 'name', 'slug', 'image')
                ->where([['id', $id], ['status', '1'], ['type', 'taxonomy']])
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => '分类不存在'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
            ], 200);
        } catch (\Exception $e) {
            Log::error('获取分类详情失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'category_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '获取分类详情失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * 获取层级分类（用于前端 4-level picker）
     *
     * GET /api/categories/hierarchical
     *
     * @return JsonResponse
     */
    public function hierarchical(): JsonResponse
    {
        try {
            $items = Product::select('id', 'name', 'cat_id', 'sort')
                ->where([['status', '1'], ['type', 'taxonomy']])
                ->orderBy('sort', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $data = $items->map(function (Product $cat) {
                return [
                    'id' => (int) $cat->id,
                    'title' => (string) ($cat->name ?? ''),
                    'parent_id' => (int) ($cat->cat_id ?? 0),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            Log::error('获取层级分类失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '获取层级分类失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }
}
