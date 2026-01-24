# Hướng Dẫn Đọc Log Checkout Calculation

## 1. Cách Ghi Log

Log được tự động ghi vào Laravel log file khi:
- User chọn địa chỉ (cập nhật shipping fee)
- User áp dụng/hủy voucher
- User thay đổi số lượng sản phẩm
- Hệ thống tính toán tổng thanh toán

## 2. Vị Trí Log File

```
storage/logs/laravel.log
```

## 3. Cách Đọc Log

### Cách 1: Dùng Script PHP (Khuyến nghị)

```bash
# Đọc 100 dòng log gần nhất
php read_checkout_logs.php

# Đọc 500 dòng log gần nhất
php read_checkout_logs.php --tail=500

# Tìm log cụ thể
php read_checkout_logs.php --grep="TOTAL MISMATCH"
```

### Cách 2: Đọc Trực Tiếp

```bash
# Windows PowerShell
Get-Content storage\logs\laravel.log -Tail 100 | Select-String "CHECKOUT_CALCULATION"

# Linux/Mac
tail -100 storage/logs/laravel.log | grep "CHECKOUT_CALCULATION"
```

### Cách 3: Xem Trong Code Editor

Mở file `storage/logs/laravel.log` và tìm kiếm: `CHECKOUT_CALCULATION`

## 4. Các Loại Log Quan Trọng

### A. Shipping Fee Debug
```
[CHECKOUT_CALCULATION] SHIPPING FEE DEBUG - All Sources
```
**Chứa**:
- `input[name="feeShip"] raw`: Giá trị raw từ input
- `input[name="feeShip"] parsed`: Giá trị sau khi parse
- `Final shippingFee used`: Giá trị cuối cùng được dùng

**Kiểm tra**: Nếu `parsed` khác `raw` (ví dụ: "40,000" → 40) → Bug parse!

### B. Calculation Input
```
[CHECKOUT_CALCULATION] CALLING CartPriceCalculator.calculateTotal
```
**Chứa**:
- `itemsCount`: Số lượng items
- `items`: Mảng items với subtotal
- `shippingFee`: Phí vận chuyển
- `orderVoucher`: Voucher đơn hàng

### C. Calculation Result
```
[CHECKOUT_CALCULATION] CartPriceCalculator Step 4 - Final total calculation
```
**Chứa**:
- `calculation`: Formula chi tiết
- `totalBeforeMax`: Tổng trước khi áp dụng max(0, ...)
- `totalFinal`: Tổng cuối cùng

### D. Total Mismatch (Nếu có lỗi)
```
[CHECKOUT_CALCULATION] ❌ TOTAL MISMATCH!
```
**Chứa**:
- `calculated`: Giá trị tính được
- `expected`: Giá trị mong đợi
- `difference`: Số tiền sai lệch
- `BREAKDOWN`: Chi tiết từng thành phần
  - `Missing`: Số tiền bị thiếu

## 5. Ví Dụ Phân Tích Log

### Vấn Đề: 4.550.000đ - 50.000đ + 40.000đ = 3.640.000đ (sai 900.000đ)

**Bước 1**: Tìm log `SHIPPING FEE DEBUG`
```json
{
  "input[name=\"feeShip\"] parsed": 40,  // ❌ SAI! Phải là 40000
  "Final shippingFee used": 40
}
```
→ **Nguyên nhân**: Parse sai "40,000" thành 40

**Bước 2**: Tìm log `TOTAL MISMATCH`
```json
{
  "BREAKDOWN": {
    "Expected": 4540000,
    "Got": 3640000,
    "Missing": 900000
  }
}
```
→ **Xác nhận**: Thiếu 900.000đ

## 6. Checklist Debug

Khi đọc log, kiểm tra:

- [ ] Shipping fee parsed đúng (không phải 40 mà là 40000)
- [ ] Items đủ và subtotal đúng
- [ ] Order voucher đúng (50.000đ)
- [ ] Formula tính toán đúng: `(subtotal - discount) + shipping`
- [ ] Total matches expected (difference <= 1)
- [ ] Không có log `TOTAL MISMATCH`

## 7. Lưu Ý

- Log được ghi bất đồng bộ (fire and forget), có thể có độ trễ nhỏ
- Nếu không thấy log, kiểm tra:
  - Route `/api/debug/log` có hoạt động không
  - CSRF token có đúng không
  - Network tab trong DevTools có lỗi không
- Log file có thể rất lớn, nên dùng `--tail` để giới hạn

