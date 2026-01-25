# QUY TRÌNH TÍNH TOÁN TẠI /CART

## TỔNG QUAN

Quy trình tính toán giá tại trang giỏ hàng (`/cart`) là một hệ thống phức tạp xử lý nhiều loại giá khác nhau: giá thường, Flash Sale, Deal Sốc, và khuyến mãi. Tài liệu này mô tả chi tiết quy trình tính toán và cách dữ liệu được map sang trang thanh toán.

## 1. LUỒNG DỮ LIỆU TỔNG QUAN

```
Session Cart → CartController::index() → CartService::getCart() → PriceEngineService → View
```

### 1.1. Entry Point: CartController::index()

**File:** `app/Themes/Website/Controllers/CartController.php`

**Chức năng:**
- Đọc giỏ hàng từ Session
- Tính lại giá cho từng item với số lượng thực tế
- Áp dụng logic Deal Sốc
- Trả về view với dữ liệu đã tính toán

**Quy trình:**
1. Lấy cart từ Session: `Session::get('cart')`
2. Duyệt qua từng item trong cart
3. Với mỗi item:
   - Gọi `PriceEngineService::calculatePriceWithQuantity()` để tính giá
   - Kiểm tra Deal Sốc và áp dụng giá Deal nếu có
   - Lưu vào mảng `$productsWithPrice`
4. Tính tổng giá: `$recalculatedTotal`
5. Trả về view với:
   - `$products`: Danh sách items
   - `$totalPrice`: Tổng giá đã tính lại
   - `$productsWithPrice`: Mảng chứa giá chi tiết cho từng item

### 1.2. Single Source of Truth: CartService::getCart()

**File:** `app/Services/Cart/CartService.php`

**Chức năng:**
- Là nguồn dữ liệu duy nhất (Single Source of Truth) cho cart summary
- Tính lại giá cho tất cả items từ PriceEngineService
- Xử lý logic Deal Sốc phức tạp
- Trả về cấu trúc dữ liệu chuẩn cho API và View

**Quy trình tính toán:**

#### Bước 1: Đọc Cart từ Session
```php
$oldCart = session()->has('cart') ? session()->get('cart') : null;
$cart = new Cart($oldCart);
```

#### Bước 2: Duyệt qua từng Item và Tính Giá
```php
foreach ($cart->items as $variantId => $item) {
    // 1. Lấy variant và product
    $variant = Variant::with(['product', 'color', 'size'])->find($variantId);
    $quantity = (int)($item['qty'] ?? 1);
    
    // 2. Tính giá với PriceEngineService (Flash Sale Mixed Pricing)
    $priceWithQuantity = $this->priceEngine->calculatePriceWithQuantity(
        $product->id,
        $variantId,
        $quantity
    );
    
    // 3. Xử lý Deal Sốc
    if (!empty($item['is_deal'])) {
        // Validate Deal availability
        $dealCheck = $this->validateDealAvailability(...);
        
        if ($dealCheck['available']) {
            // Nếu là sản phẩm mua kèm (is_deal = 1): LUÔN dùng giá Deal
            if ($isDealItem) {
                $dealPrice = $this->getDealPrice($product->id, $variantId);
                $newSubtotal = $dealPrice * $quantity;
            } else {
                // Sản phẩm mua thông thường: Áp dụng Deal nếu không có Flash Sale/Promotion
                $dealPricing = $this->applyDealPriceForCartItem(...);
            }
        }
    }
    
    // 4. Lưu vào mảng items
    $items[] = [
        'variant_id' => $variantId,
        'subtotal' => $newSubtotal,
        'price_breakdown' => $priceWithQuantity['price_breakdown'],
        // ...
    ];
    
    // 5. Cộng dồn vào tổng
    $subtotal += $newSubtotal;
}
```

#### Bước 3: Tính Tổng Cuối Cùng
```php
$finalSubtotal = (float)$subtotal;
$finalTotal = (float)max(0, $finalSubtotal - $discount);
```

**Cấu trúc dữ liệu trả về:**
```php
[
    'items' => [...], // Danh sách items với giá đã tính
    'summary' => [
        'total_qty' => int,
        'subtotal' => float, // Tổng giá trước giảm
        'discount' => float, // Giảm giá từ coupon
        'shipping_fee' => float,
        'total' => float, // Tổng cuối cùng
    ],
    'products_with_price' => [...], // Chi tiết giá cho từng variant_id
    'deal_counts' => [...], // Đếm số lượng deal theo deal_id
]
```

