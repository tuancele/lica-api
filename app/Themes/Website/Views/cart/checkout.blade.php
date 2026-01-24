@extends('Website::layout')
@section('title','Thanh toán')
@section('description','Thanh toán')
@section('content')
@php
// TEMPORARY DEBUG - XÓA SAU KHI FIX
echo "<!-- DEBUG VARIABLES:\n";
echo "totalPrice: " . (isset($totalPrice) ? $totalPrice : 'NOT SET') . "\n";
echo "sale: " . (isset($sale) ? $sale : 'NOT SET') . "\n";
echo "feeship: " . (isset($feeship) ? $feeship : 'NOT SET') . "\n";
echo "code: " . (isset($code) ? $code : 'NOT SET') . "\n";
echo "-->\n";
$member = auth()->guard('member')->user();
@endphp
<link href="/public/website/select2/select2.min.css" rel="stylesheet" />
<script src="/public/website/select2/select2.min.js"></script>
<section class="mt-3 mb-5" id="page_checkout">
    <div class="container-lg">
        <div class="breadcrumb">
            <ol>
                <li><a href="/">Trang chủ</a></li>
                <li><a href="{{route('cart.payment')}}">Thanh toán</a></li>
            </ol>
        </div>
        <h1 class="fs-24 fw-bold">Thông tin thanh toán</h1>
        <form id="checkoutForm" method="post" class="checkout mt-2" action="">
        @csrf
        <input type="hidden" name="token" value="{{$token}}">
        <div class="row mt-3">
            <div class="col-12 col-md-8" style="background-color: #fff; padding: 20px; border-radius: 8px;">
                <div class="align-center space-between mb-2 mt-3">
                    <span class="fs-18 fw-bold">Thông tin người mua hàng</span>
                    @if(!isset($member) && empty($member))
                    <button class="link" type="button" data-bs-toggle="modal" data-bs-target="#myLogin">Đăng nhập nhanh</button>
                    @endif
                </div>
                @if(isset($member) && !empty($member))
                <p>Bạn đã đăng nhập với tài khoản <a class="text-underline" href="/account/profile">{{$member['email']}}</a>. <a href="{{route('account.logout')}}">Đăng xuất</a></p>
                @php   
                    if(Session::has('ss_address')){
                        $address = App\Modules\Address\Models\Address::where([['member_id',$member['id']],['id',Session::get('ss_address')]])->first();    
                    }else{
                        $address = App\Modules\Address\Models\Address::where([['member_id',$member['id']],['is_default','1']])->first();
                    }
                @endphp
                @if(isset($address) && !empty($address))
                <div class="box_address">
                    <div class="item-address">
                        <p><strong>{{$address->last_name}} | {{$address->phone}} | {{$address->email}}</strong></p>
                        <p>{{$address->address}}@if($address->ward), {{$address->ward->name}}@endif @if($address->district), {{$address->district->name}}@endif @if($address->province), {{$address->province->name}}@endif</p>
                        <input type="hidden" name="full_name" value="{{$address->first_name}} {{$address->last_name}}">
                        <input type="hidden" name="phone" value="{{$address->phone}}">
                        <input type="hidden" name="email" value="{{$address->email}}">
                        <input type="hidden" name="province" value="{{$address->provinceid}}">
                        <input type="hidden" name="district" value="{{$address->districtid}}">
                        <input type="hidden" name="ward" value="{{$address->wardid}}">
                        <input type="hidden" name="address" value="{{$address->address}}">
                    </div>
                    <a href="javascript:;" class="btn_change_address" data-bs-toggle="modal" data-bs-target="#changeAddress">Thay đổi</a>
                </div>
                @else
                    <div class="mb-2">
                        <label>Tên người mua <span>*</span></label>
                        <input class="form-control" type="text" name="full_name" placeholder="">
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">
                            <label>Số điện thoại <span>*</span></label>
                            <input class="form-control" type="text" name="phone" placeholder="">
                        </div>
                        <div class="col-6">
                            <label>Email</label>
                            <input class="form-control" type="email" name="email" placeholder="">
                        </div>
                    </div>
                    <div class="align-center space-between mb-2 mt-3">
                        <span class="fs-18 fw-bold">Thông tin nhận hàng</span>
                    </div>
                    <div class="mb-2 position-relative">
                        <label>Địa chỉ: <span>*</span></label>
                        <input type="text" class="form-control" id="search_location_input" autocomplete="off" placeholder="Nhập Xã, Huyện, Tỉnh để gợi ý địa chỉ">
                        <div id="search_location_results" class="autocomplete-results"></div>
                        <input type="hidden" name="province" id="province_id">
                        <input type="hidden" name="district" id="district_id">
                        <input type="hidden" name="ward" id="ward_id">
                        <input type="hidden" id="province_name">
                        <input type="hidden" id="district_name">
                        <input type="hidden" id="ward_name">
                    </div>
                    <div class="mb-2">
                        <label>Chi tiết địa chỉ</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                @endif
                @else
                <div class="mb-2">
                    <label>Tên người mua <span>*</span></label>
                    <input class="form-control" type="text" name="full_name" placeholder="">
                </div>
                <div class="row mb-2">
                    <div class="col-6">
                        <label>Số điện thoại <span>*</span></label>
                        <input class="form-control" type="text" name="phone" placeholder="">
                    </div>
                    <div class="col-6">
                        <label>Email </label>
                        <input class="form-control" type="email" name="email" placeholder="">
                    </div>
                </div>
                <div class="align-center space-between mb-2 mt-3">
                    <span class="fs-18 fw-bold">Thông tin nhận hàng</span>
                </div>
                    <div class="mb-2 position-relative">
                        <label>Địa chỉ: <span>*</span></label>
                        <input type="text" class="form-control" id="search_location_input" autocomplete="off" placeholder="Nhập Xã, Huyện, Tỉnh để gợi ý địa chỉ">
                        <div id="search_location_results" class="autocomplete-results"></div>
                        <input type="hidden" name="province" id="province_id">
                        <input type="hidden" name="district" id="district_id">
                        <input type="hidden" name="ward" id="ward_id">
                        <input type="hidden" id="province_name">
                        <input type="hidden" id="district_name">
                        <input type="hidden" id="ward_name">
                    </div>
                <div class="mb-2">
                    <label>Chi tiết địa chỉ</label>
                    <input type="text" name="address" class="form-control">
                </div>        
                @endif
                <div class="mb-2">
                    <label>Ghi chú</label>
                    <textarea name="remark" class="form-control height-auto" placeholder="" rows="3"></textarea>
                </div>
                <div class="align-center space-between mb-2 mt-3">
                    <span class="fs-18 fw-bold">Phương thức thanh toán</span>
                </div>
                <div class="list-payment">
                    <div class="item-radio align-center mt-3">
                        <label class="align-center space-between mb-0">
                            <input type="radio" name="method-payment" value="1" checked="">
                            <span class="color-none ms-2">Trả tiền mặt khi nhận hàng (COD)</span>
                        </label>
                    </div>
                </div>
                <div class="align-center space-between mb-2 mt-3">
                    <span class="fs-18 fw-bold">Phương thức vận chuyển</span>
                </div>
                <div class="list-payment">
                    <div class="item-radio align-center space-between mt-0">
                        <label class="align-center mb-0">
                            <input type="radio" name="item-ship" value="1" checked="">
                            <span class="color-none ms-2">Giao hàng tiêu chuẩn (3 - 6 ngày) (Giao giờ hành chính)</span>
                        </label>
                        <span class="item-ship">{{number_format($feeship)}}đ</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mt-3 mt-md-0">
                <div class="cart-sidebar">
                    <div class="coupon-voucher mb-3">
                        <div class="mb-2">Coupon & Voucher</div>
                        <div class="form-group">
                            <input type="text" name="coupon" class="form-control" placeholder="NHẬP MÃ GIẢM GIÁ (NẾU CÓ)">
                            <button class="btn_coupon" type="button">Áp dụng</button>
                        </div>
                        <div class="box-alert-promotion"></div>
                    </div>
                    <span class="fw-bold fs-16">Đơn hàng</span>
                    <div class="list-cart">
                        @if(Session::has('cart'))
                        @foreach($products as $variant)
                        @php $product = App\Modules\Product\Models\Product::find($variant['item']['product_id']);@endphp
                        @if(isset($product) && !empty($product))
                        <div class="item-cart d-flex mt-3 mb-3 item-cart-{{$variant['item']['id']}}">
                            <div class="img-thumb">
                                <div class="skeleton--img-sm js-skeleton">
                                    @php
                                        // Get variant image if available, otherwise use product image
                                        $variantImage = null;
                                        if (isset($variant['item'])) {
                                            // Handle both object and array (after session serialization)
                                            if (is_object($variant['item'])) {
                                                $variantImage = $variant['item']->image ?? null;
                                            } elseif (is_array($variant['item'])) {
                                                $variantImage = $variant['item']['image'] ?? null;
                                            }
                                        }
                                        // Use variant image if exists and not empty, otherwise use product image
                                        $displayImage = !empty($variantImage) ? $variantImage : $product->image;
                                    @endphp
                                    <img src="{{getImage($displayImage)}}" width="60" height="60" alt="{{$product->name}}" class="js-skeleton-img">
                                </div>
                            </div>
                            <div class="des-cart ms-2" data-product-id="{{$product->id}}" data-variant-id="{{$variant['item']['id']}}">
                                <div class="header-cart d-flex space-between">
                                    <div>
                                        <a class="product-name fw-600 fs-12 d-block" href="{{getSlug($product->slug)}}">
                                            {{$product->name}}
                                            @if(isset($variant['is_deal']) && $variant['is_deal'] == 1)
                                                <span class="badge bg-danger ms-2">Deal sốc</span>
                                            @endif
                                        </a>
                                        <div class="fs-12 d-block">@if($variant['item']->color)<span class="me-3">Màu sắc: {{$variant['item']->color->name}}</span>@endif @if($variant['item']->size)<span>Kích thước: {{$variant['item']->size->name}}{{$variant['item']->size->unit}}</span>@endif</div>
                                    </div>
                                    <div class="action-del ms-2">
                                        <button class="remove-cart" type="button" data-id="{{$variant['item']['id']}}"><span role="img" aria-label="minus" class="icon"><svg viewBox="64 64 896 896" focusable="false" data-icon="minus" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M872 474H152c-4.4 0-8 3.6-8 8v60c0 4.4 3.6 8 8 8h720c4.4 0 8-3.6 8-8v-60c0-4.4-3.6-8-8-8z"></path></svg></span></button>
                                    </div>
                                </div>
                                <div class="d-flex space-between mt-1">
                                    <div class="quantity align-center">
                                        @if(isset($variant['is_deal']) && $variant['is_deal'] == 1)
                                            <span class="fs-12">Số lượng: <strong>{{$variant['qty']}}</strong></span>
                                        @else
                                            <button class="btn_minus qtyminus" type="button" data-id="{{$variant['item']['id']}}">
                                                <span role="img" class="icon"><svg width="14" height="2" viewBox="0 0 14 2" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 0C0.447715 0 0 0.447715 0 1C0 1.55228 0.447715 2 1 2L1 0ZM13 2C13.5523 2 14 1.55228 14 1C14 0.447715 13.5523 0 13 0V2ZM1 2L13 2V0L1 0L1 2Z" fill="black"></path></svg></span>
                                            </button>
                                            <input type="text" name="" min="0" id="quantity-{{$variant['item']['id']}}" class="form-quatity" value="{{$variant['qty']}}">
                                            <button class="btn_plus qtyplus" type="button" data-id="{{$variant['item']['id']}}">
                                                <span role="img" class="icon"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 6C0.447715 6 0 6.44772 0 7C0 7.55228 0.447715 8 1 8L1 6ZM13 8C13.5523 8 14 7.55228 14 7C14 6.44772 13.5523 6 13 6V8ZM1 8L13 8V6L1 6L1 8Z" fill="black"></path><path d="M6 13C6 13.5523 6.44772 14 7 14C7.55228 14 8 13.5523 8 13L6 13ZM8 1C8 0.447715 7.55228 -2.41411e-08 7 0C6.44771 2.41411e-08 6 0.447715 6 1L8 1ZM8 13L8 1L6 1L6 13L8 13Z" fill="black"></path></svg></span>
                                            </button>
                                        @endif
                                    </div>
                                    <div class="price">
                                        @php
                                            $variantId = $variant['item']['id'];
                                            $priceData = $productsWithPrice[$variantId] ?? null;
                                            $hasBreakdown = $priceData && isset($priceData['price_breakdown']) && count($priceData['price_breakdown']) > 1;
                                            // Dùng LUÔN total_price từ productsWithPrice (kể cả nó là 0đ cho Deal Sốc)
                                            $itemTotalPrice = $priceData['total_price'] ?? 0;
                                        @endphp
                                        <span class="fw-600 price-item-{{$variantId}}">{{number_format($itemTotalPrice)}}đ</span>
                                        @if($hasBreakdown)
                                            <div class="fs-11 text-muted mt-1" style="cursor: pointer;" title="Click để xem chi tiết">
                                                @foreach($priceData['price_breakdown'] as $bd)
                                                    {{$bd['quantity']}}x{{number_format($bd['unit_price'])}}đ
                                                    @if(!$loop->last) + @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <!-- Flash Sale Warning & Stock Error Container -->
                            @php
                                $variantId = $variant['item']['id'];
                                $priceData = $productsWithPrice[$variantId] ?? null;
                                $hasStockError = $priceData && isset($priceData['is_available']) && $priceData['is_available'] === false;
                            @endphp
                            <div class="flash-sale-warning-container-{{$variantId}} checkout-warning-container">
                                @if($hasStockError)
                                    <!-- Hiển thị lỗi tồn kho nếu có -->
                                    <div class="stock-error alert alert-danger checkout-warning">
                                        <i class="fa fa-exclamation-circle"></i>
                                        <strong>{{$priceData['stock_error'] ?? 'Số lượng vượt quá tồn kho'}}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endif
                        @endforeach
                        @endif
                        <div class="divider-horizontal"></div>
                        <!-- Stock Error Summary (sẽ hiển thị nếu có lỗi tồn kho) -->
                        <div class="stock-error-summary checkout-warning-summary" style="display: none;">
                            <i class="fa fa-exclamation-circle"></i>
                            <strong>Có sản phẩm vượt quá tồn kho. Vui lòng điều chỉnh số lượng.</strong>
                        </div>
                        <div class="align-center space-between mb-3 fs-14">
                            <span>Tổng giá trị đơn hàng</span>
                            <span class="subtotal-cart">
                                @if(isset($totalPrice) && $totalPrice > 0)
                                    {{ number_format($totalPrice) }}đ
                                @else
                                    0đ
                                @endif
                            </span>
                        </div>
                        <div class="align-center space-between fs-14 mb-1">
                            <span>Giảm giá</span>
                            <span class="sale-promotion">@if($sale != 0)-{{number_format($sale)}}đ @else 0đ @endif</span>
                        </div>
                        <div class="box-code-coupon">@if(isset($code))<span>{{$code}}</span>@endif</div>
                        <div class="align-center space-between fs-14 mt-2">
                            <span>Phí vận chuyển</span>
                            <span class="item-ship fee_ship">{{number_format($feeship)}}đ</span>
                            <input type="hidden" name="feeShip" value="{{$feeship}}">
                        </div>
                        <div class="divider-horizontal"></div>
                        <div class="align-center space-between fs-14 mb-3">
                            <span class="fw-600">Tổng</span>
                            <span class="fw-600 total-order">
                                @php
                                    $initialTotal = max(0, ($totalPrice ?? 0) - ($sale ?? 0) + ($feeship ?? 0));
                                @endphp
                                {{ number_format($initialTotal) }}đ
                            </span>
                        </div>
                        <button type="submit" id="place_order" class="btn-checkout btn bg-gradient w-100 fw-bold">ĐẶT HÀNG</button>
                    </div>
                </div>
                <div class="list-promotion mt-3">
                </div>
            </div>
        </div>
        </form>
    </div>
