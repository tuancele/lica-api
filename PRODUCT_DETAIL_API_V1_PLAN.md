# Kế Hoạch Nâng Cấp Trang Chi Tiết Sản Phẩm Sang RESTful API V1

## 📋 Tổng Quan

**Mục tiêu:** Xây dựng endpoint `GET /api/v1/products/{slug}` để trả về toàn bộ dữ liệu cần thiết cho trang chi tiết sản phẩm theo chuẩn RESTful API V1, tương thích với mobile app và frontend hiện đại.

**Ví dụ URL:** `nuoc-hoa-vung-kin-foellie-bijou-chinh-hang-100`

---

## 🔍 Phân Tích Logic Backend Hiện Tại

### 1. Controller Hiện Tại: `app/Themes/Website/Controllers/ProductController.php`

**Phương thức `show($slug)`:**
- Tìm sản phẩm theo slug với điều kiện: `status='1'` và `type='product'`
- Lấy dữ liệu:
  - Product model với relationships: `brand`, `origin`
  - Variants: Lấy tất cả variants, sắp xếp theo `position` và `id`
  - Categories: Parse từ JSON `cat_id`, lấy category đầu tiên
  - Rates: Lấy 5 đánh giá mới nhất, và tất cả rates để tính điểm trung bình
  - Related products: Lấy 9 sản phẩm cùng category
  - Flash Sale: Kiểm tra Flash Sale đang hoạt động
  - Deal: Kiểm tra Deal sốc đang hoạt động
  - Gallery: Parse từ JSON `gallery`

**Điểm cần cải thiện:**
- ❌ Không sử dụng Eager Loading đầy đủ (N+1 query problem)
- ❌ Logic xử lý ingredients/paulas nằm trong Blade view (không tái sử dụng được)
- ❌ Không có Resource class để chuẩn hóa dữ liệu trả về
- ❌ Xử lý gallery và cat_id thủ công (json_decode)

### 2. Model Relationships: `app/Modules/Product/Models/Product.php`

**Relationships hiện có:**
```php
- brand(): belongsTo(Brand)
- origin(): belongsTo(Origin)
- variants(): hasMany(Variant)
- rates(): hasMany(Rate)
- category(): belongsTo(Product) // Self-referential cho taxonomy
```

**Accessor:**
- `price_info`: Tính giá ưu tiên Flash Sale > Marketing Campaign > Sale > Normal

**Trường dữ liệu quan trọng:**
- `gallery`: JSON string → cần parse thành array
- `cat_id`: JSON string → cần parse thành array
- `ingredient`: Text/HTML → cần extract paulas links

### 3. Xử Lý Ingredients/Paulas

**Logic hiện tại (từ `detail.blade.php`):**
```php
// Tự động link ingredients từ text
$str = $detail->ingredient;
if (strpos($str, 'item_ingredient') === false) {
    $list = Ingredient::where('status','1')->get();
    foreach ($list as $value) {
        $str = str_replace($value->name, 
            '<a href="javascript:;" class="item_ingredient" data-id="'.$value->slug.'">'.$value->name.'</a>', 
            $str);
    }
}
```

**Cần tái sử dụng logic này trong Service/Helper:**
- Extract danh sách ingredients từ text
- Link đến IngredientPaulas dictionary
- Trả về cả HTML đã xử lý và danh sách ingredients dạng array

---

## 🎯 Kế Hoạch Xây Dựng API V1

### 1. Endpoint: `GET /api/v1/products/{slug}`

**URL Pattern:** `/api/v1/products/{slug}`

**Method:** `GET`

**Controller:** `App\Http\Controllers\Api\V1\ProductController@show`

**Route Registration:** Thêm vào `routes/api.php`:
```php
Route::prefix('v1/products')->namespace('Api\V1')->group(function () {
    Route::get('/{slug}', 'ProductController@show');
});
```

### 2. Eager Loading Strategy

**Tối ưu query với Eager Loading:**
```php
Product::with([
    'brand:id,name,slug,image,logo',           // Brand info
    'origin:id,name,slug',                      // Origin info
    'variants' => function($query) {
        $query->orderBy('position', 'asc')
              ->orderBy('id', 'asc')
              ->with(['color:id,name,color', 'size:id,name,unit']);
    },
    'rates' => function($query) {
        $query->where('status', '1')
              ->orderBy('created_at', 'desc')
              ->limit(5);
    },
    'category:id,name,slug,cat_id'             // Primary category
])
->where([['slug', $slug], ['status', '1'], ['type', 'product']])
->first();
```

**Lợi ích:**
- ✅ Giảm số lượng queries từ ~20+ xuống còn 3-4 queries
- ✅ Tải tất cả dữ liệu liên quan trong một lần
- ✅ Tối ưu cho mobile app (giảm số lần request)

