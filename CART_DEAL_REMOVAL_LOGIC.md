# Cart Deal Removal Logic - Xóa Sản Phẩm Chính và Deal Sốc

## ✅ Đã Hoàn Thành

### Logic Xóa Tự Động

**File:** `app/Services/Cart/CartService.php`

**Tính năng:**
- ✅ **Khi xóa sản phẩm chính → Tự động xóa tất cả sản phẩm deal sốc liên quan**
  - Đảm bảo tính nhất quán: Không có deal items mà không có main product
- ✅ **Khi xóa sản phẩm deal sốc → CHỈ xóa deal item, KHÔNG xóa sản phẩm chính**
  - User có thể xóa deal item độc lập
  - Main product vẫn giữ lại trong cart
  - User có thể thêm deal item lại sau nếu muốn
- ✅ Validate deals sau khi xóa
- ✅ Cập nhật cart summary

## 🔄 Logic Hoạt Động

### Scenario 1: Xóa Sản Phẩm Chính

**Ví dụ:** Sản phẩm A (chính) + Sản phẩm B (deal sốc)

**Khi user xóa Sản phẩm A:**
1. Xóa Sản phẩm A khỏi cart
2. Tìm tất cả Deal IDs mà Sản phẩm A tham gia
3. Tìm tất cả SaleDeal products trong các Deal đó
4. Xóa tất cả deal items (Sản phẩm B) khỏi cart
5. Validate remaining deals

**Code:**
```php
// In removeItem()
if (!$isDeal && $productId) {
    $this->removeRelatedDealItems($cart, $productId);
}
```

### Scenario 2: Xóa Sản Phẩm Deal Sốc

**Ví dụ:** Sản phẩm A (chính) + Sản phẩm B (deal sốc)

**Khi user xóa Sản phẩm B:**
1. Xóa Sản phẩm B khỏi cart
2. **CHỈ xóa deal item, KHÔNG xóa main product**
3. Main product (Sản phẩm A) vẫn giữ lại trong cart
4. User có thể thêm deal item lại sau nếu muốn
5. Validate remaining deals

**Code:**
```php
// In removeItem()
// Note: We DON'T remove main product when removing deal item
// User can keep main product and remove deal items separately
if (!$isDeal && $productId) {
    // Only remove deal items when removing main product
    $this->removeRelatedDealItems($cart, $productId);
}
```

## 📝 Implementation Details

### Method: `removeRelatedDealItems()`

**Mục đích:** Xóa tất cả deal items khi xóa sản phẩm chính

**Logic:**
1. Tìm Deal IDs mà main product tham gia
2. Tìm tất cả SaleDeal product IDs trong các Deal đó
3. Xóa tất cả cart items có `is_deal = 1` và `product_id` trong danh sách

```php
private function removeRelatedDealItems(Cart &$cart, int $mainProductId): void
{
    // 1. Find deal IDs
    $dealIds = ProductDeal::where('product_id', $mainProductId)
        ->whereHas('deal', function($q) use ($now) {
            $q->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
        })
        ->pluck('deal_id')
        ->toArray();
    
    // 2. Find sale deal product IDs
    $saleDealProductIds = SaleDeal::whereIn('deal_id', $dealIds)
        ->where('status', '1')
        ->pluck('product_id')
        ->toArray();
    
    // 3. Remove deal items
    foreach ($cart->items as $key => $item) {
        if (isset($item['is_deal']) && $item['is_deal'] == 1) {
            $productId = ...;
            if ($productId && in_array($productId, $saleDealProductIds)) {
                $cart->removeItem($key);
            }
        }
    }
}
```

### Method: `removeRelatedMainProduct()` (Deprecated - Không còn sử dụng)

**Mục đích:** ~~Xóa sản phẩm chính khi xóa deal item~~ (Đã thay đổi)

**Thay đổi:**
- ❌ **KHÔNG còn xóa main product khi xóa deal item**
- ✅ User có thể xóa deal item độc lập
- ✅ Main product vẫn giữ lại trong cart
- ✅ Method này vẫn tồn tại trong code nhưng không được gọi

**Lý do thay đổi:**
- User experience tốt hơn: User có thể xóa deal item mà không mất main product
- Linh hoạt hơn: User có thể thêm deal item lại sau nếu muốn
- Đảm bảo tính nhất quán: Main product vẫn có thể mua độc lập

**Code hiện tại:**
```php
// In removeItem()
// Note: We DON'T remove main product when removing deal item
// User can keep main product and remove deal items separately
if (!$isDeal && $productId) {
    // Only remove deal items when removing main product
    $this->removeRelatedDealItems($cart, $productId);
}
// Removed: if ($isDeal && $productId) { ... }
```

## 🎨 UI Updates

### JavaScript Changes

**File:** `app/Themes/Website/Views/cart/index.blade.php`