### 1.3. PriceEngineService: Tính Giá với Flash Sale Mixed Pricing

**File:** `app/Services/Pricing/PriceEngineService.php`

**Chức năng:**
- Tính giá với số lượng cụ thể
- Xử lý Flash Sale Mixed Pricing (giá khác nhau theo số lượng)
- Trả về price_breakdown chi tiết

**Quy trình:**
1. Lấy thông tin Flash Sale active
2. Lấy thông tin Promotion
3. Tính giá theo số lượng:
   - Nếu có Flash Sale: áp dụng giá Flash Sale cho số lượng trong quota
   - Số lượng còn lại: áp dụng giá Promotion hoặc giá thường
4. Trả về:
   ```php
   [
       'total_price' => float,
       'price_breakdown' => [
           [
               'type' => 'flashsale|promotion|normal',
               'quantity' => int,
               'unit_price' => float,
               'subtotal' => float,
           ],
           // ... nhiều dòng nếu mixed pricing
       ],
       'flash_sale_remaining' => int,
       'warning' => string|null,
   ]
   ```

## 2. LOGIC XỬ LÝ DEAL SỐC

### 2.1. Quy Tắc Ưu Tiên Giá

**Thứ tự ưu tiên:**
1. **Flash Sale** (cao nhất)
2. **Promotion**
3. **Deal Sốc** (chỉ áp dụng khi không có Flash Sale/Promotion)
4. **Giá gốc** (thấp nhất)

### 2.2. Hai Loại Sản Phẩm Deal

#### A. Sản Phẩm Mua Kèm (is_deal = 1)
- **Luôn** lấy giá từ Deal Sốc, bất kể có Flash Sale/Promotion hay không
- Giá có thể là 0đ (quà tặng)
- Số lượng cố định, không thể thay đổi

#### B. Sản Phẩm Mua Thông Thường (is_deal = 0)
- Chỉ áp dụng giá Deal khi:
  - Không có Flash Sale
  - Không có Promotion
  - Giá Deal < giá gốc

### 2.3. Validate Deal Availability

**Kiểm tra:**
1. Deal còn active (status = 1, start <= now <= end)
2. Quỹ Deal còn đủ (qty - buy >= quantity cần)
3. Tồn kho Deal còn đủ (deal_stock > 0 từ deal_hold)

**Nếu Deal hết:**
- Hiển thị cảnh báo: "Quà tặng Deal Sốc đã hết, giá được chuyển về giá thường/khuyến mại"
- Giữ giá từ PriceEngineService (đã là giá thường/promo)

## 3. MAP DỮ LIỆU SANG TRANG THANH TOÁN

### 3.1. Entry Point: CartController::checkout()

**File:** `app/Themes/Website/Controllers/CartController.php`

**Quy trình:**

#### Bước 1: Đồng Bộ Session
```php
$token = md5(Session::getId() . 'checkout_secure');
$cartItems = session()->get('cart', []);
```

#### Bước 2: Tính Lại Tổng Tiền (Server-side Only)
```php
$finalSubtotal = 0;
foreach ($cart->items as $variantId => $item) {
    // Tính giá với PriceEngineService
    $priceInfo = $this->priceEngine->calculatePriceWithQuantity(...);
    
    // Áp dụng Deal Sốc nếu có
    if (!empty($item['is_deal'])) {
        $dealPricing = $this->applyDealPriceForCartItem(...);
    }
    
    $finalSubtotal += $priceInfo['total_price'];
}
```

#### Bước 3: Gọi CartService để Lấy Dữ Liệu Chuẩn
```php
$cartSummary = $this->cartService->getCart();
$totalPrice = 0;
$productsWithPrice = [];

foreach ($cartSummary['items'] as $item) {
    $itemSubtotal = (float)($item['subtotal'] ?? 0);
    $totalPrice += $itemSubtotal;
    
    $productsWithPrice[$variantId] = [
        'total_price' => $itemSubtotal,
        'price_breakdown' => $item['price_breakdown'] ?? null,
        // ...
    ];
}
```

#### Bước 4: Tạo finalCartJSON (Giá Chốt)
```php
$finalCartJSON = [];
foreach ($productsWithPrice as $variantId => $priceData) {
    $finalPrice = (float)($priceData['total_price'] ?? 0);
    
    // CRITICAL: Deal sốc PHẢI LÀ 0đ
    if (isset($cart->items[$variantId]) && !empty($cart->items[$variantId]['is_deal'])) {
        $finalPrice = 0.0;
    }
    
    $finalCartJSON[] = [
        'variant_id' => (int)$variantId,
        'final_price' => $finalPrice,
    ];
}
```

