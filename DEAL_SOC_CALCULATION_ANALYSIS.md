# PHÂN TÍCH LUỒNG TÍNH TOÁN KHI CÓ DEAL SỐC

## VẤN ĐỀ

**Test kết quả:**
- ✅ Đơn hàng thông thường (không có Deal Sốc): Tính toán 100% chính xác khi có vận chuyển
- ❌ Đơn hàng có Deal Sốc: Tính toán 100% sai khi có vận chuyển

**Log từ console:**
```
[Checkout_Price] subtotal from backend: 875000
[Checkout_Price] sale from backend: 0
[Checkout_Price] feeship from backend: 0
[Checkout_Price] total from backend: 875000
```

**Phân tích:** Subtotal = 875.000đ có vẻ như đang cộng thêm giá Deal sốc (có thể là giá gốc của Deal sốc thay vì 0đ).

## LUỒNG TÍNH TOÁN KHI CÓ DEAL SỐC

### 1. Backend: CartService::getCart()

**File:** `app/Services/Cart/CartService.php`

**Logic xử lý Deal Sốc (is_deal = 1):**

```php
// Dòng 152-199
if ($isDealItem) {
    // Sản phẩm mua kèm: LUÔN lấy giá từ Deal Sốc (kể cả 0đ)
    try {
        $dealPrice = $this->getDealPrice($product->id, $variantId);
        $newPrice = $dealPrice;
        $newSubtotal = $dealPrice * $quantity;
        
        // CRITICAL: Cập nhật priceWithQuantity['total_price'] để đảm bảo tính tổng đúng
        $priceWithQuantity['total_price'] = $newSubtotal;
        
        // Ghi đè breakdown để FE hiển thị đúng
        $priceWithQuantity['price_breakdown'] = [
            [
                'type' => 'deal',
                'quantity' => $quantity,
                'unit_price' => $dealPrice,
                'subtotal' => $newSubtotal,
            ],
        ];
    } catch (\Throwable $e) {
        // Nếu lỗi khi lấy Deal price, vẫn giữ giá 0đ cho Deal Sốc
        $newPrice = 0.0;
        $newSubtotal = 0.0;
        $priceWithQuantity['total_price'] = 0.0;
    }
}
```

**Tính tổng subtotal (dòng 358-364):**

```php
$total = 0.0;
foreach ($items as $it) {
    $itemSubtotal = (float)($it['subtotal'] ?? 0);
    // Kể cả is_deal = 1 (quà tặng, mua kèm) vẫn phải cộng subtotal
    // Nếu Deal 0đ thì subtotal = 0, không làm âm tổng
    $total += $itemSubtotal;
}
```

**✅ Logic đúng:** Nếu Deal Sốc có giá 0đ, `$itemSubtotal = 0`, không ảnh hưởng đến tổng.

### 2. Backend: CartController::recalculatePrices()

**File:** `app/Themes/Website/Controllers/CartController.php`

**Logic tính totalPrice (dòng 405-435):**

```php
$totalPrice = 0.0;
$productsWithPrice = [];

foreach ($cartSummary['items'] as $item) {
    $vId = $item['variant_id'] ?? null;
    if (!$vId) {
        continue;
    }

    // Lấy subtotal từ Service (đã tính đúng với mixed pricing)
    $itemSubtotal = (float)($item['subtotal'] ?? 0);
    $itemSubtotal = max(0.0, $itemSubtotal);
    
    $totalPrice += $itemSubtotal;
    
    // Lưu vào mảng productsWithPrice
    $productsWithPrice[$vId] = [
        'total_price' => $itemSubtotal,
        'price_breakdown' => $item['price_breakdown'] ?? null,
        // ...
    ];
}

// Tính lại từ productsWithPrice để đảm bảo chính xác
$recalculatedTotal = 0.0;
foreach ($productsWithPrice as $vId => $priceData) {
    $recalculatedTotal += (float)($priceData['total_price'] ?? 0);
}
$totalPrice = max(0.0, $recalculatedTotal);
```

**Tạo finalCartJSON (dòng 446-463):**

