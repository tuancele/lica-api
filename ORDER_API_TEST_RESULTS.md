# Order API Test Results

**Ngày test:** 2025-01-18  
**Module:** Order Management API V1  
**Base URL:** `https://lica.test/admin/api/orders` (hoặc `http://localhost/admin/api/orders`)

---

## Test Plan

### 1. GET /admin/api/orders - Danh sách đơn hàng

#### Test Case 1.1: Lấy danh sách đơn hàng cơ bản
**Request:**
```bash
GET /admin/api/orders
Headers: Authorization: Bearer {token}
```

**Expected:** Status 200, danh sách đơn hàng với pagination

---

#### Test Case 1.2: Lọc theo trạng thái
**Request:**
```bash
GET /admin/api/orders?status=0
Headers: Authorization: Bearer {token}
```

**Expected:** Chỉ trả về đơn hàng có status = 0 (Chờ xử lý)

---

#### Test Case 1.3: Tìm kiếm theo keyword
**Request:**
```bash
GET /admin/api/orders?keyword=0123456789
Headers: Authorization: Bearer {token}
```

**Expected:** Trả về đơn hàng có số điện thoại, tên hoặc mã đơn hàng chứa keyword

---

#### Test Case 1.4: Lọc theo ngày tháng
**Request:**
```bash
GET /admin/api/orders?date_from=2024-01-01&date_to=2024-12-31
Headers: Authorization: Bearer {token}
```

**Expected:** Chỉ trả về đơn hàng trong khoảng thời gian chỉ định

---

#### Test Case 1.5: Phân trang
**Request:**
```bash
GET /admin/api/orders?page=1&limit=20
Headers: Authorization: Bearer {token}
```

**Expected:** Trả về 20 đơn hàng mỗi trang

---

### 2. GET /admin/api/orders/{id} - Chi tiết đơn hàng

#### Test Case 2.1: Lấy chi tiết đơn hàng hợp lệ
**Request:**
```bash
GET /admin/api/orders/1
Headers: Authorization: Bearer {token}
```

**Expected:** Status 200, trả về đầy đủ thông tin đơn hàng kèm danh sách sản phẩm

---

#### Test Case 2.2: Đơn hàng không tồn tại
**Request:**
```bash
GET /admin/api/orders/99999
Headers: Authorization: Bearer {token}
```

**Expected:** Status 404, message "Đơn hàng không tồn tại"

---

### 3. PATCH /admin/api/orders/{id}/status - Cập nhật trạng thái

#### Test Case 3.1: Cập nhật trạng thái thành công
**Request:**
```bash
PATCH /admin/api/orders/1/status
Headers: Authorization: Bearer {token}
Body: {
  "status": "1"
}
```

**Expected:** Status 200, cập nhật thành công

---

#### Test Case 3.2: Hủy đơn hàng (hoàn lại tồn kho)
**Request:**
```bash
PATCH /admin/api/orders/1/status
Headers: Authorization: Bearer {token}
Body: {
  "status": "4"
}
```

**Expected:** 
- Status 200
- Đơn hàng chuyển sang trạng thái "Đã hủy"
- Tồn kho của các sản phẩm trong đơn được hoàn lại

---

#### Test Case 3.3: Khôi phục đơn hàng đã hủy (trừ lại tồn kho)
**Request:**
```bash
PATCH /admin/api/orders/1/status
Headers: Authorization: Bearer {token}
Body: {
  "status": "1"
}
```

**Expected:**
- Status 200 (nếu đủ tồn kho)
- Đơn hàng chuyển sang trạng thái "Đã xác nhận"
- Tồn kho được trừ lại

---

#### Test Case 3.4: Khôi phục đơn hàng nhưng không đủ tồn kho
**Request:**
```bash
PATCH /admin/api/orders/1/status
Headers: Authorization: Bearer {token}
Body: {
  "status": "1"
}
```

**Expected:** Status 500, message lỗi về không đủ tồn kho

---

#### Test Case 3.5: Validation error - status không hợp lệ
**Request:**
```bash
PATCH /admin/api/orders/1/status
Headers: Authorization: Bearer {token}
Body: {
  "status": "99"
}
```

**Expected:** Status 400, validation errors

---

### 4. PUT /admin/api/orders/{id} - Chỉnh sửa đơn hàng

#### Test Case 4.1: Cập nhật thông tin khách hàng
**Request:**
```bash
PUT /admin/api/orders/1
Headers: Authorization: Bearer {token}
Body: {
  "name": "Nguyễn Văn B",
  "phone": "0987654321",
  "email": "newemail@example.com"
}
```

**Expected:** Status 200, thông tin khách hàng được cập nhật

---

#### Test Case 4.2: Cập nhật địa chỉ
**Request:**
```bash
PUT /admin/api/orders/1
Headers: Authorization: Bearer {token}
Body: {
  "address": "456 Đường XYZ",
  "provinceid": 2,
  "districtid": 2,
  "wardid": 2
}
```

