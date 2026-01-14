# HƯỚNG DẪN DEBUG VÀ TEST

## 🔍 CÁC BƯỚC DEBUG

### 1. Kiểm tra Logs
```bash
# Xem logs real-time
tail -f storage/logs/laravel.log

# Hoặc trên Windows PowerShell
Get-Content storage\logs\laravel.log -Wait -Tail 50
```

### 2. Enable Debug Mode
Trong file `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### 3. Clear All Caches
```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 4. Test Routes trong Browser

#### List Products
```
GET /admin/product
```
**Expected:** Hiển thị danh sách sản phẩm

#### Create Product Form
```
GET /admin/product/create
```
**Expected:** Hiển thị form tạo sản phẩm

#### Create Product (POST)
```
POST /admin/product/create
```
**Data:**
```json
{
  "name": "Test Product",
  "slug": "test-product",
  "content": "Content here",
  "status": "1",
  "imageOther": [],
  "price": 100000,
  "sale": 80000
}
```
**Expected:** Tạo sản phẩm thành công, trả về JSON success

#### Edit Product Form
```
GET /admin/product/edit/{id}
```
**Expected:** Hiển thị form edit với data của product

#### Update Product (POST)
```
POST /admin/product/edit
```
**Data:**
```json
{
  "id": 1,
  "name": "Updated Product",
  "slug": "updated-product",
  "content": "Updated content",
  "status": "1",
  "imageOther": []
}
```
**Expected:** Cập nhật thành công

#### Delete Product
```
POST /admin/product/delete
```
**Data:**
```json
{
  "id": 1
}
```
**Expected:** Xóa thành công

---

## 🐛 CÁC LỖI THƯỜNG GẶP VÀ CÁCH FIX

### Lỗi 1: Class not found
**Triệu chứng:** `Class 'App\...' not found`
**Fix:**
```bash
composer dump-autoload
php artisan optimize:clear
```

### Lỗi 2: Service binding failed
**Triệu chứng:** `Target [Interface] is not instantiable`
**Fix:** Kiểm tra `AppServiceProvider` đã bind chưa

### Lỗi 3: Method not found
**Triệu chứng:** `Call to undefined method`
**Fix:** Kiểm tra method có tồn tại trong Service/Repository

### Lỗi 4: Database error
**Triệu chứng:** `SQLSTATE[42S22]: Column not found`
**Fix:** 
- Kiểm tra migration đã chạy chưa
- Kiểm tra column có tồn tại không

### Lỗi 5: Validation error
**Triệu chứng:** Validation fails không rõ lý do
**Fix:** 
- Kiểm tra Form Request rules
- Kiểm tra data gửi lên

---

## 📊 CHECKLIST DEBUG

### Trước khi test:
- [ ] Clear tất cả caches
- [ ] Enable debug mode
- [ ] Check database connection
- [ ] Check migrations

### Khi test:
- [ ] Test từng chức năng một
- [ ] Check browser console (F12)
- [ ] Check network tab
- [ ] Check Laravel logs
- [ ] Check database changes

### Sau khi test:
- [ ] Review logs
- [ ] Fix các lỗi tìm được
- [ ] Test lại
- [ ] Document các issues

---

## 🔧 TOOLS HỮU ÍCH

### 1. Laravel Debugbar
```bash
composer require barryvdh/laravel-debugbar --dev
```

### 2. Telescope (Laravel 10)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
```

### 3. Tinker
```bash
php artisan tinker
# Test service
$service = app(\App\Services\Product\ProductServiceInterface::class);
$service->getProducts();
```

---

## 📝 LOG FORMAT

Khi gặp lỗi, ghi lại:
1. **Error message:** Full error message
2. **Stack trace:** Where it happened
3. **Request data:** What was sent
4. **Expected:** What should happen
5. **Actual:** What actually happened

---

**Sẵn sàng debug! 🚀**
