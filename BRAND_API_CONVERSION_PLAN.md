# Phân Tích & Kế Hoạch Chuyển Đổi Trang Thương Hiệu Sang API/V1 RESTful

## 1. DANH SÁCH CÁC MODULE HIỆN CÓ

Dựa trên quét codebase, hệ thống hiện có **47 modules** trong `app/Modules/`:

### Modules Quản Lý Nội Dung
- **Address** - Quản lý địa chỉ
- **Banner** - Quản lý banner
- **Brand** - Quản lý thương hiệu ⭐ (Module cần chuyển đổi)
- **Category** - Quản lý danh mục
- **Color** - Quản lý màu sắc
- **Config** - Cấu hình hệ thống
- **Contact** - Quản lý liên hệ
- **Dictionary** - Từ điển thành phần
- **FooterBlock** - Khối footer
- **Ingredient** - Thành phần sản phẩm
- **Menu** - Quản lý menu
- **Origin** - Xuất xứ sản phẩm
- **Page** - Quản lý trang
- **Post** - Quản lý bài viết
- **Product** - Quản lý sản phẩm
- **Size** - Quản lý kích thước
- **Slider** - Quản lý slider
- **Tag** - Quản lý tag
- **Taxonomy** - Phân loại
- **Video** - Quản lý video

### Modules Thương Mại Điện Tử
- **Cart** - Giỏ hàng (trong Website Theme)
- **Compare** - So sánh sản phẩm
- **Deal** - Khuyến mãi/Deal
- **Delivery** - Vận chuyển
- **FlashSale** - Flash sale
- **Marketing** - Marketing campaigns
- **Order** - Đơn hàng
- **Pick** - Điểm lấy hàng
- **Promotion** - Khuyến mãi
- **Rate** - Đánh giá sản phẩm
- **Selling** - Bán hàng
- **Warehouse** - Kho hàng

### Modules Người Dùng & Phân Quyền
- **Member** - Thành viên
- **User** - Người dùng
- **Role** - Vai trò
- **Permission** - Quyền
- **Right** - Quyền truy cập
- **Login** - Đăng nhập

### Modules Hệ Thống
- **ApiAdmin** - API quản trị ⭐ (Module API Admin)
- **Dashboard** - Bảng điều khiển
- **Download** - Tải xuống
- **Feedback** - Phản hồi
- **GroupShowroom** - Nhóm showroom
- **History** - Lịch sử
- **Layout** - Layout
- **Location** - Vị trí
- **Redirection** - Chuyển hướng
- **Recommendation** - Hệ thống gợi ý
- **Search** - Tìm kiếm
- **Setting** - Cài đặt
- **Showroom** - Showroom
- **Subcriber** - Đăng ký nhận tin
- **Website** - Theme Website

---

## 2. PHÂN TÍCH TRANG `/thuong-hieu` HIỆN TẠI

### 2.1. Route Hiện Tại
```php
// File: app/Themes/Website/routes.php (dòng 26)
Route::get('thuong-hieu/{url}', 'BrandController@index')->name('home.brand');
```

### 2.2. Controller Logic
**File:** `app/Themes/Website/Controllers/BrandController.php`

**Chức năng hiện tại:**
- Nhận tham số `$url` (slug của thương hiệu)
- Tìm thương hiệu theo `slug` và `status = 1`
- Lấy thông tin chi tiết thương hiệu:
  - `detail`: Thông tin brand (name, slug, image, banner, logo, content, gallery)
  - `galleries`: Mảng ảnh gallery (từ JSON)
  - `total`: Tổng số sản phẩm thuộc thương hiệu
  - `products`: Danh sách sản phẩm còn hàng (paginate 30)
  - `stocks`: Danh sách sản phẩm hết hàng
- Trả về view `Website::brand.index` hoặc 404

### 2.3. Dữ Liệu Hiển Thị
Từ view `app/Themes/Website/Views/brand/index.blade.php`:

1. **Thông tin Brand:**
   - Banner (ảnh banner)
   - Logo (ảnh logo)
   - Tên thương hiệu
   - Số lượng sản phẩm
   - Nội dung mô tả (content)
   - Gallery (mảng ảnh)

2. **Danh sách Sản phẩm (còn hàng):**
   - Hình ảnh, tên, giá
   - Thương hiệu con
   - Đánh giá (rating)
   - Số lượng đã bán
   - Wishlist

3. **Danh sách Sản phẩm (hết hàng):**
   - Tương tự như trên nhưng hiển thị "Hết hàng"