#### Bước 5: Tính Phí Ship
```php
$feeShip = 0;
// Gọi GHTK API hoặc check free ship
```

#### Bước 6: Trả Về View
```php
return view('Website::cart.checkout', [
    'products' => $cart->items,
    'productsWithPrice' => $productsWithPrice,
    'totalPrice' => $totalPrice,
    'sale' => $sale, // Từ coupon
    'feeship' => $feeShip,
    'finalCartJSON' => $finalCartJSON, // CRITICAL: JSON chứa giá chốt
]);
```

### 3.2. Cấu Trúc Dữ Liệu finalCartJSON

**Mục đích:** Đảm bảo giá chốt được gửi đúng sang trang thanh toán, đặc biệt là Deal Sốc phải là 0đ.

**Format:**
```json
[
    {
        "variant_id": 123,
        "final_price": 350000.0
    },
    {
        "variant_id": 456,
        "final_price": 0.0  // Deal Sốc
    }
]
```

**Sử dụng trong Checkout:**
- Frontend có thể dùng để validate giá
- Backend dùng để đối soát khi tạo đơn hàng

## 4. CÁC ĐIỂM QUAN TRỌNG

### 4.1. Single Source of Truth
- **CartService::getCart()** là nguồn dữ liệu duy nhất cho cart summary
- Luôn tính lại giá từ PriceEngineService, không tin tưởng giá trong Session

### 4.2. Tính Toán Giá
- **Luôn** tính lại giá với số lượng thực tế
- Sử dụng `subtotal` từ `price_breakdown`, không dùng `price * qty` (sai với mixed pricing)
- Deal Sốc mua kèm: LUÔN dùng giá Deal, kể cả 0đ

### 4.3. Đồng Bộ Dữ Liệu
- Session được persist ngay sau mỗi thao tác (add/update/remove)
- Checkout tính lại toàn bộ giá từ đầu
- finalCartJSON đảm bảo giá chốt chính xác

### 4.4. Xử Lý Lỗi
- Nếu Deal hết: hiển thị cảnh báo, giữ giá thường/promo
- Nếu tồn kho không đủ: throw exception
- Log chi tiết để debug

## 5. FLOW DIAGRAM

```
┌─────────────────┐
│  User Action   │
│  (Add/Update)  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  CartService    │
│  addItem() /    │
│  updateItem()   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Validate Stock │
│  & Deal Limit   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Save to Session│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Cart Page      │
│  (GET /cart)    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ CartController  │
│ ::index()       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  CartService    │
│  ::getCart()    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ PriceEngine     │
│ calculatePrice  │
│ WithQuantity()  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Apply Deal     │
│  Logic          │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Render View    │
│  with Prices    │
└─────────────────┘
```

## 6. MAP DỮ LIỆU CHUẨN SANG TRANG THANH TOÁN

### 6.1. Dữ Liệu Được Map

**File:** `app/Themes/Website/Views/cart/checkout.blade.php`

**Các biến được truyền từ Controller:**
- `$products`: Danh sách items trong cart
- `$productsWithPrice`: Mảng chứa giá chi tiết cho từng variant_id
- `$totalPrice`: Tổng giá trị đơn hàng (đã tính lại từ CartService)
- `$sale`: Số tiền giảm giá từ coupon
- `$feeship`: Phí vận chuyển
- `$finalCartJSON`: **CRITICAL** - Mảng JSON chứa giá chốt của từng item

### 6.2. JavaScript Global Variables

**Khởi tạo trong checkout.blade.php:**

```javascript
// 1. checkoutData: Tổng giá trị đơn hàng
window.checkoutData = {
    subtotal: {{ $totalPrice ?? 0 }},  // Tổng giá trước giảm
    sale: {{ $sale ?? 0 }},             // Giảm giá từ coupon
    feeship: {{ $feeship ?? 0 }},       // Phí ship
    total: {{ max(0, ($totalPrice ?? 0) - ($sale ?? 0) + ($feeship ?? 0)) }}
};

// 2. finalCartJSON: Giá chốt của từng item (CRITICAL)
window.finalCartJSON = @json($finalCartJSON ?? []);
// Format: [{ variant_id: 123, final_price: 350000.0 }, ...]

// 3. checkoutPriceBreakdowns: Chi tiết price breakdown cho từng item
window.checkoutPriceBreakdowns = {
    [variantId]: {
        total_price: float,
        price_breakdown: [...],
        is_available: true
    }
};
```

