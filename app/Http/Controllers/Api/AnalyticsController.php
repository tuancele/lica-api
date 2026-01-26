<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Recommendation\Models\UserBehavior;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * 获取用户浏览历史.
     */
    public function getUserHistory(Request $request): JsonResponse
    {
        $sessionId = $request->get('session_id');
        $userId = $request->get('user_id');
        $limit = (int) $request->get('limit', 100);

        $query = UserBehavior::with(['product.brand'])
            ->where('behavior_type', UserBehavior::TYPE_VIEW)
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        $behaviors = $query->limit($limit)->get();

        $history = $behaviors->map(function ($behavior) {
            return [
                'product_id' => $behavior->product_id,
                'product_name' => $behavior->product->name ?? null,
                'product_brand' => $behavior->product->brand->name ?? null,
                'product_categories' => $behavior->product_categories ?? [],
                'product_ingredients' => $behavior->product_ingredients ?? [],
                'viewed_at' => $behavior->created_at->toDateTimeString(),
                'duration' => $behavior->duration,
                'scroll_depth' => $behavior->scroll_depth,
                'read_description' => $behavior->read_description,
                'viewed_gallery' => $behavior->viewed_gallery,
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => $history,
            'count' => count($history),
        ]);
    }

    /**
     * 分析用户偏好.
     */
    public function getUserPreferences(Request $request): JsonResponse
    {
        $sessionId = $request->get('session_id');
        $userId = $request->get('user_id');

        $behaviors = UserBehavior::where('behavior_type', UserBehavior::TYPE_VIEW)
            ->where(function ($query) use ($sessionId, $userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } elseif ($sessionId) {
                    $query->where('session_id', $sessionId);
                }
            })
            ->get();

        $preferences = [
            'total_views' => $behaviors->count(),
            'unique_products' => $behaviors->pluck('product_id')->unique()->count(),
            'favorite_brands' => [],
            'favorite_categories' => [],
            'favorite_ingredients' => [],
            'average_session_duration' => 0,
            'device_preferences' => [],
        ];

        $brands = $behaviors->pluck('product_brand_id')->filter()->countBy();
        $preferences['favorite_brands'] = $brands->sortDesc()->take(10)->toArray();

        $categories = $behaviors->pluck('product_categories')
            ->flatten()
            ->filter()
            ->countBy();
        $preferences['favorite_categories'] = $categories->sortDesc()->take(10)->toArray();

        $ingredients = $behaviors->pluck('product_ingredients')
            ->flatten()
            ->filter()
            ->countBy();
        $preferences['favorite_ingredients'] = $ingredients->sortDesc()->take(20)->toArray();

        $totalDuration = $behaviors->sum('duration');
        $preferences['average_session_duration'] = $behaviors->count() > 0
            ? round($totalDuration / $behaviors->count(), 2)
            : 0;

        $devices = $behaviors->pluck('device_type')->filter()->countBy();
        $preferences['device_preferences'] = $devices->toArray();

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    /**
     * 导出数据用于AI训练.
     */
    public function exportForAI(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date')
            ? Carbon::parse($request->get('start_date'))
            : Carbon::now()->subDays(30);

        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : Carbon::now();

        $limit = (int) $request->get('limit', 10000);

        $behaviors = UserBehavior::with(['product.brand'])
            ->where('behavior_type', UserBehavior::TYPE_VIEW)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $data = $behaviors->map(function ($behavior) {
            return [
                'session_id' => $behavior->session_id,
                'user_id' => $behavior->user_id,
                'product_id' => $behavior->product_id,
                'product_name' => $behavior->product->name ?? null,
                'product_brand_id' => $behavior->product_brand_id,
                'product_brand' => $behavior->product->brand->name ?? null,
                'product_categories' => $behavior->product_categories ?? [],
                'product_ingredients' => $behavior->product_ingredients ?? [],
                'product_features' => $behavior->product_features ?? [],
                'behavior_type' => $behavior->behavior_type,
                'duration' => $behavior->duration,
                'scroll_depth' => $behavior->scroll_depth,
                'clicked_product' => $behavior->clicked_product,
                'viewed_gallery' => $behavior->viewed_gallery,
                'read_description' => $behavior->read_description,
                'device_type' => $behavior->device_type,
                'country' => $behavior->country,
                'region' => $behavior->region,
                'city' => $behavior->city,
                'created_at' => $behavior->created_at->toDateTimeString(),
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => count($data),
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ]);
    }

    /**
     * 获取产品成分分析.
     */
    public function getProductIngredientAnalysis(Request $request): JsonResponse
    {
        $productId = (int) $request->get('product_id');

        if (! $productId) {
            return response()->json([
                'success' => false,
                'message' => 'Product ID is required',
            ], 400);
        }

        $behaviors = UserBehavior::where('product_id', $productId)
            ->whereNotNull('product_ingredients')
            ->get();

        $ingredientStats = [];

        foreach ($behaviors as $behavior) {
            $ingredients = is_array($behavior->product_ingredients)
                ? $behavior->product_ingredients
                : json_decode($behavior->product_ingredients, true) ?? [];

            foreach ($ingredients as $ingredient) {
                if (! isset($ingredientStats[$ingredient])) {
                    $ingredientStats[$ingredient] = [
                        'name' => $ingredient,
                        'view_count' => 0,
                        'users' => [],
                    ];
                }
                $ingredientStats[$ingredient]['view_count']++;
                $userId = $behavior->user_id ?? $behavior->session_id;
                if (! in_array($userId, $ingredientStats[$ingredient]['users'])) {
                    $ingredientStats[$ingredient]['users'][] = $userId;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => array_values($ingredientStats),
        ]);
    }
}
