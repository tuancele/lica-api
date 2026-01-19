# Hướng Dẫn Test Warehouse API V1

## 📋 Tổng Quan

Tài liệu này hướng dẫn cách test các API endpoints của Warehouse Management API V1.

**Base URL:** `https://lica.test/admin/api/v1/warehouse`  
**Authentication:** Tất cả endpoints yêu cầu authentication token (`auth:api` middleware)

---

## 🔐 Authentication

Trước khi test, bạn cần có authentication token. Có thể sử dụng:
- Personal Access Token (Laravel Sanctum/Passport)
- Session-based authentication (nếu đã đăng nhập qua web)

### Lấy Token (nếu dùng Sanctum/Passport):
```bash
POST /api/login
{
  "email": "admin@example.com",
  "password": "password"
}
```

---

## 🧪 Test Cases

### 1. Inventory Management

#### 1.1. GET /admin/api/v1/warehouse/inventory
**Mục đích:** Lấy danh sách tồn kho

**Request:**
```bash
curl -X GET "https://lica.test/admin/api/v1/warehouse/inventory?limit=10&page=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Response mẫu:**
```json
{
  "success": true,
  "data": [
    {
      "variant_id": 1,
      "variant_sku": "SKU-001",
      "variant_option": "500ml",
      "product_id": 10,
      "product_name": "Sản phẩm A",
      "import_total": 1000,
      "export_total": 750,
      "current_stock": 250
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 150,
    "last_page": 15
  }
}
```

#### 1.2. GET /admin/api/v1/warehouse/inventory/{variantId}
**Mục đích:** Lấy chi tiết tồn kho của một variant

**Request:**
```bash
curl -X GET "https://lica.test/admin/api/v1/warehouse/inventory/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

### 2. Import Receipts Management

#### 2.1. GET /admin/api/v1/warehouse/import-receipts
**Mục đích:** Lấy danh sách phiếu nhập hàng

**Request:**
```bash
curl -X GET "https://lica.test/admin/api/v1/warehouse/import-receipts?limit=10&page=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### 2.2. POST /admin/api/v1/warehouse/import-receipts
**Mục đích:** Tạo phiếu nhập hàng mới

**Request:**
```bash
curl -X POST "https://lica.test/admin/api/v1/warehouse/import-receipts" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "code": "NH-TEST-001",
    "subject": "Nhập hàng test",
    "content": "Ghi chú nhập hàng",
    "vat_invoice": "VAT-2026-001",
    "items": [
      {
        "variant_id": 1,
        "price": 100000,
        "quantity": 20
      }
    ]
  }'
```

**Response thành công (201):**
```json
{
  "success": true,
  "message": "Tạo phiếu nhập hàng thành công",
  "data": {
    "id": 100,
    "code": "NH-TEST-001",
    "receipt_code": "PH-20260120-000100",
    "subject": "Nhập hàng test",
    "total_items": 1,
    "total_quantity": 20,
    "total_value": 2000000
  }
}
```

**Response lỗi validation (422):**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "code": ["Mã đơn hàng đã tồn tại"],
    "items.0.variant_id": ["Phân loại không hợp lệ."]
  }
}
```

#### 2.3. GET /admin/api/v1/warehouse/import-receipts/{id}
**Mục đích:** Lấy chi tiết phiếu nhập hàng

**Request:**
```bash
curl -X GET "https://lica.test/admin/api/v1/warehouse/import-receipts/100" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### 2.4. PUT /admin/api/v1/warehouse/import-receipts/{id}
**Mục đích:** Cập nhật phiếu nhập hàng

**Request:**
```bash
curl -X PUT "https://lica.test/admin/api/v1/warehouse/import-receipts/100" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "subject": "Nhập hàng test (Đã cập nhật)",
    "items": [
      {
        "variant_id": 1,
        "price": 100000,
        "quantity": 25
      }
    ]
  }'
