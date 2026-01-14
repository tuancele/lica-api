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
                                                            <div class="skeleton--img-sm js-skeleton">
                                                                <img
                                                                    src="{{getImage($product->image)}}"
                                                                    data-src="{{getImage($product->image)}}"
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
                                                        <div class="show-for-small mobile-product-price">
                                                            <span class="commerce-Price-amount amount">{{number_format($variant['price'])}}đ</span>
                                                        </div>
                                                    </td>

                                                    <td class="product-price" data-title="Giá">
                                                        <span class="commerce-Price-amount amount">{{number_format($variant['price'])}}đ</span>
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
                                                        <span class="commerce-Price-amount amount item-total-{{$variant['item']['id']}}">{{number_format($variant['price']*$variant['qty'])}}đ</span>
                                                    </td>
                                                </tr>
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
                                                </div>
                                                <div class="action_deal">
                                                    <button type="button" class="btn btn-danger btn-sm px-3 br-20 fw-bold addDealCart" 
                                                        data-id="{{$saledeal->product->variant($saledeal->product->id)->id ?? ''}}"
                                                        data-deal-id="{{$deal->id}}"
                                                        data-limited="{{$deal->limited}}">THÊM NGAY</button>
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
<script>
    window.dealCounts = @json($deal_counts ?? []);
</script>
<style>
    .is-deal-row { background-color: #fff9f9; }
    .is-deal-row .product-thumbnail { padding-left: 20px; }
    .is-deal-row .product-name::before { content: "↳ "; color: #dc3545; font-weight: bold; }
</style>
<script>
    $('body').on('click','.remove-item-cart',function(){
      var id = $(this).data('id');
      $.ajax({
          type: 'post',
          url: '{{route("cart.del")}}',
          data: {id:id},
          headers:
          {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (res) {
            window.location.reload();
          },
          error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
            }
      })
    });
    $('body').on('click','.btn-plus',function(e){
    e.preventDefault();
    var id = $(this).attr('data-id');
    var input = $('#quantity-cart-'+id+'');
    var currentVal = parseInt(input.val());
    var qty = 1;
    if (!isNaN(currentVal)) {
      qty = currentVal + 1;
    }
    input.val(qty);
    $.ajax({
      type: "post",
      url: "{{route('cart.update')}}",
      data: { id: id, qty: qty },
      headers:
      {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (res) {
        $('.item-total-'+id+'').html(res.subtotal+'₫');
        $(".total-price").html(res.price+'₫');
        $('.count-cart').html(res.total);
      },
      error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
        }
  });
});
    $('body').on('click','.addDealCart',function(){
        var id = $(this).attr('data-id');
        var dealId = $(this).attr('data-deal-id');
        var limited = parseInt($(this).attr('data-limited'));
        var currentCount = window.dealCounts[dealId] || 0;

        if(currentCount >= limited) {
            alert('Bạn đã đạt giới hạn tối đa ' + limited + ' sản phẩm cho chương trình Deal này.');
            return;
        }

        var qty = 1;
        var btn = $(this);
        $.ajax({
            type: 'post',
            url: '{{route("cart.add")}}',
            data: {id:id,qty:qty,is_deal:1},
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function () {
                btn.prop('disabled',true).html('<span class="spinner-border spinner-border-sm"></span>');
            },
            success: function (res) {
                if(res.status == 'success'){
                    window.location.reload();
                }
            }
        });
    });

    $('body').on('click','.btn-minus',function(e){
    e.preventDefault();
    var id = $(this).attr('data-id');
    var input = $('#quantity-cart-'+id+'');
    var currentVal = parseInt(input.val());
    var qty = 1;
    if (!isNaN(currentVal) && currentVal > 1) {
        input.val(currentVal - 1);
        qty = currentVal - 1;
    }
    input.val(qty);
    $.ajax({
      type: "post",
      url: "{{route('cart.update')}}",
      data: { id: id, qty: qty },
      headers:
      {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (res) {
        $('.item-total-'+id+'').html(res.subtotal+'<span class="commerce-Price-currencySymbol">₫</span>');
        $(".total-price").html(res.price+'<span class="commerce-Price-currencySymbol">₫</span>');
        $('.count-cart').html(res.total);
      },
      error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
        }
    });
});
</script>
@endsection
