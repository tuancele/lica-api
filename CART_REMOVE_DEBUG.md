# Cart Remove Item Debug - Enhanced Logging

## 🔍 Vấn Đề

User báo: **"Khi xóa 1 sản phẩm các sản phẩm khác cũng bị xóa hết"**

Từ logs trước:
- `"session_has_cart":false` - Session không có cart khi API được gọi
- `"removed_variant_ids":[]` - Không có item nào bị xóa
- `"total_qty":0` - Cart trống

## ✅ Giải Pháp - Enhanced Logging

### 1. Enhanced Logging trong CartService

**File:** `app/Services/Cart/CartService.php`

**Thêm logs:**
- Cart state before removal (items count, keys, item exists)
- Cart state after removal (items count before/after, keys before/after)
- Session state after save

### 2. Enhanced Logging trong CartController

**File:** `app/Http/Controllers/Api/V1/CartController.php`

**Thêm logs:**
- Cart state before service call (items count, keys)
- Cart state after service call (items count before/after, keys before/after)
- Session state after save

## 📝 Files Đã Sửa

1. ✅ `app/Services/Cart/CartService.php` - Enhanced logging trong `removeItem()`
2. ✅ `app/Http/Controllers/Api/V1/CartController.php` - Enhanced logging trong `removeItem()`

## 🎯 Mục Đích

Logging chi tiết sẽ giúp:
1. Xác định xem cart có items trước khi xóa không
2. Xác định xem chỉ 1 item bị xóa hay tất cả items bị xóa
3. Xác định xem session có được lưu đúng không
4. Xác định xem có vấn đề gì với session sharing không

## 🧪 Testing

1. **Test:**
   - Thêm nhiều sản phẩm vào cart
   - Xóa 1 sản phẩm
   - Check logs: `php check_cart_logs.php --tail=100`

2. **Expected logs:**
   ```
   [CartService] removeItem - Cart state before:
   - cart_items_count: > 0
   - cart_items_keys: [variant_id1, variant_id2, ...]
   - item_exists: true
   
   [CartService] removeItem - Cart state after:
   - items_count_before: > 0
   - items_count_after: items_count_before - 1
   - items_keys_after: [variant_id2, ...] (không có variant_id1)
   ```

3. **Nếu tất cả items bị xóa:**
   - `items_count_after: 0` → Có vấn đề với Cart model's `removeItem()`
   - `items_keys_after: []` → Có vấn đề với session hoặc Cart object

## ⚠️ Next Steps

Sau khi có logs chi tiết, sẽ xác định được:
1. Vấn đề ở đâu (CartService, Cart model, hoặc Session)
2. Tại sao tất cả items bị xóa
3. Cách fix

---

**Ngày fix:** 2025-01-18  
**Trạng thái:** ✅ Enhanced logging added - Awaiting test results
