@extends('Website::layout')
@section('title','Giỏ hàng của bạn')
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
            <div class="col-12 col">
                    <div class="commerce">
                          @if(Session::has('cart'))
                        <div class="row">
                            <div class="col-12 col-md-8 pb-0">
                                    <div class="cart-wrapper sm-touch-scroll">
                                        <table class="shop_table cart mb-0" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th class="product-name" colspan="3">Sản phẩm</th>
                                                    <th class="product-price">Giá</th>
                                                    <th class="product-quantity">Số lượng</th>
                                                    <th class="product-subtotal">Tổng</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                              @foreach($products as $variant)
                                                @php $product = App\Modules\Product\Models\Product::find($variant['item']['product_id']);@endphp
                                                @if(isset($product) && !empty($product))
                                                <tr class="item-cart-{{$variant['item']['id']}} @if(isset($variant['is_deal']) && $variant['is_deal'] == 1) is-deal-row @endif" data-main-id="{{$variant['item']['product_id']}}">
                                                    <td class="product-remove">
                                                        <a  href="javascript:;" data-id="{{$variant['item']['id']}}"  class="remove-item-cart fs-24" aria-label="Xóa sản phẩm này">  ×</a>
                                                    </td>

                                                    <td class="product-thumbnail">
                                                        <a href="{{getSlug($product->slug)}}">
                                                            <div class="skeleton--img-sm js-skeleton cart-product-image">
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
                                                                <img
                                                                    src="{{getImage($displayImage)}}"
                                                                    data-src="{{getImage($displayImage)}}"
                                                                    class="attachment-commerce_thumbnail size-commerce_thumbnail lazy-load-active js-skeleton-img"
                                                                    alt="{{$product->name}}"
                                                                />
                                                            </div>
                                                        </a>
                                                    </td>

                                                    <td class="product-name" data-title="Sản phẩm">
                                                        <a class="fw-600" href="{{getSlug($product->slug)}}">{{$product->name}}</a>
                                                        @if(isset($variant['is_deal']) && $variant['is_deal'] == 1)
                                                        <span class="badge bg-danger ms-2">Deal sốc</span>
                                                        @endif
                                                        <div>
                                                            @if($variant['item']->color)<span class="mt-2 me-3">Màu sắc: {{$variant['item']->color->name}}</span>@endif
                                                            @if($variant['item']->size)<span class="mt-2">Kích thước: {{$variant['item']->size->name}}{{$variant['item']->size->unit}}</span>@endif
                                                        </div>
                                                        @php
                                                            $variantId = $variant['item']['id'];
                                                            $priceData = $productsWithPrice[$variantId] ?? null;
                                                            $totalPriceForUnit = $priceData['total_price'] ?? ($variant['price'] * $variant['qty']);
                                                            $unitPrice = $variant['qty'] > 0 ? ($totalPriceForUnit / $variant['qty']) : $variant['price'];
                                                        @endphp
                                                        <div class="show-for-small mobile-product-price">
                                                            <span class="commerce-Price-amount amount item-unit-{{$variantId}}">
                                                                {{number_format($unitPrice)}}đ
                                                            </span>
                                                        </div>
                                                    </td>

                                                    <td class="product-price" data-title="Giá">
                                                        <span class="commerce-Price-amount amount item-unit-{{$variantId}}">
                                                            {{number_format($unitPrice)}}đ
                                                        </span>
                                                    </td>

                                                    <td class="product-quantity" data-title="Số lượng">
                                                        @if(isset($variant['is_deal']) && $variant['is_deal'] == 1)
                                                        <div class="quantity align-center justify-content-center">
                                                            <span class="fw-bold">{{$variant['qty']}}</span>
                                                            <input type="hidden" id="quantity-cart-{{$variant['item']['id']}}" value="{{$variant['qty']}}">
                                                        </div>
                                                        @else
                                                        <div class="quantity align-center">
                                                            <button class="btn-minus" type="button" data-id="{{$variant['item']['id']}}">
                                                                <span role="img" class="icon"><svg width="14" height="2" viewBox="0 0 14 2" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 0C0.447715 0 0 0.447715 0 1C0 1.55228 0.447715 2 1 2L1 0ZM13 2C13.5523 2 14 1.55228 14 1C14 0.447715 13.5523 0 13 0V2ZM1 2L13 2V0L1 0L1 2Z" fill="black"></path></svg></span>
                                                            </button>
                                                            <input type="text" name="" min="0" id="quantity-cart-{{$variant['item']['id']}}" class="form-quatity" value="{{$variant['qty']}}">
                                                            <button class="btn-plus" type="button" data-id="{{$variant['item']['id']}}">
                                                                <span role="img" class="icon"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 6C0.447715 6 0 6.44772 0 7C0 7.55228 0.447715 8 1 8L1 6ZM13 8C13.5523 8 14 7.55228 14 7C14 6.44772 13.5523 6 13 6V8ZM1 8L13 8V6L1 6L1 8Z" fill="black"></path><path d="M6 13C6 13.5523 6.44772 14 7 14C7.55228 14 8 13.5523 8 13L6 13ZM8 1C8 0.447715 7.55228 -2.41411e-08 7 0C6.44771 2.41411e-08 6 0.447715 6 1L8 1ZM8 13L8 1L6 1L6 13L8 13Z" fill="black"></path></svg></span>
                                                            </button>
                                                        </div>
                                                        @endif
                                                    </td>

                                                    <td class="product-subtotal" data-title="Tổng">
                                                        @php
                                                            $variantId = $variant['item']['id'];
                                                            $priceData = $productsWithPrice[$variantId] ?? null;
                                                            $hasBreakdown = $priceData && isset($priceData['price_breakdown']) && count($priceData['price_breakdown']) > 1;
                                                            $totalPrice = $priceData['total_price'] ?? ($variant['price'] * $variant['qty']);
                                                        @endphp
                                                        <span class="commerce-Price-amount amount item-total-{{$variantId}}">
                                                            {{number_format($totalPrice)}}đ
                                                        </span>
                                                        @if($hasBreakdown)
                                                            <div class="fs-11 text-muted mt-1" style="cursor: pointer;" title="Click để xem chi tiết">
                                                                @foreach($priceData['price_breakdown'] as $bd)
                                                                    {{$bd['quantity']}}x{{number_format($bd['unit_price'])}}đ
                                                                    @if(!$loop->last) + @endif
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <!-- Flash Sale Warning Container for this item -->
                                                @php
                                                    $variantId = $variant['item']['id'];
                                                    $priceData = $productsWithPrice[$variantId] ?? null;
                                                    $hasWarning = $priceData && !empty($priceData['warning']);
                                                    $hasDealWarning = $priceData && !empty($priceData['deal_warning']);
                                                @endphp
                                                @if($hasWarning)
                                                <tr class="flash-sale-warning-row-{{$variantId}}">
                                                    <td colspan="6" class="flash-sale-warning-container-{{$variantId}}" style="padding: 10px 15px;">
                                                        <div class="flash-sale-warning" style="padding: 10px; background-color: #fff3cd; border-left: 3px solid #ffc107; border-radius: 4px; font-size: 12px;">
                                                            <i class="fa fa-exclamation-triangle" style="color: #856404; margin-right: 5px;"></i>
                                                            <strong style="color: #856404;">Vượt quá số lượng Flash Sale</strong>
                                                            @if(isset($priceData['price_breakdown']) && count($priceData['price_breakdown']) > 1)
                                                                <div style="margin-top: 5px; padding-top: 5px; border-top: 1px solid #ffc107;">
                                                                    @foreach($priceData['price_breakdown'] as $bd)
                                                                        @php
                                                                            $typeLabel = $bd['type'] === 'flashsale' ? 'Flash Sale' : ($bd['type'] === 'promotion' ? 'Khuyến mãi' : 'Giá thường');
                                                                        @endphp
                                                                        {{$bd['quantity']}} sản phẩm × {{number_format($bd['unit_price'])}}đ ({{$typeLabel}}) = {{number_format($bd['subtotal'])}}đ
                                                                        @if(!$loop->last)<br>@endif
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                                @else
                                                <tr class="flash-sale-warning-row-{{$variantId}}" style="display: none;">
                                                    <td colspan="6" class="flash-sale-warning-container-{{$variantId}}" style="padding: 10px 15px;"></td>
                                                </tr>
                                                @endif
                                                @if($hasDealWarning)
                                                <tr class="deal-warning-row-{{$variantId}}">
                                                    <td colspan="6" class="deal-warning-container-{{$variantId}}" style="padding: 10px 15px;">
                                                        <div class="deal-warning" style="padding: 10px; background-color: #ffeaea; border-left: 3px solid #dc3545; border-radius: 4px; font-size: 12px; color: #b02a37;">
                                                            <i class="fa fa-times-circle" style="color: #b02a37; margin-right: 5px;"></i>
                                                            <strong>Quà tặng Deal Sốc đã hết</strong>
                                                            <div style="margin-top: 4px;">{{$priceData['deal_warning']}}</div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @else
                                                <tr class="deal-warning-row-{{$variantId}}" style="display: none;">
                                                    <td colspan="6" class="deal-warning-container-{{$variantId}}" style="padding: 10px 15px;"></td>
                                                </tr>
                                                @endif
                                                @endif
                                                @endforeach
                                                <tr>
                                                    <td colspan="6" class="actions clear">
                                                        <div class="continue-shopping pull-left text-left">
                                                            <a class="fw-600" href="/"> ← Tiếp tục mua sản phẩm </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                            </div>

                            <div class="cart-collaterals col-md-4 col-12 pb-0">
                                <div class="cart-sidebar">
                                    <div class="cart_totals">
                                        <table cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th class="product-name" colspan="2">CỘNG GIỎ HÀNG</th>
                                                </tr>
                                            </thead>
                                        </table>
                                        <table cellspacing="0" class="shop_table shop_table_responsive">
                                            <tbody>
                                                <tr class="order-total">
                                                    <td class="text-start pb-3">Tổng giá trị đơn hàng</td>
                                                    <td class="pb-3" data-title="Tổng">
                                                        <strong>
                                                            <span class="commerce-Price-amount amount total-price">{{number_format($totalPrice)}}đ</span>
                                                        </strong>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <div class="wc-proceed-to-checkout">
                                            <a href="/cart/thanh-toan" class="checkout-button button alt bg-gradient"> Tiến hành thanh toán</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="cart-footer-content after-cart-content relative mt-5">
                            @if(isset($available_deals) && $available_deals->count() > 0)
                            <div class="deal-suggestion bg-white border br-15 p-4 shadow-sm">
                                <h3 class="fs-20 fw-bold mb-4 text-uppercase"><i class="fa fa-gift text-danger me-2"></i>Ưu đãi mua kèm có thể bạn quan tâm</h3>
                                <div class="row">
                                    @foreach($available_deals as $deal)
                                        @foreach($deal->sales as $saledeal)
                                        <div class="col-12 col-md-6 mb-3">
                                            <div class="item_deal_cart d-flex align-items-center p-3 border br-10 hover-shadow transition-all">
                                                <div class="thumb_deal" style="width: 80px;">
                                                    <div class="skeleton--img-sm js-skeleton br-10" style="width: 80px; height: 80px;">
                                                        <img src="{{getImage($saledeal->product->image)}}" class="w-100 br-10 js-skeleton-img" alt="{{$saledeal->product->name}}">
                                                    </div>
                                                </div>
                                                <div class="info_deal ps-3 flex-grow-1">
                                                    <h5 class="fs-15 fw-600 mb-1 line-clamp-2">{{$saledeal->product->name}}</h5>
                                                    <div class="price_deal">
                                                        <span class="text-danger fw-bold fs-16">{{number_format($saledeal->price)}}đ</span>
                                                        <del class="fs-12 text-muted ms-2">{{number_format($saledeal->product->variant($saledeal->product->id)->price ?? 0)}}đ</del>
                                                    </div>
                                                    @if(isset($saledeal->available) && !$saledeal->available)
                                                        <div class="text-danger fs-12 mt-1">Deal đã hết quà hoặc hết kho</div>
                                                    @endif
                                                </div>
                                                <div class="action_deal">
                                                    <button type="button" class="btn btn-danger btn-sm px-3 br-20 fw-bold addDealCart" 
                                                        data-id="{{$saledeal->product->variant($saledeal->product->id)->id ?? ''}}"
                                                        data-deal-id="{{$deal->id}}"
                                                        data-limited="{{$deal->limited}}"
                                                        @if(isset($saledeal->available) && !$saledeal->available) disabled @endif
                                                    >
                                                        @if(isset($saledeal->available) && !$saledeal->available)
                                                            HẾT QUÀ
                                                        @else
                                                            THÊM NGAY
                                                        @endif
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                            <style>
                                .hover-shadow:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: #dc3545 !important; }
                                .transition-all { transition: all 0.3s ease; }
                                .br-15 { border-radius: 15px; }
                                .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
                            </style>
                            @endif
                        </div>
                        @else
                          <div class="text-center mb-5 mt-5"><p class="cart-empty">Chưa có sản phẩm nào trong giỏ hàng.</p><p class="return-to-shop"><a class="button bg_gradient" href="/">Quay trở lại cửa hàng</a></p></div>
                        @endif
                    </div>
                <!-- .col-inner -->
            </div>
            <!-- .large-12 -->
        </div>
        <!-- .row -->
    </div>
