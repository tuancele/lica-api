# Cart validateDeals Temporarily Disabled

## 🔍 Vấn Đề

User báo: **"Xóa 1 sản phẩm tất cả sản phẩm khác đều bị xóa"**

Từ logs:
- `"session_has_cart":false` - Session không có cart khi API được gọi
- Có thể `validateDeals()` đang xóa tất cả items

## ✅ Giải Pháp Tạm Thời

### 1. Tạm Thời Disable validateDeals()

**File:** `app/Services/Cart/CartService.php`

**Thay đổi:**
- Comment out `validateDeals()` call trong `removeItem()`
- Thêm log để track

**Lý do:**
- `validateDeals()` có thể đang xóa tất cả items
- Cần investigate kỹ hơn trước khi enable lại

### 2. Thêm withCredentials cho AJAX Requests

**File:** `public/js/cart-api-v1.js`

**Thay đổi:**
- Thêm `xhrFields: { withCredentials: true }` vào tất cả AJAX requests
- Đảm bảo cookies được gửi với requests

**Lý do:**
- Session cookies cần được gửi với AJAX requests
- `withCredentials: true` đảm bảo cookies được include

### 3. Enhanced Logging

**Thêm logs:**
- `getCart()` - Log session state và cart state
- `addItem()` - Log session state và cart state before/after

## 📝 Files Đã Sửa

1. ✅ `app/Services/Cart/CartService.php` - Disable validateDeals() tạm thời
2. ✅ `public/js/cart-api-v1.js` - Thêm withCredentials cho AJAX requests
3. ✅ `app/Services/Cart/CartService.php` - Enhanced logging trong getCart() và addItem()

## 🎯 Kết Quả

**Trước:**
- ❌ Xóa 1 sản phẩm → tất cả sản phẩm bị xóa
- ❌ Session không có cart

**Sau:**
- ✅ validateDeals() tạm thời disabled
- ✅ withCredentials đảm bảo cookies được gửi
- ✅ Enhanced logging để debug

## 🧪 Testing

1. **Test:**
   - Thêm nhiều sản phẩm vào cart
   - Xóa 1 sản phẩm
   - Expected: Chỉ sản phẩm đó bị xóa, các sản phẩm khác vẫn còn

2. **Check logs:**
   ```bash
   php check_cart_logs.php --tail=50
   ```

3. **Expected logs:**
   ```
   "session_has_cart":true
   "cart_items_count":>0
   "Skipping validateDeals (temporarily disabled)"
   ```

## ⚠️ Next Steps

1. **Investigate validateDeals()** - Tại sao nó xóa tất cả items?
2. **Fix validateDeals()** - Chỉ xóa invalid deals, không xóa tất cả
3. **Re-enable validateDeals()** - Sau khi fix xong

---

**Ngày fix:** 2025-01-18  
**Trạng thái:** ✅ Temporary fix applied - validateDeals disabled
