# API V1 Brand - Hướng Dẫn Testing

Hướng dẫn test các API endpoints cho Brand V1 đã được triển khai.

---

## Các Endpoints Đã Triển Khai

### 1. GET /api/v1/brands/featured
Lấy danh sách thương hiệu nổi bật cho trang chủ

**Test URL:**
```
GET https://lica.test/api/v1/brands/featured
GET https://lica.test/api/v1/brands/featured?limit=14
GET https://lica.test/api/v1/brands/featured?limit=20
```

**Ví dụ với cURL:**
```bash
curl -X GET "https://lica.test/api/v1/brands/featured" \
  -H "Accept: application/json"
```

**Response mẫu:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "La Roche-Posay",
      "slug": "la-roche-posay",
      "image": "https://cdn.lica.vn/...",
      "banner": "https://cdn.lica.vn/...",
      "logo": "https://cdn.lica.vn/...",
      "content": "Mô tả thương hiệu",
      "gallery": ["https://..."],
      "status": "1",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "count": 14
}
```

**Lưu ý:**
- Endpoint này được tối ưu cho trang chủ
- Sử dụng cache 1 giờ để tối ưu hiệu năng
- Mặc định trả về 14 brands (giống trang chủ hiện tại)
- Sắp xếp theo `sort` field (asc)

---

### 2. GET /api/v1/brands
Lấy danh sách tất cả thương hiệu

**Test URL:**
```
GET https://lica.test/api/v1/brands
GET https://lica.test/api/v1/brands?page=1&limit=20
GET https://lica.test/api/v1/brands?keyword=la
GET https://lica.test/api/v1/brands?status=1
```

**Ví dụ với cURL:**
```bash
curl -X GET "https://lica.test/api/v1/brands" \
  -H "Accept: application/json"
```

**Response mẫu:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Thương hiệu A",
      "slug": "thuong-hieu-a",
      "image": "https://cdn.lica.vn/...",
      "banner": "https://cdn.lica.vn/...",
      "logo": "https://cdn.lica.vn/...",
      "content": "Mô tả thương hiệu",
      "gallery": ["https://..."],
      "status": "1",
      "total_products": 150,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "last_page": 3
  }
}
```

---

### 3. GET /api/v1/brands/{slug}
Lấy chi tiết một thương hiệu theo slug

**Test URL:**
```
GET https://lica.test/api/v1/brands/la-roche-posay
GET https://lica.test/api/v1/brands/vichy
```

**Ví dụ với cURL:**
```bash
curl -X GET "https://lica.test/api/v1/brands/la-roche-posay" \
  -H "Accept: application/json"
```

**Response mẫu:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "La Roche-Posay",
    "slug": "la-roche-posay",
    "image": "https://cdn.lica.vn/...",
    "banner": "https://cdn.lica.vn/...",
    "logo": "https://cdn.lica.vn/...",
    "content": "Mô tả chi tiết...",
    "gallery": ["https://..."],
    "status": "1",
    "total_products": 150,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "Thương hiệu không tồn tại"
}
```

---

### 4. GET /api/v1/brands/{slug}/products
Lấy danh sách sản phẩm của thương hiệu

**Test URL:**
```
GET https://lica.test/api/v1/brands/la-roche-posay/products
GET https://lica.test/api/v1/brands/la-roche-posay/products?page=1&limit=30
GET https://lica.test/api/v1/brands/la-roche-posay/products?stock=1&sort=newest
GET https://lica.test/api/v1/brands/la-roche-posay/products?stock=0&sort=price_asc
GET https://lica.test/api/v1/brands/la-roche-posay/products?sort=name_asc
```

**Query Parameters:**
- `page` (integer): Số trang, mặc định 1
- `limit` (integer): Số lượng mỗi trang, mặc định 30, tối đa 100
- `stock` (string): Lọc theo kho (0=hết hàng, 1=còn hàng, all=tất cả)
- `sort` (string): Sắp xếp (newest, oldest, price_asc, price_desc, name_asc, name_desc)

**Ví dụ với cURL:**
```bash
curl -X GET "https://lica.test/api/v1/brands/la-roche-posay/products?page=1&limit=30&stock=1&sort=newest" \
  -H "Accept: application/json"