</section>
@endsection
@section('footer')
<script>
    // Bước 3: Khóa biến $totalPrice cho Sidebar (Global Data Lock)
    // JavaScript CHỈ ĐƯỢC PHÉP đọc số từ đây, không được tự tính toán lại
    // Con số này phải là tổng từ CartService (kể cả Deal Sốc 0đ)
    window.checkoutData = {
        // Tổng tiền hàng từ Backend (đã tính từ cartSummary.items, kể cả Deal Sốc 0đ)
        // Ví dụ: (398.400 + 0) = 398.400
        subtotal: {{ $totalPrice ?? 0 }}, 
        // Số tiền giảm giá từ Coupon
        sale: {{ $sale ?? 0 }},      
        // Phí ship hiện tại
        feeship: {{ $feeship ?? 0 }},
        // Tổng thanh toán (không bao giờ âm)
        total: {{ max(0, ($totalPrice ?? 0) - ($sale ?? 0) + ($feeship ?? 0)) }}
    };
    
    // Bước 5: Thêm Log Debug
    console.log('[Checkout_Price] subtotal from backend:', window.checkoutData.subtotal);
    console.log('[Checkout_Price] sale from backend:', window.checkoutData.sale);
    console.log('[Checkout_Price] feeship from backend:', window.checkoutData.feeship);
    console.log('[Checkout_Price] total from backend:', window.checkoutData.total);
    console.log('[Checkout_Price] checkoutData:', window.checkoutData);
    
    // CRITICAL: Khởi tạo price breakdowns từ backend khi trang load
    // Đảm bảo giá được render đúng từ PriceEngineService, không bị JavaScript reset về 0
    window.checkoutPriceBreakdowns = window.checkoutPriceBreakdowns || {};
    @if(isset($productsWithPrice) && is_array($productsWithPrice))
        @foreach($productsWithPrice as $variantId => $priceData)
            window.checkoutPriceBreakdowns[{{$variantId}}] = {
                total_price: {{$priceData['total_price'] ?? 0}},
                price_breakdown: @json($priceData['price_breakdown'] ?? []),
                is_available: true
            };
        @endforeach
    @endif
    
    // Bước 5: Thêm Log Debug
    console.log('[Checkout_Price] subtotal from backend: {{ $totalPrice ?? 0 }}');
    console.log('[Checkout_Price] sale from backend: {{ $sale ?? 0 }}');
    console.log('[Checkout_Price] feeship from backend: {{ $feeship ?? 0 }}');
    console.log('[Checkout_Price] total from backend: {{ ($totalPrice ?? 0) - ($sale ?? 0) + ($feeship ?? 0) }}');
    console.log('[Checkout_Price] checkoutData:', window.checkoutData);
    console.log('[Checkout] Price breakdowns initialized from backend:', window.checkoutPriceBreakdowns);
