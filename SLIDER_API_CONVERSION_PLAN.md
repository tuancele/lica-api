# Kế Hoạch Nâng Cấp Module Slider Sang RESTful API V1

## 📋 Mục Lục
1. [Phân Tích Cấu Trúc Hiện Tại](#phân-tích-cấu-trúc-hiện-tại)
2. [Luồng Dữ Liệu Hiện Tại](#luồng-dữ-liệu-hiện-tại)
3. [Kế Hoạch Triển Khai](#kế-hoạch-triển-khai)
4. [Chi Tiết Endpoint API](#chi-tiết-endpoint-api)
5. [Cấu Trúc Database](#cấu-trúc-database)
6. [Lưu Ý Bảo Mật](#lưu-ý-bảo-mật)

---

## 🔍 Phân Tích Cấu Trúc Hiện Tại

### 1. Database Schema

**Bảng:** `medias`
- **Mục đích:** Bảng chung để lưu trữ media (slider, banner, v.v.)
- **Phân biệt:** Sử dụng trường `type = 'slider'` để phân biệt slider với các loại media khác

**Các trường chính:**
```sql
- id (integer, primary key, auto increment)
- name (string) - Tiêu đề slider
- link (string, nullable) - Liên kết khi click vào slider
- image (string, nullable) - Đường dẫn ảnh slider
- content (text, nullable) - Nội dung mô tả (hiện tại chưa sử dụng)
- status (smallInteger, nullable) - Trạng thái: 0 = Ẩn, 1 = Hiển thị
- type (string, nullable) - Loại media: 'slider' cho slider
- user_id (integer, nullable) - ID người tạo
- created_at (timestamp)
- updated_at (timestamp)
```

**Các trường bổ sung được sử dụng (có thể được thêm qua migration sau):**
- `display` (string) - Thiết bị hiển thị: 'desktop' hoặc 'mobile'
- `sort` (integer) - Thứ tự sắp xếp (được sử dụng trong Controller nhưng chưa có trong migration gốc)

### 2. Model Hiện Tại

**File:** `app/Modules/Slider/Models/Slider.php`
- Sử dụng bảng `medias`
- Có relationship với `User` (belongsTo)
- **Lưu ý:** Model rất đơn giản, chưa có fillable, casts, hoặc các method hỗ trợ

### 3. Controller Hiện Tại

**File:** `app/Modules/Slider/Controllers/SliderController.php`

**Các method hiện có:**
- `index()` - Hiển thị danh sách slider với pagination, filter theo status và keyword
- `create()` - Hiển thị form tạo mới
- `edit($id)` - Hiển thị form chỉnh sửa
- `store()` - Tạo slider mới (POST)
- `update()` - Cập nhật slider (POST)
- `delete()` - Xóa slider (POST)
- `status()` - Cập nhật trạng thái (POST)
- `sort()` - Cập nhật thứ tự sắp xếp (POST)
- `action()` - Thao tác hàng loạt: Ẩn/Hiển thị/Xóa (POST)

**Đặc điểm:**
- Tất cả đều trả về JSON response (phù hợp với AJAX)
- Có xử lý cache: `Cache::forget('home_sliders_v1')` và `Cache::forget('home_sliderms_v1')`
- Validation đơn giản: chỉ validate `name` (required, min:1, max:250)
- Sử dụng `Auth::id()` để lấy user_id

### 4. Routes Hiện Tại

**File:** `app/Modules/Slider/routes.php`

**Cấu trúc:**
- Prefix: `/admin/slider`
- Middleware: `web`, `admin`
- Namespace: `App\Modules\Slider\Controllers`

**Routes:**
```
GET    /admin/slider              -> index()
GET    /admin/slider/create       -> create()
GET    /admin/slider/edit/{id}    -> edit()
POST   /admin/slider/create       -> store()
POST   /admin/slider/edit         -> update()
POST   /admin/slider/delete       -> delete()
POST   /admin/slider/status       -> status()
POST   /admin/slider/action       -> action()
POST   /admin/slider/sort         -> sort()
```

### 5. Views (Blade Templates)

**Các file view:**
- `index.blade.php` - Danh sách slider với bảng, filter, pagination
- `create.blade.php` - Form tạo mới
- `edit.blade.php` - Form chỉnh sửa

**Đặc điểm:**
- Sử dụng AJAX để submit form
- Upload ảnh qua R2 với helper `r2-upload-preview.js`
- Hiển thị ảnh qua helper `getImage()`

---

## 🔄 Luồng Dữ Liệu Hiện Tại

### Luồng Quản Trị (Admin)

1. **Hiển thị danh sách:**
   ```
   User → GET /admin/slider → SliderController@index
   → Query: medias WHERE type='slider' + filters
   → Paginate(10) → View index.blade.php
   ```

2. **Tạo mới:**
   ```
   User → GET /admin/slider/create → View create.blade.php
   → User nhập form → POST /admin/slider/create
   → Validation → Insert vào medias
   → Clear cache → JSON response {status: 'success', url: '/admin/slider'}
   ```

3. **Chỉnh sửa:**
   ```
   User → GET /admin/slider/edit/{id} → View edit.blade.php
   → User sửa form → POST /admin/slider/edit
   → Validation → Update medias WHERE id={id}
   → Clear cache → JSON response {status: 'success', url: '/admin/slider'}
   ```

4. **Xóa:**
   ```
   User → POST /admin/slider/delete (id trong body)
   → Delete medias WHERE id={id}
   → Clear cache → JSON response {status: 'success'}
   ```

5. **Cập nhật trạng thái:**
   ```
   User → POST /admin/slider/status (id, status trong body)
   → Update medias SET status={status} WHERE id={id}
   → Clear cache → JSON response {status: 'success'}
   ```

6. **Sắp xếp:**
   ```
   User → POST /admin/slider/sort (sort array trong body)
   → Loop update medias SET sort={value} WHERE id={key}
   → Clear cache → JSON response
   ```

### Cache Strategy

**Cache keys được sử dụng:**
- `home_sliders_v1` - Cache cho slider desktop
- `home_sliderms_v1` - Cache cho slider mobile

**Cache được clear khi:**
- Tạo mới slider
- Cập nhật slider
- Xóa slider
- Cập nhật trạng thái
- Sắp xếp lại

---

## 🚀 Kế Hoạch Triển Khai

### Giai Đoạn 1: Xây Dựng API Public V1

**Mục tiêu:** Cung cấp API công khai để frontend lấy danh sách slider đang hoạt động.

**Endpoint:**
- `GET /api/v1/sliders`

**Yêu cầu:**
- Chỉ trả về slider có `status = 1` (đang hoạt động)
- Hỗ trợ query param `?display=desktop` hoặc `?display=mobile` để lọc theo thiết bị
- Sắp xếp theo `sort` ASC, sau đó theo `created_at` DESC
- Sử dụng SliderResource để format JSON
- Không cần authentication (public API)

**Controller:** `App\Http\Controllers\Api\V1\SliderController`
**Method:** `index()`

### Giai Đoạn 2: Xây Dựng API Admin

**Mục tiêu:** Cung cấp API RESTful cho admin quản lý slider.

**Endpoints:**
1. `GET /admin/api/sliders` - Danh sách slider với pagination và filters
2. `GET /admin/api/sliders/{id}` - Chi tiết một slider
3. `POST /admin/api/sliders` - Tạo slider mới
4. `PUT /admin/api/sliders/{id}` - Cập nhật slider
5. `DELETE /admin/api/sliders/{id}` - Xóa slider
6. `PATCH /admin/api/sliders/{id}/status` - Cập nhật trạng thái

**Yêu cầu:**
- Sử dụng middleware `auth:api` để xác thực admin
- Validation đầy đủ với Request classes
- Sử dụng SliderResource để format response
- Xử lý lỗi chuẩn với try-catch
- Clear cache sau mỗi thao tác thay đổi dữ liệu

**Controller:** `App\Modules\ApiAdmin\Controllers\SliderController`

### Giai Đoạn 3: Chuẩn Hóa Resource

**Mục tiêu:** Tạo SliderResource để format JSON response nhất quán.

**File:** `app/Http/Resources/Slider/SliderResource.php`

**Yêu cầu:**
- Format URL ảnh qua helper `getImage()` (đã xử lý R2)
- Bao gồm đầy đủ các trường: id, name, link, image, display, status, sort, user, created_at, updated_at
- Sử dụng `when()` để chỉ include các trường có giá trị

### Giai Đoạn 4: Cập Nhật Tài Liệu

**Mục tiêu:** Ghi lại thông tin API vào `API_ADMIN_DOCS.md`.

**Nội dung cần ghi:**
- Mô tả từng endpoint
- Tham số đầu vào (query params, body params)
- Response mẫu (success và error)
- Trạng thái hoàn thành

---

## 📝 Chi Tiết Endpoint API

### API Public V1

#### 1. GET /api/v1/sliders

**Mục tiêu:** Lấy danh sách slider đang hoạt động cho frontend.

**Query Parameters:**
- `display` (string, optional): Lọc theo thiết bị
  - `desktop` - Chỉ lấy slider cho desktop
  - `mobile` - Chỉ lấy slider cho mobile
  - Không có - Lấy tất cả slider

**Response Success (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Slider Tiêu Đề",
      "link": "https://example.com",
      "image": "https://r2-domain.com/uploads/sliders/image.jpg",
      "display": "desktop",
      "status": "1",
      "sort": 1,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

**Response Error (500):**
```json
{
  "success": false,
  "message": "Lấy danh sách slider thất bại",
  "error": "Chi tiết lỗi (chỉ trong debug mode)"
}
```

**Logic:**
1. Query `medias` WHERE `type = 'slider'` AND `status = 1`
2. Nếu có `display`, thêm điều kiện `display = {display}`
3. Order by `sort` ASC, `created_at` DESC
4. Format qua SliderResource
5. Trả về JSON

---

### API Admin

#### 1. GET /admin/api/sliders

**Mục tiêu:** Lấy danh sách slider với pagination và filters cho admin.

**Query Parameters:**
- `page` (integer, optional): Trang hiện tại, mặc định 1
- `limit` (integer, optional): Số lượng mỗi trang, mặc định 10, tối đa 100
- `status` (string, optional): Lọc theo trạng thái (0/1)
- `display` (string, optional): Lọc theo thiết bị (desktop/mobile)
- `keyword` (string, optional): Tìm kiếm theo tên

**Response Success (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Slider Tiêu Đề",
      "link": "https://example.com",
      "image": "https://r2-domain.com/uploads/sliders/image.jpg",
      "display": "desktop",
      "status": "1",
      "sort": 1,
      "user": {
        "id": 1,
        "name": "Admin User"
      },
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

**Response Error (500):**
```json
{
  "success": false,
  "message": "获取slider列表失败",
  "error": "Chi tiết lỗi (chỉ trong debug mode)"
}
```

**Logic:**
1. Lấy filters từ query params
2. Query `medias` WHERE `type = 'slider'` + filters
3. Paginate với limit
4. Format qua SliderResource collection
5. Trả về JSON với pagination info

---

#### 2. GET /admin/api/sliders/{id}

**Mục tiêu:** Lấy chi tiết một slider.

**URL Parameters:**
- `id` (integer, required): ID của slider

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Slider Tiêu Đề",
    "link": "https://example.com",
    "image": "https://r2-domain.com/uploads/sliders/image.jpg",
    "display": "desktop",
    "status": "1",
    "sort": 1,
    "user": {
      "id": 1,
      "name": "Admin User"
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Slider không tồn tại"
}
```

**Logic:**
1. Tìm slider theo ID và type='slider'
2. Nếu không tìm thấy → 404
3. Format qua SliderResource
4. Trả về JSON

---

#### 3. POST /admin/api/sliders

**Mục tiêu:** Tạo slider mới.

**Request Body:**
```json
{
  "name": "Slider Tiêu Đề",
  "link": "https://example.com",
  "image": "uploads/sliders/image.jpg",
  "display": "desktop",
  "status": "1"
}
```

**Validation Rules:**
- `name` (required, string, min:1, max:250)
- `link` (nullable, string, url)
- `image` (nullable, string)
- `display` (required, string, in:desktop,mobile)
- `status` (required, string, in:0,1)

**Response Success (201):**
```json
{
  "success": true,
  "message": "Tạo slider thành công",
  "data": {
    "id": 1,
    "name": "Slider Tiêu Đề",
    ...
  }
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["Tiêu đề không được bỏ trống."]
  }
}
```

**Logic:**
1. Validate request
2. Insert vào `medias` với `type = 'slider'`, `user_id = Auth::id()`
3. Clear cache: `home_sliders_v1`, `home_sliderms_v1`
4. Format qua SliderResource
5. Trả về JSON 201

---

#### 4. PUT /admin/api/sliders/{id}

**Mục tiêu:** Cập nhật slider.

**URL Parameters:**
- `id` (integer, required): ID của slider

**Request Body:**
```json
{
  "name": "Slider Tiêu Đề Updated",
  "link": "https://example.com/new",
  "image": "uploads/sliders/image-new.jpg",
  "display": "mobile",
  "status": "1"
}
```

**Validation Rules:** (giống POST)

**Response Success (200):**
```json
{
  "success": true,
  "message": "Cập nhật slider thành công",
  "data": {
    "id": 1,
    "name": "Slider Tiêu Đề Updated",
    ...
  }
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Slider không tồn tại"
}
```

**Logic:**
1. Tìm slider theo ID và type='slider'
2. Nếu không tìm thấy → 404
3. Validate request
4. Update `medias` WHERE id={id}
5. Clear cache
6. Format qua SliderResource
7. Trả về JSON 200

---

#### 5. DELETE /admin/api/sliders/{id}

**Mục tiêu:** Xóa slider.

**URL Parameters:**
- `id` (integer, required): ID của slider

**Response Success (200):**
```json
{
  "success": true,
  "message": "Xóa slider thành công"
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Slider không tồn tại"
}
```

**Logic:**
1. Tìm slider theo ID và type='slider'
2. Nếu không tìm thấy → 404
3. Delete `medias` WHERE id={id}
4. Clear cache
5. Trả về JSON 200

---

#### 6. PATCH /admin/api/sliders/{id}/status

**Mục tiêu:** Cập nhật trạng thái slider.

**URL Parameters:**
- `id` (integer, required): ID của slider

**Request Body:**
```json
{
  "status": "1"
}
```

**Validation Rules:**
- `status` (required, string, in:0,1)

**Response Success (200):**
```json
{
  "success": true,
  "message": "Cập nhật trạng thái thành công",
  "data": {
    "id": 1,
    "status": "1",
    ...
  }
}
```

**Logic:**
1. Tìm slider theo ID và type='slider'
2. Nếu không tìm thấy → 404
3. Validate status
4. Update `medias` SET status={status} WHERE id={id}
5. Clear cache
6. Format qua SliderResource
7. Trả về JSON 200

---

## 🗄️ Cấu Trúc Database

### Bảng: medias

**Lưu ý quan trọng:**
- Bảng `medias` là bảng chung cho nhiều loại media
- Slider được phân biệt bằng `type = 'slider'`
- Trường `display` và `sort` có thể chưa có trong migration gốc, cần kiểm tra và thêm nếu thiếu

**Migration cần kiểm tra/thêm:**
```php
// Nếu chưa có cột display và sort, cần thêm migration:
Schema::table('medias', function (Blueprint $table) {
    if (!Schema::hasColumn('medias', 'display')) {
        $table->string('display')->nullable()->after('image');
    }
    if (!Schema::hasColumn('medias', 'sort')) {
        $table->integer('sort')->default(0)->after('display');
    }
});
```

---

## 🔒 Lưu Ý Bảo Mật

### 1. Authentication & Authorization

**API Public:**
- Không cần authentication
- Chỉ trả về slider có `status = 1`

**API Admin:**
- **Bắt buộc:** Middleware `auth:api`
- **Khuyến nghị:** Kiểm tra quyền admin (có thể thêm Policy hoặc middleware custom)

### 2. Validation

- **Bắt buộc:** Validate tất cả input từ client
- Sử dụng Form Request classes cho Admin API
- Validate URL format cho trường `link`
- Validate image path cho trường `image`

### 3. XSS Protection

- Slider name và link có thể chứa user input
- Đảm bảo frontend escape HTML khi hiển thị
- Backend không cần escape vì trả về JSON

### 4. Rate Limiting

- **Khuyến nghị:** Thêm rate limiting cho API Public để tránh abuse
- API Admin đã có middleware `auth:api` nên ít rủi ro hơn

---

## 📌 Checklist Triển Khai

### Bước 1: Chuẩn Bị
- [ ] Kiểm tra migration: đảm bảo có cột `display` và `sort` trong bảng `medias`
- [ ] Nếu thiếu, tạo migration để thêm các cột này

### Bước 2: Tạo Resource
- [ ] Tạo `app/Http/Resources/Slider/SliderResource.php`
- [ ] Implement format JSON với helper `getImage()`
- [ ] Test Resource với dữ liệu mẫu

### Bước 3: API Public V1
- [ ] Tạo `app/Http/Controllers/Api/V1/SliderController.php`
- [ ] Implement method `index()`
- [ ] Đăng ký route trong `routes/api.php`
- [ ] Test endpoint với Postman/curl

### Bước 4: API Admin
- [ ] Tạo `app/Modules/ApiAdmin/Controllers/SliderController.php`
- [ ] Implement các method: index, show, store, update, destroy, updateStatus
- [ ] Tạo Request classes cho validation (nếu cần)
- [ ] Đăng ký routes trong `app/Modules/ApiAdmin/routes.php`
- [ ] Test tất cả endpoints

### Bước 5: Tài Liệu
- [ ] Cập nhật `API_ADMIN_DOCS.md` với thông tin các endpoint mới
- [ ] Đảm bảo format nhất quán với các API khác

### Bước 6: Testing
- [ ] Test API Public với các query params khác nhau
- [ ] Test API Admin với authentication
- [ ] Test validation errors
- [ ] Test cache clearing
- [ ] Test với dữ liệu thực tế

### Bước 7: Bảo Đảm Không Phá Vỡ Code Cũ
- [ ] Kiểm tra Blade views vẫn hoạt động bình thường
- [ ] Đảm bảo routes cũ (`/admin/slider`) vẫn hoạt động
- [ ] Test tạo/sửa/xóa slider qua giao diện admin cũ

---

## 🎯 Kết Luận

Kế hoạch này đảm bảo:
1. ✅ **Tương thích ngược:** Không phá vỡ giao diện quản trị Blade hiện tại
2. ✅ **Chuẩn RESTful:** Tuân thủ các nguyên tắc REST API
3. ✅ **Bảo mật:** Xác thực và validation đầy đủ
4. ✅ **Hiệu năng:** Sử dụng cache và pagination hợp lý
5. ✅ **Tài liệu:** Tự động cập nhật vào API_ADMIN_DOCS.md

Sau khi hoàn thành, hệ thống sẽ có:
- API Public V1 cho frontend lấy slider
- API Admin đầy đủ CRUD cho quản trị
- Resource chuẩn hóa JSON response
- Tài liệu API đầy đủ