### 6.3. Sử Dụng finalCartJSON

**Mục đích:**
- Đảm bảo giá chốt được gửi đúng sang trang thanh toán
- Đặc biệt quan trọng với Deal Sốc (phải là 0đ)
- Frontend có thể validate giá trước khi submit
- Backend dùng để đối soát khi tạo đơn hàng

**Format:**
```json
[
    {
        "variant_id": 123,
        "final_price": 350000.0
    },
    {
        "variant_id": 456,
        "final_price": 0.0  // Deal Sốc - PHẢI LÀ 0đ
    }
]
```

**Validation trong JavaScript:**
```javascript
// Kiểm tra giá chốt trước khi submit
function validateCheckoutPrices() {
    const finalPrices = window.finalCartJSON || [];
    
    // Đối soát với giá hiển thị
    finalPrices.forEach(item => {
        const displayedPrice = getDisplayedPrice(item.variant_id);
        if (Math.abs(item.final_price - displayedPrice) > 0.01) {
            console.error('Price mismatch:', item);
            return false;
        }
    });
    
    return true;
}
```

### 6.4. Hiển Thị Giá Trong Checkout

**Sử dụng `$productsWithPrice`:**
```php
@php
    $variantId = $variant['item']['id'];
    $priceData = $productsWithPrice[$variantId] ?? null;
    $itemTotalPrice = $priceData['total_price'] ?? 0;
@endphp
<span class="fw-600 price-item-{{$variantId}}">
    {{number_format($itemTotalPrice)}}đ
</span>
```

**Lưu ý:**
- Luôn dùng `total_price` từ `$productsWithPrice`, không dùng `price * qty`
- Deal Sốc sẽ có `total_price = 0` (đúng)
- Hiển thị price breakdown nếu có (mixed pricing)

## 7. ĐỒNG BỘ DỮ LIỆU TỪ CART SANG THANH TOÁN

### 7.1. Quy Trình Đã Được Triển Khai

**File:** `app/Themes/Website/Controllers/CartController.php` - Method `checkout()`

**Các bước đã được tối ưu theo quy trình chuẩn:**

1. **Bước 1: Đồng bộ Session** ✓
   - Kiểm tra security token
   - Validate cart không rỗng

2. **Bước 2: Tính lại tổng tiền** ✓
   - Loại bỏ tính toán trùng lặp
   - Sử dụng CartService::getCart() làm Single Source of Truth

3. **Bước 3: Gọi CartService để lấy dữ liệu chuẩn** ✓
   - `$cartSummary = $this->cartService->getCart()`
   - Xây dựng `$productsWithPrice` từ `$cartSummary['items']`
   - Tính `$totalPrice` từ `$productsWithPrice` (không dùng price * qty)

4. **Bước 4: Lấy coupon/sale** ✓
   - Lấy từ Session hoặc cartSummary

5. **Bước 5: Tính phí ship** ✓
   - Gọi GHTK API hoặc check free ship

6. **Bước 6: Tạo finalCartJSON** ✓
   - Đảm bảo Deal Sốc có giá 0đ
   - Format: `[{ variant_id: int, final_price: float }, ...]`

7. **Bước 7: Trả về view** ✓
   - Truyền đầy đủ biến: `products`, `productsWithPrice`, `totalPrice`, `sale`, `feeship`, `finalCartJSON`

### 7.2. JavaScript Validation

**File:** `app/Themes/Website/Views/cart/checkout.blade.php`

**Đã thêm:**
- Function `validateCheckoutPrices()` để kiểm tra giá chốt trước khi submit
- Validation trong `submitHandler` của form
- Log debug chi tiết cho `finalCartJSON`

**Cách sử dụng:**
```javascript
// Tự động validate trước khi submit
if (!validateCheckoutPrices()) {
    alert('Có lỗi xảy ra với giá sản phẩm. Vui lòng tải lại trang và thử lại.');
    return false;
}
```

### 7.3. Các Điểm Đã Được Tối Ưu

1. **Loại bỏ tính toán trùng lặp:**
   - Xóa phần tính `$finalSubtotal` không cần thiết
   - Chỉ sử dụng `CartService::getCart()` làm nguồn dữ liệu

2. **Comment rõ ràng theo quy trình:**
   - Mỗi bước đều có comment theo đúng quy trình trong file .md
   - Dễ dàng trace và maintain

3. **Đảm bảo logic Deal Sốc:**
   - `finalCartJSON` luôn set giá 0đ cho Deal Sốc (is_deal = 1)
   - Log chi tiết để debug