</script>
@if(isset($member) && !empty($member))
<div class="modal" tabindex="-1" id="changeAddress">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body">
        <button  data-bs-dismiss="modal" aria-label="Close" class="btn btnClose" type="button">
            <span class="icon">
                <svg width="1em" height="1em" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.2453 9L17.5302 2.71516C17.8285 2.41741 17.9962 2.01336 17.9966 1.59191C17.997 1.17045 17.8299 0.76611 17.5322 0.467833C17.2344 0.169555 16.8304 0.00177586 16.4089 0.00140366C15.9875 0.00103146 15.5831 0.168097 15.2848 0.465848L9 6.75069L2.71516 0.465848C2.41688 0.167571 2.01233 0 1.5905 0C1.16868 0 0.764125 0.167571 0.465848 0.465848C0.167571 0.764125 0 1.16868 0 1.5905C0 2.01233 0.167571 2.41688 0.465848 2.71516L6.75069 9L0.465848 15.2848C0.167571 15.5831 0 15.9877 0 16.4095C0 16.8313 0.167571 17.2359 0.465848 17.5342C0.764125 17.8324 1.16868 18 1.5905 18C2.01233 18 2.41688 17.8324 2.71516 17.5342L9 11.2493L15.2848 17.5342C15.5831 17.8324 15.9877 18 16.4095 18C16.8313 18 17.2359 17.8324 17.5342 17.5342C17.8324 17.2359 18 16.8313 18 16.4095C18 15.9877 17.8324 15.5831 17.5342 15.2848L11.2453 9Z" fill="currentColor"></path></svg>
            </span>
        </button>
        <div class="box-list-address">
            <h4 class="text-center">Chọn địa chỉ</h4>
            <div class="list_address">
                @php  $addresss = App\Modules\Address\Models\Address::where([['member_id',$member['id']]])->get(); @endphp
                @if($addresss->count() > 0)
                @foreach($addresss as $item)
                <label>
                    <input type="radio" name="choseAddress" value="{{$item->id}}" @if(Session::has('ss_address') && Session::get('ss_address') == $item->id) checked @elseif($item->is_default == 1) checked @endif>
                    <div class="box-address">
                        <p><strong>{{$item->last_name}} | {{$item->phone}} | {{$item->email}}</strong></p>
                        <p>{{$item->address}}@if($item->ward), {{$item->ward->name}}@endif @if($item->district), {{$item->district->name}}@endif @if($item->province), {{$item->province->name}}@endif</p>
                    </div>
                </label>
                @endforeach
                @endif
                <div class="text-end">
                    <button class="btn btn_save_address" type="button">Lưu</button>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif
<div class="modal" tabindex="-1" id="myPromotion">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <button  data-bs-dismiss="modal" aria-label="Close" class="btn btnClose btnClosePromotion" type="button">
            <span class="icon">
                <svg width="1em" height="1em" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.2453 9L17.5302 2.71516C17.8285 2.41741 17.9962 2.01336 17.9966 1.59191C17.997 1.17045 17.8299 0.76611 17.5322 0.467833C17.2344 0.169555 16.8304 0.00177586 16.4089 0.00140366C15.9875 0.00103146 15.5831 0.168097 15.2848 0.465848L9 6.75069L2.71516 0.465848C2.41688 0.167571 2.01233 0 1.5905 0C1.16868 0 0.764125 0.167571 0.465848 0.465848C0.167571 0.764125 0 1.16868 0 1.5905C0 2.01233 0.167571 2.41688 0.465848 2.71516L6.75069 9L0.465848 15.2848C0.167571 15.5831 0 15.9877 0 16.4095C0 16.8313 0.167571 17.2359 0.465848 17.5342C0.764125 17.8324 1.16868 18 1.5905 18C2.01233 18 2.41688 17.8324 2.71516 17.5342L9 11.2493L15.2848 17.5342C15.5831 17.8324 15.9877 18 16.4095 18C16.8313 18 17.2359 17.8324 17.5342 17.5342C17.8324 17.2359 18 16.8313 18 16.4095C18 15.9877 17.8324 15.5831 17.5342 15.2848L11.2453 9Z" fill="currentColor"></path></svg>
            </span>
        </button>
        <div class="modal-body">
            
        </div>
    </div>
  </div>
</div>
<script>
    $('body .select2').select2();
    $('body').on('click','.btn-dieukien',function(){
        var id = $(this).attr('data-id');
        $.ajax({
          type: "post",
          url: "{{route('promotion')}}",
          data: { id: id},
          headers:
          {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (res) {
            $('#myPromotion .modal-body').html(res);
            var myPromotion = new bootstrap.Modal(document.getElementById('myPromotion'))
            myPromotion.show();
          },
          error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
           }
      });
    });