### 2.4. Database Schema
**Bảng `brands`:**
- `id` - ID thương hiệu
- `name` - Tên thương hiệu
- `slug` - URL slug
- `image` - Ảnh đại diện
- `banner` - Ảnh banner
- `logo` - Logo
- `content` - Nội dung mô tả
- `gallery` - JSON mảng ảnh
- `status` - Trạng thái (0/1)
- `user_id` - ID người tạo
- `created_at`, `updated_at`

**Quan hệ:**
- `Brand` hasMany `Product` (qua `brand_id`)

---

## 3. KẾ HOẠCH CHUYỂN ĐỔI SANG API/V1 RESTful

### 3.1. Cấu Trúc API Đề Xuất

#### **Endpoint Base:** `/api/v1/brands`

#### **Các Endpoint:**

1. **GET `/api/v1/brands`**
   - **Mục tiêu:** Lấy danh sách tất cả thương hiệu
   - **Query Params:**
     - `page` (integer, optional): Số trang, mặc định 1
     - `limit` (integer, optional): Số lượng mỗi trang, mặc định 20
     - `status` (string, optional): Lọc theo trạng thái (0/1)
     - `keyword` (string, optional): Tìm kiếm theo tên
   - **Response:** Danh sách thương hiệu với pagination

2. **GET `/api/v1/brands/{slug}`**
   - **Mục tiêu:** Lấy thông tin chi tiết một thương hiệu theo slug
   - **Response:** Thông tin brand + tổng số sản phẩm

3. **GET `/api/v1/brands/{slug}/products`**
   - **Mục tiêu:** Lấy danh sách sản phẩm của thương hiệu
   - **Query Params:**
     - `page` (integer, optional): Số trang
     - `limit` (integer, optional): Số lượng mỗi trang, mặc định 30
     - `stock` (string, optional): Lọc theo tình trạng hàng (0=hết hàng, 1=còn hàng, all=tất cả)
     - `sort` (string, optional): Sắp xếp (newest, oldest, price_asc, price_desc, name_asc, name_desc)
   - **Response:** Danh sách sản phẩm với pagination

4. **GET `/api/v1/brands/{slug}/products/available`**
   - **Mục tiêu:** Lấy danh sách sản phẩm còn hàng
   - **Tương tự endpoint trên nhưng chỉ trả về sản phẩm còn hàng**

5. **GET `/api/v1/brands/{slug}/products/out-of-stock`**
   - **Mục tiêu:** Lấy danh sách sản phẩm hết hàng
   - **Tương tự endpoint trên nhưng chỉ trả về sản phẩm hết hàng**

### 3.2. Cấu Trúc File Đề Xuất

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           └── V1/
│               └── BrandController.php  (Mới tạo)
├── Http/
│   └── Resources/
│       └── Brand/
│           ├── BrandResource.php  (Mới tạo)
│           └── BrandCollection.php  (Mới tạo - optional)
└── Modules/
    └── ApiAdmin/  (Có thể mở rộng hoặc tạo module mới)
