# PHP Upgrade Verification

**Ngày:** 2025-01-21

## ⚠️ Vấn Đề Phát Hiện

Composer vẫn thấy PHP 8.1.32, mặc dù bạn đã nâng cấp PHP.

## Nguyên Nhân Có Thể

1. **Terminal đang dùng PHP cũ** - Cần restart terminal
2. **Laragon chưa switch PHP version** - Cần kiểm tra lại
3. **PATH environment variable** - Cần refresh

## Cách Kiểm Tra & Fix

### Bước 1: Kiểm Tra PHP Version Trong Laragon

1. Mở Laragon
2. Menu → PHP → Version
3. Đảm bảo đã chọn **PHP 8.3** (hoặc 8.2+)
4. Click **Restart All**

### Bước 2: Restart Terminal

1. **Đóng terminal hiện tại**
2. **Mở terminal mới** (PowerShell hoặc CMD)
3. Chạy: `php -v`
4. Phải thấy: `PHP 8.3.x` hoặc `PHP 8.2.x`

### Bước 3: Verify Composer

Sau khi restart terminal:
```bash
php -v
composer --version
composer diagnose
```

### Bước 4: Nếu Vẫn Không Được

**Option 1: Set PHP Path Manually**
```powershell
# Tìm PHP 8.3 path trong Laragon
# Thường là: C:\laragon\bin\php\php-8.3.x\
# Thêm vào PATH hoặc dùng full path
```

**Option 2: Dùng Laragon Terminal**
- Mở Laragon
- Click "Terminal" button
- Terminal này sẽ tự động dùng PHP version đã chọn

## Sau Khi Verify

Khi `php -v` show 8.3+, tiếp tục với:
1. `composer update --dry-run` - Kiểm tra conflicts
2. `composer update` - Nâng cấp packages
3. Fix breaking changes

---

**Last Updated:** 2025-01-21

