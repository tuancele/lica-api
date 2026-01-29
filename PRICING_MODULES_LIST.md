# Danh sách Module/File Tính Tiền trong Hệ Thống

## 📋 Tổng Quan
Tài liệu này liệt kê tất cả các module, service, controller và file liên quan đến việc tính toán giá, tổng tiền, giảm giá, phí vận chuyển trong hệ thống e-commerce.

---

## 🔧 **1. SERVICES - Core Pricing Logic**

### 1.1. Price Calculation Services
- **`app/Services/PriceCalculationService.php`**
  - Tính giá sản phẩm theo độ ưu tiên: Flash Sale > Marketing Campaign > Normal Price
  - Hỗ trợ tính giá theo Product và Variant
  - Tính effective stock (min của Flash Sale remaining và warehouse stock)

- **`app/Services/Pricing/PriceEngineService.php`**
  - Service chính tính giá hiển thị với độ ưu tiên
  - Tính giá theo số lượng (Tiered Pricing) cho sản phẩm Maputi
  - Hỗ trợ Mixed Pricing (Flash Sale + Normal Price)
  - Interface: `app/Services/Pricing/PriceEngineServiceInterface.php`

### 1.2. Cart Service
- **`app/Services/Cart/CartService.php`**
  - Quản lý giỏ hàng (Session-based và Database-based)
  - Tính tổng tiền đơn hàng (subtotal)
  - Xử lý Deal Sốc (giá 0đ cho quà tặng)
  - Tính lại giá khi thay đổi số lượng
  - Validate tồn kho và Deal availability
  - Tính cart summary (subtotal, total_qty, total)

### 1.3. Flash Sale Stock Service
- **`app/Services/FlashSale/FlashSaleStockService.php`**
  - Quản lý tồn kho Flash Sale
  - Kiểm tra số lượng còn lại của Flash Sale

---

## 🎮 **2. CONTROLLERS - API & Web**

### 2.1. Cart Controllers
- **`app/Themes/Website/Controllers/CartController.php`**
  - `index()` - Hiển thị giỏ hàng, tính lại giá với PriceEngineService
  - `checkout()` - Trang thanh toán, tính tổng tiền (subtotal, sale, feeship)
  - `postCheckout()` - Xử lý đặt hàng, tính tổng cuối cùng
  - `applyCoupon()` - Áp dụng mã giảm giá
  - `cancelCoupon()` - Hủy mã giảm giá
  - `feeship()` - Tính phí vận chuyển

- **`app/Http/Controllers/Api/V1/CartController.php`**
  - API endpoints cho Cart operations
  - `getCart()` - Lấy thông tin giỏ hàng với summary (subtotal, total)
  - `addItem()` - Thêm sản phẩm vào giỏ
  - `updateItem()` - Cập nhật số lượng
  - `removeItem()` - Xóa sản phẩm
  - `applyCoupon()` - Áp dụng coupon qua API
  - `removeCoupon()` - Hủy coupon
  - `calculateShippingFee()` - Tính phí vận chuyển

### 2.2. Order Controllers
- **`app/Modules/Order/Controllers/OrderController.php`**
  - Quản lý đơn hàng
  - Tính tổng tiền đơn hàng (total, subtotal, sale, fee_ship)

---

## 💻 **3. JAVASCRIPT - Frontend Calculation**

### 3.1. Price Calculation JS
- **`public/js/flash-sale-mixed-price.js`**
  - Tính giá Mixed Pricing (Flash Sale + Normal Price)
  - Tính Tiered Pricing cho sản phẩm Maputi
  - Hàm `calculatePriceWithQuantity()` - Tính giá theo số lượng
  - Hàm `updateTotalOrderPrice()` - Cập nhật tổng tiền trong Cart
  - Format số tiền hiển thị

- **`public/js/cart-api-v1.js`**
  - API client cho Cart operations
  - Hàm `updateCartUI()` - Cập nhật UI giỏ hàng với giá từ backend
  - Format currency

