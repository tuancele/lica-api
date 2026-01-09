<!DOCTYPE html>
<html>
<head>
	<title>CHI TIẾT PHIẾU NHẬP HÀNG</title>
	<script src="/public/admin/plugins/jQuery/jQuery-2.1.4.min.js"></script>
	<link href="/public/admin/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<script src="/public/admin/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
</head>
<body>
<div id="showDetail" style="padding:30px">
<h4 style="text-align: center;">CHI TIẾT PHIẾU NHẬP HÀNG</h4>
<hr/>
<table style="width: 100%;">
	<tr style="padding-bottom: 10px;">
		<td width="30%"><strong>Mã đơn hàng</strong></td>
		<td width="70%">{{$detail->code}}</td>
	</tr>
	<tr style="padding-bottom: 10px;">
		<td width="30%"><strong>Người nhập</strong></td>
		<td width="70%">@if(isset($detail->user)) {{$detail->user->name}} @endif</td>
	</tr>
	<tr style="padding-bottom: 10px;">
		<td width="30%"><strong>Nội dung</strong></td>
		<td width="70%">{{$detail->subject}}</td>
	</tr>
	<tr style="padding-bottom: 10px;">
		<td width="30%"><strong>Ngày nhập</strong></td>
		<td width="70%">{{date('H:i:s  d-m-Y',strtotime($detail->created_at))}}</td>
	</tr>
</table>
<table class="table table-bordered table-striped" style="margin-top: 15px;">
	<tr>
		<th>STT</th>
		<th>Sản phẩm</th>
		<th>Màu sắc</th>
		<th>Kích thước</th>
		<th>Đơn giá</th>
		<th>Số lượng</th>
		<th>Thành tiền</th>
	</tr>
	@if($products->count() > 0)
	@php $total=0 @endphp
	@foreach($products as $key => $product)
	@php 
		$subtotal = $product->price * $product->qty;
		$total = $total + $subtotal;
	@endphp
	<tr>
		<td>{{$key + 1}}</td>
		<td width="40%">{{$product->variant->product->name}}</td>
		<td width="10%">{{$product->variant->color->name}}</td>
		<td width="10%">{{$product->variant->size->name}}{{$product->variant->size->unit}}</td>
		<td width="10%">{{number_format($product->price)}}đ</td>
		<td width="10%">{{$product->qty}}</td>
		<td width="10%">{{number_format($subtotal)}}đ</td>
	</tr>
	@endforeach
	<tr>
		<th></th>
		<th colspan="5">Tổng giá trị đơn hàng</th>
		<th>{{number_format($total)}}đ</th>
	</tr>
	@endif
</table>
<table style="width: 100%">
	<tr>
		<td width="70%"></td>
		<td width="30%">
			<p><strong>Người xuất hóa đơn</strong></p>
			<p>(Ký và ghi rõ họ tên)</p>
		</td>
	</tr>
</table>
</div>
</body>
<script type="text/javascript">
	window.print();
</script>
</html>