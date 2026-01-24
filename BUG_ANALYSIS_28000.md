# Bug Analysis: Shipping Fee 28,000đ - Total Calculation Error

## 🐛 Vấn Đề

**User báo:**
- Subtotal: 1.400.000đ
- Shipping Fee: 28,000đ  
- Expected: 1.400.000đ + 28.000đ = **1.428.000đ**
- Actual: **1.228.000đ** ❌ (thiếu 200.000đ)

## 📊 Phân Tích Log

### Log Entries với Shipping Fee 28,000đ

**Time: 16:36:03 - 16:36:12**
```
[2026-01-24 16:36:03] SHIPPING FEE DEBUG - All Sources
  input[name="feeShip"] raw: "28000"
  input[name="feeShip"] parsed: 28000 ✅
  Final shippingFee used: 28000 ✅
```

### ⚠️ VẤN ĐỀ PHÁT HIỆN

**KHÔNG CÓ LOG `CALLING CartPriceCalculator` hoặc `Step 4` sau khi shipping fee = 28,000đ!**

Điều này có nghĩa:
1. ✅ Shipping fee được parse đúng (28,000)
2. ❌ `calculateAndUpdateTotals()` KHÔNG được gọi sau khi shipping fee thay đổi
3. ❌ Hoặc `calculateTotal()` được gọi nhưng không log

## 🔍 Nguyên Nhân Có Thể

### 1. Event Handler Không Gọi `updateTotalOrderPriceCheckout()`

Khi shipping fee thay đổi (từ `getFeeShip()` hoặc input change), có thể:
- Event handler không gọi `window.updateTotalOrderPriceCheckout()`
- Hoặc gọi nhưng với `forceFetchCartData = false`, dẫn đến không tính lại

### 2. Race Condition

- Shipping fee được set vào `input[name="feeShip"]`
- Nhưng `updateTotalOrderPriceCheckout()` được gọi TRƯỚC khi input được update
- Dẫn đến tính toán với shipping fee cũ (0)

### 3. Subtotal Bị Sai

- Subtotal có thể không phải 1,400,000đ mà là 1,200,000đ
- Khi cộng 28,000đ → 1,228,000đ (đúng với subtotal sai)

## 🎯 Giải Pháp

### Bước 1: Thêm Log Chi Tiết

Thêm log vào:
1. `getFeeShip()` - khi shipping fee được set
2. Event handler của `input[name="feeShip"]` - khi input thay đổi
3. `updateTotalOrderPriceCheckout()` - khi được gọi

### Bước 2: Đảm Bảo `updateTotalOrderPriceCheckout()` Được Gọi

Sau khi set shipping fee, BẮT BUỘC phải gọi:
```javascript
window.updateTotalOrderPriceCheckout(true); // forceFetchCartData = true
```

### Bước 3: Kiểm Tra Subtotal

Đảm bảo subtotal được tính đúng từ `checkoutPriceBreakdowns` hoặc `cartData`.

## 📝 Code Cần Sửa

### 1. `getFeeShip()` - Đảm bảo gọi `updateTotalOrderPriceCheckout()`

```javascript
// Sau khi set input[name="feeShip"]
$('input[name="feeShip"]').val(feeShipNum);
window.checkoutData.feeship = feeShipNum;

// BẮT BUỘC gọi updateTotalOrderPriceCheckout
setTimeout(function() {
    window.updateTotalOrderPriceCheckout(false); // false vì không cần fetch cart data
}, 100);
```

### 2. Event Handler `input[name="feeShip"]` - Thêm change event

```javascript
$('input[name="feeShip"]').on('change blur', function() {
    const feeShipValue = parseFloat($(this).val().toString().replace(/[^\d]/g, '')) || 0;
    window.checkoutData.feeship = feeShipValue;
    window.updateTotalOrderPriceCheckout(false);
});
```

## ✅ Test Case

1. **Test với shipping fee > 0:**
   - Chọn địa chỉ → shipping fee = 28,000đ
   - Kiểm tra log có `CALLING CartPriceCalculator` với `shippingFee: 28000`
   - Kiểm tra log có `Step 4` với `shippingFee: 28000`
   - Kiểm tra UI hiển thị total = 1,428,000đ

2. **Test với subtotal = 1,400,000đ:**
   - Đảm bảo subtotal được tính đúng từ items
   - Kiểm tra log `Subtotal sum: 1400000`

3. **Test với order voucher:**
   - Nếu có voucher, đảm bảo được trừ đúng
   - Formula: `(subtotal - orderDiscount) + shippingFee`

