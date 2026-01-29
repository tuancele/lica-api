@extends('Website::layout')
@section('title','Thanh toán')
@section('description','Thanh toán')
@section('content')
<link href="{{ asset('website/select2/select2.min.css') }}" rel="stylesheet" />
<script src="{{ asset('website/select2/select2.min.js') }}"></script>
<script>
    // Pass routes to checkout handler
    window.checkoutRoute = '{{ route("checkout.v2.checkout") }}';
    window.shippingFeeRoute = '{{ route("checkout.v2.shippingFee") }}';
    window.applyCouponRoute = '{{ route("checkout.v2.applyCoupon") }}';
    window.removeCouponRoute = '{{ route("checkout.v2.removeCoupon") }}';
    window.searchLocationRoute = '{{ route("checkout.v2.searchLocation") }}';
    window.checkoutResultRoute = '{{ route("checkout.v2.result") }}';
</script>
<script src="{{ asset('website/js/checkout-handler.js') }}" defer></script>
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
        <div class="row mt-3 checkout-layout">
            <div class="col-12 col-lg-8 checkout-form-section">
                <div class="checkout-form-card">
                    <div class="form-section-header">
                        <h2 class="section-title">Thông tin người mua hàng</h2>
                        @if(!isset($member) && empty($member))
                        <button class="btn-link" type="button" data-bs-toggle="modal" data-bs-target="#myLogin">Đăng nhập nhanh</button>
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
                
                <!-- Payment Methods Section -->
                <div class="payment-methods-section">
                    <h3 class="section-subtitle">Phương thức thanh toán</h3>
                    <div class="payment-methods-grid">
                        <label class="payment-method-card">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <div class="payment-card-content">
                                <div class="payment-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M20 4H4C2.89 4 2.01 4.89 2.01 6L2 18C2 19.11 2.89 20 4 20H20C21.11 20 22 19.11 22 18V6C22 4.89 21.11 4 20 4ZM20 18H4V8H20V18Z" fill="currentColor"/>
                                    </svg>
                                </div>
                                <div class="payment-info">
                                    <span class="payment-name">Thanh toán khi nhận hàng</span>
                                    <span class="payment-desc">COD</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="payment-method-card">
                            <input type="radio" name="payment_method" value="bank_transfer">
                            <div class="payment-card-content">
                                <div class="payment-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M20 4H4C2.89 4 2.01 4.89 2.01 6L2 18C2 19.11 2.89 20 4 20H20C21.11 20 22 19.11 22 18V6C22 4.89 21.11 4 20 4ZM20 18H4V8H20V18Z" fill="currentColor"/>
                                    </svg>
                                </div>
                                <div class="payment-info">
                                    <span class="payment-name">Chuyển khoản ngân hàng</span>
                                    <span class="payment-desc">Bank Transfer</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                </div>
            </div>
            <div class="col-12 col-lg-4 checkout-summary-section">
                <div class="order-summary-card sticky-summary">
                    <h3 class="summary-title">Tóm tắt đơn hàng</h3>
                    <div class="order-summary-details">
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
                        <div class="summary-row summary-total">
                            <span><strong>Tổng cộng:</strong></span>
                            <span class="total-amount"><strong id="total">{{number_format($cart['summary']['total'] ?? 0)}}đ</strong></span>
                        </div>
                    </div>
                    
                    <div class="voucher-section">
                        <label class="voucher-label">Mã giảm giá</label>
                        <div class="voucher-input-group">
                            <input type="text" 
                                   id="coupon-code" 
                                   class="form-control voucher-input" 
                                   placeholder="Nhập mã giảm giá">
                            <button type="button" 
                                    id="apply-coupon" 
                                    class="btn btn-primary btn-sm voucher-btn">
                                Áp dụng
                            </button>
                        </div>
                        <button type="button" 
                                id="remove-coupon" 
                                class="btn btn-link btn-sm remove-coupon-btn" 
                                style="display:none;">
                            Hủy mã
                        </button>
                    </div>
                    
                    <div class="trust-signals">
                        <div class="trust-item">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M10 0L12.09 6.26L18 7.27L13 11.14L14.18 17.02L10 14.77L5.82 17.02L7 11.14L2 7.27L7.91 6.26L10 0Z" fill="currentColor"/>
                            </svg>
                            <span>Chính hãng 100%</span>
                        </div>
                        <div class="trust-item">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm-2 15l-5-5 1.41-1.41L8 12.17l7.59-7.59L17 6l-9 9z" fill="currentColor"/>
                            </svg>
                            <span>Bảo mật thanh toán</span>
                        </div>
                        <div class="trust-item">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm1 15H9v-2h2v2zm0-4H9V5h2v6z" fill="currentColor"/>
                            </svg>
                            <span>Miễn phí vận chuyển</span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-checkout-submit w-100">
                        XÁC NHẬN ĐẶT HÀNG
                    </button>
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

