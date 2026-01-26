# Laravel 11 Migration Guide - Step by Step

**Ngày:** 2025-01-21  
**Từ:** Laravel 10.50.0  
**Đến:** Laravel 11.x

---

## ⚠️ QUAN TRỌNG: PHP Version

**Composer vẫn thấy PHP 8.1.32**

**Action Required:**
1. **Restart terminal hoàn toàn** (đóng và mở lại)
2. Hoặc sử dụng **Laragon Terminal** (Menu → Terminal)
3. Chạy script: `.\check-php-version.ps1` để kiểm tra

**Sau khi PHP 8.3+ được detect, mới chạy `composer update`**

---

## Bước 1: Verify PHP & Composer

```bash
php -v                    # Phải show 8.3+
composer --version        # Phải dùng PHP 8.3+
composer diagnose         # Check PHP version
```

---

## Bước 2: Composer Update

### 2.1 Dry Run (Kiểm Tra Conflicts)
```bash
composer update --dry-run
```

### 2.2 Nếu Có Conflicts
Xem file `DEPENDENCIES_COMPATIBILITY_CHECK.md` để xử lý:
- `milon/barcode` - Có thể cần alternative
- `unisharp/laravel-filemanager` - Có thể cần alternative

### 2.3 Chạy Update
```bash
composer update
```

**Lưu Ý:** Quá trình này có thể mất 5-10 phút.

---

## Bước 3: Update bootstrap/app.php

### 3.1 Backup File Cũ
```bash
cp bootstrap/app.php bootstrap/app.php.laravel10.backup
```

### 3.2 Thay Thế File Mới
```bash
cp bootstrap/app.php.laravel11 bootstrap/app.php
```

**Hoặc:** Copy nội dung từ `bootstrap/app.php.laravel11` vào `bootstrap/app.php`

### 3.3 Verify
- File phải có cấu trúc Laravel 11 với `Application::configure()`
- Middleware được đăng ký trong `withMiddleware()`
- Routes được đăng ký trong `withRouting()`

---

## Bước 4: Update Service Providers

### 4.1 RouteServiceProvider
**Laravel 11:** RouteServiceProvider có thể không cần thiết nữa vì routes được load trong `bootstrap/app.php`.

**Action:**
- [ ] Kiểm tra xem có custom logic trong RouteServiceProvider không
- [ ] Nếu không, có thể xóa hoặc để trống
- [ ] Nếu có, di chuyển logic sang `bootstrap/app.php`

### 4.2 AppServiceProvider
- [ ] Giữ nguyên `register()` method
- [ ] Giữ nguyên `boot()` method
- [ ] Verify tất cả bindings vẫn hoạt động

### 4.3 Các Providers Khác
- [ ] `AuthServiceProvider` - Review nếu có custom logic
- [ ] `EventServiceProvider` - Giữ nguyên
- [ ] `BroadcastServiceProvider` - Giữ nguyên
- [ ] `InventoryServiceProvider` - Custom, cần test kỹ

---

## Bước 5: Update Http/Kernel.php

### 5.1 Laravel 11 Changes
- `$routeMiddleware` → Đã di chuyển sang `bootstrap/app.php` (middleware aliases)
- `$middlewarePriority` → Đã di chuyển sang `bootstrap/app.php`
- `$middlewareGroups` → Đã di chuyển sang `bootstrap/app.php`

### 5.2 Action
**Option 1:** Giữ Kernel.php nhưng rỗng (chỉ extends HttpKernel)
**Option 2:** Xóa Kernel.php nếu không cần custom logic

**Khuyến nghị:** Giữ lại nhưng để trống để tương thích ngược.

---

## Bước 6: Config Files Review

### 6.1 Files Cần Review
- [ ] `config/app.php` - Merge với Laravel 11 defaults
- [ ] `config/auth.php` - Check changes
- [ ] `config/cache.php` - Check Redis config
- [ ] `config/session.php` - Check Redis config
- [ ] `config/queue.php` - Check Redis config

### 6.2 Process
1. Backup config files
2. Compare với Laravel 11 defaults
3. Merge custom configs
4. Test application

---

## Bước 7: Testing

### 7.1 Basic Checks
```bash
php artisan migrate:status    # Check migrations
php artisan route:list        # Check routes
php artisan config:cache      # Cache config
php artisan route:cache       # Cache routes (optional)
```

### 7.2 Application Tests
- [ ] Test API endpoints
- [ ] Test admin panel
- [ ] Test public website
- [ ] Test authentication
- [ ] Test middleware

### 7.3 Performance
- [ ] Benchmark response times
- [ ] Check memory usage
- [ ] Monitor errors

---

## Bước 8: Fix Breaking Changes

### 8.1 Common Issues

**Issue 1: Route Model Binding**
- Laravel 11 có thể thay đổi cách route model binding hoạt động
- Check routes có sử dụng model binding

**Issue 2: Middleware Registration**
- Nếu middleware không hoạt động, check `bootstrap/app.php`
- Verify middleware aliases đã đăng ký đúng

**Issue 3: Service Provider Boot Order**
- Laravel 11 có thể thay đổi boot order
- Test các dependencies

---

## Rollback Plan

Nếu có vấn đề nghiêm trọng:

1. **Git Rollback:**
   ```bash
   git checkout v1.0-pre-upgrade-20250121
   composer install
   ```

2. **Database Rollback:**
   - Restore từ backup
   - Verify data integrity

---

## Checklist Tổng Hợp

- [ ] PHP 8.3+ verified
- [ ] Composer update completed
- [ ] bootstrap/app.php updated
- [ ] Service providers reviewed
- [ ] Http/Kernel.php updated
- [ ] Config files reviewed
- [ ] Routes tested
- [ ] APIs tested
- [ ] Admin panel tested
- [ ] Performance benchmarked
- [ ] Breaking changes documented

---

**Last Updated:** 2025-01-21

