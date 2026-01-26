# Hướng Dẫn Nâng Cấp PHP 8.3 cho Laragon

## Phương Pháp 1: Tự Động (Khuyến Nghị)

### Sử dụng Script PowerShell

1. **Chạy script tự động**:
   ```powershell
   .\upgrade-php-83.ps1
   ```

2. **Script sẽ**:
   - Kiểm tra PHP version hiện tại
   - Tải PHP 8.3 từ php.net
   - Giải nén vào Laragon\bin\php
   - Cấu hình extensions cần thiết

3. **Sau khi script chạy xong**:
   - Mở Laragon
   - Menu > PHP > Select version
   - Chọn php-8.3.x
   - Restart Laragon

## Phương Pháp 2: Thủ Công

### Bước 1: Tải PHP 8.3

1. Truy cập: https://windows.php.net/download/
2. Tải **PHP 8.3 Thread Safe (VS16 x64 Non Thread Safe)**
   - File: `php-8.3.x-Win32-vs16-x64.zip`

### Bước 2: Giải Nén

1. Giải nén file zip
2. Copy thư mục vào: `C:\laragon\bin\php\`
3. Đổi tên thành: `php-8.3.x` (ví dụ: `php-8.3.15`)

### Bước 3: Cấu Hình

1. Copy `php.ini-development` thành `php.ini`
2. Mở `php.ini` và bỏ comment các extensions:
   ```ini
   extension=curl
   extension=fileinfo
   extension=gd
   extension=mbstring
   extension=mysqli
   extension=openssl
   extension=pdo_mysql
   extension=zip
   ```

### Bước 4: Cài Redis Extension (Nếu cần)

1. Tải phpredis từ: https://pecl.php.net/package/redis
2. Hoặc sử dụng DLL từ: https://windows.php.net/downloads/pecl/releases/redis/
3. Copy `php_redis.dll` vào thư mục `ext`
4. Thêm vào `php.ini`:
   ```ini
   extension=redis
   ```

### Bước 5: Chuyển Đổi trong Laragon

1. Mở Laragon
2. Click **Menu** > **PHP** > **Select version**
3. Chọn **php-8.3.x**
4. Click **Stop All** rồi **Start All**
5. Hoặc restart Laragon

### Bước 6: Kiểm Tra

```bash
php -v
```

Kết quả phải hiển thị: `PHP 8.3.x`

## Kiểm Tra Extensions

```bash
php -m
```

Các extensions cần có:
- curl
- fileinfo
- gd
- mbstring
- mysqli
- openssl
- pdo_mysql
- redis (nếu cần)
- zip

## Troubleshooting

### PHP không chạy sau khi nâng cấp

1. Kiểm tra Laragon đã chọn đúng PHP version
2. Restart Laragon hoàn toàn
3. Kiểm tra `php.ini` có đúng không
4. Kiểm tra PATH environment variable

### Extension không load

1. Kiểm tra file DLL có trong thư mục `ext`
2. Kiểm tra `php.ini` có dòng `extension=xxx`
3. Kiểm tra `extension_dir` trong `php.ini`

### Composer vẫn dùng PHP cũ

```bash
# Clear composer cache
composer clear-cache

# Verify composer uses correct PHP
composer --version
```

## Sau Khi Nâng Cấp

1. **Cập nhật Composer**:
   ```bash
   composer self-update
   ```

2. **Cập nhật Dependencies**:
   ```bash
   composer update
   ```

3. **Kiểm Tra Laravel**:
   ```bash
   php artisan --version
   ```

4. **Chạy Tests**:
   ```bash
   php artisan test
   ```

## Lưu Ý

- Backup `php.ini` trước khi chỉnh sửa
- Nếu có nhiều project, có thể cần cấu hình riêng
- Một số extension có thể cần Visual C++ Redistributable