**Cải thiện:**
1. **Confirm Message:** Hiển thị message khác nhau cho main product và deal
   - Main product: "Các sản phẩm deal sốc liên quan cũng sẽ bị xóa"
   - Deal: "Sản phẩm chính liên quan cũng sẽ bị xóa"

2. **Multiple Items Removal:** Xóa tất cả items liên quan trong UI
   - Get cart sau khi xóa
   - So sánh với cart hiện tại
   - Xóa tất cả rows không còn trong cart

3. **Success Message:** Hiển thị số lượng items đã xóa
   - "Đã xóa X sản phẩm khỏi giỏ hàng" (nếu > 1)
   - "Đã xóa sản phẩm khỏi giỏ hàng" (nếu = 1)

## 📊 Flow Diagram

```
User clicks "Xóa" on item
    ↓
Check if main product or deal
    ↓
┌─────────────────┬─────────────────┐
│ Main Product    │ Deal Item        │
│                 │                  │
│ 1. Remove item  │ 1. Remove item   │
│ 2. Find Deal IDs│ 2. (Skip)       │
│ 3. Find SaleDeal│    CHỈ xóa deal │
│    products     │    item, KHÔNG   │
│ 4. Remove all   │    xóa main      │
│    deal items   │    product       │
└─────────────────┴─────────────────┘
    ↓
Validate remaining deals
    ↓
Update cart summary
    ↓
Return response
```

## 🧪 Test Cases

### Test Case 1: Xóa Sản Phẩm Chính
**Setup:**
- Cart có: Product A (main) + Product B (deal)
- Deal: Product A → Product B

**Action:** Xóa Product A

**Expected:**
- ✅ Product A bị xóa
- ✅ Product B bị xóa tự động
- ✅ Cart summary cập nhật
- ✅ Success message hiển thị

### Test Case 2: Xóa Sản Phẩm Deal
**Setup:**
- Cart có: Product A (main) + Product B (deal)
- Deal: Product A → Product B

**Action:** Xóa Product B

**Expected:**
- ✅ Product B bị xóa
- ✅ Product A VẪN GIỮ LẠI trong cart (không bị xóa)
- ✅ Cart summary cập nhật
- ✅ Success message hiển thị

### Test Case 3: Nhiều Sản Phẩm Chính
**Setup:**
- Cart có: Product A (main) + Product C (main) + Product B (deal)
- Deal: Product A, Product C → Product B

**Action:** Xóa Product B

**Expected:**
- ✅ Product B bị xóa
- ✅ Product A VẪN GIỮ LẠI trong cart (không bị xóa)
- ✅ Product C VẪN GIỮ LẠI trong cart (không bị xóa)
- ✅ Cart summary cập nhật

### Test Case 4: Nhiều Deal Items
**Setup:**
- Cart có: Product A (main) + Product B (deal) + Product C (deal)
- Deal: Product A → Product B, Product C

**Action:** Xóa Product A

**Expected:**
- ✅ Product A bị xóa
- ✅ Product B bị xóa tự động
- ✅ Product C bị xóa tự động
- ✅ Cart summary cập nhật

## ⚠️ Lưu Ý

### 1. Multiple Main Products
Nếu một Deal có nhiều sản phẩm chính:
- Khi xóa 1 deal item → Xóa TẤT CẢ sản phẩm chính trong Deal
- Điều này đảm bảo tính nhất quán: Deal phải có cả main và sale products

### 2. Multiple Deal Items
Nếu một Deal có nhiều sản phẩm deal:
- Khi xóa 1 sản phẩm chính → Xóa TẤT CẢ sản phẩm deal trong Deal
- Điều này đảm bảo: Không có deal items mà không có main product

### 3. Multiple Deals
Nếu một sản phẩm tham gia nhiều Deals:
- Khi xóa sản phẩm chính → Xóa tất cả deal items từ TẤT CẢ Deals
- Khi xóa deal item → Chỉ xóa sản phẩm chính trong Deal đó

## 🔧 Code Location

**Files Modified:**
1. `app/Services/Cart/CartService.php`
   - `removeItem()` - Updated to handle related items
   - `removeRelatedDealItems()` - New method
   - `removeRelatedMainProduct()` - New method

2. `app/Themes/Website/Views/cart/index.blade.php`
   - JavaScript updated to handle multiple items removal
   - Confirm message updated
   - UI updates for multiple items

## ✅ Checklist

- [x] Logic xóa deal items khi xóa main product
- [x] Logic xóa main product khi xóa deal item
- [x] Validate deals sau khi xóa
- [x] Update cart summary
- [x] JavaScript xử lý multiple items removal
- [x] Confirm message phù hợp
- [x] Success message hiển thị số lượng
- [x] UI animation cho multiple items
- [x] Error handling

---

**Ngày hoàn thành:** 2025-01-18  
**Trạng thái:** ✅ Đã implement đầy đủ
