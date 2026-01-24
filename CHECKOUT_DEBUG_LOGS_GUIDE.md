# Hướng Dẫn Debug Tính Toán Checkout - Log Chi Tiết

## 🎯 Mục Đích

Khi tính toán sai (ví dụ: 4.550.000đ - 50.000đ + 40.000đ = 3.640.000đ thay vì 4.540.000đ), các log này sẽ giúp xác định chính xác vấn đề ở đâu.

## 📊 Các Log Đã Thêm

### 1. Log Khi Bắt Đầu Tính Toán

```
[JS_CART_CHECKOUT_LOG] 📊 calculateAndUpdateTotals() called
[JS_CART_CHECKOUT_LOG] 📊 cartData: {...}
[JS_CART_CHECKOUT_LOG] 📊 window.checkoutPriceBreakdowns: {...}
[JS_CART_CHECKOUT_LOG] 📊 window.checkoutData: {...}
```

**Mục đích**: Xem dữ liệu đầu vào từ đâu.

---

### 2. Log Items Được Tính

```
[JS_CART_CHECKOUT_LOG] Processing items from cartData, count: X
[JS_CART_CHECKOUT_LOG] Item {variantId} using breakdown price: X instead of cart price: Y
[JS_CART_CHECKOUT_LOG] Items for calculation: [...]
[JS_CART_CHECKOUT_LOG] Subtotal sum: X
```

**Mục đích**: Xem items nào được tính, giá nào được dùng (cartData hay breakdown).

---

### 3. Log Shipping Fee - TẤT CẢ NGUỒN

```
[JS_CART_CHECKOUT_LOG] 🔍 SHIPPING FEE DEBUG - All Sources: {
    'input[name="feeShip"] raw': "...",
    'input[name="feeShip"] parsed': X,
    'window.checkoutData.feeship': Y,
    'window.checkoutData.feeship parsed': Z,
    '.fee_ship HTML text': "...",
    '.fee_ship HTML parsed': W,
    'Final shippingFee used': FINAL_VALUE
}
```

**Mục đích**: Xem shipping fee được lấy từ đâu và giá trị cuối cùng là gì.

**⚠️ QUAN TRỌNG**: Nếu `input[name="feeShip"]` có giá trị "40,000" nhưng parsed thành 40 thay vì 40000 → Đây là bug parse!

---

### 4. Log Order Voucher

```
[JS_CART_CHECKOUT_LOG] Order voucher - sale: X, voucher: {...}
```

**Mục đích**: Xem voucher đơn hàng có giá trị bao nhiêu.

---

### 5. Log Gọi CartPriceCalculator

```
[JS_CART_CHECKOUT_LOG] 🔢 CALLING CartPriceCalculator.calculateTotal with: {
    itemsCount: X,
    items: [...],
    shippingFee: Y,
    shippingVoucher: null,
    orderVoucher: {...}
}
```

**Mục đích**: Xem chính xác giá trị nào được truyền vào `calculateTotal()`.

---

### 6. Log Từng Bước Trong CartPriceCalculator

```
[CartPriceCalculator] calculateTotal called with: {...}
[CartPriceCalculator] Step 1 - Subtotal calculation: {...}
[CartPriceCalculator] Step 2 - Shipping calculation: {...}
[CartPriceCalculator] Step 3 - Order voucher calculation: {...}
[CartPriceCalculator] Step 4 - Final total calculation: {
    calculation: "(X - Y - Z) + W = RESULT",
    totalBeforeMax: RESULT,
    totalFinal: FINAL
}
[CartPriceCalculator] Final result: {...}
```

**Mục đích**: Xem từng bước tính toán trong `CartPriceCalculator`.

---

### 7. Log Manual Calculation Check

```
[JS_CART_CHECKOUT_LOG] 🔢 MANUAL CALCULATION CHECK: {
    'Manual Subtotal': X,
    'Manual Item Discount': Y,
    'Manual Order Discount': Z,
    'Manual Shipping Fee': W,
    'Manual Total Formula': "(X - Y - Z) + W",
    'Manual Total Result': RESULT,
    'CartPriceCalculator Total': CALCULATOR_RESULT,
    'Difference (Manual vs Calculator)': DIFF
}
```

**Mục đích**: So sánh tính toán thủ công với kết quả từ `CartPriceCalculator` để tìm sai lệch.

---

### 8. Log Validation Check

```
[JS_CART_CHECKOUT_LOG] ✅ VALIDATION CHECK: {
    'Expected Total': X,
    'Calculated Total': Y,
    'Difference': Z,
    'Is Match?': true/false,
    'Formula': "(subtotal - discount) + shipping = expected"
}
```

**Mục đích**: So sánh kết quả tính toán với giá trị mong đợi.

---

### 9. Log Nếu Có Sai Lệch

