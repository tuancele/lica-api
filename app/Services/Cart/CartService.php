<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Modules\Address\Models\Address;
use App\Modules\Config\Models\Config;
use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\ProductDeal;
use App\Modules\Deal\Models\SaleDeal;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Province;
use App\Modules\Location\Models\Ward;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderDetail;
use App\Modules\Pick\Models\Pick;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\Promotion\Models\Promotion;
use App\Services\FlashSale\FlashSaleStockService;
use App\Services\PriceCalculationService;
use App\Services\Pricing\PriceEngineServiceInterface;
use App\Services\Warehouse\WarehouseServiceInterface;
use App\Themes\Website\Models\Cart;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Cart Service.
 *
 * Centralized service for cart operations
 * Supports both Session-based (guest) and Database-based (authenticated users)
 */
class CartService
{
    protected PriceCalculationService $priceService;
    protected FlashSaleStockService $flashSaleStockService;
    protected PriceEngineServiceInterface $priceEngine;
    protected WarehouseServiceInterface $warehouseService;
    protected \App\Services\Cart\Contracts\CartPricingServiceInterface $cartPricingService;
    private const DEAL_EXHAUSTED_MESSAGE = 'Quà tặng Deal Sốc đã hết, giá được chuyển về giá thường/khuyến mại.';

    /**
     * Get cart items directly from session (no caching, always fresh)
     * Bước 1: Loại bỏ thuộc tính $items khỏi bộ nhớ Service.
     */
    private function getCartItemsFromSession(): array
    {
        $oldCart = session()->has('cart') ? session()->get('cart') : null;
        $cart = new Cart($oldCart);
        $items = $cart->items ?? [];

        if (! is_array($items)) {
            $items = [];
        }

        return $items;
    }

    public function __construct(
        PriceCalculationService $priceService,
        FlashSaleStockService $flashSaleStockService,
        PriceEngineServiceInterface $priceEngine,
        WarehouseServiceInterface $warehouseService,
        \App\Services\Cart\Contracts\CartPricingServiceInterface $cartPricingService
    ) {
        $this->priceService = $priceService;
        $this->flashSaleStockService = $flashSaleStockService;
        $this->priceEngine = $priceEngine;
        $this->warehouseService = $warehouseService;
        $this->cartPricingService = $cartPricingService;

        // Inject WarehouseService vào PriceEngineService
        if (method_exists($this->priceEngine, 'setWarehouseService')) {
            $this->priceEngine->setWarehouseService($warehouseService);
        }
    }

    /**
     * Get cart data.
     *
     * @param  int|null  $userId  Optional user ID
     */
    public function getCart(?int $userId = null): array
    {
        // CRITICAL: Single Source of Truth - Always read directly from session first
        // Bước 3: Đảm bảo "Single Source of Truth" cho Cart Summary
        $oldCart = session()->has('cart') ? session()->get('cart') : null;
        $cart = new Cart($oldCart);

        $items = [];
        $totalQty = 0;
        $subtotal = 0;

        foreach ($cart->items as $variantId => $item) {
            $builtItem = $this->buildCartItem((int) $variantId, (array) $item);
            if ($builtItem === null) {
                continue;
            }

            $items[] = $builtItem['item'];
            $totalQty += $builtItem['quantity'];
            $subtotal += $builtItem['subtotal'];

            // ===== THÊM LOG RUNNING TOTAL =====
            Log::info('[DEBUG_CHECKOUT] Running subtotal', [
                'after_variant_id' => (int) $variantId,
                'item_subtotal' => $builtItem['subtotal'],
                'running_subtotal' => $subtotal,
            ]);
            // ===== END LOG =====
        }

        // Get coupon info
        $coupon = null;
        $discount = 0;
        if (Session::has('ss_counpon')) {
            $couponData = Session::get('ss_counpon');
            $coupon = [
                'id' => $couponData['id'] ?? null,
                'code' => $couponData['code'] ?? '',
                'discount' => (float) ($couponData['sale'] ?? 0),
            ];
            $discount = (float) ($couponData['sale'] ?? 0);
        }

        // Get available deals
        $availableDeals = $this->getAvailableDeals($cart);

        // ===== BƯỚC 1: Tính lại tổng tiền dựa trên items (kể cả Deal Sốc) =====
        // CRITICAL: Phải dùng $it['subtotal'] thay vì $it['price'] * $qty
        // Vì $it['price'] là giá trung bình, không chính xác với mixed pricing (Flash Sale + Promotion)
        // Ví dụ: 1x350k (Flash Sale) + 1x525k (Promotion) = 875k
        // Giá trung bình = 437.5k, nhưng subtotal = 875k (đúng)
        $total = 0.0;
        foreach ($items as $it) {
            $itemSubtotal = (float) ($it['subtotal'] ?? 0);
            // Kể cả is_deal = 1 (quà tặng, mua kèm) vẫn phải cộng subtotal
            // Nếu Deal 0đ thì subtotal = 0, không làm âm tổng
            $total += $itemSubtotal;
        }
        $summaryTotal = (float) max(0, $total - $discount);

        // BƯỚC 5: Log cảnh báo nếu có item subtotal = 0 (đặc biệt là Deal Sốc)
        $zeroItems = array_filter($items, static function ($it) {
            return (float) ($it['subtotal'] ?? 0) <= 0;
        });
        if (! empty($zeroItems)) {
            Log::warning('[CartService] Found items with 0 or negative subtotal in cart summary', [
                'count' => count($zeroItems),
                'items' => array_map(static function ($it) {
                    return [
                        'variant_id' => $it['variant_id'] ?? null,
                        'subtotal' => $it['subtotal'] ?? null,
                        'is_deal' => $it['is_deal'] ?? 0,
                    ];
                }, $zeroItems),
            ]);
        }

        // ===== THÊM LOG FINAL SUMMARY =====
        Log::info('[DEBUG_CHECKOUT] Final cart summary', [
            'total_items' => count($items),
            'total_qty' => $totalQty,
            'subtotal_from_items' => $subtotal, // Tính từ newSubtotal (đúng)
            'recalculated_from_price_qty' => $total, // Tính từ price * qty (có thể sai với mixed pricing)
            'discount' => $discount,
            'final_total' => $summaryTotal,
        ]);
        // ===== END LOG =====

        // CRITICAL: Sử dụng $subtotal (tính từ newSubtotal) thay vì $total (tính từ price * qty)
        // Vì $total có thể sai với mixed pricing (Flash Sale + Promotion)
        // $subtotal đã được tính đúng từ $newSubtotal ở dòng 302
        $finalSubtotal = (float) $subtotal;
        $finalTotal = (float) max(0, $finalSubtotal - $discount);

        // Build productsWithPrice array (for cart page display)
        $productsWithPrice = [];
        foreach ($items as $item) {
            $variantId = $item['variant_id'];
            $productsWithPrice[$variantId] = [
                'total_price' => (float) ($item['subtotal'] ?? 0),
                'price_breakdown' => $item['price_breakdown'] ?? null,
                'warning' => $item['warning'] ?? null,
                'deal_warning' => $item['deal_warning'] ?? null,
                'flash_sale_remaining' => $item['flash_sale_remaining'] ?? 0,
            ];
        }

        // Calculate deal_counts (for cart page)
        $deal_counts = [];
        foreach ($cart->items as $item) {
            if (isset($item['is_deal']) && $item['is_deal'] == 1) {
                $now = strtotime(date('Y-m-d H:i:s'));
                $saledeal = SaleDeal::where('product_id', $item['item']['product_id'])
                    ->whereHas('deal', function ($query) use ($now) {
                        $query->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
                    })->where('status', '1')->first();
                if ($saledeal) {
                    $deal_counts[$saledeal->deal_id] = ($deal_counts[$saledeal->deal_id] ?? 0) + 1;
                }
            }
        }

        return [
            'items' => $items,
            'summary' => [
                'total_qty' => $totalQty,
                'subtotal' => $finalSubtotal, // Dùng subtotal tính từ newSubtotal (đúng)
                'discount' => $discount,
                'shipping_fee' => 0,
                'total' => $finalTotal, // Dùng finalTotal tính từ finalSubtotal (đúng)
            ],
            'coupon' => $coupon,
            'available_deals' => $availableDeals,
            'products_with_price' => $productsWithPrice, // For cart page display
            'deal_counts' => $deal_counts, // For cart page deal tracking
        ];
    }

