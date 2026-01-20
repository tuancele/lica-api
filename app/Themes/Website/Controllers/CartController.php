<?php

namespace App\Themes\Website\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Post\Models\Post;
use App\Modules\Promotion\Models\Promotion;
use App\Modules\Product\Models\Variant;
use App\Themes\Website\Models\Cart;
use App\Modules\Pick\Models\Pick;
use Session;
use Validator;
use App\Modules\Order\Models\Order;
use App\Modules\Address\Models\Address;
use App\Modules\Product\Models\Product;
use App\Modules\Order\Models\OrderDetail;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Ward;
use App\Modules\Location\Models\Province;
use App\Traits\Location;
use App\Traits\Sendmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\SaleDeal;
use App\Themes\Website\Models\Facebook;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Models\ProductWarehouse;
use App\Services\Pricing\PriceEngineServiceInterface;
use App\Services\Warehouse\WarehouseServiceInterface;
use App\Services\Cart\CartService;

class CartController extends Controller
{
    use Location, Sendmail;
    
    protected PriceEngineServiceInterface $priceEngine;
    protected WarehouseServiceInterface $warehouseService;
    protected CartService $cartService;
    
    public function __construct(
        PriceEngineServiceInterface $priceEngine,
        WarehouseServiceInterface $warehouseService,
        CartService $cartService
    ) {
        $this->priceEngine = $priceEngine;
        $this->cartService = $cartService;
        $this->warehouseService = $warehouseService;
        
        // Inject WarehouseService vào PriceEngineService
        if (method_exists($this->priceEngine, 'setWarehouseService')) {
            $this->priceEngine->setWarehouseService($warehouseService);
        }
    }

    public function district($id)
    {
        echo $this->getDistrict($id);
    }

    public function ward($id)
    {
        echo $this->getWard($id);
    }

    public function index()
    {
        $data['products'] = null;
        $data['totalPrice'] = 0;
        $data['productsWithPrice'] = []; // Mảng chứa sản phẩm với giá đã tính lại

        if (Session::has('cart')) {
            $oldCart = Session::get('cart');
            $cart = new Cart($oldCart);
            $data['products'] = $cart->items;
            
            // QUAN TRỌNG: Tính lại giá cho từng item với số lượng thực tế
            $recalculatedTotal = 0;
            foreach ($cart->items as $variantId => $item) {
                $variant = Variant::with('product')->find($variantId);
                if (!$variant || !$variant->product) {
                    continue;
                }
                
                $quantity = (int)($item['qty'] ?? 1);
                
                // Tính lại giá với PriceEngineService
                $priceWithQuantity = $this->priceEngine->calculatePriceWithQuantity(
                    $variant->product->id,
                    $variantId,
                    $quantity
                );

                // Kiểm tra Deal Sốc (cảnh báo hiển thị, không đổi DOM)
                $dealWarning = null;
                if (!empty($item['is_deal'])) {
                    $dealCheck = $this->validateDealAvailability($item['item']['product_id'], $variantId, $quantity);
                    if (!$dealCheck['available']) {
                        $dealWarning = $dealCheck['message'];
                    } else {
                        // Áp dụng giá Deal nếu thỏa điều kiện ưu tiên (kể cả Deal 0đ)
                        $dealPricing = $this->applyDealPriceForCartItem(
                            $item['item']['product_id'],
                            $variantId,
                            $quantity,
                            $priceWithQuantity
                        );
                        if ($dealPricing !== null) {
                            $priceWithQuantity['total_price'] = $dealPricing['total_price'];
                            $priceWithQuantity['price_breakdown'] = $dealPricing['price_breakdown'];
                        }
                    }
                }
                
                $recalculatedTotal += $priceWithQuantity['total_price'];
                
                // Lưu thông tin giá đã tính lại
                $data['productsWithPrice'][$variantId] = [
                    'price_breakdown' => $priceWithQuantity['price_breakdown'] ?? null,
                    'total_price' => $priceWithQuantity['total_price'],
                    'warning' => $priceWithQuantity['warning'] ?? null,
                    'deal_warning' => $dealWarning,
                    'flash_sale_remaining' => $priceWithQuantity['flash_sale_remaining'] ?? 0,
                ];
            }
            
            // Log để kiểm tra
            Log::info('[CartController::index] Price recalculated', [
                'old_total' => $cart->totalPrice,
                'new_total' => $recalculatedTotal,
                'difference' => abs($cart->totalPrice - $recalculatedTotal),
            ]);
            
            $data['totalPrice'] = $recalculatedTotal; // Sử dụng tổng đã tính lại

            // Đếm số lượng deal hiện có trong giỏ hàng theo từng deal_id
            $deal_counts = [];
            foreach($cart->items as $item) {
                if(isset($item['is_deal']) && $item['is_deal'] == 1) {
                    // Cần lấy deal_id từ sản phẩm phụ này
                    $now = strtotime(date('Y-m-d H:i:s'));
                    $saledeal = SaleDeal::where('product_id', $item['item']['product_id'])
                        ->whereHas('deal', function($query) use ($now) {
                            $query->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
                        })->where('status', '1')->first();
                    if($saledeal) {
                        $deal_counts[$saledeal->deal_id] = ($deal_counts[$saledeal->deal_id] ?? 0) + 1;
                    }
                }
            }
            $data['deal_counts'] = $deal_counts;

            // Kiểm tra các sản phẩm chính trong giỏ hàng có deal sốc không để gợi ý
            $main_product_ids = [];
            foreach($cart->items as $item) {
                if(!isset($item['is_deal']) || $item['is_deal'] == 0) {
                    $main_product_ids[] = $item['item']['product_id'];
                }
            }

            if(!empty($main_product_ids)) {
                $now = strtotime(date('Y-m-d H:i:s'));
                $deals = Deal::whereHas('products', function($q) use ($main_product_ids) {
                    $q->whereIn('product_id', $main_product_ids)->where('status', '1');
                })->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]])->with('sales.product')->get();
                
                // Gắn cờ available cho sale products theo quota (qty-buy) và tồn kho thực (S_phy)
                $deals = $deals->map(function($deal) {
                    $deal->sales = $deal->sales->map(function($sale) {
                        $remaining = max(0, ((int)$sale->qty) - ((int)($sale->buy ?? 0)));
                        $stock = 0;
                        try {
                            $variantId = $sale->variant_id;
                            if ($variantId) {
                                $stockInfo = app(WarehouseServiceInterface::class)->getVariantStock($variantId);
                                $stock = (int)($stockInfo['current_stock'] ?? 0);
                            } else {
                                // không có variant, dùng product->stock nếu có
                                $stock = (int)($sale->product->stock ?? 0);
                            }
                        } catch (\Throwable $e) {
                            Log::warning('[CartController] getVariantStock fail for deal suggestion', [
                                'sale_id' => $sale->id,
                                'variant_id' => $sale->variant_id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                        $sale->remaining_quota = $remaining;
                        $sale->available = $remaining > 0 && $stock > 0;
                        $sale->physical_stock = $stock;
                        return $sale;
                    });
                    return $deal;
                });

                $data['available_deals'] = $deals;
            }
        }

        return view('Website::cart.index', $data);
    }

