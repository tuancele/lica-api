# Hướng Dẫn Chuyển Đổi Sang PHP 8.3

## ✅ PHP 8.3 Đã Sẵn Sàng!

PHP 8.3.28 đã được cài đặt trong Laragon tại:
```
C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64
```

## 🚀 Cách Chuyển Đổi (3 Bước Đơn Giản)

### Bước 1: Mở Laragon

Mở ứng dụng Laragon trên máy tính của bạn.

### Bước 2: Chọn PHP Version

1. Click vào **Menu** (góc trên bên phải)
2. Chọn **PHP**
3. Chọn **Select version**
4. Chọn **php-8.3.28-Win32-vs16-x64**

### Bước 3: Restart Laragon

1. Click **Stop All** (dừng tất cả services)
2. Click **Start All** (khởi động lại)
3. HOẶC đóng và mở lại Laragon hoàn toàn

## ✅ Kiểm Tra

Mở Command Prompt hoặc PowerShell và chạy:

```bash
php -v
```

Kết quả phải hiển thị:
```
PHP 8.3.28 (cli) ...
```

## 📋 Sau Khi Chuyển Đổi

### 1. Cập Nhật Composer Dependencies

```bash
cd c:\laragon\www\lica
composer update
```

### 2. Kiểm Tra Laravel

```bash
php artisan --version
```

### 3. Chạy Tests

```bash
php artisan test
```

## 🔧 Scripts Có Sẵn

- **`CHUYEN_PHP_83.bat`** - Double-click để xem hướng dẫn
- **`switch-to-php83.ps1`** - PowerShell script (đã chạy, cấu hình xong)
- **`upgrade-php-83.ps1`** - Script tải và cài PHP 8.3 (không cần vì đã có)

## ⚠️ Lưu Ý

- Đảm bảo Laragon đã được restart sau khi chuyển đổi
- Nếu `php -v` vẫn hiển thị 8.1.32, hãy:
  1. Đóng tất cả terminal/command prompt
  2. Restart Laragon hoàn toàn
  3. Mở terminal mới và kiểm tra lại

## 🆘 Troubleshooting

### PHP vẫn là 8.1.32 sau khi chuyển đổi

1. **Kiểm tra Laragon đã chọn đúng version chưa**:
   - Menu > PHP > Select version
   - Phải chọn `php-8.3.28-Win32-vs16-x64`

2. **Restart hoàn toàn**:
   - Đóng Laragon
   - Mở lại Laragon
   - Start All

3. **Kiểm tra PATH**:
   ```bash
   echo %PATH%
   ```
   Phải có đường dẫn đến PHP 8.3

4. **Mở terminal mới**:
   - Đóng tất cả terminal hiện tại
   - Mở terminal mới
   - Chạy `php -v`

### Composer vẫn dùng PHP cũ

```bash
composer clear-cache
composer --version
```

Nếu vẫn không đúng, thử:
```bash
composer self-update
```

## ✅ Checklist

- [ ] Đã chọn PHP 8.3 trong Laragon
- [ ] Đã restart Laragon
- [ ] `php -v` hiển thị 8.3.28
- [ ] `composer update` chạy thành công
- [ ] `php artisan --version` hoạt động

## 🎯 Tiếp Theo

Sau khi PHP 8.3 đã active:

1. ✅ Chạy `composer update`
2. ✅ Cập nhật `.env` với Redis config
3. ✅ Test Redis connection
4. ✅ Chạy `composer pint` và `composer phpstan`
5. ✅ Hoàn tất Phase 1!

Xem `PHASE1_HOAN_TAT.md` để biết các bước tiếp theo.