</script>
<script type="text/javascript">
    $.ajax({
        type: 'get',
        url: '{{route("cart.loadPromotion")}}',
        data: {},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (res) {
            $('.list-promotion').html(res);
        }
    })
    $('body').on('click','.btn_save_address',function(){
        var id = $('body input[name="choseAddress"]:checked').val();
        $.ajax({
            type: 'post',
            url: '{{route("cart.choseAddress")}}',
            data: {id:id},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('#changeAddress').modal('hide');
                $('.item-address').html(res.address);
                
                // Bước 3: Sửa lỗi AJAX getFeeShip
                // Chỉ làm 2 việc: Gán giá trị vào input[name="feeShip"] và gọi updateTotalOrderPriceCheckout()
                // CẤM: Không được dùng các lệnh kiểu amount + feeship trực tiếp trong hàm AJAX
                console.log('[AJAX_FEESHIP_SAVE_ADDRESS] Response received:', res);
                console.log('[AJAX_FEESHIP_SAVE_ADDRESS] checkoutPriceBreakdowns BEFORE update:', window.checkoutPriceBreakdowns);
                console.log('[AJAX_FEESHIP_SAVE_ADDRESS] Number of items in breakdowns:', window.checkoutPriceBreakdowns ? Object.keys(window.checkoutPriceBreakdowns).length : 0);
                
                // CRITICAL: Dùng parseFloat thay vì parseInt để tránh mất số thập phân
                const feeShipNum = parseFloat(res.feeship.replace(/[^\d]/g, '')) || 0;
                $('input[name="feeShip"]').val(feeShipNum);
                console.log('[AJAX_FEESHIP_SAVE_ADDRESS] Parsed feeShip:', feeShipNum, '| Type:', typeof feeShipNum);
                
                // Cập nhật hiển thị phí ship
                $('.item-ship').html(res.feeship+'đ');
                $('.fee_ship').html(res.feeship+'đ');
                
                console.log('[AJAX_FEESHIP_SAVE_ADDRESS] checkoutPriceBreakdowns AFTER update:', window.checkoutPriceBreakdowns);
                console.log('[AJAX_FEESHIP_SAVE_ADDRESS] Number of items in breakdowns:', window.checkoutPriceBreakdowns ? Object.keys(window.checkoutPriceBreakdowns).length : 0);
                
                // Gọi hàm tính tổng để cập nhật tổng thanh toán
                updateTotalOrderPriceCheckout();
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
            }
        })
    });
    // Use different variable name to avoid conflict with layout.blade.php
    var checkoutSearchTimeout;
    $('#search_location_input').on('input', function() {
        let query = $(this).val();
        clearTimeout(checkoutSearchTimeout);
        if (query.length < 2) {
            $('#search_location_results').hide();
            return;
        }

        checkoutSearchTimeout = setTimeout(function() {
            $.ajax({
                url: '{{route("cart.searchLocation")}}',
                data: { q: query },
                success: function(data) {
                    let html = '';
                    if (data.results && data.results.length > 0) {
                        data.results.forEach(function(item) {
                            html += `<div class="autocomplete-item" 
                                        data-province-id="${item.province_id}" 
                                        data-district-id="${item.district_id}" 
                                        data-ward-id="${item.ward_id}"
                                        data-province-name="${item.province_name}"
                                        data-district-name="${item.district_name}"
                                        data-ward-name="${item.ward_name}">
                                        ${item.text}
                                    </div>`;
                        });
                        $('#search_location_results').html(html).show();
                    } else {
                        $('#search_location_results').hide();
                    }
                }
            });
        }, 300);
    });

    $(document).on('click', '.autocomplete-item', function() {
        let item = $(this);
        let text = item.text().trim();
        $('#search_location_input').val(text);
        $('#province_id').val(item.data('province-id'));
        $('#district_id').val(item.data('district-id'));
        $('#ward_id').val(item.data('ward-id'));
        $('#province_name').val(item.data('province-name'));
        $('#district_name').val(item.data('district-name'));
        $('#ward_name').val(item.data('ward-name'));
        
        $('#search_location_results').hide();
        
        if($('#checkoutForm').data('validator')) {
             $('#province_id').valid();
             $('#district_id').valid();
             $('#ward_id').valid();
        }
        
        // Trigger fee ship calculation
        getFeeShip();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.position-relative').length) {
            $('#search_location_results').hide();
        }
    });

    $('input[name="address"]').on('change', function() {
        getFeeShip();
    });

    function getFeeShip() {
        var ward = $('#ward_name').val(),
            province = $('#province_name').val(), 
            district = $('#district_name').val();
        var address = $('input[name="address"]').val();
        
        if(!province || !district || !ward) return;

        $.ajax({
            type: 'post',
            url: '{{route("cart.feeship")}}',
            data: {province:province,district:district,ward:ward,address:address},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                // Bước 3: Sửa lỗi AJAX getFeeShip
                // Chỉ làm 2 việc: Gán giá trị vào input[name="feeShip"] và gọi updateTotalOrderPriceCheckout()
                // CẤM: Không được dùng các lệnh kiểu amount + feeship trực tiếp trong hàm AJAX
                console.log('[AJAX_FEESHIP_GETFEESHIP] Response received:', res);
                console.log('[AJAX_FEESHIP_GETFEESHIP] checkoutPriceBreakdowns BEFORE update:', window.checkoutPriceBreakdowns);
                console.log('[AJAX_FEESHIP_GETFEESHIP] Number of items in breakdowns:', window.checkoutPriceBreakdowns ? Object.keys(window.checkoutPriceBreakdowns).length : 0);
                
                // CRITICAL: Dùng parseFloat thay vì parseInt để tránh mất số thập phân
                const feeShipNum = parseFloat(res.feeship.replace(/[^\d]/g, '')) || 0;
                $('input[name="feeShip"]').val(feeShipNum);
                console.log('[AJAX_FEESHIP_GETFEESHIP] Parsed feeShip:', feeShipNum, '| Type:', typeof feeShipNum);
                
                // Cập nhật hiển thị phí ship
                $('.item-ship').html(res.feeship+'đ');
                $('.fee_ship').html(res.feeship+'đ');
                
                console.log('[AJAX_FEESHIP_GETFEESHIP] checkoutPriceBreakdowns AFTER update:', window.checkoutPriceBreakdowns);
                console.log('[AJAX_FEESHIP_GETFEESHIP] Number of items in breakdowns:', window.checkoutPriceBreakdowns ? Object.keys(window.checkoutPriceBreakdowns).length : 0);
                
                // Gọi hàm tính tổng để cập nhật tổng thanh toán
                updateTotalOrderPriceCheckout();
            }
        });
    }
</script>
@if(getConfig('ghtk_status'))
<script>
    $('#ward').change(function(){
        var ward = $('#ward option:selected').text(),province = $('#province option:selected').text(), district = $('#district option:selected').text();
        var address = $('input[name="address"]').val();
        $.ajax({
            type: 'post',
            url: '{{route("cart.feeship")}}',
            data: {province:province,district:district,ward:ward,address:address},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                // Bước 3: Sửa lỗi AJAX getFeeShip
                // Chỉ làm 2 việc: Gán giá trị vào input[name="feeShip"] và gọi updateTotalOrderPriceCheckout()
                // CẤM: Không được dùng các lệnh kiểu amount + feeship trực tiếp trong hàm AJAX
                console.log('[AJAX_FEESHIP_GHTK] Response received:', res);
                console.log('[AJAX_FEESHIP_GHTK] checkoutPriceBreakdowns BEFORE update:', window.checkoutPriceBreakdowns);
                console.log('[AJAX_FEESHIP_GHTK] Number of items in breakdowns:', window.checkoutPriceBreakdowns ? Object.keys(window.checkoutPriceBreakdowns).length : 0);
                
                // CRITICAL: Dùng parseFloat thay vì parseInt để tránh mất số thập phân
                const feeShipNum = parseFloat(res.feeship.replace(/[^\d]/g, '')) || 0;
                $('input[name="feeShip"]').val(feeShipNum);
                console.log('[AJAX_FEESHIP_GHTK] Parsed feeShip:', feeShipNum, '| Type:', typeof feeShipNum);
                
                // Cập nhật hiển thị phí ship
                $('.item-ship').html(res.feeship+'đ');
                $('.fee_ship').html(res.feeship+'đ');
                
                console.log('[AJAX_FEESHIP_GHTK] checkoutPriceBreakdowns AFTER update:', window.checkoutPriceBreakdowns);
                console.log('[AJAX_FEESHIP_GHTK] Number of items in breakdowns:', window.checkoutPriceBreakdowns ? Object.keys(window.checkoutPriceBreakdowns).length : 0);
                
                // Gọi hàm tính tổng để cập nhật tổng thanh toán
                updateTotalOrderPriceCheckout();
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                //window.location = window.location.href;
            }
        })
    })
