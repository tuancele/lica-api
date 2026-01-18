# Flash Sale API Deployment Guide

## 📋 Pre-Deployment Checklist

### 1. Code Review
- [x] Migration đã được tạo và test thành công
- [x] Models đã được cập nhật với scopes và accessors
- [x] Resources đã được tạo
- [x] Controllers đã được tạo (API V1 và Admin API)
- [x] Routes đã được đăng ký
- [x] Admin Panel Views đã được cập nhật để hỗ trợ variants
- [x] Không có lỗi linter

### 2. Local Testing
- [x] Migration chạy thành công
- [x] Routes được đăng ký đúng
- [ ] Test Public API endpoints
- [ ] Test Admin API endpoints
- [ ] Test với sản phẩm có variants
- [ ] Test với sản phẩm không có variants

---

## 🚀 Deployment Steps

### Step 1: Backup Database

**Trước khi deploy, BẮT BUỘC phải backup database:**

```bash
# MySQL backup
mysqldump -u username -p database_name > backup_before_flashsale_$(date +%Y%m%d_%H%M%S).sql

# Hoặc sử dụng Laravel backup package
php artisan backup:run
```

### Step 2: Deploy Code

```bash
# 1. Pull latest code
git pull origin main

# 2. Install/Update dependencies (nếu có)
composer install --no-dev --optimize-autoloader

# 3. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 3: Run Migration

```bash
# Chạy migration
php artisan migrate

# Verify migration
php artisan migrate:status
```

**Expected Output:**
```
2026_01_18_120338_add_variant_id_to_productsales_table .......... DONE
```

### Step 4: Verify Database Schema

```sql
-- Kiểm tra cột variant_id đã được thêm
DESCRIBE productsales;

-- Kiểm tra index
SHOW INDEX FROM productsales WHERE Key_name = 'productsales_flashsale_variant_index';
```

**Expected:**
- Cột `variant_id` có type `int(11) unsigned`, nullable
- Index `productsales_flashsale_variant_index` tồn tại

### Step 5: Verify Routes

```bash
php artisan route:list --path=api/v1/flash-sales
php artisan route:list --path=admin/api/flash-sales
```

**Expected:**
- 2 Public API routes
- 7 Admin API routes

### Step 6: Test API Endpoints

#### Test Public API (Không cần authentication)

```bash
# Test active Flash Sales
curl -X GET "https://your-domain.com/api/v1/flash-sales/active" \
  -H "Accept: application/json"

# Test products in Flash Sale
curl -X GET "https://your-domain.com/api/v1/flash-sales/1/products" \
  -H "Accept: application/json"
```

#### Test Admin API (Cần authentication)

```bash
# Get token first (tùy theo authentication method)
TOKEN="your-api-token"

# Test list Flash Sales
curl -X GET "https://your-domain.com/admin/api/flash-sales" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

# Test create Flash Sale
curl -X POST "https://your-domain.com/admin/api/flash-sales" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
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

### Step 7: Test Admin Panel

1. **Login vào Admin Panel:**
   - URL: `https://your-domain.com/admin/flashsale`

2. **Test tạo Flash Sale mới:**
   - Tạo Flash Sale với sản phẩm không có variants
   - Verify lưu thành công

3. **Test với sản phẩm có variants:**
   - Chọn sản phẩm có `has_variants = 1`
   - Verify hiển thị đủ tất cả variants
   - Set giá Flash Sale cho từng variant
   - Verify lưu thành công

4. **Test chỉnh sửa Flash Sale:**
   - Edit Flash Sale đã tạo
   - Thêm/xóa sản phẩm
   - Verify dữ liệu được cập nhật đúng

### Step 8: Monitor Logs

```bash
# Monitor Laravel logs
tail -f storage/logs/laravel.log

# Monitor error logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log

# Check for errors
grep -i "error\|exception" storage/logs/laravel-*.log
```

### Step 9: Performance Check

```bash
# Clear all caches
php artisan optimize:clear
php artisan optimize

# Rebuild autoloader
composer dump-autoload --optimize
```

---

## 🔍 Post-Deployment Verification

### 1. Database Verification

```sql
-- Kiểm tra dữ liệu cũ vẫn còn (variant_id = NULL)
SELECT COUNT(*) FROM productsales WHERE variant_id IS NULL;

-- Kiểm tra có thể tạo Flash Sale với variant_id
SELECT * FROM productsales WHERE variant_id IS NOT NULL LIMIT 5;
```

### 2. API Response Verification

**Test với Postman hoặc browser:**

1. **Public API:**
   - `GET /api/v1/flash-sales/active` → Should return 200
   - `GET /api/v1/flash-sales/{id}/products` → Should return 200 with products