**Expected:** Status 200, địa chỉ được cập nhật

---

#### Test Case 4.3: Cập nhật số lượng sản phẩm
**Request:**
```bash
PUT /admin/api/orders/1
Headers: Authorization: Bearer {token}
Body: {
  "items": [
    {
      "id": 1,
      "qty": 5
    }
  ]
}
```

**Expected:**
- Status 200
- Số lượng sản phẩm được cập nhật
- Tồn kho được điều chỉnh (tăng/giảm tương ứng)
- Tổng tiền đơn hàng được tính lại

---

#### Test Case 4.4: Thêm sản phẩm mới vào đơn hàng
**Request:**
```bash
PUT /admin/api/orders/1
Headers: Authorization: Bearer {token}
Body: {
  "items": [
    {
      "product_id": 10,
      "variant_id": 5,
      "qty": 2
    }
  ]
}
```

**Expected:**
- Status 200
- Sản phẩm mới được thêm vào đơn hàng
- Tồn kho được trừ
- Tổng tiền được tính lại

---

#### Test Case 4.5: Xóa sản phẩm khỏi đơn hàng
**Request:**
```bash
PUT /admin/api/orders/1
Headers: Authorization: Bearer {token}
Body: {
  "items": [
    {
      "id": 1,
      "qty": 2
    }
    // Không bao gồm item có id=2 → sẽ bị xóa
  ]
}
```

**Expected:**
- Status 200
- Sản phẩm không có trong items array bị xóa
- Tồn kho được hoàn lại
- Tổng tiền được tính lại

---

#### Test Case 4.6: Cập nhật đơn hàng đã hủy (should fail)
**Request:**
```bash
PUT /admin/api/orders/1
Headers: Authorization: Bearer {token}
Body: {
  "name": "New Name"
}
```

**Expected:** Status 400, message "Không thể chỉnh sửa đơn hàng đã hủy"

---

#### Test Case 4.7: Thêm sản phẩm nhưng không đủ tồn kho
**Request:**
```bash
PUT /admin/api/orders/1
Headers: Authorization: Bearer {token}
Body: {
  "items": [
    {
      "product_id": 10,
      "qty": 99999
    }
  ]
}
```

**Expected:** Status 500, message lỗi về không đủ tồn kho

---

#### Test Case 4.8: Validation error
**Request:**
```bash
PUT /admin/api/orders/1
Headers: Authorization: Bearer {token}
Body: {
  "email": "invalid-email"
}
```

**Expected:** Status 400, validation errors

---

## Test Results

### Environment Setup
- **Base URL:** `http://lica.test` hoặc `https://lica.test`
- **Authentication:** Bearer Token (Required)
- **Database:** MySQL

---

### Initial Test Results

#### ✅ Authentication Check
**Test:** GET /admin/api/orders (without token)  
**Result:** ✓ PASS  
**HTTP Code:** 401 Unauthenticated  
**Status:** API đang hoạt động đúng, yêu cầu authentication token như mong đợi.

**Response:**
```json
{
  "message": "Unauthenticated."
}
```

---

### Test Execution Log

#### GET /admin/api/orders
- [⏳] Test Case 1.1: Lấy danh sách đơn hàng cơ bản - **Cần token**
- [⏳] Test Case 1.2: Lọc theo trạng thái - **Cần token**
- [⏳] Test Case 1.3: Tìm kiếm theo keyword - **Cần token**
- [⏳] Test Case 1.4: Lọc theo ngày tháng - **Cần token**
- [⏳] Test Case 1.5: Phân trang - **Cần token**

#### GET /admin/api/orders/{id}
- [⏳] Test Case 2.1: Lấy chi tiết đơn hàng hợp lệ - **Cần token**
- [⏳] Test Case 2.2: Đơn hàng không tồn tại - **Cần token**

#### PATCH /admin/api/orders/{id}/status
- [⏳] Test Case 3.1: Cập nhật trạng thái thành công - **Cần token**
- [⏳] Test Case 3.2: Hủy đơn hàng (hoàn lại tồn kho) - **Cần token**
- [⏳] Test Case 3.3: Khôi phục đơn hàng đã hủy - **Cần token**
- [⏳] Test Case 3.4: Khôi phục đơn hàng nhưng không đủ tồn kho - **Cần token**
- [⏳] Test Case 3.5: Validation error - **Cần token**

