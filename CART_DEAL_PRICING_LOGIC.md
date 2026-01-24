# Logic Tính Giá Deal Sốc trong Giỏ Hàng

## Tổng quan

Tài liệu này mô tả chi tiết logic tính giá cho sản phẩm Deal Sốc (mua kèm) trong giỏ hàng, đảm bảo giá Deal được áp dụng đúng cho sản phẩm mua kèm và giá thông thường cho sản phẩm mua trực tiếp.

---

## Quy tắc tính giá

### 1. Mua thông thường (Không phải Deal Sốc)

**Quy tắc ưu tiên:**
```
Flash Sale > Chương trình khuyến mại (Marketing Campaign) > Giá gốc
```

**Áp dụng khi:**
- Sản phẩm được thêm vào giỏ hàng trực tiếp (không qua Deal sốc)
- Sản phẩm được truy cập trực tiếp từ trang chi tiết sản phẩm

**Ví dụ:**
- Giá gốc: 500,000đ
- Flash Sale: 400,000đ (đang active)
- → Giá hiển thị: **400,000đ** (Flash Sale)

---

### 2. Mua sản phẩm A có Deal sốc B (Mua kèm)

**Quy tắc:**
```
Sản phẩm B (mua kèm) LUÔN lấy giá từ Deal Sốc, bất kể có Flash Sale/Promotion hay không
```

**Áp dụng khi:**
- Sản phẩm A được thêm vào giỏ hàng
- Sản phẩm B được thêm vào giỏ hàng qua nút "THÊM NGAY" trong Deal sốc
- Cờ `is_deal = 1` được set trong session cart

**Ví dụ:**
- Giá gốc sản phẩm B: 500,000đ
- Flash Sale sản phẩm B: 400,000đ (đang active)
- Deal sốc giá: **0đ** (được cài trong `/admin/deal/edit/10`)
- → Giá hiển thị trong giỏ hàng: **0đ** (giá Deal sốc, không phải Flash Sale)

---

### 3. Truy cập thẳng vào sản phẩm phụ B

**Quy tắc:**
```
Flash Sale > Chương trình khuyến mại > Giá gốc
```

**Áp dụng khi:**
- Người dùng truy cập trực tiếp vào trang chi tiết sản phẩm B
- Sản phẩm B được thêm vào giỏ hàng từ trang chi tiết (không qua Deal sốc)

**Ví dụ:**
- Giá gốc: 500,000đ
- Flash Sale: 400,000đ (đang active)
- → Giá hiển thị: **400,000đ** (Flash Sale)

---

## Implementation Details

### File: `app/Services/Cart/CartService.php`

#### Method: `getCart()`

**Logic xử lý Deal Sốc (dòng 139-208):**

```php
// Kiểm tra nếu là sản phẩm Deal Sốc
if (!empty($item['is_deal'])) {
    $dealCheck = $this->validateDealAvailability($product->id, $variantId, $quantity);
    
    if ($dealCheck['available']) {
        $isDealItem = !empty($item['is_deal']) && (int)$item['is_deal'] === 1;
        
        if ($isDealItem) {
            // ===== CRITICAL: Logic 2 - Sản phẩm mua kèm LUÔN lấy giá từ Deal Sốc =====
            // Bất kể có Flash Sale/Promotion hay không
            $dealPrice = $this->getDealPrice($product->id, $variantId);
            $newPrice = $dealPrice;
            $newSubtotal = $dealPrice * $quantity;
        } else {
            // Sản phẩm mua thông thường: Áp dụng Deal nếu không có Flash Sale/Promotion
            $dealPricing = $this->applyDealPriceForCartItem(..., false);
        }
    }
}
```

**Điểm quan trọng:**
- Nếu `is_deal = 1`: **BỎ QUA** logic Flash Sale/Promotion, **LUÔN** dùng giá Deal
- Nếu `is_deal = 0`: Tuân thủ quy tắc ưu tiên Flash Sale > Promotion > Deal > Giá gốc

---

#### Method: `applyDealPriceForCartItem()`

**Signature mới:**
```php
private function applyDealPriceForCartItem(
    int $productId, 
    int $variantId, 
    int $quantity, 
    array $priceWithQuantity, 
    bool $isDealItem = false
): ?array
```

**Logic:**

1. **Nếu `$isDealItem = true` (sản phẩm mua kèm):**
   - **LUÔN** trả về giá Deal (kể cả 0đ)
   - **BỎ QUA** kiểm tra Flash Sale/Promotion
   - Trả về `price_breakdown` với `type = 'deal'`

