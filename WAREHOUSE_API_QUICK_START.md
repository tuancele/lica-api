# Warehouse API V1 - Quick Start Guide

## ✅ Đã Hoàn Thành

### 1. Routes Đã Đăng Ký
Tất cả 21 routes đã được đăng ký thành công:
- ✅ 2 Inventory endpoints
- ✅ 6 Import Receipts endpoints (CRUD + print)
- ✅ 6 Export Receipts endpoints (CRUD + print)
- ✅ 4 Supporting endpoints
- ✅ 3 Statistics endpoints

**Kiểm tra routes:**
```bash
php artisan route:list --path=admin/api/v1/warehouse
```

### 2. Service Layer
- ✅ `WarehouseServiceInterface` - Interface đã được định nghĩa
- ✅ `WarehouseService` - Implementation đã hoàn thành
- ✅ Service đã được bind trong `AppServiceProvider`

### 3. Request Validation
- ✅ `StoreImportReceiptRequest`
- ✅ `UpdateImportReceiptRequest`
- ✅ `StoreExportReceiptRequest`
- ✅ `UpdateExportReceiptRequest`

### 4. Resource Classes
- ✅ `InventoryResource`
- ✅ `ImportReceiptResource` & `ImportReceiptCollection`
- ✅ `ExportReceiptResource` & `ExportReceiptCollection`
- ✅ `ReceiptItemResource`

### 5. Controller
- ✅ `WarehouseController` với đầy đủ 21 methods

---

## 🚀 Cách Sử Dụng

### Option 1: Test với Postman

1. **Import Collection:**
   - Tạo Collection mới: "Warehouse API V1"
   - Base URL: `https://lica.test/admin/api/v1/warehouse`

2. **Setup Authentication:**
   - Nếu dùng Sanctum/Passport: Thêm Bearer Token vào Headers
   - Nếu dùng Session: Đăng nhập qua web trước, sau đó dùng cookie

3. **Test các endpoints:**
   - Xem file `WAREHOUSE_API_TEST_GUIDE.md` để biết chi tiết từng endpoint

### Option 2: Test với cURL

#### Ví dụ: Lấy danh sách tồn kho
```bash
curl -X GET "https://lica.test/admin/api/v1/warehouse/inventory?limit=10" \
  -H "Accept: application/json" \
  -H "Cookie: laravel_session=YOUR_SESSION_COOKIE"
```

#### Ví dụ: Tạo phiếu nhập hàng
```bash
curl -X POST "https://lica.test/admin/api/v1/warehouse/import-receipts" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Cookie: laravel_session=YOUR_SESSION_COOKIE" \
  -d '{
    "code": "NH-TEST-001",
    "subject": "Nhập hàng test",
    "items": [
      {
        "variant_id": 1,
        "price": 100000,
        "quantity": 20
      }
    ]
  }'
```

### Option 3: Test từ Frontend (JavaScript)

```javascript
// Lấy danh sách tồn kho
fetch('/admin/api/v1/warehouse/inventory?limit=10', {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  credentials: 'same-origin'
})
.then(response => response.json())
.then(data => {
  console.log('Inventory:', data);
});

// Tạo phiếu nhập hàng
fetch('/admin/api/v1/warehouse/import-receipts', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  credentials: 'same-origin',
  body: JSON.stringify({
    code: 'NH-TEST-001',
    subject: 'Nhập hàng test',
    items: [
      {
        variant_id: 1,
        price: 100000,
        quantity: 20
      }
    ]
  })
})
.then(response => response.json())
.then(data => {
  console.log('Created:', data);
});
```

---

## 📋 Checklist Test Nhanh

### Basic Tests (Không cần dữ liệu)
- [ ] `GET /admin/api/v1/warehouse/inventory` - Lấy danh sách tồn kho
- [ ] `GET /admin/api/v1/warehouse/statistics/summary` - Tổng hợp thống kê
- [ ] `GET /admin/api/v1/warehouse/products/search?q=test` - Tìm kiếm sản phẩm

### Import Receipts Tests (Cần có variant_id hợp lệ)
- [ ] `GET /admin/api/v1/warehouse/import-receipts` - Danh sách phiếu nhập
- [ ] `POST /admin/api/v1/warehouse/import-receipts` - Tạo phiếu nhập mới
- [ ] `GET /admin/api/v1/warehouse/import-receipts/{id}` - Chi tiết phiếu nhập
- [ ] `PUT /admin/api/v1/warehouse/import-receipts/{id}` - Cập nhật phiếu nhập
- [ ] `DELETE /admin/api/v1/warehouse/import-receipts/{id}` - Xóa phiếu nhập

### Export Receipts Tests (Cần có tồn kho)
- [ ] `GET /admin/api/v1/warehouse/export-receipts` - Danh sách phiếu xuất
- [ ] `POST /admin/api/v1/warehouse/export-receipts` - Tạo phiếu xuất (kiểm tra tồn kho)
- [ ] `GET /admin/api/v1/warehouse/export-receipts/{id}` - Chi tiết phiếu xuất

### Supporting Endpoints
- [ ] `GET /admin/api/v1/warehouse/products/{productId}/variants` - Lấy phân loại
- [ ] `GET /admin/api/v1/warehouse/variants/{variantId}/stock` - Lấy tồn kho
- [ ] `GET /admin/api/v1/warehouse/variants/{variantId}/price` - Lấy giá đề xuất

---

## 🔍 Kiểm Tra Lỗi Thường Gặp

### 1. Lỗi 401 Unauthorized
**Nguyên nhân:** Chưa đăng nhập hoặc token không hợp lệ

**Giải pháp:**
- Đăng nhập qua web trước: `https://lica.test/admin/login`
- Hoặc tạo Personal Access Token nếu dùng API authentication

### 2. Lỗi 404 Not Found
**Nguyên nhân:** Route chưa được đăng ký hoặc URL sai

**Giải pháp:**
```bash
# Kiểm tra routes
php artisan route:list --path=admin/api/v1/warehouse

# Clear route cache (nếu có)
php artisan route:clear
```

### 3. Lỗi 422 Validation Error
**Nguyên nhân:** Dữ liệu đầu vào không hợp lệ

**Giải pháp:**
- Kiểm tra format JSON
- Kiểm tra các trường bắt buộc
- Xem chi tiết lỗi trong response `errors`

### 4. Lỗi 500 Server Error
**Nguyên nhân:** Lỗi server hoặc database

**Giải pháp:**
```bash
# Xem log
tail -f storage/logs/laravel.log

# Kiểm tra database connection
php artisan migrate:status
```

---

## 📚 Tài Liệu Tham Khảo

1. **Chi tiết API:** `WAREHOUSE_API_CONVERSION_PLAN.md`
2. **Hướng dẫn test:** `WAREHOUSE_API_TEST_GUIDE.md`
3. **API Documentation:** `API_ADMIN_DOCS.md` (phần Warehouse Management API)

---

## 🎯 Next Steps

1. **Test các endpoints cơ bản** với Postman hoặc curl
2. **Tích hợp vào frontend** nếu cần
3. **Monitor logs** để phát hiện lỗi
4. **Tối ưu performance** nếu cần (cache, index database)

---

**Ngày tạo:** 2026-01-20  
**Trạng thái:** ✅ Sẵn sàng sử dụng