```

**Response mẫu:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Sản phẩm A",
      "slug": "san-pham-a",
      "image": "https://cdn.lica.vn/...",
      "gallery": ["https://..."],
      "content": "Nội dung sản phẩm",
      "description": "Mô tả sản phẩm",
      "price_info": {
        "price": 80000,
        "original_price": 100000,
        "type": "sale",
        "label": "Giảm giá"
      },
      "status": "1",
      "stock": "1",
      "brand": {
        "id": 1,
        "name": "La Roche-Posay",
        "slug": "la-roche-posay",
        "image": "https://...",
        "logo": "https://..."
      },
      "variants": [...],
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "brand": {
    "id": 1,
    "name": "La Roche-Posay",
    "slug": "la-roche-posay"
  },
  "pagination": {
    "current_page": 1,
    "per_page": 30,
    "total": 150,
    "last_page": 5
  }
}
```

---

### 5. GET /api/v1/brands/{slug}/products/available
Lấy danh sách sản phẩm còn hàng (shortcut cho `stock=1`)

**Test URL:**
```
GET https://lica.test/api/v1/brands/la-roche-posay/products/available
GET https://lica.test/api/v1/brands/la-roche-posay/products/available?page=1&limit=30&sort=newest
```

**Ví dụ với cURL:**
```bash
curl -X GET "https://lica.test/api/v1/brands/la-roche-posay/products/available" \
  -H "Accept: application/json"
```

---

### 6. GET /api/v1/brands/{slug}/products/out-of-stock
Lấy danh sách sản phẩm hết hàng (shortcut cho `stock=0`)

**Test URL:**
```
GET https://lica.test/api/v1/brands/la-roche-posay/products/out-of-stock
GET https://lica.test/api/v1/brands/la-roche-posay/products/out-of-stock?page=1&limit=30
```

**Ví dụ với cURL:**
```bash
curl -X GET "https://lica.test/api/v1/brands/la-roche-posay/products/out-of-stock" \
  -H "Accept: application/json"
```

---

## Testing với Postman

1. **Import Collection:**
   - Tạo collection mới trong Postman
   - Thêm các requests theo các endpoints trên

2. **Environment Variables:**
   - `base_url`: `https://lica.test`
   - `api_base`: `{{base_url}}/api/v1`

3. **Test Cases:**
   - ✅ Test lấy danh sách brands
   - ✅ Test pagination
   - ✅ Test search keyword
   - ✅ Test filter status
   - ✅ Test lấy chi tiết brand
   - ✅ Test 404 khi brand không tồn tại
   - ✅ Test lấy products của brand
   - ✅ Test filter stock
   - ✅ Test sorting
   - ✅ Test pagination products

---

## Testing với Browser

Mở trực tiếp trong browser (chỉ GET requests):

```
https://lica.test/api/v1/brands
https://lica.test/api/v1/brands/la-roche-posay
https://lica.test/api/v1/brands/la-roche-posay/products
```

---

## Testing với JavaScript (Fetch API)

```javascript
// Lấy danh sách thương hiệu nổi bật (cho trang chủ)
fetch('https://lica.test/api/v1/brands/featured?limit=14')
  .then(response => response.json())
  .then(data => console.log(data));

// Lấy danh sách brands
fetch('https://lica.test/api/v1/brands')
  .then(response => response.json())
  .then(data => console.log(data));

// Lấy chi tiết brand
fetch('https://lica.test/api/v1/brands/la-roche-posay')
  .then(response => response.json())
  .then(data => console.log(data));

// Lấy products của brand
fetch('https://lica.test/api/v1/brands/la-roche-posay/products?page=1&limit=30&stock=1&sort=newest')
  .then(response => response.json())
  .then(data => console.log(data));
```

---

## Kiểm Tra Performance

### Eager Loading
API sử dụng Eager Loading để tối ưu hiệu năng:
- Tất cả relationships được load trong một query
- Tránh N+1 query problem
- Phù hợp cho mobile app

### Pagination
- Mặc định: 20 brands/page, 30 products/page
- Tối đa: 100 items/page
- Response bao gồm thông tin pagination đầy đủ

---

## Lưu Ý

1. **Backward Compatibility:** Route web `/thuong-hieu/{url}` vẫn hoạt động bình thường
2. **Image URLs:** Tự động format qua R2 CDN
3. **Error Handling:** Tất cả errors đều trả về JSON format chuẩn
4. **CORS:** Cần cấu hình CORS nếu gọi từ domain khác

---

## Troubleshooting

### Lỗi 404
- Kiểm tra slug brand có đúng không
- Kiểm tra brand có status = 1 không

### Lỗi 500
- Kiểm tra logs: `storage/logs/laravel.log`
- Kiểm tra database connection
- Kiểm tra helper function `getImage()` có tồn tại không

### Response chậm
- Kiểm tra database indexes trên `brands.slug` và `posts.brand_id`
- Kiểm tra eager loading có hoạt động đúng không

---

**Ngày tạo:** 2025-01-18
**Phiên bản:** V1