</section>
@endsection
@section('footer')
<script src="{{asset('js/cart-api-v1.js')}}"></script>
<script>
    window.dealCounts = @json($deal_counts ?? []);
</script>
<style>
    .is-deal-row { background-color: #fff9f9; }
    .is-deal-row .product-thumbnail { padding-left: 20px; }
    .is-deal-row .product-name::before { content: "↳ "; color: #dc3545; font-weight: bold; }
    .cart-loading { opacity: 0.6; pointer-events: none; }
    .btn-loading { position: relative; }
    .btn-loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid #fff;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    /* Cart Product Image Styles */
    .cart-product-image {
        width: 60px !important;
        height: 60px !important;
        min-width: 60px !important;
        max-width: 60px !important;
        min-height: 60px !important;
        max-height: 60px !important;
        flex: 0 0 60px !important;
        overflow: hidden;
        border-radius: 4px;
        background-color: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .cart-product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }
    
    .product-thumbnail {
        width: 60px;
        padding: 8px;
    }
    
    .product-thumbnail a {
        display: block;
        width: 100%;
        height: 100%;
    }
    
    /* Ensure consistent sizing on mobile */
    @media (max-width: 768px) {
        .cart-product-image {
            width: 50px !important;
            height: 50px !important;
            min-width: 50px !important;
            max-width: 50px !important;
            min-height: 50px !important;
            max-height: 50px !important;
            flex: 0 0 50px !important;
        }
        
        .product-thumbnail {
            width: 50px;
            padding: 4px;
        }
    }
</style>
<script>
    // Wait for CartAPI to be loaded
    $(document).ready(function() {
        // Check if CartAPI is available
        if (typeof CartAPI === 'undefined') {
            console.error('CartAPI is not loaded. Please ensure cart-api-v1.js is included.');
            return;
        }
        
        // Global error handler for AJAX timeouts
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            if (xhr.status === 0) {
                CartAPI.showError('Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.');
            } else if (xhr.status === 408 || thrownError === 'timeout') {
                CartAPI.showError('Request timeout. Vui lòng thử lại.');
            } else if (xhr.status === 500) {
                CartAPI.showError('Lỗi server. Vui lòng thử lại sau.');
            } else if (xhr.status === 503) {
                CartAPI.showError('Service unavailable. Vui lòng thử lại sau.');
            }
        });
        // Remove item from cart
        $('body').on('click', '.remove-item-cart', function(e) {
            e.preventDefault();
            var variantId = $(this).data('id');
            var $row = $(this).closest('tr');
            var $btn = $(this);
            var isDeal = $row.hasClass('is-deal-row');
            var mainProductId = $row.data('main-id');
            
            // Validate variantId
            if (!variantId || variantId <= 0) {
                CartAPI.showError('Variant ID không hợp lệ');
                return;
            }
            
            // Determine message based on item type
            var confirmMsg = isDeal 
                ? 'Bạn có chắc chắn muốn xóa sản phẩm deal này khỏi giỏ hàng?'
                : 'Bạn có chắc chắn muốn xóa sản phẩm này? Các sản phẩm deal sốc liên quan cũng sẽ bị xóa.';
            
            if (!confirm(confirmMsg)) {
                return;
            }
            
            // DEBUG: Log remove attempt
            console.log('[CART DEBUG] Remove item attempt:', {
                variantId: variantId,
                isDeal: isDeal,
                mainProductId: mainProductId,
                timestamp: new Date().toISOString()
            });
            
            $btn.prop('disabled', true).text('Đang xóa...');
            $('.cart-wrapper').addClass('cart-loading');
            
            CartAPI.removeItem(variantId)
                .done(function(response) {
                    if (response.success) {
                        // Get removed variant IDs from response
                        var removedVariantIds = response.data && response.data.removed_variant_ids 
                            ? response.data.removed_variant_ids 
                            : [variantId];
                        
                        // DEBUG: Log response data
                        console.log('[CART DEBUG] Response data:', {
                            response: response,
                            removedVariantIds: removedVariantIds,
                            variantId: variantId
                        });
                        
                        // Remove all rows for removed variant IDs
                        var removedCount = 0;
                        var $rowsToRemove = [];
                        
                        // DEBUG: Log all rows before filtering
                        var allRows = [];
                        $('tr[class*="item-cart-"]').each(function() {
                            var $tr = $(this);
                            var trVariantId = $tr.find('.remove-item-cart').data('id');
                            allRows.push({
                                variantId: trVariantId,
                                class: $tr.attr('class'),
                                row: $tr[0]
                            });
                            
                            if (trVariantId && removedVariantIds.includes(parseInt(trVariantId))) {
                                $rowsToRemove.push($tr);
                            }
                        });
                        
                        console.log('[CART DEBUG] All rows found:', allRows);
                        console.log('[CART DEBUG] Rows to remove:', $rowsToRemove.length);
                        
                        // Update cart summary first (before removing rows)
                        if (response.data && response.data.summary) {
                            var summary = response.data.summary;
                            // Update all total-price elements (table and sidebar)
                            $('.total-price').text(CartAPI.formatCurrency(summary.subtotal));
                            $('.count-cart').text(summary.total_qty || 0);
                            
                            // Also update checkout button state
                            if (summary.total_qty === 0) {
                                $('.checkout-button').prop('disabled', true).addClass('disabled');
                            } else {
                                $('.checkout-button').prop('disabled', false).removeClass('disabled');
                            }
                        }
                        
                        // Remove rows with animation
                        var totalRowsToRemove = $rowsToRemove.length;
                        var rowsRemoved = 0;
                        
                        if ($rowsToRemove.length > 0) {
                            $rowsToRemove.forEach(function($tr) {
                                $tr.fadeOut(300, function() {
                                    $(this).remove();
                                    removedCount++;
                                    rowsRemoved++;
                                    
                                    // After all animations complete, re-enable interactions
                                    if (rowsRemoved >= totalRowsToRemove) {
                                        // Remove loading state
                                        $('.cart-wrapper').removeClass('cart-loading');
                                        $btn.prop('disabled', false).text('×');
                                        
                                        // Re-enable all buttons and inputs in remaining rows
                                        $('tr[class*="item-cart-"]').each(function() {
                                            var $tr = $(this);
                                            $tr.find('.btn-plus, .btn-minus').prop('disabled', false).removeClass('btn-loading');
                                            $tr.find('.form-quatity').prop('disabled', false);
                                            $tr.find('.remove-item-cart').prop('disabled', false);
                                        });
                                    }
                                });
                            });
                        } else {
                            // Fallback: just remove the clicked row
                            $row.fadeOut(300, function() {
                                $(this).remove();
                                
                                // Remove loading state
                                $('.cart-wrapper').removeClass('cart-loading');
                                $btn.prop('disabled', false).text('×');
                                
                                // Re-enable all buttons and inputs in remaining rows
                                $('tr[class*="item-cart-"]').each(function() {
                                    var $tr = $(this);
                                    $tr.find('.btn-plus, .btn-minus').prop('disabled', false).removeClass('btn-loading');
                                    $tr.find('.form-quatity').prop('disabled', false);
                                    $tr.find('.remove-item-cart').prop('disabled', false);
                                });
                            });
                        }
                        
                        // Show success message
                        var successMsg = removedCount > 1 
                            ? 'Đã xóa ' + removedCount + ' sản phẩm khỏi giỏ hàng'
                            : 'Đã xóa sản phẩm khỏi giỏ hàng';
                        CartAPI.showSuccess(successMsg);
                        
                        // DEBUG: Log success
                        console.log('[CART DEBUG] Remove item success:', {
                            variantId: variantId,
                            removedVariantIds: removedVariantIds,
                            summary: summary,
                            removedCount: removedCount
                        });
                        
                        // Check if cart is empty, reload only if empty
                        if (summary.total_qty === 0) {
                            console.log('[CART DEBUG] Cart is empty, reloading...');
                            setTimeout(function() {
                                window.location.reload();
                            }, 500);
                        }
                    } else {
                        CartAPI.showError(response.message || 'Xóa sản phẩm thất bại');
                        $btn.prop('disabled', false).text('×');
                        $('.cart-wrapper').removeClass('cart-loading');
                    }
                })
                .fail(function(xhr, status, error) {
                    // DEBUG: Log error details
                    console.error('[CART DEBUG] Remove item failed:', {
                        variantId: variantId,
                        status: status,
                        error: error,
                        xhrStatus: xhr.status,
                        xhrStatusText: xhr.statusText,
                        responseText: xhr.responseText,
                        responseJSON: xhr.responseJSON,
                        headers: xhr.getAllResponseHeaders(),
                        timestamp: new Date().toISOString()
                    });
                    
                    var errorMsg = 'Có lỗi xảy ra, vui lòng thử lại';
                    
                    // Handle different error types
                    if (status === 'timeout') {
                        errorMsg = 'Request timeout. Vui lòng thử lại.';
                    } else if (xhr.status === 0) {
                        errorMsg = 'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.status === 500) {
                        errorMsg = 'Lỗi server. Vui lòng thử lại sau.';
                    } else if (xhr.status === 503) {
                        errorMsg = 'Service unavailable. Vui lòng thử lại sau.';
                    } else if (xhr.status === 419) {
                        errorMsg = 'Phiên đăng nhập đã hết hạn. Vui lòng tải lại trang.';
                        console.error('[CART DEBUG] CSRF token expired, reloading in 2 seconds...');
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else if (xhr.status === 404) {
                        errorMsg = 'API endpoint không tìm thấy. Vui lòng kiểm tra lại.';
                    } else if (xhr.status === 405) {
                        errorMsg = 'Phương thức HTTP không được phép.';
                    }
                    
                    CartAPI.showError(errorMsg);
                    $btn.prop('disabled', false).text('×');
                    $('.cart-wrapper').removeClass('cart-loading');
                    
                    // DEBUG: Show detailed error in console
                    console.error('[CART DEBUG] Error message shown to user:', errorMsg);
                });
        });

        // Increase quantity
        $('body').on('click', '.btn-plus', function(e) {
            e.preventDefault();
            var variantId = $(this).attr('data-id');
            var $input = $('#quantity-cart-' + variantId);
            var $btn = $(this);
            
            // Validate variantId
            if (!variantId || variantId <= 0) {
                CartAPI.showError('Variant ID không hợp lệ');
                return;
            }
            
            var currentVal = parseInt($input.val()) || 0;
            var newQty = currentVal + 1;
            
            $input.val(newQty);
            $btn.prop('disabled', true).addClass('btn-loading');
            $('.cart-wrapper').addClass('cart-loading');
            
            CartAPI.updateItem(variantId, newQty)
                .done(function(response) {
                    if (response.success && response.data) {
                        var data = response.data;
                        
                        // Update item subtotal
                        $('.item-total-' + variantId).text(CartAPI.formatCurrency(data.subtotal));
                        
                        // Update cart summary
                        if (data.summary) {
                            $('.total-price').text(CartAPI.formatCurrency(data.summary.subtotal));
                            $('.count-cart').text(data.summary.total_qty || 0);
                        }
                        
                        // Check Flash Sale Mixed Price
                        checkFlashSalePrice(variantId, newQty);
                    } else {
                        // Revert quantity on error
                        $input.val(currentVal);
                        CartAPI.showError(response.message || 'Cập nhật số lượng thất bại');
                    }
                })
                .fail(function(xhr, status, error) {
                    // Revert quantity on error
                    $input.val(currentVal);
                    var errorMsg = 'Có lỗi xảy ra, vui lòng thử lại';
                    
                    if (status === 'timeout') {
                        errorMsg = 'Request timeout. Vui lòng thử lại.';
                    } else if (xhr.status === 0) {
                        errorMsg = 'Không thể kết nối đến server.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.status === 500) {
                        errorMsg = 'Lỗi server. Vui lòng thử lại sau.';
                    }
                    
                    CartAPI.showError(errorMsg);
                })
                .always(function() {
                    $btn.prop('disabled', false).removeClass('btn-loading');
                    $('.cart-wrapper').removeClass('cart-loading');
                });
        });

        // Decrease quantity
        $('body').on('click', '.btn-minus', function(e) {
            e.preventDefault();
            var variantId = $(this).attr('data-id');
            var $input = $('#quantity-cart-' + variantId);
            var $btn = $(this);
            
            // Validate variantId
            if (!variantId || variantId <= 0) {
                CartAPI.showError('Variant ID không hợp lệ');
                return;
            }
            
            var currentVal = parseInt($input.val()) || 1;
            
            if (currentVal <= 1) {
                return;
            }
            
            var newQty = currentVal - 1;
            $input.val(newQty);
            $btn.prop('disabled', true).addClass('btn-loading');
            $('.cart-wrapper').addClass('cart-loading');
            
            CartAPI.updateItem(variantId, newQty)
                .done(function(response) {
                    if (response.success && response.data) {
                        var data = response.data;
                        
                        // Update item subtotal
                        $('.item-total-' + variantId).text(CartAPI.formatCurrency(data.subtotal));
                        
                        // Update cart summary
                        if (data.summary) {
                            $('.total-price').text(CartAPI.formatCurrency(data.summary.subtotal));
                            $('.count-cart').text(data.summary.total_qty || 0);
                        }
                    } else {
                        // Revert quantity on error
                        $input.val(currentVal);
                        CartAPI.showError(response.message || 'Cập nhật số lượng thất bại');
                    }
                })
                .fail(function(xhr, status, error) {
                    // Revert quantity on error
                    $input.val(currentVal);
                    var errorMsg = 'Có lỗi xảy ra, vui lòng thử lại';
                    
                    if (status === 'timeout') {
                        errorMsg = 'Request timeout. Vui lòng thử lại.';
                    } else if (xhr.status === 0) {
                        errorMsg = 'Không thể kết nối đến server.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.status === 500) {
                        errorMsg = 'Lỗi server. Vui lòng thử lại sau.';
                    }
                    
                    CartAPI.showError(errorMsg);
                })
                .always(function() {
                    $btn.prop('disabled', false).removeClass('btn-loading');
                    $('.cart-wrapper').removeClass('cart-loading');
                });
        });

        // Function to check Flash Sale price when quantity changes
        function checkFlashSalePrice(variantId, quantity) {
            if (typeof FlashSaleMixedPrice === 'undefined') {
                return; // FlashSaleMixedPrice not loaded
            }
            
            // Get product_id from the row
            var $row = $('.item-cart-' + variantId);
            var productId = $row.attr('data-main-id');
            
            if (!productId) {
                return;
            }
            
            // Show warning row
            var $warningRow = $('.flash-sale-warning-row-' + variantId);
            var $warningContainer = $('.flash-sale-warning-container-' + variantId);
            
            // Call FlashSaleMixedPrice to calculate price với callback để cập nhật tổng tiền
            FlashSaleMixedPrice.calculatePriceWithQuantity(
                parseInt(productId),
                parseInt(variantId),
                quantity,
                '.item-total-' + variantId, // Price display selector
                '.flash-sale-warning-container-' + variantId, // Warning container
                function(priceData) {
                    // Callback: Cập nhật tổng tiền sau khi tính giá thành công
                    FlashSaleMixedPrice.updateTotalOrderPrice();
                    
                    // Show/hide warning row based on warning content
                    setTimeout(function() {
                        if ($warningContainer.html().trim() !== '') {
                            $warningRow.show();
                        } else {
                            $warningRow.hide();
                        }
                    }, 100);
                }
            );
        }
        
        // Manual quantity input change
        $('body').on('blur', '.form-quatity', function() {
            var variantId = $(this).closest('tr').find('.btn-plus').attr('data-id');
            var $input = $(this);
            
            // Validate variantId
            if (!variantId || variantId <= 0) {
                CartAPI.showError('Variant ID không hợp lệ');
                return;
            }
            
            var newQty = parseInt($input.val()) || 1;
            
            if (newQty < 1) {
                newQty = 1;
                $input.val(1);
            }
            
            $input.prop('disabled', true);
            $('.cart-wrapper').addClass('cart-loading');
            
            CartAPI.updateItem(variantId, newQty)
                .done(function(response) {
                    if (response.success && response.data) {
                        var data = response.data;
                        
                        // Update item subtotal
                        $('.item-total-' + variantId).text(CartAPI.formatCurrency(data.subtotal));
                        
                        // Update cart summary
                        if (data.summary) {
                            $('.total-price').text(CartAPI.formatCurrency(data.summary.subtotal));
                            $('.count-cart').text(data.summary.total_qty || 0);
                        }
                        
                        // Check Flash Sale Mixed Price
                        checkFlashSalePrice(variantId, newQty);
                    } else {
                        CartAPI.showError(response.message || 'Cập nhật số lượng thất bại');
                        // Reload to get correct value
                        window.location.reload();
                    }
                })
                .fail(function(xhr, status, error) {
                    var errorMsg = 'Có lỗi xảy ra, vui lòng thử lại';
                    
                    if (status === 'timeout') {
                        errorMsg = 'Request timeout. Vui lòng thử lại.';
                    } else if (xhr.status === 0) {
                        errorMsg = 'Không thể kết nối đến server.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.status === 500) {
                        errorMsg = 'Lỗi server. Vui lòng thử lại sau.';
                    }
                    
                    CartAPI.showError(errorMsg);
                    // Reload after a delay to show error message
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                })
                .always(function() {
                    $input.prop('disabled', false);
                    $('.cart-wrapper').removeClass('cart-loading');
                });
        });

        // Add deal to cart
        $('body').on('click', '.addDealCart', function() {
            var variantId = $(this).attr('data-id');
            var dealId = $(this).attr('data-deal-id');
            var limited = parseInt($(this).attr('data-limited'));
            var currentCount = window.dealCounts && window.dealCounts[dealId] ? window.dealCounts[dealId] : 0;
            var $btn = $(this);

            // Validate variantId
            if (!variantId || variantId <= 0) {
                CartAPI.showError('Variant ID không hợp lệ');
                return;
            }

            if (currentCount >= limited) {
                CartAPI.showError('Bạn đã đạt giới hạn tối đa ' + limited + ' sản phẩm cho chương trình Deal này.');
                return;
            }

            $btn.prop('disabled', true).addClass('btn-loading').html('<span class="spinner-border spinner-border-sm"></span>');
            
            CartAPI.addItem(variantId, 1, true)
                .done(function(response) {
                    if (response.success) {
                        CartAPI.showSuccess('Đã thêm sản phẩm deal vào giỏ hàng');
                        // Reload to update deal counts and cart
                        setTimeout(function() {
                            window.location.reload();
                        }, 500);
                    } else {
                        CartAPI.showError(response.message || 'Thêm sản phẩm thất bại');
                        $btn.prop('disabled', false).removeClass('btn-loading').text('THÊM NGAY');
                    }
                })
                .fail(function(xhr, status, error) {
                    var errorMsg = 'Có lỗi xảy ra, vui lòng thử lại';
                    
                    if (status === 'timeout') {
                        errorMsg = 'Request timeout. Vui lòng thử lại.';
                    } else if (xhr.status === 0) {
                        errorMsg = 'Không thể kết nối đến server.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.status === 500) {
                        errorMsg = 'Lỗi server. Vui lòng thử lại sau.';
                    }
                    
                    CartAPI.showError(errorMsg);
                    $btn.prop('disabled', false).removeClass('btn-loading').text('THÊM NGAY');
                });
        });
    });
</script>
@endsection
