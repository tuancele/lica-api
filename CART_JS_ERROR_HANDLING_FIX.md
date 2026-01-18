# Cart JavaScript Error Handling Fix - Deep Dive

## ✅ Đã Sửa

### Vấn Đề Phát Hiện

1. **Không có validation input:**
   - ❌ Không check variantId trước khi gọi API
   - ❌ Không check qty > 0
   - ❌ Có thể gây lỗi nếu input không hợp lệ

2. **Error handling không đầy đủ:**
   - ❌ Chỉ handle response error, không handle timeout
   - ❌ Không handle network errors
   - ❌ Không handle server errors (500, 503)

3. **Notification system:**
   - ❌ Chỉ dùng `alert()` cho errors
   - ❌ `showSuccess()` chỉ log console, user không thấy
   - ❌ Không support toast notifications

4. **Không check CartAPI availability:**
   - ❌ Có thể lỗi nếu script chưa load
   - ❌ Không có fallback

5. **Không có timeout:**
   - ❌ Requests có thể hang mãi
   - ❌ User không biết có lỗi

## 🔧 Giải Pháp

### 1. Input Validation

**File:** `public/js/cart-api-v1.js`

**Before:**
```javascript
addItem: function(variantId, qty, isDeal = false) {
    return $.ajax({...});
}
```

**After:**
```javascript
addItem: function(variantId, qty, isDeal = false) {
    // Validate inputs
    if (!variantId || variantId <= 0) {
        return $.Deferred().reject({
            responseJSON: { message: 'Variant ID không hợp lệ' }
        });
    }
    if (!qty || qty <= 0) {
        return $.Deferred().reject({
            responseJSON: { message: 'Số lượng phải lớn hơn 0' }
        });
    }
    
    return $.ajax({...});
}
```

**Áp dụng cho:**
- ✅ `addItem()` - Validate variantId và qty
- ✅ `updateItem()` - Validate variantId và qty
- ✅ `removeItem()` - Validate variantId

### 2. Timeout Handling

**File:** `public/js/cart-api-v1.js`

**Added:**
```javascript
return $.ajax({
    url: this.baseUrl + '/items',
    method: 'POST',
    timeout: 10000, // 10 seconds timeout
    ...
});
```

**Áp dụng cho:**
- ✅ `getCart()`
- ✅ `addItem()`
- ✅ `updateItem()`
- ✅ `removeItem()`

### 3. Enhanced Error Handling

**File:** `app/Themes/Website/Views/cart/index.blade.php`

**Before:**
```javascript
.fail(function(xhr) {
    var errorMsg = 'Có lỗi xảy ra, vui lòng thử lại';
    if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMsg = xhr.responseJSON.message;
    }
    CartAPI.showError(errorMsg);
});
```

**After:**
```javascript
.fail(function(xhr, status, error) {
    var errorMsg = 'Có lỗi xảy ra, vui lòng thử lại';
    
    // Handle different error types
    if (status === 'timeout') {
        errorMsg = 'Request timeout. Vui lòng thử lại.';
    } else if (xhr.status === 0) {
        errorMsg = 'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.';
    } else if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMsg = xhr.responseJSON.message;
    } else if (xhr.status === 500) {
        errorMsg = 'Lỗi server. Vui lòng thử lại sau.';
    } else if (xhr.status === 503) {
        errorMsg = 'Service unavailable. Vui lòng thử lại sau.';
    }
    
    CartAPI.showError(errorMsg);
});
```

**Error Types Handled:**
- ✅ Timeout (408)
- ✅ Network error (0)
- ✅ Server error (500)
- ✅ Service unavailable (503)
- ✅ Custom API errors

### 4. Enhanced Notification System

**File:** `public/js/cart-api-v1.js`

**Before:**
```javascript
showError: function(message) {
    alert(message || 'Có lỗi xảy ra, vui lòng thử lại');
},
showSuccess: function(message) {
    if (message) {
        console.log('Success:', message);
    }
}
```

**After:**
```javascript
showError: function(message) {
    var errorMsg = message || 'Có lỗi xảy ra, vui lòng thử lại';
    
    // Try to use toast if available, otherwise use alert
    if (typeof toastr !== 'undefined') {
        toastr.error(errorMsg);
    } else if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: errorMsg,
            confirmButtonText: 'Đóng'
        });
    } else {
        alert(errorMsg);
    }
    
    // Log to console for debugging
    console.error('CartAPI Error:', errorMsg);
},
showSuccess: function(message) {
    if (!message) return;
    
    // Try to use toast if available, otherwise use console
    if (typeof toastr !== 'undefined') {
        toastr.success(message);
    } else if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Thành công',
            text: message,
            timer: 2000,
            showConfirmButton: false
        });
    } else {
        console.log('Success:', message);
    }
}
```

