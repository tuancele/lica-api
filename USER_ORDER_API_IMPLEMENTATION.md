# User Order API V1 Implementation

**Ngày tạo:** 2025-01-18  
**Mục tiêu:** Chuyển đổi trang `/account/orders` thành RESTful API V1 cho mobile app

---

## Tổng Quan

Đã tạo API V1 để lấy danh sách đơn hàng và chi tiết đơn hàng của user đã đăng nhập, thay thế cho trang web `/account/orders`.

---

## Endpoints Đã Tạo

### 1. GET /api/v1/orders
**Mục tiêu:** Lấy danh sách đơn hàng của user đã đăng nhập

**Authentication:** Required (`auth:member`)

**Query Parameters:**
- `page` (optional): Trang hiện tại
- `limit` (optional): Số lượng mỗi trang (max 50)
- `status` (optional): Lọc theo trạng thái
- `payment` (optional): Lọc theo trạng thái thanh toán
- `ship` (optional): Lọc theo trạng thái vận chuyển
- `date_from` (optional): Ngày bắt đầu
- `date_to` (optional): Ngày kết thúc

**Response Format:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "code": "1680426297",
      "date": "02-04-2023",
      "date_raw": "2023-04-02T00:00:00.000000Z",
      "address": "Hà Đông, Mỗ Lao",
      "total": 430000,
      "total_formatted": "430,000₫",
      "payment_status": "0",
      "payment_label": "Chưa thanh toán",
      "ship_status": "0",
      "ship_label": "Chưa giao hàng",
      "status": "0",
      "status_label": "Chờ xử lý"
    }
  ],
  "pagination": {...}
}
```

---

### 2. GET /api/v1/orders/{code}
**Mục tiêu:** Lấy chi tiết đơn hàng theo mã đơn hàng

**Authentication:** Required (`auth:member`)

**URL Parameters:**
- `code` (required): Mã đơn hàng

**Response Format:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "code": "1680426297",
    "name": "Nguyễn Văn A",
    "phone": "0123456789",
    "email": "email@example.com",
    "address": "123 Đường ABC",
    "province": {...},
    "district": {...},
    "ward": {...},
    "remark": "Ghi chú",
    "total": 430000,
    "sale": 0,
    "fee_ship": 30000,
    "final_total": 460000,
    "status": "0",
    "status_label": "Chờ xử lý",
    "payment": "0",
    "payment_label": "Chưa thanh toán",
    "ship": "0",
    "ship_label": "Chưa giao hàng",
    "items": [...]
  }
}
```

---

## Files Đã Tạo/Cập Nhật

### 1. Controller
- `app/Http/Controllers/Api/V1/OrderController.php` - Mới tạo
  - `index()` - Lấy danh sách đơn hàng
  - `show($code)` - Lấy chi tiết đơn hàng

### 2. Resource Classes
- `app/Http/Resources/Order/UserOrderResource.php` - Mới tạo
  - Format đơn giản cho danh sách đơn hàng của user
  - Bao gồm: code, date, address, total, payment_status, ship_status

### 3. Routes
- `routes/api.php` - Đã cập nhật
  - Thêm routes cho `/api/v1/orders`
  - Middleware: `web`, `auth:member`

### 4. Documentation
- `API_ADMIN_DOCS.md` - Đã cập nhật
  - Thêm section "User Order API V1"

---

## Tính Năng

### Bảo Mật
- ✅ Yêu cầu authentication (`auth:member`)
- ✅ Chỉ trả về đơn hàng của user hiện tại
- ✅ Kiểm tra quyền truy cập khi xem chi tiết đơn hàng

### Tương Thích
- ✅ Hỗ trợ cả `member_id` và `user_id` để tương thích với database hiện tại
- ✅ Sử dụng middleware `web` để hỗ trợ session (giống Cart API)

### Format Dữ Liệu
- ✅ UserOrderResource: Format đơn giản cho danh sách
- ✅ OrderDetailResource: Format đầy đủ cho chi tiết
- ✅ Tự động format ngày tháng và giá tiền

---

## So Sánh Với Trang Web

### Trang Web (`/account/orders`)
- Hiển thị: Mã đơn hàng, Ngày, Địa chỉ, Giá trị, TT thanh toán, TT vận chuyển
- Link đến chi tiết: `/account/order/{code}`

### API V1 (`/api/v1/orders`)
- Trả về: Tất cả thông tin trên + thêm các trường hỗ trợ mobile app
- Chi tiết: `/api/v1/orders/{code}`
- Format: JSON chuẩn với pagination

---

## Test

### Test với Authentication
```bash
# Lấy danh sách đơn hàng
curl -X GET "http://lica.test/api/v1/orders" \
  -H "Cookie: laravel_session=YOUR_SESSION" \
  -H "Accept: application/json"

# Lấy chi tiết đơn hàng
curl -X GET "http://lica.test/api/v1/orders/1680426297" \
  -H "Cookie: laravel_session=YOUR_SESSION" \
  -H "Accept: application/json"
```

### Test với Postman
1. Đăng nhập vào website để lấy session cookie
2. Sử dụng cookie trong Postman request
3. Test các endpoints

---

## Lưu Ý

1. **Authentication:** API yêu cầu user đã đăng nhập (guard: member)
2. **Session:** Sử dụng middleware `web` để hỗ trợ session-based authentication
3. **Member ID:** Hỗ trợ cả `member_id` và `user_id` để tương thích
4. **Pagination:** Mặc định 10 items/trang, tối đa 50 items/trang

---

**Trạng thái:** ✅ Hoàn thành
