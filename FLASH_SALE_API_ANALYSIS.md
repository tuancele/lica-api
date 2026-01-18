# Phân Tích Chuyên Sâu & Kế Hoạch Nâng Cấp Flash Sale API V1

## 📋 Mục Lục
1. [Phân Tích Codebase Hiện Tại](#1-phân-tích-codebase-hiện-tại)
2. [Cấu Trúc Database](#2-cấu-trúc-database)
3. [Luồng Dữ Liệu Hiện Tại](#3-luồng-dữ-liệu-hiện-tại)
4. [Kế Hoạch Chuyển Đổi API](#4-kế-hoạch-chuyển-đổi-api)
5. [Chuẩn Hóa Resource](#5-chuẩn-hóa-resource)
6. [Đánh Giá Logic Nghiệp Vụ](#6-đánh-giá-logic-nghiệp-vụ)
7. [Rủi Ro & Giải Pháp](#7-rủi-ro--giải-pháp)

---

## 1. Phân Tích Codebase Hiện Tại

### 1.1 Cấu Trúc Module FlashSale

**Vị trí:** `app/Modules/FlashSale/`

**Các thành phần chính:**
- **Models:**
  - `FlashSale.php`: Model chính quản lý chương trình Flash Sale
  - `ProductSale.php`: Model quản lý sản phẩm trong Flash Sale (quan hệ many-to-many)
  
- **Controller:**
  - `FlashSaleController.php`: Xử lý CRUD cho Admin Panel (Blade-based)
  
- **Routes:**
  - `routes.php`: Đăng ký routes với prefix `admin/flashsale`
  
- **Views:**
  - `index.blade.php`: Danh sách Flash Sale
  - `create.blade.php`: Tạo mới Flash Sale
  - `edit.blade.php`: Chỉnh sửa Flash Sale
  - `product_rows.blade.php`: Partial view hiển thị sản phẩm đã chọn
  - `load_product.blade.php`: Modal tìm kiếm sản phẩm

### 1.2 Quan Hệ Database

```
flashsales (bảng chính)
├── id (PK)
├── start (timestamp - Unix timestamp)
├── end (timestamp - Unix timestamp)
├── status (0/1)
├── user_id (FK -> users)
├── created_at
└── updated_at

productsales (bảng quan hệ)
├── id (PK)
├── flashsale_id (FK -> flashsales)
├── product_id (FK -> posts/products)
├── price_sale (giá khuyến mãi)
├── number (số lượng khuyến mãi)
├── buy (số lượng đã bán)
├── user_id (FK -> users)
├── created_at
└── updated_at
```

### 1.3 Quan Hệ Eloquent

**FlashSale Model:**
```php
- belongsTo: User
- hasMany: ProductSale
```

**ProductSale Model:**
```php
- belongsTo: User
- belongsTo: FlashSale
- belongsTo: Product (posts table)
- belongsTo: Variant (variants table) - **MỚI**
```

---

## 2. Cấu Trúc Database

### 2.1 Bảng `flashsales`

| Cột | Kiểu | Mô Tả |
|-----|------|-------|
| id | INT (PK) | ID chương trình |
| start | INT | Thời gian bắt đầu (Unix timestamp) |
| end | INT | Thời gian kết thúc (Unix timestamp) |
| status | TINYINT | Trạng thái (0=ẩn, 1=hiển thị) |
| user_id | INT (FK) | Người tạo |
| created_at | TIMESTAMP | Ngày tạo |
| updated_at | TIMESTAMP | Ngày cập nhật |

**Lưu ý quan trọng:**
- `start` và `end` sử dụng **Unix timestamp** (INT), không phải DATETIME
- Logic kiểm tra active: `start <= now() AND end >= now() AND status = 1`

### 2.2 Bảng `productsales`

| Cột | Kiểu | Mô Tả |
|-----|------|-------|
| id | INT (PK) | ID bản ghi |
| flashsale_id | INT (FK) | ID chương trình Flash Sale |
| product_id | INT (FK) | ID sản phẩm (posts table) |
| variant_id | INT (FK, nullable) | ID phân loại (variants table) - **MỚI** |
| price_sale | DECIMAL | Giá khuyến mãi |
| number | INT | Số lượng khuyến mãi |
| buy | INT | Số lượng đã bán |
| user_id | INT (FK) | Người tạo |
| created_at | TIMESTAMP | Ngày tạo |
| updated_at | TIMESTAMP | Ngày cập nhật |

**Logic nghiệp vụ:**
- Sản phẩm chỉ áp dụng Flash Sale khi: `buy < number` (còn hàng khuyến mãi)
- `price_sale` là giá cuối cùng hiển thị cho khách hàng
- **MỚI:** Nếu sản phẩm có variants (`has_variants = 1`), cần set Flash Sale cho từng variant riêng biệt
- **Tương thích ngược:** `variant_id` có thể NULL để hỗ trợ sản phẩm không có variants

---

## 3. Luồng Dữ Liệu Hiện Tại

### 3.1 Admin Panel (Blade-based)

**Luồng tạo/chỉnh sửa Flash Sale:**

1. **Tạo mới (`store`):**
   ```
   Admin → POST /admin/flashsale/create
   → Validate (start, end required)
   → Insert flashsales (start/end convert to timestamp)
   → Insert productsales (price_sale, number cho từng sản phẩm)
   → Return JSON response
   ```

2. **Chỉnh sửa (`update`):**
   ```
   Admin → POST /admin/flashsale/edit
   → Validate
   → Update flashsales
   → Xóa productsales không còn trong checklist
   → Update/Insert productsales mới
   → Return JSON response
   ```

3. **Tìm kiếm sản phẩm (`searchProduct`):**
   ```
   Admin → POST /admin/flashsale/search-product
   → Query products (status=1, type=product, name LIKE keyword)
   → Return HTML table rows (AJAX)
   ```

4. **Hiển thị variants khi add/edit (`choseProduct`):**
   ```
   Admin → Chọn sản phẩm có variants
   → Kiểm tra product.has_variants = 1
   → Load tất cả variants của sản phẩm
   → Hiển thị từng variant với input price_sale, number riêng
   → Lưu ProductSale với variant_id tương ứng
   ```

### 3.2 Frontend Website

**Luồng hiển thị giá Flash Sale:**

1. **Helper Function `checkSale($productId)`:**
   ```
   Product Display → checkSale($id)
   → Lấy timestamp hiện tại
   → Query FlashSale active (status=1, start<=now, end>=now)
   → Query ProductSale (flashsale_id, product_id)
   → Kiểm tra buy < number (còn hàng)
   → Return HTML: price_sale + original_price + percent discount
   ```

2. **Model Attribute `getPriceInfoAttribute()`:**
   ```
   Product Model → $product->price_info
   → Priority 1: Flash Sale (nếu active)
   → Priority 2: Marketing Campaign
   → Priority 3: Variant sale price
   → Priority 4: Normal price
   → Return object {price, original_price, type, label}
   ```

3. **Trang Flash Sale (`/flashsale`):**
   ```
   User → GET /flashsale
   → HomeController@flashsale
   → Query products có Flash Sale active
   → Render Blade view với danh sách sản phẩm
   ```

### 3.3 Logic Tính Giá Khuyến Mãi

**Thứ tự ưu tiên (theo `Product::getPriceInfoAttribute()`):**

1. **Flash Sale** (ưu tiên cao nhất)
   - Điều kiện: Flash Sale active + ProductSale tồn tại + `buy < number`
   - Giá: `ProductSale::price_sale`
   - Original: `Variant::price`

2. **Marketing Campaign**
   - Điều kiện: Campaign active + Product trong campaign
   - Giá: `MarketingCampaignProduct::price`
   - Original: `Variant::price`

3. **Variant Sale Price**
   - Điều kiện: `Variant::sale > 0 AND sale < price`
   - Giá: `Variant::sale`
   - Original: `Variant::price`

4. **Normal Price**
   - Giá: `Variant::price`
   - Original: `Variant::price`

---

## 4. Kế Hoạch Chuyển Đổi API

### 4.1 Public API V1 (Mobile App)

#### 4.1.1 GET /api/v1/flash-sales/active

**Mục tiêu:** Lấy danh sách các chương trình Flash Sale đang diễn ra

**Endpoint:** `GET /api/v1/flash-sales/active`

**Query Parameters:**
- `limit` (integer, optional): Số lượng kết quả, mặc định 10, tối đa 50

**Logic xử lý:**
```php
1. Lấy timestamp hiện tại: time()
2. Query FlashSale:
   - WHERE status = 1
   - WHERE start <= now()
   - WHERE end >= now()
   - ORDER BY start DESC
   - LIMIT limit
3. Format response với FlashSaleResource
```

**Response mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Flash Sale Tháng 1",
      "start": "2024-01-15T00:00:00.000000Z",
      "end": "2024-01-20T23:59:59.000000Z",
      "start_timestamp": 1705276800,
      "end_timestamp": 1705708799,
      "status": "1",
      "countdown_seconds": 432000,
      "total_products": 25,
      "created_at": "2024-01-10T00:00:00.000000Z",
      "updated_at": "2024-01-10T00:00:00.000000Z"
    }
  ],
  "count": 1
}
```

**Controller:** `App\Http\Controllers\Api\V1\FlashSaleController@getActive`

---

#### 4.1.2 GET /api/v1/flash-sales/{id}/products

**Mục tiêu:** Lấy danh sách sản phẩm trong Flash Sale cụ thể (với Eager Loading)

**Endpoint:** `GET /api/v1/flash-sales/{id}/products`

**URL Parameters:**
- `id` (integer, required): ID chương trình Flash Sale

**Query Parameters:**
- `page` (integer, optional): Trang, mặc định 1
- `limit` (integer, optional): Số lượng mỗi trang, mặc định 20, tối đa 100
- `available_only` (boolean, optional): Chỉ lấy sản phẩm còn hàng (buy < number), mặc định true

**Logic xử lý:**
```php
1. Validate FlashSale tồn tại và đang active
2. Query ProductSale với Eager Loading:
   - with(['product' => function($q) {
       $q->with(['brand', 'origin', 'variants']);
     }])
   - WHERE flashsale_id = {id}
   - WHERE buy < number (nếu available_only = true)
3. Format response với ProductResource (đã có sẵn)
4. Include thông tin Flash Sale: price_sale, number, buy, remaining
```

**Response mẫu (200):**
```json
{
  "success": true,
  "data": {
    "flash_sale": {
      "id": 1,
      "start": "2024-01-15T00:00:00.000000Z",
      "end": "2024-01-20T23:59:59.000000Z",
      "countdown_seconds": 432000
    },
    "products": [
      {
        "id": 10,
        "name": "Sản phẩm Flash Sale",
        "slug": "san-pham-flash-sale",
        "image": "https://cdn.lica.vn/uploads/image/product.jpg",
        "brand": {
          "id": 1,
          "name": "Brand Name"
        },
        "variants": [
          {
            "id": 1,
            "price": 200000,
            "sale": 0,
            "stock": 50
          }
        ],
        "flash_sale_info": {
          "price_sale": 150000,
          "original_price": 200000,
          "discount_percent": 25,
          "number": 100,
          "buy": 45,
          "remaining": 55
        },
        "price_info": {
          "price": 150000,
          "original_price": 200000,
          "type": "flashsale",
          "label": "Flash Sale"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 25,
      "last_page": 2
    }
  }
}
```

**Controller:** `App\Http\Controllers\Api\V1\FlashSaleController@getProducts`

---

### 4.2 Admin API (CRUD Operations)

#### 4.2.1 GET /admin/api/flash-sales

**Mục tiêu:** Lấy danh sách Flash Sale (Admin)

**Query Parameters:**
- `page` (integer, optional): Trang, mặc định 1
- `limit` (integer, optional): Số lượng mỗi trang, mặc định 10
- `status` (string, optional): Lọc theo trạng thái (0/1)
- `keyword` (string, optional): Tìm kiếm theo tên

**Response:** Danh sách Flash Sale với pagination

---

#### 4.2.2 GET /admin/api/flash-sales/{id}

**Mục tiêu:** Lấy chi tiết Flash Sale (bao gồm danh sách sản phẩm)

**Response:** Flash Sale detail + productsales array

---

#### 4.2.3 POST /admin/api/flash-sales

**Mục tiêu:** Tạo mới Flash Sale

**Request Body:**
```json
{
  "start": "2024-01-15 00:00:00",
  "end": "2024-01-20 23:59:59",
  "status": "1",
  "products": [
    {
      "product_id": 10,
      "variant_id": null,
      "price_sale": 150000,
      "number": 100
    },
    {
      "product_id": 10,
      "variant_id": 5,
      "price_sale": 140000,
      "number": 50
    }
  ]
}
```

**Validation:**
- `start`: required, date format
- `end`: required, date format, after:start
- `status`: required, in:0,1
- `products`: array, optional
- `products.*.product_id`: required, exists:posts,id
- `products.*.variant_id`: nullable, exists:variants,id (phải thuộc product_id)
- `products.*.price_sale`: required, numeric, min:0
- `products.*.number`: required, integer, min:1

**Logic xử lý:**
- Nếu sản phẩm có variants (`has_variants = 1`), bắt buộc phải gửi từng variant riêng
- Nếu sản phẩm không có variants, `variant_id` = null
- Validate: `variant_id` phải thuộc `product_id` tương ứng

---

#### 4.2.4 PUT /admin/api/flash-sales/{id}

**Mục tiêu:** Cập nhật Flash Sale

**Request Body:** Tương tự POST, nhưng có thể chỉ gửi các field cần update

**Logic:**
- Update flashsales table
- Xóa productsales không còn trong request
- Update/Insert productsales mới

---

#### 4.2.5 DELETE /admin/api/flash-sales/{id}

**Mục tiêu:** Xóa Flash Sale

**Logic:**
- Xóa productsales liên quan
- Xóa flashsales

---

#### 4.2.6 POST /admin/api/flash-sales/{id}/status

**Mục tiêu:** Thay đổi trạng thái Flash Sale

**Request Body:**
```json
{
  "status": "1"
}
```

---

#### 4.2.7 POST /admin/api/flash-sales/search-products

**Mục tiêu:** Tìm kiếm sản phẩm để thêm vào Flash Sale (Admin)

**Query Parameters:**
- `keyword` (string, required): Từ khóa tìm kiếm
- `page` (integer, optional): Trang, mặc định 1
- `limit` (integer, optional): Số lượng, mặc định 50

**Response:** Danh sách products với thông tin cơ bản (id, name, image, price)

---

## 5. Chuẩn Hóa Resource

### 5.1 FlashSaleResource

**Vị trí:** `app/Http/Resources/FlashSale/FlashSaleResource.php`

**Cấu trúc:**
```php
{
  "id": 1,
  "name": "Flash Sale Tháng 1", // Nếu có field name, nếu không dùng "Flash Sale #{id}"
  "start": "2024-01-15T00:00:00.000000Z", // ISO 8601 format
  "end": "2024-01-20T23:59:59.000000Z", // ISO 8601 format
  "start_timestamp": 1705276800, // Unix timestamp (để tương thích)
  "end_timestamp": 1705708799, // Unix timestamp
  "status": "1",
  "is_active": true, // Computed: start <= now AND end >= now AND status = 1
  "countdown_seconds": 432000, // Computed: end - now (nếu active)
  "total_products": 25, // Count productsales
  "created_at": "2024-01-10T00:00:00.000000Z",
  "updated_at": "2024-01-10T00:00:00.000000Z"
}
```

**Lưu ý:**
- Convert timestamp sang ISO 8601 format cho `start` và `end`
- Giữ nguyên timestamp cho tương thích ngược
- Tính toán `is_active` và `countdown_seconds` trong Resource

---

### 5.2 ProductSaleResource

**Vị trí:** `app/Http/Resources/FlashSale/ProductSaleResource.php`

**Cấu trúc:**
```php
{
  "id": 1,
  "flashsale_id": 1,
  "product_id": 10,
  "variant_id": 5, // nullable - MỚI
  "price_sale": 150000,
  "number": 100,
  "buy": 45,
  "remaining": 55, // Computed: number - buy
  "is_available": true, // Computed: buy < number
  "product": { // Eager loaded ProductResource
    // ... product data
  },
  "variant": { // Eager loaded VariantResource (nếu có) - MỚI
    "id": 5,
    "sku": "SKU-001",
    "option1_value": "500ml",
    "price": 200000,
    "stock": 50,
    "color": {...},
    "size": {...}
  }
}
```

---

### 5.3 FlashSaleDetailResource (Admin)

**Vị trí:** `app/Http/Resources/FlashSale/FlashSaleDetailResource.php`

**Cấu trúc:** Mở rộng FlashSaleResource + thêm `products` array

```php
{
  // ... FlashSaleResource fields
  "products": [
    {
      // ProductSaleResource
    }
  ]
}
```

---

## 6. Đánh Giá Logic Nghiệp Vụ

### 6.1 Tính Giá Khuyến Mãi

**Vấn đề hiện tại:**
- Logic tính giá nằm rải rác ở nhiều nơi:
  - `Product::getPriceInfoAttribute()` (Model)
  - `checkSale()` helper function (Frontend)
  - `getVariantFinalPrice()` helper function (Frontend)
- **MỚI:** Chưa hỗ trợ Flash Sale theo từng variant, chỉ set ở cấp product
- **MỚI:** Khi sản phẩm có variants, cần kiểm tra Flash Sale theo `variant_id` thay vì chỉ `product_id`

**Giải pháp:**
1. **Tạo Service Layer:** `App\Services\PriceCalculationService`
   - Method: `calculateProductPrice(Product $product): PriceInfo`
   - Method: `calculateVariantPrice(Variant $variant, ?int $productId = null): PriceInfo`
   - **MỚI:** Method: `calculateVariantPriceWithFlashSale(Variant $variant, ?int $flashSaleId = null): PriceInfo`
   - Centralize logic tính giá tại một nơi

2. **Đảm bảo tính đồng nhất:**
   - API V1 sử dụng `PriceCalculationService`
   - Frontend Blade vẫn dùng helper functions (tương thích ngược)
   - Helper functions gọi `PriceCalculationService` internally

3. **Cấu trúc PriceInfo:**
```php
class PriceInfo {
  public float $price;           // Giá cuối cùng
  public float $original_price;  // Giá gốc
  public string $type;           // 'flashsale' | 'campaign' | 'sale' | 'normal'
  public string $label;          // 'Flash Sale' | 'Khuyến mại' | 'Giảm giá' | ''
  public ?int $discount_percent; // Phần trăm giảm giá
  public ?object $flash_sale_info; // Thông tin Flash Sale (nếu có)
  public ?int $variant_id;       // Variant ID nếu áp dụng Flash Sale cho variant - MỚI
}
```

4. **Logic kiểm tra Flash Sale mới (hỗ trợ variants):**
```php
// Ưu tiên: variant_id > product_id
$productSale = ProductSale::where('flashsale_id', $flashSaleId)
  ->where(function($q) use ($variantId, $productId) {
    if ($variantId) {
      $q->where('variant_id', $variantId);
    } else {
      $q->where('product_id', $productId)
        ->whereNull('variant_id');
    }
  })
  ->whereHas('flashsale', function($q) {
    $q->active();
  })
  ->first();
```

---

### 6.2 Kiểm Tra Flash Sale Active

**Logic hiện tại:**
```php
$now = time();
$flash = FlashSale::where([
  ['status', '1'],
  ['start', '<=', $now],
  ['end', '>=', $now]
])->first();
```

**Vấn đề:**
- Logic này được lặp lại ở nhiều nơi
- Không có scope hoặc method tái sử dụng

**Giải pháp:**
1. **Thêm Scope vào FlashSale Model:**
```php
public function scopeActive($query) {
  $now = time();
  return $query->where('status', '1')
    ->where('start', '<=', $now)
    ->where('end', '>=', $now);
}
```

2. **Thêm Accessor:**
```php
public function getIsActiveAttribute(): bool {
  $now = time();
  return $this->status == '1' 
    && $this->start <= $now 
    && $this->end >= $now;
}
```

---

### 6.3 Kiểm Tra Sản Phẩm Còn Hàng Flash Sale

**Logic hiện tại:**
```php
if($productSale->buy < $productSale->number) {
  // Áp dụng Flash Sale
}
```

**Giải pháp:**
1. **Thêm Accessor vào ProductSale Model:**
```php
public function getIsAvailableAttribute(): bool {
  return $this->buy < $this->number;
}

public function getRemainingAttribute(): int {
  return max(0, $this->number - $this->buy);
}
```

---

### 6.4 Countdown Timer

**Yêu cầu:** Mobile App cần thời gian đếm ngược (countdown) để hiển thị timer

**Giải pháp:**
1. **Tính toán trong FlashSaleResource:**
```php
'countdown_seconds' => $this->is_active 
  ? max(0, $this->end - time()) 
  : 0
```

2. **Format thêm cho frontend:**
```php
'countdown' => [
  'seconds' => 432000,
  'days' => 5,
  'hours' => 0,
  'minutes' => 0,
  'formatted' => '5 ngày 0 giờ 0 phút'
]
```

---

## 7. Rủi Ro & Giải Pháp

### 7.1 Rủi Ro

1. **Phá vỡ logic Blade hiện có:**
   - **Nguy cơ:** Thay đổi Model/Helper có thể ảnh hưởng đến frontend
   - **Giải pháp:** 
     - Giữ nguyên helper functions
     - Refactor internal logic, không thay đổi signature
     - Test kỹ các trang Blade sau khi refactor

2. **Performance với Eager Loading:**
   - **Nguy cơ:** N+1 query khi load products
   - **Giải pháp:**
     - Sử dụng `with(['product', 'product.brand', 'product.origin', 'product.variants'])`
     - Chỉ load các field cần thiết
     - Sử dụng pagination

3. **Timestamp vs DateTime:**
   - **Nguy cơ:** Confusion giữa timestamp (INT) và datetime (STRING)
   - **Giải pháp:**
     - API trả về cả 2 format: ISO 8601 (chuẩn) và timestamp (tương thích)
     - Document rõ ràng trong API docs

4. **Đồng bộ giá giữa API và Frontend:**
   - **Nguy cơ:** Mobile App hiển thị giá khác với Website
   - **Giải pháp:**
     - Sử dụng chung `PriceCalculationService`
     - Test so sánh giá giữa 2 nguồn

---

### 7.2 Migration Path

**Giai đoạn 1: Tạo API V1 (Không ảnh hưởng Blade)**
- Tạo Controllers mới
- Tạo Resources mới
- Đăng ký routes mới
- Test API độc lập

**Giai đoạn 2: Refactor Logic (Tương thích ngược)**
- Tạo `PriceCalculationService`
- Refactor helper functions để gọi Service
- Test lại các trang Blade

**Giai đoạn 3: Tối ưu Model**
- Thêm Scopes và Accessors
- Refactor Controller cũ để dùng Scopes
- Test lại toàn bộ

---

## 8. Tóm Tắt Implementation Plan

### 8.1 Files Cần Tạo

1. **Controllers:**
   - `app/Http/Controllers/Api/V1/FlashSaleController.php`
   - `app/Modules/ApiAdmin/Controllers/FlashSaleController.php` (Admin API)

2. **Resources:**
   - `app/Http/Resources/FlashSale/FlashSaleResource.php`
   - `app/Http/Resources/FlashSale/ProductSaleResource.php`
   - `app/Http/Resources/FlashSale/FlashSaleDetailResource.php`
   - `app/Http/Resources/Product/VariantResource.php` (nếu chưa có)

3. **Services:**
   - `app/Services/PriceCalculationService.php`

4. **Requests (Validation):**
   - `app/Http/Requests/Admin/FlashSale/StoreFlashSaleRequest.php`
   - `app/Http/Requests/Admin/FlashSale/UpdateFlashSaleRequest.php`

5. **Migrations:**
   - `database/migrations/YYYY_MM_DD_HHMMSS_add_variant_id_to_productsales_table.php`

### 8.2 Files Cần Sửa

1. **Models:**
   - `app/Modules/FlashSale/Models/FlashSale.php` (thêm scopes, accessors)
   - `app/Modules/FlashSale/Models/ProductSale.php` (thêm accessors)

2. **Routes:**
   - `routes/api.php` (thêm API V1 routes)
   - `app/Modules/ApiAdmin/routes.php` (thêm Admin API routes)

3. **Helpers:**
   - `app/Themes/Website/Helpers/Function.php` (refactor checkSale, getVariantFinalPrice)

4. **Views (Admin):**
   - `app/Modules/FlashSale/Views/product_rows.blade.php` (hiển thị variants)
   - `app/Modules/FlashSale/Views/load_product.blade.php` (hiển thị variants)
   - `app/Modules/FlashSale/Controllers/FlashSaleController.php` (logic load variants)

### 8.3 Documentation

1. **API Documentation:**
   - Cập nhật `API_V1_DOCS.md` (Public API)
   - Cập nhật `API_ADMIN_DOCS.md` (Admin API)

---

## 9. Chi Tiết Hỗ Trợ Variants

### 9.1 Migration: Thêm variant_id vào productsales

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_add_variant_id_to_productsales_table.php`

```php
Schema::table('productsales', function (Blueprint $table) {
    if (!Schema::hasColumn('productsales', 'variant_id')) {
        $table->unsignedInteger('variant_id')->nullable()->after('product_id');
        $table->foreign('variant_id')->references('id')->on('variants')->onDelete('cascade');
        $table->index(['flashsale_id', 'variant_id']);
    }
});
```

**Lưu ý:**
- `variant_id` là nullable để tương thích với dữ liệu cũ
- Thêm index để tối ưu query
- Foreign key với cascade delete

---

### 9.2 Cập Nhật Model ProductSale

**File:** `app/Modules/FlashSale/Models/ProductSale.php`

```php
// Thêm quan hệ
public function variant(){
    return $this->belongsTo('App\Modules\Product\Models\Variant', 'variant_id', 'id');
}

// Thêm scope
public function scopeForVariant($query, $variantId) {
    return $query->where('variant_id', $variantId);
}

public function scopeForProduct($query, $productId) {
    return $query->where('product_id', $productId)
        ->whereNull('variant_id');
}
```

---

### 9.3 Cập Nhật Admin View: Hiển Thị Variants

**File:** `app/Modules/FlashSale/Views/product_rows.blade.php`

**Logic mới:**
```php
@if($product->has_variants == 1)
    {{-- Hiển thị tất cả variants --}}
    @foreach($product->variants as $variant)
        <tr class="item-{{$product->id}}-variant-{{$variant->id}}">
            <td>
                <input type="checkbox" name="checklist[]" 
                    value="{{$product->id}}_v{{$variant->id}}">
            </td>
            <td>
                <img src="{{getImage($product->image)}}">
                <p>{{$product->name}}</p>
                <small class="text-muted">
                    Phân loại: {{$variant->option1_value ?? 'N/A'}}
                    @if($variant->color) - Màu: {{$variant->color->name}} @endif
                    @if($variant->size) - Size: {{$variant->size->name}} @endif
                </small>
            </td>
            <td>{{number_format($variant->price)}}đ</td>
            <td>
                <input type="text" name="pricesale[{{$product->id}}][{{$variant->id}}]" 
                    class="form-control pricesale price" value="{{$price_sale}}">
            </td>
            <td>
                <input type="number" name="numbersale[{{$product->id}}][{{$variant->id}}]" 
                    class="form-control" value="{{$number_sale}}">
            </td>
            <td>
                <input type="hidden" name="variant_ids[{{$product->id}}][{{$variant->id}}]" 
                    value="{{$variant->id}}">
                <a class="btn btn-danger btn-xs delete_item">Xóa</a>
            </td>
        </tr>
    @endforeach
@else
    {{-- Sản phẩm không có variants - giữ nguyên logic cũ --}}
    <tr class="item-{{$product->id}}">
        {{-- ... existing code ... --}}
    </tr>
@endif
```

---

### 9.4 Cập Nhật Controller: Xử Lý Variants

**File:** `app/Modules/FlashSale/Controllers/FlashSaleController.php`

**Method `store` và `update` cần xử lý:**

```php
// Xử lý products với variants
if(isset($pricesale) && !empty($pricesale)){
    foreach ($pricesale as $productId => $variants) {
        // Nếu là array -> sản phẩm có variants
        if(is_array($variants)) {
            foreach($variants as $variantId => $priceValue) {
                $numberValue = $numbersale[$productId][$variantId] ?? '0';
                
                $product = ProductSale::where([
                    ['flashsale_id', $request->id ?? $id],
                    ['product_id', $productId],
                    ['variant_id', $variantId]
                ])->first();
                
                if($product) {
                    ProductSale::where('id', $product->id)->update([
                        'price_sale' => str_replace(',','', $priceValue) ?: 0,
                        'number' => $numberValue,
                    ]);
                } else {
                    ProductSale::insertGetId([
                        'flashsale_id' => $request->id ?? $id,
                        'product_id' => $productId,
                        'variant_id' => $variantId,
                        'price_sale' => str_replace(',','', $priceValue) ?: 0,
                        'number' => $numberValue,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        } else {
            // Sản phẩm không có variants (logic cũ)
            // ... existing code ...
        }
    }
}
```

---

### 9.5 Cập Nhật Logic Tính Giá: Hỗ Trợ Variants

**File:** `app/Services/PriceCalculationService.php` (mới)

```php
public function calculateVariantPrice(Variant $variant, ?int $flashSaleId = null): PriceInfo
{
    $originalPrice = $variant->price;
    $finalPrice = $originalPrice;
    
    // 1. Check Flash Sale (ưu tiên variant_id)
    if ($flashSaleId) {
        $productSale = ProductSale::where('flashsale_id', $flashSaleId)
            ->where('variant_id', $variant->id)
            ->whereHas('flashsale', function($q) {
                $q->active();
            })
            ->first();
            
        if ($productSale && $productSale->is_available) {
            return new PriceInfo(
                price: $productSale->price_sale,
                original_price: $originalPrice,
                type: 'flashsale',
                label: 'Flash Sale',
                variant_id: $variant->id
            );
        }
    } else {
        // Tìm Flash Sale active bất kỳ
        $productSale = ProductSale::where('variant_id', $variant->id)
            ->whereHas('flashsale', function($q) {
                $q->active();
            })
            ->first();
            
        if ($productSale && $productSale->is_available) {
            return new PriceInfo(
                price: $productSale->price_sale,
                original_price: $originalPrice,
                type: 'flashsale',
                label: 'Flash Sale',
                variant_id: $variant->id
            );
        }
    }
    
    // 2. Check Marketing Campaign
    // ... existing logic ...
    
    // 3. Fallback to variant sale price
    // ... existing logic ...
}
```

---

### 9.6 API Response: Bao Gồm Variants

**Endpoint:** `GET /api/v1/flash-sales/{id}/products`

**Response mẫu với variants:**

```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 10,
        "name": "Sản phẩm có variants",
        "has_variants": true,
        "variants": [
          {
            "id": 5,
            "option1_value": "500ml",
            "price": 200000,
            "stock": 50,
            "flash_sale_info": {
              "price_sale": 150000,
              "original_price": 200000,
              "discount_percent": 25,
              "number": 100,
              "buy": 45,
              "remaining": 55
            }
          },
          {
            "id": 6,
            "option1_value": "1000ml",
            "price": 350000,
            "stock": 30,
            "flash_sale_info": {
              "price_sale": 280000,
              "original_price": 350000,
              "discount_percent": 20,
              "number": 50,
              "buy": 20,
              "remaining": 30
            }
          }
        ]
      }
    ]
  }
}
```

---

## 10. Next Steps

Sau khi hoàn thành phân tích này, các bước tiếp theo:

1. ✅ **Review & Approval:** Xem xét và phê duyệt kế hoạch
2. ⏳ **Implementation:** Bắt đầu viết code theo kế hoạch
3. ⏳ **Testing:** Test API và đảm bảo không ảnh hưởng Blade
4. ⏳ **Documentation:** Cập nhật API docs
5. ⏳ **Deployment:** Triển khai lên production

---

---

## 11. Tóm Tắt Yêu Cầu Variants

### 11.1 Vấn Đề Hiện Tại

- **Hiện tại:** Flash Sale chỉ set ở cấp product, không phân biệt variants
- **Vấn đề:** Khi sản phẩm có 10 variants, chỉ hiển thị 1 variant đầu tiên
- **Yêu cầu:** Cần hiển thị đủ tất cả variants để set giá Flash Sale cho từng variant riêng biệt

### 11.2 Giải Pháp Đề Xuất

1. **Database:**
   - Thêm cột `variant_id` (nullable) vào bảng `productsales`
   - Tương thích ngược: dữ liệu cũ vẫn hoạt động (variant_id = NULL)

2. **Admin Panel:**
   - Khi chọn sản phẩm có variants (`has_variants = 1`), hiển thị tất cả variants
   - Mỗi variant có input riêng cho `price_sale` và `number`
   - Lưu ProductSale với `variant_id` tương ứng

3. **Logic Tính Giá:**
   - Ưu tiên kiểm tra Flash Sale theo `variant_id` trước
   - Nếu không có variant_id, fallback về `product_id` (tương thích ngược)

4. **API:**
   - Response bao gồm thông tin variants và Flash Sale info cho từng variant
   - Admin API hỗ trợ tạo/sửa Flash Sale cho từng variant

### 11.3 Ví Dụ Cụ Thể

**Sản phẩm A có 10 variants:**
- Variant 1: 500ml - Giá gốc 200,000đ → Flash Sale: 150,000đ (số lượng: 100)
- Variant 2: 1000ml - Giá gốc 350,000đ → Flash Sale: 280,000đ (số lượng: 50)
- Variant 3: 2000ml - Giá gốc 600,000đ → Flash Sale: 450,000đ (số lượng: 30)
- ... (7 variants khác)

**Khi add/edit Flash Sale:**
- Hiển thị đủ 10 variants
- Mỗi variant có input riêng để set giá và số lượng
- Lưu vào `productsales` với `variant_id` tương ứng

**Khi hiển thị trên Mobile App:**
- API trả về đầy đủ thông tin Flash Sale cho từng variant
- App hiển thị giá Flash Sale chính xác theo variant được chọn

---

**Ngày tạo:** 2024-01-XX  
**Người phân tích:** AI Assistant  
**Trạng thái:** Đã bổ sung hỗ trợ Variants - Đang chờ phê duyệt
