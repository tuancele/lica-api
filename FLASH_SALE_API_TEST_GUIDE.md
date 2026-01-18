# Flash Sale API Test Guide

## ✅ Đã Hoàn Thành

### 1. Migration
- ✅ Đã chạy migration thành công
- ✅ Cột `variant_id` đã được thêm vào bảng `productsales`
- ✅ Index đã được tạo cho performance

### 2. Routes Đã Đăng Ký

**Public API V1:**
- ✅ `GET /api/v1/flash-sales/active`
- ✅ `GET /api/v1/flash-sales/{id}/products`

**Admin API:**
- ✅ `GET /admin/api/flash-sales`
- ✅ `POST /admin/api/flash-sales`
- ✅ `GET /admin/api/flash-sales/{id}`
- ✅ `PUT /admin/api/flash-sales/{id}`
- ✅ `DELETE /admin/api/flash-sales/{id}`
- ✅ `POST /admin/api/flash-sales/{id}/status`
- ✅ `POST /admin/api/flash-sales/search-products`

### 3. Code Quality
- ✅ Không có lỗi linter
- ✅ Tất cả Models, Resources, Controllers đã được tạo

---

## 🧪 Hướng Dẫn Test

### Test Public API (Không cần authentication)

#### 1. Test GET /api/v1/flash-sales/active

```bash
curl -X GET "http://lica.test/api/v1/flash-sales/active?limit=10" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Flash Sale Tháng 1",
      "start": "2024-01-15T00:00:00.000000Z",
      "end": "2024-01-20T23:59:59.000000Z",
      "is_active": true,
      "countdown_seconds": 432000
    }
  ],
  "count": 1
}
```

#### 2. Test GET /api/v1/flash-sales/{id}/products

```bash
curl -X GET "http://lica.test/api/v1/flash-sales/1/products?page=1&limit=20" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "flash_sale": {...},
    "products": [
      {
        "id": 10,
        "name": "Sản phẩm",
        "has_variants": true,
        "variants": [...],
        "flash_sale_info": {...}
      }
    ],
    "pagination": {...}
  }
}
```

---

### Test Admin API (Cần authentication)

#### 1. Test GET /admin/api/flash-sales

```bash
curl -X GET "http://lica.test/admin/api/flash-sales?page=1&limit=10" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 2. Test POST /admin/api/flash-sales (Create)

```bash
curl -X POST "http://lica.test/admin/api/flash-sales" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "start": "2024-01-15 00:00:00",
    "end": "2024-01-20 23:59:59",
    "status": "1",
    "products": [
      {
        "product_id": 10,
        "variant_id": 5,
        "price_sale": 150000,
        "number": 100
      }
    ]
  }'
```

#### 3. Test POST /admin/api/flash-sales/search-products

```bash
curl -X POST "http://lica.test/admin/api/flash-sales/search-products" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "keyword": "sản phẩm",
    "page": 1,
    "limit": 50
  }'
```

---

## 🔍 Kiểm Tra Database

### Kiểm tra cột variant_id đã được thêm:

```sql
DESCRIBE productsales;
```

Bạn sẽ thấy cột `variant_id` với:
- Type: `int(11) unsigned`
- Null: `YES`
- Key: `MUL` (có index)

### Kiểm tra index:

```sql
SHOW INDEX FROM productsales WHERE Key_name = 'productsales_flashsale_variant_index';
```

---

## 📝 Test Cases Quan Trọng

### 1. Test với sản phẩm có variants
- Tạo Flash Sale với sản phẩm có `has_variants = 1`
- Set giá Flash Sale cho từng variant riêng biệt
- Verify API trả về đúng thông tin cho từng variant

### 2. Test với sản phẩm không có variants
- Tạo Flash Sale với sản phẩm không có variants
- Set giá Flash Sale ở cấp product (variant_id = null)
- Verify API trả về đúng thông tin

### 3. Test tính giá
- Verify `PriceCalculationService` tính giá đúng theo thứ tự ưu tiên:
  1. Flash Sale (variant_id)
  2. Flash Sale (product_id)
  3. Marketing Campaign
  4. Variant Sale Price
  5. Normal Price

### 4. Test countdown
- Verify `countdown_seconds` được tính đúng
- Verify countdown = 0 khi Flash Sale đã kết thúc

---

## 🚀 Deployment Checklist

- [x] Migration đã chạy thành công
- [x] Routes đã được đăng ký
- [x] Không có lỗi linter
- [ ] Test Public API endpoints
- [ ] Test Admin API endpoints (với authentication)
- [ ] Test với dữ liệu thực tế
- [ ] Verify variants được hiển thị đúng
- [ ] Verify tính giá hoạt động đúng
- [ ] Update Admin Panel Views (nếu cần)

---

## 📚 Documentation

- **API V1 Docs:** `API_V1_DOCS.md`
- **Admin API Docs:** `API_ADMIN_DOCS.md`
- **Analysis:** `FLASH_SALE_API_ANALYSIS.md`

---

**Ngày tạo:** 2025-01-18  
**Trạng thái:** Sẵn sàng để test và deploy
