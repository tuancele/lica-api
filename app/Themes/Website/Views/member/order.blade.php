@extends('Website::layout',['image' => ''])
@section('title', 'Chi tiết đơn hàng')
@section('description','Chi tiết đơn hàng')
@section('content')
<section class="mt-4">
	<div class="wrapper-container2 mb-5">
		<div class="row">
			<div class="col-12 col-md-4">
				<div class="breadcrumb d-block d-md-none">
		            <ol>
		                <li><a href="/">Trang chủ</a></li>
		                <li><a href="{{route('account.profile')}}">Tài khoản</a></li>
                        <li><a href="{{route('account.orders')}}">Đơn hàng</a></li>
		            </ol>
		        </div>
				@include('Website::member.sidebar',['active' => 'orders'])
			</div>
			<div class="col-12 col-md-8 mt-4 mt-md-0">
				<div class="breadcrumb d-none d-md-block">
		            <ol>
		                <li><a href="/">Trang chủ</a></li>
		                <li><a href="{{route('account.profile')}}">Tài khoản</a></li>
                        <li><a href="{{route('account.orders')}}">Đơn hàng</a></li>
		            </ol>
		        </div>
		        <h1 class="title_account">Chi tiết đơn hàng #{{$detail->code}}</h1>
                <p>Ngày tạo: <strong>{{formatDate($detail->created_at)}}</strong></p>
                <div class="align-center">
		        	<div class="payment_status me-5">
		        		Trạng thái thanh toán: @if($detail->payment == 2)<strong class="color-red">Hoàn trả</strong>@elseif($detail->payment == 1)<strong class="color-green">Đã thanh toán</strong>@else<strong class="color-orange">Chưa thanh toán</strong>@endif
			        </div>
			        <div class="shipping_status">
			        	Trạng thái vận chuyển: @if($detail->ship == 3)<strong class="color-red">Hoàn trả</strong>@elseif($detail->ship == 2)<strong class="color-green">Đã nhận</strong>@elseif($detail->ship)<strong class="color-blue">Đã giao hàng</strong>@else<strong class="color-orange">Chưa giao hàng</strong>@endif
			        </div>
		        </div>
		        <div class="row mt-3">
		        	<div class="col-12 col-md-12">
		        		<h6 class="text-uppercase">Địa chỉ giao hàng</h6>
		        		<div class="border br-10 pe-3 ps-3 pt-2 pb-2 min-height-110">
		        			<p class="mb-1"><strong>{{$detail->name}}</strong> | <strong>{{$detail->phone}}</strong></p>
		        			<p class="mb-1"><strong>{{$detail->email}}</strong></p>
		        			<p class="mb-1">{{$detail->address}}, {{$detail->ward->name.','??''}} {{$detail->district->name.','??''}} {{$detail->province->name??''}}</p>
		        		</div>
		        	</div>
		        	<div class="col-12 col-md-12 mt-3">
		        		<h6 class="text-uppercase">Ghi chú</h6>
		        		<div class="border br-10 pe-3 ps-3 pt-2 pb-2 min-height-110">
		        			@if($detail->remark != ""){{$detail->remark}} @else Không có ghi chú @endif
		        		</div>
		        	</div>
		        </div>
		        @if($detail->detail->count() > 0)
		        <div class="border br-10 pe-3 ps-3 pt-2 pb-2 mt-4">
			        <table class="table">
					  <thead>
					    <tr>
					      <th scope="col" colspan="2" style="width:55%" class="fw-bold">Sản phẩm</th>
					      <th scope="col" style="width:15%"  class="fw-bold">Đơn giá</th>
					      <th scope="col" style="width:15%" class="fw-bold">Số lượng</th>
					      <th scope="col" style="width:15%" class="fw-bold">Tổng</th>
					    </tr>
					  </thead>
					  <tbody style="border-top:1px solid currentColor">
					  	@foreach($detail->detail as $product)
                        @php
                            $item = App\Modules\Post\Models\Post::select('slug')->where('id',$product->product_id)->first();
                        @endphp
					  	<tr>   
                            <td style="width:10%">
                                <img src="{{getImage($product->image)}}" width="50px"> 
                            </td>
					  		<td style="width:35%">
                                <a href="{{asset($item->slug)}}" target="_blank">{{$product->name}}</a>
                                <p style="margin-bottom:0px">@if($product->color)<span class="me-3" style="margin-right:15px">Màu sắc: <strong>{{$product->color->name}}</strong></span>@endif @if($product->size)<span>Kích thước: <strong>{{$product->size->name}}{{$product->size->unit}}</strong></span>@endif</p>
					  		</td>
					  		<td style="width:15%;display: table-cell;vertical-align: middle;">{{formatPrice($product->price)}}</td>
					  		<td style="width:15%;display: table-cell;vertical-align: middle;">{{$product->qty}}</td>
					  		<td style="width:15%;display: table-cell;vertical-align: middle;">{{formatPrice($product->qty*$product->price)}}</td>
					  	</tr>
					  	@endforeach
					  	<tr>
					  		<td class="pt-3" style="border-bottom:none" colspan="4"><strong>Tạm tính</strong></td>
					  		<td class="pt-3" style="border-bottom:none"><strong>{{formatPrice($detail->total)}}</strong></td>
					  	</tr>
                        <tr>
					  		<td style="border-bottom:none" colspan="4"><strong>Phí vận chuyển</strong></td>
					  		<td style="border-bottom:none"><strong>{{formatPrice($detail->fee_ship)}}</strong></td>
					  	</tr>
                        <tr>
					  		<td style="border-bottom:none" colspan="4"><strong>Tổng cộng</strong></td>
					  		<td style="border-bottom:none"><strong>{{formatPrice($detail->total + $detail->fee_ship)}}</strong></td>
					  	</tr>
					  </tbody>
					</table>
				</div>
				@endif
			</div>
		</div>
	</div>	
</section>
@endsection