</script>
@endif
<script>
    // Text animation đơn giản cho các trường checkout (placeholder gõ từng chữ)
    $(document).ready(function () {
        var fields = [
            {selector: '#checkoutForm input[name="full_name"]', text: 'Nhập tên người mua'},
            {selector: '#checkoutForm input[name="phone"]', text: 'Nhập số điện thoại liên hệ'},
            {selector: '#checkoutForm input[name="email"]', text: 'Nhập email (nếu có)'},
            {selector: '#search_location_input', text: 'Nhập Xã, Huyện, Tỉnh để gợi ý địa chỉ'},
            {selector: '#checkoutForm input[name="address"]', text: 'Số nhà, tên đường, tòa nhà...'},
            {selector: '#checkoutForm textarea[name="remark"]', text: 'Ghi chú cho đơn hàng (nếu cần)'},
            {selector: '#checkoutForm input[name="coupon"]', text: 'NHẬP MÃ GIẢM GIÁ (NẾU CÓ)'}
        ];

        fields.forEach(function (cfg) {
            var $input = $(cfg.selector);
            if (!$input.length) return;

            // Không chạy nếu đã có sẵn giá trị
            if ($input.val() && $input.val().length > 0) return;

            var fullText = cfg.text;
            var index = 0;
            var started = false;

            function typeOnce() {
                if ($input.is(':focus') || $input.val() !== '') {
                    return;
                }
                if (index <= fullText.length) {
                    $input.attr('placeholder', fullText.substring(0, index) + (index < fullText.length ? '|' : ''));
                    index++;
                    setTimeout(typeOnce, 55);
                } else {
                    setTimeout(function () {
                        if ($input.val() === '' && !$input.is(':focus')) {
                            $input.attr('placeholder', fullText);
                        }
                    }, 350);
                }
            }

            setTimeout(function () {
                if ($input.val() === '' && !$input.is(':focus')) {
                    started = true;
                    typeOnce();
                }
            }, 800);

            $input.on('focus input', function () {
                $input.attr('placeholder', '');
            });
            $input.on('blur', function () {
                if ($input.val() === '') {
                    $input.attr('placeholder', fullText);
                }
            });
        });
    });

    $('#checkoutForm').validate({
    ignore: [],
    invalidHandler: function(event, validator) {
        var errors = validator.numberOfInvalids();
        if (errors) {
            var message = errors == 1
              ? 'Có 1 trường chưa nhập đúng. Vui lòng kiểm tra lại.'
              : 'Có ' + errors + ' trường chưa nhập đúng. Vui lòng kiểm tra lại.';
            var errorList = "";
            for (var i = 0; i < validator.errorList.length; i++) {
                errorList += "\n- " + validator.errorList[i].message;
            }
            alert(message + errorList);
            console.log("Validation Errors:", validator.errorList);
        }
    },
    rules: { 
        full_name: {
           required: true,
           maxlength:120,        
        },
        phone: {
           required: true, 
           number: true,
        },
        email: {
           email: true,
        },
        province:{
          required: true, 
        },
        district:{
          required: true, 
        },
        ward:{
          required: true, 
        },
    },
    messages: {
        full_name: {
           required: "Bạn chưa nhập tên người mua",
           maxlength:"Số ký tự không vượt quá 120" 
        },
        phone: {
           required: "Bạn chưa nhập số điện thoại",
           number: "Số điện thoại không đúng",
        },
        email: {
           email: "Địa chỉ email không đúng",
        },
        province:{
          required: "Bạn chưa chọn tỉnh/thành phố", 
        },
        district:{
          required: "Bạn chưa chọn quận/huyện", 
        },
        ward:{
          required: "Bạn chưa chọn xã/phường", 
        },
    },
    submitHandler: function (form) {
        console.log("Form submitting...");
        console.log("Serialized Data:", $(form).serialize());
        $.ajax({
            type: 'post',
            url:  '{{route("cart.checkout")}}',
            data: $(form).serialize(),
            beforeSend: function () {
                console.log("Sending AJAX request...");
                $('#place_order').html('<span class="spinner-border"></span>');
                $('#place_order').prop('disabled',true);
            },
            success: function (res) {
            console.log("AJAX Success response:", res);
            if(res.status == 'error'){
                var errTxt = '';
                if(res.errors !== undefined) {
                    Object.keys(res.errors).forEach(key => {
                        errTxt += '<li>'+res.errors[key][0]+'</li>';
                    });
                } else {
                        errTxt = '<li>'+res.message+'</li>';
                    } 
                    $('body .alert-box').html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">Ă—</span></button><h5>Thông báo!</h5><ul>'+errTxt+'</ul>');
                
                // Bước 3: Ép làm mới giỏ hàng khi lỗi "Suất quà tặng"
                if (res.should_refresh_cart === true) {
                    console.log('[Checkout] Refreshing cart due to deal quota error');
                    // Gọi lại CartService::getCart() để tự động cập nhật lại trạng thái quà tặng
                    if (typeof CartAPI !== 'undefined' && typeof CartAPI.getCart === 'function') {
                        CartAPI.getCart().done(function(cartData) {
                            console.log('[Checkout] Cart refreshed after deal quota error', cartData);
                            // Reload trang để hiển thị trạng thái mới nhất
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }).fail(function() {
                            console.error('[Checkout] Failed to refresh cart');
                            // Vẫn reload trang để đảm bảo đồng bộ
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        });
                    } else {
                        // Fallback: Reload trang trực tiếp
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    }
                }
                }else{
                    alert("Đặt hàng thành công!")
                    window.location = res.url;
                }
                $('#place_order').html('Đặt hàng');
                $('#place_order').prop('disabled',false);
            }
        });
        return false;
    }
});
$('.btn_coupon').click(function(){
    var code = $('input[name="coupon"]').val();
    $.ajax({
        type: 'post',
        url: '{{route("cart.applyCoupon")}}',
        data: {code:code},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (res) {
            if(res.status == 'success'){
                if (!window.checkoutData || !window.checkoutData.subtotal || window.checkoutData.subtotal <= 0) {
                    console.error('[Checkout_Fix] Cannot apply coupon: subtotal is invalid', {
                        checkoutData: window.checkoutData
                    });
                    $('.box-alert-promotion').html('<div class="alert alert-danger mt-3" role="alert">Không thể áp dụng coupon: Tổng tiền đơn hàng chưa được tính đúng. Vui lòng làm mới trang.</div>');
                    return;
                }
                
                // Cập nhật Data Store từ response
                const saleNum = parseInt(res.sale.replace(/[^\d]/g, '')) || 0;
                if (typeof syncCheckoutData === 'function') {
                    syncCheckoutData(undefined, saleNum, undefined);
                } else {
                    window.checkoutData.sale = saleNum;
                    updateTotalOrderPriceCheckout();
                }
                
                $('.sale-promotion').html('-'+res.sale);
                $('.item-promotion-'+res.id+' .btn_apply').html('Hủy').addClass('btn_cancel_promotion').removeClass('btn_apply');
                $('.box-code-coupon').html('<span>'+res.code+'</span>');
            }else{
                $('.box-alert-promotion').html('<div class="alert alert-danger mt-3" role="alert">'+res.message+'</div>')
            }
        },
        error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
        }
    });
})
$('body').on('click','.btn_apply',function(){
    var id = $(this).attr('data-id');
    var code = $(this).attr('data-code');
    var feeship = $('input[name="feeShip"]').val();
    $.ajax({
        type: 'post',
        url: '{{route("cart.applyCoupon")}}',
        data: {code:code,feeship:feeship},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (res) {
            if(res.status == 'success'){
                if (!window.checkoutData || !window.checkoutData.subtotal || window.checkoutData.subtotal <= 0) {
                    console.error('[Checkout_Price] Cannot apply coupon: subtotal is invalid', {
                        checkoutData: window.checkoutData
                    });
                    $('.item-promotion-'+id+' .alert-item-promotion').html('<div class="alert alert-danger mt-3 me-3" role="alert">Không thể áp dụng coupon: Tổng tiền đơn hàng chưa được tính đúng. Vui lòng làm mới trang.</div>');
                    return;
                }
                
                // Đồng bộ giảm giá với Data Store
                const saleNum = parseInt(res.sale.replace(/[^\d]/g, '')) || 0;
                if (typeof syncCheckoutData === 'function') {
                    syncCheckoutData(undefined, saleNum, undefined);
                } else {
                    window.checkoutData.sale = saleNum;
                    updateTotalOrderPriceCheckout();
                }
                
                $('.sale-promotion').html('-'+res.sale);
                $('.item-promotion-'+id+' .btn_apply').html('Hủy').addClass('btn_cancel_promotion').removeClass('btn_apply');
                $('.box-code-coupon').html('<span>'+res.code+'</span>');
                $('.box-alert-promotion').html('');
            }else{
                $('.item-promotion-'+id+' .alert-item-promotion').html('<div class="alert alert-danger mt-3 me-3" role="alert">'+res.message+'</div>')
            }
        },
        error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
        }
    });
});
$('body').on('click','.btn_cancel_promotion',function(){
    var id = $(this).attr('data-id');
    var feeship = $('input[name="feeShip"]').val();
    $.ajax({
        type: 'post',
        url: '{{route("cart.cancelCoupon")}}',
        data: {feeship:feeship},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (res) {
            if(res.status == 'success'){
                // Đồng bộ giảm giá với Data Store (hủy coupon = sale = 0)
                if (typeof syncCheckoutData === 'function') {
                    syncCheckoutData(undefined, 0, undefined);
                } else if (window.checkoutData) {
                    window.checkoutData.sale = 0;
                    updateTotalOrderPriceCheckout();
                } else {
                    $('.total-order').html(res.total);
                }
                $('.sale-promotion').html('0đ');
                $('.item-promotion-'+id+' .btn_cancel_promotion').html('Áp dụng').addClass('btn_apply').removeClass('btn_cancel_promotion');
                $('.box-code-coupon').html('');
                $('.box-alert-promotion').html('');
            }
        },
        error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
        }
    });
})
</script>
<style>
    .select2-container--default .select2-selection--single .select2-selection__rendered{
        line-height: 45px;
    }
    .select2-container--default .select2-selection--single{
        border: 1px solid #ced4da;
        height: 45px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow{
        height: 40px;
    }
    .autocomplete-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1000;
        background: #fff;
        border: 1px solid #ced4da;
        border-top: none;
        max-height: 250px;
        overflow-y: auto;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: none;
        border-bottom-left-radius: 4px;
        border-bottom-right-radius: 4px;
    }
    .autocomplete-item {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f8f9fa;
        font-size: 14px;
    }
    .autocomplete-item:last-child {
        border-bottom: none;
    }
    .autocomplete-item:hover {
        background-color: #f1f1f1;
    }
    #search_location_input {
        height: 45px;
    }
    #search_location_input::placeholder {
        font-size: 12px;
    }

    /* ========== CSS cho cảnh báo trong cart-sidebar ========== */
    .checkout-warning-container {
        margin-top: 8px;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
    }
    
    .checkout-warning {
        padding: 8px 10px;
        border-left: 3px solid #dc3545;
        border-radius: 4px;
        font-size: 8px;
        line-height: 1.3;
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: break-word;
        white-space: normal;
        max-width: 100%;
        box-sizing: border-box;
        margin-bottom: 6px;
    }
    
    .checkout-warning i {
        color: #dc3545;
        margin-right: 4px;
        font-size: 8px;
        vertical-align: middle;
    }
    
    .checkout-warning strong {
        color: #dc3545;
        font-size: 8px;
        font-weight: 600;
        line-height: 1.3;
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: break-word;
        display: inline-block;
        max-width: calc(100% - 20px);
    }
    
    /* Flash Sale Warning (màu vàng) */
    .flash-sale-warning {
        padding: 8px 10px;
        background-color: #fff3cd;
        border-left: 3px solid #ffc107;
        border-radius: 4px;
        font-size: 8px;
        line-height: 1.3;
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: break-word;
        white-space: normal;
        max-width: 100%;
        box-sizing: border-box;
        margin-top: 6px;
        margin-bottom: 6px;
    }
    
    .flash-sale-warning i {
        color: #856404;
        margin-right: 4px;
        font-size: 8px;
        vertical-align: middle;
    }
    
    .flash-sale-warning strong {
        color: #856404;
        font-size: 8px;
        font-weight: 600;
        line-height: 1.3;
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: break-word;
        display: inline-block;
        max-width: calc(100% - 20px);
    }
    
    .flash-sale-warning > div {
        margin-top: 4px;
        padding-top: 4px;
        border-top: 1px solid #ffc107;
        font-size: 8px;
        line-height: 1.4;
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    
    /* Stock Error Summary */
    .checkout-warning-summary {
        padding: 8px 10px;
        background-color: #f8d7da;
        border-left: 3px solid #dc3545;
        border-radius: 4px;
        font-size: 8px;
        line-height: 1.3;
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: break-word;
        white-space: normal;
        max-width: 100%;
        box-sizing: border-box;
        margin-bottom: 10px;
    }
    
    .checkout-warning-summary i {
        color: #dc3545;
        margin-right: 4px;
        font-size: 8px;
        vertical-align: middle;
    }
    
    .checkout-warning-summary strong {
        color: #dc3545;
        font-size: 8px;
        font-weight: 600;
        line-height: 1.3;
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: break-word;
        display: inline-block;
        max-width: calc(100% - 20px);
    }


    /* ========== Hiệu ứng nhập liệu (đen trắng, tinh gọn) cho trang checkout ========== */
    #page_checkout label {
        font-weight: 600;
        letter-spacing: 0.1px;
    }
    #page_checkout .form-control,
    #page_checkout textarea.form-control {
        position: relative;
        background: #fff;
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 11px 12px;
        transition: all 0.2s ease;
        box-shadow: none;
    }
    #page_checkout .form-control::placeholder,
    #page_checkout textarea.form-control::placeholder {
        color: #9a9a9a;
        opacity: 1;
        font-size: 12px;
    }
    #page_checkout .form-control:focus,
    #page_checkout textarea.form-control:focus {
        border-color: #111;
        box-shadow: 0 0 0 2px rgba(0,0,0,0.04);
        transform: translateY(-1px);
        outline: none;
    }
    #page_checkout input:-webkit-autofill,
    #page_checkout textarea:-webkit-autofill {
        -webkit-box-shadow: 0 0 0 30px #fff inset !important;
        -webkit-text-fill-color: #222 !important;
        transition: background-color 5000s ease-in-out 0s;
    }
