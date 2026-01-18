# Flash Sale Debug Guide

## Cách kiểm tra tại sao Flash Sale không hiển thị

### Bước 1: Kiểm tra API Response

Mở browser và vào:
```
https://lica.test/api/products/flash-sale
```

**Response mong đợi:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Sản phẩm",
      "price_sale": 250000,
      "flash_sale": {
        "number": 100,
        "buy": 0,
        "remaining": 100
      }
    }
  ],
  "flash_sale": {
    "id": 20,
    "end_timestamp": 1738321620,
    "end_date": "2026-01-31 12:27:00"
  },
  "count": 1
}
```

**Nếu `data` rỗng hoặc `count = 0`:**
- Kiểm tra có Flash Sale active không
- Kiểm tra có ProductSale với `buy < number` không

### Bước 2: Kiểm tra Browser Console

1. Mở trang chủ: `https://lica.test`
2. Mở Developer Tools (F12) → Console tab
3. Tìm các log:
   - `Flash Sale API Response:` - Xem response từ API
   - `Has data:` - Xem có dữ liệu không
   - `Showing Flash Sale section with X products` - Xem có hiển thị không
   - `No Flash Sale products found` - Nếu không có sản phẩm

### Bước 3: Kiểm tra Network Tab

1. Mở Developer Tools (F12) → Network tab
2. Reload trang
3. Tìm request: `/api/products/flash-sale`
4. Kiểm tra:
   - Status code: Phải là 200
   - Response: Xem có dữ liệu không

### Bước 4: Kiểm tra Laravel Log

```bash
tail -f storage/logs/laravel.log | grep "Flash Sale"
```

**Log mong đợi:**
```
[INFO] Flash Sale API Check: {"current_time":...,"flash_found":20}
[INFO] Flash Sale Products Check: {"flash_id":20,"product_sales_count":5}
[INFO] Flash Sale API Response: {"flash_id":20,"products_count":5,"has_data":true}
```

### Bước 5: Kiểm tra Database

**Kiểm tra Flash Sale active:**
```sql
SELECT * FROM flashsales 
WHERE status = 1 
AND start <= UNIX_TIMESTAMP(NOW()) 
AND end >= UNIX_TIMESTAMP(NOW());
```

**Kiểm tra ProductSale available:**
```sql
SELECT * FROM productsales 
WHERE flashsale_id = 20 
AND buy < number;
```

**Kiểm tra Product status:**
```sql
SELECT id, name, status, stock 
FROM posts 
WHERE id IN (SELECT product_id FROM productsales WHERE flashsale_id = 20)
AND status = 1 
AND stock = 1;
```

### Bước 6: Kiểm tra JavaScript

Trong Console, chạy:
```javascript
// Test API
$.get('/api/products/flash-sale', function(response) {
    console.log('API Response:', response);
    console.log('Has data:', response.data && response.data.length > 0);
});

// Test section visibility
const section = $('#flash-sale-section');
console.log('Section exists:', section.length > 0);
console.log('Section visible:', section.is(':visible'));
console.log('Section display:', section.css('display'));
```

### Các vấn đề thường gặp

1. **Flash Sale không active:**
   - Kiểm tra `status = 1`
   - Kiểm tra `start <= now` và `end >= now`

2. **Không có sản phẩm available:**
   - Tất cả ProductSale có `buy >= number`
   - Sản phẩm có `status = 0` hoặc `stock = 0`

3. **JavaScript không load:**
   - Kiểm tra Console có error không
   - Kiểm tra jQuery có load không
   - Kiểm tra `loadFlashSaleProducts()` có được gọi không

4. **Section bị ẩn:**
   - Kiểm tra CSS: `section.css('display')`
   - Kiểm tra `hiddenContent` có visible không

### Quick Fix

Nếu vẫn không hiển thị, thử:

1. Clear cache:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

2. Hard refresh browser: `Ctrl + F5`

3. Kiểm tra lại Flash Sale trong Admin:
   - Vào `/admin/flashsale`
   - Kiểm tra Flash Sale có active không
   - Kiểm tra có sản phẩm trong Flash Sale không
