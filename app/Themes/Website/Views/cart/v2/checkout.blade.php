@extends('Website::layout')
@section('title','Thanh toán')
@section('description','Thanh toán')
@section('content')
<link href="/public/website/select2/select2.min.css" rel="stylesheet" />
<script src="/public/website/select2/select2.min.js"></script>
<section class="mt-3 mb-5" id="page_checkout">
    <div class="container-lg">
        <div class="breadcrumb">
            <ol>
                <li><a href="/">Trang chủ</a></li>
                <li><a href="{{route('checkout.v2.index')}}">Thanh toán</a></li>
            </ol>
        </div>
        <h1 class="fs-24 fw-bold">Thông tin thanh toán</h1>
        <form id="checkoutForm" method="post" class="checkout mt-2">
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
                @php $member = auth()->guard('member')->user(); @endphp
                @if(isset($member) && !empty($member))
                <p>Bạn đã đăng nhập với tài khoản <a class="text-underline" href="/account/profile">{{$member['email']}}</a>. <a href="{{route('account.logout')}}">Đăng xuất</a></p>
                @if(isset($address) && !empty($address))
                <div class="box_address">
                    <div class="item-address">
                        <p><strong>{{$address->last_name}} | {{$address->phone}} | {{$address->email}}</strong></p>
                        <p>{{$address->address}}@if($address->ward), {{$address->ward->name}}@endif @if($address->district), {{$address->district->name}}@endif @if($address->province), {{$address->province->name}}@endif</p>
                        <input type="hidden" name="full_name" value="{{$address->first_name}} {{$address->last_name}}">
                        <input type="hidden" name="phone" value="{{$address->phone}}">
                        <input type="hidden" name="email" value="{{$address->email}}">
                        <input type="hidden" name="province_id" value="{{$address->provinceid}}">
                        <input type="hidden" name="district_id" value="{{$address->districtid}}">
                        <input type="hidden" name="ward_id" value="{{$address->wardid}}">
                        <input type="hidden" name="address" value="{{$address->address}}">
                    </div>
                    <a href="javascript:;" class="btn_change_address" data-bs-toggle="modal" data-bs-target="#changeAddress">Thay đổi</a>
                </div>
                @else
                @include('Website::cart.v2.partials.address-form')
                @endif
                @else
                @include('Website::cart.v2.partials.address-form')
                @endif
            </div>
            <div class="col-12 col-md-4">
                <div class="cart-sidebar">
                    <div class="cart_totals">
                        <h3>CỘNG GIỎ HÀNG</h3>
                        <div class="cart-summary">
                            <div class="summary-row">
                                <span>Tạm tính:</span>
                                <span id="subtotal">{{number_format($cart['summary']['subtotal'] ?? 0)}}đ</span>
                            </div>
                            <div class="summary-row">
                                <span>Giảm giá:</span>
                                <span class="text-success" id="discount">-{{number_format($cart['summary']['discount'] ?? 0)}}đ</span>
                            </div>
                            <div class="summary-row">
                                <span>Phí vận chuyển:</span>
                                <span id="shipping-fee">{{number_format($cart['summary']['shipping_fee'] ?? 0)}}đ</span>
                            </div>
                            <div class="summary-row total-row">
                                <span><strong>Tổng cộng:</strong></span>
                                <span><strong id="total">{{number_format($cart['summary']['total'] ?? 0)}}đ</strong></span>
                            </div>
                        </div>
                        <div class="coupon-section mt-3">
                            <input type="text" id="coupon-code" class="form-control" placeholder="Nhập mã giảm giá">
                            <button type="button" id="apply-coupon" class="btn btn-sm btn-primary mt-2">Áp dụng</button>
                            <button type="button" id="remove-coupon" class="btn btn-sm btn-secondary mt-2" style="display:none;">Hủy mã</button>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-3">Đặt hàng</button>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initCheckout();
});

function initCheckout() {
    document.getElementById('checkoutForm').addEventListener('submit', handleCheckout);
    document.getElementById('apply-coupon').addEventListener('click', applyCoupon);
    document.getElementById('remove-coupon').addEventListener('click', removeCoupon);
    
    if (document.getElementById('search_location_input')) {
        initLocationSearch();
    }
    
    const provinceId = document.getElementById('province_id');
    const districtId = document.getElementById('district_id');
    const wardId = document.getElementById('ward_id');
    const addressInput = document.querySelector('input[name="address"]');
    
    if (provinceId && districtId && wardId) {
        const hasAddress = provinceId.value && districtId.value && wardId.value;
        if (hasAddress) {
            setTimeout(function() {
                calculateShippingFee();
            }, 500);
        }
        
        provinceId.addEventListener('change', function() {
            if (this.value && districtId.value && wardId.value) {
                calculateShippingFee();
            }
        });
        
        districtId.addEventListener('change', function() {
            if (provinceId.value && this.value && wardId.value) {
                calculateShippingFee();
            }
        });
        
        wardId.addEventListener('change', function() {
            if (provinceId.value && districtId.value && this.value) {
                calculateShippingFee();
            }
        });
        
        if (addressInput) {
            addressInput.addEventListener('blur', function() {
                if (provinceId.value && districtId.value && wardId.value) {
                    calculateShippingFee();
                }
            });
        }
    }
}

