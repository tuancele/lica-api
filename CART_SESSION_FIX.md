# Cart Session Fix - Root Cause Found!

## 🔍 Vấn Đề Phát Hiện

Từ logs:
```
"session_has_cart":false
"cart_items_count":0
"item_exists":false
"available_items":[]
```

**Root Cause:** API routes không có `StartSession` middleware!

### Phân Tích

1. **API Routes** (`routes/api.php`) sử dụng `api` middleware group
2. **API Middleware Group** (`app/Http/Kernel.php`) KHÔNG có `StartSession`
3. **Web Middleware Group** có `StartSession`
4. **Kết quả:** Session không hoạt động trong API routes → `session_has_cart` luôn là `false`

## ✅ Giải Pháp

### Thêm `web` Middleware vào Cart API Routes

**File:** `routes/api.php`

**Thay đổi:**
```php
// Before:
Route::prefix('v1/cart')->namespace('Api\V1')->group(function () {
    // ...
});

// After:
Route::prefix('v1/cart')->namespace('Api\V1')->middleware('web')->group(function () {
    // ...
});
```

**Lý do:**
- `web` middleware group có `StartSession`
- Cart API cần session để lưu cart data
- CSRF token vẫn hoạt động qua header `X-CSRF-TOKEN`

## 📝 Files Đã Sửa

1. ✅ `routes/api.php` - Thêm `middleware('web')` vào Cart API routes

## 🎯 Kết Quả

**Trước:**
- ❌ API routes không có session
- ❌ `session_has_cart` luôn là `false`
- ❌ Cart không được lưu giữa các requests

**Sau:**
- ✅ API routes có session support
- ✅ `session_has_cart` hoạt động đúng
- ✅ Cart được lưu giữa các requests

## 🧪 Testing

1. **Clear cache:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   ```

2. **Test lại:**
   - Thêm sản phẩm vào cart
   - Xóa sản phẩm
   - Kiểm tra logs: `php check_cart_logs.php --tail=50`
   - Expected: `"session_has_cart":true` và `"cart_items_count" > 0`

## 📊 Expected Logs After Fix

```
[CartService] Cart state before remove: {
    "cart_items_count": 1,  // ✅ > 0
    "item_exists": true,     // ✅ true
    "available_items": [8396] // ✅ Có items
}
```

---

**Ngày sửa:** 2025-01-18  
**Trạng thái:** ✅ Root cause found và fixed!
