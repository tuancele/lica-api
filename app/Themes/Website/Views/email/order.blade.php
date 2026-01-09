@php 
	$order = App\Modules\Order\Models\Order::where('code',$data['body'])->first();
@endphp
<div style="font:15px/1.35 'Helvetica Neue',Arial,sans-serif;color:#333333">
	<div style="background-color:#f0f0f0;padding:10px">
		<div style="border:1px solid #d8dfe6;background-color:#ffffff;padding:20px 10px 30px 10px">
			<div style="margin-bottom:10px">
				<a href="{{asset('')}}" title="{{getConfig('company_name')}}" target="_blank">
					<img style="width:auto;height:30px;border:0" alt="{{getConfig('company_name')}}" src="{{getImage(getConfig('logo'))}}" class="CToWUd">
				</a>
			</div>
			<div>
				<h1 style="color:#3f74b8;font-size:18px;border-bottom:solid 1px #d8dfe6;margin-top:0;padding-top:0;padding-bottom:5px;margin-bottom:20px">
					Đơn đặt hàng {{$order->code}}
					<span style="float:right;color:#333;font-size:13px;font-weight: normal;">Ngày: {{date('H:i:s d-m-Y',strtotime($order->created_at))}}</span>
				</h1>
				<div style="width:100%;overflow: hidden;font-size:13px">
					<div style="width: 48%;float: left;padding-right:15px">
						<h3 style="margin-top: 0px;margin-bottom: 10px;font-size: 15px;">Thông tin người đặt</h3>
						<p style="margin-bottom: 0px;margin-top: 0px">Họ tên: {{$order->name}}</p>
						<p style="margin-bottom: 0px;margin-top: 0px">Điện thoại: {{$order->phone}}</p>
						<p style="margin-bottom: 0px;margin-top: 0px">Địa chỉ: {{$order->address}}, @if($order->ward) {{$order->ward->name}}, @endif @if($order->district) {{$order->district->name}}, @endif @if($order->province) {{$order->province->name}} @endif
						</p>
					</div>
				</div>
				<table style="margin-top:30px;font-size:13px;border: 1px solid #f4f4f4;width: 100%;border-spacing: 0;border-collapse: collapse;">
					<tr style="background-color: #f9f9f9;">
						<th style="border: 1px solid #f4f4f4;padding: 8px;text-align: left;">STT</th>
						<th colspan="2" style="border: 1px solid #f4f4f4;padding: 8px;text-align: left;">Sản phẩm</th>
						<th style="border: 1px solid #f4f4f4;padding: 8px;text-align: left;">Số lượng</th>
						<th style="border: 1px solid #f4f4f4;padding: 8px;text-align: left;">Đơn giá</th>
						<th style="border: 1px solid #f4f4f4;padding: 8px;text-align: left;">Thành tiền</th>
					</tr>
					@php $products = App\Modules\Order\Models\OrderDetail::where('order_id',$order->id)->get(); @endphp
					@if($products->count() > 0)
					@foreach($products as $key => $product)
					<tr>
						<td style="border: 1px solid #f4f4f4;padding: 8px;">{{$key+1}}</td>
						<td style="border: 1px solid #f4f4f4;padding: 8px;"><img width="70px" src="{{getImage($product->image)}}" alt="{{$product->name}}"></td>
						<td style="border: 1px solid #f4f4f4;padding: 8px;">
							{{$product->name}}
							<p style="margin-bottom:0px">@if($product->color)<span class="me-3" style="margin-right:15px">Màu sắc: {{$product->color->name}}</span>@endif @if($product->size)<span>Kích thước: {{$product->size->name}}{{$product->size->unit}}</span>@endif</p>
						</td>
						<td style="border: 1px solid #f4f4f4;padding: 8px;">{{$product->qty}}</td>
						<td style="border: 1px solid #f4f4f4;padding: 8px;font-weight: 600;color:red">{{number_format($product->price)}} VNĐ</td>
						<td style="border: 1px solid #f4f4f4;padding: 8px;font-weight: 600;color:red">{{number_format($product->price * $product->qty)}} VNĐ</td>
					</tr>
					@endforeach
					@endif
				</table>
				<div style="margin-top: 10px;font-weight: 600;">
					Tạm tính: <span style="color:red;font-size:14px">{{number_format($order->total)}} VNĐ</span>
				</div>
				@if($order->sale != 0)
				<div style="margin-top: 6px;font-weight: 600;">
					Khuyến mại: <span style="color:blue;font-size:14px">-{{number_format($order->sale)}} VNĐ</span>
				</div>
				@endif
				<div style="margin-top: 5px;font-weight: 600;">
					Tổng tiền: <span style="color:red;font-size:16px">{{number_format($order->total - $order->sale)}} VNĐ</span>
				</div>
			</div>
		</div>
		<div style="padding:10px 0;color:#333333;font-size:13px">
			<div style="margin-bottom:5px">
				<a href="{{asset('')}}" style="color:#333333;text-decoration:none;font-size:16px;font-weight:600;" target="_blank">{{getConfig('company_name')}}</a>
			</div>
			<div style="margin-bottom:20px">
				Email:
				<a href="mailto:{{getConfig('company_email')}}" style="color:#333333;text-decoration:none" target="_blank">{{getConfig('company_email')}}</a>
				<br>
				Điện thoại:
				<a href="tel:{{getConfig('company_phone')}}" style="color:#333333;text-decoration:none" target="_blank">{{getConfig('company_phone')}}</a>
				<p style="color:#333333;margin-top:0px">Địa chỉ:{{getConfig('company_address')}}</p>
			</div>
		</div>
	</div>
</div>