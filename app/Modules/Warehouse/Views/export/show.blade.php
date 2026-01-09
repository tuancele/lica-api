<div id="showDetail">
<h4 style="text-align: center;">CHI TIẾT PHIẾU XUẤT HÀNG</h4>
<hr/>
<table style="width: 100%;">
	<tr style="padding-bottom: 10px;">
		<td width="30%"><strong>Mã đơn hàng</strong></td>
		<td width="70%">{{$detail->code}}</td>
	</tr>
	<tr style="padding-bottom: 10px;">
		<td width="30%"><strong>Người xuất</strong></td>
		<td width="70%">@if(isset($detail->user)) {{$detail->user->name}} @endif</td>
	</tr>
	<tr style="padding-bottom: 10px;">
		<td width="30%"><strong>Nội dung</strong></td>
		<td width="70%">{{$detail->subject}}</td>
	</tr>
	<tr style="padding-bottom: 10px;">
		<td width="30%"><strong>Ngày xuất</strong></td>
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
<div class="form-group" style="overflow: hidden;">
	<button class="btn btn-danger pull-right closeModal" type="button" style="margin-left: 5px;" data-dismiss="modal">Đóng</button>
	<a class="btn btn-success pull-right" target="_blank" href="/admin/export-goods/print/{{$detail->id}}"><i class="fa fa-print" aria-hidden="true"></i> In</a>
</div>