    /**
     * Build a normalized cart item with full pricing & deal logic.
     *
     * This method is extracted from the original loop in getCart()
     * and preserves the exact behavior (pricing, Deal Sốc, logs).
     */
    private function buildCartItem(int $variantId, array $item): ?array
    {
        $variant = Variant::with(['product', 'color', 'size'])->find($variantId);
        if (! $variant || ! $variant->product) {
            return null;
        }

        $product = $variant->product;
        $quantity = (int) ($item['qty'] ?? 1);

        // QUAN TRỌNG: Tính lại giá với số lượng thực tế từ PriceEngineService
        // Không tin tưởng vào giá lưu sẵn trong Session
        // Phase 2: mọi call vào PriceEngineService đều đi qua CartPricingService
        $priceWithQuantity = $this->cartPricingService->calculateItemPriceWithQuantity(
            $product->id,
            $variantId,
            $quantity
        );

        // Lấy giá cũ từ session để so sánh (logging)
        $oldPrice = (float) ($item['price'] ?? 0);
        $oldSubtotal = $oldPrice * $quantity;

        // Sử dụng giá mới từ PriceEngineService
        $newPrice = $quantity > 0 ? ($priceWithQuantity['total_price'] / $quantity) : 0; // Giá trung bình
        $newSubtotal = (float) $priceWithQuantity['total_price'];

        // ===== THÊM LOG DEBUG =====
        Log::info('[DEBUG_CHECKOUT] Item price calculation', [
            'variant_id' => $variantId,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'is_deal' => $item['is_deal'] ?? 0,
            'old_price_from_session' => $oldPrice,
            'new_price_from_engine' => $newPrice,
            'new_subtotal' => $newSubtotal,
            'price_breakdown' => $priceWithQuantity['price_breakdown'] ?? null,
            'deal_unavailable' => false, // Will be set below
        ]);
        // ===== END LOG =====

        // Deal Sốc pricing & fallback:
        // - Nếu là quà Deal Sốc và còn quota/kho: áp dụng giá Deal theo thứ tự ưu tiên
        //   Flash Sale > Promotion > Deal > Base (Deal chỉ override khi đang ở price type = normal)
        // - Nếu quỹ/kho đã hết: hạ về giá thường/promo (giữ PriceEngine), gắn cảnh báo
        $dealUnavailable = false;
        $dealWarning = null;
        if (! empty($item['is_deal'])) {
            $dealCheck = $this->validateDealAvailability($product->id, $variantId, $quantity);
            if (! $dealCheck['available']) {
                $dealUnavailable = true;
                $dealWarning = $dealCheck['message'];
                // Giữ newPrice/newSubtotal từ PriceEngine (đã là giá thường/promo)
            } else {
                // ===== CRITICAL: Logic 2 - Sản phẩm mua kèm (Deal Sốc) LUÔN lấy giá từ Deal Sốc =====
                // Nếu là sản phẩm mua kèm (is_deal = 1), LUÔN áp dụng giá Deal Sốc, bất kể có Flash Sale/Promotion
                $isDealItem = ! empty($item['is_deal']) && (int) $item['is_deal'] === 1;

                if ($isDealItem) {
                    // Sản phẩm mua kèm: LUÔN lấy giá từ Deal Sốc (kể cả 0đ)
                    try {
                        $dealPrice = $this->getDealPrice($product->id, $variantId);
                        $newPrice = $dealPrice;
                        $newSubtotal = $dealPrice * $quantity;

                        // CRITICAL: Cập nhật priceWithQuantity['total_price'] để đảm bảo tính tổng đúng
                        $priceWithQuantity['total_price'] = $newSubtotal;

                        // Ghi đè breakdown để FE hiển thị đúng
                        $priceWithQuantity['price_breakdown'] = [
                            [
                                'type' => 'deal',
                                'quantity' => $quantity,
                                'unit_price' => $dealPrice,
                                'subtotal' => $newSubtotal,
                            ],
                        ];

                        Log::info('[CartService] Deal Sốc price applied (mua kèm - always use Deal price)', [
                            'product_id' => $product->id,
                            'variant_id' => $variantId,
                            'deal_price' => $dealPrice,
                            'quantity' => $quantity,
                            'subtotal' => $newSubtotal,
                        ]);
                    } catch (\Throwable $e) {
                        // Nếu lỗi khi lấy Deal price, vẫn giữ giá 0đ cho Deal Sốc
                        $newPrice = 0.0;
                        $newSubtotal = 0.0;

                        // CRITICAL: Cập nhật priceWithQuantity['total_price'] để đảm bảo tính tổng đúng
                        $priceWithQuantity['total_price'] = 0.0;

                        // CRITICAL: Cập nhật priceWithQuantity['total_price'] để đảm bảo tính tổng đúng
                        $priceWithQuantity['total_price'] = 0.0;

                        Log::warning('[CartService] getDealPrice failed for Deal Sốc, keeping 0đ', [
                            'product_id' => $product->id,
                            'variant_id' => $variantId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    // Sản phẩm mua thông thường: Áp dụng giá Deal nếu thỏa điều kiện ưu tiên (không có Flash Sale/Promotion)
                    $dealPricing = $this->applyDealPriceForCartItem($product->id, $variantId, $quantity, $priceWithQuantity, false);
                    if ($dealPricing !== null) {
                        $newPrice = $dealPricing['unit_price'];
                        $newSubtotal = $dealPricing['total_price'];

                        // CRITICAL: Cập nhật priceWithQuantity['total_price'] để đảm bảo tính tổng đúng
                        $priceWithQuantity['total_price'] = $newSubtotal;

                        // Ghi đè breakdown để FE hiển thị đúng
                        if (isset($dealPricing['price_breakdown'])) {
                            $priceWithQuantity['price_breakdown'] = $dealPricing['price_breakdown'];
                        }
                    }

                    // Fallback về giá gốc nếu subtotal <= 0 và không phải Deal
                    if ($newSubtotal <= 0.0) {
                        if (! empty($variant->price) && (float) $variant->price > 0) {
                            $basePrice = (float) $variant->price;
                            $newPrice = $basePrice;
                            $newSubtotal = $basePrice * $quantity;

                            // CRITICAL: Cập nhật priceWithQuantity['total_price'] để đảm bảo tính tổng đúng
                            $priceWithQuantity['total_price'] = $newSubtotal;

                            Log::warning('[CartService] Fallback to variant price (not a Deal)', [
                                'product_id' => $product->id,
                                'variant_id' => $variantId,
                                'base_price' => $basePrice,
                            ]);
                        }
                    }
                }
            }
        }

        // CRITICAL: Đảm bảo $newSubtotal và $priceWithQuantity['total_price'] đồng bộ
        // Sau khi xử lý Deal, $priceWithQuantity['total_price'] đã được cập nhật
        // Cần đọc lại để đảm bảo $newSubtotal chính xác
        $newSubtotal = (float) $priceWithQuantity['total_price'];
        $newPrice = $quantity > 0 ? ($newSubtotal / $quantity) : 0; // Giá trung bình

        // ===== CẬP NHẬT LOG DEBUG với thông tin Deal =====
        Log::info('[DEBUG_CHECKOUT] Item price calculation (after Deal)', [
            'variant_id' => $variantId,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'is_deal' => $item['is_deal'] ?? 0,
            'old_price_from_session' => $oldPrice,
            'new_price_from_engine' => $newPrice,
            'new_subtotal' => $newSubtotal,
            'price_breakdown' => $priceWithQuantity['price_breakdown'] ?? null,
            'deal_unavailable' => $dealUnavailable,
            'deal_warning' => $dealWarning,
        ]);
        // ===== END LOG =====

        // Log nếu giá thay đổi
        if (abs($oldSubtotal - $newSubtotal) > 0.01) {
            Log::info('[CartService] Price recalculated for cart item', [
                'variant_id' => $variantId,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'old_price' => $oldPrice,
                'old_subtotal' => $oldSubtotal,
                'new_price' => $newPrice,
                'new_subtotal' => $newSubtotal,
                'price_breakdown' => $priceWithQuantity['price_breakdown'] ?? null,
                'warning' => $priceWithQuantity['warning'] ?? null,
            ]);
        }

        // Get stock (fallback cho UI; stock thực đã được PriceEngine/Warehouse check)
        // Legacy "999" sentinel is removed; fallback to 0 if unknown
        $stock = isset($variant->stock) && $variant->stock !== null
            ? (int) $variant->stock
            : 0;

        // Lấy price info cơ bản để hiển thị
        $priceInfo = $this->priceService->calculateVariantPrice($variant);

        $cartItem = [
            'variant_id' => $variantId,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => $this->formatImageUrl($product->image),
            'variant' => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'option1_value' => $variant->option1_value,
                'color' => $variant->color ? [
                    'id' => $variant->color->id,
                    'name' => $variant->color->name,
                ] : null,
                'size' => $variant->size ? [
                    'id' => $variant->size->id,
                    'name' => $variant->size->name,
                    'unit' => $variant->size->unit ?? '',
                ] : null,
            ],
            'qty' => $quantity,
            'price' => $newPrice, // Giá trung bình (để hiển thị)
            'original_price' => (float) $priceInfo->original_price,
            'subtotal' => max(0, $newSubtotal), // Tổng giá đã tính lại, không âm
            'is_deal' => isset($item['is_deal']) ? (int) $item['is_deal'] : 0,
            'deal_unavailable' => $dealUnavailable,
            'deal_warning' => $dealWarning,
            'price_info' => [
                'price' => (float) $priceInfo->price,
                'original_price' => (float) $priceInfo->original_price,
                'type' => $priceInfo->type,
                'label' => $priceInfo->label,
                'discount_percent' => $priceInfo->discount_percent ?? 0,
            ],
            // Thêm thông tin Mixed Price từ PriceEngineService
            'price_breakdown' => $priceWithQuantity['price_breakdown'] ?? null,
            'flash_sale_remaining' => $priceWithQuantity['flash_sale_remaining'] ?? 0,
            'warning' => $dealWarning ?: ($priceWithQuantity['warning'] ?? null),
            'stock' => $stock,
            'available' => $stock > 0,
        ];

        return [
            'item' => $cartItem,
            'quantity' => $quantity,
            'subtotal' => $newSubtotal,
        ];
    }

    /**
     * Bước 2: Đồng bộ hóa mảng Items trong CartService
     * Quét lại toàn bộ sản phẩm Deal Sốc trong giỏ hàng và cập nhật trạng thái is_available
     * Nếu sản phẩm đó đã được bổ sung số lượng trong Admin, cập nhật is_available = true trong Session.
     */
    public function syncDealItemsAvailability(): void
    {
        $oldCart = session()->has('cart') ? session()->get('cart') : null;
        if (! $oldCart) {
            return;
        }

        $cart = new Cart($oldCart);
        $hasChanges = false;

        foreach ($cart->items as $variantId => $item) {
            // Chỉ xử lý các item là Deal Sốc
            if (empty($item['is_deal']) || (int) $item['is_deal'] !== 1) {
                continue;
            }

            $productId = null;
            if (is_object($item['item'] ?? null)) {
                $productId = $item['item']->product_id ?? null;
            } elseif (is_array($item['item'] ?? null)) {
                $productId = $item['item']['product_id'] ?? null;
            }

            if (! $productId) {
                continue;
            }

            $quantity = (int) ($item['qty'] ?? 1);

            // Validate Deal availability với dữ liệu mới nhất từ Database
            $dealCheck = $this->validateDealAvailability($productId, $variantId, $quantity);

            // Nếu Deal đã available (Admin đã tăng số lượng), cập nhật lại Session
            if ($dealCheck['available']) {
                // Đảm bảo item có cờ is_deal = 1 và dealsale_id
                if (! isset($item['is_deal']) || (int) $item['is_deal'] !== 1) {
                    $cart->items[$variantId]['is_deal'] = 1;
                    $hasChanges = true;
                }

                Log::info('[CartService] Deal item synced - now available', [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                ]);
            } else {
                Log::warning('[CartService] Deal item still unavailable after sync', [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'message' => $dealCheck['message'],
                ]);
            }
        }

        // Nếu có thay đổi, persist lại vào Session
        if ($hasChanges) {
            session()->put('cart', $cart);
            session()->save();
            Log::info('[CartService] Cart session updated after Deal items sync');
        }
    }

    /**
     * Validate Deal availability for a given product/variant and quantity.
     */
    private function validateDealAvailability(int $productId, int $variantId, int $quantity): array
    {
        $now = time();

        $saleDealQuery = SaleDeal::where('product_id', $productId)
            ->whereHas('deal', function ($q) use ($now) {
                $q->where('status', '1')
                    ->where('start', '<=', $now)
                    ->where('end', '>=', $now);
            });

        if ($variantId) {
            $saleDealQuery->where(function ($q) use ($variantId) {
                $q->where('variant_id', $variantId)
                    ->orWhereNull('variant_id');
            });
        } else {
            $saleDealQuery->whereNull('variant_id');
        }

        $saleDeal = $saleDealQuery->first();
        if (! $saleDeal) {
            return [
                'available' => false,
                'message' => self::DEAL_EXHAUSTED_MESSAGE,
            ];
        }

        // CRITICAL: Refresh model để đảm bảo đọc đúng số lượng mới nhất từ Database
        // Điều này đảm bảo nếu Admin đã tăng số lượng, backend sẽ đọc được giá trị mới nhất
        $saleDeal->refresh();

        // Quỹ deal (Shopee style): qty là số suất còn lại
        if ((int) $saleDeal->qty < $quantity) {
            return [
                'available' => false,
                'message' => self::DEAL_EXHAUSTED_MESSAGE,
            ];
        }

        // Kiểm tra tồn kho vật lý
        try {
            $stockInfo = $this->warehouseService->getVariantStock($variantId);
            $phy = (int) ($stockInfo['current_stock'] ?? 0);
            if ($phy <= 0) {
                return [
                    'available' => false,
                    'message' => self::DEAL_EXHAUSTED_MESSAGE,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('[CartService] validateDealAvailability stock check failed', [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'available' => true,
            'message' => null,
        ];
    }

    /**
     * Lấy giá Deal Sốc thực tế cho 1 product/variant đang active.
     * Fallback: nếu không có Deal hoặc hết hạn => trả 0.
     */
    public function getDealPrice(int $productId, int $variantId): float
    {
        $now = strtotime(date('Y-m-d H:i:s'));

        // Lấy các deal_id mà product này tham gia
        $dealIds = ProductDeal::where('product_id', $productId)
            ->whereHas('deal', function ($q) use ($now) {
                $q->where('status', '1')
                    ->where('start', '<=', $now)
                    ->where('end', '>=', $now);
            })
            ->pluck('deal_id')
            ->toArray();

        if (empty($dealIds)) {
            return 0.0;
        }

        // Tìm Deal đang active
        $activeDeal = Deal::whereIn('id', $dealIds)
            ->where('status', '1')
            ->where('start', '<=', $now)
            ->where('end', '>=', $now)
            ->first();

        if (! $activeDeal) {
            return 0.0;
        }

        // Lấy giá từ SaleDeal cho variant cụ thể
        $saleDeal = SaleDeal::where([
            ['deal_id', $activeDeal->id],
            ['product_id', $productId],
            ['status', '1'],
        ])
            ->when($variantId, function ($q) use ($variantId) {
                $q->where(function ($sub) use ($variantId) {
                    $sub->where('variant_id', $variantId)
                        ->orWhereNull('variant_id');
                });
            })
            ->first();

        return $saleDeal ? (float) $saleDeal->price : 0.0;
    }

    /**
     * Áp dụng giá Deal Sốc cho item trong giỏ hàng (khi còn quota/kho).
     *
     * Rule:
     * - Nếu là sản phẩm mua kèm (is_deal = 1): LUÔN áp dụng giá Deal Sốc, bất kể có Flash Sale/Promotion hay không
     * - Nếu là sản phẩm mua thông thường: Tuân thủ quy tắc Flash Sale > Promotion > Giá gốc (Deal không override)
     * - Lấy SaleDeal.price làm dealPrice
     * - Nếu dealPrice >= 0 (kể cả 0đ) => áp dụng Deal
     *
     * @param  bool  $isDealItem  Whether this is a deal item (mua kèm) or normal item
     */
    private function applyDealPriceForCartItem(int $productId, int $variantId, int $quantity, array $priceWithQuantity, bool $isDealItem = false): ?array
    {
        if ($quantity <= 0) {
            return null;
        }

        // Tìm SaleDeal tương ứng để lấy giá Deal
        $now = time();
        $saleDealQuery = SaleDeal::where('product_id', $productId)
            ->whereHas('deal', function ($q) use ($now) {
                $q->where('status', '1')
                    ->where('start', '<=', $now)
                    ->where('end', '>=', $now);
            });

        if ($variantId) {
            $saleDealQuery->where(function ($q) use ($variantId) {
                $q->where('variant_id', $variantId)
                    ->orWhereNull('variant_id');
            });
        } else {
            $saleDealQuery->whereNull('variant_id');
        }

        /** @var SaleDeal|null $saleDeal */
        $saleDeal = $saleDealQuery->first();
        if (! $saleDeal) {
            return null;
        }

        $dealPrice = (float) ($saleDeal->price ?? 0);

        // Nếu là sản phẩm mua kèm (Deal Sốc): LUÔN áp dụng giá Deal, bất kể có Flash Sale/Promotion
        if ($isDealItem) {
            $total = $dealPrice * $quantity;

            Log::info('[CartService] Deal Sốc price applied (mua kèm)', [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'deal_price' => $dealPrice,
                'quantity' => $quantity,
                'total' => $total,
            ]);

            return [
                'unit_price' => $dealPrice,
                'total_price' => $total,
                'price_breakdown' => [
                    [
                        'type' => 'deal',
                        'quantity' => $quantity,
                        'unit_price' => $dealPrice,
                        'subtotal' => $total,
                    ],
                ],
            ];
        }

        // Nếu là sản phẩm mua thông thường: Chỉ áp dụng Deal nếu không có Flash Sale/Promotion
        if (empty($priceWithQuantity['price_breakdown']) || ! is_array($priceWithQuantity['price_breakdown'])) {
            return null;
        }

        $breakdown = $priceWithQuantity['price_breakdown'];

        // Nếu có bất kỳ dòng flashsale/promotion => giữ nguyên, Deal không override
        $hasFlashSale = false;
        $hasPromotion = false;
        foreach ($breakdown as $bd) {
            if (($bd['type'] ?? null) === 'flashsale') {
                $hasFlashSale = true;
            }
            if (($bd['type'] ?? null) === 'promotion') {
                $hasPromotion = true;
            }
        }

        if ($hasFlashSale || $hasPromotion) {
            return null;
        }

        // Đến đây tức là toàn bộ breakdown đang là "normal"
        $firstLine = $breakdown[0];
        $originalPrice = (float) ($firstLine['unit_price'] ?? 0);

        if ($originalPrice <= 0) {
            return null;
        }

        // Deal price > 0 & rẻ hơn giá gốc => áp dụng Deal
        if ($dealPrice > 0 && $dealPrice < $originalPrice) {
            $total = $dealPrice * $quantity;

            return [
                'unit_price' => $dealPrice,
                'total_price' => $total,
                'price_breakdown' => [
                    [
                        'type' => 'deal',
                        'quantity' => $quantity,
                        'unit_price' => $dealPrice,
                        'subtotal' => $total,
                    ],
                ],
            ];
        }

        // Case Deal 0đ: cho phép nếu originalPrice > 0 (chỉ cho sản phẩm mua thông thường, không phải mua kèm)
        if ($dealPrice == 0.0 && $originalPrice > 0) {
            $total = 0.0;

            return [
                'unit_price' => 0.0,
                'total_price' => $total,
                'price_breakdown' => [
                    [
                        'type' => 'deal',
                        'quantity' => $quantity,
                        'unit_price' => 0.0,
                        'subtotal' => $total,
                    ],
                ],
            ];
        }

        // Không lời hơn Base => không dùng Deal
        return null;
    }

    /**
     * Add item to cart.
     */
    public function addItem(int $variantId, int $qty, bool $isDeal = false, ?int $userId = null, bool $forceRefresh = false): array
    {
        // Bước 4: Vá lỗi Backend (addItem Force Refresh)
        // Nếu nhận được tham số force_refresh, ép Laravel phải mở lại file session từ đĩa cứng
        if ($forceRefresh) {
            // Ép Laravel phải mở lại file session từ đĩa cứng để lấy dữ liệu mới nhất
            session()->save(); // Flush any pending writes
            session()->regenerate(false); // Reload from storage (false = keep same ID)
            Log::info('[CartService] Force refresh session triggered', [
                'variant_id' => $variantId,
                'session_id' => session()->getId(),
            ]);
        }

        // Bước 1: Đọc trực tiếp từ Session (không dùng biến tạm trong bộ nhớ)

        $variant = Variant::with('product')->find($variantId);
        if (! $variant || ! $variant->product) {
            throw new \Exception('Sản phẩm không tồn tại');
        }

        // Validate stock from Warehouse (single source of truth)
        $product = $variant->product;
        $variantStock = 0;
        $dealStock = 0;
        $physicalStock = 0;

        try {
            $stockInfo = $this->warehouseService->getVariantStock($variantId);
            $physicalStock = (int) ($stockInfo['physical_stock'] ?? 0);
            $dealStock = (int) ($stockInfo['deal_stock'] ?? 0);

            // For Deal items: use deal_stock (from deal_hold in inventory_stocks)
            // For normal items: use available_stock (physical - reserved - flash_sale - deal)
            if ($isDeal) {
                $variantStock = $dealStock; // Use deal_stock for Deal items
            } else {
                $variantStock = (int) ($stockInfo['available_stock'] ?? 0);
            }
        } catch (\Throwable $e) {
            Log::warning('[CartService] getVariantStock failed in addItem', [
                'variant_id' => $variantId,
                'is_deal' => $isDeal,
                'error' => $e->getMessage(),
            ]);
            // Fallback to 0 if warehouse check fails
            $variantStock = 0;
        }

        // Check stock availability
        if ($variantStock <= 0) {
            if ($isDeal) {
                throw new \Exception('Quà tặng Deal Sốc đã hết, giá được chuyển về giá thường/khuyến mại.');
            } else {
                throw new \Exception('Phân loại đã hết hàng');
            }
        }

        // Also check physical_stock > 0 to ensure there's actual inventory
        if ($physicalStock <= 0) {
            throw new \Exception('Phân loại đã hết hàng');
        }

        // Bước 1: Đọc trực tiếp từ Session (không dùng biến tạm trong bộ nhớ)
        $currentCartItems = $this->getCartItemsFromSession();
        $currentQty = isset($currentCartItems[$variantId]) ? ($currentCartItems[$variantId]['qty'] ?? 0) : 0;

        if ($variantStock > 0 && ($currentQty + $qty) > $variantStock) {
            throw new \Exception('Số lượng vượt quá tồn kho của phân loại');
        }

        // Handle Deal price & per-order limit
        if ($isDeal) {
            // Bước 3: Sửa logic Validation trong addItem - dùng biến local từ Session
            // CRITICAL: Force flush session before reading to ensure latest data
            session()->save();

            // Đọc trực tiếp từ Session (không dùng biến tạm)
            $currentCart = session()->get('cart', null);
            $currentCartItems = [];
            if ($currentCart) {
                if (is_object($currentCart)) {
                    $currentCartItems = $currentCart->items ?? [];
                } elseif (is_array($currentCart) && isset($currentCart['items'])) {
                    $currentCartItems = $currentCart['items'];
                } elseif (is_array($currentCart)) {
                    $currentCartItems = $currentCart;
                }
            }
            if (! is_array($currentCartItems)) {
                $currentCartItems = [];
            }

            // DEBUG: Log cart state before deal limit check
            Log::info('[CartService] addItem - Cart state before deal limit check', [
                'variant_id' => $variantId,
                'is_deal' => true,
                'current_cart_items_count' => count($currentCartItems),
                'current_cart_items_keys' => array_keys($currentCartItems),
                'session_has_cart' => session()->has('cart'),
                'session_id' => session()->getId(),
            ]);

            $now = strtotime(date('Y-m-d H:i:s'));
            $saledeal = SaleDeal::with('deal')->where('product_id', $product->id)
                ->whereHas('deal', function ($query) use ($now) {
                    $query->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
                })->where('status', '1')->first();

            if ($saledeal && $saledeal->deal) {
                // Giới hạn số lượng mua kèm trong 1 đơn (lấy từ deals.limited, không phải deal_sales.qty)
                // deal_sales.qty là remaining quota, deals.limited là per-order limit
                $dealLimit = (int) ($saledeal->deal->limited ?? 0);
                if ($dealLimit > 0) {
                    // Bước 3: Sửa logic Validation trong addItem - dùng biến local từ Session
                    // Đếm số lượng deal items từ currentCartItems (đã đọc từ session ở trên)
                    $currentDealQty = 0;
                    $dealItemsInCart = [];
                    foreach ($currentCartItems as $cartVariantId => $cartItem) {
                        // Check if item is a deal item with the same deal_id
                        if (! empty($cartItem['is_deal']) && isset($cartItem['deal_id'])) {
                            if ((int) $cartItem['deal_id'] === (int) $saledeal->deal_id) {
                                $itemQty = (int) ($cartItem['qty'] ?? 0);
                                $currentDealQty += $itemQty;
                                $dealItemsInCart[] = [
                                    'variant_id' => $cartVariantId,
                                    'product_id' => $cartItem['item']['product_id'] ?? null,
                                    'qty' => $itemQty,
                                    'deal_id' => $cartItem['deal_id'],
                                ];
                            }
                        }
                    }

                    // Bước 4: Logs đối soát
                    Log::info('[CART_VALIDATION] Kiểm tra Deal ID: '.$saledeal->deal_id.' | Số lượng trong giỏ FRESH: '.$currentDealQty, [
                        'variant_id' => $variantId,
                        'deal_id' => $saledeal->deal_id,
                        'deal_limit' => $dealLimit,
                        'current_deal_qty' => $currentDealQty,
                        'current_cart_items_count' => count($currentCartItems),
                        'deal_items_in_cart' => $dealItemsInCart,
                        'all_current_cart_items_keys' => array_keys($currentCartItems),
                    ]);

                    // Check if variant already in cart (from currentCartItems read from session)
                    $alreadyInCart = isset($currentCartItems[$variantId]) && ! empty($currentCartItems[$variantId]['is_deal']);

                    // CRITICAL: Chặn hoàn toàn việc mua vượt mức deal
                    // Không cho phép clamp hoặc điều chỉnh số lượng
                    // Nếu vượt limit thì throw exception ngay lập tức
                    if (($currentDealQty + $qty) > $dealLimit) {
                        $remaining = $dealLimit - $currentDealQty;
                        if ($remaining <= 0) {
                            throw new \Exception('Bạn đã đạt giới hạn tối đa '.$dealLimit.' sản phẩm cho chương trình Deal này. Không thể thêm nữa.');
                        } else {
                            throw new \Exception('Bạn chỉ có thể thêm tối đa '.$remaining.' sản phẩm nữa cho chương trình Deal này (giới hạn: '.$dealLimit.' sản phẩm).');
                        }
                    }
                }
                $variant->price = $saledeal->price;
            }
        }

        // CRITICAL: Get Cart object from session for add operation
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);

        // Add to cart
        if ($qty > 0) {
            $cart->add($variant, $variantId, $qty, $isDeal ? 1 : 0);
        }

        // Save cart directly to session (same as old controller)
        // The Cart object will be serialized automatically by Laravel
        Session::put('cart', $cart);

        // Force save session to ensure persistence
        session()->save();
        Session::save();

        // Session đã được persist ở trên, không cần sync

        return [
            'total_qty' => $cart->totalQty,
            'item' => [
                'variant_id' => $variantId,
                'qty' => $qty,
                'price' => (float) $cart->items[$variantId]['price'],
            ],
        ];
    }

    /**
     * Update item quantity.
     */
    public function updateItem(int $variantId, int $qty, ?int $userId = null): array
    {
        if ($qty <= 0) {
            return $this->removeItem($variantId, $userId);
        }

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);

        if (! isset($cart->items[$variantId])) {
            throw new \Exception('Sản phẩm không tồn tại trong giỏ hàng');
        }

        $item = $cart->items[$variantId];

        // CRITICAL: Chặn hoàn toàn việc thay đổi số lượng deal items
        // Deal items phải có số lượng cố định, không thể tăng/giảm
        if (! empty($item['is_deal']) && (int) $item['is_deal'] === 1) {
            throw new \Exception('Không thể thay đổi số lượng sản phẩm Deal Sốc. Sản phẩm Deal Sốc có số lượng cố định.');
        }

        // CRITICAL: Kiểm tra deal limit nếu là deal item
        if (! empty($item['is_deal']) && isset($item['deal_id'])) {
            $now = strtotime(date('Y-m-d H:i:s'));
            $saledeal = SaleDeal::with('deal')->where('product_id', $item['item']['product_id'])
                ->whereHas('deal', function ($query) use ($now) {
                    $query->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
                })->where('status', '1')->first();

            if ($saledeal && $saledeal->deal) {
                $dealLimit = (int) ($saledeal->deal->limited ?? 0);
                if ($dealLimit > 0) {
                    // Đếm số lượng deal items hiện có trong giỏ (trừ item đang update)
                    $currentDealQty = 0;
                    foreach ($cart->items as $cartVariantId => $cartItem) {
                        if ($cartVariantId === $variantId) {
                            continue; // Bỏ qua item đang update
                        }
                        if (! empty($cartItem['is_deal']) && isset($cartItem['deal_id'])) {
                            if ((int) $cartItem['deal_id'] === (int) $saledeal->deal_id) {
                                $currentDealQty += (int) ($cartItem['qty'] ?? 0);
                            }
                        }
                    }

                    // Kiểm tra nếu số lượng mới vượt limit
                    if (($currentDealQty + $qty) > $dealLimit) {
                        $remaining = $dealLimit - $currentDealQty;
                        if ($remaining <= 0) {
                            throw new \Exception('Bạn đã đạt giới hạn tối đa '.$dealLimit.' sản phẩm cho chương trình Deal này. Không thể tăng số lượng.');
                        } else {
                            throw new \Exception('Bạn chỉ có thể tăng tối đa '.$remaining.' sản phẩm nữa cho chương trình Deal này (giới hạn: '.$dealLimit.' sản phẩm).');
                        }
                    }
                }
            }
        }

        // QUAN TRỌNG: Kiểm tra tồn kho thực tế từ Warehouse API
        $variant = Variant::with('product')->find($variantId);
        if ($variant) {
            try {
                $stockInfo = $this->warehouseService->getVariantStock($variantId);
                $physicalStock = (int) ($stockInfo['current_stock'] ?? 0);

                if ($qty > $physicalStock) {
                    throw new \Exception("Rất tiếc, sản phẩm này chỉ còn tối đa {$physicalStock} sản phẩm trong kho. Vui lòng điều chỉnh lại số lượng.");
                }
            } catch (\Exception $e) {
                // Nếu lỗi từ WarehouseService, fallback về kiểm tra stock cũ (không dùng sentinel 999)
                $product = $variant->product;
                $variantStock = isset($variant->stock) && $variant->stock !== null
                    ? (int) $variant->stock
                    : 0;

                if ($variantStock > 0 && $qty > $variantStock) {
                    throw new \Exception('Số lượng vượt quá tồn kho');
                }

                // Nếu là lỗi từ WarehouseService về tồn kho, throw lại
                if (strpos($e->getMessage(), 'chỉ còn tối đa') !== false) {
                    throw $e;
                }
            }
        }

        $cart->update($variantId, $qty);

        // Validate deals if qty is 0
        if ($qty <= 0) {
            $this->validateDeals($cart);
        }

        if (count($cart->items) > 0) {
            // Save cart directly to session (same as old controller)
            Session::put('cart', $cart);
        } else {
            Session::forget('cart');
            Session::forget('ss_counpon');
        }
        // Force save session to ensure persistence
        session()->save();
        Session::save();

        $discount = Session::has('ss_counpon') ? Session::get('ss_counpon')['sale'] : 0;

        return [
            'variant_id' => $variantId,
            'qty' => $qty,
            'subtotal' => (float) ($cart->items[$variantId]['price'] * $qty),
            'summary' => [
                'total_qty' => $cart->totalQty,
                'subtotal' => (float) $cart->totalPrice,
                'discount' => (float) $discount,
                'total' => (float) ($cart->totalPrice - $discount),
            ],
        ];
    }

    /**
     * Remove item from cart - SIMPLIFIED VERSION
     * Only removes the requested item, no automatic removal of related items.
     */
    public function removeItem(int $variantId, ?int $userId = null): array
    {
        // Bước 1: Đọc trực tiếp từ Session (không dùng biến tạm trong bộ nhớ)
        // Get Cart object from session for operations
        $oldCart = session()->has('cart') ? session()->get('cart') : null;
        $cart = new Cart($oldCart);

        // DEBUG: Log cart state before removal
        Log::info('[CartService] removeItem - Cart state before', [
            'variant_id' => $variantId,
            'cart_items_count' => count($cart->items),
            'cart_items_keys' => array_keys($cart->items),
            'item_exists' => isset($cart->items[$variantId]),
            'session_has_cart' => session()->has('cart'),
            'session_id' => session()->getId(),
        ]);

        // Check if item exists
        if (! isset($cart->items[$variantId])) {
            // Item doesn't exist - return current cart state (idempotent)
            Log::warning('[CartService] removeItem - Item not found', [
                'variant_id' => $variantId,
                'available_items' => array_keys($cart->items),
            ]);
            $discount = Session::has('ss_counpon') ? Session::get('ss_counpon')['sale'] : 0;

            return [
                'removed_variant_ids' => [],
                'summary' => [
                    'total_qty' => $cart->totalQty,
                    'subtotal' => (float) $cart->totalPrice,
                    'discount' => (float) $discount,
                    'total' => (float) ($cart->totalPrice - $discount),
                ],
            ];
        }

        // Store items count before removal
        $itemsCountBefore = count($cart->items);
        $itemsKeysBefore = array_keys($cart->items);

        // Determine item info for cascading removal
        $itemToRemove = $cart->items[$variantId] ?? null;
        $removedRelated = [];

        // If removing main product => remove related deal items
        if ($itemToRemove && (empty($itemToRemove['is_deal']) || $itemToRemove['is_deal'] == 0)) {
            $mainProductId = $itemToRemove['item']['product_id'] ?? null;
            if ($mainProductId) {
                $removedRelated = $this->removeRelatedDealItems($cart, (int) $mainProductId);
            }
        }

        // Remove the item (simple - just call Cart model's removeItem)
        $cart->removeItem($variantId);

        // Bước 2: Ép ghi Session vật lý trong removeItem
        // IMPORTANT: persist session immediately after removal to avoid needing F5 for next add
        // Thứ tự thực hiện: unset -> put -> save
        if (count($cart->items) > 0) {
            session()->put('cart', $cart);
        } else {
            session()->forget('cart');
            session()->forget('ss_counpon');
        }

        // CRITICAL: Ép PHP ghi xuống file session ngay lập tức
        session()->save();

        // DEBUG: Log cart state after removal (including session verification)
        $sessionCartAfter = Session::has('cart') ? Session::get('cart') : null;
        $sessionCartItems = $sessionCartAfter ? (is_object($sessionCartAfter) ? $sessionCartAfter->items : ($sessionCartAfter['items'] ?? [])) : [];
        Log::info('[CartService] removeItem - Cart state after removal', [
            'variant_id' => $variantId,
            'items_count_before' => $itemsCountBefore,
            'items_count_after' => count($cart->items),
            'items_keys_before' => $itemsKeysBefore,
            'items_keys_after' => array_keys($cart->items),
            'removed_item' => $variantId,
            'session_has_cart' => Session::has('cart'),
            'session_cart_items_count' => count($sessionCartItems),
            'session_cart_items_keys' => array_keys($sessionCartItems),
        ]);

        // Save session (already persisted above); keep logs for debugging
        if (count($cart->items) > 0) {
            Log::info('[CartService] removeItem - Session put cart', [
                'items_count' => count($cart->items),
            ]);
        } else {
            Log::info('[CartService] removeItem - Session forget cart (empty)');
        }

        // Recalculate cart totals using getCart to ensure single source of truth
        $cartData = $this->getCart($userId);

        return [
            'removed_variant_ids' => array_values(array_unique(array_merge([$variantId], $removedRelated))),
            'summary' => $cartData['summary'],
            'items' => $cartData['items'],
        ];
    }

    /**
     * Apply coupon.
     */
    public function applyCoupon(string $code, ?int $userId = null): array
    {
        if (Session::has('ss_counpon')) {
            throw new \Exception('Mã giảm không được dùng chung với mã khác');
        }

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);

        $promotion = Promotion::where([
            ['status', '1'],
            ['start', '<=', date('Y-m-d')],
            ['end', '>=', date('Y-m-d')],
            ['order_sale', '<=', $cart->totalPrice],
            ['code', $code],
        ])->first();

        if (! $promotion) {
            throw new \Exception('Mã khuyến mãi không khả dụng');
        }

        $count = Order::where('promotion_id', $promotion->id)->count();
        if ($count >= $promotion->number) {
            throw new \Exception('Mã đã hết lượt sử dụng');
        }

        $sale = ($promotion->unit == 0)
            ? round(($cart->totalPrice / 100) * $promotion->value)
            : $promotion->value;

        Session::put('ss_counpon', [
            'id' => $promotion->id,
            'sale' => $sale,
            'code' => $promotion->code,
            'value' => $promotion->value,
            'unit' => $promotion->unit,
        ]);
        // Force save session to ensure persistence
        session()->save();
        Session::save(); // Force save session

        return [
            'coupon' => [
                'id' => $promotion->id,
                'code' => $promotion->code,
                'discount' => (float) $sale,
            ],
            'summary' => [
                'subtotal' => (float) $cart->totalPrice,
                'discount' => (float) $sale,
                'total' => (float) ($cart->totalPrice - $sale),
            ],
        ];
    }

    /**
     * Remove coupon.
     */
    public function removeCoupon(?int $userId = null): array
    {
        Session::forget('ss_counpon');
        // Force save session to ensure persistence
        session()->save();
        Session::save(); // Force save session

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);

        return [
            'summary' => [
                'subtotal' => (float) $cart->totalPrice,
                'discount' => 0,
                'total' => (float) $cart->totalPrice,
            ],
        ];
    }

    /**
     * Calculate shipping fee.
     *
     * @param  array  $address  Address data with province_id, district_id, ward_id, address
     * @param  int|null  $userId  Optional user ID
     * @return float Shipping fee in VND
     */
    /**
     * Parse location ID from string format (e.g., "01TTT" -> 1) or return integer as is.
     *
     * @param  mixed  $id
     */
    private function parseLocationId($id): ?int
    {
        if (is_null($id)) {
            return null;
        }

        if (is_int($id)) {
            return $id;
        }

        if (is_string($id)) {
            // Handle format like "01TTT", "008HH", "00328"
            // Extract numeric part from beginning
            if (preg_match('/^(\d+)/', $id, $matches)) {
                return (int) $matches[1];
            }

            // Try direct conversion
            if (is_numeric($id)) {
                return (int) $id;
            }
        }

        return null;
    }

    public function calculateShippingFee(array $address, ?int $userId = null): float
    {
        try {
            Log::info('[CartService] calculateShippingFee called', [
                'address' => $address,
                'user_id' => $userId,
            ]);

            // Use getCart() method to get cart data (Single Source of Truth)
            $cartData = $this->getCart($userId);
            $subtotal = $cartData['summary']['subtotal'] ?? 0;
            $discount = $cartData['summary']['discount'] ?? 0;
            $sale = $discount; // Use discount from summary

            Log::info('[CartService] calculateShippingFee cart data', [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'has_items' => ! empty($cartData['items']),
                'items_count' => count($cartData['items'] ?? []),
            ]);

            // Check free ship config
            $freeShipEnabled = $this->getConfig('free_ship');
            $freeOrderAmount = $this->getConfig('free_order');

            Log::info('[CartService] calculateShippingFee free ship check', [
                'free_ship_enabled' => $freeShipEnabled,
                'free_order_amount' => $freeOrderAmount,
                'subtotal' => $subtotal,
                'will_apply_free_ship' => ($freeShipEnabled && $subtotal >= $freeOrderAmount),
            ]);

            if ($freeShipEnabled && $subtotal >= $freeOrderAmount) {
                Log::info('[CartService] calculateShippingFee free ship applied', [
                    'subtotal' => $subtotal,
                    'free_order_amount' => $freeOrderAmount,
                ]);

                return 0;
            }

            // Check GHTK status
            $ghtkStatus = $this->getConfig('ghtk_status');
            Log::info('[CartService] calculateShippingFee GHTK status check', [
                'ghtk_status' => $ghtkStatus,
                'ghtk_status_type' => gettype($ghtkStatus),
                'is_enabled' => (bool) $ghtkStatus,
            ]);

            if (! $ghtkStatus) {
                Log::info('[CartService] calculateShippingFee GHTK disabled');

                return 0;
            }

            // Get pick address (warehouse) with relationships
            $pick = Pick::where('status', '1')->orderBy('sort', 'asc')->first();

            if (! $pick) {
                Log::warning('[CartService] calculateShippingFee: No pick address found');

                return 0;
            }

            // Parse pick address IDs from string format to integer
            $pickProvinceId = $this->parseLocationId($pick->province_id);
            $pickDistrictId = $this->parseLocationId($pick->district_id);
            $pickWardId = $this->parseLocationId($pick->ward_id);

            // Load relationships using parsed IDs
            $pickProvince = $pickProvinceId ? Province::find($pickProvinceId) : null;
            $pickDistrict = $pickDistrictId ? District::find($pickDistrictId) : null;
            $pickWard = $pickWardId ? Ward::find($pickWardId) : null;

            Log::info('[CartService] calculateShippingFee pick address loaded', [
                'pick_id' => $pick->id,
                'pick_province_id_raw' => $pick->province_id,
                'pick_province_id_parsed' => $pickProvinceId,
                'pick_district_id_raw' => $pick->district_id,
                'pick_district_id_parsed' => $pickDistrictId,
                'pick_ward_id_raw' => $pick->ward_id,
                'pick_ward_id_parsed' => $pickWardId,
                'has_province' => $pickProvince !== null,
                'has_district' => $pickDistrict !== null,
                'has_ward' => $pickWard !== null,
                'province_name' => $pickProvince->name ?? 'NULL',
                'district_name' => $pickDistrict->name ?? 'NULL',
                'ward_name' => $pickWard->name ?? 'NULL',
            ]);

            if (! $pickProvince || ! $pickDistrict || ! $pickWard) {
                Log::warning('[CartService] calculateShippingFee: Pick address relationships not found', [
                    'pick_province_id' => $pickProvinceId,
                    'pick_district_id' => $pickDistrictId,
                    'pick_ward_id' => $pickWardId,
                ]);

                return 0;
            }

            // Calculate total weight from cart items
            $weight = 0;
            $items = $cartData['items'] ?? [];

            foreach ($items as $item) {
                $variantId = $item['variant_id'] ?? null;
                $qty = $item['qty'] ?? 1;
                $itemWeight = 0;

                if ($variantId) {
                    // Load variant with product to get weight
                    $variant = Variant::with('product')->find($variantId);
                    if ($variant) {
                        // Try to get weight from variant first, then from product
                        $itemWeight = $variant->weight ?? $variant->product->weight ?? 0;
                    }
                }

                // If weight is 0 or not set, use default weight (100g per item)
                if ($itemWeight <= 0) {
                    $itemWeight = 100; // Default 100g per item
                }

                $weight += ($itemWeight * $qty);
            }

            Log::info('[CartService] calculateShippingFee weight calculated', [
                'total_weight' => $weight,
                'items_count' => count($items),
                'weight_per_item' => count($items) > 0 ? ($weight / array_sum(array_column($items, 'qty'))) : 0,
            ]);

            // Get delivery location names - parse from string format if needed
            $provinceIdRaw = $address['province_id'] ?? null;
            $districtIdRaw = $address['district_id'] ?? null;
            $wardIdRaw = $address['ward_id'] ?? null;

            $provinceId = $this->parseLocationId($provinceIdRaw);
            $districtId = $this->parseLocationId($districtIdRaw);
            $wardId = $this->parseLocationId($wardIdRaw);

            if (! $provinceId || ! $districtId || ! $wardId) {
                Log::warning('[CartService] calculateShippingFee: Missing or invalid address IDs', [
                    'province_id_raw' => $provinceIdRaw,
                    'province_id_parsed' => $provinceId,
                    'district_id_raw' => $districtIdRaw,
                    'district_id_parsed' => $districtId,
                    'ward_id_raw' => $wardIdRaw,
                    'ward_id_parsed' => $wardId,
                ]);

                return 0;
            }

            // Models now have correct primary key defined, can use find()
            $province = Province::find($provinceId);
            $district = District::find($districtId);
            $ward = Ward::find($wardId);

            if (! $province || ! $district || ! $ward) {
                Log::warning('[CartService] calculateShippingFee: Invalid delivery address', [
                    'province_id' => $provinceId,
                    'district_id' => $districtId,
                    'ward_id' => $wardId,
                    'province_found' => $province !== null,
                    'district_found' => $district !== null,
                    'ward_found' => $ward !== null,
                ]);

                return 0;
            }

            // Prepare GHTK API request data
            $info = [
                'pick_province' => $pickProvince->name ?? '',
                'pick_district' => $pickDistrict->name ?? '',
                'pick_ward' => $pickWard->name ?? '',
                'pick_street' => $pick->street ?? '',
                'pick_address' => $pick->address ?? '',
                'province' => $province->name ?? '',
                'district' => $district->name ?? '',
                'ward' => $ward->name ?? '',
                'address' => $address['address'] ?? '',
                'weight' => $weight,
                'value' => max(0, $subtotal - $sale),
                'transport' => 'road',
                'deliver_option' => 'none',
                'tags' => [0],
            ];

            Log::info('[CartService] calculateShippingFee GHTK request prepared', [
                'info' => $info,
            ]);

            // Call GHTK API
            $ghtkUrl = $this->getConfig('ghtk_url');
            $ghtkToken = $this->getConfig('ghtk_token');

            Log::info('[CartService] calculateShippingFee GHTK config', [
                'ghtk_url' => $ghtkUrl ? 'SET' : 'EMPTY',
                'ghtk_token' => $ghtkToken ? 'SET' : 'EMPTY',
            ]);

            if (empty($ghtkUrl) || empty($ghtkToken)) {
                Log::warning('[CartService] calculateShippingFee: Missing GHTK URL or Token configuration', [
                    'ghtk_url_empty' => empty($ghtkUrl),
                    'ghtk_token_empty' => empty($ghtkToken),
                ]);

                return 0;
            }

            $apiUrl = rtrim($ghtkUrl, '/').'/services/shipment/fee';
            Log::info('[CartService] calculateShippingFee calling GHTK API', [
                'url' => $apiUrl,
                'method' => 'GET',
                'query_params' => $info,
            ]);

            try {
                $client = new Client;
                $response = $client->request('GET', $apiUrl, [
                    'headers' => [
                        'Token' => $ghtkToken,
                    ],
                    'query' => $info,
                    'timeout' => 10, // 10 seconds timeout
                ]);

                $statusCode = $response->getStatusCode();
                $responseBody = $response->getBody()->getContents();

                Log::info('[CartService] calculateShippingFee GHTK API response', [
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                ]);

                $result = json_decode($responseBody);

                Log::info('[CartService] calculateShippingFee GHTK response parsed', [
                    'result' => $result,
                    'has_success' => isset($result->success),
                    'success_value' => $result->success ?? null,
                    'has_fee' => isset($result->fee),
                    'fee_value' => $result->fee->fee ?? null,
                ]);

                if ($result && isset($result->success) && $result->success && isset($result->fee->fee)) {
                    $fee = (float) $result->fee->fee;
                    Log::info('[CartService] calculateShippingFee GHTK success', [
                        'fee' => $fee,
                    ]);

                    return $fee;
                }

                Log::warning('[CartService] calculateShippingFee: GHTK invalid response', [
                    'response' => $result,
                    'response_body' => $responseBody,
                ]);

                return 0;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                Log::error('[CartService] calculateShippingFee GHTK API request exception', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
                ]);

                return 0;
            }
        } catch (\Exception $e) {
            Log::error('[CartService] calculateShippingFee exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'address' => $address,
            ]);

            return 0;
        }
    }