### 3. Resource Class: `ProductDetailResource`

**Vị trí:** `app/Http/Resources/Product/ProductDetailResource.php`

**Cấu trúc dữ liệu trả về:**
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
      "html": "<p>Water, <a href='...'>Glycerin</a>...</p>",
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
    "category": {
      "id": 5,
      "name": "Nước hoa",
      "slug": "nuoc-hoa"
    },
    "categories": [5, 12, 15],
    "first_variant": {
      "id": 10,
      "sku": "SKU-001",
      "price": 100000,
      "sale": 80000,
      "stock": 50
    },
    "variants": [
      {
        "id": 10,
        "sku": "SKU-001",
        "option1_value": "100ml",
        "image": "https://...",
        "price": 100000,
        "sale": 80000,
        "stock": 50,
        "weight": 0.1,
        "size_id": 1,
        "color_id": null,
        "color": null,
        "size": {
          "id": 1,
          "name": "100ml",
          "unit": "ml"
        },
        "price_info": {
          "final_price": 70000,
          "original_price": 100000,
          "type": "flashsale",
          "label": "Flash Sale",
          "discount_percent": 30,
          "html": "<p>70,000đ</p><del>100,000đ</del><div class='tag'><span>-30%</span></div>"
        },
        "option_label": "100ml"
      }
    ],
    "variants_count": 3,
    "rating": {
      "average": 4.5,
      "count": 120,
      "sum": 540
    },
    "total_sold": 1500,
    "rates": [
      {
        "id": 1,
        "rate": 5,
        "comment": "Sản phẩm rất tốt",
        "user_name": "Nguyễn Văn A",
        "created_at": "2024-01-15T10:30:00.000000Z"
      }
    ],
    "flash_sale": {
      "id": 1,
      "name": "Flash Sale Tháng 1",
      "start": 1704067200,
      "end": 1704153600,
      "end_date": "2024/01/02 00:00:00",
      "price_sale": 60000,
      "number": 100,
      "buy": 50,
      "remaining": 50
    },
    "deal": {
      "id": 1,
      "name": "Deal sốc",
      "limited": 2,
      "sale_deals": [
        {
          "id": 1,
          "product_id": 2,
          "product_name": "Sản phẩm kèm theo",
          "product_image": "https://...",
          "variant_id": 2,
          "price": 50000,
          "original_price": 80000
        }
      ]
    },
    "related_products": [
      {
        "id": 2,
        "name": "Sản phẩm liên quan",
        "slug": "san-pham-lien-quan",
        "image": "https://...",
        "brand": {
          "id": 1,
          "name": "Foellie",
          "slug": "foellie"
        },
        "price_info": {
          "price": 90000,
          "original_price": 120000,
          "type": "sale",
          "label": "Giảm giá",
          "discount_percent": 25
        },
        "stock": 1,
        "best": 0,
        "is_new": 1
      }
    ]
  }
}
```

### 4. Xử Lý Logic Nghiệp Vụ

#### 4.1. Ingredients/Paulas Processing

**Service:** `app/Services/Product/IngredientService.php`

**Phương thức:**
```php
public function processIngredient(string $ingredientText): array
{
    // 1. Extract ingredients từ text
    // 2. Link đến IngredientPaulas dictionary
    // 3. Trả về: raw, html, ingredients_list
}
```

**Logic:**
- Nếu `ingredient` đã có HTML với `item_ingredient` links → parse links
- Nếu chưa có → tự động link từ danh sách Ingredient
- Trả về cả HTML và danh sách ingredients dạng array

#### 4.2. Price Calculation

**Tái sử dụng:** `PriceCalculationService` (đã có sẵn)

**Thứ tự ưu tiên:**
1. Flash Sale (nếu đang active)
2. Marketing Campaign (nếu đang active)
3. Variant sale price
4. Variant normal price

#### 4.3. Stock & Status Validation

**Kiểm tra:**
- Product `status = '1'` (active)
- Product `stock = '1'` (có hàng)
- Variant `stock > 0` (nếu có variants)

**Trả về:**
- `stock`: 0 hoặc 1 (product level)
- `variants[].stock`: số lượng cụ thể (variant level)

### 5. Validation & Error Handling

**Validation:**
- Slug phải tồn tại trong database
- Product phải có `status = '1'` và `type = 'product'`

**Error Responses:**

**404 Not Found:**
```json
{
  "success": false,
  "message": "Sản phẩm không tồn tại"
}
```

**500 Internal Server Error:**
```json
{
  "success": false,
  "message": "Lỗi hệ thống",
  "error": "Chi tiết lỗi (chỉ trong debug mode)"
}
```

---

## 📝 Implementation Steps

### Bước 1: Tạo Service Xử Lý Ingredients

**File:** `app/Services/Product/IngredientService.php`

**Chức năng:**
- Extract ingredients từ text
- Link đến IngredientPaulas
- Trả về structured data

### Bước 2: Tạo ProductDetailResource

**File:** `app/Http/Resources/Product/ProductDetailResource.php`

**Chức năng:**
- Chuẩn hóa dữ liệu trả về
- Xử lý gallery, categories, variants
- Format price info

### Bước 3: Tạo V1 ProductController

**File:** `app/Http/Controllers/Api/V1/ProductController.php`

**Chức năng:**
- Method `show($slug)`
- Eager Loading tối ưu
- Xử lý Flash Sale, Deal, Related Products
- Sử dụng ProductDetailResource

### Bước 4: Đăng Ký Route

**File:** `routes/api.php`

**Thêm:**
```php
Route::prefix('v1/products')->namespace('Api\V1')->group(function () {
    Route::get('/{slug}', 'ProductController@show');
});
```

### Bước 5: Testing

**Test Cases:**
1. ✅ Sản phẩm có variants
2. ✅ Sản phẩm không có variants
3. ✅ Sản phẩm có Flash Sale
4. ✅ Sản phẩm có Deal
5. ✅ Sản phẩm có ingredients
6. ✅ Sản phẩm không tồn tại (404)
7. ✅ Sản phẩm inactive (404)

### Bước 6: Documentation

**Cập nhật:** `API_V1_DOCS.md`

**Nội dung:**
- Endpoint description
- Request/Response examples
- Error handling
- Performance notes

---

## 🔄 Backward Compatibility

**Quan trọng:** Giữ nguyên route web hiện tại

**Routes không thay đổi:**
- ✅ `GET /{slug}` → `ProductController@show` (Blade view)
- ✅ `GET /api/products/{slug}/detail` → Vẫn hoạt động (legacy API)

**Routes mới:**
- 🆕 `GET /api/v1/products/{slug}` → RESTful API V1

---

## 📊 Performance Optimization

### 1. Caching Strategy

**Cache Key:** `api_v1_product_detail_{slug}`

**TTL:** 30 phút (1800 giây)

**Invalidation:**
- Khi product được update
- Khi variant được update
- Khi Flash Sale thay đổi

### 2. Query Optimization

**Trước (N+1 queries):**
- 1 query product
- N queries variants
- 1 query brand
- 1 query origin
- 1 query rates
- 1 query category
- N queries related products
- **Tổng: ~15-20 queries**

**Sau (Eager Loading):**
- 1 query product với relationships
- 1 query related products
- 1 query Flash Sale (nếu cần)
- 1 query Deal (nếu cần)
- **Tổng: ~3-5 queries**

**Cải thiện: ~75% số lượng queries**

### 3. Image URL Formatting

**Sử dụng:** Helper method `formatImageUrl()` từ `ProductController` hiện có

**Lợi ích:**
- Tự động sử dụng R2 CDN
- Xử lý edge cases (duplicate domains, missing images)
- Consistent URL format

---

## 🧪 Testing Checklist

- [ ] Test với sản phẩm có variants
- [ ] Test với sản phẩm không có variants
- [ ] Test với sản phẩm có Flash Sale active
- [ ] Test với sản phẩm có Deal active
- [ ] Test với sản phẩm có ingredients
- [ ] Test với sản phẩm không tồn tại (404)
- [ ] Test với sản phẩm inactive (404)
- [ ] Test performance (số lượng queries)
- [ ] Test caching hoạt động đúng
- [ ] Test image URLs format đúng
- [ ] Test price calculation đúng thứ tự ưu tiên

---

## 📚 Tài Liệu Tham Khảo

1. **API V1 Docs:** `API_V1_DOCS.md`
2. **Admin API Docs:** `API_ADMIN_DOCS.md`
3. **Product Model:** `app/Modules/Product/Models/Product.php`
4. **Variant Model:** `app/Modules/Product/Models/Variant.php`
5. **Price Service:** `app/Services/PriceCalculationService.php`
6. **Existing API:** `app/Http/Controllers/Api/ProductController.php`

---

## ✅ Kết Luận

Kế hoạch này đảm bảo:
- ✅ RESTful API V1 chuẩn
- ✅ Tối ưu performance với Eager Loading
- ✅ Chuẩn hóa dữ liệu với Resource classes
- ✅ Tái sử dụng logic nghiệp vụ hiện có
- ✅ Backward compatibility với routes web
- ✅ Documentation đầy đủ

**Thời gian ước tính:** 4-6 giờ development + testing

**Ngày tạo:** 2025-01-18
**Phiên bản:** 1.0