2. **Nếu `$isDealItem = false` (sản phẩm mua thông thường):**
   - Kiểm tra xem có Flash Sale/Promotion không
   - Nếu có → return `null` (không áp dụng Deal)
   - Nếu không → áp dụng Deal nếu `dealPrice < originalPrice` hoặc `dealPrice = 0`

---

#### Method: `getDealPrice()`

**Chức năng:**
- Lấy giá Deal Sốc từ bảng `deal_sales` (SaleDeal model)
- Tìm Deal đang active (status = 1, trong khoảng thời gian start-end)
- Trả về giá Deal cho product/variant cụ thể

**Return:**
- `float`: Giá Deal (có thể là 0đ)
- `0.0`: Nếu không tìm thấy Deal hoặc Deal đã hết hạn

---

### File: `app/Themes/Website/Controllers/CartController.php`

#### Method: `index()`

**Logic tương tự `CartService::getCart()`:**

```php
if (!empty($item['is_deal'])) {
    $dealCheck = $this->validateDealAvailability(...);
    
    if (!$dealCheck['available']) {
        $dealWarning = $dealCheck['message'];
    } else {
        $isDealItem = !empty($item['is_deal']) && (int)$item['is_deal'] === 1;
        
        if ($isDealItem) {
            // Sản phẩm mua kèm: LUÔN lấy giá từ Deal Sốc
            $dealPrice = $this->cartService->getDealPrice(...);
            $priceWithQuantity['total_price'] = $dealPrice * $quantity;
        } else {
            // Sản phẩm mua thông thường: Áp dụng Deal nếu thỏa điều kiện
            $dealPricing = $this->applyDealPriceForCartItem(...);
        }
    }
}
```

---

## Flow Diagram

### Scenario 1: Mua sản phẩm A có Deal sốc B

```
1. User thêm sản phẩm A vào giỏ hàng
   └─> Giá A: Flash Sale > Promotion > Giá gốc

2. User click "THÊM NGAY" cho Deal sốc B
   └─> CartService::addItem($variantId, $qty, $isDeal = true)
   └─> Session cart: items[$variantId]['is_deal'] = 1

3. CartService::getCart() xử lý item B
   └─> Kiểm tra: is_deal = 1? → YES
   └─> BỎ QUA PriceEngineService (Flash Sale/Promotion)
   └─> Gọi getDealPrice() → Lấy giá từ deal_sales.price
   └─> Áp dụng giá Deal (kể cả 0đ)

4. View hiển thị
   └─> Giá B: 0đ (từ Deal sốc)
   └─> Badge: "Deal sốc"
```

---

### Scenario 2: Truy cập thẳng vào sản phẩm B

```
1. User truy cập trang chi tiết sản phẩm B
   └─> PriceEngineService tính giá: Flash Sale > Promotion > Giá gốc

2. User click "Thêm vào giỏ hàng"
   └─> CartService::addItem($variantId, $qty, $isDeal = false)
   └─> Session cart: items[$variantId]['is_deal'] = 0 (hoặc không có)

3. CartService::getCart() xử lý item B
   └─> Kiểm tra: is_deal = 1? → NO
   └─> Gọi PriceEngineService → Tính giá theo Flash Sale/Promotion
   └─> applyDealPriceForCartItem(..., false) → Chỉ áp dụng Deal nếu không có Flash Sale/Promotion

4. View hiển thị
   └─> Giá B: 400,000đ (từ Flash Sale, không phải Deal)
   └─> Không có badge "Deal sốc"
```

---

## Database Schema

### Bảng: `deal_sales` (SaleDeal Model)

**Các trường quan trọng:**
- `deal_id`: ID của Deal
- `product_id`: ID sản phẩm mua kèm
- `variant_id`: ID variant (nullable)
- `price`: **Giá Deal sốc** (có thể là 0đ)
- `qty`: Số lượng suất Deal còn lại
- `buy`: Số lượng đã bán
- `status`: Trạng thái (1 = active)

**Ví dụ:**
```sql
deal_id: 10
product_id: 34
variant_id: 123
price: 0  -- Giá Deal sốc = 0đ
qty: 100
buy: 5
status: 1
```

---

## Test Cases

### Test Case 1: Deal sốc 0đ cho sản phẩm mua kèm

