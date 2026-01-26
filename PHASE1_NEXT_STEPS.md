# Giai Đoạn 1: Bước Tiếp Theo

**Ngày:** 2025-01-21  
**Trạng Thái:** 🔄 Đang Thực Hiện

---

## ✅ Đã Hoàn Thành

1. ✅ **Composer.json Updated**
   - PHP: `^8.3`
   - Laravel: `^11.0`
   - Dev dependencies updated

2. ✅ **Laravel 11 Files Prepared**
   - `bootstrap/app.php.laravel11` - File mới cho Laravel 11
   - `LARAVEL_11_MIGRATION_GUIDE.md` - Hướng dẫn chi tiết
   - `check-php-version.ps1` - Script kiểm tra PHP
   - `use-php83.ps1` - Script sử dụng PHP 8.3

3. ✅ **Documentation**
   - Tất cả breaking changes đã được document
   - Dependencies compatibility đã được check
   - Migration guide đã được tạo

---

## ⚠️ VẤN ĐỀ: PHP PATH

**Phát Hiện:**
- PHP 8.3.28 đã được cài đặt tại: `C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\`
- Nhưng PATH vẫn trỏ đến PHP 8.1.32

**Giải Pháp:**

### Option 1: Sử Dụng Script (Khuyến Nghị)
```powershell
.\use-php83.ps1
composer update
```

### Option 2: Sử Dụng Full Path
```powershell
C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe C:\laragon\bin\composer\composer.bat update
```

### Option 3: Laragon Terminal
1. Mở Laragon
2. Menu → Terminal
3. Terminal này sẽ tự động dùng PHP version đã chọn
4. Chạy `composer update`

---

## 📋 Bước Tiếp Theo (Sau Khi PHP 8.3 Được Sử Dụng)

### Bước 1: Composer Update

```bash
# 1. Verify PHP
php -v  # Phải show 8.3.28

# 2. Dry run để check conflicts
composer update --dry-run

# 3. Nếu OK, chạy update
composer update
```

**Lưu Ý:**
- Quá trình có thể mất 5-10 phút
- Có thể có conflicts với `milon/barcode` và `unisharp/laravel-filemanager`
- Xem `DEPENDENCIES_COMPATIBILITY_CHECK.md` để xử lý

### Bước 2: Update bootstrap/app.php

```bash
# Backup file cũ
cp bootstrap/app.php bootstrap/app.php.laravel10.backup

# Thay thế file mới
cp bootstrap/app.php.laravel11 bootstrap/app.php
```

**Hoặc:** Copy nội dung từ `bootstrap/app.php.laravel11` vào `bootstrap/app.php`

### Bước 3: Review Service Providers

Theo checklist trong `API_DOCUMENTATION.md`:
- [ ] RouteServiceProvider - Có thể không cần trong Laravel 11
- [ ] AppServiceProvider - Giữ nguyên
- [ ] AuthServiceProvider - Review
- [ ] EventServiceProvider - Giữ nguyên
- [ ] InventoryServiceProvider - Custom, test kỹ

### Bước 4: Update Http/Kernel.php

Laravel 11:
- Middleware đã di chuyển sang `bootstrap/app.php`
- Có thể giữ Kernel.php rỗng hoặc xóa

### Bước 5: Testing

```bash
php artisan migrate:status
php artisan route:list
php artisan config:cache
```

---

## 📝 Checklist Theo API_DOCUMENTATION.md

### 1.1 Nâng Cấp Laravel 10.x → 11.x

#### Trước Khi Nâng Cấp
- [x] Backup database đầy đủ
- [x] Backup codebase (git tag)
- [x] Review Laravel 11 breaking changes
- [x] Kiểm tra tất cả dependencies compatibility
- [ ] Tạo staging environment

#### Quá Trình Nâng Cấp
- [x] Update `composer.json`: `"laravel/framework": "^11.0"`
- [ ] Chạy `composer update` ⏳ **CHỜ PHP 8.3**
- [ ] Xử lý breaking changes:
  - [ ] Exception handling changes
  - [ ] Route model binding changes
  - [ ] Middleware changes
  - [ ] Service provider changes
  - [ ] Config file changes
- [x] Update `bootstrap/app.php` (Laravel 11 structure) - ✅ **Đã chuẩn bị file**
- [ ] Update route files
- [ ] Update middleware registration

#### Sau Khi Nâng Cấp
- [ ] Chạy `php artisan migrate:status`
- [ ] Chạy `php artisan route:list`
- [ ] Chạy `php artisan config:cache`
- [ ] Test tất cả API endpoints
- [ ] Test admin panel
- [ ] Test public website
- [ ] Performance benchmark
- [ ] Document breaking changes

---

## 🎯 Immediate Action

**CHẠY NGAY:**
```powershell
.\use-php83.ps1
composer update --dry-run
```

Nếu dry-run OK:
```powershell
composer update
```

---

**Last Updated:** 2025-01-21

