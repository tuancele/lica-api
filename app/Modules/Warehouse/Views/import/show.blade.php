<div id="showDetail">
<div class="receipt-container" style="max-width: 800px; margin: 0 auto; padding: 20px; background: #fff;">
	<!-- QR Code và Header trên cùng một hàng -->
	<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px;">
		<!-- QR Code bên trái -->
		<div class="qr-code-header" style="text-align: center; flex-shrink: 0;">
			@if(isset($qr_code) && !empty($qr_code))
			<img src="{{$qr_code}}" alt="QR Code" style="width: 120px; height: 120px; border: 1px solid #ddd; padding: 5px; background: #fff; display: block; margin: 0 auto;" onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{urlencode($view_url)}}&format=png&margin=1'">
			@else
			<img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{urlencode($view_url)}}&format=png&margin=1" alt="QR Code" style="width: 120px; height: 120px; border: 1px solid #ddd; padding: 5px; background: #fff; display: block; margin: 0 auto;">
			@endif
			<p style="margin: 5px 0 0 0; font-size: 11px; color: #666;">Quét mã để xem chi tiết</p>
		</div>

		<!-- Header ở giữa -->
		<div class="receipt-header" style="text-align: center; flex-grow: 1;">
			<h2 style="margin: 0; font-size: 24px; font-weight: bold; color: #333;">PHIẾU NHẬP HÀNG</h2>
			<p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">Mã phiếu: <strong>{{$receipt_code}}</strong></p>
		</div>

		<!-- Khoảng trống bên phải để cân đối -->
		<div style="width: 120px; flex-shrink: 0;"></div>
	</div>

	<!-- Thông tin đơn hàng -->
	<div class="receipt-info" style="margin-bottom: 25px;">
		<table style="width: 100%; border-collapse: collapse;">
			<tr>
				<td style="padding: 8px 0; width: 35%; font-weight: bold; color: #333;">Mã đơn hàng:</td>
				<td style="padding: 8px 0; width: 65%;">{{$detail->code}}</td>
			</tr>
			<tr>
				<td style="padding: 8px 0; font-weight: bold; color: #333;">Người nhập:</td>
				<td style="padding: 8px 0;">@if(isset($detail->user)) {{$detail->user->name}} @else - @endif</td>
			</tr>
			<tr>
				<td style="padding: 8px 0; font-weight: bold; color: #333;">Nội dung:</td>
				<td style="padding: 8px 0;">{{$detail->subject}}</td>
			</tr>
			@if($vat_invoice)
			<tr>
				<td style="padding: 8px 0; font-weight: bold; color: #333;">Số hóa đơn VAT:</td>
				<td style="padding: 8px 0;">{{$vat_invoice}}</td>
			</tr>
			@endif
			<tr>
				<td style="padding: 8px 0; font-weight: bold; color: #333;">Ngày nhập:</td>
				<td style="padding: 8px 0;">{{date('H:i:s  d/m/Y',strtotime($detail->created_at))}}</td>
			</tr>
		</table>
	</div>

	<!-- Danh sách sản phẩm -->
	<div class="receipt-products" style="margin-bottom: 25px;">
		<table class="table table-bordered" style="width: 100%; border-collapse: collapse; margin: 0;">
			<thead>
				<tr style="background-color: #f5f5f5;">
					<th style="padding: 10px; text-align: center; border: 1px solid #ddd; width: 5%;">STT</th>
					<th style="padding: 10px; text-align: left; border: 1px solid #ddd; width: 40%;">Sản phẩm</th>
					<th style="padding: 10px; text-align: center; border: 1px solid #ddd; width: 15%;">Phân loại</th>
					<th style="padding: 10px; text-align: right; border: 1px solid #ddd; width: 12%;">Đơn giá</th>
					<th style="padding: 10px; text-align: center; border: 1px solid #ddd; width: 10%;">Số lượng</th>
					<th style="padding: 10px; text-align: right; border: 1px solid #ddd; width: 18%;">Thành tiền</th>
				</tr>
			</thead>
			<tbody>
				@if($products->count() > 0)
				@php $total=0 @endphp
				@foreach($products as $key => $product)
				@php 
					$subtotal = $product->price * $product->qty;
					$total = $total + $subtotal;
				@endphp
				<tr>
					<td style="padding: 8px; text-align: center; border: 1px solid #ddd;">{{$key + 1}}</td>
					<td style="padding: 8px; text-align: left; border: 1px solid #ddd;">{{$product->variant->product->name}}</td>
					<td style="padding: 8px; text-align: center; border: 1px solid #ddd;">{{$product->variant->option1_value ?? 'Mặc định'}}</td>
					<td style="padding: 8px; text-align: right; border: 1px solid #ddd;">{{number_format($product->price, 0, ',', '.')}} đ</td>
					<td style="padding: 8px; text-align: center; border: 1px solid #ddd;">{{number_format($product->qty, 0, ',', '.')}}</td>
					<td style="padding: 8px; text-align: right; border: 1px solid #ddd; font-weight: bold;">{{number_format($subtotal, 0, ',', '.')}} đ</td>
				</tr>
				@endforeach
				<tr style="background-color: #f9f9f9; font-weight: bold;">
					<td colspan="5" style="padding: 12px; text-align: right; border: 1px solid #ddd;">Tổng giá trị đơn hàng:</td>
					<td style="padding: 12px; text-align: right; border: 1px solid #ddd; font-size: 16px; color: #d9534f;">{{number_format($total, 0, ',', '.')}} đ</td>
				</tr>
				<tr>
					<td colspan="6" style="padding: 10px; border: 1px solid #ddd; background-color: #fffacd;">
						<strong>Bằng chữ:</strong> <em>{{convertNumberToWords($total)}} đồng</em>
					</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>

	<!-- Ghi chú -->
	@if($detail->content && !$vat_invoice)
	<div class="receipt-note" style="margin-bottom: 25px;">
		<p style="margin: 0;"><strong>Ghi chú:</strong> {{$detail->content}}</p>
	</div>
	@endif

	<!-- Chữ ký -->
	<div class="receipt-signature" style="margin-top: 40px;">
		<table style="width: 100%;">
			<tr>
				<td style="width: 50%; text-align: center; padding-top: 80px;">
					<p style="margin: 0; font-weight: bold;">Người lập phiếu</p>
					<p style="margin: 5px 0 0 0; font-style: italic; color: #666;">(Ký và ghi rõ họ tên)</p>
				</td>
				<td style="width: 50%; text-align: center; padding-top: 80px;">
					<p style="margin: 0; font-weight: bold;">Người nhận hàng</p>
					<p style="margin: 5px 0 0 0; font-style: italic; color: #666;">(Ký và ghi rõ họ tên)</p>
				</td>
			</tr>
		</table>
	</div>

	<!-- URL và Mã phiếu - Tăng khoảng trống để đóng dấu và ký tên -->
	<div class="receipt-footer" style="margin-top: 100px; padding-top: 15px; border-top: 1px dashed #ccc; text-align: center; font-size: 11px; color: #999;">
		<p style="margin: 5px 0;">URL truy cập: <a href="{{$view_url}}" target="_blank" style="color: #3c8dbc; word-break: break-all;">{{$view_url}}</a></p>
		<p style="margin: 5px 0;">Mã phiếu: <strong>{{$receipt_code}}</strong></p>
	</div>
</div>
</div>
<div class="form-group" style="overflow: hidden; margin-top: 20px;">
	<button class="btn btn-danger pull-right closeModal" type="button" style="margin-left: 5px;" data-dismiss="modal">Đóng</button>
	<a class="btn btn-success pull-right" target="_blank" href="/admin/import-goods/print/{{$detail->id}}"><i class="fa fa-print" aria-hidden="true"></i> In</a>
</div>

<style>
.qr-code-header {
	flex-shrink: 0;
}
.receipt-header {
	flex-grow: 1;
}
@media print {
	.receipt-container {
		max-width: 100% !important;
		padding: 0 !important;
	}
	.form-group {
		display: none !important;
	}
	.receipt-footer {
		page-break-inside: avoid;
	}
	.qr-code-header,
	.receipt-header {
		page-break-inside: avoid;
	}
}
@media (max-width: 768px) {
	div[style*="display: flex"] {
		flex-direction: column !important;
	}
	.qr-code-header {
		margin-bottom: 15px;
	}
}
</style>
