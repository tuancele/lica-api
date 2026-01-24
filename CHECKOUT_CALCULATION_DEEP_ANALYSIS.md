# Phân Tích Sâu Logic Tính Toán Checkout

## 1. Các Thành Phần Tham Gia Tính Toán

### A. Dữ Liệu Đầu Vào (Input Data)

1. **window.checkoutData** (Khởi tạo từ Backend khi trang load):
   - `subtotal`: Tổng tiền hàng từ Backend ({{ $totalPrice }})
   - `sale`: Giảm giá từ coupon ({{ $sale }})
   - `feeship`: Phí vận chuyển ({{ $feeship }})
   - `total`: Tổng thanh toán (tính từ Backend)

2. **window.checkoutPriceBreakdowns** (Khởi tạo từ Backend + cập nhật từ FlashSale API):
   - Key: `variantId`
   - Value: `{ total_price, price_breakdown, is_available }`
   - Nguồn: Backend khi trang load + FlashSale API khi quantity thay đổi

3. **Input Fields**:
   - `input[name="feeShip"]`: Phí vận chuyển (có thể có định dạng số khác nhau)

4. **Cart API Data** (`/api/v1/cart`):
   - `items[]`: Mảng items với `variant_id`, `subtotal`, `price_breakdown`
   - `summary`: Tổng hợp với `total_qty`, `shipping_fee`

### B. Flow Tính Toán

```
1. User Action (chọn địa chỉ / áp dụng voucher / thay đổi quantity)
   ↓
2. AJAX Call (getFeeShip / applyCoupon / updateItem)
   ↓
3. Parse Response & Update Data Store
   - Parse số từ response (loại bỏ dấu phân cách)
   - Update window.checkoutData.sale / feeship
   - Update input[name="feeShip"]
   ↓
4. Gọi updateTotalOrderPriceCheckout()
   ↓
5. Quyết định có fetch cart data không
   - forceFetchCartData = true → Fetch
   - checkoutPriceBreakdowns empty → Fetch
   - Chỉ update shipping/voucher → Không fetch
   ↓
6. calculateAndUpdateTotals(cartData)
   ↓
7. Build items array từ:
   - cartData.items (nếu có) → Merge với checkoutPriceBreakdowns
   - checkoutPriceBreakdowns (fallback)
   - checkoutData.subtotal (last fallback)
   ↓
8. Parse shipping fee từ:
   - input[name="feeShip"] (ưu tiên) → Loại bỏ dấu phân cách
   - window.checkoutData.feeship (fallback)
   ↓
9. Parse order voucher từ:
   - window.checkoutData.sale
   ↓
10. CartPriceCalculator.calculateTotal()
    - Step 1: Tính subtotal từ items
    - Step 2: Tính shipping fee (áp dụng shipping voucher nếu có)
    - Step 3: Tính order voucher discount
    - Step 4: Tính total = (subtotal - itemDiscount - orderDiscount) + shippingFee
   ↓
11. CartPriceCalculator.updateUI()
    - Update .subtotal-cart
    - Update .total-order
    - Update .fee_ship
    - Update .sale-promotion
```

## 2. Các Điểm Có Thể Gây Sai Số

### A. Parse Số Từ Input/Response

**Vấn đề**: Định dạng số khác nhau (dấu chấm/phẩy) có thể gây parse sai

**Giải pháp**: Luôn loại bỏ tất cả ký tự không phải số trước khi parse
```javascript
parseFloat(value.toString().replace(/[^\d]/g, ''))
```

### B. Race Condition

**Vấn đề**: Ghi đè HTML trực tiếp TRƯỚC khi `updateTotalOrderPriceCheckout()` hoàn thành

**Giải pháp**: Xóa tất cả các dòng ghi đè HTML trực tiếp, chỉ để `updateUI()` xử lý

### C. Fetch Cart Data Không Cần Thiết

**Vấn đề**: Khi chỉ cập nhật shipping fee, không cần fetch lại cart data

**Giải pháp**: Chỉ fetch khi `forceFetchCartData = true` hoặc `checkoutPriceBreakdowns` empty

### D. Merge Items Sai

**Vấn đề**: Có thể thiếu items hoặc dùng giá sai

**Giải pháp**: 
- Luôn merge cartData với checkoutPriceBreakdowns
- Ưu tiên checkoutPriceBreakdowns (giá từ FlashSale API)

## 3. Công Thức Tính Toán

### A. CartPriceCalculator.calculateTotal()

```javascript
// Step 1: Subtotal
subtotal = Σ(item.subtotal)
itemDiscount = Σ(voucher sản phẩm)

// Step 2: Shipping
shippingFee = shippingFee gốc
if (shippingVoucher) {
    shippingFee = max(0, shippingFee - shippingVoucher.value)
}

// Step 3: Order Voucher
orderTotal = (subtotal - itemDiscount) + shippingFee
if (orderVoucher) {
    orderDiscount = calculateOrderVoucher(subtotal - itemDiscount, shippingFee, orderVoucher)
}

// Step 4: Total
total = (subtotal - itemDiscount - orderDiscount) + shippingFee
total = max(0, total)
```

### B. Ví Dụ Tính Toán

**Input**:
- Subtotal: 2.975.000đ
- Order Voucher: -50.000đ (FIXED)
- Shipping Fee: 34.750đ

**Tính toán**:
```
Step 1: subtotal = 2.975.000, itemDiscount = 0
Step 2: shippingFee = 34.750
Step 3: orderTotal = 2.975.000 + 34.750 = 3.009.750
        orderDiscount = 50.000 (FIXED, min(50.000, 3.009.750) = 50.000)
Step 4: total = (2.975.000 - 0 - 50.000) + 34.750 = 2.959.750
```

**Kết quả mong đợi**: 2.959.750đ

## 4. Debug Checklist

Khi test, kiểm tra các log sau:

1. `[JS_CART_CHECKOUT_LOG] updateTotalOrderPriceCheckout() called` - Hàm được gọi
2. `[JS_CART_CHECKOUT_LOG] shouldFetchCartData` - Có fetch cart data không
3. `[JS_CART_CHECKOUT_LOG] Items for calculation` - Items được tính
4. `[JS_CART_CHECKOUT_LOG] Subtotal sum` - Tổng subtotal
5. `[JS_CART_CHECKOUT_LOG] Shipping fee - Input raw/parsed/Final` - Shipping fee
6. `[JS_CART_CHECKOUT_LOG] Order voucher` - Order voucher
7. `[CartPriceCalculator] Step 1-4` - Từng bước tính toán
8. `[CartPriceCalculator] Final result` - Kết quả cuối cùng
9. `[JS_CART_CHECKOUT_LOG] Calculation result` - Kết quả từ calculateTotal
10. `[JS_CART_CHECKOUT_LOG] Expected` - Giá trị mong đợi
11. `[JS_CART_CHECKOUT_LOG] UI updated` - UI đã được cập nhật

## 5. Các Bug Đã Fix

1. ✅ Parse shipping fee sai khi có dấu phân cách
2. ✅ Ghi đè HTML trực tiếp gây race condition
3. ✅ Fetch cart data không cần thiết khi chỉ update shipping
4. ✅ Breakdown hiển thị "0đ" do parse sai unit_price
5. ✅ updateTotalOrderPriceCheckout() không accessible từ global scope

## 6. Các Bug Còn Lại Cần Fix

1. ⚠️ Có thể còn chỗ ghi đè HTML trực tiếp (dòng 831, 878, 915)
2. ⚠️ Cần kiểm tra xem có chỗ nào đang đọc giá trị từ HTML text không

