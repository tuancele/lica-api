# PHP 8.3 Path Detection & Composer Update Script

**Vấn đề:** Terminal đang dùng PHP 8.1.32 từ PATH, cần dùng PHP 8.3

## Giải Pháp

### Option 1: Restart Terminal (Khuyến nghị)
1. **Đóng terminal hiện tại hoàn toàn**
2. **Mở Laragon**
3. **Menu → PHP → Version → Chọn PHP 8.3**
4. **Click "Restart All"**
5. **Mở terminal mới từ Laragon** (click nút Terminal)

### Option 2: Dùng PHP 8.3 Trực Tiếp
Tìm đường dẫn PHP 8.3 trong Laragon (thường là):
```
C:\laragon\bin\php\php-8.3.x-nts-Win32-vs16-x64\php.exe
```

Sau đó dùng full path:
```powershell
C:\laragon\bin\php\php-8.3.x-nts-Win32-vs16-x64\php.exe -v
C:\laragon\bin\php\php-8.3.x-nts-Win32-vs16-x64\php.exe C:\laragon\bin\composer\composer.phar update --dry-run
```

### Option 3: Update PATH Tạm Thời
```powershell
# Tìm PHP 8.3 path
$php83Path = "C:\laragon\bin\php\php-8.3.x-nts-Win32-vs16-x64"
$env:PATH = "$php83Path;$env:PATH"
php -v
```

---

## Sau Khi Verify PHP 8.3

Chạy:
```bash
composer update --dry-run
composer update
```

---

**Last Updated:** 2025-01-21