</style>
<script>
    // Bước 3: Sửa lỗi JavaScript checkFlashSalePriceCheckout is not defined
    // Định nghĩa hàm này ngay trong thẻ <script> để không làm treo luồng xử lý AJAX khi bấm nút Đặt hàng
    window.checkFlashSalePriceCheckout = function(variantId, quantity) {
            // Nếu FlashSaleMixedPrice chưa được load, bypass để không làm treo AJAX
            if (typeof FlashSaleMixedPrice === 'undefined') {
                console.log('[CheckFlashSalePriceCheckout] FlashSaleMixedPrice not loaded, bypass');
                return true; // Return true để không block AJAX
            }
            
            // Get product_id from the item - try multiple ways
            var $item = $('[data-variant-id="' + variantId + '"]');
            if ($item.length === 0) {
                // Try to find by closest parent with data attribute
                $item = $('#quantity-' + variantId).closest('[data-product-id], .des-cart');
            }
            
            var productId = $item.attr('data-product-id');
            
            if (!productId) {
                // Try to get from cart API or reload
                return;
            }
            
            // Call FlashSaleMixedPrice to calculate price với callback để cập nhật tổng tiền
            FlashSaleMixedPrice.calculatePriceWithQuantity(
                parseInt(productId),
                parseInt(variantId),
                quantity,
                '.price-item-' + variantId, // Price display selector
                '.flash-sale-warning-container-' + variantId, // Warning container
                function(priceData) {
                    // Lưu price breakdown để tính tổng
                    if (!window.checkoutPriceBreakdowns) {
                        window.checkoutPriceBreakdowns = {};
                    }
                    window.checkoutPriceBreakdowns[variantId] = priceData;
                    
                    // Cập nhật giá hiển thị và breakdown giá
                    const $priceItem = $('.price-item-' + variantId);
                    if ($priceItem.length) {
                        $priceItem.text(FlashSaleMixedPrice.formatNumber(priceData.total_price) + 'đ');
                        
                        // Bước 4: Cập nhật breakdown giá (ví dụ: 100x385,000 + 11x440,000)
                        if (priceData.price_breakdown && priceData.price_breakdown.length > 1) {
                            const $breakdownContainer = $priceItem.next('.fs-11.text-muted.mt-1');
                            if ($breakdownContainer.length) {
                                let breakdownText = '';
                                priceData.price_breakdown.forEach(function(bd, index) {
                                    breakdownText += bd.quantity + 'x' + FlashSaleMixedPrice.formatNumber(bd.unit_price) + 'đ';
                                    if (index < priceData.price_breakdown.length - 1) {
                                        breakdownText += ' + ';
                                    }
                                });
                                $breakdownContainer.text(breakdownText);
                            } else {
                                // Nếu chưa có container breakdown, tạo mới
                                const breakdownHtml = '<div class="fs-11 text-muted mt-1" style="cursor: pointer;" title="Click để xem chi tiết">' +
                                    priceData.price_breakdown.map(function(bd, index) {
                                        return bd.quantity + 'x' + FlashSaleMixedPrice.formatNumber(bd.unit_price) + 'đ';
                                    }).join(' + ') +
                                    '</div>';
                                $priceItem.after(breakdownHtml);
                            }
                        }
                    }
                    
                    // Callback: Cập nhật tổng tiền đơn hàng sau khi tính giá thành công
                    updateTotalOrderPriceCheckout();
                    
                    // Kiểm tra và xử lý lỗi tồn kho
                    if (priceData.is_available === false) {
                        // Vô hiệu hóa nút đặt hàng
                        $('#place_order').prop('disabled', true).addClass('disabled');
                        $('.stock-error-summary').show();
                        
                        // Tự động điều chỉnh số lượng về tồn kho thực tế
                        if (priceData.total_physical_stock !== null && priceData.total_physical_stock !== undefined) {
                            const maxStock = parseInt(priceData.total_physical_stock);
                            const $quantityInput = $('#quantity-' + variantId);
                            const currentVal = parseInt($quantityInput.val()) || 1;
                            
                            if (currentVal > maxStock) {
                                $quantityInput.val(maxStock);
                                // Tính lại giá với số lượng mới
                                setTimeout(() => {
                                    checkFlashSalePriceCheckout(variantId, maxStock);
                                }, 100);
                            }
                        }
                    } else {
                        // Kiểm tra tất cả items xem còn lỗi tồn kho không
                        let hasAnyStockError = false;
                        if (window.checkoutPriceBreakdowns) {
                            Object.keys(window.checkoutPriceBreakdowns).forEach(function(vId) {
                                const pData = window.checkoutPriceBreakdowns[vId];
                                if (pData && pData.is_available === false) {
                                    hasAnyStockError = true;
                                }
                            });
                        }
                        
                        if (!hasAnyStockError) {
                            // Kích hoạt lại nút đặt hàng nếu tất cả items đều hợp lệ
                            $('#place_order').prop('disabled', false).removeClass('disabled');
                            $('.stock-error-summary').hide();
                        }
                    }
                }
            );
    };
    
    $(document).ready(function() {
        
        // Store price breakdown data for each item
        window.checkoutPriceBreakdowns = window.checkoutPriceBreakdowns || {};
        
        // ===== Bước 1: JS Data Store Sync - Nguồn sự thật duy nhất =====
        // Bước 1: Khóa biến Subtotal - Tuyệt đối không tính lại ở Frontend
        // Biến window.checkoutData.subtotal phải được coi là hằng số sau khi trang đã load
        // Chỉ có Backend mới được phép thay đổi số này
        window.syncCheckoutData = function (newSubtotal, newSale, newFeeship) {
            if (!window.checkoutData) {
                window.checkoutData = {
                    subtotal: 0,
                    sale: 0,
                    feeship: 0,
                    total: 0
                };
            }

            // CẤM: Không cho phép Frontend thay đổi subtotal
            // Subtotal chỉ được set từ Backend khi trang load
            // if (typeof newSubtotal !== 'undefined' && newSubtotal !== null) {
            //     window.checkoutData.subtotal = parseFloat(newSubtotal) || 0;
            // }
            
            if (typeof newSale !== 'undefined' && newSale !== null) {
                window.checkoutData.sale = parseFloat(newSale) || 0;
            }
            
            // Chỉ cập nhật feeship, không làm ảnh hưởng đến subtotal
            if (typeof newFeeship !== 'undefined' && newFeeship !== null) {
                window.checkoutData.feeship = parseFloat(newFeeship) || 0;
                // Đồng bộ input ẩn để các chỗ khác (nếu có) vẫn đọc được
                $('input[name="feeShip"]').val(window.checkoutData.feeship);
            }

            // Sau khi sync xong thì mới render ra màn hình
            updateTotalOrderPriceCheckout();
        };

        // ===== Bước 2: Viết lại hàm tính Tổng (Clean Code) =====
        // Thay thế toàn bộ nội dung hàm updateTotalOrderPriceCheckout bằng logic sạch
        // để tránh việc cộng nhầm biến rác
        function updateTotalOrderPriceCheckout() {
            console.log('[UPDATE_TOTAL] ===== BẮT ĐẦU TÍNH TỔNG =====');
            
            if (!window.checkoutData) {
                console.warn('[UPDATE_TOTAL] checkoutData is not initialized');
                return 0;
            }

            function formatPrice(price) {
                if (typeof FlashSaleMixedPrice !== 'undefined' && FlashSaleMixedPrice.formatNumber) {
                    return FlashSaleMixedPrice.formatNumber(price);
                }
                return new Intl.NumberFormat('vi-VN').format(price);
            }

            // ===== BƯỚC 1: KIỂM TRA VÀ LOG window.checkoutPriceBreakdowns =====
            console.log('[UPDATE_TOTAL] Step 1: Checking window.checkoutPriceBreakdowns');
            console.log('[UPDATE_TOTAL] window.checkoutPriceBreakdowns:', window.checkoutPriceBreakdowns);
            console.log('[UPDATE_TOTAL] Number of items in breakdowns:', window.checkoutPriceBreakdowns ? Object.keys(window.checkoutPriceBreakdowns).length : 0);
            
            if (window.checkoutPriceBreakdowns) {
                Object.keys(window.checkoutPriceBreakdowns).forEach(function(vId) {
                    let itemData = window.checkoutPriceBreakdowns[vId];
                    console.log('[UPDATE_TOTAL] Item variantId:', vId, '| total_price:', itemData?.total_price, '| Full data:', itemData);
                });
            }

            // ===== BƯỚC 2: TÍNH SUBTOTAL TỪ BREAKDOWNS =====
            let calculatedSubtotal = 0;
            let hasBreakdownData = false;
            let breakdownItems = [];
            
            if (window.checkoutPriceBreakdowns && Object.keys(window.checkoutPriceBreakdowns).length > 0) {
                console.log('[UPDATE_TOTAL] Step 2: Calculating subtotal from breakdowns');
                
                // CRITICAL: Tính tổng từ TẤT CẢ items trong breakdown - KHÔNG BỎ SÓT ITEM NÀO
                const allVariantIds = Object.keys(window.checkoutPriceBreakdowns);
                console.log('[UPDATE_TOTAL] All variant IDs found:', allVariantIds, '| Count:', allVariantIds.length);
                
                allVariantIds.forEach(function(vId, index) {
                    let itemData = window.checkoutPriceBreakdowns[vId];
                    console.log('[UPDATE_TOTAL] Processing item #' + (index + 1) + ' of ' + allVariantIds.length + ':', {
                        variantId: vId,
                        itemData: itemData,
                        total_price: itemData?.total_price,
                        total_price_type: typeof itemData?.total_price
                    });
                    
                    if (itemData && itemData.total_price !== undefined && itemData.total_price !== null) {
                        const itemPrice = parseFloat(itemData.total_price) || 0;
                        const beforeAdd = calculatedSubtotal;
                        calculatedSubtotal += itemPrice;
                        hasBreakdownData = true;
                        breakdownItems.push({
                            variantId: vId,
                            price: itemPrice,
                            beforeAdd: beforeAdd,
                            afterAdd: calculatedSubtotal
                        });
                        console.log('[UPDATE_TOTAL] ✓ Added item #' + (index + 1) + ':', {
                            variantId: vId,
                            price: itemPrice,
                            before: beforeAdd,
                            after: calculatedSubtotal,
                            accumulated: calculatedSubtotal
                        });
                    } else {
                        console.error('[UPDATE_TOTAL] ✗ Item #' + (index + 1) + ' SKIPPED - Invalid data:', {
                            variantId: vId,
                            itemData: itemData,
                            reason: !itemData ? 'itemData is null/undefined' : 
                                   (itemData.total_price === undefined ? 'total_price is undefined' : 
                                   (itemData.total_price === null ? 'total_price is null' : 'unknown'))
                        });
                    }
                });
                
                console.log('[UPDATE_TOTAL] Breakdown calculation complete:', {
                    itemCount: breakdownItems.length,
                    calculatedSubtotal: calculatedSubtotal,
                    items: breakdownItems
                });
            } else {
                console.log('[UPDATE_TOTAL] No breakdown data found, will use backend subtotal');
            }
            
            // Nếu không có breakdown data hoặc breakdown rỗng, dùng subtotal từ backend
            // Điều này đảm bảo khi trang load lần đầu, subtotal được lấy từ backend
            if (!hasBreakdownData) {
                calculatedSubtotal = parseFloat(window.checkoutData.subtotal) || 0;
                console.log('[UPDATE_TOTAL] Using backend subtotal:', calculatedSubtotal);
            }

            // ===== BƯỚC 3: LẤY DISCOUNT =====
            const discount = parseFloat(window.checkoutData.sale) || 0;
            console.log('[UPDATE_TOTAL] Step 3: Discount =', discount);
            
            // ===== BƯỚC 4: LẤY PHÍ SHIP =====
            const feeshipInput = $('input[name="feeShip"]').val();
            const feeship = parseFloat(feeshipInput) || 0;
            console.log('[UPDATE_TOTAL] Step 4: Shipping fee');
            console.log('[UPDATE_TOTAL]   - Input value (raw):', feeshipInput);
            console.log('[UPDATE_TOTAL]   - Parsed value:', feeship);
            console.log('[UPDATE_TOTAL]   - Type:', typeof feeship);

            // ===== BƯỚC 5: TÍNH TỔNG CUỐI CÙNG =====
            // Phép tính duy nhất và cuối cùng: Subtotal - Discount + Shipping Fee
            // Phí vận chuyển phải là bước cộng cuối cùng sau khi đã trừ toàn bộ mã giảm giá
            const finalTotal = calculatedSubtotal - discount + feeship;
            
            console.log('[UPDATE_TOTAL] Step 5: Final calculation');
            console.log('[UPDATE_TOTAL]   - Subtotal:', calculatedSubtotal);
            console.log('[UPDATE_TOTAL]   - Discount:', discount);
            console.log('[UPDATE_TOTAL]   - Shipping Fee:', feeship);
            console.log('[UPDATE_TOTAL]   - Formula: Subtotal - Discount + Shipping Fee');
            console.log('[UPDATE_TOTAL]   - Calculation:', calculatedSubtotal, '-', discount, '+', feeship, '=', finalTotal);

            // ===== BƯỚC 6: CẬP NHẬT DATA STORE =====
            window.checkoutData.subtotal = calculatedSubtotal;
            window.checkoutData.feeship = feeship;
            window.checkoutData.total = Math.max(0, finalTotal);
            
            console.log('[UPDATE_TOTAL] Step 6: Updated window.checkoutData:', window.checkoutData);

            // ===== BƯỚC 7: CẬP NHẬT UI =====
            $('.subtotal-cart').text(formatPrice(calculatedSubtotal) + 'đ');
            $('.sale-promotion').text('-' + formatPrice(discount) + 'đ');
            $('.fee_ship').text(formatPrice(feeship) + 'đ');
            $('.total-order').text(formatPrice(Math.max(0, finalTotal)) + 'đ');
            
            console.log('[UPDATE_TOTAL] Step 7: UI updated');
            console.log('[UPDATE_TOTAL] ===== KẾT THÚC TÍNH TỔNG =====');
            console.log('[UPDATE_TOTAL] FINAL RESULT:', {
                calculatedSubtotal: calculatedSubtotal,
                discount: discount,
                feeship: feeship,
                finalTotal: finalTotal,
                breakdownItemsCount: breakdownItems.length,
                hasBreakdownData: hasBreakdownData
            });

            // 8. Cập nhật giá từng dòng khi thay đổi số lượng
            // Quét qua các item trong window.checkoutPriceBreakdowns (đã có từ backend)
            if (window.checkoutPriceBreakdowns) {
                Object.keys(window.checkoutPriceBreakdowns).forEach(function(vId) {
                    let itemData = window.checkoutPriceBreakdowns[vId];
                    if (itemData && itemData.total_price !== undefined) {
                        // Cập nhật lại con số hiển thị của từng dòng sản phẩm
                        const $priceItem = $('.price-item-' + vId);
                        if ($priceItem.length) {
                            $priceItem.text(formatPrice(itemData.total_price) + 'đ');
                        }
                        
                        // Bước 4: Cập nhật breakdown giá (ví dụ: 100x385,000 + 11x440,000)
                        if (itemData.price_breakdown && itemData.price_breakdown.length > 1) {
                            const $breakdownContainer = $('.price-item-' + vId).next('.fs-11.text-muted.mt-1');
                            if ($breakdownContainer.length) {
                                let breakdownText = '';
                                itemData.price_breakdown.forEach(function(bd, index) {
                                    breakdownText += bd.quantity + 'x' + formatPrice(bd.unit_price) + 'đ';
                                    if (index < itemData.price_breakdown.length - 1) {
                                        breakdownText += ' + ';
                                    }
                                });
                                $breakdownContainer.text(breakdownText);
                            }
                        }
                    }
                });
            }

            // Gatekeeper: nếu subtotal tính được <= 0 => cảnh báo
            if (calculatedSubtotal <= 0) {
                console.warn('[Checkout_Sync] calculatedSubtotal is 0 or negative. Using original subtotal from backend.', {
                    calculatedSubtotal: calculatedSubtotal,
                    originalSubtotal: window.checkoutData.subtotal,
                    checkoutData: window.checkoutData
                });
            }

            return finalTotal;
        }
        
        // Handle quantity change in checkout (qtyplus/qtyminus buttons)
        $('body').on('click', '.qtyplus, .qtyminus', function() {
            var variantId = $(this).attr('data-id');
            var $input = $('#quantity-' + variantId);
            var quantity = parseInt($input.val()) || 1;
            
            // Debounce to avoid too many calls
            clearTimeout(window.checkoutPriceTimeout);
            window.checkoutPriceTimeout = setTimeout(function() {
                checkFlashSalePriceCheckout(variantId, quantity);
            }, 300);
        });
        
        // Handle manual input change
        $('body').on('change input', '.form-quatity', function() {
            var inputId = $(this).attr('id');
            if (!inputId || !inputId.startsWith('quantity-')) {
                return;
            }
            var variantId = inputId.replace('quantity-', '');
            var quantity = parseInt($(this).val()) || 1;
            
            // Debounce
            clearTimeout(window.checkoutPriceTimeout);
            window.checkoutPriceTimeout = setTimeout(function() {
                checkFlashSalePriceCheckout(variantId, quantity);
            }, 500);
        });
        
        // CRITICAL: Gọi updateTotalOrderPriceCheckout() khi trang load để hiển thị giá đúng ngay từ đầu
        $(document).ready(function() {
            // Đợi một chút để đảm bảo window.checkoutData đã được khởi tạo
            setTimeout(function() {
                updateTotalOrderPriceCheckout();
            }, 100);
        });
    });
</script>
@endsection
