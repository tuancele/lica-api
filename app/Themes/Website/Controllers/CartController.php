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
use GuzzleHttp\Client;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\SaleDeal;
use App\Themes\Website\Models\Facebook;

class CartController extends Controller
{
    use Location, Sendmail;

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

        if (Session::has('cart')) {
            $oldCart = Session::get('cart');
            $cart = new Cart($oldCart);
            $data['products'] = $cart->items;
            $data['totalPrice'] = $cart->totalPrice;

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
        if (!Session::has('cart')) {
            return redirect('cart/gio-hang');
        }

        // Security Token
        $token = md5(Session::getId() . 'checkout_secure');
        if (!$request->has('token') || $request->token !== $token) {
            return redirect()->route('cart.payment', ['token' => $token]);
        }

        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        $data['products'] = $cart->items;
        $data['cart'] = $cart;
        $data['totalPrice'] = $cart->totalPrice;
        $data['province'] = $this->getProvince();
        $data['promotions'] = Promotion::where([['status', '1'], ['end', '>=', date('Y-m-d')], ['order_sale', '<=', $cart->totalPrice]])->limit('8')->get();
        
        $sale = 0;
        if (Session::has('ss_counpon')) {
            $sale = Session::get('ss_counpon')['sale'];
            $data['code'] = Session::get('ss_counpon')['code'];
        }
        $data['sale'] = $sale;

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
                        $weight += ($variant['item']['weight'] * $variant['qty']);
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
        }
        $data['feeship'] = $feeShip;
        $data['token'] = $token;
        return view('Website::cart.checkout', $data);
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
            $sale = 0;
            $promotion = 0;
            $oldCart = Session::get('cart');
            $cart = new Cart($oldCart);

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
                        $weight += ($variant['item']['weight'] * $variant['qty']);
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
                        foreach ($cart->items as $variant) {
                            $product = Product::find($variant['item']['product_id']);
                            $product_name = $product->name;
                            if (isset($variant['is_deal']) && $variant['is_deal'] == 1) {
                                $product_name = '[DEAL SỐC] ' . $product_name;
                            }
                            OrderDetail::insert([
                                'order_id' => $order_id,
                                'product_id' => $variant['item']['product_id'],
                                'variant_id' => $variant['item']['id'],
                                'name' => $product_name,
                                'color_id' => $variant['item']->color_id,
                                'size_id' => $variant['item']->size_id,
                                'price' => $variant['price'],
                                'qty' => $variant['qty'],
                                'image' => $product->image,
                                'weight' => $variant['item']->weight * $variant['qty'],
                                'subtotal' => $variant['price'] * $variant['qty'],
                                'created_at' => date('Y-m-d H:i:s')
                            ]);

                    // Update FlashSale Stock
                    $date = strtotime(date('Y-m-d H:i:s'));
                    $flash = FlashSale::where([['status', '1'], ['start', '<=', $date], ['end', '>=', $date]])->first();
                    if ($flash) {
                        $pro = ProductSale::where([['flashsale_id', $flash->id], ['product_id', $variant['item']['product_id']]])->first();
                        if ($pro) {
                            $pro->increment('buy', $variant['qty']);
                        }
                    }

                    // Facebook Tracking
                    $dataf = [
                        'email' => $req->email,
                        'phone' => $req->phone,
                        'product_id' => $variant['item']['product_id'],
                        'price' => $variant['price'],
                        'url' => getSlug($product->slug),
                        'event' => 'Purchase',
                    ];
                    Facebook::track($dataf);
                }

                $this->send('Website::email.order', 'Đơn đặt hàng Walcos', getConfig('reply_email'), $code);
                Session::forget('cart');
                Session::forget('ss_counpon');
                
                return response()->json([
                    'status' => 'success',
                    'url' => '/cart/dat-hang-thanh-cong?code=' . $code
                ]);
            }
            
            return response()->json(['status' => 'error', 'message' => 'Lỗi tạo đơn hàng.']);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
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
                    $variant = Variant::find($item['id']);
                    if ($variant) {
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
                        $cart->add($variant, $variant->id, $item['qty'], $is_deal);
                    }
                }
                Session::put('cart', $cart);
                return response()->json([
                    'status' => 'success',
                    'total' => $cart->totalQty
                ]);
            }

            // Thêm 1 sản phẩm như cũ
            $variant = Variant::find($req->id);
            if ($variant) {
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

                $cart->add($variant, $variant->id, $req->qty, $is_deal);
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
                    $weight += ($variant['item']['weight'] * $variant['qty']);
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
                    $weight += ($variant['item']['weight'] * $variant['qty']);
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
}