**Setup:**
- Sản phẩm A: Giá gốc 500,000đ
- Sản phẩm B: Giá gốc 500,000đ, Flash Sale 400,000đ (active)
- Deal sốc: Mua A → Tặng B với giá **0đ**

**Steps:**
1. Thêm sản phẩm A vào giỏ hàng
2. Click "THÊM NGAY" cho Deal sốc B

**Expected:**
- Giỏ hàng hiển thị:
  - Sản phẩm A: 500,000đ (giá gốc)
  - Sản phẩm B: **0đ** (giá Deal sốc, không phải Flash Sale 400,000đ)
  - Badge "Deal sốc" trên sản phẩm B

**Actual:** ✅ PASS

---

### Test Case 2: Truy cập thẳng vào sản phẩm B

**Setup:**
- Sản phẩm B: Giá gốc 500,000đ, Flash Sale 400,000đ (active)
- Deal sốc: Mua A → Tặng B với giá 0đ (nhưng user không mua A)

**Steps:**
1. Truy cập trang chi tiết sản phẩm B
2. Click "Thêm vào giỏ hàng"

**Expected:**
- Giỏ hàng hiển thị:
  - Sản phẩm B: **400,000đ** (giá Flash Sale, không phải Deal 0đ)
  - Không có badge "Deal sốc"

**Actual:** ✅ PASS

---

### Test Case 3: Deal sốc giá > 0 cho sản phẩm mua kèm

**Setup:**
- Sản phẩm A: Giá gốc 500,000đ
- Sản phẩm B: Giá gốc 500,000đ, Flash Sale 400,000đ (active)
- Deal sốc: Mua A → Tặng B với giá **250,000đ**

**Steps:**
1. Thêm sản phẩm A vào giỏ hàng
2. Click "THÊM NGAY" cho Deal sốc B

**Expected:**
- Giỏ hàng hiển thị:
  - Sản phẩm A: 500,000đ
  - Sản phẩm B: **250,000đ** (giá Deal sốc, không phải Flash Sale 400,000đ)

**Actual:** ✅ PASS

---

## Debugging

### Log Locations

**CartService logs:**
```php
Log::info('[CartService] Deal Sốc price applied (mua kèm - always use Deal price)', [
    'product_id' => $product->id,
    'variant_id' => $variantId,
    'deal_price' => $dealPrice,
    'quantity' => $quantity,
    'subtotal' => $newSubtotal,
]);
```

**File:** `storage/logs/laravel.log`

**Search for:**
- `[CartService] Deal Sốc price applied`
- `[CartController] Deal Sốc price applied`
- `[CartService] Fallback to variant price`

---

## Key Points

1. **Cờ `is_deal` là quyết định:**
   - `is_deal = 1` → LUÔN dùng giá Deal (bất kể Flash Sale/Promotion)
   - `is_deal = 0` hoặc không có → Tuân thủ quy tắc ưu tiên giá

2. **Giá Deal có thể là 0đ:**
   - Hệ thống CHẤP NHẬN giá 0đ cho Deal sốc
   - Không fallback về giá gốc nếu `is_deal = 1`

3. **Truy cập trực tiếp vs Mua kèm:**
   - Truy cập trực tiếp: Tuân thủ Flash Sale > Promotion > Giá gốc
   - Mua kèm: LUÔN dùng giá Deal

4. **Validation:**
   - Kiểm tra Deal availability (quota, stock) trước khi áp dụng giá
   - Nếu Deal hết → Hiển thị cảnh báo, fallback về giá thường/promo

---

## Related Files

- `app/Services/Cart/CartService.php` - Logic chính tính giá
- `app/Themes/Website/Controllers/CartController.php` - Controller xử lý giỏ hàng
- `app/Themes/Website/Views/cart/index.blade.php` - View hiển thị giỏ hàng
- `app/Modules/Deal/Models/SaleDeal.php` - Model Deal sốc
- `app/Modules/Deal/Models/Deal.php` - Model Deal

---

## Changelog

**2026-01-23:**
- ✅ Sửa logic: Sản phẩm mua kèm (Deal sốc) LUÔN lấy giá từ Deal Sốc, bất kể có Flash Sale/Promotion
- ✅ Thêm tham số `$isDealItem` vào `applyDealPriceForCartItem()`
- ✅ Đổi `getDealPrice()` từ `private` sang `public`
- ✅ Cập nhật `CartController::index()` để áp dụng logic tương tự

---

**Last Updated:** 2026-01-23  
**Author:** AI Assistant  
**Status:** ✅ Implemented & Tested