function handleCheckout(e) {
    e.preventDefault();
    
    const form = document.getElementById('checkoutForm');
    const formData = new FormData(form);
    
    fetch('{{route("checkout.v2.checkout")}}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{csrf_token()}}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.data.redirect_url || '{{route("checkout.v2.result")}}?code=' + data.data.order_code;
        } else {
            alert(data.message || 'Đặt hàng thất bại');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra');
    });
}

function applyCoupon() {
    const code = document.getElementById('coupon-code').value;
    if (!code) {
        alert('Vui lòng nhập mã giảm giá');
        return;
    }
    
    fetch('{{route("checkout.v2.applyCoupon")}}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{csrf_token()}}'
        },
        body: JSON.stringify({ code: code })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateSummary(data.data.summary);
            document.getElementById('remove-coupon').style.display = 'block';
            alert('Áp dụng mã thành công');
        } else {
            alert(data.message || 'Áp dụng mã thất bại');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra');
    });
}

function removeCoupon() {
    fetch('{{route("checkout.v2.removeCoupon")}}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{csrf_token()}}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateSummary(data.data.summary);
            document.getElementById('remove-coupon').style.display = 'none';
            document.getElementById('coupon-code').value = '';
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function calculateShippingFee() {
    const provinceIdEl = document.getElementById('province_id');
    const districtIdEl = document.getElementById('district_id');
    const wardIdEl = document.getElementById('ward_id');
    const addressEl = document.querySelector('input[name="address"]');
    
    if (!provinceIdEl || !districtIdEl || !wardIdEl) {
        console.warn('[Checkout] Address fields not found');
        return;
    }
    
    const provinceId = parseInt(provinceIdEl.value);
    const districtId = parseInt(districtIdEl.value);
    const wardId = parseInt(wardIdEl.value);
    const address = addressEl ? addressEl.value.trim() : '';
    
    if (!provinceId || !districtId || !wardId || isNaN(provinceId) || isNaN(districtId) || isNaN(wardId)) {
        console.warn('[Checkout] Missing address info', {
            province_id: provinceId,
            district_id: districtId,
            ward_id: wardId
        });
        return;
    }
    
    console.log('[Checkout] Calculating shipping fee', {
        province_id: provinceId,
        district_id: districtId,
        ward_id: wardId,
        address: address
    });
    
    fetch('{{route("checkout.v2.shippingFee")}}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{csrf_token()}}'
        },
        body: JSON.stringify({
            province_id: provinceId,
            district_id: districtId,
            ward_id: wardId,
            address: address
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('[Checkout] Shipping fee calculated', data.data);
            updateSummary(data.data.summary);
        } else {
            console.error('[Checkout] Shipping fee calculation failed', data.message || 'Unknown error');
            alert(data.message || 'Tính phí vận chuyển thất bại');
        }
    })
    .catch(error => {
        console.error('[Checkout] Shipping fee error:', error);
        alert('Có lỗi xảy ra khi tính phí vận chuyển: ' + error.message);
    });
}

function updateSummary(summary) {
    if (summary) {
        document.getElementById('subtotal').textContent = formatPrice(summary.subtotal || 0);
        document.getElementById('discount').textContent = '-' + formatPrice(summary.discount || 0);
        document.getElementById('shipping-fee').textContent = formatPrice(summary.shipping_fee || 0);
        document.getElementById('total').textContent = formatPrice(summary.total || 0);
    }
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
}

function initLocationSearch() {
    const input = document.getElementById('search_location_input');
    if (!input) return;
    
    input.addEventListener('input', function() {
        const keyword = this.value;
        if (keyword.length < 2) return;
        
        fetch('{{route("checkout.v2.searchLocation")}}?q=' + encodeURIComponent(keyword))
            .then(response => response.json())
            .then(data => {
                const results = document.getElementById('search_location_results');
                if (data.results && data.results.length > 0) {
                    results.innerHTML = data.results.map(item => 
                        `<div class="location-item" data-ward="${item.ward_id}" data-district="${item.district_id}" data-province="${item.province_id}">${item.text}</div>`
                    ).join('');
                    
                    results.querySelectorAll('.location-item').forEach(item => {
                        item.addEventListener('click', function() {
                            document.getElementById('province_id').value = this.dataset.province;
                            document.getElementById('district_id').value = this.dataset.district;
                            document.getElementById('ward_id').value = this.dataset.ward;
                            input.value = this.textContent;
                            results.innerHTML = '';
                            calculateShippingFee();
                        });
                    });
                } else {
                    results.innerHTML = '';
                }
            });
    });
}
</script>
@endsection

