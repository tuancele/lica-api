<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Recommendation\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\ProductDeal;
use App\Modules\Deal\Models\SaleDeal;

class RecommendationController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * 获取推荐产品
     */
    public function getRecommendations(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 12);
        $offset = (int) $request->get('offset', 0);
        $excludeIds = $request->get('exclude', []);
        
        if (!is_array($excludeIds)) {
            $excludeIds = explode(',', $excludeIds);
        }
        $excludeIds = array_filter(array_map('intval', $excludeIds));

        $products = $this->recommendationService->getRecommendedProducts($limit, $excludeIds, $offset);

        $formattedProducts = $products->map(function($product) {
            $priceInfo = $product->price_info ?? (object)[
                'price' => $product->price ?? 0,
                'original_price' => $product->price ?? 0,
                'type' => 'normal',
                'label' => ''
            ];

            $rates = $product->rates ?? collect();
            $totalRating = $rates->sum('rate');
            $ratingCount = $rates->count();
            $averageRating = $ratingCount > 0 ? $totalRating / $ratingCount : 0;

            // 获取购买数量
            $totalSold = \Illuminate\Support\Facades\DB::table('orderdetail')
                ->join('orders', 'orderdetail.order_id', '=', 'orders.id')
                ->where('orderdetail.product_id', $product->id)
                ->where('orders.ship', 2)
                ->where('orders.status', '!=', 2)
                ->sum('orderdetail.qty') ?? 0;

            // 检查产品是否参与 deal sốc
            $dealDiscountPercent = 0;
            $dealName = null;
            try {
                $now = strtotime(date('Y-m-d H:i:s'));
                $deal_id = ProductDeal::where('product_id', $product->id)->where('status', 1)->pluck('deal_id')->toArray();
                if (!empty($deal_id)) {
                    $activeDeal = Deal::whereIn('id', $deal_id)
                        ->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]])
                        ->first();
                    
                    if ($activeDeal) {
                        $dealName = $activeDeal->name ?? 'Deal sốc';
                        $variant = \App\Modules\Product\Models\Variant::select('price','sale')->where('product_id', $product->id)->first();
                        if ($variant && isset($variant->price) && $variant->price > 0) {
                            $saleDeal = SaleDeal::where([['deal_id', $activeDeal->id], ['product_id', $product->id], ['status', '1']])->first();
                            if ($saleDeal && isset($saleDeal->price)) {
                                $dealDiscountPercent = round(($variant->price - $saleDeal->price) / ($variant->price / 100));
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // 静默处理错误，不返回 deal 信息
                $dealName = null;
                $dealDiscountPercent = 0;
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => getImage($product->image ?? ''),
                'price' => $priceInfo->price,
                'original_price' => $priceInfo->original_price,
                'price_label' => $priceInfo->label,
                'url' => url('/' . $product->slug),
                'brand_name' => $product->brand->name ?? null,
                'brand_url' => $product->brand ? route('home.brand', ['url' => $product->brand->slug]) : null,
                'rating' => round($averageRating, 1),
                'review_count' => $ratingCount,
                'total_sold' => (int) $totalSold,
                'stock' => $product->stock ?? 1,
                'deal_name' => $dealName,
                'deal_discount_percent' => $dealDiscountPercent,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedProducts,
            'count' => $formattedProducts->count(),
        ]);
    }

    /**
     * 跟踪用户行为
     */
    public function trackBehavior(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'behavior_type' => 'required|string|in:view,click,add_to_cart,purchase',
            'duration' => 'nullable|integer|min:0',
            'scroll_depth' => 'nullable|integer|min:0|max:100',
            'clicked_product' => 'nullable|boolean',
            'viewed_gallery' => 'nullable|boolean',
            'read_description' => 'nullable|boolean',
            'page_title' => 'nullable|string|max:255',
        ]);

        $this->recommendationService->trackBehavior(
            $request->product_id,
            $request->behavior_type,
            [
                'duration' => $request->duration ?? 0,
                'scroll_depth' => $request->scroll_depth ?? 0,
                'clicked_product' => $request->clicked_product ?? false,
                'viewed_gallery' => $request->viewed_gallery ?? false,
                'read_description' => $request->read_description ?? false,
                'page_title' => $request->page_title ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Behavior tracked successfully',
        ]);
    }

    /**
     * 获取浏览历史
     */
    public function getViewHistory(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 20);
        $products = $this->recommendationService->getUserViewHistory($limit);

        $formattedProducts = $products->map(function($product) {
            $priceInfo = $product->price_info ?? (object)[
                'price' => 0,
                'original_price' => 0,
                'type' => 'normal',
                'label' => ''
            ];

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => getImage($product->image ?? ''),
                'price' => $priceInfo->price,
                'original_price' => $priceInfo->original_price,
                'price_label' => $priceInfo->label,
                'url' => url('/' . $product->slug),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedProducts,
            'count' => $formattedProducts->count(),
        ]);
    }
}