```php
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

**⚠️ VẤN ĐỀ:** `finalCartJSON` set `final_price = 0.0` cho Deal Sốc, nhưng `products_with_price` vẫn có `total_price` từ `$itemSubtotal` (có thể không phải 0đ nếu Deal price không phải 0đ).

### 3. Frontend: recalculateCartPrices()

**File:** `app/Themes/Website/Views/cart/checkout.blade.php`

**Logic cập nhật subtotal (dòng 636-657):**

```javascript
let backendSubtotal = parseFloat(data.subtotal) || 0;

if (data.products_with_price && Object.keys(data.products_with_price).length > 0) {
    let calculatedSubtotal = 0;
    Object.keys(data.products_with_price).forEach(function(vId) {
        const priceData = data.products_with_price[vId];
        calculatedSubtotal += parseFloat(priceData.total_price) || 0;
    });
    
    // Nếu subtotal từ backend khác với tính toán từ products_with_price, dùng giá trị tính toán
    if (Math.abs(backendSubtotal - calculatedSubtotal) > 1) {
        backendSubtotal = calculatedSubtotal;
    }
}

window.checkoutData.subtotal = backendSubtotal;
```

**⚠️ VẤN ĐỀ:** Frontend tính `calculatedSubtotal` từ `products_with_price`, nhưng `products_with_price` có thể chứa giá Deal sốc (không phải 0đ) nếu backend chưa set đúng.

### 4. Frontend: updateTotalOrderPriceCheckout()

**File:** `app/Themes/Website/Views/cart/checkout.blade.php`

**Logic validation subtotal (dòng 1542-1567):**

```javascript
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
```

**✅ Logic đúng:** Validation tự động sửa subtotal nếu không khớp với `checkoutPriceBreakdowns`.

## NGUYÊN NHÂN TÍNH TOÁN SAI

### Vấn đề 1: Backend có thể trả về giá Deal sốc không phải 0đ

**Nguyên nhân:**
- `CartService::getCart()` lấy `$dealPrice = $this->getDealPrice($product->id, $variantId)`
- Nếu `getDealPrice()` trả về giá không phải 0đ (ví dụ: giá gốc của sản phẩm Deal), thì `$newSubtotal = $dealPrice * $quantity` sẽ không phải 0đ
- `$itemSubtotal` sẽ không phải 0đ, dẫn đến subtotal bị cộng thêm giá Deal sốc

**Giải pháp:**
- Đảm bảo `getDealPrice()` trả về 0đ cho Deal Sốc mua kèm (is_deal = 1)
- Hoặc force set `$newSubtotal = 0.0` cho Deal Sốc mua kèm

### Vấn đề 2: Frontend không validate với finalCartJSON

**Nguyên nhân:**
- Frontend tính `calculatedSubtotal` từ `products_with_price`, nhưng `products_with_price` có thể chứa giá Deal sốc (không phải 0đ)
- `finalCartJSON` có `final_price = 0.0` cho Deal Sốc, nhưng frontend không dùng `finalCartJSON` để validate subtotal

**Giải pháp:**
- Validate subtotal với `finalCartJSON` thay vì `products_with_price`
- Hoặc đảm bảo `products_with_price` có `total_price = 0` cho Deal Sốc

### Vấn đề 3: Phí vận chuyển được thêm vào sau khi tính subtotal

**Nguyên nhân:**
- Khi `getFeeShip()` được gọi, nó cập nhật `window.checkoutData.feeship`
- Sau đó gọi `recalculateCartPrices()` để lấy giá mới
- `recalculateCartPrices()` có thể trả về subtotal sai (có giá Deal sốc)
- `updateTotalOrderPriceCheckout()` tính `finalTotal = subtotal - discount + feeship` với subtotal sai

**Giải pháp:**
- Đảm bảo `recalculateCartPrices()` trả về subtotal đúng (không có giá Deal sốc)
- Validation trong `updateTotalOrderPriceCheckout()` sẽ tự động sửa nếu subtotal sai

## GIẢI PHÁP

### Fix 1: Đảm bảo Deal Sốc có giá 0đ trong CartService

**File:** `app/Services/Cart/CartService.php`

**Thay đổi:**
```php
if ($isDealItem) {
    // Sản phẩm mua kèm: LUÔN lấy giá từ Deal Sốc (kể cả 0đ)
    try {
        $dealPrice = $this->getDealPrice($product->id, $variantId);
        // CRITICAL: Force set giá 0đ cho Deal Sốc mua kèm
        $dealPrice = 0.0; // Deal Sốc mua kèm luôn là 0đ
        $newPrice = $dealPrice;
        $newSubtotal = $dealPrice * $quantity; // = 0
        // ...
    }
}
```

### Fix 2: Đảm bảo products_with_price có total_price = 0 cho Deal Sốc

**File:** `app/Themes/Website/Controllers/CartController.php`

**Thay đổi:**
```php
foreach ($cartSummary['items'] as $item) {
    $vId = $item['variant_id'] ?? null;
    $itemSubtotal = (float)($item['subtotal'] ?? 0);
    
    // CRITICAL: Deal sốc PHẢI LÀ 0đ trong products_with_price
    if (isset($cart->items[$vId]) && !empty($cart->items[$vId]['is_deal'])) {
        $itemSubtotal = 0.0;
    }
    
    $totalPrice += $itemSubtotal;
    $productsWithPrice[$vId] = [
        'total_price' => $itemSubtotal, // = 0 cho Deal Sốc
        // ...
    ];
}
```

### Fix 3: Validate subtotal với finalCartJSON trong Frontend

**File:** `app/Themes/Website/Views/cart/checkout.blade.php`

**Thay đổi:**
```javascript
// Validation: Tính lại từ finalCartJSON (giá chốt)
if (window.finalCartJSON && window.finalCartJSON.length > 0) {
    let calculatedSubtotalFromFinal = 0;
    window.finalCartJSON.forEach(function(item) {
        calculatedSubtotalFromFinal += parseFloat(item.final_price) || 0;
    });
    
    // Nếu subtotal khác với tính toán từ finalCartJSON, dùng giá trị từ finalCartJSON
    if (Math.abs(subtotal - calculatedSubtotalFromFinal) > 1) {
        subtotal = calculatedSubtotalFromFinal;
        window.checkoutData.subtotal = subtotal;
    }
}
```

## KẾT QUẢ KỲ VỌNG

Sau khi fix:
- ✅ Deal Sốc có giá 0đ trong `CartService::getCart()`
- ✅ Deal Sốc có `total_price = 0` trong `products_with_price` (đã force set trong `recalculatePrices()`)
- ✅ Frontend validate subtotal với `finalCartJSON` (giá chốt) - Priority 1
- ✅ Frontend force set `total_price = 0` cho Deal Sốc trong `checkoutPriceBreakdowns`
- ✅ Validation cuối cùng đảm bảo `finalTotal` đúng
- ✅ Tính toán đúng khi có vận chuyển: `finalTotal = subtotal - discount + feeship`

**Ví dụ:**
- Item 1: 875.000đ
- Item 2 (Deal sốc): 0đ
- Phí ship: 28.000đ
- **Tổng: 903.000đ** (đúng: 875.000 - 0 + 28.000)

---

## CÁC FIX ĐÃ THỰC HIỆN

### Fix 1: Backend - Force set Deal Sốc = 0đ trong products_with_price

**File:** `app/Themes/Website/Controllers/CartController.php` - Method `recalculatePrices()`

**Thay đổi:**
```php
// CRITICAL: Lấy cart từ session để check is_deal
$oldCart = Session::get('cart');
$cart = new Cart($oldCart);