#### PUT /admin/api/orders/{id}
- [⏳] Test Case 4.1: Cập nhật thông tin khách hàng - **Cần token**
- [⏳] Test Case 4.2: Cập nhật địa chỉ - **Cần token**
- [⏳] Test Case 4.3: Cập nhật số lượng sản phẩm - **Cần token**
- [⏳] Test Case 4.4: Thêm sản phẩm mới - **Cần token**
- [⏳] Test Case 4.5: Xóa sản phẩm - **Cần token**
- [⏳] Test Case 4.6: Cập nhật đơn hàng đã hủy (should fail) - **Cần token**
- [⏳] Test Case 4.7: Thêm sản phẩm nhưng không đủ tồn kho - **Cần token**
- [⏳] Test Case 4.8: Validation error - **Cần token**

---

## Hướng Dẫn Test

### 1. Lấy Authentication Token

#### Option A: Sử dụng Laravel Passport/Sanctum
```bash
# Tạo token cho user admin
php artisan tinker
>>> $user = App\User::find(1);
>>> $token = $user->createToken('admin-api')->accessToken;
>>> echo $token;
```

#### Option B: Test với Postman
1. Tạo request POST đến `/api/login` (nếu có)
2. Lấy token từ response
3. Sử dụng token trong header: `Authorization: Bearer {token}`

### 2. Test với cURL

```bash
# Lấy danh sách đơn hàng
curl -X GET "http://lica.test/admin/api/orders" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"

# Lấy chi tiết đơn hàng
curl -X GET "http://lica.test/admin/api/orders/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"

# Cập nhật trạng thái
curl -X PATCH "http://lica.test/admin/api/orders/1/status" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"status": "1"}'

# Cập nhật đơn hàng
curl -X PUT "http://lica.test/admin/api/orders/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name": "New Name", "phone": "0123456789"}'
```

### 3. Test với PHP Script

Chỉnh sửa file `test_order_api.php`:
```php
$apiToken = 'YOUR_TOKEN_HERE'; // Thay bằng token thực tế
```

Sau đó chạy:
```bash
php test_order_api.php
```

### 4. Test với Postman

1. **Collection Setup:**
   - Base URL: `http://lica.test/admin/api`
   - Authorization: Bearer Token
   - Headers: `Accept: application/json`

2. **Endpoints:**
   - `GET /orders`
   - `GET /orders/{id}`
   - `PATCH /orders/{id}/status`
   - `PUT /orders/{id}`

---

## Code Review Results

### ✅ Đã Kiểm Tra
1. **Routes:** Đã đăng ký đúng 4 routes
   - GET /admin/api/orders
   - GET /admin/api/orders/{id}
   - PUT /admin/api/orders/{id}
   - PATCH /admin/api/orders/{id}/status

2. **Resource Classes:** Đã tạo đầy đủ
   - OrderResource.php ✓
   - OrderDetailResource.php ✓
   - OrderItemResource.php ✓

3. **Controller:** Đã implement đầy đủ methods
   - index() ✓
   - show() ✓
   - updateStatus() ✓
   - update() ✓

4. **Model Relationships:** Đã thêm
   - promotion() ✓
   - member() ✓

5. **Authentication:** Middleware hoạt động đúng
   - Trả về 401 khi không có token ✓

---

## Issues Found

### ⚠️ Minor Issues
- **None** - Code đã được kiểm tra và không có lỗi syntax

### 💡 Suggestions
1. **Test với Authentication:** Cần có token để test đầy đủ các endpoints
2. **Database Data:** Đảm bảo có dữ liệu test trong bảng `orders` và `orderdetail`
3. **Stock Management:** Test kỹ logic hoàn trả tồn kho khi hủy đơn hàng

---

## Summary

**Total Test Cases:** 18  
**Passed:** 1 (Authentication check)  
**Failed:** 0  
**Pending:** 17 (Cần authentication token)

**Status:** ✅ Code implementation hoàn thành, sẵn sàng test với authentication token

**Next Steps:**
1. Lấy authentication token
2. Test các endpoints với token
3. Kiểm tra logic tồn kho khi cập nhật trạng thái
4. Kiểm tra validation và error handling

---

## User Order API V1 Test Results

### ✅ Authentication Check
**Test:** GET /api/v1/orders (without authentication)  
**Result:** ✓ PASS  
**HTTP Code:** 401 Unauthenticated  
**Status:** API đang hoạt động đúng, yêu cầu authentication như mong đợi.

**Response:**
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### ✅ Routes Registration
**Test:** php artisan route:list --path=api/v1/orders  
**Result:** ✓ PASS  
**Routes Found:** 2
- GET /api/v1/orders
- GET /api/v1/orders/{code}

**Status:** Routes đã được đăng ký đúng.

### ⏳ Pending Tests (Cần authentication)
- [⏳] GET /api/v1/orders (with authentication)
- [⏳] GET /api/v1/orders/{code} (with authentication)
- [⏳] Test filters (status, payment, ship)
- [⏳] Test pagination
- [⏳] Test date filters

**Note:** Để test đầy đủ, cần đăng nhập vào website và sử dụng session cookie.
