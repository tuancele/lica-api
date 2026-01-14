# R2 Upload Logging System - Hướng dẫn sử dụng

## Tổng quan
Hệ thống logging đã được tích hợp vào cả Frontend (JavaScript) và Backend (PHP) để ghi lại toàn bộ sự kiện trong quá trình upload ảnh lên R2.

## Frontend Logging (JavaScript)

### Truy cập logs
1. Mở Console trong trình duyệt (F12)
2. Logs được lưu trong `window.R2Logs` hoặc `R2Logger.logs`
3. Các lệnh hữu ích:
   ```javascript
   // Xem tất cả logs
   R2Logger.logs
   
   // Xem logs dạng JSON
   JSON.stringify(R2Logger.logs, null, 2)
   
   // Download logs về file JSON
   R2Logger.download()
   
   // Xem logs lỗi
   R2Logger.logs.filter(log => log.level === 'error')
   ```

### Các sự kiện được log
- Khởi tạo component
- Chọn file
- Tạo preview
- Bắt đầu upload
- Upload từng file
- Cập nhật input/img src
- Submit form
- AJAX request/response
- Lỗi (nếu có)

## Backend Logging (PHP)

### Xem logs
```bash
# Xem log Laravel
tail -f storage/logs/laravel.log | grep "R2 Upload"

# Hoặc trên Windows PowerShell
Get-Content storage\logs\laravel.log -Tail 100 | Select-String "R2 Upload"
```

### Log ID
Mỗi request upload có một `logId` duy nhất để dễ theo dõi:
- Format: `R2-{uniqid}`
- Tất cả logs trong cùng một request sẽ có cùng logId

### Các sự kiện được log
- Request bắt đầu
- File keys được tìm thấy
- Validation
- WebP conversion
- Upload lên R2
- URL generation
- Lỗi (nếu có)

## Debug Workflow

### Khi có lỗi:
1. Mở Console (F12)
2. Chạy: `R2Logger.download()` để tải logs về
3. Kiểm tra log file: `storage/logs/laravel.log`
4. Tìm logId trong cả 2 file để theo dõi toàn bộ flow

### Các lỗi thường gặp:
- **Input không được cập nhật**: Kiểm tra `updateStatus` trong logs
- **AJAX error**: Kiểm tra `responseText` trong error logs
- **Upload failed**: Kiểm tra backend logs với logId tương ứng

## Tips
- Logs được giới hạn 1000 entries để tránh memory leak
- Logs có timestamp ISO 8601
- Tất cả logs đều có level: `info`, `warn`, `error`