```

#### 2.5. DELETE /admin/api/v1/warehouse/import-receipts/{id}
**Mục đích:** Xóa phiếu nhập hàng

**Request:**
```bash
curl -X DELETE "https://lica.test/admin/api/v1/warehouse/import-receipts/100" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### 2.6. GET /admin/api/v1/warehouse/import-receipts/{id}/print
**Mục đích:** Lấy dữ liệu in phiếu nhập hàng

**Request:**
```bash
curl -X GET "https://lica.test/admin/api/v1/warehouse/import-receipts/100/print" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

### 3. Export Receipts Management

#### 3.1. POST /admin/api/v1/warehouse/export-receipts
**Mục đích:** Tạo phiếu xuất hàng mới

**Request:**
```bash
curl -X POST "https://lica.test/admin/api/v1/warehouse/export-receipts" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "code": "PX-TEST-001",
    "subject": "Xuất hàng test",
    "content": "Ghi chú xuất hàng",
    "items": [
      {
        "variant_id": 1,
        "price": 120000,
        "quantity": 10
      }
    ]
  }'
```

**Response thành công (201):**
```json
{
  "success": true,
  "message": "Tạo phiếu xuất hàng thành công",
  "data": {
    "id": 200,
    "code": "PX-TEST-001",
    "receipt_code": "PX-20260120-000200"
  }
}
```

**Response lỗi thiếu tồn kho (422):**
```json
{
  "success": false,
  "message": "Không đủ tồn kho để xuất hàng",
  "errors": {
    "items.0.quantity": [
      "Số lượng vượt quá tồn kho. Tồn kho hiện tại: 5"
    ]
  }
}
```

---

### 4. Supporting Endpoints

#### 4.1. GET /admin/api/v1/warehouse/products/search
**Mục đích:** Tìm kiếm sản phẩm

**Request:**
```bash
curl -X GET "https://lica.test/admin/api/v1/warehouse/products/search?q=sản phẩm&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "Sản phẩm A",
      "slug": "san-pham-a",
      "image": "https://example.com/image.jpg"
    }
  ]
}
```

#### 4.2. GET /admin/api/v1/warehouse/products/{productId}/variants
**Mục đích:** Lấy danh sách phân loại của sản phẩm

**Request:**
```bash
curl -X GET "https://lica.test/admin/api/v1/warehouse/products/5/variants" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### 4.3. GET /admin/api/v1/warehouse/variants/{variantId}/stock
**Mục đích:** Lấy thông tin tồn kho của variant

**Request:**
```bash
curl -X GET "https://lica.test/admin/api/v1/warehouse/variants/1/stock" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### 4.4. GET /admin/api/v1/warehouse/variants/{variantId}/price
**Mục đích:** Lấy giá đề xuất

**Request:**
```bash
curl -X GET "https://lica.test/admin/api/v1/warehouse/variants/1/price?type=export" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

### 5. Statistics

#### 5.1. GET /admin/api/v1/warehouse/statistics/quantity
**Mục đích:** Thống kê số lượng tồn kho

**Request:**
```bash
curl -X GET "https://lica.test/admin/api/v1/warehouse/statistics/quantity?limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### 5.2. GET /admin/api/v1/warehouse/statistics/revenue
**Mục đích:** Thống kê doanh thu

**Request:**
```bash
curl -X GET "https://lica.test/admin/api/v1/warehouse/statistics/revenue?limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### 5.3. GET /admin/api/v1/warehouse/statistics/summary
**Mục đích:** Tổng hợp thống kê

**Request:**
```bash
curl -X GET "https://lica.test/admin/api/v1/warehouse/statistics/summary?date_from=2026-01-01&date_to=2026-01-31" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_products": 150,
    "total_variants": 300,
    "total_import_receipts": 50,
    "total_export_receipts": 30,
    "total_import_value": 1000000000,
    "total_export_value": 900000000,
    "total_profit": 100000000,
    "current_stock_value": 250000000,
    "low_stock_items": 15,
    "out_of_stock_items": 5
  }
}
```

