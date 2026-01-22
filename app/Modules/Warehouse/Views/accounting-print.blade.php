<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In phiếu {{ $receipt->receipt_code }}</title>
    <link href="/public/admin/css/warehouse-accounting.css" rel="stylesheet" type="text/css">
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }
        body { padding: 20px; }
        .qr-code-image {
            width: 120px;
            height: 120px;
        }
    </style>
</head>
<body>
    <div class="warehouse-accounting-container">
        <!-- Header -->
        <div class="receipt-header">
            <div style="flex: 1;">
                <div class="receipt-title">{{ $receipt->type === 'import' ? 'PHIẾU NHẬP KHO' : 'PHIẾU XUẤT KHO' }}</div>
            </div>
            <div class="receipt-code-section">
                <div class="receipt-code-label">Mã phiếu:</div>
                <div class="receipt-code-input" style="border: none; background: transparent;">{{ $receipt->receipt_code }}</div>
            </div>
        </div>

        <!-- Partner Info -->
        <div class="partner-info">
            <div class="partner-info-row">
                <div class="partner-info-label">{{ $receipt->type === 'import' ? 'Nhà cung cấp:' : 'Khách hàng:' }}</div>
                <div class="partner-info-input" style="border: none; background: transparent;">
                    {{ $receipt->supplier_name ?? $receipt->customer_name ?? '-' }}
                </div>
            </div>
            <div class="partner-info-row">
                <div class="partner-info-label">Địa chỉ:</div>
                <div class="partner-info-input" style="border: none; background: transparent;">-</div>
            </div>
            <div class="partner-info-row">
                <div class="partner-info-label">SĐT:</div>
                <div class="partner-info-input" style="border: none; background: transparent;">-</div>
            </div>
            <div class="partner-info-row">
                <div class="partner-info-label">Mã số thuế:</div>
                <div class="partner-info-input" style="border: none; background: transparent;">{{ $receipt->vat_invoice ?? '-' }}</div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="items-table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="col-stt">STT</th>
                        <th class="col-product">Sản phẩm</th>
                        <th class="col-variant">Phân loại</th>
                        <th class="col-quantity">Số lượng</th>
                        <th class="col-price">Đơn giá</th>
                        <th class="col-total">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipt->items as $index => $item)
                    <tr>
                        <td class="col-stt">{{ $index + 1 }}</td>
                        <td class="col-product">{{ $item->variant->product->name ?? '-' }}</td>
                        <td class="col-variant">{{ $item->variant->option1_value ?? 'Mặc định' }}</td>
                        <td class="col-quantity">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                        <td class="col-price">{{ number_format($item->unit_price, 0, ',', '.') }} đ</td>
                        <td class="col-total">{{ number_format($item->total_price, 0, ',', '.') }} đ</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Subject -->
        <div class="partner-info-row" style="margin-top: 20px;">
            <div class="partner-info-label">Nội dung:</div>
            <div class="partner-info-input" style="border: none; background: transparent;">{{ $receipt->subject ?? '-' }}</div>
        </div>

        <!-- Totals -->
        <div class="receipt-totals">
            <div class="total-row">
                <div class="total-label">Tổng tiền:</div>
                <div class="total-value">{{ number_format($receipt->total_value, 0, ',', '.') }} đ</div>
            </div>
        </div>

        <!-- QR Code -->
        <div class="qr-code-section">
            <div class="qr-code-container">
                <img src="{{ route('warehouse.accounting.qrcode', ['receiptCode' => $receipt->receipt_code]) }}" 
                     alt="QR Code: {{ $receipt->receipt_code }}" 
                     class="qr-code-image">
            </div>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <div class="signature-section">
                <div class="signature-label">Người lập</div>
                <div style="margin-top: 40px;">{{ $receipt->creator->name ?? '-' }}</div>
            </div>
            <div class="signature-section">
                <div class="signature-label">Người duyệt</div>
            </div>
            <div class="signature-section">
                <div class="signature-label">Thủ kho</div>
            </div>
        </div>
    </div>
</body>
</html>