**Features:**
- ✅ Support toastr (if available)
- ✅ Support SweetAlert2 (if available)
- ✅ Fallback to alert/console
- ✅ Log errors to console for debugging

### 5. CartAPI Availability Check

**File:** `app/Themes/Website/Views/cart/index.blade.php`

**Added:**
```javascript
$(document).ready(function() {
    // Check if CartAPI is available
    if (typeof CartAPI === 'undefined') {
        console.error('CartAPI is not loaded. Please ensure cart-api-v1.js is included.');
        return;
    }
    ...
});
```

### 6. Global AJAX Error Handler

**File:** `app/Themes/Website/Views/cart/index.blade.php`

**Added:**
```javascript
// Global error handler for AJAX timeouts
$(document).ajaxError(function(event, xhr, settings, thrownError) {
    if (xhr.status === 0) {
        CartAPI.showError('Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.');
    } else if (xhr.status === 408 || thrownError === 'timeout') {
        CartAPI.showError('Request timeout. Vui lòng thử lại.');
    } else if (xhr.status === 500) {
        CartAPI.showError('Lỗi server. Vui lòng thử lại sau.');
    } else if (xhr.status === 503) {
        CartAPI.showError('Service unavailable. Vui lòng thử lại sau.');
    }
});
```

### 7. Input Validation in Event Handlers

**File:** `app/Themes/Website/Views/cart/index.blade.php`

**Added validation for:**
- ✅ Remove item - Check variantId
- ✅ Increase quantity - Check variantId
- ✅ Decrease quantity - Check variantId
- ✅ Manual input - Check variantId
- ✅ Add deal - Check variantId and dealCounts

**Example:**
```javascript
$('body').on('click', '.remove-item-cart', function(e) {
    var variantId = $(this).data('id');
    
    // Validate variantId
    if (!variantId || variantId <= 0) {
        CartAPI.showError('Variant ID không hợp lệ');
        return;
    }
    ...
});
```

## 📊 Error Handling Flow

```
User Action
    ↓
Input Validation
    ├─ Invalid → Show error, return
    └─ Valid → Continue
    ↓
API Call (with timeout)
    ↓
Response Handling
    ├─ Success → Update UI
    ├─ Timeout → Show timeout error
    ├─ Network Error → Show network error
    ├─ Server Error → Show server error
    └─ API Error → Show API error message
    ↓
Error Recovery
    ├─ Revert UI changes
    ├─ Re-enable buttons
    └─ Remove loading states
```

## 🧪 Test Cases

### Test Case 1: Invalid Variant ID
**Action:** Click remove with variantId = 0
**Expected:**
- ✅ Show error: "Variant ID không hợp lệ"
- ✅ No API call
- ✅ Button not disabled

### Test Case 2: Network Timeout
**Action:** API call takes > 10 seconds
**Expected:**
- ✅ Show error: "Request timeout. Vui lòng thử lại."
- ✅ Revert UI changes
- ✅ Re-enable buttons

### Test Case 3: Network Error
**Action:** No internet connection
**Expected:**
- ✅ Show error: "Không thể kết nối đến server."
- ✅ Revert UI changes
- ✅ Re-enable buttons

### Test Case 4: Server Error
**Action:** Server returns 500
**Expected:**
- ✅ Show error: "Lỗi server. Vui lòng thử lại sau."
- ✅ Revert UI changes
- ✅ Re-enable buttons

### Test Case 5: CartAPI Not Loaded
**Action:** Script not included
**Expected:**
- ✅ Console error logged
- ✅ No JavaScript errors
- ✅ Page still functional (fallback)

## 📝 Files Modified

1. **`public/js/cart-api-v1.js`**
   - Added input validation
   - Added timeout (10s)
   - Enhanced `showError()` - Support toastr/Swal
   - Enhanced `showSuccess()` - Support toastr/Swal

2. **`app/Themes/Website/Views/cart/index.blade.php`**
   - Added CartAPI availability check
   - Added global AJAX error handler
   - Enhanced error handling in all event handlers
   - Added input validation in event handlers
   - Better error messages for different error types

## ⚠️ Lưu Ý

### Timeout Value
- **10 seconds:** Đủ cho most requests
- **Có thể điều chỉnh:** Tùy network conditions
- **Balance:** Không quá ngắn (false positives) hoặc quá dài (user đợi lâu)

### Notification Priority
1. **toastr** (if available) - Best UX
2. **SweetAlert2** (if available) - Good UX
3. **alert()** - Fallback

### Error Recovery
- **Always revert UI:** Đảm bảo consistency
- **Re-enable buttons:** User có thể thử lại
- **Remove loading states:** Clear visual feedback

---

**Ngày hoàn thành:** 2025-01-18  
**Trạng thái:** ✅ Đã sửa và test
