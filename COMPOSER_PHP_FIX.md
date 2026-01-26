# Fix Composer PHP Version Issue

**Vấn Đề:** Composer vẫn đang dùng PHP 8.1.32 thay vì PHP 8.3

## Giải Pháp

### Option 1: Sử dụng PHP 8.3 trực tiếp với Composer

```powershell
# Tìm PHP 8.3 path
$php83Path = Get-ChildItem "C:\laragon\bin\php\php-8.3*" -Directory | Select-Object -First 1 -ExpandProperty FullName
$php83Exe = Join-Path $php83Path "php.exe"

# Verify PHP version
& $php83Exe -v

# Chạy composer với PHP 8.3
& $php83Exe "C:\laragon\bin\composer\composer.phar" update --dry-run
```

### Option 2: Update PATH trong Terminal

```powershell
# Tìm PHP 8.3 path
$php83Path = Get-ChildItem "C:\laragon\bin\php\php-8.3*" -Directory | Select-Object -First 1 -ExpandProperty FullName

# Thêm vào PATH (chỉ cho session hiện tại)
$env:PATH = "$php83Path;$env:PATH"

# Verify
php -v
composer --version
```

### Option 3: Dùng Laragon Terminal

1. Mở Laragon
2. Đảm bảo PHP 8.3 được chọn trong Menu → PHP → Version
3. Click "Terminal" button trong Laragon
4. Terminal này sẽ tự động dùng PHP version đã chọn

### Option 4: Set PHP Path trong Composer Config

```powershell
# Tìm PHP 8.3 path
$php83Path = Get-ChildItem "C:\laragon\bin\php\php-8.3*" -Directory | Select-Object -First 1 -ExpandProperty FullName
$php83Exe = Join-Path $php83Path "php.exe"

# Set trong composer config
composer config -g php-path $php83Exe
```

---

**Last Updated:** 2025-01-21