4. **Validation đầy đủ:**
   - Frontend validate giá trước khi submit
   - Backend validate lại khi tạo đơn hàng

## 8. TÍNH LẠI GIÁ KHI CÓ PHÍ VẬN CHUYỂN

### 8.1. Vấn Đề

Khi người dùng nhập địa chỉ và có giá vận chuyển, cần **ép tính toán lại giá 1 lần nữa** từ backend để đảm bảo:
- Giá chính xác với Flash Sale đang chạy
- Giá chính xác với Deal Sốc đang chạy
- Giá chính xác với Promotion đang chạy
- Đồng bộ giá giữa cart và checkout

### 8.2. Giải Pháp

**Endpoint mới:** `GET /cart/recalculate-prices`

**File:** `app/Themes/Website/Controllers/CartController.php` - Method `recalculatePrices()`

**Quy trình:**
1. Gọi `CartService::getCart()` để lấy dữ liệu chuẩn
2. Xây dựng `$productsWithPrice` từ `$cartSummary['items']`
3. Tính `$totalPrice` từ `$productsWithPrice`
4. Tạo `$finalCartJSON` với giá chốt
5. Trả về JSON với đầy đủ dữ liệu

**Response format:**
```json
{
    "success": true,
    "data": {
        "subtotal": 350000.0,
        "sale": 0,
        "code": null,
        "products_with_price": {
            "123": {
                "total_price": 350000.0,
                "price_breakdown": [...],
                "warning": null,
                "deal_warning": null,
                "flash_sale_remaining": 0
            }
        },
        "final_cart_json": [
            {
                "variant_id": 123,
                "final_price": 350000.0
            }
        ],
        "summary": {
            "subtotal": 350000.0,
            "discount": 0,
            "total": 350000.0
        }
    }
}
```

### 8.3. Frontend Implementation

**File:** `app/Themes/Website/Views/cart/checkout.blade.php`

**Hàm mới:** `recalculateCartPrices()`

**Chức năng:**
- Gọi API `/cart/recalculate-prices`
- Cập nhật `window.checkoutData` với giá mới
- Cập nhật `window.checkoutPriceBreakdowns` với giá mới
- Cập nhật `window.finalCartJSON` với giá chốt mới
- Gọi `updateTotalOrderPriceCheckout()` để cập nhật UI

**Tích hợp vào getFeeShip():**
```javascript
function getFeeShip() {
    // ... tính phí ship ...
    
    success: function (res) {
        // Bước 1: Cập nhật phí ship
        const feeShipNum = parseInt(res.feeship.replace(/[^\d]/g, '')) || 0;
        $('input[name="feeShip"]').val(feeShipNum);
        
        // Bước 2: THEO QUY TRÌNH CHUẨN - Ép tính toán lại giá từ backend
        // Đảm bảo giá chính xác với Flash Sale, Deal, Promotion đang chạy
        recalculateCartPrices()
            .done(function() {
                updateTotalOrderPriceCheckout();
            })
            .fail(function() {
                updateTotalOrderPriceCheckout(); // Fallback với giá cũ
            });
    }
}
```

### 8.4. Các Điểm Gọi recalculateCartPrices()

1. **Khi chọn địa chỉ từ autocomplete** (`search_location_input`)
2. **Khi chọn địa chỉ từ dropdown** (`choseAddress`)
3. **Khi thay đổi địa chỉ chi tiết** (`input[name="address"]`)

**Lưu ý:**
- Chỉ gọi sau khi đã có phí ship
- Không block flow nếu lỗi (fallback với giá cũ)
- Log chi tiết để debug

## 9. CẬP NHẬT

**Ngày cập nhật:** 2024-12-19

**Phiên bản:** 1.3

**Thay đổi:**
- Thêm section 8: Tính lại giá khi có phí vận chuyển
- Thêm endpoint `GET /cart/recalculate-prices`
- Thêm hàm `recalculateCartPrices()` trong frontend
- Cập nhật `getFeeShip()` để gọi tính lại giá sau khi có phí ship
- Đảm bảo giá chính xác với Flash Sale, Deal, Promotion đang chạy

**Ghi chú:** Tài liệu này sẽ được cập nhật khi có thay đổi logic tính toán.

---

## 10. FIX BUG TÍNH TOÁN SAI KHI KHÔNG CÓ VOUCHER

### 10.1. Vấn Đề

Khi không có voucher, tổng tiền hiển thị sai (ví dụ: 872.000đ thay vì 372.000đ). Nhưng khi áp dụng voucher, tính toán lại hoàn toàn đúng.