---

## 🧪 Test Script (PHP)

Tạo file `test_warehouse_api.php` để test tự động:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$baseUrl = 'https://lica.test/admin/api/v1/warehouse';
$token = 'YOUR_TOKEN_HERE'; // Thay bằng token thực tế

function makeRequest($method, $url, $data = null, $token = null) {
    $ch = curl_init();
    
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true),
    ];
}

// Test 1: Get Inventory
echo "Test 1: Get Inventory\n";
$result = makeRequest('GET', $baseUrl . '/inventory?limit=5', null, $token);
echo "Status: " . $result['code'] . "\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 2: Search Products
echo "Test 2: Search Products\n";
$result = makeRequest('GET', $baseUrl . '/products/search?q=test', null, $token);
echo "Status: " . $result['code'] . "\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test 3: Get Summary Statistics
echo "Test 3: Get Summary Statistics\n";
$result = makeRequest('GET', $baseUrl . '/statistics/summary', null, $token);
echo "Status: " . $result['code'] . "\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
```

---

## 📝 Postman Collection

Có thể import các endpoints vào Postman:

1. Tạo Collection mới: "Warehouse API V1"
2. Thêm Environment variable:
   - `base_url`: `https://lica.test/admin/api/v1/warehouse`
   - `token`: `YOUR_TOKEN`
3. Thêm các requests theo các endpoints trên

---

## ✅ Checklist Test

- [ ] Test GET /inventory - Lấy danh sách tồn kho
- [ ] Test GET /inventory/{variantId} - Chi tiết tồn kho
- [ ] Test GET /import-receipts - Danh sách phiếu nhập
- [ ] Test POST /import-receipts - Tạo phiếu nhập
- [ ] Test GET /import-receipts/{id} - Chi tiết phiếu nhập
- [ ] Test PUT /import-receipts/{id} - Cập nhật phiếu nhập
- [ ] Test DELETE /import-receipts/{id} - Xóa phiếu nhập
- [ ] Test GET /import-receipts/{id}/print - In phiếu nhập
- [ ] Test GET /export-receipts - Danh sách phiếu xuất
- [ ] Test POST /export-receipts - Tạo phiếu xuất (kiểm tra tồn kho)
- [ ] Test GET /export-receipts/{id} - Chi tiết phiếu xuất
- [ ] Test PUT /export-receipts/{id} - Cập nhật phiếu xuất
- [ ] Test DELETE /export-receipts/{id} - Xóa phiếu xuất
- [ ] Test GET /products/search - Tìm kiếm sản phẩm
- [ ] Test GET /products/{productId}/variants - Lấy phân loại
- [ ] Test GET /variants/{variantId}/stock - Lấy tồn kho
- [ ] Test GET /variants/{variantId}/price - Lấy giá đề xuất
- [ ] Test GET /statistics/quantity - Thống kê số lượng
- [ ] Test GET /statistics/revenue - Thống kê doanh thu
- [ ] Test GET /statistics/summary - Tổng hợp thống kê

---

## 🐛 Troubleshooting

### Lỗi 401 Unauthorized
- Kiểm tra token có hợp lệ không
- Kiểm tra middleware `auth:api` đã được cấu hình đúng chưa

### Lỗi 404 Not Found
- Kiểm tra route đã được đăng ký: `php artisan route:list --path=admin/api/v1/warehouse`
- Kiểm tra URL có đúng không

### Lỗi 422 Validation Error
- Kiểm tra dữ liệu đầu vào có đúng format không
- Xem chi tiết lỗi trong response `errors`

### Lỗi 500 Server Error
- Kiểm tra log: `storage/logs/laravel.log`
- Kiểm tra database connection
- Kiểm tra helper functions đã được load chưa

---

**Ngày tạo:** 2026-01-20  
**Phiên bản:** 1.0
