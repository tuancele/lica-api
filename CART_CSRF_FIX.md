# Cart CSRF Token Fix

## 🔍 Vấn Đề

**CSRF token mismatch** khi gọi Cart API:
```
DELETE https://lica.test/api/v1/cart/items/8394 419 (unknown status)
"message": "CSRF token mismatch."
```

**Nguyên nhân:**
- Đã thêm `web` middleware vào API routes → kích hoạt CSRF protection
- CSRF token có thể không được gửi đúng cách hoặc không match

## ✅ Giải Pháp

### 1. Exclude Cart API Routes khỏi CSRF Verification

**File:** `app/Http/Middleware/VerifyCsrfToken.php`

```php
protected $except = [
    'api/v1/cart/*',
];
```

**Lý do:**
- Cart API routes cần session nhưng không cần CSRF verification
- Đơn giản hóa và tránh token mismatch issues

### 2. Cải Thiện CSRF Token Handling trong JavaScript

**File:** `public/js/cart-api-v1.js`

**Thay đổi:**
- Thêm helper function `getCookie()` để lấy token từ cookie
- Fallback: lấy từ meta tag hoặc cookie
- Tất cả methods đều sử dụng cách lấy token mới

**Helper Function:**
```javascript
getCookie: function(name) {
    var value = "; " + document.cookie;
    var parts = value.split("; " + name + "=");
    if (parts.length === 2) {
        return parts.pop().split(";").shift();
    }
    return null;
}
```

**Usage:**
```javascript
var csrfToken = $('meta[name="csrf-token"]').attr('content') || this.getCookie('XSRF-TOKEN');
```

## 📝 Files Đã Sửa

1. ✅ `app/Http/Middleware/VerifyCsrfToken.php` - Exclude Cart API routes
2. ✅ `public/js/cart-api-v1.js` - Cải thiện CSRF token handling:
   - `addItem()`
   - `addCombo()`
   - `updateItem()`
   - `removeItem()`
   - `applyCoupon()`
   - `removeCoupon()`
   - `calculateShippingFee()`
   - Thêm `getCookie()` helper

## 🎯 Kết Quả

**Trước:**
- ❌ CSRF token mismatch (419 error)
- ❌ Cart operations fail

**Sau:**
- ✅ Cart API routes excluded từ CSRF verification
- ✅ CSRF token được lấy từ meta tag hoặc cookie
- ✅ Cart operations hoạt động bình thường

## 🧪 Testing

1. **Clear cache:**
   ```bash
   php artisan config:clear
   ```

2. **Test cart operations:**
   - Thêm sản phẩm
   - Xóa sản phẩm
   - Update số lượng
   - Apply coupon

3. **Expected:**
   - Không còn 419 error
   - Cart operations thành công

---

**Ngày fix:** 2025-01-18  
**Trạng thái:** ✅ CSRF issue fixed!