**Nguyên nhân:**
- `applyCoupon()` và `cancelCoupon()` dùng `$cart->totalPrice` từ Session, không phải từ `CartService::getCart()`
- `$cart->totalPrice` từ Session có thể chứa giá cũ/sai (có thể đang cộng thêm giá Deal sốc)
- Khi áp dụng voucher, `updateTotalOrderPriceCheckout()` được gọi và tự động sửa subtotal từ `checkoutPriceBreakdowns`
- Nhưng khi trang load lần đầu, validation không chạy đúng hoặc không được gọi

### 10.2. Giải Pháp

#### A. Fix Backend: `applyCoupon()` và `cancelCoupon()`

**File:** `app/Themes/Website/Controllers/CartController.php`

**Thay đổi:**
- Dùng `CartService::getCart()` thay vì `$cart->totalPrice` từ Session
- Đảm bảo giá được tính đúng với Flash Sale, Deal, Promotion đang chạy

**Code:**
```php
// Trước (SAI):
$cart = new Cart($oldCart);
$detail = Promotion::where([..., ['order_sale', '<=', $cart->totalPrice], ...])->first();

// Sau (ĐÚNG):
$cartSummary = $this->cartService->getCart();
$totalPrice = (float)($cartSummary['summary']['subtotal'] ?? 0);
$detail = Promotion::where([..., ['order_sale', '<=', $totalPrice], ...])->first();
```

#### B. Fix Frontend: Validation khi khởi tạo `window.checkoutData`

**File:** `app/Themes/Website/Views/cart/checkout.blade.php`

**Thay đổi:**
- Tính lại `subtotal` từ `checkoutPriceBreakdowns` khi khởi tạo `window.checkoutData`
- So sánh với `$totalPrice` từ backend
- Nếu không khớp, dùng giá trị tính từ `checkoutPriceBreakdowns`

**Code:**
```javascript
// Tính lại subtotal từ checkoutPriceBreakdowns
let calculatedSubtotalFromBreakdowns = 0;
if (window.checkoutPriceBreakdowns && Object.keys(window.checkoutPriceBreakdowns).length > 0) {
    Object.keys(window.checkoutPriceBreakdowns).forEach(function(vId) {
        const itemData = window.checkoutPriceBreakdowns[vId];
        if (itemData && itemData.total_price !== undefined) {
            calculatedSubtotalFromBreakdowns += parseFloat(itemData.total_price) || 0;
        }
    });
}

// Sử dụng giá trị tính từ breakdowns nếu khác với $totalPrice từ backend
let validatedSubtotal = {{ $totalPrice ?? 0 }};
if (Math.abs(validatedSubtotal - calculatedSubtotalFromBreakdowns) > 1 && calculatedSubtotalFromBreakdowns > 0) {
    validatedSubtotal = calculatedSubtotalFromBreakdowns;
}

window.checkoutData = {
    subtotal: validatedSubtotal, // Dùng giá trị đã được validate
    // ...
};
```

#### C. Fix Frontend: Validation trong `updateTotalOrderPriceCheckout()`

**File:** `app/Themes/Website/Views/cart/checkout.blade.php`

**Thay đổi:**
- Thêm validation để tính lại `subtotal` từ `checkoutPriceBreakdowns` nếu không khớp với `checkoutData.subtotal`
- Tự động sửa và cập nhật `window.checkoutData.subtotal`

**Code:**
```javascript
function updateTotalOrderPriceCheckout() {
    let subtotal = parseFloat(window.checkoutData.subtotal) || 0;
    
    // Validation: Tính lại từ checkoutPriceBreakdowns
    if (window.checkoutPriceBreakdowns && Object.keys(window.checkoutPriceBreakdowns).length > 0) {
        let calculatedSubtotal = 0;
        Object.keys(window.checkoutPriceBreakdowns).forEach(function(vId) {
            const itemData = window.checkoutPriceBreakdowns[vId];
            if (itemData && itemData.total_price !== undefined) {
                calculatedSubtotal += parseFloat(itemData.total_price) || 0;
            }
        });
        
        // Nếu không khớp, dùng giá trị tính từ breakdowns
        if (Math.abs(subtotal - calculatedSubtotal) > 1) {
            subtotal = calculatedSubtotal;
            window.checkoutData.subtotal = subtotal;
        }
    }
    
    // Tính tổng
    const finalTotal = subtotal - discount + feeship;
    // ...
}
```

#### D. Đảm bảo `updateTotalOrderPriceCheckout()` được gọi khi trang load

