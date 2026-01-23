<?php

namespace App\Services\Cart;

use App\Services\PriceCalculationService;
use App\Services\Pricing\PriceEngineServiceInterface;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Support\Facades\DB;
use App\Modules\Product\Models\Variant;
use App\Modules\Product\Models\Product;
use App\Modules\Promotion\Models\Promotion;
use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\ProductDeal;
use App\Modules\Deal\Models\SaleDeal;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderDetail;
use App\Modules\Address\Models\Address;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Services\FlashSale\FlashSaleStockService;
use App\Modules\Pick\Models\Pick;
use App\Modules\Location\Models\Province;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Ward;
use App\Themes\Website\Models\Cart;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use App\Modules\Config\Models\Config;

/**
 * Cart Service
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
    private const DEAL_EXHAUSTED_MESSAGE = 'Quà tặng Deal Sốc đã hết, giá được chuyển về giá thường/khuyến mại.';
    
    /**
     * Get cart items directly from session (no caching, always fresh)
     * Bước 1: Loại bỏ thuộc tính $items khỏi bộ nhớ Service
     * 
     * @return array
     */
    private function getCartItemsFromSession(): array
    {
        $oldCart = session()->has('cart') ? session()->get('cart') : null;
        $cart = new Cart($oldCart);
        $items = $cart->items ?? [];
        
        if (!is_array($items)) {
            $items = [];
        }
        
        return $items;
    }

    public function __construct(
        PriceCalculationService $priceService,
        FlashSaleStockService $flashSaleStockService,
        PriceEngineServiceInterface $priceEngine,
        WarehouseServiceInterface $warehouseService
    ) {
        $this->priceService = $priceService;
        $this->flashSaleStockService = $flashSaleStockService;
        $this->priceEngine = $priceEngine;
        $this->warehouseService = $warehouseService;
        
        // Inject WarehouseService vào PriceEngineService
        if (method_exists($this->priceEngine, 'setWarehouseService')) {
            $this->priceEngine->setWarehouseService($warehouseService);
        }
    }

    /**
     * Get cart data
     * 
     * @param int|null $userId Optional user ID
     * @return array
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
            $variant = Variant::with(['product', 'color', 'size'])->find($variantId);
            if (!$variant || !$variant->product) {
                continue;
            }
            
            $product = $variant->product;
            $quantity = (int)($item['qty'] ?? 1);
            
            // QUAN TRỌNG: Tính lại giá với số lượng thực tế từ PriceEngineService
            // Không tin tưởng vào giá lưu sẵn trong Session
            $priceWithQuantity = $this->priceEngine->calculatePriceWithQuantity(
                $product->id,
                $variantId,
                $quantity
            );
            
            // Lấy giá cũ từ session để so sánh (logging)
            $oldPrice = (float)($item['price'] ?? 0);
            $oldSubtotal = $oldPrice * $quantity;
            
            // Sử dụng giá mới từ PriceEngineService
            $newPrice = $quantity > 0 ? ($priceWithQuantity['total_price'] / $quantity) : 0; // Giá trung bình
            $newSubtotal = (float)$priceWithQuantity['total_price'];

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
            if (!empty($item['is_deal'])) {
                $dealCheck = $this->validateDealAvailability($product->id, $variantId, $quantity);
                if (!$dealCheck['available']) {
                    $dealUnavailable = true;
                    $dealWarning = $dealCheck['message'];
                    // Giữ newPrice/newSubtotal từ PriceEngine (đã là giá thường/promo)
                } else {
                    // ===== CRITICAL: Logic 2 - Sản phẩm mua kèm (Deal Sốc) LUÔN lấy giá từ Deal Sốc =====
                    // Nếu là sản phẩm mua kèm (is_deal = 1), LUÔN áp dụng giá Deal Sốc, bất kể có Flash Sale/Promotion
                    $isDealItem = !empty($item['is_deal']) && (int)$item['is_deal'] === 1;
                    
                    if ($isDealItem) {
                        // Sản phẩm mua kèm: LUÔN lấy giá từ Deal Sốc (kể cả 0đ)
                        try {
                            $dealPrice = $this->getDealPrice($product->id, $variantId);
                            $newPrice = $dealPrice;
                            $newSubtotal = $dealPrice * $quantity;
                            
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
                            // Ghi đè breakdown để FE hiển thị đúng
                            if (isset($dealPricing['price_breakdown'])) {
                                $priceWithQuantity['price_breakdown'] = $dealPricing['price_breakdown'];
                            }
                        }
                        
                        // Fallback về giá gốc nếu subtotal <= 0 và không phải Deal
                        if ($newSubtotal <= 0.0) {
                            if (!empty($variant->price) && (float)$variant->price > 0) {
                                $basePrice = (float)$variant->price;
                                $newPrice = $basePrice;
                                $newSubtotal = $basePrice * $quantity;
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
                ? (int)$variant->stock 
                : 0;
            
            // Lấy price info cơ bản để hiển thị
            $priceInfo = $this->priceService->calculateVariantPrice($variant);
            
            $items[] = [
                'variant_id' => (int)$variantId,
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
                'original_price' => (float)$priceInfo->original_price,
                'subtotal' => max(0, $newSubtotal), // Tổng giá đã tính lại, không âm
                'is_deal' => isset($item['is_deal']) ? (int)$item['is_deal'] : 0,
                'deal_unavailable' => $dealUnavailable,
                'deal_warning' => $dealWarning,
                'price_info' => [
                    'price' => (float)$priceInfo->price,
                    'original_price' => (float)$priceInfo->original_price,
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
            
            $totalQty += $quantity;
            $subtotal += $newSubtotal; // Sử dụng subtotal mới
            
            // ===== THÊM LOG RUNNING TOTAL =====
            Log::info('[DEBUG_CHECKOUT] Running subtotal', [
                'after_variant_id' => $variantId,
                'item_subtotal' => $newSubtotal,
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
                'discount' => (float)($couponData['sale'] ?? 0),
            ];
            $discount = (float)($couponData['sale'] ?? 0);
        }
        
        // Get available deals
        $availableDeals = $this->getAvailableDeals($cart);
        
        // ===== BƯỚC 1: Tính lại tổng tiền dựa trên items (kể cả Deal Sốc) =====
        $total = 0.0;
        foreach ($items as $it) {
            $unitPrice = (float)($it['price'] ?? 0);
            $qty = (int)($it['qty'] ?? 0);
            // Kể cả is_deal = 1 (quà tặng, mua kèm) vẫn phải nhân giá * số lượng
            // Nếu Deal 0đ thì unitPrice = 0, không làm âm tổng
            $total += ($unitPrice * $qty);
        }
        $summaryTotal = (float)max(0, $total - $discount);

        // BƯỚC 5: Log cảnh báo nếu có item subtotal = 0 (đặc biệt là Deal Sốc)
        $zeroItems = array_filter($items, static function ($it) {
            return (float)($it['subtotal'] ?? 0) <= 0;
        });
        if (!empty($zeroItems)) {
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
            'subtotal' => $subtotal,
            'recalculated_items_total' => $total,
            'discount' => $discount,
            'final_total' => $summaryTotal,
        ]);
        // ===== END LOG =====
        
        return [
            'items' => $items,
            'summary' => [
                'total_qty' => $totalQty,
                'subtotal' => (float)$subtotal,
                'discount' => $discount,
                'shipping_fee' => 0,
                'total' => $summaryTotal,
            ],
            'coupon' => $coupon,
            'available_deals' => $availableDeals,
        ];
    }

    /**
     * Bước 2: Đồng bộ hóa mảng Items trong CartService
     * Quét lại toàn bộ sản phẩm Deal Sốc trong giỏ hàng và cập nhật trạng thái is_available
     * Nếu sản phẩm đó đã được bổ sung số lượng trong Admin, cập nhật is_available = true trong Session
     * 
     * @return void
     */
    public function syncDealItemsAvailability(): void
    {
        $oldCart = session()->has('cart') ? session()->get('cart') : null;
        if (!$oldCart) {
            return;
        }
        
        $cart = new Cart($oldCart);
        $hasChanges = false;
        
        foreach ($cart->items as $variantId => $item) {
            // Chỉ xử lý các item là Deal Sốc
            if (empty($item['is_deal']) || (int)$item['is_deal'] !== 1) {
                continue;
            }
            
            $productId = null;
            if (is_object($item['item'] ?? null)) {
                $productId = $item['item']->product_id ?? null;
            } elseif (is_array($item['item'] ?? null)) {
                $productId = $item['item']['product_id'] ?? null;
            }
            
            if (!$productId) {
                continue;
            }
            
            $quantity = (int)($item['qty'] ?? 1);
            
            // Validate Deal availability với dữ liệu mới nhất từ Database
            $dealCheck = $this->validateDealAvailability($productId, $variantId, $quantity);
            
            // Nếu Deal đã available (Admin đã tăng số lượng), cập nhật lại Session
            if ($dealCheck['available']) {
                // Đảm bảo item có cờ is_deal = 1 và dealsale_id
                if (!isset($item['is_deal']) || (int)$item['is_deal'] !== 1) {
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
        if (!$saleDeal) {
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
     *
     * @param int $productId
     * @param int $variantId
     * @return float
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

        if (!$activeDeal) {
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

        return $saleDeal ? (float)$saleDeal->price : 0.0;
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
     * @param int   $productId
     * @param int   $variantId
     * @param int   $quantity
     * @param array $priceWithQuantity
     * @param bool  $isDealItem Whether this is a deal item (mua kèm) or normal item
     * @return array|null
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
        if (!$saleDeal) {
            return null;
        }

        $dealPrice = (float)($saleDeal->price ?? 0);

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
        if (empty($priceWithQuantity['price_breakdown']) || !is_array($priceWithQuantity['price_breakdown'])) {
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
        $originalPrice = (float)($firstLine['unit_price'] ?? 0);

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
     * Add item to cart
     * 
     * @param int $variantId
     * @param int $qty
     * @param bool $isDeal
     * @param int|null $userId
     * @return array
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
        if (!$variant || !$variant->product) {
            throw new \Exception('Sản phẩm không tồn tại');
        }
        
        // Validate stock from Warehouse (single source of truth)
        $product = $variant->product;
        $variantStock = 0;
        $dealStock = 0;
        $physicalStock = 0;
        
        try {
            $stockInfo = $this->warehouseService->getVariantStock($variantId);
            $physicalStock = (int)($stockInfo['physical_stock'] ?? 0);
            $dealStock = (int)($stockInfo['deal_stock'] ?? 0);
            
            // For Deal items: use deal_stock (from deal_hold in inventory_stocks)
            // For normal items: use available_stock (physical - reserved - flash_sale - deal)
            if ($isDeal) {
                $variantStock = $dealStock; // Use deal_stock for Deal items
            } else {
                $variantStock = (int)($stockInfo['available_stock'] ?? 0);
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
            if (!is_array($currentCartItems)) {
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
                ->whereHas('deal', function($query) use ($now) {
                    $query->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
                })->where('status', '1')->first();
            
            if ($saledeal && $saledeal->deal) {
                // Giới hạn số lượng mua kèm trong 1 đơn (lấy từ deals.limited, không phải deal_sales.qty)
                // deal_sales.qty là remaining quota, deals.limited là per-order limit
                $dealLimit = (int)($saledeal->deal->limited ?? 0);
                if ($dealLimit > 0) {
                    // Bước 3: Sửa logic Validation trong addItem - dùng biến local từ Session
                    // Đếm số lượng deal items từ currentCartItems (đã đọc từ session ở trên)
                    $currentDealQty = 0;
                    $dealItemsInCart = [];
                    foreach ($currentCartItems as $cartVariantId => $cartItem) {
                        // Check if item is a deal item with the same deal_id
                        if (!empty($cartItem['is_deal']) && isset($cartItem['deal_id'])) {
                            if ((int)$cartItem['deal_id'] === (int)$saledeal->deal_id) {
                                $itemQty = (int)($cartItem['qty'] ?? 0);
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
                    Log::info('[CART_VALIDATION] Kiểm tra Deal ID: ' . $saledeal->deal_id . ' | Số lượng trong giỏ FRESH: ' . $currentDealQty, [
                        'variant_id' => $variantId,
                        'deal_id' => $saledeal->deal_id,
                        'deal_limit' => $dealLimit,
                        'current_deal_qty' => $currentDealQty,
                        'current_cart_items_count' => count($currentCartItems),
                        'deal_items_in_cart' => $dealItemsInCart,
                        'all_current_cart_items_keys' => array_keys($currentCartItems),
                    ]);
                    
                    // Check if variant already in cart (from currentCartItems read from session)
                    $alreadyInCart = isset($currentCartItems[$variantId]) && !empty($currentCartItems[$variantId]['is_deal']);
                    $remaining = $dealLimit - $currentDealQty;

                    // Nếu variant đã có trong giỏ: không throw cứng, chỉ clamp hoặc no-op
                    if ($alreadyInCart && ($currentDealQty + $qty) > $dealLimit) {
                        if ($remaining <= 0) {
                            // No-op: đã đạt giới hạn, không tăng thêm
                            Log::info('[CartService] Deal limit reached (noop for existing variant)', [
                                'variant_id' => $variantId,
                                'deal_id' => $saledeal->deal_id,
                                'limit' => $dealLimit,
                                'current' => $currentDealQty,
                            ]);
                            $qty = 0;
                        } else {
                            // Clamp qty add
                            Log::info('[CartService] Deal qty clamped for existing variant', [
                                'variant_id' => $variantId,
                                'deal_id' => $saledeal->deal_id,
                                'limit' => $dealLimit,
                                'current' => $currentDealQty,
                                'requested_add' => $qty,
                                'allowed_add' => $remaining,
                            ]);
                            $qty = $remaining;
                        }
                    }

                    // Nếu là thêm mới vượt limit: chặn
                    if (!$alreadyInCart && ($currentDealQty + $qty) > $dealLimit) {
                        throw new \Exception('Bạn đã đạt giới hạn số lượng quà tặng cho ưu đãi này');
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
                'price' => (float)$cart->items[$variantId]['price'],
            ],
        ];
    }

    /**
     * Update item quantity
     * 
     * @param int $variantId
     * @param int $qty
     * @param int|null $userId
     * @return array
     */
    public function updateItem(int $variantId, int $qty, ?int $userId = null): array
    {
        if ($qty <= 0) {
            return $this->removeItem($variantId, $userId);
        }
        
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);
        
        if (!isset($cart->items[$variantId])) {
            throw new \Exception('Sản phẩm không tồn tại trong giỏ hàng');
        }
        
        // QUAN TRỌNG: Kiểm tra tồn kho thực tế từ Warehouse API
        $variant = Variant::with('product')->find($variantId);
        if ($variant) {
            try {
                $stockInfo = $this->warehouseService->getVariantStock($variantId);
                $physicalStock = (int)($stockInfo['current_stock'] ?? 0);
                
                if ($qty > $physicalStock) {
                    throw new \Exception("Rất tiếc, sản phẩm này chỉ còn tối đa {$physicalStock} sản phẩm trong kho. Vui lòng điều chỉnh lại số lượng.");
                }
            } catch (\Exception $e) {
                // Nếu lỗi từ WarehouseService, fallback về kiểm tra stock cũ (không dùng sentinel 999)
                $product = $variant->product;
                $variantStock = isset($variant->stock) && $variant->stock !== null 
                    ? (int)$variant->stock 
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
            'subtotal' => (float)($cart->items[$variantId]['price'] * $qty),
            'summary' => [
                'total_qty' => $cart->totalQty,
                'subtotal' => (float)$cart->totalPrice,
                'discount' => (float)$discount,
                'total' => (float)($cart->totalPrice - $discount),
            ],
        ];
    }

    /**
     * Remove item from cart - SIMPLIFIED VERSION
     * Only removes the requested item, no automatic removal of related items
     * 
     * @param int $variantId
     * @param int|null $userId
     * @return array
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
        if (!isset($cart->items[$variantId])) {
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
                    'subtotal' => (float)$cart->totalPrice,
                    'discount' => (float)$discount,
                    'total' => (float)($cart->totalPrice - $discount),
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
                $removedRelated = $this->removeRelatedDealItems($cart, (int)$mainProductId);
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
     * Apply coupon
     * 
     * @param string $code
     * @param int|null $userId
     * @return array
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
        
        if (!$promotion) {
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
                'discount' => (float)$sale,
            ],
            'summary' => [
                'subtotal' => (float)$cart->totalPrice,
                'discount' => (float)$sale,
                'total' => (float)($cart->totalPrice - $sale),
            ],
        ];
    }

    /**
     * Remove coupon
     * 
     * @param int|null $userId
     * @return array
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
                'subtotal' => (float)$cart->totalPrice,
                'discount' => 0,
                'total' => (float)$cart->totalPrice,
            ],
        ];
    }

    /**
     * Calculate shipping fee
     * 
     * @param array $address Address data with province_id, district_id, ward_id, address
     * @param int|null $userId Optional user ID
     * @return float Shipping fee in VND
     */
    public function calculateShippingFee(array $address, ?int $userId = null): float
    {
        // Check free ship
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);
        $subtotal = $cart->totalPrice;
        $sale = Session::has('ss_counpon') ? Session::get('ss_counpon')['sale'] ?? 0 : 0;
        
        // Check free ship config
        $freeShipEnabled = $this->getConfig('free_ship');
        $freeOrderAmount = $this->getConfig('free_order');
        
        if ($freeShipEnabled && $subtotal >= $freeOrderAmount) {
            return 0;
        }
        
        // Check GHTK status
        $ghtkStatus = $this->getConfig('ghtk_status');
        if (!$ghtkStatus) {
            return 0;
        }
        
        // Get pick address (warehouse)
        $pick = Pick::where('status', '1')->orderBy('sort', 'asc')->first();
        if (!$pick) {
            Log::warning('GHTK: No pick address found');
            return 0;
        }
        
        // Calculate total weight from cart items
        $weight = 0;
        foreach ($cart->items as $variant) {
            $item = $variant['item'];
            $itemWeight = 0;
            
            if (is_object($item)) {
                $itemWeight = $item->weight ?? 0;
            } elseif (is_array($item)) {
                $itemWeight = $item['weight'] ?? 0;
            }
            
            $weight += ($itemWeight * ($variant['qty'] ?? 1));
        }
        
        // Get delivery location names
        $province = Province::find($address['province_id'] ?? null);
        $district = District::find($address['district_id'] ?? null);
        $ward = Ward::find($address['ward_id'] ?? null);
        
        if (!$province || !$district || !$ward) {
            Log::warning('GHTK: Invalid delivery address', $address);
            return 0;
        }
        
        // Prepare GHTK API request data
        $info = [
            "pick_province" => $pick->province->name ?? '',
            "pick_district" => $pick->district->name ?? '',
            "pick_ward" => $pick->ward->name ?? '',
            "pick_street" => $pick->street ?? '',
            "pick_address" => $pick->address ?? '',
            "province" => $province->name ?? '',
            "district" => $district->name ?? '',
            "ward" => $ward->name ?? '',
            "address" => $address['address'] ?? '',
            "weight" => $weight,
            "value" => $subtotal - $sale,
            "transport" => 'road',
            "deliver_option" => 'none',
            "tags" => [0],
        ];
        
        // Call GHTK API
        try {
            $ghtkUrl = $this->getConfig('ghtk_url');
            $ghtkToken = $this->getConfig('ghtk_token');
            
            if (empty($ghtkUrl) || empty($ghtkToken)) {
                Log::warning('GHTK: Missing URL or Token configuration');
                return 0;
            }
            
            $client = new Client();
            $response = $client->request('GET', rtrim($ghtkUrl, '/') . "/services/shipment/fee", [
                'headers' => [
                    'Token' => $ghtkToken,
                ],
                'query' => $info,
                'timeout' => 10, // 10 seconds timeout
            ]);
            
            $result = json_decode($response->getBody()->getContents());
            
            if ($result && isset($result->success) && $result->success && isset($result->fee->fee)) {
                return (float)$result->fee->fee;
            }
            
            Log::warning('GHTK: Invalid response', ['response' => $result]);
            return 0;
        } catch (\Exception $e) {
            Log::error('GHTK getFee Error: ' . $e->getMessage(), [
                'address' => $address,
                'info' => $info,
            ]);
            return 0;
        }
    }
    
    /**
     * Get config value from database
     * 
     * @param string $name
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
        return (isset($result) && !empty($result)) ? $result->value : '';
    }

    /**
     * Checkout
     * 
     * @param array $data
     * @param int|null $userId
     * @return array
     */
    public function checkout(array $data, ?int $userId = null): array
    {
        if (!Session::has('cart')) {
            throw new \Exception('Giỏ hàng trống');
        }
        
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        
        if (count($cart->items) === 0) {
            throw new \Exception('Giỏ hàng trống');
        }
        
        // Re-validate coupon
        $sale = 0;
        $promotionId = 0;
        if (Session::has('ss_counpon')) {
            $couponData = Session::get('ss_counpon');
            $promotion = Promotion::where([
                ['status', '1'],
                ['start', '<=', date('Y-m-d')],
                ['end', '>=', date('Y-m-d')],
                ['order_sale', '<=', $cart->totalPrice],
                ['id', $couponData['id']],
            ])->first();
            
            if ($promotion) {
                $count = Order::where('promotion_id', $promotion->id)->count();
                if ($count < $promotion->number) {
                    $sale = ($promotion->unit == 0) 
                        ? round(($cart->totalPrice / 100) * $promotion->value) 
                        : $promotion->value;
                    $promotionId = $promotion->id;
                }
            }
        }
        
        // Create order
        $code = time();
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
            'total' => $cart->totalPrice,
            'promotion_id' => $promotionId,
            'fee_ship' => $data['shipping_fee'] ?? 0,
            'status' => '0',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        if ($orderId <= 0) {
            throw new \Exception('Lỗi tạo đơn hàng');
        }
        
        // Create order details
        $date = strtotime(date('Y-m-d H:i:s'));
        $flash = FlashSale::where([['status', '1'], ['start', '<=', $date], ['end', '>=', $date]])->first();
        
        foreach ($cart->items as $variantId => $item) {
            $variant = Variant::with('product')->find($variantId);
            if (!$variant || !$variant->product) {
                continue;
            }
            
            $product = $variant->product;
            $productName = $product->name;
            if (isset($item['is_deal']) && $item['is_deal'] == 1) {
                $productName = '[DEAL SỐC] ' . $productName;
            }
            
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
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            // Update Flash Sale stock with race condition protection
            if ($flash) {
                try {
                    // Check if this is variant-specific Flash Sale first
                    $variantProductSale = ProductSale::where([
                        ['flashsale_id', $flash->id],
                        ['product_id', $product->id],
                        ['variant_id', $variant->id],
                    ])->first();
                    
                    $variantId = $variantProductSale ? $variant->id : null;
                    
                    // Use FlashSaleStockService to safely increment buy count
                    $this->flashSaleStockService->incrementBuy(
                        $flash->id,
                        $product->id,
                        $variantId,
                        $item['qty']
                    );
                } catch (\Exception $e) {
                    // Log error but don't fail the order creation
                    // The order is already created, but Flash Sale stock wasn't updated
                    Log::error('Failed to update Flash Sale stock during checkout', [
                        'flash_sale_id' => $flash->id,
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'qty' => $item['qty'],
                        'error' => $e->getMessage(),
                    ]);
                    // Note: In production, you might want to rollback the order or handle this differently
                }
            }
        }
        
        // Clear cart and coupon
        Session::forget('cart');
        Session::forget('ss_counpon');
        // Force save session to ensure persistence
        session()->save();
        Session::save(); // Force save session
        
        // Auto create export receipt for order (status = '0' means chờ xác nhận -> receipt status = completed)
        try {
            $order = Order::find($orderId);
            if ($order && $order->status === '0') {
                $orderStockReceiptService = app(\App\Services\Warehouse\OrderStockReceiptService::class);
                $orderStockReceiptService->createExportReceiptFromOrder($order, \App\Models\StockReceipt::STATUS_COMPLETED);
            }
        } catch (\Exception $e) {
            // Log error but don't fail order creation
            Log::error('Failed to auto-create export receipt for order', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
        
        return [
            'order_code' => (string)$code,
            'order_id' => $orderId,
            'redirect_url' => '/cart/dat-hang-thanh-cong?code=' . $code,
        ];
    }

    /**
     * Get available deals for products in cart
     * 
     * @param Cart $cart
     * @return array
     */
    private function getAvailableDeals(Cart $cart): array
    {
        $mainProductIds = [];
        foreach ($cart->items as $item) {
            if (!isset($item['is_deal']) || $item['is_deal'] == 0) {
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
        $deals = Deal::whereHas('products', function($q) use ($mainProductIds) {
            $q->whereIn('product_id', $mainProductIds)->where('status', '1');
        })->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]])
          ->with('sales.product')
          ->get();
        
        $result = [];
        foreach ($deals as $deal) {
            $saleDeals = [];
            foreach ($deal->sales as $saleDeal) {
                if ($saleDeal->status != '1') continue;
                
                $dealProduct = $saleDeal->product;
                if (!$dealProduct) continue;
                
                $dealVariant = Variant::where('product_id', $dealProduct->id)
                    ->orderBy('position', 'asc')
                    ->orderBy('id', 'asc')
                    ->first();
                
                if (!$dealVariant) continue;
                
                $saleDeals[] = [
                    'id' => $saleDeal->id,
                    'product_id' => $saleDeal->product_id,
                    'product_name' => $dealProduct->name,
                    'product_image' => $this->formatImageUrl($dealProduct->image),
                    'variant_id' => $dealVariant->id,
                    'price' => (float)$saleDeal->price,
                    'original_price' => (float)$dealVariant->price,
                ];
            }
            
            if (!empty($saleDeals)) {
                $result[] = [
                    'id' => $deal->id,
                    'name' => $deal->name,
                    'limited' => (int)$deal->limited,
                    'sale_deals' => $saleDeals,
                ];
            }
        }
        
        return $result;
    }

    /**
     * Remove related deal items when main product is removed
     * 
     * @param Cart $cart
     * @param int $mainProductId
     * @return array Array of removed variant IDs
     */
    private function removeRelatedDealItems(Cart &$cart, int $mainProductId): array
    {
        $now = strtotime(date('Y-m-d H:i:s'));
        $removedVariantIds = [];
        
        // Find deal IDs that this main product belongs to
        $dealIds = ProductDeal::where('product_id', $mainProductId)
            ->whereHas('deal', function($q) use ($now) {
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
     * Remove related main product when deal item is removed
     * 
     * @param Cart $cart
     * @param int $dealProductId
     * @return array Array of removed variant IDs
     */
    private function removeRelatedMainProduct(Cart &$cart, int $dealProductId): array
    {
        $now = strtotime(date('Y-m-d H:i:s'));
        $removedVariantIds = [];
        
        // Find deal ID that this deal product belongs to
        $saleDeal = SaleDeal::where('product_id', $dealProductId)
            ->whereHas('deal', function($q) use ($now) {
                $q->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
            })
            ->where('status', '1')
            ->first();
        
        if (!$saleDeal) {
            return $removedVariantIds;
        }
        
        $dealId = $saleDeal->deal_id;
        
        // Find main product IDs in this deal
        // Note: ProductDeal may not have status field, so we check via deal
        $mainProductIds = ProductDeal::where('deal_id', $dealId)
            ->whereHas('deal', function($q) use ($now) {
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
            if (!isset($item['is_deal']) || $item['is_deal'] == 0) {
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
     * Validate and remove invalid deals
     * 
     * @param Cart $cart
     * @return void
     */
    private function validateDeals(Cart &$cart): void
    {
        if (empty($cart->items)) return;
        
        $now = strtotime(date('Y-m-d H:i:s'));
        
        // Get current main product IDs
        $currentMainProductIds = [];
        foreach ($cart->items as $item) {
            if (!isset($item['is_deal']) || $item['is_deal'] == 0) {
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
        if (!empty($currentMainProductIds)) {
            $activeDealIds = ProductDeal::whereIn('product_id', $currentMainProductIds)
                ->whereHas('deal', function($q) use ($now) {
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
                
                if (!$productId) {
                    $keysToRemove[] = $key;
                    continue;
                }
                
                $saledeal = SaleDeal::where('product_id', $productId)
                    ->whereHas('deal', function($q) use ($now) {
                        $q->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
                    })
                    ->where('status', '1')
                    ->first();
                
                if (!$saledeal || !in_array($saledeal->deal_id, $activeDealIds)) {
                    $keysToRemove[] = $key;
                }
            }
        }
        
        // Remove collected keys (reverse sort to avoid index issues)
        if (!empty($keysToRemove)) {
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
     * Format image URL
     * 
     * @param string|null $image
     * @return string
     */
    private function formatImageUrl(?string $image): string
    {
        if (empty($image)) {
            $r2Domain = config('filesystems.disks.r2.url', '');
            if (!empty($r2Domain)) {
                return rtrim($r2Domain, '/') . '/public/image/no_image.png';
            }
            return asset('/public/image/no_image.png');
        }
        
        $r2Domain = config('filesystems.disks.r2.url', '');
        $r2DomainClean = !empty($r2Domain) ? rtrim($r2Domain, '/') : '';
        
        if (empty($r2DomainClean)) {
            return filter_var($image, FILTER_VALIDATE_URL) ? $image : asset($image);
        }
        
        $image = trim($image);
        $checkR2 = str_replace(['http://', 'https://'], '', $r2DomainClean);
        $cleanPath = str_replace(['http://', 'https://'], '', $image);
        $cleanPath = str_replace($checkR2 . '/', '', $cleanPath);
        $cleanPath = str_replace($checkR2, '', $cleanPath);
        $cleanPath = preg_replace('#/+#', '/', $cleanPath);
        $cleanPath = preg_replace('#(uploads/)+#', 'uploads/', $cleanPath);
        $cleanPath = ltrim($cleanPath, '/');
        
        return $r2DomainClean . '/' . $cleanPath;
    }
}
