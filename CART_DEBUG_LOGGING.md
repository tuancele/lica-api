# Cart Debug Logging - Hệ Thống Ghi Log Chi Tiết

## 🔍 Mục Đích

Tạo hệ thống logging chi tiết để debug các vấn đề:
- Không thể xóa sản phẩm
- Không thể thêm/giảm số lượng
- Session không được lưu
- API không hoạt động

## ✅ Đã Thêm Logging

### 1. Frontend JavaScript Logging

**File:** `app/Themes/Website/Views/cart/index.blade.php`

**Logging Points:**
- ✅ Before remove attempt (variantId, isDeal, timestamp)
- ✅ After remove success (removedVariantIds, summary)
- ✅ On error (full xhr details, status, response)

**File:** `public/js/cart-api-v1.js`

**Logging Points:**
- ✅ Before AJAX request (url, method, variantId, CSRF token)
- ✅ On success (status, data)
- ✅ On failure (status, statusText, responseText, responseJSON)

### 2. Backend Laravel Logging

**File:** `app/Http/Controllers/Api/V1/CartController.php`

**Logging Points:**
- ✅ Request received (variantId, IP, user agent, session ID)
- ✅ Before service call (variantId, userId, session has cart)
- ✅ After service call (result, session has cart)
- ✅ Session saved (session ID)
- ✅ Error with full details (message, file, line, trace)

**File:** `app/Services/Cart/CartService.php`

**Logging Points:**
- ✅ Start removeItem (variantId, userId, session state)
- ✅ Cart state before remove (items count, total qty, total price)
- ✅ Item details (isDeal, productId)
- ✅ After remove item (items count, total qty, total price)
- ✅ Removing related deal items
- ✅ Session put/forget
- ✅ Session saved (session ID, session has cart)
- ✅ Final result

## 📝 Cách Xem Logs

### 1. Browser Console (F12)

**Mở Developer Tools:**
1. Nhấn `F12` hoặc `Ctrl+Shift+I`
2. Chọn tab **Console**
3. Tìm các log bắt đầu với `[CART DEBUG]` hoặc `[CartAPI]`

**Ví dụ:**
```javascript
[CART DEBUG] Remove item attempt: {variantId: 123, isDeal: false, ...}
[CartAPI] removeItem request: {url: "/api/v1/cart/items/123", ...}
[CartAPI] removeItem success: {status: 200, data: {...}}
```

### 2. Laravel Log File

**Location:** `storage/logs/laravel.log`

**Xem log:**
```bash
# Windows PowerShell
Get-Content storage\logs\laravel.log -Tail 100

# Linux/Mac
tail -f storage/logs/laravel.log
```

**Tìm log cart:**
```bash
# Windows PowerShell
Select-String -Path storage\logs\laravel.log -Pattern "\[CART|\[CartService" | Select-Object -Last 50

# Linux/Mac
grep -i "\[CART\|\[CartService" storage/logs/laravel.log | tail -50
```

**Log Format:**
```
[2025-01-18 10:30:45] local.INFO: [CART API] removeItem request {"variant_id":123,"ip":"127.0.0.1",...}
[2025-01-18 10:30:45] local.INFO: [CartService] removeItem start {"variant_id":123,...}
[2025-01-18 10:30:45] local.INFO: [CartService] Session saved {"session_id":"abc123",...}
```

## 🔍 Debug Workflow

### Step 1: Reproduce Issue
1. Mở Browser Console (F12)
2. Thực hiện thao tác (xóa sản phẩm, thêm số lượng, etc.)
3. Xem console logs

### Step 2: Check Network Tab
1. Mở Developer Tools (F12)
2. Chọn tab **Network**
3. Tìm request đến `/api/v1/cart/items/{variant_id}`
4. Xem:
   - Request Headers (CSRF token, etc.)
   - Request Payload
   - Response Status
   - Response Body

### Step 3: Check Laravel Logs
1. Mở `storage/logs/laravel.log`
2. Tìm các log với `[CART API]` hoặc `[CartService]`
3. Xem chi tiết từng bước

### Step 4: Analyze
- **Nếu không có request trong Network:** JavaScript error
- **Nếu request 404:** Route không đúng
- **Nếu request 419:** CSRF token expired
- **Nếu request 500:** Server error (xem Laravel log)
- **Nếu request 200 nhưng UI không update:** JavaScript error

## 🎯 Common Issues & Solutions

### Issue 1: CSRF Token Missing
**Log:**
```
[CartAPI] removeItem request: {csrfToken: "MISSING!"}
```

**Solution:**
- Kiểm tra `<meta name="csrf-token">` trong layout
- Đảm bảo jQuery đã load

### Issue 2: Session Not Saved
**Log:**
```
[CartService] Session saved: {session_has_cart: false}
```

**Solution:**
- Kiểm tra session driver trong `config/session.php`
- Kiểm tra quyền ghi file trong `storage/framework/sessions`

### Issue 3: Item Not Found
**Log:**
```
[CartService] Item not found in cart: {available_items: [...]}
```

**Solution:**
- Item đã bị xóa trước đó
- Session không đồng bộ

## 📊 Log Levels

- **INFO:** Normal operations (request received, item removed, etc.)
- **WARNING:** Potential issues (item not found, etc.)
- **ERROR:** Errors (exceptions, failures)

## 🚀 Next Steps

1. **Reproduce issue** với logging enabled
2. **Check browser console** cho JavaScript errors
3. **Check network tab** cho API requests
4. **Check Laravel logs** cho backend errors
5. **Share logs** để debug tiếp

---

**Ngày tạo:** 2025-01-18  
**Trạng thái:** ✅ Logging system ready