**File:** `app/Themes/Website/Views/cart/checkout.blade.php`

**Thay đổi:**
- Tăng timeout từ 100ms lên 200ms để đảm bảo tất cả biến đã được khởi tạo
- Thêm log để debug

**Code:**
```javascript
$(document).ready(function() {
    setTimeout(function() {
        if (typeof updateTotalOrderPriceCheckout === 'function') {
            updateTotalOrderPriceCheckout();
            console.log('[Checkout_Init] updateTotalOrderPriceCheckout() called on page load');
        }
    }, 200);
});
```

### 10.3. Validation trong `recalculateCartPrices()`

**File:** `app/Themes/Website/Views/cart/checkout.blade.php`

**Thay đổi:**
- Validate `subtotal` từ backend với tổng tính từ `products_with_price`
- Nếu không khớp, dùng giá trị tính từ `products_with_price`

**Code:**
```javascript
function recalculateCartPrices() {
    // ...
    success: function (res) {
        let backendSubtotal = parseFloat(data.subtotal) || 0;
        
        // Validate với products_with_price
        if (data.products_with_price && Object.keys(data.products_with_price).length > 0) {
            let calculatedSubtotal = 0;
            Object.keys(data.products_with_price).forEach(function(vId) {
                calculatedSubtotal += parseFloat(data.products_with_price[vId].total_price) || 0;
            });
            
            if (Math.abs(backendSubtotal - calculatedSubtotal) > 1) {
                backendSubtotal = calculatedSubtotal;
            }
        }
        
        window.checkoutData.subtotal = backendSubtotal;
        // ...
    }
}
```

### 10.4. Kết Quả

Sau khi fix:
- ✅ Tính toán đúng ngay khi trang load (không cần đợi áp dụng voucher)
- ✅ `applyCoupon()` và `cancelCoupon()` dùng `CartService::getCart()` (Single Source of Truth)
- ✅ Validation tự động sửa subtotal nếu sai
- ✅ Log debug chi tiết để theo dõi

**Ví dụ:**
- Item 1: 350.000đ
- Item 2 (Deal sốc): 0đ
- Phí ship: 22.000đ
- **Tổng: 372.000đ** (đúng: 350.000 - 0 + 22.000)

---

**Ngày cập nhật:** 2025-01-23

**Phiên bản:** 1.4

**Thay đổi:**
- Thêm section 10: Fix bug tính toán sai khi không có voucher
- Fix `applyCoupon()` và `cancelCoupon()` để dùng `CartService::getCart()`
- Thêm validation khi khởi tạo `window.checkoutData`
- Cải thiện validation trong `updateTotalOrderPriceCheckout()`
- Đảm bảo `updateTotalOrderPriceCheckout()` được gọi khi trang load
- Thêm validation trong `recalculateCartPrices()`

---

## 11. LUÔN LẤY GIÁ TRỊ SẢN PHẨM THỜI GIAN THỰC KHI TRANG CHECKOUT LOAD

### 11.1. Yêu Cầu

**Vấn đề:** Trang checkout có thể hiển thị giá cũ từ session hoặc giá đã render trong HTML, không phản ánh giá thời gian thực từ backend.

**Yêu cầu:** Trang checkout phải **luôn lấy giá trị sản phẩm thời gian thực** từ backend khi trang load, đảm bảo giá chính xác với Flash Sale, Deal, Promotion đang chạy.

### 11.2. Giải Pháp

#### A. Tự Động Gọi API Khi Trang Load

**File:** `app/Themes/Website/Views/cart/checkout.blade.php`

**Thay đổi:**
- Khi trang checkout load, tự động gọi `recalculateCartPrices()` để lấy giá mới nhất từ backend
- Không tin tưởng vào giá đã render trong HTML
- Đảm bảo giá luôn chính xác với Flash Sale, Deal, Promotion đang chạy

**Code:**
```javascript
// ===== CRITICAL: LUÔN LẤY GIÁ TRỊ SẢN PHẨM THỜI GIAN THỰC KHI TRANG LOAD =====
$(document).ready(function() {
    setTimeout(function() {
        console.log('[Checkout_Init] Starting real-time price fetch from backend...');
        
        // CRITICAL: Gọi recalculateCartPrices() để lấy giá thời gian thực từ backend
        if (typeof recalculateCartPrices === 'function') {
            recalculateCartPrices()
                .done(function() {
                    console.log('[Checkout_Init] Real-time prices fetched and updated successfully');
                    // Sau khi có giá mới, gọi updateTotalOrderPriceCheckout() để cập nhật UI
                    if (typeof updateTotalOrderPriceCheckout === 'function') {
                        updateTotalOrderPriceCheckout();
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('[Checkout_Init] Failed to fetch real-time prices', {
                        status: status,
                        error: error,
                        response: xhr.responseJSON
                    });
                    // Fallback: Vẫn gọi updateTotalOrderPriceCheckout() với giá đã render
                    if (typeof updateTotalOrderPriceCheckout === 'function') {
                        updateTotalOrderPriceCheckout();
                    }
                });
        }
    }, 300); // Đợi 300ms để đảm bảo tất cả biến và hàm đã được khởi tạo
});
```

