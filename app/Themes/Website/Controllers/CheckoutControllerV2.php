<?php

declare(strict_types=1);
namespace App\Themes\Website\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use App\Modules\Promotion\Models\Promotion;
use App\Modules\Address\Models\Address;
use App\Modules\Location\Models\Province;
use App\Modules\Pick\Models\Pick;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class CheckoutControllerV2 extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Parse location ID from string format (e.g., "01TTT" -> 1) or return integer as is
     * 
     * @param mixed $id
     * @return int|null
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
                return (int)$matches[1];
            }
            
            // Try direct conversion
            if (is_numeric($id)) {
                return (int)$id;
            }
        }
        
        return null;
    }

    public function index()
    {
        try {
            $userId = auth('member')->id();
            Log::info('[CheckoutV2] index called', [
                'user_id' => $userId,
                'session_id' => Session::getId(),
                'has_cart' => Session::has('cart'),
            ]);

            if (!Session::has('cart')) {
                Log::warning('[CheckoutV2] index empty cart');
                return redirect()->route('cart.v2.index')->with('error', 'Giỏ hàng của bạn đã trống');
            }

            $cartData = $this->cartService->getCart($userId);

            if (empty($cartData['items'])) {
                Log::warning('[CheckoutV2] index cart has no items');
                return redirect()->route('cart.v2.index')->with('error', 'Giỏ hàng của bạn đã trống');
            }

            $member = auth()->guard('member')->user();
            $address = null;
            if ($member) {
                $address = Session::has('ss_address')
                    ? Address::where([['member_id', $member['id']], ['id', Session::get('ss_address')]])->first()
                    : Address::where([['member_id', $member['id']], ['is_default', '1']])->first();
            }

            $provinces = Province::orderBy('name', 'asc')->get();
            $promotions = Promotion::where([
                ['status', '1'],
                ['end', '>=', date('Y-m-d')],
                ['order_sale', '<=', $cartData['summary']['subtotal'] ?? 0]
            ])->limit(8)->get();

            $token = md5(Session::getId() . 'checkout_secure_v2');
            
            $shippingFee = 0;
            if ($address && $address->provinceid && $address->districtid && $address->wardid) {
                try {
                    Log::info('[CheckoutV2] index calculating initial shipping fee', [
                        'province_id' => $address->provinceid,
                        'district_id' => $address->districtid,
                        'ward_id' => $address->wardid,
                    ]);
                    $shippingFee = $this->cartService->calculateShippingFee([
                        'province_id' => $address->provinceid,
                        'district_id' => $address->districtid,
                        'ward_id' => $address->wardid,
                        'address' => $address->address ?? '',
                    ], $userId);
                    
                    $subtotal = $cartData['summary']['subtotal'] ?? 0;
                    $discount = $cartData['summary']['discount'] ?? 0;
                    $cartData['summary']['shipping_fee'] = $shippingFee;
                    $cartData['summary']['total'] = $subtotal - $discount + $shippingFee;
                    
                    Log::info('[CheckoutV2] index initial shipping fee calculated', [
                        'shipping_fee' => $shippingFee,
                        'subtotal' => $subtotal,
                        'discount' => $discount,
                        'total' => $cartData['summary']['total'],
                    ]);
                } catch (\Exception $e) {
                    Log::error('[CheckoutV2] Calculate shipping fee on load failed', [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                }
            }

            Log::info('[CheckoutV2] index success', [
                'items_count' => count($cartData['items'] ?? []),
                'has_address' => $address !== null,
                'promotions_count' => $promotions->count(),
            ]);

            return view('Website::cart.v2.checkout', [
                'cart' => $cartData,
                'address' => $address,
                'provinces' => $provinces,
                'promotions' => $promotions,
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            Log::error('[CheckoutV2] Index failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('cart.v2.index')->with('error', 'Không thể tải trang thanh toán');
        }
    }

    public function applyCoupon(Request $request)
    {
        try {
            $userId = auth('member')->id();
            Log::info('[CheckoutV2] applyCoupon called', [
                'code' => $request->code,
                'user_id' => $userId,
                'session_id' => Session::getId(),
            ]);

            $validator = Validator::make($request->all(), [
                'code' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::warning('[CheckoutV2] applyCoupon validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'code' => $request->code,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mã giảm giá không hợp lệ',
                ], 400);
            }

            if (Session::has('ss_counpon')) {
                Log::warning('[CheckoutV2] applyCoupon already has coupon', [
                    'existing_coupon' => Session::get('ss_counpon'),
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mã giảm giá không được dùng chung với mã khác',
                ], 400);
            }

            $result = $this->cartService->applyCoupon($request->code, $userId);

            session()->save();

            Log::info('[CheckoutV2] applyCoupon success', [
                'code' => $request->code,
                'discount' => $result['coupon']['discount'] ?? 0,
            ]);

            if ($request->has('legacy_format')) {
                $cart = $this->cartService->getCart($userId);
                $discount = $cart['summary']['discount'] ?? 0;
                $subtotal = $cart['summary']['subtotal'] ?? 0;
                $feeship = (float)($request->feeship ?? 0);
                
                return response()->json([
                    'status' => 'success',
                    'sale' => number_format($discount) . 'đ',
                    'total' => number_format($subtotal - $discount + $feeship) . 'đ',
                    'id' => Session::get('ss_counpon')['id'] ?? null,
                    'code' => $request->code,
                    'message' => 'Áp dụng mã thành công',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Áp dụng mã thành công',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('[CheckoutV2] Apply coupon failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $request->code ?? null,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?: 'Áp dụng mã thất bại',
            ], 400);
        }
    }

    public function removeCoupon(Request $request = null)
    {
        try {
            $userId = auth('member')->id();
            Log::info('[CheckoutV2] removeCoupon called', [
                'user_id' => $userId,
                'session_id' => Session::getId(),
                'has_coupon' => Session::has('ss_counpon'),
            ]);

            $result = $this->cartService->removeCoupon($userId);

            session()->save();

            Log::info('[CheckoutV2] removeCoupon success', [
                'discount_removed' => Session::get('ss_counpon')['discount'] ?? 0,
            ]);

            if ($request && $request->has('legacy_format')) {
                $cart = $this->cartService->getCart($userId);
                $subtotal = $cart['summary']['subtotal'] ?? 0;
                $feeship = (float)($request->feeship ?? 0);
                
                return response()->json([
                    'status' => 'success',
                    'total' => number_format($subtotal + $feeship) . 'đ',
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('[CheckoutV2] Remove coupon failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Hủy mã thất bại',
            ], 400);
        }
    }

    public function calculateShippingFee(Request $request)
    {
        try {
            Log::info('[CheckoutV2] calculateShippingFee called', [
                'request_data' => $request->all(),
                'user_id' => auth('member')->id(),
                'session_id' => Session::getId(),
            ]);

            $validator = Validator::make($request->all(), [
                'province_id' => 'nullable',
                'province' => 'nullable|string',
                'district_id' => 'nullable',
                'district' => 'nullable|string',
                'ward_id' => 'nullable',
                'ward' => 'nullable|string',
                'address' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::warning('[CheckoutV2] calculateShippingFee validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all(),
                ]);
                return response()->json([
                    'success' => false,
                    'status' => 'false',
                    'message' => 'Dữ liệu địa chỉ không hợp lệ',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $userId = auth('member')->id();
            
            // Parse to integer, handle both string format (e.g., "01TTT") and integer input
            $provinceIdRaw = $request->province_id ?? $request->province;
            $districtIdRaw = $request->district_id ?? $request->district;
            $wardIdRaw = $request->ward_id ?? $request->ward;
            
            $provinceId = $this->parseLocationId($provinceIdRaw);
            $districtId = $this->parseLocationId($districtIdRaw);
            $wardId = $this->parseLocationId($wardIdRaw);
            
            Log::info('[CheckoutV2] calculateShippingFee address parsed', [
                'province_id' => $provinceId,
                'district_id' => $districtId,
                'ward_id' => $wardId,
                'address' => $request->address ?? '',
            ]);
            
            if (!$provinceId || !$districtId || !$wardId) {
                Log::warning('[CheckoutV2] calculateShippingFee missing address info', [
                    'province_id' => $provinceId,
                    'district_id' => $districtId,
                    'ward_id' => $wardId,
                ]);
                return response()->json([
                    'success' => false,
                    'status' => 'false',
                    'message' => 'Thiếu thông tin địa chỉ',
                ], 400);
            }

            Log::info('[CheckoutV2] calculateShippingFee calling CartService', [
                'address_params' => [
                    'province_id' => $provinceId,
                    'district_id' => $districtId,
                    'ward_id' => $wardId,
                    'address' => $request->address ?? '',
                ],
                'user_id' => $userId,
            ]);

            try {
                $shippingFee = $this->cartService->calculateShippingFee([
                    'province_id' => $provinceId,
                    'district_id' => $districtId,
                    'ward_id' => $wardId,
                    'address' => $request->address ?? '',
                ], $userId);

                Log::info('[CheckoutV2] calculateShippingFee result from CartService', [
                    'shipping_fee' => $shippingFee,
                    'shipping_fee_type' => gettype($shippingFee),
                    'province_id' => $provinceId,
                    'district_id' => $districtId,
                    'ward_id' => $wardId,
                ]);
            } catch (\Exception $e) {
                Log::error('[CheckoutV2] calculateShippingFee CartService exception', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            try {
                $cart = $this->cartService->getCart($userId);
                Log::info('[CheckoutV2] calculateShippingFee getCart success', [
                    'cart_keys' => array_keys($cart ?? []),
                    'has_summary' => isset($cart['summary']),
                ]);
            } catch (\Exception $e) {
                Log::error('[CheckoutV2] calculateShippingFee getCart exception', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                throw $e;
            }

            if (!is_array($cart) || !isset($cart['summary'])) {
                Log::warning('[CheckoutV2] calculateShippingFee cart structure invalid', [
                    'cart_type' => gettype($cart),
                    'cart_is_array' => is_array($cart),
                    'cart_keys' => is_array($cart) ? array_keys($cart) : null,
                ]);
                $cart = ['summary' => ['subtotal' => 0, 'discount' => 0]];
            }

            $discount = $cart['summary']['discount'] ?? 0;
            $subtotal = $cart['summary']['subtotal'] ?? 0;
            
            Log::info('[CheckoutV2] calculateShippingFee cart summary extracted', [
                'discount' => $discount,
                'subtotal' => $subtotal,
            ]);

            // Ensure shippingFee is numeric
            $shippingFee = is_numeric($shippingFee) ? (float)$shippingFee : 0.0;
            $discount = is_numeric($discount) ? (float)$discount : 0.0;
            $subtotal = is_numeric($subtotal) ? (float)$subtotal : 0.0;

            $summary = [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_fee' => $shippingFee,
                'total' => (float)($subtotal - $discount + $shippingFee),
            ];

            Log::info('[CheckoutV2] calculateShippingFee summary', [
                'summary' => $summary,
            ]);

            if ($request->has('legacy_format')) {
                return response()->json([
                    'status' => 'true',
                    'feeship' => number_format($shippingFee),
                    'amount' => number_format($subtotal - $discount + $shippingFee),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'shipping_fee' => (float)$shippingFee,
                    'free_ship' => $shippingFee == 0,
                    'summary' => $summary,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[CheckoutV2] Calculate shipping fee failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'status' => 'false',
                'message' => 'Tính phí vận chuyển thất bại',
            ], 500);
        }
    }

    public function checkout(Request $request)
    {
        try {
            $userId = auth('member')->id();
            Log::info('[CheckoutV2] checkout called', [
                'user_id' => $userId,
                'session_id' => Session::getId(),
                'has_cart' => Session::has('cart'),
                'request_data' => $request->except(['token']),
            ]);

            $token = md5(Session::getId() . 'checkout_secure_v2');
            if ($request->token !== $token) {
                Log::warning('[CheckoutV2] checkout invalid token', [
                    'expected_token' => $token,
                    'received_token' => $request->token,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Phiên giao dịch không hợp lệ',
                ], 403);
            }

            // Parse location IDs from string format (e.g., "01TTT" -> 1) before validation
            $provinceIdRaw = $request->province_id;
            $districtIdRaw = $request->district_id;
            $wardIdRaw = $request->ward_id;
            
            $provinceId = $this->parseLocationId($provinceIdRaw);
            $districtId = $this->parseLocationId($districtIdRaw);
            $wardId = $this->parseLocationId($wardIdRaw);
            
            // Merge parsed values back to request for validation
            $request->merge([
                'province_id' => $provinceId,
                'district_id' => $districtId,
                'ward_id' => $wardId,
            ]);
            
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'required|string',
                'province_id' => 'required|integer|min:1',
                'district_id' => 'required|integer|min:1',
                'ward_id' => 'required|integer|min:1',
                'remark' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::warning('[CheckoutV2] checkout validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng điền đầy đủ thông tin giao hàng',
                    'errors' => $validator->errors(),
                ], 400);
            }

            if (!Session::has('cart')) {
                Log::warning('[CheckoutV2] checkout empty cart');
                return response()->json([
                    'success' => false,
                    'message' => 'Giỏ hàng trống',
                ], 400);
            }

            $checkoutData = [
                'full_name' => $request->full_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'province_id' => $request->province_id,
                'district_id' => $request->district_id,
                'ward_id' => $request->ward_id,
                'remark' => $request->remark,
                'shipping_fee' => $request->shipping_fee ?? 0,
            ];

            Log::info('[CheckoutV2] checkout processing', [
                'checkout_data' => $checkoutData,
            ]);

            $result = $this->cartService->checkout($checkoutData, $userId);

            Log::info('[CheckoutV2] checkout success', [
                'order_id' => $result['order_id'] ?? null,
                'order_code' => $result['order_code'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            Log::error('[CheckoutV2] Checkout failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Đặt hàng thất bại',
            ], 400);
        }
    }

    public function result(Request $request)
    {
        try {
            $code = $request->code;
            if (!$code) {
                return redirect('/');
            }

            $order = \App\Modules\Order\Models\Order::where('code', $code)->first();
            if (!$order) {
                return redirect('/');
            }

            $products = \App\Modules\Order\Models\OrderDetail::where('order_id', $order->id)->get();

            return view('Website::cart.v2.result', [
                'order' => $order,
                'products' => $products,
            ]);
        } catch (\Exception $e) {
            Log::error('[CheckoutV2] Result failed: ' . $e->getMessage());
            return redirect('/');
        }
    }

    public function searchLocation(Request $request)
    {
        try {
            $keyword = $request->q;
            if (!$keyword) {
                return response()->json(['results' => []]);
            }

            $keywordAscii = strtolower(to_ascii($keyword));
            $tokens = explode(' ', $keywordAscii);
            $tokens = array_filter($tokens);

            $locations = \Illuminate\Support\Facades\Cache::remember('locations_all_v1', 60 * 24, function () {
                $data = \App\Modules\Location\Models\Ward::join('district', 'ward.districtid', '=', 'district.districtid')
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
        } catch (\Exception $e) {
            Log::error('[CheckoutV2] Search location failed: ' . $e->getMessage());
            return response()->json(['results' => []]);
        }
    }

    public function loadPromotion()
    {
        try {
            if (!Session::has('cart')) {
                return 'Không có dữ liệu';
            }

            $userId = auth('member')->id();
            $cart = $this->cartService->getCart($userId);
            $subtotal = $cart['summary']['subtotal'] ?? 0;

            $promotions = Promotion::where([
                ['status', '1'],
                ['end', '>=', date('Y-m-d')],
                ['order_sale', '<=', $subtotal]
            ])->limit(10)->get();

            return view('Website::cart.promotion', [
                'list' => $promotions,
            ]);
        } catch (\Exception $e) {
            Log::error('[CheckoutV2] Load promotion failed: ' . $e->getMessage());
            return 'Không có dữ liệu';
        }
    }
}