foreach ($cartSummary['items'] as $item) {
    $vId = $item['variant_id'] ?? null;
    $itemSubtotal = (float)($item['subtotal'] ?? 0);
    
    // CRITICAL: Deal sốc PHẢI LÀ 0đ trong products_with_price
    if (isset($cart->items[$vId]) && !empty($cart->items[$vId]['is_deal'])) {
        $itemSubtotal = 0.0;
    }
    
    $productsWithPrice[$vId] = [
        'total_price' => $itemSubtotal, // = 0 cho Deal Sốc
        // ...
    ];
}
```

### Fix 2: Frontend - Force set Deal Sốc = 0đ trong checkoutPriceBreakdowns

**File:** `app/Themes/Website/Views/cart/checkout.blade.php` - Function `recalculateCartPrices()`

**Thay đổi:**
```javascript
// Cập nhật window.checkoutPriceBreakdowns với giá mới
// CRITICAL: Đảm bảo Deal Sốc có total_price = 0 trong checkoutPriceBreakdowns
if (data.products_with_price) {
    Object.keys(data.products_with_price).forEach(function(vId) {
        const priceData = data.products_with_price[vId];
        let itemTotalPrice = parseFloat(priceData.total_price) || 0;
        
        // CRITICAL: Nếu item là Deal Sốc (có trong finalCartJSON với final_price = 0), force set total_price = 0
        if (data.final_cart_json && data.final_cart_json.length > 0) {
            const finalItem = data.final_cart_json.find(function(item) {
                return item.variant_id == vId;
            });
            if (finalItem && finalItem.final_price === 0) {
                itemTotalPrice = 0;
            }
        }
        
        window.checkoutPriceBreakdowns[vId] = {
            total_price: itemTotalPrice, // = 0 cho Deal Sốc
            // ...
        };
    });
}
```

### Fix 3: Frontend - Cải thiện validation với finalCartJSON

**File:** `app/Themes/Website/Views/cart/checkout.blade.php` - Function `updateTotalOrderPriceCheckout()`

**Thay đổi:**
```javascript
// Priority 1: Tính từ finalCartJSON (giá chốt - đảm bảo Deal Sốc = 0đ)
// CRITICAL: finalCartJSON là nguồn sự thật cuối cùng, Deal Sốc đã được set = 0đ
if (window.finalCartJSON && window.finalCartJSON.length > 0) {
    let calculatedSubtotalFromFinal = 0;
    window.finalCartJSON.forEach(function(item) {
        calculatedSubtotalFromFinal += parseFloat(item.final_price) || 0;
    });
    
    // CRITICAL: LUÔN dùng giá trị từ finalCartJSON nếu có sự khác biệt
    if (Math.abs(subtotal - calculatedSubtotalFromFinal) > 1) {
        subtotal = calculatedSubtotalFromFinal;
        window.checkoutData.subtotal = subtotal;
    }
}
```

### Fix 4: Frontend - Validation cuối cùng đảm bảo finalTotal đúng

**File:** `app/Themes/Website/Views/cart/checkout.blade.php` - Function `updateTotalOrderPriceCheckout()`

**Thay đổi:**
```javascript
// CRITICAL: Validation cuối cùng - đảm bảo finalTotal đúng
const expectedTotal = subtotal - discount + feeship;
if (Math.abs(finalTotal - expectedTotal) > 1) {
    console.error('[CALC_DEBUG] ❌ FINAL TOTAL MISMATCH!', {
        calculated_finalTotal: finalTotal,
        expected_finalTotal: expectedTotal,
        difference: Math.abs(finalTotal - expectedTotal)
    });
    // Force sửa lại
    const correctedTotal = expectedTotal;
    $('.total-order').text(formatPrice(Math.max(0, correctedTotal)) + 'đ');
    window.checkoutData.total = Math.max(0, correctedTotal);
}
```

### Fix 5: Fix lỗi JavaScript - updateTotalOrderPriceCheckout is not defined

**File:** `app/Themes/Website/Views/cart/checkout.blade.php`

**Thay đổi:**
- Di chuyển `updateTotalOrderPriceCheckout()` ra ngoài `$(document).ready()` để có thể gọi từ bất kỳ đâu
- Đảm bảo hàm được định nghĩa trước khi `recalculateCartPrices()` được gọi

---

**Ngày cập nhật:** 2025-01-23

**Phiên bản:** 1.1

**Thay đổi:**
- Thêm các fix chi tiết cho Deal Sốc
- Thêm validation cuối cùng đảm bảo finalTotal đúng
- Fix lỗi JavaScript updateTotalOrderPriceCheckout is not defined

