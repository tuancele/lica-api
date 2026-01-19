<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>PHIẾU NHẬP HÀNG - {{$receipt_code}}</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		body {
			font-family: 'Times New Roman', serif;
			font-size: 12pt;
			line-height: 1.5;
			color: #000;
			background: #fff;
			padding: 20px;
		}
		.receipt-container {
			max-width: 210mm;
			margin: 0 auto;
			background: #fff;
		}
		.qr-code-header {
			text-align: center;
		}
		.qr-code-header img {
			display: block;
			margin: 0 auto;
		}
		.receipt-header {
			text-align: center;
		}
		.receipt-header h2 {
			font-size: 20pt;
			font-weight: bold;
			margin-bottom: 5px;
			text-transform: uppercase;
		}
		.receipt-header p {
			font-size: 11pt;
			margin: 0;
		}
		.receipt-info table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
		}
		.receipt-info td {
			padding: 6px 0;
			vertical-align: top;
		}
		.receipt-info td:first-child {
			width: 35%;
			font-weight: bold;
		}
		.receipt-products table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
		}
		.receipt-products th,
		.receipt-products td {
			border: 1px solid #000;
			padding: 8px;
			text-align: left;
		}
		.receipt-products th {
			background-color: #f0f0f0;
			font-weight: bold;
			text-align: center;
		}
		.receipt-products td {
			text-align: left;
		}
		.receipt-products td:nth-child(1) {
			text-align: center;
			width: 5%;
		}
		.receipt-products td:nth-child(4),
		.receipt-products td:nth-child(6) {
			text-align: right;
		}
		.receipt-products td:nth-child(5) {
			text-align: center;
		}
		.receipt-products tr.total-row {
			background-color: #f9f9f9;
			font-weight: bold;
		}
		.receipt-products tr.words-row {
			background-color: #fffacd;
		}
		.qr-code-header {
			text-align: center;
			margin-bottom: 15px;
		}
		.qr-code-header img {
			display: inline-block;
		}
		.receipt-signature {
			margin-top: 40px;
		}
		.receipt-signature table {
			width: 100%;
		}
		.receipt-signature td {
			text-align: center;
			padding-top: 100px;
		}
		.receipt-footer {
			margin-top: 120px;
			padding-top: 15px;
			border-top: 1px dashed #999;
			text-align: center;
			font-size: 9pt;
			color: #666;
		}
		@media print {
			body {
				padding: 10mm;
			}
			.receipt-container {
				max-width: 100%;
			}
			.receipt-footer {
				page-break-inside: avoid;
			}
		}
	</style>
</head>
<body>
<div class="receipt-container">
	<!-- QR Code và Header trên cùng một hàng -->
	<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; border-bottom: 2px solid #000; padding-bottom: 15px;">
		<!-- QR Code bên trái -->
		<div class="qr-code-header" style="text-align: center; flex-shrink: 0;">
			@if(isset($qr_code) && !empty($qr_code))
			<img src="{{$qr_code}}" alt="QR Code" style="width: 100px; height: 100px; border: 1px solid #000; padding: 3px; background: #fff; display: block; margin: 0 auto;">
			@else
			<img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{urlencode($view_url)}}&format=png&margin=1" alt="QR Code" style="width: 100px; height: 100px; border: 1px solid #000; padding: 3px; background: #fff; display: block; margin: 0 auto;">
			@endif
			<p style="margin: 3px 0 0 0; font-size: 9pt; color: #666;">Quét mã để xem chi tiết</p>
		</div>

		<!-- Header ở giữa -->
		<div class="receipt-header" style="text-align: center; flex-grow: 1;">
			<h2>PHIẾU NHẬP HÀNG</h2>
			<p>Mã phiếu: <strong>{{$receipt_code}}</strong></p>
		</div>

		<!-- Khoảng trống bên phải để cân đối -->
		<div style="width: 100px; flex-shrink: 0;"></div>
	</div>

	<!-- Thông tin đơn hàng -->
	<div class="receipt-info">
		<table>
			<tr>
				<td>Mã đơn hàng:</td>
				<td>{{$detail->code}}</td>
			</tr>
			<tr>
				<td>Người nhập:</td>
				<td>@if(isset($detail->user)) {{$detail->user->name}} @else - @endif</td>
			</tr>
			<tr>
				<td>Nội dung:</td>
				<td>{{$detail->subject}}</td>
			</tr>
			@if($vat_invoice)
			<tr>
				<td>Số hóa đơn VAT:</td>
				<td>{{$vat_invoice}}</td>
			</tr>
			@endif
			<tr>
				<td>Ngày nhập:</td>
				<td>{{date('H:i:s  d/m/Y',strtotime($detail->created_at))}}</td>
			</tr>
		</table>
	</div>

	<!-- Danh sách sản phẩm -->
	<div class="receipt-products">
		<table>
			<thead>
				<tr>
					<th>STT</th>
					<th>Sản phẩm</th>
					<th>Phân loại</th>
					<th>Đơn giá</th>
					<th>Số lượng</th>
					<th>Thành tiền</th>
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
					<td>{{$key + 1}}</td>
					<td>{{$product->variant->product->name}}</td>
					<td>{{$product->variant->option1_value ?? 'Mặc định'}}</td>
					<td>{{number_format($product->price, 0, ',', '.')}} đ</td>
					<td>{{number_format($product->qty, 0, ',', '.')}}</td>
					<td>{{number_format($subtotal, 0, ',', '.')}} đ</td>
				</tr>
				@endforeach
				<tr class="total-row">
					<td colspan="5" style="text-align: right;">Tổng giá trị đơn hàng:</td>
					<td style="text-align: right; font-size: 14pt;">{{number_format($total, 0, ',', '.')}} đ</td>
				</tr>
				<tr class="words-row">
					<td colspan="6" style="padding: 10px;">
						<strong>Bằng chữ:</strong> <em>{{convertNumberToWords($total)}} đồng</em>
					</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>

	<!-- Ghi chú -->
	@if($detail->content && !$vat_invoice)
	<div style="margin-bottom: 20px;">
		<p><strong>Ghi chú:</strong> {{$detail->content}}</p>
	</div>
	@endif

	<!-- Chữ ký -->
	<div class="receipt-signature">
		<table>
			<tr>
				<td>
					<p style="margin: 0; font-weight: bold;">Người lập phiếu</p>
					<p style="margin: 5px 0 0 0; font-style: italic;">(Ký và ghi rõ họ tên)</p>
				</td>
				<td>
					<p style="margin: 0; font-weight: bold;">Người nhận hàng</p>
					<p style="margin: 5px 0 0 0; font-style: italic;">(Ký và ghi rõ họ tên)</p>
				</td>
			</tr>
		</table>
	</div>

	<!-- URL và Mã phiếu -->
	<div class="receipt-footer">
		<p style="margin: 3px 0;">URL truy cập: {{$view_url}}</p>
		<p style="margin: 3px 0;">Mã phiếu: <strong>{{$receipt_code}}</strong></p>
	</div>
</div>
</body>
<script type="text/javascript">
	window.onload = function() {
		window.print();
	}
</script>
</html>