2. **Admin API:**
   - `GET /admin/api/flash-sales` → Should return 200 with list
   - `POST /admin/api/flash-sales` → Should create successfully
   - `GET /admin/api/flash-sales/{id}` → Should return detail with variants

### 3. Admin Panel Verification

1. **Tạo Flash Sale mới:**
   - Chọn sản phẩm có variants
   - Verify hiển thị đủ variants
   - Set giá cho từng variant
   - Save và verify thành công

2. **Chỉnh sửa Flash Sale:**
   - Edit Flash Sale đã tạo
   - Verify variants được load đúng
   - Update giá và verify lưu thành công

---

## 🐛 Troubleshooting

### Issue 1: Migration fails

**Error:** `Referencing column 'variant_id' and referenced column 'id' are incompatible`

**Solution:**
- Migration đã được sửa để không tạo foreign key ngay
- Nếu vẫn lỗi, chạy migration với `--force`:
  ```bash
  php artisan migrate --force
  ```

### Issue 2: Routes not found

**Error:** `404 Not Found` khi gọi API

**Solution:**
```bash
# Clear route cache
php artisan route:clear
php artisan route:cache

# Verify routes
php artisan route:list --path=api/v1/flash-sales
```

### Issue 3: Variants not showing in Admin Panel

**Error:** Sản phẩm có variants nhưng không hiển thị

**Solution:**
1. Kiểm tra `has_variants = 1` trong database
2. Kiểm tra variants có tồn tại:
   ```sql
   SELECT * FROM variants WHERE product_id = YOUR_PRODUCT_ID;
   ```
3. Clear view cache:
   ```bash
   php artisan view:clear
   ```

### Issue 4: API returns empty data

**Error:** API trả về `data: []` mặc dù có Flash Sale

**Solution:**
1. Kiểm tra Flash Sale có đang active:
   ```sql
   SELECT * FROM flashsales 
   WHERE status = 1 
   AND start <= UNIX_TIMESTAMP() 
   AND end >= UNIX_TIMESTAMP();
   ```
2. Kiểm tra ProductSale có dữ liệu:
   ```sql
   SELECT * FROM productsales WHERE flashsale_id = YOUR_FLASH_SALE_ID;
   ```

---

## 📊 Monitoring

### 1. Monitor API Performance

```bash
# Check API response times
# Sử dụng monitoring tool như New Relic, Datadog, hoặc tự build

# Log slow queries
# Thêm vào AppServiceProvider:
DB::listen(function ($query) {
    if ($query->time > 1000) { // > 1 second
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'time' => $query->time
        ]);
    }
});
```

### 2. Monitor Errors

```bash
# Set up error tracking (Sentry, Bugsnag, etc.)
# Hoặc monitor Laravel logs:

# Check for errors in last hour
grep -i "error" storage/logs/laravel-$(date +%Y-%m-%d).log | tail -20
```

### 3. Monitor Database

```sql
-- Check table size
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.TABLES
WHERE table_schema = 'your_database'
AND table_name IN ('flashsales', 'productsales')
ORDER BY size_mb DESC;

-- Check index usage
SHOW INDEX FROM productsales;
```

---

## 🔄 Rollback Plan

Nếu có vấn đề nghiêm trọng, rollback như sau:

### 1. Rollback Migration

```bash
# Rollback last migration
php artisan migrate:rollback --step=1

# Verify rollback
DESCRIBE productsales; # variant_id should be gone
```

### 2. Restore Code

```bash
# Revert to previous commit
git revert HEAD
# hoặc
git reset --hard PREVIOUS_COMMIT_HASH
```

### 3. Restore Database

```bash
# Restore from backup
mysql -u username -p database_name < backup_before_flashsale_YYYYMMDD_HHMMSS.sql
```

---

## ✅ Success Criteria

Deployment được coi là thành công khi:

1. ✅ Migration chạy thành công
2. ✅ Tất cả routes hoạt động
3. ✅ Public API trả về dữ liệu đúng
4. ✅ Admin API CRUD hoạt động
5. ✅ Admin Panel hiển thị variants đúng
6. ✅ Có thể tạo/sửa Flash Sale với variants
7. ✅ Không có lỗi trong logs
8. ✅ Performance không bị ảnh hưởng

---

## 📞 Support

Nếu gặp vấn đề:

1. Check logs: `storage/logs/laravel.log`
2. Check database: Verify schema và data
3. Check routes: `php artisan route:list`
4. Contact team lead hoặc DevOps

---

**Ngày tạo:** 2025-01-18  
**Phiên bản:** 1.0  
**Trạng thái:** Ready for Production
