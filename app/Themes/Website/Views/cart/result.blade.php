@extends('Website::layout')
@section('title','Đặt hàng thành công')
@section('description','Đặt hàng thành công')
@section('content')
<section class="pt-5 pb-5">
    <div class="container-lg">
    <div id="content" class="content-area page-wrapper" role="main">
        <div class="row row-main">
            <div class="col-12 col">
                <div class="col-inner">
                    <div class="woocommerce">
                        <div class="row">
                            <div class="col-12 col-md-7 mb-3 mb-md-0">
                                <section class="woocommerce-order-details">
                                    <h2 class="title_detail">Chi tiết đơn hàng</h2>

                                    <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
                                        <thead>
                                            <tr>
                                                <th class="woocommerce-table__product-name product-name">Sản phẩm</th>
                                                <th class="woocommerce-table__product-table product-total">Tổng</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @if($products->count() > 0)
                                            @foreach($products as $product)
                                            @php $slug = App\Modules\Product\Models\Product::select('slug')->where('id',$product->product_id)->first(); @endphp
                                            <tr class="woocommerce-table__line-item order_item">
                                                <td class="woocommerce-table__product-name product-name">
                                                    <a href="{{getSlug($slug->slug)}}" target="_blank">{{$product->name}}</a> <strong class="product-quantity">× {{$product->qty}}</strong>
                                                </td>

                                                <td class="woocommerce-table__product-total product-total">
                                                    <span class="woocommerce-Price-amount amount">{{number_format($product->price * $product->qty)}}<span class="woocommerce-Price-currencySymbol">₫</span></span>
                                                </td>
                                            </tr>
                                            @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </section>
                            </div>

                            <div class="col-12 col-md-5">
                                <div class="is-well col-inner entry-content box_success">
                                    <p class="success-color woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><strong>Cảm ơn bạn đã đặt hàng. Đơn hàng của bạn đã được ghi nhận.</strong></p>

                                    <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
                                        <li class="woocommerce-order-overview__order order">Mã đơn hàng: <strong>{{$order->code}}</strong></li>

                                        <li class="woocommerce-order-overview__date date">Ngày: <strong>{{formatDate($order->created_at)}}</strong></li>
                                        <li class="woocommerce-order-overview__total total">
                                            Phí vận chuyển:
                                            <strong>
                                                <span class="woocommerce-Price-amount amount">{{number_format($order->fee_ship)}}<span class="woocommerce-Price-currencySymbol">₫</span></span>
                                            </strong>
                                        </li>
                                        @if($order->sale != 0)
                                        <li class="woocommerce-order-overview__total total">
                                            Khuyến mại:
                                            <strong>
                                                <span class="woocommerce-Price-amount amount">-{{number_format($order->sale)}}<span class="woocommerce-Price-currencySymbol">₫</span></span>
                                            </strong>
                                        </li>
                                        @endif
                                        <li class="woocommerce-order-overview__total total">
                                            Tổng cộng:
                                            <strong>
                                                <span class="woocommerce-Price-amount amount">{{number_format($order->total + $order->fee_ship - $order->sale)}}<span class="woocommerce-Price-currencySymbol">₫</span></span>
                                            </strong>
                                        </li>
                                    </ul>
                                    <h3>Thông tin người mua</h3>
                                    <ul>
                                        <li>
                                            Họ tên:
                                            <strong>
                                                <span class="woocommerce-Price-amount amount">{{$order->name}}</span>
                                            </strong>
                                        </li>
                                        <li>
                                            Điện thoại:
                                            <strong>
                                                <span class="woocommerce-Price-amount amount">{{$order->phone}}</span>
                                            </strong>
                                        </li>
                                        @if($order->email != "")
                                        <li>
                                            Email:
                                            <strong>
                                                <span class="woocommerce-Price-amount amount">{{$order->email}}</span>
                                            </strong>
                                        </li>
                                        @endif
                                        <li>
                                            Địa chỉ:
                                            <strong>
                                                <span class="woocommerce-Price-amount">{{$order->address}}, @if($order->ward) {{$order->ward->name}}, @endif @if($order->district) {{$order->district->name}}, @endif @if($order->province) {{$order->province->name}} @endif</span>
                                            </strong>
                                        </li>
                                    </ul>

                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- .col-inner -->
            </div>
            <!-- .large-12 -->
        </div>
        <!-- .row -->
    </div>
</div>
</section>
<style>
    .box_success{
        border: 1px solid #ccc;
        background-color: #fff;
        padding: 20px;
    }
    .box_success .success-color{
        color: green;
    }
    .box_success  ul li{
        margin-bottom: 10px;
        list-style: initial;
        font-size:14px;
     }
     .box_success  ul{
        padding-left: 20px;
     }
     .box_success  ul  h3{

     }
</style>
@endsection
