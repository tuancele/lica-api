# Hướng Dẫn Test Product Detail API V1

## Endpoint Mới

**URL:** `GET /api/v1/products/{slug}`

**Ví dụ:** `GET /api/v1/products/nuoc-hoa-vung-kin-foellie-bijou-chinh-hang-100`

---

## Cách 1: Test Bằng Script PHP

### Bước 1: Khởi động Laravel Server

```bash
php artisan serve
```

Server sẽ chạy tại: `http://localhost:8000`

### Bước 2: Chạy Script Test

```bash
php test_product_detail_api_v1_simple.php
```

**Lưu ý:** 
- Mở file `test_product_detail_api_v1_simple.php`
- Cập nhật `$testSlug` với slug sản phẩm thực tế từ database của bạn
- Nếu server chạy trên port khác, cập nhật `$baseUrl`

---

## Cách 2: Test Bằng cURL

### Test với sản phẩm hợp lệ:

```bash
curl -X GET "http://localhost:8000/api/v1/products/nuoc-hoa-vung-kin-foellie-bijou-chinh-hang-100" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json"
```

### Test với sản phẩm không tồn tại (404):

```bash
curl -X GET "http://localhost:8000/api/v1/products/invalid-slug" \
  -H "Accept: application/json"
```

### Format đẹp hơn với jq (nếu có):

```bash
curl -X GET "http://localhost:8000/api/v1/products/nuoc-hoa-vung-kin-foellie-bijou-chinh-hang-100" \
  -H "Accept: application/json" | jq .
```

---

## Cách 3: Test Bằng Postman

1. **Method:** `GET`
2. **URL:** `http://localhost:8000/api/v1/products/{slug}`
   - Thay `{slug}` bằng slug sản phẩm thực tế
3. **Headers:**
   - `Accept: application/json`
   - `Content-Type: application/json`
4. **Send Request**

---

## Cách 4: Test Bằng Browser

Mở trình duyệt và truy cập:

```
http://localhost:8000/api/v1/products/nuoc-hoa-vung-kin-foellie-bijou-chinh-hang-100
```

**Lưu ý:** Cần cài extension JSON Viewer để xem JSON đẹp hơn.

---