### 3.2. Checkout Calculation JS
- **`app/Themes/Website/Views/cart/checkout.blade.php`** (phần JavaScript)
  - Hàm `updateTotalOrderPriceCheckout()` - **QUAN TRỌNG**: Tính tổng đơn hàng tại checkout
    - Tính lại subtotal từ `window.checkoutPriceBreakdowns`
    - Lấy discount từ `window.checkoutData.sale`
    - Lấy phí ship từ `input[name="feeShip"]`
    - Công thức: `Subtotal - Discount + Shipping Fee`
  - Hàm `checkFlashSalePriceCheckout()` - Tính giá khi thay đổi số lượng tại checkout
  - Hàm `syncCheckoutData()` - Đồng bộ dữ liệu checkout
  - Xử lý real-time update khi:
    - Thay đổi số lượng sản phẩm
    - Thay đổi phí vận chuyển
    - Áp dụng/hủy coupon

---

## 📄 **4. VIEWS - Display & Calculation**

### 4.1. Cart Views
- **`app/Themes/Website/Views/cart/index.blade.php`**
  - Hiển thị giỏ hàng
  - Hiển thị subtotal, total
  - Xử lý thay đổi số lượng và tính lại giá

- **`app/Themes/Website/Views/cart/checkout.blade.php`**
  - **QUAN TRỌNG**: Trang thanh toán
  - Hiển thị: Subtotal, Discount, Shipping Fee, Total
  - Khởi tạo `window.checkoutData` từ backend:
    - `subtotal`: Tổng giá trị đơn hàng
    - `sale`: Số tiền giảm giá
    - `feeship`: Phí vận chuyển
    - `total`: Tổng thanh toán
  - Khởi tạo `window.checkoutPriceBreakdowns` từ backend
  - JavaScript tính toán real-time

- **`app/Themes/Website/Views/cart/result.blade.php`**
  - Hiển thị kết quả đặt hàng
  - Hiển thị tổng tiền: `{{number_format($order->total + $order->fee_ship - $order->sale)}}`

### 4.2. Product Views
- **`app/Themes/Website/Views/product/detail.blade.php`**
  - Hiển thị giá sản phẩm
  - Tính giá khi thay đổi số lượng

---

## 🗄️ **5. MODELS - Data Structure**

### 5.1. Order Models
- **`app/Modules/Order/Models/Order.php`**
  - Model đơn hàng
  - Các trường: `total`, `subtotal`, `sale`, `fee_ship`

- **`app/Modules/Order/Models/OrderDetail.php`**
  - Chi tiết đơn hàng
  - Các trường: `price`, `quantity`, `subtotal`

### 5.2. Cart Models
- **`app/Themes/Website/Models/Cart.php`**
  - Model giỏ hàng (Session-based)
  - Tính tổng tiền từ items

### 5.3. Pricing Models
- **`app/Modules/FlashSale/Models/FlashSale.php`**
  - Model Flash Sale
  - Quản lý khung giờ và trạng thái

- **`app/Modules/FlashSale/Models/ProductSale.php`**
  - Model sản phẩm trong Flash Sale
  - Các trường: `price_sale`, `number`, `buy`, `remaining`

- **`app/Modules/Deal/Models/Deal.php`**
  - Model Deal Sốc
  - Quản lý quà tặng (giá 0đ)

- **`app/Modules/Deal/Models/SaleDeal.php`**
  - Model sản phẩm trong Deal
  - Các trường: `price` (có thể là 0đ cho quà tặng)

- **`app/Modules/Promotion/Models/Promotion.php`**
  - Model mã giảm giá
  - Tính discount

---

## 🔌 **6. API ENDPOINTS**

### 6.1. Cart API V1
- **`app/Http/Controllers/Api/V1/CartController.php`**
  - `GET /api/v1/cart` - Lấy giỏ hàng với summary
  - `POST /api/v1/cart/items` - Thêm sản phẩm
  - `PUT /api/v1/cart/items/{variantId}` - Cập nhật số lượng
  - `DELETE /api/v1/cart/items/{variantId}` - Xóa sản phẩm
  - `POST /api/v1/cart/coupon/apply` - Áp dụng coupon
  - `DELETE /api/v1/cart/coupon` - Hủy coupon
  - `POST /api/v1/cart/shipping-fee` - Tính phí vận chuyển

---

## 📊 **7. CALCULATION FLOW**

### 7.1. Cart Calculation Flow
```
1. User thêm sản phẩm vào giỏ
   → CartService::addItem()
   → PriceEngineService::calculatePriceWithQuantity()
   → Tính giá theo số lượng (Tiered Pricing)
   → Cập nhật session cart

2. Hiển thị giỏ hàng
   → CartController::index()
   → PriceEngineService::calculatePriceWithQuantity() (tính lại)
   → Hiển thị subtotal, total

3. Thay đổi số lượng
   → JavaScript: FlashSaleMixedPrice.calculatePriceWithQuantity()
   → AJAX: CartService::updateItem()
   → Tính lại giá và cập nhật UI
```

