<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu {{ $receipt->receipt_code }}</title>
    <link href="/public/admin/css/warehouse-accounting.css" rel="stylesheet" type="text/css">
    <style>
        body { 
            padding: 20px; 
            background-color: #f5f5f5;
        }
        .warehouse-accounting-container {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .public-header {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dfdfdf;
            margin-bottom: 20px;
        }
        .public-header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .public-url {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="public-header">
        <h1>PHIẾU {{ $receipt->type === 'import' ? 'NHẬP' : 'XUẤT' }} KHO</h1>
        <div class="public-url">{{ route('warehouse.receipt.public', ['receiptCode' => $receipt->receipt_code]) }}</div>
    </div>
    <div class="warehouse-accounting-container">
        <!-- Form Header theo mẫu 02-VT -->
        <div class="receipt-form-header">
            <div class="receipt-form-top">
                <div class="receipt-form-left">
                    <div class="form-field">
                        <label>Đơn vị:</label>
                        <div class="form-input" style="border: none; background: transparent;">{{ config('app.name', 'Công ty TNHH Lica') }}</div>
                    </div>
                    <div class="form-field">
                        <label>Bộ phận:</label>
                        <div class="form-input" style="border: none; background: transparent;">{{ $receipt->type === 'import' ? 'Mua hàng' : 'Bán hàng' }}</div>
                    </div>
                </div>
                <div class="receipt-form-qr-center">
                    <!-- QR Code -->
                    <div class="qr-code-container">
                        <img src="{{ route('warehouse.accounting.qrcode', ['receiptCode' => $receipt->receipt_code]) }}" 
                             alt="QR Code: {{ $receipt->receipt_code }}" 
                             class="qr-code-image">
                    </div>
                </div>
                <div class="receipt-form-right">
                    <div class="form-number">Mẫu số: 02 - VT</div>
                    <div class="form-circular">
                        (Kèm theo Thông tư số 99/2025/TT-BTC<br>
                        ngày 27 tháng 10 năm 2025 của Bộ trưởng Bộ Tài chính)
                    </div>
                </div>
            </div>
            
            <div class="receipt-title-section">
                <div class="receipt-title-main">
                    {{ $receipt->type === 'import' ? 'PHIẾU NHẬP KHO' : 'PHIẾU XUẤT KHO' }}
                </div>
                <div class="receipt-date">
                    Ngày {{ $receipt->created_at->day }} tháng {{ $receipt->created_at->month }} năm {{ $receipt->created_at->year }}
                </div>
            </div>
            
            <div class="receipt-accounting-info">
                <div class="accounting-left">
                    <div class="form-field">
                        <label>Họ và tên người nhận hàng:</label>
                        <div class="form-input" style="border: none; background: transparent;">
                            {{ $receipt->type === 'import' ? ($receipt->supplier_name ?? '-') : ($receipt->customer_name ?? '-') }}
                        </div>
                    </div>
                    <div class="form-field">
                        <label>Địa chỉ (bộ phận):</label>
                        <div class="form-input" style="border: none; background: transparent;">{{ $receipt->content ?? '-' }}</div>
                    </div>
                    <div class="form-field">
                        <label>Lý do {{ $receipt->type === 'import' ? 'nhập' : 'xuất' }} kho:</label>
                        <div class="form-input" style="border: none; background: transparent;">{{ $receipt->subject ?? '-' }}</div>
                    </div>
                    <div class="form-field">
                        <label>{{ $receipt->type === 'import' ? 'Nhập' : 'Xuất' }} tại kho (ngăn lô):</label>
                        <div class="form-input" style="border: none; background: transparent;">-</div>
                        <label style="margin-left: 20px;">Địa điểm:</label>
                        <div class="form-input" style="border: none; background: transparent; width: 200px;">-</div>
                    </div>
                </div>
                <div class="accounting-right">
                    <div class="form-field">
                        <label>Số:</label>
                        <div class="form-input receipt-code-display" style="border: none; background: transparent;">{{ $receipt->receipt_code }}</div>
                    </div>
                    <div class="form-field">
                        <label>Nợ:</label>
                        <div class="form-input" style="border: none; background: transparent;">...</div>
                    </div>
                    <div class="form-field">
                        <label>Có:</label>
                        <div class="form-input" style="border: none; background: transparent;">...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="items-table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="col-stt">STT<br><small>A</small></th>
                        <th class="col-product">Tên, nhãn hiệu, quy cách, phẩm chất vật tư, dụng cụ, sản phẩm, hàng hóa<br><small>B</small></th>
                        <th class="col-code">Mã số<br><small>C</small></th>
                        <th class="col-unit">Đơn vị tính<br><small>D</small></th>
                        <th class="col-quantity-header" colspan="2">Số lượng</th>
                        <th class="col-price">Đơn giá<br><small>3</small></th>
                        <th class="col-total">Thành tiền<br><small>4</small></th>
                    </tr>
                    <tr class="sub-header">
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th class="col-quantity-request">Yêu cầu<br><small>1</small></th>
                        <th class="col-quantity-actual">Thực {{ $receipt->type === 'import' ? 'nhập' : 'xuất' }}<br><small>2</small></th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipt->items as $index => $item)
                    <tr>
                        <td class="col-stt">{{ $index + 1 }}</td>
                        <td class="col-product">
                            {{ $item->variant->product->name ?? '-' }}
                            @if($item->variant->option1_value)
                                - {{ $item->variant->option1_value }}
                            @endif
                        </td>
                        <td class="col-code">{{ $item->variant->sku ?? '-' }}</td>
                        <td class="col-unit">Cái</td>
                        <td class="col-quantity-request">{{ $item->quantity }}</td>
                        <td class="col-quantity-actual">{{ $item->quantity }}</td>
                        <td class="col-price">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="col-total total-cell">{{ number_format($item->total_price, 0, ',', '.') }} đ</td>
                    </tr>
                    @endforeach
                    <tr class="total-row-table">
                        <td colspan="4" style="text-align: center;">Cộng</td>
                        <td colspan="2" style="text-align: center;">x</td>
                        <td style="text-align: center;">x</td>
                        <td class="col-total" style="text-align: right;">{{ number_format($receipt->total_value ?? 0, 0, ',', '.') }} đ</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Totals and Summary -->
        <div class="receipt-summary">
            <div class="summary-row">
                <div class="summary-label">Tổng số tiền (viết bằng chữ):</div>
                <div class="summary-value">{{ $totalInWords }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Số chứng từ gốc kèm theo:</div>
                <div class="form-input" style="border: none; background: transparent; flex: 1;">{{ $receipt->vat_invoice ? 'VAT số ' . $receipt->vat_invoice : '-' }}</div>
            </div>
        </div>

        <div class="receipt-totals">
            <div class="total-row">
                <div class="total-label">Tổng tiền:</div>
                <div class="total-value">{{ number_format($receipt->total_value ?? 0, 0, ',', '.') }} đ</div>
            </div>
        </div>

        <!-- Footer Signatures -->
        <div class="receipt-footer">
            <div class="signature-date">
                Ngày {{ date('d') }} tháng {{ date('m') }} năm {{ date('Y') }}
            </div>
            <div class="signature-sections">
                <div class="signature-section">
                    <div class="signature-label">Người lập phiếu</div>
                    <div class="signature-name">(Ký, họ tên)</div>
                </div>
                <div class="signature-section">
                    <div class="signature-label">Người nhận hàng</div>
                    <div class="signature-name">(Ký, họ tên)</div>
                </div>
                <div class="signature-section">
                    <div class="signature-label">Thủ kho</div>
                    <div class="signature-name">(Ký, họ tên)</div>
                </div>
                <div class="signature-section">
                    <div class="signature-label">Kế toán trưởng<br>(Hoặc bộ phận có nhu cầu nhập)</div>
                    <div class="signature-name">(Ký, họ tên)</div>
                </div>
                <div class="signature-section">
                    <div class="signature-label">Giám đốc</div>
                    <div class="signature-name">(Ký, họ tên)</div>
                </div>
            </div>
        </div>
        
        <!-- Public URL Footer -->
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dfdfdf; font-size: 12px; color: #666;">
            {{ route('warehouse.receipt.public', ['receiptCode' => $receipt->receipt_code]) }}
        </div>
    </div>
</body>
</html>

