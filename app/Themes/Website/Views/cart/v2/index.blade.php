@extends('Website::layout')
@section('title','Giỏ hàng')
@section('description','Giỏ hàng của bạn')
@section('content')
<section class="mt-3 mb-5">
    <div class="container-lg">
        <div class="breadcrumb">
            <ol>
                <li><a href="/">Trang chủ</a></li>
                <li><a href="{{route('cart.index')}}">Giỏ hàng</a></li>
            </ol>
        </div>
        <h1 class="fs-24 fw-bold">Giỏ hàng</h1>
        <div class="row mt-3">
            <div class="col-12 col-md-8" id="cart-items-container">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4" id="cart-summary-container">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // Pass routes to cart handler
    window.cartJsonRoute = '{{ route("cart.v2.json") }}';
    window.cartUpdateRoute = '/cart/items';
    window.cartRemoveRoute = '/cart/items';
    window.checkoutRoute = '{{ route("checkout.v2.index") }}';
</script>
<script src="{{ asset('website/js/cart-handler.js') }}" defer></script>
<script>
    // Load cart on page load
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.CartHandler !== 'undefined') {
            window.CartHandler.loadCart();
        }
    });
</script>
@endsection

