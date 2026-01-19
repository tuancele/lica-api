<?php

namespace App\Services\Cart;

use App\Services\PriceCalculationService;
use App\Services\Pricing\PriceEngineServiceInterface;
use App\Services\Warehouse\WarehouseServiceInterface;
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
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
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
            $newPrice = $priceWithQuantity['total_price'] / $quantity; // Giá trung bình
            $newSubtotal = (float)$priceWithQuantity['total_price'];
            
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
            
            // Get stock
            $stock = isset($variant->stock) && $variant->stock !== null 
                ? (int)$variant->stock 
                : (isset($product->stock) && $product->stock == 1 ? 999 : 0);
            
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
                'subtotal' => $newSubtotal, // Tổng giá đã tính lại
                'is_deal' => isset($item['is_deal']) ? (int)$item['is_deal'] : 0,
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
                'warning' => $priceWithQuantity['warning'] ?? null,
                'stock' => $stock,
                'available' => $stock > 0,
            ];
            
            $totalQty += $quantity;
            $subtotal += $newSubtotal; // Sử dụng subtotal mới
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
        
        return [
            'items' => $items,
            'summary' => [
                'total_qty' => $totalQty,
                'subtotal' => (float)$subtotal,
                'discount' => $discount,
                'shipping_fee' => 0,
                'total' => (float)($subtotal - $discount),
            ],
            'coupon' => $coupon,
            'available_deals' => $availableDeals,
        ];
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
    public function addItem(int $variantId, int $qty, bool $isDeal = false, ?int $userId = null): array
    {
        $variant = Variant::with('product')->find($variantId);
        if (!$variant || !$variant->product) {
            throw new \Exception('Sản phẩm không tồn tại');
        }
        
        // Validate stock
        $product = $variant->product;
        $variantStock = isset($variant->stock) && $variant->stock !== null 
            ? (int)$variant->stock 
            : (isset($product->stock) && $product->stock == 1 ? 999 : 0);
        
        if ($variantStock === 0) {
            throw new \Exception('Phân loại đã hết hàng');
        }
        
        // Check current cart qty
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);
        $currentQty = isset($cart->items[$variantId]) ? $cart->items[$variantId]['qty'] : 0;
        
        if ($variantStock > 0 && ($currentQty + $qty) > $variantStock) {
            throw new \Exception('Số lượng vượt quá tồn kho của phân loại');
        }
        
        // Handle Deal price
        if ($isDeal) {
            $now = strtotime(date('Y-m-d H:i:s'));
            $saledeal = SaleDeal::where('product_id', $product->id)
                ->whereHas('deal', function($query) use ($now) {
                    $query->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
                })->where('status', '1')->first();
            
            if ($saledeal) {
                $variant->price = $saledeal->price;
                $variant->sale = 0;
            }
        }
        
        // Add to cart
        $cart->add($variant, $variantId, $qty, $isDeal ? 1 : 0);
        
        // Save cart directly to session (same as old controller)
        // The Cart object will be serialized automatically by Laravel
        Session::put('cart', $cart);
        
        // Force save session to ensure persistence
        session()->save();
        Session::save();
        
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
                // Nếu lỗi từ WarehouseService, fallback về kiểm tra stock cũ
                $product = $variant->product;
                $variantStock = isset($variant->stock) && $variant->stock !== null 
                    ? (int)$variant->stock 
                    : (isset($product->stock) && $product->stock == 1 ? 999 : 0);
                
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
        // Get cart from session (same as old controller)
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);
        
        // DEBUG: Log cart state before removal
        Log::info('[CartService] removeItem - Cart state before', [
            'variant_id' => $variantId,
            'cart_items_count' => count($cart->items),
            'cart_items_keys' => array_keys($cart->items),
            'item_exists' => isset($cart->items[$variantId]),
            'session_has_cart' => Session::has('cart'),
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
        
        // Remove the item (simple - just call Cart model's removeItem)
        $cart->removeItem($variantId);
        
        // DEBUG: Log cart state after removal
        Log::info('[CartService] removeItem - Cart state after', [
            'variant_id' => $variantId,
            'items_count_before' => $itemsCountBefore,
            'items_count_after' => count($cart->items),
            'items_keys_before' => $itemsKeysBefore,
            'items_keys_after' => array_keys($cart->items),
            'removed_item' => $variantId,
        ]);
        
        // Save session (same as old controller - save directly)
        if (count($cart->items) > 0) {
            Session::put('cart', $cart);
            Log::info('[CartService] removeItem - Session put cart', [
                'items_count' => count($cart->items),
            ]);
        } else {
            Session::forget('cart');
            Session::forget('ss_counpon');
            Log::info('[CartService] removeItem - Session forget cart (empty)');
        }
        
        // Force save session
        session()->save();
        Session::save();
        
        // DEBUG: Verify session after save
        Log::info('[CartService] removeItem - Session after save', [
            'session_has_cart' => Session::has('cart'),
            'session_cart_items_count' => Session::has('cart') ? count(Session::get('cart')->items) : 0,
        ]);
        
        $discount = Session::has('ss_counpon') ? Session::get('ss_counpon')['sale'] : 0;
        
        return [
            'removed_variant_ids' => [$variantId],
            'summary' => [
                'total_qty' => $cart->totalQty,
                'subtotal' => (float)$cart->totalPrice,
                'discount' => (float)$discount,
                'total' => (float)($cart->totalPrice - $discount),
            ],
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