```

### 3.3. Resource Structure

**BrandResource:**
```php
{
  "id": 1,
  "name": "Tên thương hiệu",
  "slug": "ten-thuong-hieu",
  "image": "https://...",
  "banner": "https://...",
  "logo": "https://...",
  "content": "Nội dung mô tả",
  "gallery": ["url1", "url2"],
  "status": "1",
  "total_products": 150,
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

**Product trong Brand:**
- Sử dụng `ProductResource` đã có sẵn
- Bao gồm: id, name, slug, image, price_info, brand, rating, sales_count, stock

### 3.4. Validation Rules

**GET `/api/v1/brands/{slug}`:**
- `slug`: required, string, exists trong bảng brands

**GET `/api/v1/brands/{slug}/products`:**
- `slug`: required, string
- `page`: optional, integer, min:1
- `limit`: optional, integer, min:1, max:100
- `stock`: optional, in:0,1,all
- `sort`: optional, in:newest,oldest,price_asc,price_desc,name_asc,name_desc

### 3.5. Response Format Chuẩn

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    // BrandResource hoặc ProductResource
  },
  "pagination": {  // Nếu có pagination
    "current_page": 1,
    "per_page": 30,
    "total": 150,
    "last_page": 5
  }
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "Thương hiệu không tồn tại",
  "errors": {}
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ",
  "errors": {
    "slug": ["Slug không hợp lệ"]
  }
}
```

### 3.6. Middleware & Authentication

- **Public API:** Không yêu cầu authentication (giống như trang web hiện tại)
- **Middleware:** `api` (JSON response)
- **CORS:** Cấu hình nếu cần thiết

### 3.7. Tích Hợp Với Hệ Thống Hiện Tại

1. **Tái sử dụng:**
   - Model `App\Modules\Brand\Models\Brand`
   - Model `App\Modules\Product\Models\Product`
   - Resource `ProductResource` (đã có)
   - Helper functions: `getImage()`, `getSlug()`

2. **Logic nghiệp vụ:**
   - Giữ nguyên logic lọc sản phẩm theo `brand_id`
   - Giữ nguyên logic phân biệt sản phẩm còn hàng/hết hàng
   - Tái sử dụng logic tính giá (FlashSale, Marketing Campaign)

3. **Tối ưu hóa:**
   - Sử dụng Eloquent eager loading (`with()`) để tránh N+1 query
   - Cache kết quả nếu cần (danh sách brand, tổng số sản phẩm)
   - Sử dụng pagination cho danh sách sản phẩm

---

## 4. IMPLEMENTATION CHECKLIST

### Phase 1: Tạo Resource Classes
- [ ] Tạo `BrandResource.php`
- [ ] Tạo `BrandCollection.php` (optional)

### Phase 2: Tạo Controller
- [ ] Tạo `Api/V1/BrandController.php`
- [ ] Implement `index()` - Lấy danh sách brands
- [ ] Implement `show($slug)` - Lấy chi tiết brand
- [ ] Implement `getProducts($slug)` - Lấy sản phẩm của brand
- [ ] Implement `getAvailableProducts($slug)` - Sản phẩm còn hàng
- [ ] Implement `getOutOfStockProducts($slug)` - Sản phẩm hết hàng

### Phase 3: Đăng Ký Routes
- [ ] Thêm routes vào `routes/api.php`
- [ ] Nhóm routes với prefix `api/v1/brands`

### Phase 4: Validation & Error Handling
- [ ] Tạo FormRequest classes (optional)
- [ ] Xử lý 404 khi brand không tồn tại
- [ ] Xử lý validation errors

### Phase 5: Testing
- [ ] Test GET `/api/v1/brands`
- [ ] Test GET `/api/v1/brands/{slug}`
- [ ] Test GET `/api/v1/brands/{slug}/products`
- [ ] Test pagination
- [ ] Test filters (stock, sort)
- [ ] Test error cases (404, validation)

### Phase 6: Documentation
- [ ] Cập nhật `API_ADMIN_DOCS.md` hoặc tạo `API_V1_DOCS.md`
- [ ] Ghi lại tất cả endpoints, params, responses

---

## 5. LƯU Ý QUAN TRỌNG

1. **Backward Compatibility:**
   - Giữ nguyên route web hiện tại (`/thuong-hieu/{url}`)
   - API mới không ảnh hưởng đến frontend hiện tại

2. **Performance:**
   - Sử dụng eager loading cho relationships
   - Cache tổng số sản phẩm nếu cần
   - Index database cho `brands.slug` và `posts.brand_id`

3. **Security:**
   - Validate input đầy đủ
   - Chỉ trả về dữ liệu public (status=1)
   - Không expose thông tin nhạy cảm

4. **Code Standards:**
   - Type hinting cho PHP 8.2+
   - Sử dụng Eloquent Resources
   - Error handling với try-catch
   - Code comments bằng tiếng Anh

---

## 6. SO SÁNH TRƯỚC VÀ SAU

### Trước (Web Route):
```
GET /thuong-hieu/{url}
→ Trả về HTML view
→ Logic trong BrandController@index
→ Không có pagination cho sản phẩm hết hàng
```

### Sau (API RESTful):
```
GET /api/v1/brands
→ Trả về JSON danh sách brands

GET /api/v1/brands/{slug}
→ Trả về JSON chi tiết brand

GET /api/v1/brands/{slug}/products?page=1&limit=30&stock=1&sort=newest
→ Trả về JSON danh sách sản phẩm với pagination và filters
```

**Lợi ích:**
- ✅ Tách biệt frontend/backend
- ✅ Hỗ trợ mobile app, SPA
- ✅ Dễ dàng mở rộng và tích hợp
- ✅ Chuẩn RESTful, dễ maintain
- ✅ Hỗ trợ pagination, filtering, sorting linh hoạt

---

**Ngày tạo:** {{ date }}
**Trạng thái:** Kế hoạch - Chưa triển khai
