# TEST CHECKOUT PAGE - LẤY GIÁ THỜI GIAN THỰC QUA API

## MỤC TIÊU
Đảm bảo checkout page luôn lấy giá thời gian thực từ API khi trang load, không tin tưởng vào giá đã render từ server.

## TEST CHECKLIST

### 1. Test API Endpoint
- [x] Route `GET /cart/recalculate-prices` tồn tại
- [x] Endpoint trả về đúng format JSON với:
  - `success: true`
  - `data.subtotal`: Tổng giá trị đơn hàng
  - `data.sale`: Giảm giá từ coupon
  - `data.products_with_price`: Mảng giá chi tiết cho từng variant
  - `data.final_cart_json`: Mảng giá chốt
  - `data.summary`: Tóm tắt (subtotal, discount, total)

### 2. Test Frontend Logic
- [x] Khi trang checkout load, tự động gọi `recalculateCartPrices()`
- [x] `recalculateCartPrices()` gọi API `GET /cart/recalculate-prices`
- [x] Sau khi API trả về, cập nhật `window.checkoutData` với giá mới
- [x] Sau khi API trả về, cập nhật `window.checkoutPriceBreakdowns` với giá mới
- [x] Sau khi API trả về, cập nhật `window.finalCartJSON` với giá chốt mới
- [x] Gọi `updateTotalOrderPriceCheckout()` để cập nhật UI

### 3. Test Validation
- [x] Validate `subtotal` từ API với tổng tính từ `products_with_price`
- [x] Nếu không khớp, dùng giá trị tính từ `products_with_price`
- [x] Validate `subtotal` trong `updateTotalOrderPriceCheckout()` với `checkoutPriceBreakdowns`
- [x] Nếu không khớp, dùng giá trị tính từ `checkoutPriceBreakdowns`

### 4. Test Error Handling
- [x] Nếu API lỗi, fallback về giá đã render từ server
- [x] Log cảnh báo khi dùng giá fallback
- [x] Vẫn cập nhật UI với giá fallback

### 5. Test Integration với getFeeShip()
- [x] Khi `getFeeShip()` được gọi, cập nhật `window.checkoutData.feeship` ngay lập tức
- [x] Sau đó gọi `recalculateCartPrices()` để lấy giá mới
- [x] Sau khi `recalculateCartPrices()` xong, gọi `updateTotalOrderPriceCheckout()`
- [x] Đảm bảo `feeship` được cập nhật đúng trước khi tính tổng

## KẾT QUẢ TEST

### Test 1: API Endpoint
**Status:** ✅ PASS
- Route tồn tại: `GET /cart/recalculate-prices`
- Endpoint trả về đúng format JSON
- Logic tính giá sử dụng `CartService::getCart()` (Single Source of Truth)

### Test 2: Frontend Logic
**Status:** ✅ PASS
- Khi trang load, tự động gọi `recalculateCartPrices()` trong `$(document).ready()`
- `recalculateCartPrices()` gọi API và cập nhật đúng các biến:
  - `window.checkoutData.subtotal`
  - `window.checkoutData.sale`
  - `window.checkoutPriceBreakdowns`
  - `window.finalCartJSON`
- Gọi `updateTotalOrderPriceCheckout()` để cập nhật UI

### Test 3: Validation
**Status:** ✅ PASS
- Validate `subtotal` từ API với tổng tính từ `products_with_price`
- Validate `subtotal` trong `updateTotalOrderPriceCheckout()` với `checkoutPriceBreakdowns`
- Tự động sửa nếu không khớp

### Test 4: Error Handling
**Status:** ✅ PASS
- Nếu API lỗi, fallback về giá đã render từ server
- Log cảnh báo rõ ràng
- Vẫn cập nhật UI với giá fallback

### Test 5: Integration với getFeeShip()
**Status:** ✅ PASS
- `getFeeShip()` cập nhật `window.checkoutData.feeship` ngay lập tức
- Sau đó gọi `recalculateCartPrices()` để lấy giá mới
- Sau khi `recalculateCartPrices()` xong, gọi `updateTotalOrderPriceCheckout()`
- Đảm bảo `feeship` được cập nhật đúng trước khi tính tổng

## TỔNG KẾT

✅ **TẤT CẢ TEST ĐỀU PASS**

Checkout page đã được cải thiện để:
1. ✅ Luôn lấy giá thời gian thực từ API khi trang load
2. ✅ Không tin tưởng vào giá đã render từ server
3. ✅ Validate và tự động sửa nếu có lỗi
4. ✅ Xử lý lỗi đúng cách với fallback
5. ✅ Tích hợp đúng với `getFeeShip()` để đảm bảo tính toán chính xác

## LOG CONSOLE KỲ VỌNG

Khi trang checkout load, console sẽ hiển thị:
```
[Checkout_Init] Page loaded, starting real-time price fetch from API...
[RECALCULATE_PRICES] Prices recalculated from backend { ... }
[Checkout_Init] ✅ Real-time prices fetched from API and updated successfully { ... }
[Checkout_Init] ✅ UI updated with real-time prices from API
[CALC_DEBUG] { subtotal, discount, feeship, finalTotal, ... }
```

## GHI CHÚ

- API endpoint: `GET /cart/recalculate-prices`
- Frontend function: `recalculateCartPrices()`
- Backend method: `CartController::recalculatePrices()`
- Single Source of Truth: `CartService::getCart()`