#### B. Validation Logic Tính Tổng

**File:** `app/Themes/Website/Views/cart/checkout.blade.php`

**Thay đổi:**
- Thêm validation để đảm bảo `finalTotal = subtotal - discount + feeship`
- Không được trừ thêm bất kỳ giá trị nào khác (như giá Deal sốc)
- Log cảnh báo nếu phát hiện tính toán sai

**Code:**
```javascript
// 3. Phép tính duy nhất và cuối cùng
// CRITICAL: Đảm bảo công thức đúng: finalTotal = subtotal - discount + feeship
// KHÔNG được trừ thêm bất kỳ giá trị nào khác (như giá Deal sốc)
const finalTotal = subtotal - discount + feeship;

// Validation: Đảm bảo finalTotal không âm và hợp lý
if (finalTotal < 0) {
    console.error('[Checkout_Calc] finalTotal is negative!', {
        subtotal: subtotal,
        discount: discount,
        feeship: feeship,
        finalTotal: finalTotal
    });
}

// Validation: Đảm bảo finalTotal >= subtotal - discount (không được nhỏ hơn)
const minExpectedTotal = subtotal - discount;
if (finalTotal < minExpectedTotal) {
    console.error('[Checkout_Calc] finalTotal is less than expected!', {
        subtotal: subtotal,
        discount: discount,
        feeship: feeship,
        finalTotal: finalTotal,
        minExpectedTotal: minExpectedTotal,
        difference: minExpectedTotal - finalTotal
    });
}
```

### 11.3. Quy Trình Hoạt Động

1. **Trang checkout load:**
   - Khởi tạo `window.checkoutData` và `window.checkoutPriceBreakdowns` từ giá đã render
   - Đợi 300ms để đảm bảo tất cả biến đã được khởi tạo

2. **Tự động gọi API:**
   - Gọi `recalculateCartPrices()` để lấy giá mới nhất từ backend
   - Backend gọi `CartService::getCart()` để tính lại giá với Flash Sale, Deal, Promotion mới nhất

3. **Cập nhật dữ liệu:**
   - Cập nhật `window.checkoutData.subtotal` với giá mới từ backend
   - Cập nhật `window.checkoutPriceBreakdowns` với giá mới từ backend
   - Cập nhật `window.finalCartJSON` với giá chốt mới

4. **Cập nhật UI:**
   - Gọi `updateTotalOrderPriceCheckout()` để cập nhật hiển thị
   - Validation tự động sửa subtotal nếu sai
   - Tính tổng: `finalTotal = subtotal - discount + feeship`

### 11.4. Kết Quả

Sau khi fix:
- ✅ Trang checkout tự động lấy giá thời gian thực từ backend khi load
- ✅ Giá luôn chính xác với Flash Sale, Deal, Promotion đang chạy
- ✅ Validation tự động sửa subtotal nếu sai
- ✅ Đảm bảo `finalTotal = subtotal - discount + feeship` (không trừ nhầm)
- ✅ Log debug chi tiết để theo dõi

**Ví dụ:**
- Item 1: 2.975.000đ (1x350.000 + 5x525.000)
- Item 2 (Deal sốc): 0đ
- Phí ship: 35.000đ
- **Tổng: 3.010.000đ** (đúng: 2.975.000 - 0 + 35.000)

---

**Ngày cập nhật:** 2025-01-23

**Phiên bản:** 1.5

**Thay đổi:**
- Thêm section 11: Luôn lấy giá trị sản phẩm thời gian thực khi trang checkout load
- Tự động gọi `recalculateCartPrices()` khi trang load
- Thêm validation để đảm bảo `finalTotal = subtotal - discount + feeship` (không trừ nhầm)
- Đảm bảo giá luôn chính xác với Flash Sale, Deal, Promotion đang chạy

**Ghi chú:** Tài liệu này sẽ được cập nhật khi có thay đổi logic tính toán.