    public function loadDistrict($id)
    {
        $districts = District::select('districtid', 'name')->where('provinceid', $id)->orderBy('name', 'asc')->get();
        return response()->json($districts);
    }

    public function loadWard($id)
    {
        $wards = Ward::select('wardid', 'name')->where('districtid', $id)->orderBy('name', 'asc')->get();
        return response()->json($wards);
    }

    public function checkout(Request $request)
    {
        // Bước 1: Đồng bộ Session theo Token (The Core Fix)
        // Trước khi lấy dữ liệu giỏ hàng, hãy kiểm tra: Nếu có token trên URL, phải đảm bảo Session hiện tại được liên kết đúng với giỏ hàng của người dùng đó
        
        // Security Token
        $token = md5(Session::getId() . 'checkout_secure');
        if (!$request->has('token') || $request->token !== $token) {
            return redirect()->route('cart.payment', ['token' => $token]);
        }
        
        // ÉP BUỘC: Gọi session()->get('cart', []) để kiểm tra session cart
        $cartItems = session()->get('cart', []);
        
        // KIỂM TRA: Nếu $cartItems rỗng, hãy Log error
        if (empty($cartItems)) {
            Log::error('[CHECKOUT_FATAL] Session cart is empty on checkout page!', [
                'session_id' => Session::getId(),
                'has_cart_session' => Session::has('cart'),
                'token' => $token,
            ]);
            return redirect('cart/gio-hang')->with('error', 'Giỏ hàng của bạn đã trống. Vui lòng thêm sản phẩm vào giỏ hàng.');
        }
        
        // Bước 2: Ép Backend tính toán lại Tổng tiền (Server-side Only)
        // Không tin tưởng vào bất kỳ biến $cart->totalPrice nào có sẵn
        // Tính toán thủ công một lần nữa từ mảng Session
        $finalSubtotal = 0;
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        
        // Duyệt qua từng item trong session cart
        foreach ($cart->items as $variantId => $item) {
            $variant = Variant::with('product')->find($variantId);
            if (!$variant || !$variant->product) {
                Log::warning('[CartController::checkout] Variant not found', [
                    'variant_id' => $variantId,
                ]);
                continue;
            }
            
            $quantity = (int)($item['qty'] ?? 1);
            
            // Gọi trực tiếp PriceEngine để lấy giá chuẩn (Flash Sale/Thường)
            $priceInfo = $this->priceEngine->calculatePriceWithQuantity(
                $variant->product->id,
                $variantId,
                $quantity
            );
            
            // Áp dụng giá Deal Sốc nếu có và thỏa điều kiện
            if (!empty($item['is_deal'])) {
                $dealPricing = $this->applyDealPriceForCartItem(
                    $variant->product->id,
                    $variantId,
                    $quantity,
                    $priceInfo
                );
                if ($dealPricing !== null) {
                    $priceInfo['total_price'] = $dealPricing['total_price'];
                }
            }
            
            $finalSubtotal += (float)($priceInfo['total_price'] ?? 0);
        }
        
        // 1. Lấy dữ liệu tổng lực từ Service
        $cartSummary = $this->cartService->getCart();

        // 2. Tính lại subtotal từ cartSummary.items
        // Lấy LUÔN subtotal từ Service trả về (kể cả nó là 0đ cho Deal Sốc/quà tặng)
        $totalPrice = 0.0;
        $productsWithPrice = [];
        foreach ($cartSummary['items'] as $item) {
            $vId = $item['variant_id'] ?? null;
            if (!$vId) {
                continue;
            }

            // Lấy LUÔN subtotal từ Service trả về (kể cả nó là 0đ)
            $itemSubtotal = (float)($item['subtotal'] ?? 0);

            // Chốt: không để âm
            $itemSubtotal = max(0.0, $itemSubtotal);

            $totalPrice += $itemSubtotal;
            $productsWithPrice[$vId] = [
                'total_price' => $itemSubtotal,
                'price_breakdown' => $item['price_breakdown'] ?? null,
                'warning' => $item['warning'] ?? null,
                'deal_warning' => $item['deal_warning'] ?? null,
                'flash_sale_remaining' => $item['flash_sale_remaining'] ?? 0,
            ];
        }

        // ===== THÊM LOG DEBUG (tổng lực sau khi re-calc) =====
        Log::info('[DEBUG_CHECKOUT] CartController recalculated total from cartSummary.items', [
            'cart_summary' => $cartSummary['summary'] ?? null,
            'items_count' => count($cartSummary['items'] ?? []),
            'recalculated_total' => $totalPrice,
        ]);
        // ===== END LOG =====
        
        // ===== PHẦN 4: Lấy coupon/sale =====
        $sale = (float)($cartSummary['summary']['discount'] ?? 0);
        $code = null;
        if (Session::has('ss_counpon')) {
            $couponData = Session::get('ss_counpon');
            $sale = (float)($couponData['sale'] ?? 0);
            $code = $couponData['code'] ?? null;
        }
        
        // CRITICAL: Nếu totalPrice bằng 0 nhưng có items, kiểm tra lại
        if ($totalPrice <= 0 && !empty($cart->items)) {
            Log::error('[CartController::checkout] Total price is 0 but cart has items', [
                'cart_items_count' => count($cart->items),
                'final_subtotal' => $finalSubtotal,
                'cart_summary' => $cartSummary['summary'] ?? null,
            ]);
        }
        
        // ===== THÊM LOG CRITICAL =====
        Log::info('[DEBUG_CHECKOUT] CRITICAL - totalPrice for view', [
            'totalPrice' => $totalPrice,
            'is_zero' => $totalPrice == 0,
            'raw_subtotal' => $cartSummary['summary']['subtotal'] ?? 'NOT_SET',
            'final_subtotal_calculated' => $finalSubtotal,
        ]);
        // ===== END LOG =====
        
        // ===== PHẦN 3: productsWithPrice đã được tính ở trên, không cần tính lại =====

        // Calculate FeeShip
        $feeShip = 0;
        $member = auth()->guard('member')->user();
        if ($member && getConfig('ghtk_status')) {
            $pick = Pick::where('status', '1')->orderBy('sort', 'asc')->first();
            if ($pick) {
                $address = Session::has('ss_address') 
                    ? Address::where([['member_id', $member['id']], ['id', Session::get('ss_address')]])->first()
                    : Address::where([['member_id', $member['id']], ['is_default', '1']])->first();

                if ($address) {
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

                    $info = [
                        "pick_province" => $pick->province->name ?? '',
                        "pick_district" => $pick->district->name ?? '',
                        "pick_ward" => $pick->ward->name ?? '',
                        "pick_street" => $pick->street,
                        "pick_address" => $pick->address,
                        "province" => $address->province->name ?? '',
                        "district" => $address->district->name ?? '',
                        "ward" => $address->ward->name ?? '',
                        "address" => $address->address,
                        "weight" => $weight,
                        "value" => $totalPrice - $sale, // Sử dụng $totalPrice đã tính lại từ session
                        "transport" => 'road',
                        "deliver_option" => 'none',
                        "tags" => [0],
                    ];

                    $getFee = json_decode($this->getFee($info));
                    if ($getFee && $getFee->success) {
                        $feeShip = $getFee->fee->fee;
                    }
                }
            }
        }
        $feeship = $feeShip;
        
        // ===== PHẦN 6: Lấy province và promotions =====
        $province = $this->getProvince();
        $promotions = Promotion::where([
            ['status', '1'], 
            ['end', '>=', date('Y-m-d')], 
            ['order_sale', '<=', $totalPrice]
        ])->limit('8')->get();
        
        // ===== CRITICAL: Log để debug =====
        Log::info('[CHECKOUT_FINAL] Subtotal: ' . $totalPrice, [
            'totalPrice' => $totalPrice,
            'sale' => $sale,
            'feeship' => $feeship,
            'finalTotal' => $totalPrice - $sale + $feeship,
        ]);

        // ===== CRITICAL: Return view với TẤT CẢ biến (dùng array trực tiếp) =====
        return view('Website::cart.checkout', [
            'products' => $cart->items,
            'cart' => $cart,
            'productsWithPrice' => $productsWithPrice,
            'totalPrice' => $totalPrice,      // ← QUAN TRỌNG
            'sale' => $sale,                   // ← QUAN TRỌNG
            'code' => $code,
            'feeship' => $feeship,             // ← QUAN TRỌNG
            'province' => $province,
            'promotions' => $promotions,
            'token' => $token,
        ]);
    }

