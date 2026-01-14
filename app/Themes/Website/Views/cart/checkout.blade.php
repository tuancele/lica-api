@extends('Website::layout')
@section('title','Thanh toán')
@section('description','Thanh toán')
@section('content')
@php $member = auth()->guard('member')->user(); @endphp
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
            <div class="col-12 col-md-8">
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
                        <input type="text" class="form-control" id="search_location_input" autocomplete="off" placeholder="Nhập Xã, Huyện, Tỉnh...">
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
                        <input type="text" class="form-control" id="search_location_input" autocomplete="off" placeholder="Nhập Xã, Huyện, Tỉnh...">
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
                                    <img src="{{getImage($product->image)}}" width="60" height="60" alt="{{$product->name}}" class="js-skeleton-img">
                                </div>
                            </div>
                            <div class="des-cart ms-2">
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
                                        <span class="fw-600 price-item-{{$variant['item']['id']}}">{{number_format($variant['price'])}}đ</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        @endforeach
                        @endif
                        <div class="divider-horizontal"></div>
                        <div class="align-center space-between mb-3 fs-14">
                            <span>Tổng giá trị đơn hàng</span>
                            <span class="subtotal-cart">{{number_format($totalPrice)}}đ</span>
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
                            <span class="fw-600 total-order">{{number_format($totalPrice - $sale + $feeship)}}đ</span>
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
                $('.item-ship').html(res.feeship+'đ');
                $('.total-order').html(res.amount+'đ');
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
                let fee = res.feeship;
                $('.item-ship').html(res.feeship+'đ');
                $('.total-order').html(res.amount+'đ');
                let feeStr = (fee !== null && fee !== undefined) ? String(fee) : '0';
                $('input[name="feeShip"]').val(feeStr.replace(/,/g,""));
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
                let fee = res.feeship;
                $('.item-ship').html(res.feeship+'đ');
                $('.total-order').html(res.amount+'đ');
                // Fix: Ensure fee is a string before replace
                let feeStr = (fee !== null && fee !== undefined) ? String(fee) : '0';
                $('input[name="feeShip"]').val(feeStr.replace(/,/g,""));
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
                $('.total-order').html(res.total);
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
                $('.total-order').html(res.total);
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
                $('.total-order').html(res.total);
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
@endsection
