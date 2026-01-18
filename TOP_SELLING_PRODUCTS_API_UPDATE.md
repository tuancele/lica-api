# Top Selling Products API - Cập Nhật

**Ngày cập nhật:** 2025-01-18  
**Mục tiêu:** Cải thiện logic tính toán top sản phẩm bán chạy dựa trên tổng số lượng đã bán từ tất cả đơn hàng

---

## ✅ Đã Cập Nhật

### 1. Logic Tính Toán Mới

#### Trước đây:
- Chỉ tính từ đơn hàng đã hoàn thành (`ship = 2`)
- Loại trừ đơn hàng có `status = 2`

#### Hiện tại:
- Tính từ **tất cả đơn hàng** (trừ đơn hàng đã hủy `status = 4`)
- Bao gồm:
  - Đơn hàng chờ xử lý (`status = 0`)
  - Đơn hàng đã xác nhận (`status = 1`)
  - Đơn hàng đã giao hàng (`status = 2`)
  - Đơn hàng hoàn thành (`status = 3`)
- Loại trừ: Đơn hàng đã hủy (`status = 4`)

### 2. Thông Tin Mới Trong Response

#### Thêm Fields:
- `total_sold` (integer): Tổng số lượng đã bán từ tất cả đơn hàng
- `total_sold_month` (integer): Số lượng đã bán trong tháng hiện tại

### 3. Query Logic

```sql
SELECT 
    orderdetail.product_id, 
    SUM(orderdetail.qty) as total_sold
FROM orderdetail
JOIN orders ON orderdetail.order_id = orders.id
WHERE orders.status != '4'  -- Loại trừ đơn hàng đã hủy
  AND orderdetail.product_id IS NOT NULL
GROUP BY orderdetail.product_id
ORDER BY total_sold DESC
LIMIT 100
```

---

## 📊 Response Example

### Request
```
GET /api/products/top-selling?limit=10
```

### Response
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "Sản phẩm bán chạy",
      "slug": "san-pham-ban-chay",
      "image": "https://cdn.lica.vn/uploads/images/product.jpg",
      "brand_id": 5,
      "brand_name": "Thương hiệu",
      "brand_slug": "thuong-hieu",
      "price": 500000,
      "sale": 400000,
      "price_info": {
        "price": 400000,
        "original_price": 500000,
        "type": "normal",
        "label": "",
        "discount_percent": 20
      },
      "stock": 1,
      "best": 1,
      "is_new": 0,
      "total_sold": 150,
      "total_sold_month": 25
    }
  ],
  "count": 10
}
```

---

## 🔄 So Sánh Logic

### Trước đây (v1):
```php
->where('orders.ship', 2) // Chỉ đơn hàng đã hoàn thành
->where('orders.status', '!=', 2) // Loại trừ status = 2
```

### Hiện tại (v2):
```php
->where('orders.status', '!=', '4') // Loại trừ đơn hàng đã hủy
// Bao gồm tất cả đơn hàng khác (0, 1, 2, 3)
```

---

## 📈 Lợi Ích

1. **Chính xác hơn:** Tính toán dựa trên tất cả đơn hàng đã được xác nhận
2. **Cập nhật nhanh:** Bao gồm cả đơn hàng đang xử lý
3. **Thông tin đầy đủ:** Cung cấp cả tổng số lượng và số lượng trong tháng
4. **Hiển thị tốt hơn:** Frontend có thể hiển thị "Đã bán X/tháng"

---

## 🧪 Test

### Test với Browser
```
GET http://lica.test/api/products/top-selling?limit=10
```

### Test với cURL
```bash
curl -X GET "http://lica.test/api/products/top-selling?limit=10" \
  -H "Accept: application/json"
```

### Expected Response
- `success: true`
- `data`: Array of products với `total_sold` và `total_sold_month`
- Sắp xếp theo `total_sold` giảm dần

---

## 📝 Files Đã Cập Nhật

1. ✅ `app/Http/Controllers/Api/ProductController.php`
   - Method `getTopSelling()` - Cập nhật logic tính toán
   - Method `getTotalSoldThisMonth()` - Mới tạo

2. ✅ `API_ADMIN_DOCS.md`
   - Cập nhật documentation cho endpoint `/api/products/top-selling`

3. ✅ `TOP_SELLING_PRODUCTS_API_UPDATE.md`
   - Tài liệu chi tiết về cập nhật (file này)

---

## ✅ Checklist

- [x] Cập nhật logic tính toán (tất cả đơn hàng trừ đã hủy)
- [x] Thêm `total_sold` vào response
- [x] Thêm `total_sold_month` vào response
- [x] Cập nhật cache key (v2)
- [x] Cập nhật documentation
- [x] Test routes
- [x] Kiểm tra linter errors

---

## 🚀 Sẵn Sàng Sử Dụng

API đã được cập nhật và sẵn sàng sử dụng. Frontend có thể:
1. Hiển thị top sản phẩm bán chạy dựa trên tổng số lượng đã bán
2. Hiển thị số lượng đã bán trong tháng ("Đã bán X/tháng")
3. Sắp xếp chính xác theo số lượng đã bán

**Trạng thái:** ✅ Hoàn thành và sẵn sàng production

---

**最后更新:** 2025-01-18  
**维护者:** AI Assistant