## Response Mẫu (200 OK)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Nước hoa Vùng Kín Foellie Bijou Chính Hãng 100ml",
    "slug": "nuoc-hoa-vung-kin-foellie-bijou-chinh-hang-100",
    "image": "https://cdn.lica.vn/uploads/images/product.jpg",
    "video": null,
    "gallery": [
      "https://cdn.lica.vn/uploads/images/gallery1.jpg",
      "https://cdn.lica.vn/uploads/images/gallery2.jpg"
    ],
    "description": "Mô tả ngắn",
    "content": "Nội dung chi tiết HTML",
    "ingredient": {
      "raw": "Water, Glycerin, Fragrance...",
      "html": "<p>Water, <a href='javascript:;' class='item_ingredient' data-id='glycerin'>Glycerin</a>...</p>",
      "ingredients_list": [
        {
          "name": "Glycerin",
          "slug": "glycerin",
          "link": "/ingredient-dictionary/glycerin"
        }
      ]
    },
    "seo_title": "SEO Title",
    "seo_description": "SEO Description",
    "stock": 1,
    "best": 1,
    "is_new": 0,
    "cbmp": "CBMP123456",
    "option1_name": "Phân loại",
    "has_variants": 1,
    "brand": {
      "id": 1,
      "name": "Foellie",
      "slug": "foellie",
      "image": "https://...",
      "logo": "https://..."
    },
    "origin": {
      "id": 1,
      "name": "Pháp",
      "slug": "phap"
    },
    "categories": [5, 12, 15],
    "category": {
      "id": 5,
      "name": "Nước hoa",
      "slug": "nuoc-hoa"
    },
    "first_variant": {
      "id": 10,
      "sku": "SKU-001",
      "price": 100000,
      "sale": 80000,
      "stock": 50
    },
    "variants": [...],
    "variants_count": 3,
    "rating": {
      "average": 4.5,
      "count": 120,
      "sum": 540
    },
    "total_sold": 1500,
    "rates": [...],
    "flash_sale": {...},
    "deal": {...},
    "related_products": [...]
  }
}
```

---

## Response Lỗi (404 Not Found)

```json
{
  "success": false,
  "message": "Sản phẩm không tồn tại"
}
```

---

## Kiểm Tra Checklist

Sau khi test, kiểm tra các điểm sau:

- [ ] ✅ Response code = 200 (với slug hợp lệ)
- [ ] ✅ Response code = 404 (với slug không tồn tại)
- [ ] ✅ `success: true` trong response
- [ ] ✅ `data` object có đầy đủ thông tin
- [ ] ✅ `data.id`, `data.name`, `data.slug` có giá trị
- [ ] ✅ `data.gallery` là array (không phải JSON string)
- [ ] ✅ `data.categories` là array (không phải JSON string)
- [ ] ✅ `data.variants` là array với price_info đầy đủ
- [ ] ✅ `data.ingredient` có structure: raw, html, ingredients_list
- [ ] ✅ `data.rating` có average, count, sum
- [ ] ✅ `data.flash_sale` hiển thị nếu có Flash Sale active
- [ ] ✅ `data.deal` hiển thị nếu có Deal active
- [ ] ✅ `data.related_products` là array
- [ ] ✅ Image URLs sử dụng R2 CDN format
- [ ] ✅ Response time < 500ms (với cache)

---

## Troubleshooting

### Lỗi: Connection refused

**Nguyên nhân:** Laravel server chưa chạy

**Giải pháp:**
```bash
php artisan serve
```

### Lỗi: 404 Not Found (ngay cả với slug hợp lệ)

**Nguyên nhân:** Route chưa được đăng ký hoặc slug không tồn tại

**Giải pháp:**
1. Kiểm tra `routes/api.php` có route:
   ```php
   Route::prefix('v1/products')->namespace('Api\V1')->group(function () {
       Route::get('/{slug}', 'ProductController@show');
   });
   ```
2. Kiểm tra slug trong database:
   ```sql
   SELECT id, name, slug FROM posts WHERE type='product' AND status='1' LIMIT 10;
   ```

### Lỗi: 500 Internal Server Error

**Nguyên nhân:** Lỗi trong code hoặc database connection

**Giải pháp:**
1. Kiểm tra Laravel logs: `storage/logs/laravel.log`
2. Kiểm tra database connection trong `.env`
3. Chạy `php artisan config:clear` và `php artisan cache:clear`

### Response không có dữ liệu

**Nguyên nhân:** Eager Loading chưa load relationships

**Giải pháp:**
1. Kiểm tra ProductController có sử dụng `with()` để load relationships
2. Kiểm tra model relationships đã được định nghĩa đúng

---

## Performance Testing

### Test Response Time

```bash
time curl -X GET "http://localhost:8000/api/v1/products/{slug}" \
  -H "Accept: application/json" -o /dev/null -s -w "%{time_total}\n"
```

### Test với Cache

Lần đầu tiên request sẽ chậm hơn (không có cache).
Lần thứ 2 sẽ nhanh hơn (có cache).

Cache TTL: 30 phút (1800 giây)

---

## Kết Quả Mong Đợi

- ✅ Endpoint trả về đúng format JSON
- ✅ Tất cả dữ liệu cần thiết đều có trong response
- ✅ Gallery và Categories là array (không phải JSON string)
- ✅ Ingredients được xử lý và link đúng
- ✅ Price calculation đúng thứ tự ưu tiên
- ✅ Response time < 500ms (với cache)
- ✅ Không có N+1 query problem

---

**Ngày tạo:** 2025-01-18
**Phiên bản:** 1.0
