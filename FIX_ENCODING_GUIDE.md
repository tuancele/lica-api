# Hướng Dẫn Sửa Lỗi Font Chữ Sau Khi Nâng Cấp Laravel 11

## Vấn Đề

Sau khi nâng cấp lên Laravel 11 và import database cũ, website bị lỗi font chữ do:
- Database tables đang sử dụng charset `latin1_swedish_ci` thay vì `utf8mb4_unicode_ci`
- Middleware SetCharset chưa được đăng ký trong Laravel 11

## Giải Pháp Đã Áp Dụng

### 1. ✅ Đã Thêm SetCharset Middleware

Middleware `SetCharset` đã được thêm vào:
- Web middleware group trong `bootstrap/app.php`
- API middleware group trong `bootstrap/app.php`

Middleware này sẽ tự động thêm header `charset=UTF-8` vào tất cả responses.

### 2. ✅ Đã Tạo Command Chuyển Đổi Database Charset

Command `db:convert-charset` đã được tạo để chuyển đổi database từ latin1 sang utf8mb4.

## Các Bước Thực Hiện

### Bước 1: Backup Database (QUAN TRỌNG)

**Trước khi chạy bất kỳ lệnh nào, hãy backup database:**

```bash
# Backup database
mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Bước 2: Kiểm Tra Các Bảng Cần Chuyển Đổi (Dry Run)

```bash
php artisan db:convert-charset --dry-run
```

Lệnh này sẽ hiển thị danh sách các bảng cần chuyển đổi mà không thực hiện thay đổi.

### Bước 3: Chuyển Đổi Database Charset

**Cảnh báo:** Lệnh này sẽ thay đổi cấu trúc database. Đảm bảo đã backup!

```bash
php artisan db:convert-charset
```

Lệnh sẽ:
- Chuyển đổi tất cả tables từ `latin1_swedish_ci` sang `utf8mb4_unicode_ci`
- Chuyển đổi tất cả columns có text type sang `utf8mb4_unicode_ci`

### Bước 4: Chuyển Đổi Từng Bảng (Nếu Cần)

Nếu muốn chuyển đổi từng bảng một để kiểm tra:

```bash
php artisan db:convert-charset --table=posts
```

### Bước 5: Kiểm Tra Encoding Issues (Tùy Chọn)

```bash
php artisan db:fix-encoding --dry-run
```

Lệnh này sẽ kiểm tra các vấn đề encoding trong dữ liệu.

## Kiểm Tra Sau Khi Chuyển Đổi

### 1. Kiểm Tra Charset Của Database

```bash
php artisan tinker
```

Trong tinker:
```php
DB::select("SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");
```

Tất cả bảng nên có `TABLE_COLLATION = 'utf8mb4_unicode_ci'`

### 2. Kiểm Tra Website

- Mở website và kiểm tra xem font chữ có hiển thị đúng không
- Kiểm tra các trang có tiếng Việt
- Kiểm tra API responses có charset header đúng không

### 3. Kiểm Tra Response Headers

Mở Developer Tools (F12) và kiểm tra Response Headers:
```
Content-Type: text/html; charset=UTF-8
```

## Lưu Ý Quan Trọng

1. **Backup trước khi chạy:** Luôn backup database trước khi chạy lệnh chuyển đổi
2. **Test trên môi trường dev trước:** Nên test trên môi trường development trước khi chạy trên production
3. **Dữ liệu bị lỗi encoding:** Nếu dữ liệu đã bị lỗi encoding từ trước (double encoding), có thể cần script riêng để fix
4. **Thời gian chạy:** Quá trình chuyển đổi có thể mất thời gian nếu database lớn

## Troubleshooting

### Nếu vẫn còn lỗi font chữ sau khi chuyển đổi:

1. **Kiểm tra database connection charset:**
   - File `config/database.php` đã có `charset => 'utf8mb4'` và `collation => 'utf8mb4_unicode_ci'`

2. **Clear cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

3. **Kiểm tra dữ liệu có bị double encoding không:**
   - Nếu dữ liệu đã bị double encoding, cần script riêng để fix

4. **Kiểm tra browser encoding:**
   - Đảm bảo browser đang sử dụng UTF-8
   - Kiểm tra meta tag trong HTML: `<meta charset="UTF-8">`

## Liên Hệ

Nếu gặp vấn đề, vui lòng:
1. Kiểm tra logs: `storage/logs/laravel.log`
2. Chạy lệnh với `--dry-run` để xem sẽ thay đổi gì
3. Kiểm tra database connection settings