```
[JS_CART_CHECKOUT_LOG] ❌ TOTAL MISMATCH! {
    calculated: X,
    expected: Y,
    difference: Z,
    calcResult: {...},
    inputs: {...},
    BREAKDOWN: {
        'Subtotal': A,
        'Order Discount': B,
        'Shipping Fee': C,
        'Expected': D,
        'Got': E,
        'Missing': F  // ← Số tiền bị thiếu
    }
}
```

**Mục đích**: Nếu có sai lệch, log này sẽ cho biết:
- Giá trị tính được: `calculated`
- Giá trị mong đợi: `expected`
- Số tiền bị thiếu/thừa: `Missing`

---

### 10. Log Update UI

```
[JS_CART_CHECKOUT_LOG] 🎨 UPDATING UI with: {...}
[CartPriceCalculator] 🎨 updateUI called with: {...}
[CartPriceCalculator] 🎨 Updating subtotal: {...}
[CartPriceCalculator] 🎨 Updating total: {...}
[CartPriceCalculator] 🎨 Updating shipping fee: {...}
[CartPriceCalculator] 🎨 Updating discount: {...}
[JS_CART_CHECKOUT_LOG] 🎨 UI VALUES AFTER UPDATE: {
    '.subtotal-cart': "...",
    '.total-order': "...",
    '.fee_ship': "...",
    '.sale-promotion': "..."
}
```

**Mục đích**: Xem giá trị nào được hiển thị trên UI sau khi update.

---

## 🔍 Cách Debug Khi Có Vấn Đề

### Bước 1: Mở Console (F12)

### Bước 2: Tìm Log Bắt Đầu

Tìm log: `[JS_CART_CHECKOUT_LOG] 📊 calculateAndUpdateTotals() called`

### Bước 3: Kiểm Tra Shipping Fee

Tìm log: `[JS_CART_CHECKOUT_LOG] 🔍 SHIPPING FEE DEBUG`

**Kiểm tra**:
- `input[name="feeShip"] parsed` có đúng không?
- Nếu hiển thị "40,000" nhưng parsed = 40 → Bug parse!
- `Final shippingFee used` có đúng không?

### Bước 4: Kiểm Tra Items

Tìm log: `[JS_CART_CHECKOUT_LOG] Items for calculation`

**Kiểm tra**:
- Items có đủ không?
- Subtotal sum có đúng không?

### Bước 5: Kiểm Tra Tính Toán

Tìm log: `[CartPriceCalculator] Step 4 - Final total calculation`

**Kiểm tra**:
- Formula có đúng không?
- `totalBeforeMax` có đúng không?

### Bước 6: Kiểm Tra Validation

Tìm log: `[JS_CART_CHECKOUT_LOG] ❌ TOTAL MISMATCH!` (nếu có)

**Kiểm tra**:
- `Missing` là bao nhiêu?
- `BREAKDOWN` cho biết số tiền bị thiếu ở đâu

---

## 📝 Ví Dụ Debug

### Vấn Đề: 4.550.000đ - 50.000đ + 40.000đ = 3.640.000đ (sai 900.000đ)

**Bước 1**: Tìm log `🔍 SHIPPING FEE DEBUG`
```
'input[name="feeShip"] parsed': 40  // ❌ SAI! Phải là 40000
```

**Nguyên nhân**: Parse sai "40,000" thành 40 thay vì 40000

**Giải pháp**: Sửa logic parse (đã sửa: `.replace(/[^\d]/g, '')`)

---

**Bước 2**: Nếu shipping fee đúng, kiểm tra log `🔢 MANUAL CALCULATION CHECK`
```
'Manual Total Result': 4540000,
'CartPriceCalculator Total': 3640000,
'Difference': 900000
```

**Nguyên nhân**: Có thể có logic nào đó đang trừ thêm 900.000đ

**Giải pháp**: Kiểm tra log `Step 4` để xem formula

---

## ✅ Checklist Khi Test

- [ ] Shipping fee parsed đúng (không phải 40 mà là 40000)
- [ ] Items đủ và subtotal đúng
- [ ] Order voucher đúng (50.000đ)
- [ ] Formula tính toán đúng: `(subtotal - discount) + shipping`
- [ ] Total matches expected (difference <= 1)
- [ ] UI hiển thị đúng giá trị

---

## 🚨 Các Lỗi Thường Gặp

1. **Parse số sai**: "40,000" → 40 thay vì 40000
   - **Fix**: Dùng `.replace(/[^\d]/g, '')` trước khi parse

2. **Đọc từ HTML text**: Đọc từ `.fee_ship` text thay vì từ input
   - **Fix**: Luôn đọc từ `input[name="feeShip"]`

3. **Race condition**: Ghi đè HTML trước khi tính toán xong
   - **Fix**: Chỉ để `updateUI()` xử lý hiển thị

4. **Thiếu items**: Không merge đúng cartData với checkoutPriceBreakdowns
   - **Fix**: Luôn merge và ưu tiên checkoutPriceBreakdowns

