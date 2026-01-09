# HƯỚNG DẪN KHẮC PHỤC LỖI "Composer detected issues in your platform"

Bạn đang gặp lỗi này vì dự án đã được nâng cấp lên **Laravel 10** (Yêu cầu PHP >= 8.1), nhưng Laragon vẫn đang chạy **PHP 7.4**.

### Cách khắc phục (Bắt buộc):

1.  Mở giao diện **Laragon**.
2.  Click vào **Menu** (góc trên bên trái hoặc chuột phải).
3.  Chọn **PHP** -> **Version**.
4.  Chọn phiên bản **`php-8.1.32-nts-Win32-vs16-x64`** (hoặc 8.3).
5.  Laragon sẽ tự động khởi động lại Apache/Nginx.

Sau khi thực hiện, lỗi trên sẽ biến mất và website sẽ hoạt động bình thường với Laravel 10.

### Lưu ý khi chạy lệnh Terminal:
Nếu bạn gõ lệnh `php artisan` trong terminal và vẫn thấy lỗi tương tự, hãy đảm bảo terminal cũng đang sử dụng PHP 8.1. Bạn có thể kiểm tra bằng lệnh:
```bash
php -v
```
Nếu vẫn là 7.4, bạn cần cập nhật biến môi trường Path hoặc sử dụng đường dẫn tuyệt đối:
```bash
C:\laragon\bin\php\php-8.1.32-nts-Win32-vs16-x64\php.exe artisan migrate
```