### 7.2. Checkout Calculation Flow
```
1. Trang checkout load
   → CartController::checkout()
   → CartService::getCart() → Tính subtotal
   → Tính sale từ coupon
   → Tính feeship từ địa chỉ
   → Render vào window.checkoutData

2. Thay đổi số lượng tại checkout
   → JavaScript: checkFlashSalePriceCheckout()
   → FlashSaleMixedPrice.calculatePriceWithQuantity()
   → Cập nhật window.checkoutPriceBreakdowns
   → updateTotalOrderPriceCheckout() → Tính lại subtotal từ breakdowns

3. Thay đổi phí ship
   → AJAX: CartController::feeship()
   → Cập nhật input[name="feeShip"]
   → updateTotalOrderPriceCheckout() → Tính lại total

4. Áp dụng coupon
   → AJAX: CartController::applyCoupon()
   → Cập nhật window.checkoutData.sale
   → updateTotalOrderPriceCheckout() → Tính lại total

5. Công thức tính tổng:
   Total = Subtotal - Discount + Shipping Fee
```

---

## ⚠️ **8. CRITICAL FILES - Cần chú ý khi sửa**

### 8.1. Backend
1. **`app/Services/Pricing/PriceEngineService.php`**
   - Service chính tính giá, KHÔNG được sửa logic priority

2. **`app/Services/Cart/CartService.php`**
   - Tính tổng tiền giỏ hàng, xử lý Deal Sốc

3. **`app/Themes/Website/Controllers/CartController.php`**
   - Tính tổng tại checkout, xử lý coupon, phí ship

### 8.2. Frontend
1. **`app/Themes/Website/Views/cart/checkout.blade.php`**
   - **QUAN TRỌNG NHẤT**: Hàm `updateTotalOrderPriceCheckout()`
   - Logic tính tổng: `Subtotal - Discount + Shipping Fee`
   - Phải tính lại subtotal từ `window.checkoutPriceBreakdowns` khi có thay đổi

2. **`public/js/flash-sale-mixed-price.js`**
   - Tính giá Mixed Pricing và Tiered Pricing
   - Cập nhật tổng tiền trong Cart

---

## 📝 **9. NOTES**

### 9.1. Single Source of Truth
- **Backend**: `CartService::getCart()` là nguồn sự thật duy nhất cho cart summary
- **Frontend Checkout**: `window.checkoutData` được khởi tạo từ backend, KHÔNG được tính lại subtotal ở frontend (trừ khi thay đổi số lượng)

### 9.2. Price Priority
1. Flash Sale (nếu trong khung giờ và còn stock)
2. Marketing Campaign / Promotion
3. Deal Sốc (giá 0đ cho quà tặng)
4. Normal Price

### 9.3. Mixed Pricing
- Khi số lượng > Flash Sale remaining:
  - Phần đầu: Giá Flash Sale
  - Phần còn lại: Giá thường/promo
- Tính tổng: `(flash_sale_qty × flash_sale_price) + (normal_qty × normal_price)`

### 9.4. Tiered Pricing (Maputi)
- Giá phân cấp theo số lượng
- Ví dụ: 1-100: 385,000đ, 101+: 440,000đ
- Tính tổng: `(100 × 385,000) + (11 × 440,000)`

---

## 🔍 **10. DEBUGGING**

### 10.1. Console Logs
- `[Checkout_Price]` - Log giá từ backend
- `[CALC_DEBUG]` - Log tính toán tổng tại checkout
- `[CartService]` - Log operations trong CartService
- `[PriceEngineService]` - Log tính giá

### 10.2. Check Points
1. Kiểm tra `window.checkoutData` trong console
2. Kiểm tra `window.checkoutPriceBreakdowns` khi thay đổi số lượng
3. Kiểm tra `input[name="feeShip"]` có giá trị đúng không
4. Kiểm tra `updateTotalOrderPriceCheckout()` có được gọi đúng không

---

**Cập nhật lần cuối**: 2026-01-24
**Người tạo**: AI Assistant
**Mục đích**: Tài liệu tham khảo cho việc sửa lỗi và phát triển tính năng tính tiền









