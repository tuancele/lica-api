# PHP 8.3 Setup Guide - Enable Extensions

**Ngày:** 2025-01-21

---

## ✅ PHP 8.3.28 Đã Được Phát Hiện

**Location:** `C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\`

---

## ⚠️ Vấn Đề: Thiếu Extension `zip`

**Error:**
```
phpoffice/phpspreadsheet requires ext-zip * -> it is missing from your system
```

---

## 🔧 Giải Pháp

### Option 1: Enable Extension trong php.ini (Khuyến Nghị)

1. **Tìm php.ini:**
   ```powershell
   $env:PATH = "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64;" + $env:PATH
   php --ini
   ```
   Sẽ show path đến php.ini file.

2. **Mở php.ini và tìm dòng:**
   ```ini
   ;extension=zip
   ```

3. **Uncomment (bỏ dấu ;):**
   ```ini
   extension=zip
   ```

4. **Save và verify:**
   ```powershell
   $env:PATH = "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64;" + $env:PATH
   php -m | Select-String -Pattern "zip"
   ```
   Phải thấy "zip" trong danh sách.

### Option 2: Sử Dụng Flag Ignore (Tạm Thời)

```powershell
$env:PATH = "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64;" + $env:PATH
composer update --ignore-platform-req=ext-zip
```

**Lưu Ý:** Chỉ dùng tạm thời, nên enable extension đúng cách.

---

## 📋 Extensions Cần Kiểm Tra

Sau khi enable zip, kiểm tra các extensions khác:

```powershell
$env:PATH = "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64;" + $env:PATH
php -m
```

**Các extensions quan trọng:**
- ✅ zip - Cần cho phpoffice/phpspreadsheet
- ✅ pdo_mysql - Database
- ✅ mbstring - String functions
- ✅ xml - XML processing
- ✅ curl - HTTP requests
- ✅ gd - Image processing
- ✅ openssl - Security

---

## 🚀 Sau Khi Enable Extensions

### 1. Verify PHP
```powershell
$env:PATH = "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64;" + $env:PATH
php -v
php -m | Select-String -Pattern "zip|pdo|mbstring|xml|curl|gd"
```

### 2. Composer Update
```powershell
$env:PATH = "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64;" + $env:PATH
composer update --dry-run
```

Nếu OK:
```powershell
composer update
```

---

## 💡 Tip: Tạo Alias PowerShell

Thêm vào PowerShell profile để dễ sử dụng:

```powershell
# Mở profile
notepad $PROFILE

# Thêm dòng này:
function Use-PHP83 {
    $env:PATH = "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64;" + $env:PATH
    php -v
}

# Sau đó chỉ cần gọi:
Use-PHP83
composer update
```

---

**Last Updated:** 2025-01-21