    /**
     * Get config value from database.
     *
     * @return mixed
     */
    private function getConfig(string $name)
    {
        // Use helper function if available
        if (function_exists('getConfig')) {
            return getConfig($name);
        }

        // Fallback: query database directly
        $result = \App\Modules\Config\Models\Config::where('name', $name)->first();

        return (isset($result) && ! empty($result)) ? $result->value : '';
    }

    /**
     * Checkout.
     */
    public function checkout(array $data, ?int $userId = null): array
    {
        try {
            Log::info('[CartService] checkout called', [
                'user_id' => $userId,
                'data_keys' => array_keys($data),
                'has_cart' => Session::has('cart'),
            ]);

            if (! Session::has('cart')) {
                Log::warning('[CartService] checkout empty cart');
                throw new \Exception('Giỏ hàng trống');
            }

            $oldCart = Session::get('cart');
            $cart = new Cart($oldCart);

            if (count($cart->items) === 0) {
                Log::warning('[CartService] checkout cart has no items');
                throw new \Exception('Giỏ hàng trống');
            }

            // Get accurate cart summary from getCart() (Single Source of Truth)
            $cartData = $this->getCart($userId);
            $subtotal = $cartData['summary']['subtotal'] ?? $cart->totalPrice;
            $discount = $cartData['summary']['discount'] ?? 0;
            $shippingFee = (float) ($data['shipping_fee'] ?? 0);
            $total = $subtotal - $discount + $shippingFee;

            Log::info('[CartService] checkout cart summary', [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'cart_totalPrice' => $cart->totalPrice,
                'items_count' => count($cart->items),
            ]);

            // Re-validate coupon
            $sale = 0;
            $promotionId = 0;
            if (Session::has('ss_counpon')) {
                $couponData = Session::get('ss_counpon');
                $promotion = Promotion::where([
                    ['status', '1'],
                    ['start', '<=', date('Y-m-d')],
                    ['end', '>=', date('Y-m-d')],
                    ['order_sale', '<=', $subtotal],
                    ['id', $couponData['id']],
                ])->first();

                if ($promotion) {
                    $count = Order::where('promotion_id', $promotion->id)->count();
                    if ($count < $promotion->number) {
                        $sale = ($promotion->unit == 0)
                                ? round(($subtotal / 100) * $promotion->value)
                            : $promotion->value;
                        $promotionId = $promotion->id;

                        Log::info('[CartService] checkout coupon applied', [
                            'promotion_id' => $promotionId,
                            'sale' => $sale,
                        ]);
                    }
                }
            }

            // Create order
            $code = time();

            Log::info('[CartService] checkout creating order', [
                'code' => $code,
                'total' => $total,
                'subtotal' => $subtotal,
                'sale' => $sale,
                'shipping_fee' => $shippingFee,
            ]);

            $orderId = Order::insertGetId([
                'code' => $code,
                'name' => $data['full_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'],
                'provinceid' => $data['province_id'],
                'districtid' => $data['district_id'],
                'wardid' => $data['ward_id'],
                'remark' => $data['remark'] ?? null,
                'member_id' => $userId ?? 0,
                'ship' => '0',
                'sale' => $sale,
                'total' => $total,
                'promotion_id' => $promotionId,
                'fee_ship' => $shippingFee,
                'status' => '0',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($orderId <= 0) {
                Log::error('[CartService] checkout order creation failed', [
                    'code' => $code,
                ]);
                throw new \Exception('Lỗi tạo đơn hàng');
            }

            Log::info('[CartService] checkout order created', [
                'order_id' => $orderId,
                'code' => $code,
            ]);

            // Create order details
            $date = strtotime(date('Y-m-d H:i:s'));
            $flash = FlashSale::where([['status', '1'], ['start', '<=', $date], ['end', '>=', $date]])->first();

            Log::info('[CartService] checkout processing order details', [
                'order_id' => $orderId,
                'items_count' => count($cart->items),
                'has_flash_sale' => $flash !== null,
                'flash_sale_id' => $flash->id ?? null,
            ]);

            foreach ($cart->items as $variantId => $item) {
                $variant = Variant::with('product')->find($variantId);
                if (! $variant || ! $variant->product) {
                    Log::warning('[CartService] checkout variant not found', [
                        'variant_id' => $variantId,
                    ]);
                    continue;
                }

                $product = $variant->product;
                $productName = $product->name;

                // Initialize IDs for tracking stock source
                $productsaleId = null;
                $dealsaleId = null;
                $dealId = null;

                // Check if item is from Deal FIRST (Deal has priority over Flash Sale)
                // If item is from Deal (is_deal = 1), it should NOT be counted as Flash Sale
                $isDealItem = isset($item['is_deal']) && $item['is_deal'] == 1;

                if ($isDealItem) {
                    $productName = '[DEAL SỐC] '.$productName;

                    // Find SaleDeal for this product/variant
                    $now = time();
                    $saleDealQuery = SaleDeal::where('product_id', $product->id)
                        ->whereHas('deal', function ($q) use ($now) {
                            $q->where('status', '1')
                                ->where('start', '<=', $now)
                                ->where('end', '>=', $now);
                        });

                    if ($variant->id) {
                        $saleDealQuery->where(function ($q) use ($variant) {
                            $q->where('variant_id', $variant->id)
                                ->orWhereNull('variant_id');
                        });
                    } else {
                        $saleDealQuery->whereNull('variant_id');
                    }

                    $saleDeal = $saleDealQuery->first();
                    if ($saleDeal) {
                        $dealsaleId = $saleDeal->id;
                        $dealId = $saleDeal->deal_id;

                        Log::info('[CartService] Item identified as Deal', [
                            'order_id' => $orderId,
                            'variant_id' => $variant->id,
                            'dealsale_id' => $dealsaleId,
                            'deal_id' => $dealId,
                        ]);
                    }
                }

                // Check if item is from Flash Sale (ONLY if NOT a Deal item)
                // Deal items should NOT be counted as Flash Sale even if they participate in Flash Sale
                if ($flash && ! $isDealItem) {
                    try {
                        // Find ProductSale - check variant-specific first, then product-level
                        $productSale = ProductSale::where([
                            ['flashsale_id', $flash->id],
                            ['product_id', $product->id],
                            ['variant_id', $variant->id],
                        ])->first();

                        // If no variant-specific, try product-level (variant_id is null)
                        if (! $productSale) {
                            $productSale = ProductSale::where([
                                ['flashsale_id', $flash->id],
                                ['product_id', $product->id],
                                ['variant_id', null],
                            ])->first();
                        }

                        if ($productSale) {
                            $productsaleId = $productSale->id;
                            // NOTE: Do NOT increment ProductSale.buy here
                            // Stock deduction and buy count increment will be handled by OrderStockReceiptService
                            // when creating export receipt. This ensures stock is only deducted once.

                            Log::info('[CartService] Flash Sale ProductSale found (buy will be incremented when export receipt is created)', [
                                'flash_sale_id' => $flash->id,
                                'product_sale_id' => $productSale->id,
                                'product_id' => $product->id,
                                'variant_id' => $variant->id,
                                'qty' => $item['qty'],
                            ]);
                        } else {
                            Log::warning('[CartService] ProductSale not found for Flash Sale', [
                                'flash_sale_id' => $flash->id,
                                'product_id' => $product->id,
                                'variant_id' => $variant->id,
                            ]);
                        }
                    } catch (\Exception $e) {
                        // Log error but don't fail the order creation
                        Log::error('[CartService] Failed to update Flash Sale stock during checkout', [
                            'flash_sale_id' => $flash->id,
                            'product_id' => $product->id,
                            'variant_id' => $variant->id,
                            'qty' => $item['qty'],
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }

                // Create order detail with stock source tracking
                OrderDetail::insert([
                    'order_id' => $orderId,
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'name' => $productName,
                    'color_id' => $variant->color_id,
                    'size_id' => $variant->size_id,
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'image' => $product->image ?? '',
                    'weight' => ($variant->weight ?? 0) * $item['qty'],
                    'subtotal' => $item['price'] * $item['qty'],
                    'productsale_id' => $productsaleId,
                    'dealsale_id' => $dealsaleId,
                    'deal_id' => $dealId,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                Log::info('[CartService] OrderDetail created with stock source tracking', [
                    'order_id' => $orderId,
                    'variant_id' => $variant->id,
                    'productsale_id' => $productsaleId,
                    'dealsale_id' => $dealsaleId,
                    'deal_id' => $dealId,
                ]);
            }

            Log::info('[CartService] checkout order details created', [
                'order_id' => $orderId,
                'items_processed' => count($cart->items),
            ]);

            // Clear cart and coupon
            Session::forget('cart');
            Session::forget('ss_counpon');
            // Force save session to ensure persistence
            session()->save();
            Session::save(); // Force save session

            Log::info('[CartService] checkout cart cleared', [
                'order_id' => $orderId,
            ]);

            // Auto create export receipt for order (status = '0' means chờ xác nhận -> receipt status = completed)
            try {
                Log::info('[CartService] Attempting to create export receipt', [
                    'order_id' => $orderId,
                ]);

                $order = Order::with(['ward', 'district', 'province'])->find($orderId);

                if (! $order) {
                    Log::warning('[CartService] Order not found for export receipt creation', [
                        'order_id' => $orderId,
                    ]);
                } else {
                    Log::info('[CartService] Order found, checking status', [
                        'order_id' => $orderId,
                        'order_code' => $order->code,
                        'order_status' => $order->status,
                        'order_status_type' => gettype($order->status),
                        'status_is_zero_string' => ($order->status === '0'),
                        'status_is_zero_int' => ($order->status === 0),
                        'status_equals_zero' => ($order->status == 0),
                    ]);

                    // Check if status is '0' (string) or 0 (int)
                    if ($order->status === '0' || $order->status === 0 || $order->status == 0) {
                        Log::info('[CartService] Order status is 0, creating export receipt', [
                            'order_id' => $orderId,
                            'order_code' => $order->code,
                        ]);

                        $orderStockReceiptService = app(\App\Services\Warehouse\OrderStockReceiptService::class);
                        $receipt = $orderStockReceiptService->createExportReceiptFromOrder($order, \App\Models\StockReceipt::STATUS_COMPLETED);

                        if ($receipt) {
                            Log::info('[CartService] checkout export receipt created successfully', [
                                'order_id' => $orderId,
                                'order_code' => $order->code,
                                'receipt_id' => $receipt->id,
                                'receipt_code' => $receipt->receipt_code,
                            ]);
                        } else {
                            Log::warning('[CartService] createExportReceiptFromOrder returned null', [
                                'order_id' => $orderId,
                                'order_code' => $order->code,
                            ]);
                        }
                    } else {
                        Log::info('[CartService] Order status is not 0, skipping export receipt creation', [
                            'order_id' => $orderId,
                            'order_status' => $order->status,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Log error but don't fail order creation
                Log::error('[CartService] Failed to auto-create export receipt for order', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            Log::info('[CartService] checkout completed successfully', [
                'order_id' => $orderId,
                'order_code' => $code,
            ]);

            return [
                'order_code' => (string) $code,
                'order_id' => $orderId,
                'redirect_url' => '/cart/dat-hang-thanh-cong?code='.$code,
            ];
        } catch (\Exception $e) {
            Log::error('[CartService] checkout exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId,
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Get available deals for products in cart.
     */
    private function getAvailableDeals(Cart $cart): array
    {
        $mainProductIds = [];
        foreach ($cart->items as $item) {
            if (! isset($item['is_deal']) || $item['is_deal'] == 0) {
                $productId = is_object($item['item'])
                    ? $item['item']->product_id
                    : ($item['item']['product_id'] ?? null);
                if ($productId) {
                    $mainProductIds[] = $productId;
                }
            }
        }

        if (empty($mainProductIds)) {
            return [];
        }

        $now = strtotime(date('Y-m-d H:i:s'));
        $deals = Deal::whereHas('products', function ($q) use ($mainProductIds) {
            $q->whereIn('product_id', $mainProductIds)->where('status', '1');
        })->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]])
            ->with('sales.product')
            ->get();

        $result = [];
        foreach ($deals as $deal) {
            $saleDeals = [];
            foreach ($deal->sales as $saleDeal) {
                if ($saleDeal->status != '1') {
                    continue;
                }

                $dealProduct = $saleDeal->product;
                if (! $dealProduct) {
                    continue;
                }

                $dealVariant = Variant::where('product_id', $dealProduct->id)
                    ->orderBy('position', 'asc')
                    ->orderBy('id', 'asc')
                    ->first();

                if (! $dealVariant) {
                    continue;
                }

                $saleDeals[] = [
                    'id' => $saleDeal->id,
                    'product_id' => $saleDeal->product_id,
                    'product_name' => $dealProduct->name,
                    'product_image' => $this->formatImageUrl($dealProduct->image),
                    'variant_id' => $dealVariant->id,
                    'price' => (float) $saleDeal->price,
                    'original_price' => (float) $dealVariant->price,
                ];
            }

            if (! empty($saleDeals)) {
                $result[] = [
                    'id' => $deal->id,
                    'name' => $deal->name,
                    'limited' => (int) $deal->limited,
                    'sale_deals' => $saleDeals,
                ];
            }
        }

        return $result;
    }

    /**
     * Remove related deal items when main product is removed.
     *
     * @return array Array of removed variant IDs
     */
    private function removeRelatedDealItems(Cart &$cart, int $mainProductId): array
    {
        $now = strtotime(date('Y-m-d H:i:s'));
        $removedVariantIds = [];

        // Find deal IDs that this main product belongs to
        $dealIds = ProductDeal::where('product_id', $mainProductId)
            ->whereHas('deal', function ($q) use ($now) {
                $q->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
            })
            ->pluck('deal_id')
            ->toArray();

        if (empty($dealIds)) {
            return $removedVariantIds;
        }

        // Find all sale deal product IDs in these deals
        $saleDealProductIds = SaleDeal::whereIn('deal_id', $dealIds)
            ->where('status', '1')
            ->pluck('product_id')
            ->toArray();

        if (empty($saleDealProductIds)) {
            return $removedVariantIds;
        }

        // Collect keys to remove first (avoid modifying array while iterating)
        $keysToRemove = [];
        foreach ($cart->items as $key => $item) {
            if (isset($item['is_deal']) && $item['is_deal'] == 1) {
                $productId = is_object($item['item'])
                    ? $item['item']->product_id
                    : ($item['item']['product_id'] ?? null);

                if ($productId && in_array($productId, $saleDealProductIds)) {
                    $keysToRemove[] = $key;
                }
            }
        }

        // Remove items (in reverse order to avoid key shifting issues)
        rsort($keysToRemove);
        foreach ($keysToRemove as $key) {
            if (isset($cart->items[$key])) {
                try {
                    $cart->removeItem($key);
                    $removedVariantIds[] = $key;
                } catch (\Exception $e) {
                    // Item may have been removed already, continue
                    Log::warning('Failed to remove deal item from cart', [
                        'key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $removedVariantIds;
    }

    /**
     * Remove related main product when deal item is removed.
     *
     * @return array Array of removed variant IDs
     */
    private function removeRelatedMainProduct(Cart &$cart, int $dealProductId): array
    {
        $now = strtotime(date('Y-m-d H:i:s'));
        $removedVariantIds = [];

        // Find deal ID that this deal product belongs to
        $saleDeal = SaleDeal::where('product_id', $dealProductId)
            ->whereHas('deal', function ($q) use ($now) {
                $q->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
            })
            ->where('status', '1')
            ->first();

        if (! $saleDeal) {
            return $removedVariantIds;
        }

        $dealId = $saleDeal->deal_id;

        // Find main product IDs in this deal
        // Note: ProductDeal may not have status field, so we check via deal
        $mainProductIds = ProductDeal::where('deal_id', $dealId)
            ->whereHas('deal', function ($q) use ($now) {
                $q->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
            })
            ->pluck('product_id')
            ->toArray();

        if (empty($mainProductIds)) {
            return $removedVariantIds;
        }

        // Collect keys to remove first (avoid modifying array while iterating)
        $keysToRemove = [];
        foreach ($cart->items as $key => $item) {
            if (! isset($item['is_deal']) || $item['is_deal'] == 0) {
                $productId = is_object($item['item'])
                    ? $item['item']->product_id
                    : ($item['item']['product_id'] ?? null);

                if ($productId && in_array($productId, $mainProductIds)) {
                    $keysToRemove[] = $key;
                }
            }
        }

        // Remove items (in reverse order to avoid key shifting issues)
        rsort($keysToRemove);
        foreach ($keysToRemove as $key) {
            if (isset($cart->items[$key])) {
                try {
                    $cart->removeItem($key);
                    $removedVariantIds[] = $key;
                } catch (\Exception $e) {
                    // Item may have been removed already, continue
                    Log::warning('Failed to remove main product from cart', [
                        'key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $removedVariantIds;
    }

    /**
     * Validate and remove invalid deals.
     */
    private function validateDeals(Cart &$cart): void
    {
        if (empty($cart->items)) {
            return;
        }

        $now = strtotime(date('Y-m-d H:i:s'));

        // Get current main product IDs
        $currentMainProductIds = [];
        foreach ($cart->items as $item) {
            if (! isset($item['is_deal']) || $item['is_deal'] == 0) {
                $productId = is_object($item['item'])
                    ? $item['item']->product_id
                    : ($item['item']['product_id'] ?? null);
                if ($productId) {
                    $currentMainProductIds[] = $productId;
                }
            }
        }

        // Get active deal IDs
        $activeDealIds = [];
        if (! empty($currentMainProductIds)) {
            $activeDealIds = ProductDeal::whereIn('product_id', $currentMainProductIds)
                ->whereHas('deal', function ($q) use ($now) {
                    $q->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
                })
                ->pluck('deal_id')
                ->unique()
                ->toArray();
        }

        // Remove invalid deal items
        // IMPORTANT: Collect keys first to avoid modification during iteration
        $keysToRemove = [];
        foreach ($cart->items as $key => $item) {
            if (isset($item['is_deal']) && $item['is_deal'] == 1) {
                $productId = is_object($item['item'])
                    ? $item['item']->product_id
                    : ($item['item']['product_id'] ?? null);

                if (! $productId) {
                    $keysToRemove[] = $key;
                    continue;
                }

                $saledeal = SaleDeal::where('product_id', $productId)
                    ->whereHas('deal', function ($q) use ($now) {
                        $q->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
                    })
                    ->where('status', '1')
                    ->first();

                if (! $saledeal || ! in_array($saledeal->deal_id, $activeDealIds)) {
                    $keysToRemove[] = $key;
                }
            }
        }

        // Remove collected keys (reverse sort to avoid index issues)
        if (! empty($keysToRemove)) {
            rsort($keysToRemove);
            foreach ($keysToRemove as $key) {
                Log::info('[CartService] validateDeals removing invalid deal', [
                    'variant_id' => $key,
                ]);
                $cart->removeItem($key);
            }
        }
    }

    /**
     * Format image URL.
     */
    private function formatImageUrl(?string $image): string
    {
        if (empty($image)) {
            $r2Domain = config('filesystems.disks.r2.url', '');
            if (! empty($r2Domain)) {
                return rtrim($r2Domain, '/').'/public/image/no_image.png';
            }

            return asset('/public/image/no_image.png');
        }

        $r2Domain = config('filesystems.disks.r2.url', '');
        $r2DomainClean = ! empty($r2Domain) ? rtrim($r2Domain, '/') : '';

        if (empty($r2DomainClean)) {
            return filter_var($image, FILTER_VALIDATE_URL) ? $image : asset($image);
        }

        $image = trim($image);
        $checkR2 = str_replace(['http://', 'https://'], '', $r2DomainClean);
        $cleanPath = str_replace(['http://', 'https://'], '', $image);
        $cleanPath = str_replace($checkR2.'/', '', $cleanPath);
        $cleanPath = str_replace($checkR2, '', $cleanPath);
        $cleanPath = preg_replace('#/+#', '/', $cleanPath);
        $cleanPath = preg_replace('#(uploads/)+#', 'uploads/', $cleanPath);
        $cleanPath = ltrim($cleanPath, '/');

        return $r2DomainClean.'/'.$cleanPath;
    }
}
