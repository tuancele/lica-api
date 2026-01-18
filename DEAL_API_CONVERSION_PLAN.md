# Kế Hoạch Nâng Cấp Module Deal Sốc Sang RESTful API V1

## ⚠️ Cập Nhật Quan Trọng: Hỗ Trợ Variants (Phân Loại Sản Phẩm)

**Yêu cầu mới:** Khi tạo Deal, nếu sản phẩm có phân loại (variants), hệ thống phải hiển thị và xử lý cả variants cho:
- **Sản phẩm chính** (products trong Deal)
- **Sản phẩm mua kèm** (sale_products trong Deal)

**Thay đổi chính:**
- Thêm cột `variant_id` vào bảng `deal_products` và `deal_sales`
- Validation: Sản phẩm có `has_variants = 1` bắt buộc phải chỉ định `variant_id`
- Kiểm tra xung đột dựa trên cặp `(product_id, variant_id)` thay vì chỉ `product_id`
- Response JSON bao gồm thông tin variant đầy đủ

---

## 📋 Mục Lục
1. [Phân Tích Chuyên Sâu (Deep Dive Analysis)](#phân-tích-chuyên-sâu)
2. [Cấu Trúc Database](#cấu-trúc-database)
3. [Logic Nghiệp Vụ Hiện Tại](#logic-nghiệp-vụ-hiện-tại)
4. [Kế Hoạch Xây Dựng API](#kế-hoạch-xây-dựng-api)
5. [Cấu Trúc JSON & Luồng Xử Lý](#cấu-trúc-json--luồng-xử-lý)
6. [Chi Tiết Implementation](#chi-tiết-implementation)

---

## 🔍 Phân Tích Chuyên Sâu

### 1. Cấu Trúc Module Hiện Tại

**Vị trí:** `app/Modules/Deal/`

**Các thành phần chính:**
- **Models:**
  - `Deal.php` - Model chính quản lý Deal
  - `ProductDeal.php` - Model quản lý sản phẩm chính trong Deal
  - `SaleDeal.php` - Model quản lý sản phẩm khuyến mãi trong Deal

- **Controller:** `DealController.php` - Xử lý các request từ web interface
- **Routes:** `routes.php` - Định nghĩa các route web (giữ nguyên)
- **Views:** Các file Blade template cho giao diện admin

### 2. Mối Quan Hệ Giữa Các Bảng

```
deals (Bảng chính)
├── id
├── name (Tên Deal)
├── start (Thời gian bắt đầu - Unix timestamp)
├── end (Thời gian kết thúc - Unix timestamp)
├── status (0=Ngừng, 1=Kích hoạt)
├── limited (Giới hạn số lượng sản phẩm mua kèm)
├── user_id (Người tạo)
└── created_at, updated_at

deal_products (Sản phẩm chính áp dụng Deal)
├── id
├── deal_id (FK -> deals.id)
├── product_id (FK -> posts.id)
├── status (0=Ngừng, 1=Kích hoạt)
└── created_at

deal_sales (Sản phẩm khuyến mãi trong Deal)
├── id
├── deal_id (FK -> deals.id)
├── product_id (FK -> posts.id)
├── price (Giá khuyến mãi)
├── qty (Số lượng khuyến mãi)
├── status (0=Ngừng, 1=Kích hoạt)
└── created_at
```

**Logic ràng buộc:**
- Một Deal có nhiều sản phẩm chính (ProductDeal)
- Một Deal có nhiều sản phẩm khuyến mãi (SaleDeal)
- Khi khách hàng mua sản phẩm chính trong giỏ hàng, hệ thống sẽ đề xuất các sản phẩm khuyến mãi từ Deal
- Mỗi Deal có giới hạn số lượng sản phẩm mua kèm (`limited`)

---

## 🗄️ Cấu Trúc Database

### Bảng `deals`
```sql
- id: INT PRIMARY KEY AUTO_INCREMENT
- name: VARCHAR(255) - Tên Deal
- start: INT - Thời gian bắt đầu (Unix timestamp)
- end: INT - Thời gian kết thúc (Unix timestamp)
- status: TINYINT(1) - Trạng thái (0=Ngừng, 1=Kích hoạt)
- limited: INT - Giới hạn số lượng sản phẩm mua kèm
- user_id: INT - ID người tạo
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

### Bảng `deal_products`
```sql
- id: INT PRIMARY KEY AUTO_INCREMENT
- deal_id: INT - FK đến deals.id
- product_id: INT - FK đến posts.id (sản phẩm chính)
- variant_id: INT NULL - FK đến variants.id (phân loại sản phẩm, NULL nếu không có phân loại)
- status: TINYINT(1) - Trạng thái (0=Ngừng, 1=Kích hoạt)
- created_at: TIMESTAMP
```

**Lưu ý:** Cần thêm cột `variant_id` vào bảng `deal_products` nếu chưa có.

### Bảng `deal_sales`
```sql
- id: INT PRIMARY KEY AUTO_INCREMENT
- deal_id: INT - FK đến deals.id
- product_id: INT - FK đến posts.id (sản phẩm khuyến mãi)
- variant_id: INT NULL - FK đến variants.id (phân loại sản phẩm, NULL nếu không có phân loại)
- price: DECIMAL(10,2) - Giá khuyến mãi
- qty: INT - Số lượng khuyến mãi
- status: TINYINT(1) - Trạng thái (0=Ngừng, 1=Kích hoạt)
- created_at: TIMESTAMP
```

**Lưu ý:** Cần thêm cột `variant_id` vào bảng `deal_sales` nếu chưa có.

---

## 💼 Logic Nghiệp Vụ Hiện Tại

### 1. Kiểm Tra Deal Đang Hoạt Động

**Điều kiện để Deal được coi là đang hoạt động:**
```php
$now = strtotime(date('Y-m-d H:i:s'));
$deal->status == '1' 
&& $deal->start <= $now 
&& $deal->end >= $now
```

**Logic trong CartService:**
- Khi khách hàng thêm sản phẩm chính vào giỏ hàng
- Hệ thống tìm các Deal đang hoạt động có chứa sản phẩm đó
- Trả về danh sách sản phẩm khuyến mãi kèm theo với giá đã giảm

### 2. Tính Toán Giá Trị Khuyến Mại

**Công thức tính số tiền tiết kiệm:**
```
Số tiền tiết kiệm = (Giá gốc sản phẩm - Giá khuyến mãi Deal) × Số lượng
```

**Ví dụ:**
- Sản phẩm khuyến mãi có giá gốc: 200,000 VNĐ
- Giá khuyến mãi trong Deal: 150,000 VNĐ
- Số lượng mua: 2
- **Số tiền tiết kiệm:** (200,000 - 150,000) × 2 = 100,000 VNĐ

**Dữ liệu trả về cho Mobile App:**
```json
{
  "id": 1,
  "name": "Deal sốc tháng 1",
  "limited": 3,
  "sale_deals": [
    {
      "id": 10,
      "product_id": 5,
      "product_name": "Sản phẩm khuyến mãi",
      "product_image": "https://...",
      "variant_id": 12,
      "price": 150000,           // Giá khuyến mãi
      "original_price": 200000    // Giá gốc
    }
  ]
}
```

### 3. Ràng Buộc Sản Phẩm

**Quy tắc:**
- Một sản phẩm chính (hoặc variant của sản phẩm) chỉ có thể thuộc về một Deal đang hoạt động tại một thời điểm
- Logic kiểm tra trong `DealController::showProduct()`:
  - Khi tạo/sửa Deal, hệ thống loại trừ các sản phẩm đã thuộc Deal khác đang hoạt động
  - Đảm bảo không có xung đột Deal
- **Với sản phẩm có phân loại (variants):**
  - Nếu sản phẩm có `has_variants = 1`, bắt buộc phải chỉ định `variant_id`
  - Nếu sản phẩm không có phân loại (`has_variants = 0`), `variant_id` sẽ là NULL
  - Kiểm tra xung đột dựa trên cặp `(product_id, variant_id)` thay vì chỉ `product_id`

### 4. Xử Lý Session (Hiện Tại)

**Vấn đề:** Controller hiện tại sử dụng Session để lưu tạm danh sách sản phẩm khi tạo/sửa Deal
- `ss_product_deal` - Danh sách sản phẩm chính
- `ss_sale_product` - Danh sách sản phẩm khuyến mãi

**Giải pháp API:** Loại bỏ Session, xử lý trực tiếp qua JSON request/response

---

## 🚀 Kế Hoạch Xây Dựng API

### 1. Endpoints Cần Xây Dựng

#### 1.1. GET /admin/api/deals
**Mục tiêu:** Lấy danh sách Deal với phân trang và lọc

**Query Parameters:**
- `page` (integer, optional): Trang hiện tại, mặc định 1
- `limit` (integer, optional): Số lượng mỗi trang, mặc định 10
- `status` (string, optional): Lọc theo trạng thái (0/1)
- `keyword` (string, optional): Tìm kiếm theo tên Deal

**Response:** Danh sách Deal với pagination

#### 1.2. GET /admin/api/deals/{id}
**Mục tiêu:** Lấy chi tiết Deal bao gồm:
- Thông tin Deal
- Danh sách sản phẩm chính (ProductDeal)
- Danh sách sản phẩm khuyến mãi (SaleDeal)

**Response:** Chi tiết Deal đầy đủ

#### 1.3. POST /admin/api/deals
**Mục tiêu:** Tạo mới Deal

**Request Body:** JSON chứa thông tin Deal và danh sách sản phẩm

**Xử lý:**
1. Validate dữ liệu đầu vào
2. Tạo Deal trong bảng `deals`
3. Lưu danh sách sản phẩm chính vào `deal_products`
4. Lưu danh sách sản phẩm khuyến mãi vào `deal_sales`
5. Trả về Deal vừa tạo kèm đầy đủ thông tin

#### 1.4. PUT /admin/api/deals/{id}
**Mục tiêu:** Cập nhật Deal

**Request Body:** JSON chứa thông tin cần cập nhật

**Xử lý:**
1. Validate dữ liệu
2. Cập nhật thông tin Deal
3. Xóa và tạo lại danh sách sản phẩm (đảm bảo đồng bộ)

#### 1.5. DELETE /admin/api/deals/{id}
**Mục tiêu:** Xóa Deal

**Xử lý:**
1. Xóa các bản ghi liên quan trong `deal_products`
2. Xóa các bản ghi liên quan trong `deal_sales`
3. Xóa Deal

#### 1.6. PATCH /admin/api/deals/{id}/status
**Mục tiêu:** Bật/tắt trạng thái Deal

**Request Body:** `{"status": 0}` hoặc `{"status": 1}`

---

## 📦 Cấu Trúc JSON & Luồng Xử Lý

### 1. Cấu Trúc JSON Request/Response

#### 1.1. GET /admin/api/deals - Response

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Deal sốc tháng 1",
      "start": "2024-01-01T00:00:00.000000Z",
      "end": "2024-01-31T23:59:59.000000Z",
      "start_timestamp": 1704067200,
      "end_timestamp": 1706745599,
      "status": "1",
      "status_text": "Kích hoạt",
      "limited": 3,
      "is_active": true,
      "created_by": {
        "id": 1,
        "name": "Admin User"
      },
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 25,
    "last_page": 3
  }
}
```

#### 1.2. GET /admin/api/deals/{id} - Response

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Deal sốc tháng 1",
    "start": "2024-01-01T00:00:00.000000Z",
    "end": "2024-01-31T23:59:59.000000Z",
    "start_timestamp": 1704067200,
    "end_timestamp": 1706745599,
    "status": "1",
    "status_text": "Kích hoạt",
    "limited": 3,
    "is_active": true,
    "created_by": {
      "id": 1,
      "name": "Admin User"
    },
    "products": [
      {
        "id": 10,
        "product_id": 5,
        "variant_id": 12,
        "product": {
          "id": 5,
          "name": "Sản phẩm chính A",
          "image": "https://example.com/image.jpg",
          "has_variants": true,
          "price": 300000,
          "stock": 50
        },
        "variant": {
          "id": 12,
          "sku": "SKU-001",
          "option1_value": "500ml",
          "price": 300000,
          "stock": 50
        },
        "status": "1",
        "status_text": "Kích hoạt"
      }
    ],
    "sale_products": [
      {
        "id": 20,
        "product_id": 8,
        "variant_id": 15,
        "product": {
          "id": 8,
          "name": "Sản phẩm khuyến mãi B",
          "image": "https://example.com/image2.jpg",
          "has_variants": true,
          "price": 200000,
          "stock": 30
        },
        "variant": {
          "id": 15,
          "sku": "SKU-002",
          "option1_value": "250ml",
          "price": 200000,
          "stock": 30
        },
        "deal_price": 150000,
        "original_price": 200000,
        "savings_amount": 50000,
        "qty": 2,
        "status": "1",
        "status_text": "Kích hoạt"
      }
    ],
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

#### 1.3. POST /admin/api/deals - Request

```json
{
  "name": "Deal sốc tháng 2",
  "start": "2024-02-01T00:00:00",
  "end": "2024-02-29T23:59:59",
  "status": "1",
  "limited": 3,
  "products": [
    {
      "product_id": 5,
      "variant_id": 12,
      "status": "1"
    },
    {
      "product_id": 6,
      "variant_id": null,
      "status": "1"
    }
  ],
  "sale_products": [
    {
      "product_id": 8,
      "variant_id": 15,
      "price": 150000,
      "qty": 2,
      "status": "1"
    },
    {
      "product_id": 9,
      "variant_id": null,
      "price": 120000,
      "qty": 1,
      "status": "1"
    }
  ]
}
```

#### 1.4. PUT /admin/api/deals/{id} - Request

```json
{
  "name": "Deal sốc tháng 2 (Đã cập nhật)",
  "start": "2024-02-01T00:00:00",
  "end": "2024-02-29T23:59:59",
  "status": "1",
  "limited": 5,
  "products": [
    {
      "product_id": 5,
      "variant_id": 12,
      "status": "1"
    }
  ],
  "sale_products": [
    {
      "product_id": 8,
      "variant_id": 15,
      "price": 140000,
      "qty": 3,
      "status": "1"
    }
  ]
}
```

### 2. Luồng Xử Lý Dữ Liệu

#### 2.1. Luồng Tạo Deal (POST)

```
1. Nhận Request JSON
   ↓
2. Validate dữ liệu:
   - Tên Deal không được trống
   - Thời gian bắt đầu/kết thúc hợp lệ
   - Thời gian kết thúc phải sau thời gian bắt đầu
   - Danh sách sản phẩm hợp lệ
   ↓
3. Validate dữ liệu đầu vào:
   - Kiểm tra sản phẩm có phân loại thì phải có variant_id
   - Validate variant_id thuộc về product_id
   ↓
4. Kiểm tra xung đột Deal:
   - Kiểm tra sản phẩm chính (hoặc variant) đã thuộc Deal khác đang hoạt động chưa
   - Kiểm tra dựa trên cặp (product_id, variant_id)
   ↓
5. Bắt đầu Transaction:
   ↓
6. Tạo Deal trong bảng deals:
   - name, start (convert to timestamp), end (convert to timestamp)
   - status, limited, user_id
   ↓
7. Lưu danh sách sản phẩm chính vào deal_products:
   - Lặp qua mảng products[]
   - Validate variant_id nếu sản phẩm có phân loại
   - Insert vào deal_products với deal_id, product_id, variant_id, status
   ↓
8. Lưu danh sách sản phẩm khuyến mãi vào deal_sales:
   - Lặp qua mảng sale_products[]
   - Validate variant_id nếu sản phẩm có phân loại
   - Lấy giá gốc từ variant (nếu có) hoặc product
   - Insert vào deal_sales với deal_id, product_id, variant_id, price, qty, status
   ↓
8. Commit Transaction
   ↓
9. Load lại Deal với relationships (products, sales)
   ↓
10. Format Response bằng DealResource
    ↓
11. Trả về JSON Response (201 Created)
```

#### 2.2. Luồng Cập Nhật Deal (PUT)

```
1. Nhận Request JSON + Deal ID
   ↓
2. Tìm Deal theo ID (404 nếu không tồn tại)
   ↓
3. Validate dữ liệu (tương tự POST)
   ↓
4. Kiểm tra xung đột Deal (loại trừ Deal hiện tại):
   - Kiểm tra sản phẩm chính (hoặc variant) đã thuộc Deal khác đang hoạt động chưa
   - Kiểm tra dựa trên cặp (product_id, variant_id)
   ↓
5. Bắt đầu Transaction:
   ↓
6. Cập nhật Deal trong bảng deals
   ↓
7. Xóa tất cả bản ghi cũ trong deal_products và deal_sales
   ↓
8. Tạo lại danh sách sản phẩm (giống POST)
   ↓
9. Commit Transaction
   ↓
10. Load lại Deal với relationships
    ↓
11. Format Response bằng DealResource
    ↓
12. Trả về JSON Response (200 OK)
```

#### 2.3. Luồng Tính Toán Giá Trị Khuyến Mại

```
Khi lấy chi tiết Deal (GET /admin/api/deals/{id}):

1. Load Deal với relationships:
   - products (ProductDeal) -> product (Product)
   - sales (SaleDeal) -> product (Product)
   ↓
2. Với mỗi SaleDeal:
   ↓
3. Lấy giá gốc sản phẩm:
   - Nếu có variant_id: Lấy từ Variant.price
   - Nếu không có variant_id: Lấy từ Product -> Variant đầu tiên -> price
   ↓
4. Tính số tiền tiết kiệm:
   savings_amount = (original_price - deal_price) × qty
   ↓
5. Format vào Response:
   {
     "variant_id": 15,            // Từ SaleDeal.variant_id (có thể null)
     "deal_price": 150000,        // Từ SaleDeal.price
     "original_price": 200000,     // Từ Variant.price hoặc Product.Variant.price
     "savings_amount": 50000,      // Tính toán
     "qty": 2                      // Từ SaleDeal.qty
   }
```

---

## 🛠️ Chi Tiết Implementation

### 1. File Structure

```
app/Modules/ApiAdmin/
├── Controllers/
│   └── DealController.php          (Mới)
├── Resources/
│   └── Deal/
│       ├── DealResource.php         (Mới)
│       ├── DealDetailResource.php   (Mới)
│       ├── ProductDealResource.php  (Mới)
│       └── SaleDealResource.php     (Mới)
└── routes.php                        (Cập nhật)
```

### 2. Validation Rules

#### 2.1. POST /admin/api/deals

```php
[
    'name' => 'required|string|max:255',
    'start' => 'required|date',
    'end' => 'required|date|after:start',
    'status' => 'required|in:0,1',
    'limited' => 'required|integer|min:1',
    'products' => 'array',
    'products.*.product_id' => 'required|exists:posts,id',
    'products.*.variant_id' => 'nullable|exists:variants,id',
    'products.*.status' => 'required|in:0,1',
    'sale_products' => 'array',
    'sale_products.*.product_id' => 'required|exists:posts,id',
    'sale_products.*.variant_id' => 'nullable|exists:variants,id',
    'sale_products.*.price' => 'required|numeric|min:0',
    'sale_products.*.qty' => 'required|integer|min:1',
    'sale_products.*.status' => 'required|in:0,1',
]
```

**Custom Validation Rules:**
- Nếu sản phẩm có `has_variants = 1`, thì `variant_id` bắt buộc phải có
- Nếu sản phẩm có `has_variants = 0`, thì `variant_id` phải là NULL
- `variant_id` phải thuộc về `product_id` tương ứng

#### 2.2. PUT /admin/api/deals/{id}

```php
[
    'name' => 'sometimes|required|string|max:255',
    'start' => 'sometimes|required|date',
    'end' => 'sometimes|required|date|after:start',
    'status' => 'sometimes|required|in:0,1',
    'limited' => 'sometimes|required|integer|min:1',
    'products' => 'sometimes|array',
    'products.*.product_id' => 'required|exists:posts,id',
    'products.*.variant_id' => 'nullable|exists:variants,id',
    'products.*.status' => 'required|in:0,1',
    'sale_products' => 'sometimes|array',
    'sale_products.*.product_id' => 'required|exists:posts,id',
    'sale_products.*.variant_id' => 'nullable|exists:variants,id',
    'sale_products.*.price' => 'required|numeric|min:0',
    'sale_products.*.qty' => 'required|integer|min:1',
    'sale_products.*.status' => 'required|in:0,1',
]
```

**Custom Validation Rules:** (tương tự POST)

### 3. Business Logic Cần Tái Sử Dụng

#### 3.1. Kiểm Tra Deal Đang Hoạt Động

```php
private function isDealActive(Deal $deal): bool
{
    $now = strtotime(date('Y-m-d H:i:s'));
    return $deal->status == '1' 
        && $deal->start <= $now 
        && $deal->end >= $now;
}
```

#### 3.2. Kiểm Tra Xung Đột Sản Phẩm

```php
/**
 * Kiểm tra xung đột sản phẩm/variant với Deal khác đang hoạt động
 * 
 * @param array $products Mảng chứa ['product_id' => int, 'variant_id' => int|null]
 * @param int|null $excludeDealId ID Deal cần loại trừ (khi update)
 * @return array Mảng các cặp (product_id, variant_id) bị xung đột
 */
private function checkProductConflict(array $products, ?int $excludeDealId = null): array
{
    $now = strtotime(date('Y-m-d H:i:s'));
    $conflicts = [];
    
    foreach ($products as $product) {
        $productId = $product['product_id'];
        $variantId = $product['variant_id'] ?? null;
        
        $query = ProductDeal::where('product_id', $productId)
            ->whereHas('deal', function($q) use ($now) {
                $q->where('status', '1')
                  ->where('start', '<=', $now)
                  ->where('end', '>=', $now);
            });
        
        if ($excludeDealId) {
            $query->where('deal_id', '!=', $excludeDealId);
        }
        
        // Kiểm tra variant_id
        if ($variantId !== null) {
            $query->where(function($q) use ($variantId) {
                $q->where('variant_id', $variantId)
                  ->orWhereNull('variant_id'); // Nếu Deal khác không chỉ định variant, cũng xung đột
            });
        } else {
            // Nếu không có variant_id, kiểm tra xem có Deal nào đã chỉ định variant của sản phẩm này không
            $query->whereNull('variant_id');
        }
        
        $existing = $query->first();
        if ($existing) {
            $conflicts[] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'conflict_deal_id' => $existing->deal_id
            ];
        }
    }
    
    return $conflicts;
}
```

#### 3.3. Tính Toán Giá Trị Khuyến Mại

```php
/**
 * Tính số tiền tiết kiệm
 */
private function calculateSavings(float $originalPrice, float $dealPrice, int $qty): float
{
    return ($originalPrice - $dealPrice) * $qty;
}

/**
 * Lấy giá gốc từ variant hoặc product
 * 
 * @param int $productId
 * @param int|null $variantId
 * @return float
 */
private function getOriginalPrice(int $productId, ?int $variantId = null): float
{
    if ($variantId) {
        $variant = Variant::find($variantId);
        if ($variant && $variant->product_id == $productId) {
            return (float) $variant->price;
        }
    }
    
    // Nếu không có variant_id, lấy variant đầu tiên của sản phẩm
    $product = Product::find($productId);
    if ($product) {
        $variant = $product->variant($productId);
        if ($variant) {
            return (float) $variant->price;
        }
    }
    
    return 0;
}

/**
 * Validate variant_id thuộc về product_id
 * 
 * @param int $productId
 * @param int|null $variantId
 * @return bool
 */
private function validateVariantBelongsToProduct(int $productId, ?int $variantId = null): bool
{
    if ($variantId === null) {
        return true; // NULL là hợp lệ
    }
    
    $variant = Variant::where('id', $variantId)
        ->where('product_id', $productId)
        ->first();
    
    return $variant !== null;
}
```

### 4. Error Handling

**Các trường hợp lỗi cần xử lý:**

1. **422 Validation Error:**
```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ",
  "errors": {
    "name": ["Tên Deal không được bỏ trống"],
    "end": ["Thời gian kết thúc phải sau thời gian bắt đầu"]
  }
}
```

2. **404 Not Found:**
```json
{
  "success": false,
  "message": "Deal không tồn tại"
}
```

3. **409 Conflict (Xung đột sản phẩm):**
```json
{
  "success": false,
  "message": "Một số sản phẩm đã thuộc Deal khác đang hoạt động",
  "conflicts": [
    {
      "product_id": 5,
      "variant_id": 12,
      "conflict_deal_id": 3
    }
  ]
}
```

4. **422 Validation Error (Variant không hợp lệ):**
```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ",
  "errors": {
    "products.0.variant_id": ["Phân loại không thuộc về sản phẩm này"],
    "products.1.variant_id": ["Sản phẩm có phân loại nhưng chưa chọn variant_id"]
  }
}
```

5. **500 Server Error:**
```json
{
  "success": false,
  "message": "Tạo Deal thất bại",
  "error": "Chi tiết lỗi (chỉ trong debug mode)"
}
```

---

## ✅ Checklist Implementation

- [ ] Tạo `DealController.php` trong `app/Modules/ApiAdmin/Controllers/`
- [ ] Tạo các Resource classes:
  - [ ] `DealResource.php`
  - [ ] `DealDetailResource.php`
  - [ ] `ProductDealResource.php`
  - [ ] `SaleDealResource.php`
- [ ] Đăng ký routes trong `app/Modules/ApiAdmin/routes.php`
- [ ] Implement các methods:
  - [ ] `index()` - Danh sách Deal
  - [ ] `show()` - Chi tiết Deal
  - [ ] `store()` - Tạo Deal
  - [ ] `update()` - Cập nhật Deal
  - [ ] `destroy()` - Xóa Deal
  - [ ] `updateStatus()` - Cập nhật trạng thái
- [ ] Implement helper methods:
  - [ ] `isDealActive()`
  - [ ] `checkProductConflict()` - Cập nhật để hỗ trợ variant_id
  - [ ] `calculateSavings()`
  - [ ] `getOriginalPrice()` - Lấy giá từ variant hoặc product
  - [ ] `validateVariantBelongsToProduct()` - Validate variant thuộc product
- [ ] Viết validation rules
- [ ] Xử lý transaction cho create/update
- [ ] Cập nhật `API_ADMIN_DOCS.md`
- [ ] Test các endpoints

---

## 📝 Ghi Chú Quan Trọng

1. **Giữ nguyên route web:** Không thay đổi các route web hiện tại trong `app/Modules/Deal/routes.php`
2. **Timestamp conversion:** Chuyển đổi giữa datetime string và Unix timestamp khi cần
3. **Transaction:** Sử dụng DB transaction cho create/update để đảm bảo tính nhất quán
4. **Relationship loading:** Luôn load đầy đủ relationships khi trả về chi tiết Deal
5. **Mobile App compatibility:** Đảm bảo response format phù hợp với Mobile App đang sử dụng
6. **Variant Support:** 
   - **Bắt buộc:** Nếu sản phẩm có `has_variants = 1`, phải chỉ định `variant_id`
   - **Tùy chọn:** Nếu sản phẩm không có phân loại, `variant_id` sẽ là NULL
   - **Validation:** Luôn kiểm tra `variant_id` thuộc về `product_id` tương ứng
   - **Database:** Cần thêm cột `variant_id` vào bảng `deal_products` và `deal_sales` nếu chưa có
7. **Xung đột Deal:** Kiểm tra xung đột dựa trên cặp `(product_id, variant_id)` thay vì chỉ `product_id`

---

**Ngày tạo:** 2025-01-18  
**Người phân tích:** AI Assistant  
**Trạng thái:** Kế hoạch hoàn thành, sẵn sàng implementation