    /**
     * Áp dụng giá Deal Sốc cho item trong giỏ hàng (tầng view Cart/Checkout).
     *
     * Rule giống CartService:
     * - Nếu breakdown có flashsale/promotion => giữ nguyên, không override Deal.
     * - Nếu toàn bộ breakdown là normal:
     *   + Lấy SaleDeal.price làm dealPrice.
     *   + Nếu dealPrice > 0 && dealPrice < original_price => dùng dealPrice.
     *   + Nếu dealPrice == 0 && original_price > 0 => dùng 0đ (Deal 0đ).
     *
     * @param int   $productId
     * @param int   $variantId
     * @param int   $quantity
     * @param array $priceWithQuantity
     * @return array|null
     */
    private function applyDealPriceForCartItem(int $productId, int $variantId, int $quantity, array $priceWithQuantity): ?array
    {
        if ($quantity <= 0) {
            return null;
        }

        if (empty($priceWithQuantity['price_breakdown']) || !is_array($priceWithQuantity['price_breakdown'])) {
            return null;
        }

        $breakdown = $priceWithQuantity['price_breakdown'];

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

        $firstLine = $breakdown[0];
        $originalPrice = (float)($firstLine['unit_price'] ?? 0);
        if ($originalPrice <= 0) {
            return null;
        }

        $now = time();
        $saleDealQuery = SaleDeal::where('product_id', $productId)
            ->whereHas('deal', function ($q) use ($now) {
                $q->where('status', '1')
                    ->where('start', '<=', $now)
                    ->where('end', '>=', $now);
            });

        $saleDealQuery->where(function ($q) use ($variantId) {
            $q->where('variant_id', $variantId)
                ->orWhereNull('variant_id');
        });

        $saleDeal = $saleDealQuery->first();
        if (!$saleDeal) {
            return null;
        }

        $dealPrice = (float)($saleDeal->price ?? 0);

        if ($dealPrice > 0 && $dealPrice < $originalPrice) {
            $total = $dealPrice * $quantity;

            return [
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

        if ($dealPrice == 0.0 && $originalPrice > 0) {
            $total = 0.0;

            return [
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

        return null;
    }

    /**
     * Kiểm tra quỹ/tồn kho Deal Sốc (hiển thị cảnh báo, không lock).
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

        $saleDealQuery->where(function ($q) use ($variantId) {
            $q->where('variant_id', $variantId)
                ->orWhereNull('variant_id');
        });

        $saleDeal = $saleDealQuery->first();
        if (!$saleDeal) {
            return [
                'available' => false,
                'message' => 'Quà tặng Deal Sốc đã hết, giá được chuyển về giá thường/khuyến mại.',
            ];
        }

        // Quỹ deal (Shopee style): qty là số suất còn lại
        if ((int) $saleDeal->qty < $quantity) {
            return [
                'available' => false,
                'message' => 'Quà tặng Deal Sốc đã hết, giá được chuyển về giá thường/khuyến mại.',
            ];
        }

        try {
            $stockInfo = $this->warehouseService->getVariantStock($variantId);
            $phy = (int) ($stockInfo['current_stock'] ?? 0);
            if ($phy <= 0) {
                return [
                    'available' => false,
                    'message' => 'Quà tặng Deal Sốc đã hết, giá được chuyển về giá thường/khuyến mại.',
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('[CartController] validateDealAvailability stock check failed', [
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

    public function loadPromotion()
    {
        if (Session::has('cart')) {
            $oldCart = Session::get('cart');
            $cart = new Cart($oldCart);
            $data['list'] = Promotion::where([['status', '1'], ['end', '>=', date('Y-m-d')], ['order_sale', '<=', $cart->totalPrice]])->limit('10')->get();
            return view('Website::cart.promotion', $data);
        }
        return 'Không có dữ liệu';
    }

    public function applyCoupon(Request $request)
    {
        if (Session::has('ss_counpon')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mã giảm không được dùng chung với mã khác'
            ]);
        }

        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        $detail = Promotion::where([['status', '1'], ['start', '<=', date('Y-m-d')], ['end', '>=', date('Y-m-d')], ['order_sale', '<=', $cart->totalPrice], ['code', $request->code]])->first();

        if ($detail) {
            $count = Order::where('promotion_id', $detail->id)->count();
            if ($count < $detail->number) {
                if ($detail->unit == 0) {
                    $sale = round(($cart->totalPrice / 100) * $detail->value);
                } else {
                    $sale = $detail->value;
                }

                Session::put('ss_counpon', [
                    'id' => $detail->id,
                    'sale' => $sale,
                    'code' => $detail->code,
                    'value' => $detail->vale,
                    'unit' => $detail->unit
                ]);

                return response()->json([
                    'status' => 'success',
                    'sale' => number_format($sale) . 'đ',
                    'total' => number_format($cart->totalPrice - $sale + $request->feeship) . 'đ',
                    'id' => $detail->id,
                    'code' => $detail->code,
                    'message' => 'Áp dụng mã thành công'
                ]);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Mã đã hết lượt sử dụng']);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => 'Mã khuyến mãi không khả dụng']);
        }
    }

    public function cancelCoupon(Request $request)
    {
        Session::forget('ss_counpon');
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        return response()->json([
            'status' => 'success',
            'total' => number_format($cart->totalPrice + $request->feeship) . 'đ',
        ]);
    }

    public function postCheckout(Request $req)
    {
        // Security Token Check
        $token = md5(Session::getId() . 'checkout_secure');
        if ($req->token !== $token) {
             return response()->json(['status' => 'error', 'message' => 'Phiên giao dịch không hợp lệ. Vui lòng tải lại trang.']);
        }

        // Add basic validation
        $validator = Validator::make($req->all(), [
            'full_name' => 'required',
            'phone' => 'required',
            'email' => 'nullable|email',
            'address' => 'nullable',
            'province' => 'required',
            'district' => 'required',
            'ward' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin giao hàng.', 'errors' => $validator->errors()]);
        }

        if (!Session::has('cart')) {
            return response()->json(['status' => 'error', 'message' => 'Giỏ hàng trống']);
        }

        try {
            DB::beginTransaction();
            $sale = 0;
            $promotion = 0;
            $oldCart = Session::get('cart');
            $cart = new Cart($oldCart);
            
            // QUAN TRỌNG: Tính lại tổng tiền tại Backend để đảm bảo tính chính xác
            $backendTotal = 0;
            foreach ($cart->items as $variantId => $item) {
                $variant = Variant::with('product')->find($variantId);
                if (!$variant || !$variant->product) {
                    continue;
                }
                
                $quantity = (int)($item['qty'] ?? 1);
                
                // Tính lại giá với PriceEngineService
                $priceWithQuantity = $this->priceEngine->calculatePriceWithQuantity(
                    $variant->product->id,
                    $variantId,
                    $quantity
                );
                
                $backendTotal += $priceWithQuantity['total_price'];
            }
            
            // Log để kiểm tra
            Log::info('[CartController::postCheckout] Price validation', [
                'session_total' => $cart->totalPrice,
                'backend_calculated_total' => $backendTotal,
                'difference' => abs($cart->totalPrice - $backendTotal),
                'frontend_total' => $req->total ?? 'not_provided',
            ]);
            
            // Nếu tổng tiền tính lại khác với tổng tiền trong session, sử dụng giá Backend
            if (abs($cart->totalPrice - $backendTotal) > 0.01) {
                Log::warning('[CartController::postCheckout] Price mismatch detected', [
                    'session_total' => $cart->totalPrice,
                    'backend_total' => $backendTotal,
                    'difference' => abs($cart->totalPrice - $backendTotal),
                ]);
                
                // Cập nhật lại tổng tiền trong cart để đảm bảo tính chính xác
                $cart->totalPrice = $backendTotal;
            }

            if (Session::has('ss_counpon')) {
                $ss_counpon = Session::get('ss_counpon');
                // Re-validate coupon
                $detail = Promotion::where([['status', '1'], ['start', '<=', date('Y-m-d')], ['end', '>=', date('Y-m-d')], ['order_sale', '<=', $cart->totalPrice], ['id', $ss_counpon['id']]])->first();
                if ($detail) {
                    $count = Order::where('promotion_id', $detail->id)->count();
                    if ($count < $detail->number) {
                        $sale = ($detail->unit == 0) ? round(($cart->totalPrice / 100) * $detail->value) : $detail->value;
                        $promotion = $ss_counpon['id'];
                    }
                }
            }

            $feeShip = 0;
            if (getConfig('free_ship') && $cart->totalPrice >= getConfig('free_order')) {
                $feeShip = 0;
            } else if (getConfig('ghtk_status')) {
                $pick = Pick::where('status', '1')->orderBy('sort', 'asc')->first();
                if ($pick) {
                    $province = Province::where('provinceid', $req->province)->first();
                    $district = District::where('districtid', $req->district)->first();
                    $ward = Ward::where('wardid', $req->ward)->first();
                    
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

                    $info = [
                        "pick_province" => $pick->province->name ?? '',
                        "pick_district" => $pick->district->name ?? '',
                        "pick_ward" => $pick->ward->name ?? '',
                        "pick_street" => $pick->street,
                        "pick_address" => $pick->address,
                        "province" => $province->name ?? '',
                        "district" => $district->name ?? '',
                        "ward" => $ward->name ?? '',
                        "address" => $req->address,
                        "weight" => $weight,
                        "value" => $cart->totalPrice - $sale,
                        "transport" => 'road',
                        "deliver_option" => 'none',
                        "tags" => [0],
                    ];
                    
                    $getFee = json_decode($this->getFee($info));
                    if ($getFee && $getFee->success) {
                        $feeShip = $getFee->fee->fee;
                    }
                }
            }

            $member = auth()->guard('member')->user();
            $code = time();

            // Create Order
            $order_id = Order::insertGetId([
                'code' => $code,
                'name' => $req->full_name,
                'phone' => $req->phone,
                'email' => $req->email,
                'address' => $req->address,
                'provinceid' => $req->province,
                'districtid' => $req->district,
                'wardid' => $req->ward,
                'remark' => $req->remark,
                'member_id' => $member['id'] ?? 0,
                'ship' => '0',
                'sale' => $sale,
                'total' => $cart->totalPrice,
                'promotion_id' => $promotion,
                'fee_ship' => $feeShip,
                'status' => '0',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Save Address if new
            if ($member && !Address::where([['member_id', $member['id']], ['is_default', '1']])->exists()) {
                $parts = explode(' ', $req->full_name, 2);
                $firstName = $parts[0] ?? '';
                $lastName = $parts[1] ?? '';
                Address::insert([
                    'member_id' => $member['id'],
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $req->email,
                    'phone' => $req->phone,
                    'address' => $req->address,
                    'wardid' => $req->ward,
                    'is_default' => '1',
                    'districtid' => $req->district,
                    'provinceid' => $req->province,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

                    if ($order_id > 0) {
                        $validItemsCount = 0;
                        $processedItems = [];
                        
                        foreach ($cart->items as $variant) {
                            try {
                                // Handle variant item - could be object or array after session serialization
                                $item = $variant['item'];
                                $product_id = null;
                                
                                // Extract product_id safely
                                if (is_object($item)) {
                                    $product_id = $item->product_id ?? null;
                                } elseif (is_array($item)) {
                                    $product_id = $item['product_id'] ?? null;
                                } else {
                                    Log::error("Invalid item format in cart: " . gettype($item));
                                    continue;
                                }
                                
                                if (!$product_id) {
                                    Log::error("Product ID not found in cart item");
                                    continue;
                                }
                                
                                $product = Product::find($product_id);
                                
                                // Validate product exists
                                if (!$product) {
                                    Log::error("Product not found: " . $product_id);
                                    continue;
                                }
                                
                                // Store valid item for processing
                                $processedItems[] = [
                                    'variant' => $variant,
                                    'item' => $item,
                                    'product' => $product,
                                    'product_id' => $product_id
                                ];
                                $validItemsCount++;
                            } catch (\Exception $itemException) {
                                Log::error("Error processing cart item: " . $itemException->getMessage());
                                Log::error("Item data: " . json_encode($variant));
                                // Continue with next item instead of failing entire order
                                continue;
                            }
                        }
                        
                        // Check if we have at least one valid item
                        if ($validItemsCount == 0) {
                            // Delete the order if no valid items
                            Order::where('id', $order_id)->delete();
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Không thể tạo đơn hàng. Một hoặc nhiều sản phẩm trong giỏ hàng không còn khả dụng. Vui lòng kiểm tra lại giỏ hàng.'
                            ]);
                        }
                        
                        // Process all valid items
                        foreach ($processedItems as $processed) {
                            $variant = $processed['variant'];
                            $item = $processed['item'];
                            $product = $processed['product'];
                            $product_id = $processed['product_id'];
                            
                            $product_name = $product->name ?? 'Sản phẩm không xác định';
                            if (isset($variant['is_deal']) && $variant['is_deal'] == 1) {
                                $product_name = '[DEAL SỐC] ' . $product_name;
                            }
                            
                            // Extract variant data safely
                            $variant_id = null;
                            $color_id = null;
                            $size_id = null;
                            $weight = 0;
                            $dealsale_id = $variant['dealsale_id'] ?? null;
                            
                            if (is_object($item)) {
                                $variant_id = $item->id ?? null;
                                $color_id = $item->color_id ?? null;
                                $size_id = $item->size_id ?? null;
                                $weight = $item->weight ?? 0;
                            } elseif (is_array($item)) {
                                $variant_id = $item['id'] ?? null;
                                $color_id = $item['color_id'] ?? null;
                                $size_id = $item['size_id'] ?? null;
                                $weight = $item['weight'] ?? 0;
                            }

                            // Shopee-style deal quota: decrement deal_sales.qty at order creation time
                            if (isset($variant['is_deal']) && (int) $variant['is_deal'] === 1) {
                                if (!$dealsale_id) {
                                    throw new \Exception('Thiếu dealsale_id cho sản phẩm Deal Sốc');
                                }

                                /** @var SaleDeal|null $saleDeal */
                                $saleDeal = SaleDeal::where('id', (int) $dealsale_id)->lockForUpdate()->first();
                                if (!$saleDeal) {
                                    throw new \Exception('Suất quà tặng vừa hết');
                                }

                                $quantityDeal = (int) ($variant['qty'] ?? 1);
                                if ((int) $saleDeal->qty < $quantityDeal) {
                                    throw new \Exception('Suất quà tặng vừa hết');
                                }

                                Log::info('[DEAL_SALE_UPDATE] DealID: ' . $dealsale_id . ' | Suất cũ: ' . ((int) $saleDeal->qty) . ' | Trừ: ' . $quantityDeal);
                                $saleDeal->decrement('qty', $quantityDeal);
                                $saleDeal->increment('buy', $quantityDeal);
                                $saleDeal->refresh();
                                Log::info('[DEAL_SALE_UPDATE] DealID: ' . $dealsale_id . ' | Suất mới sau khi trừ: ' . (int) $saleDeal->qty);
                            }
                            
                            OrderDetail::insert([
                                'order_id' => $order_id,
                                'product_id' => $product_id,
                                'variant_id' => $variant_id,
                                'name' => $product_name,
                                'color_id' => $color_id,
                                'size_id' => $size_id,
                                'price' => $variant['price'] ?? 0,
                                'qty' => $variant['qty'] ?? 1,
                                'image' => $product->image ?? '',
                                'weight' => $weight * ($variant['qty'] ?? 1),
                                'subtotal' => ($variant['price'] ?? 0) * ($variant['qty'] ?? 1),
                                'dealsale_id' => $dealsale_id,
                                'created_at' => date('Y-m-d H:i:s')
                            ]);

                            // Update FlashSale Stock
                            $date = strtotime(date('Y-m-d H:i:s'));
                            $flash = FlashSale::where([['status', '1'], ['start', '<=', $date], ['end', '>=', $date]])->first();
                            if ($flash) {
                                $pro = ProductSale::where([['flashsale_id', $flash->id], ['product_id', $product_id]])->first();
                                if ($pro) {
                                    $pro->increment('buy', $variant['qty'] ?? 1);
                                }
                            }

                            // Facebook Tracking
                            if ($product && isset($product->slug)) {
                                $dataf = [
                                    'email' => $req->email,
                                    'phone' => $req->phone,
                                    'product_id' => $product_id,
                                    'price' => $variant['price'] ?? 0,
                                    'url' => getSlug($product->slug),
                                    'event' => 'Purchase',
                                ];
                                Facebook::track($dataf);
                            }
                        }

                // Auto create export receipt for the order
                try {
                    $this->createExportReceiptFromOrder($order_id, $code);
                } catch (\Exception $exportException) {
                    // Log export receipt error but don't fail the order
                    Log::error('Auto create export receipt failed: ' . $exportException->getMessage());
                    Log::error('Order code: ' . $code);
                    Log::error('Export error trace: ' . $exportException->getTraceAsString());
                    // Continue with order success even if export receipt creation fails
                }

                // Send email notification (non-blocking - don't fail order if email fails)
                try {
                    $replyEmail = getConfig('reply_email');
                    if ($replyEmail) {
                        $this->send('Website::email.order', 'Đơn đặt hàng Walcos', $replyEmail, $code);
                    }
                } catch (\Exception $emailException) {
                    // Log email error but don't fail the order
                    Log::error('Order email sending failed: ' . $emailException->getMessage());
                    Log::error('Order code: ' . $code);
                    Log::error('Email error trace: ' . $emailException->getTraceAsString());
                    // Continue with order success even if email fails
                }
                
                DB::commit();
                Session::forget('cart');
                Session::forget('ss_counpon');
                
                return response()->json([
                    'status' => 'success',
                    'url' => '/cart/dat-hang-thanh-cong?code=' . $code
                ]);
            }
            
            return response()->json(['status' => 'error', 'message' => 'Lỗi tạo đơn hàng.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout Error: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());
            Log::error('Request Data: ' . json_encode($req->all()));
            if (isset($cart)) {
                Log::error('Cart Items: ' . json_encode($cart->items));
            }
            return response()->json([
                'status' => 'error', 
                'message' => 'Có lỗi xảy ra khi xử lý đơn hàng. Vui lòng thử lại sau.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }

    public function addCart(Request $req)
    {
        try {
            $oldCart = Session::has('cart') ? Session::get('cart') : null;
            $cart = new Cart($oldCart);

            if ($req->combo && is_array($req->combo)) {
                // Thêm nhiều sản phẩm cùng lúc (Combo)
                foreach ($req->combo as $item) {
                    $variant = Variant::with('product')->find($item['id']);
                    if ($variant) {
                        $addQty = (int)($item['qty'] ?? 0);
                        if ($addQty <= 0) continue;

                        // Basic stock guard (Shopee variant stock)
                        // Compatible with old products: if variant.stock is NULL, use product.stock
                        $variantStock = isset($variant->stock) && $variant->stock !== null 
                            ? (int)$variant->stock 
                            : (isset($variant->product->stock) && $variant->product->stock == 1 ? 999 : 0);
                        
                        if ($variantStock > 0 && $addQty > $variantStock) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Số lượng vượt quá tồn kho của phân loại'
                            ]);
                        }
                        if ($variantStock === 0) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Phân loại đã hết hàng'
                            ]);
                        }

                        $is_deal = isset($item['is_deal']) ? $item['is_deal'] : 0;
                        if ($is_deal == 1) {
                            $now = strtotime(date('Y-m-d H:i:s'));
                            $saledeal = SaleDeal::where('product_id', $variant->product_id)
                                ->whereHas('deal', function($query) use ($now) {
                                    $query->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
                                })->where('status', '1')->first();
                            if ($saledeal) {
                                $variant->price = $saledeal->price;
                                $variant->sale = 0;
                            }
                        }
                        $cart->add($variant, $variant->id, $addQty, $is_deal);
                    }
                }
                Session::put('cart', $cart);
                return response()->json([
                    'status' => 'success',
                    'total' => $cart->totalQty
                ]);
            }

            // Thêm 1 sản phẩm như cũ
            $variant = Variant::with('product')->find($req->id);
            if ($variant) {
                $addQty = (int)($req->qty ?? 0);
                if ($addQty <= 0) {
                    return response()->json(['status' => 'error', 'message' => 'Số lượng không hợp lệ']);
                }

                // Basic stock guard (Shopee variant stock)
                // Compatible with old products: if variant.stock is NULL, use product.stock
                $variantStock = isset($variant->stock) && $variant->stock !== null 
                    ? (int)$variant->stock 
                    : (isset($variant->product->stock) && $variant->product->stock == 1 ? 999 : 0);
                
                if ($variantStock > 0 && $addQty > $variantStock) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Số lượng vượt quá tồn kho của phân loại'
                    ]);
                }
                if ($variantStock === 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Phân loại đã hết hàng'
                    ]);
                }

                $is_deal = $req->is_deal ?? 0;
                // Xử lý giá deal nếu có yêu cầu
                if ($is_deal == 1) {
                    $now = strtotime(date('Y-m-d H:i:s'));
                    $saledeal = SaleDeal::where('product_id', $variant->product_id)
                        ->whereHas('deal', function($query) use ($now) {
                            $query->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
                        })->where('status', '1')->first();
                    
                    if ($saledeal) {
                        $variant->price = $saledeal->price; 
                        $variant->sale = 0; 
                    }
                }

                $cart->add($variant, $variant->id, $addQty, $is_deal);
                Session::put('cart', $cart);
                
                return response()->json([
                    'status' => 'success',
                    'name' => $variant->product->name,
                    'total' => $cart->totalQty
                ]);
            }
            return response()->json(['status' => 'error']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function delCart(Request $req)
    {
        try {
            $oldCart = Session::has('cart') ? Session::get('cart') : null;
            $cart = new Cart($oldCart);
            $cart->removeItem($req->id);
            
            $this->validateDeals($cart);

            if (count($cart->items) > 0) {
                Session::put('cart', $cart);
                $status = "true";
            } else {
                $status = "false";
                Session::forget('cart');
                Session::forget('ss_counpon');
            }
            
            return response()->json([
                'status' => $status,
                'total' => $cart->totalQty,
                'price' => number_format($cart->totalPrice),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function updateCart(Request $req)
    {
        try {
            $qty = $req->qty;
            $oldCart = Session::has('cart') ? Session::get('cart') : null;
            $cart = new Cart($oldCart);
            
            $cart->update($req->id, $qty);

            if ($qty <= 0) {
                $this->validateDeals($cart);
            }
            
            $sale = 0;
            if (Session::has('ss_counpon')) {
                $sale = Session::get('ss_counpon')['sale'];
            }

            if (count($cart->items) > 0) {
                Session::put('cart', $cart);
            } else {
                Session::forget('cart');
                Session::forget('ss_counpon');
            }
            
            return response()->json([
                'total' => $cart->totalQty,
                'price' => number_format($cart->totalPrice),
                'subtotal' => (isset($cart->items[$req->id])) ? number_format($cart->items[$req->id]['price'] * $qty) : 0,
                'totalPrice' => number_format($cart->totalPrice - $sale),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    /**
     * Kiểm tra và xóa các sản phẩm Deal Sốc nếu không còn sản phẩm chính tương ứng
     */
    private function validateDeals(&$cart)
    {
        if (empty($cart->items)) return;

        $now = strtotime(date('Y-m-d H:i:s'));
        
        // 1. Lấy danh sách sản phẩm chính đang có trong giỏ
        $current_main_product_ids = [];
        foreach ($cart->items as $item) {
            if (!isset($item['is_deal']) || $item['is_deal'] == 0) {
                $current_main_product_ids[] = $item['item']['product_id'];
            }
        }

        // 2. Tìm các Deal ID mà các sản phẩm chính này tham gia
        $active_deal_ids = [];
        if (!empty($current_main_product_ids)) {
            $active_deal_ids = \App\Modules\Deal\Models\ProductDeal::whereIn('product_id', $current_main_product_ids)
                ->whereHas('deal', function($q) use ($now) {
                    $q->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
                })
                ->pluck('deal_id')
                ->unique()
                ->toArray();
        }

        // 3. Kiểm tra các sản phẩm Deal Sốc trong giỏ
        foreach ($cart->items as $key => $item) {
            if (isset($item['is_deal']) && $item['is_deal'] == 1) {
                // Tìm xem sản phẩm này thuộc Deal nào
                $saledeal = \App\Modules\Deal\Models\SaleDeal::where('product_id', $item['item']['product_id'])
                    ->whereHas('deal', function($q) use ($now) {
                        $q->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
                    })
                    ->where('status', '1')
                    ->first();

                if (!$saledeal || !in_array($saledeal->deal_id, $active_deal_ids)) {
                    // Nếu sản phẩm này không thuộc deal nào đang active bởi sản phẩm chính, XÓA
                    $cart->removeItem($key);
                }
            }
        }
    }

    public function result(Request $req)
    {
        $order = Order::where('code', $req->code)->first();
        if (!$order) {
            return redirect('/');
        }
        $data['order'] = $order;
        $data['products'] = OrderDetail::where('order_id', $order->id)->get();
        return view('Website::cart.result', $data);
    }

    public function get()
    {
        $data['products'] = null;
        $data['totalQty'] = 0;
        $data['totalPrice'] = 0;

        if (Session::has('cart')) {
            $oldCart = Session::get('cart');
            $cart = new Cart($oldCart);
            $data['products'] = $cart->items;
            $data['cart'] = $cart;
            $data['totalQty'] = $cart->totalQty;
            $data['totalPrice'] = $cart->totalPrice;
        }
        return view('Website::cart.get', $data);
    }

    public function pickAddress()
    {
        // Use Guzzle instead of Curl
        try {
            $client = new Client();
            $response = $client->request('GET', env('GHTK_URL') . "services/shipment/list_pick_add", [
                'headers' => [
                    'Token' => env('GHTK_TOKEN'),
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents())->data;
            return $result[0]->pick_address_id ?? null;
        } catch (\Exception $e) {
            Log::error("GHTK pickAddress Error: " . $e->getMessage());
            return null;
        }
    }

    public function getFee($data)
    {
        // Use Guzzle instead of Curl
        try {
            $client = new Client();
            $response = $client->request('GET', getConfig('ghtk_url') . "/services/shipment/fee", [
                'headers' => [
                    'Token' => getConfig('ghtk_token')
                ],
                'query' => $data
            ]);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
             Log::error("GHTK getFee Error: " . $e->getMessage());
             return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function feeShip(Request $request)
    {
        if (!Session::has('cart')) {
            return response()->json(['status' => 'false', 'message' => 'Không tồn tại giỏ hàng']);
        }

        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        $subtotal = $cart->totalPrice;
        $sale = Session::has('ss_counpon') ? Session::get('ss_counpon')['sale'] : 0;

        if (getConfig('free_ship') && $cart->totalPrice >= getConfig('free_order')) {
            return response()->json([
                'status' => 'true',
                'feeship' => 0,
                'amount' => number_format($subtotal - $sale),
            ]);
        }

        if (getConfig('ghtk_status')) {
            $pick = Pick::where('status', '1')->orderBy('sort', 'asc')->first();
            if ($pick) {
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

                $info = [
                    "pick_province" => $pick->province->name ?? '',
                    "pick_district" => $pick->district->name ?? '',
                    "pick_ward" => $pick->ward->name ?? '',
                    "pick_street" => $pick->street,
                    "pick_address" => $pick->address,
                    "province" => $request->province,
                    "district" => $request->district,
                    "ward" => $request->ward,
                    "address" => $request->address,
                    "weight" => $weight,
                    "value" => $subtotal - $sale,
                    "transport" => 'road',
                    "deliver_option" => 'none',
                    "tags" => [0],
                ];

                $getFee = json_decode($this->getFee($info));
                $feeShip = ($getFee && $getFee->success) ? $getFee->fee->fee : 0;

                return response()->json([
                    'status' => 'true',
                    'feeship' => number_format($feeShip),
                    'amount' => number_format($subtotal - $sale + $feeShip),
                ]);
            }
            return response()->json(['status' => 'false', 'message' => 'Chưa cài đặt địa chỉ kho hàng']);
        }
        
        return response()->json(['status' => 'false', 'message' => 'Chưa kích hoạt vận chuyển']);
    }

    public function choseAddress(Request $request)
    {
        $member = auth()->guard('member')->user();
        Session::put('ss_address', $request->id);
        $address = Address::where([['member_id', $member['id']], ['id', $request->id]])->first();

        $feeShip = 0;
        $subtotal = 0;
        $html = "";

        if ($address) {
            $pick = Pick::where('status', '1')->orderBy('sort', 'asc')->first();
            $oldCart = Session::get('cart');
            $cart = new Cart($oldCart);
            $subtotal = $cart->totalPrice;
            $sale = Session::has('ss_counpon') ? Session::get('ss_counpon')['sale'] : 0;

            if ($pick) {
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

                $info = [
                    "pick_province" => $pick->province->name ?? '',
                    "pick_district" => $pick->district->name ?? '',
                    "pick_ward" => $pick->ward->name ?? '',
                    "pick_street" => $pick->street,
                    "pick_address" => $pick->address,
                    "province" => $address->province->name,
                    "district" => $address->district->name,
                    "ward" => $address->ward->name,
                    "address" => $address->address,
                    "weight" => $weight,
                    "value" => $subtotal - $sale,
                    "transport" => 'road',
                    "deliver_option" => 'none',
                    "tags" => [0],
                ];

                $getFee = json_decode($this->getFee($info));
                if ($getFee && $getFee->success) {
                    $feeShip = $getFee->fee->fee;
                }
            }

            $html .= '<p><strong>' . $address->last_name . ' | ' . $address->phone . ' | ' . $address->email . '</strong></p>';
            $html .= '<p>' . $address->address;
            if ($address->ward) $html .= ', ' . $address->ward->name;
            if ($address->district) $html .= ', ' . $address->district->name;
            if ($address->province) $html .= ', ' . $address->province->name;
            $html .= '</p>';
            
            $html .= '<input type="hidden" name="full_name" value="' . $address->first_name . ' ' . $address->last_name . '">';
            $html .= '<input type="hidden" name="phone" value="' . $address->phone . '">';
            $html .= '<input type="hidden" name="email" value="' . $address->email . '">';
            $html .= '<input type="hidden" name="province" value="' . $address->provinceid . '">';
            $html .= '<input type="hidden" name="district" value="' . $address->districtid . '">';
            $html .= '<input type="hidden" name="ward" value="' . $address->wardid . '">';
            $html .= '<input type="hidden" name="address" value="' . $address->address . '">';
        }

        return response()->json([
            'status' => 'success',
            'address' => $html,
            'feeship' => number_format($feeShip),
            'amount' => number_format($subtotal - $sale + $feeShip),
        ]);
    }

    public function searchLocation(Request $request)
    {
        $keyword = $request->q;
        if (!$keyword) return response()->json(['results' => []]);

        $keywordAscii = strtolower(to_ascii($keyword));
        $tokens = explode(' ', $keywordAscii);
        $tokens = array_filter($tokens);

        $locations = Cache::remember('locations_all_v1', 60 * 24, function () {
            $data = Ward::join('district', 'ward.districtid', '=', 'district.districtid')
                ->join('province', 'district.provinceid', '=', 'province.provinceid')
                ->select(
                    'ward.wardid', 
                    'ward.name as ward_name', 
                    'district.districtid', 
                    'district.name as district_name', 
                    'province.provinceid', 
                    'province.name as province_name'
                )
                ->get();
            
            $mapped = [];
            foreach ($data as $loc) {
                $fullName = $loc->ward_name . ', ' . $loc->district_name . ', ' . $loc->province_name;
                $mapped[] = [
                    'id' => $loc->wardid,
                    'text' => $fullName,
                    'ascii' => strtolower(to_ascii($fullName)),
                    'ward_id' => $loc->wardid,
                    'ward_name' => $loc->ward_name,
                    'district_id' => $loc->districtid,
                    'district_name' => $loc->district_name,
                    'province_id' => $loc->provinceid,
                    'province_name' => $loc->province_name
                ];
            }
            return $mapped;
        });

        $results = [];
        $count = 0;
        foreach ($locations as $loc) {
            $match = true;
            foreach ($tokens as $token) {
                if (strpos($loc['ascii'], $token) === false) {
                    $match = false;
                    break;
                }
            }
            
            if ($match) {
                $results[] = [
                    'id' => $loc['id'],
                    'text' => $loc['text'],
                    'ward_id' => $loc['ward_id'],
                    'ward_name' => $loc['ward_name'],
                    'district_id' => $loc['district_id'],
                    'district_name' => $loc['district_name'],
                    'province_id' => $loc['province_id'],
                    'province_name' => $loc['province_name']
                ];
                $count++;
            }
            if ($count >= 20) break;
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Auto create export receipt when order is created
     * @param int $orderId
     * @param string $orderCode
     * @return bool
     */
    private function createExportReceiptFromOrder($orderId, $orderCode): bool
    {
        // Check if export receipt already exists for this order
        $existingReceipt = Warehouse::where('content', 'like', '%Xuất hàng cho đơn hàng ' . $orderCode . '%')
            ->orWhere('content', 'like', '%Đơn hàng ' . $orderCode . '%')
            ->where('type', 'export')
            ->first();
        
        if ($existingReceipt) {
            Log::info("Export receipt already exists for order: " . $orderCode);
            return false;
        }
        
        // Get order details
        $orderDetails = OrderDetail::where('order_id', $orderId)->get();
        
        if ($orderDetails->count() == 0) {
            Log::warning("No order details found for order: " . $orderCode);
            return false;
        }
        
        // Check stock availability for all items
        $hasStock = true;
        $stockErrors = [];
        foreach ($orderDetails as $detail) {
            if ($detail->variant_id) {
                $import = countProduct($detail->variant_id, 'import');
                $export = countProduct($detail->variant_id, 'export');
                $availableStock = $import - $export;
                
                if ($availableStock < $detail->qty) {
                    $hasStock = false;
                    $stockErrors[] = "Sản phẩm ID {$detail->variant_id}: Cần {$detail->qty}, chỉ có {$availableStock}";
                }
            }
        }
        
        // If stock is insufficient, log warning but still create export receipt
        // (Admin can handle stock issues manually)
        if (!$hasStock) {
            Log::warning("Insufficient stock for order {$orderCode}: " . implode(', ', $stockErrors));
        }
        
        // Generate unique code for export receipt
        $exportCode = 'PX-' . $orderCode . '-' . time();
        
        // Check if code already exists
        while (Warehouse::where('code', $exportCode)->exists()) {
            $exportCode = 'PX-' . $orderCode . '-' . time() . '-' . rand(1000, 9999);
        }
        
        // Create warehouse entry
        $warehouseId = Warehouse::insertGetId([
            'code' => $exportCode,
            'subject' => 'Đơn hàng ' . $orderCode,
            'content' => 'Xuất hàng cho đơn hàng ' . $orderCode,
            'type' => 'export',
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => Auth::guard('member')->id() ?? 1, // Use member ID or default to 1
        ]);
        
        if ($warehouseId > 0) {
            // Create ProductWarehouse entries
            foreach ($orderDetails as $detail) {
                if ($detail->variant_id) {
                    // Only export if stock is available
                    $import = countProduct($detail->variant_id, 'import');
                    $export = countProduct($detail->variant_id, 'export');
                    $availableStock = $import - $export;
                    
                    // Export only available stock (or full qty if enough stock)
                    $exportQty = min($detail->qty, $availableStock);
                    
                    if ($exportQty > 0) {
                        ProductWarehouse::insert([
                            'variant_id' => $detail->variant_id,
                            'price' => $detail->price ?? 0,
                            'qty' => $exportQty,
                            'type' => 'export',
                            'warehouse_id' => $warehouseId,
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                        
                        // Log if partial export
                        if ($exportQty < $detail->qty) {
                            Log::warning("Partial export for variant {$detail->variant_id}: Requested {$detail->qty}, exported {$exportQty}");
                        }
                    } else {
                        Log::warning("Cannot export variant {$detail->variant_id}: No stock available");
                    }
                }
            }
            
            Log::info("Auto created export receipt: " . $exportCode . " for order: " . $orderCode);
            return true;
        }
        
        return false;
    }
}
