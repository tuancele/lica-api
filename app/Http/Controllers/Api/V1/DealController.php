<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\SaleDeal;
use App\Services\Pricing\PriceEngineServiceInterface;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DealController extends Controller
{
    private PriceEngineServiceInterface $priceEngine;
    private WarehouseServiceInterface $warehouseService;

    public function __construct(
        PriceEngineServiceInterface $priceEngine,
        WarehouseServiceInterface $warehouseService
    ) {
        $this->priceEngine = $priceEngine;
        $this->warehouseService = $warehouseService;

        // Inject WarehouseService into PriceEngine for stock checks
        if (method_exists($this->priceEngine, 'setWarehouseService')) {
            $this->priceEngine->setWarehouseService($warehouseService);
        }
    }

    /**
     * Get all active Deal bundles for frontend display.
     *
     * GET /api/v1/deals/active-bundles
     */
    public function getActiveBundles(Request $request): JsonResponse
    {
        try {
            $now = time();

            $deals = Deal::with([
                'products.product',
                'products.variant',
                'sales.product',
                'sales.variant',
            ])
                ->where('status', '1')
                ->where('start', '<=', $now)
                ->where('end', '>=', $now)
                ->orderBy('start', 'asc')
                ->get();

            $data = $deals->map(function (Deal $deal) {
                return $this->formatDealBundle($deal);
            })->values();

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => $data->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[DealController] getActiveBundles failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách Deal Sốc',
            ], 500);
        }
    }

    /**
     * Get single Deal bundle detail for frontend display.
     *
     * GET /api/v1/deals/{id}/bundle
     */
    public function showBundle(int $id): JsonResponse
    {
        try {
            $now = time();

            $deal = Deal::with([
                'products.product',
                'products.variant',
                'sales.product',
                'sales.variant',
            ])
                ->where('id', $id)
                ->where('status', '1')
                ->where('start', '<=', $now)
                ->where('end', '>=', $now)
                ->first();

            if (! $deal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal không tồn tại hoặc không còn hoạt động',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatDealBundle($deal),
            ]);
        } catch (\Throwable $e) {
            Log::error('[DealController] showBundle failed', [
                'deal_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy chi tiết Deal Sốc',
            ], 500);
        }
    }

    /**
     * Format one Deal bundle with pricing info for FE.
     */
    private function formatDealBundle(Deal $deal): array
    {
        $mainProducts = [];
        foreach ($deal->products as $productDeal) {
            if (! $productDeal->product) {
                continue;
            }

            $mainProducts[] = [
                'id' => $productDeal->id,
                'product_id' => $productDeal->product_id,
                'variant_id' => $productDeal->variant_id,
                'status' => (string) $productDeal->status,
                'product' => [
                    'id' => $productDeal->product->id,
                    'name' => $productDeal->product->name,
                    'slug' => $productDeal->product->slug,
                    'image' => $productDeal->product->image,
                    'has_variants' => (int) $productDeal->product->has_variants,
                ],
                'variant' => $productDeal->variant ? [
                    'id' => $productDeal->variant->id,
                    'sku' => $productDeal->variant->sku,
                    'option1_value' => $productDeal->variant->option1_value,
                ] : null,
            ];
        }

        $saleProducts = [];
        foreach ($deal->sales as $saleDeal) {
            if (! $saleDeal->product) {
                continue;
            }

            $saleProducts[] = $this->formatSaleProduct($saleDeal);
        }

        return [
            'id' => $deal->id,
            'name' => $deal->name,
            'start' => $deal->start,
            'end' => $deal->end,
            'status' => (string) $deal->status,
            'limited' => (int) $deal->limited,
            'is_active' => true,
            'products' => $mainProducts,
            'sale_products' => $saleProducts,
        ];
    }

    /**
     * Format one sale product with effective price (FlashSale > Promo > Deal > Base).
     */
    private function formatSaleProduct(SaleDeal $saleDeal): array
    {
        $product = $saleDeal->product;
        $variant = $saleDeal->variant;

        $productId = $product->id;
        $variantId = $variant ? $variant->id : null;

        // Check remaining quota (qty now represents remaining slots after Shopee-style decrement)
        $remainingQuota = max(0, (int) $saleDeal->qty);

        $displayPrice = $this->priceEngine->calculateDisplayPrice($productId, $variantId);

        $finalPrice = $displayPrice['price'];
        $finalType = $displayPrice['type'];
        $finalLabel = $displayPrice['label'];
        $originalPrice = $displayPrice['original_price'];
        $discountPercent = $displayPrice['discount_percent'] ?? 0;

        // Apply Deal price only when there is no FlashSale/Promotion
        if ($displayPrice['type'] === 'normal') {
            $dealPrice = (float) $saleDeal->price;
            if ($dealPrice > 0 && $dealPrice < $originalPrice) {
                $finalPrice = $dealPrice;
                $finalType = 'deal';
                $finalLabel = 'Deal Sốc';
                $discountPercent = $this->calculateDiscountPercent($originalPrice, $dealPrice);
            }
        }

        $stock = null;
        $available = true;
        try {
            if ($variantId !== null) {
                $stockInfo = $this->warehouseService->getVariantStock($variantId);
                $stock = (int) ($stockInfo['current_stock'] ?? 0);
                if ($stock <= 0) {
                    $available = false;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[DealController] Failed to get stock for sale product', [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'id' => $saleDeal->id,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'qty' => (int) $saleDeal->qty,
            'status' => (string) $saleDeal->status,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->image,
                'has_variants' => (int) $product->has_variants,
            ],
            'variant' => $variant ? [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'option1_value' => $variant->option1_value,
            ] : null,
            'deal_price' => (float) $saleDeal->price,
            'effective_price' => [
                'price' => (float) $finalPrice,
                'original_price' => (float) $originalPrice,
                'type' => $finalType,
                'label' => $finalLabel,
                'discount_percent' => (int) $discountPercent,
            ],
            'stock' => $stock,
            'remaining_quota' => $remainingQuota,
            'available' => $available && $remainingQuota > 0,
        ];
    }

    private function calculateDiscountPercent(float $originalPrice, float $salePrice): int
    {
        if ($originalPrice <= 0 || $salePrice >= $originalPrice) {
            return 0;
        }

        return (int) round((($originalPrice - $salePrice) / $originalPrice) * 100);
    }
}
