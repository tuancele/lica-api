# HƯỚNG DẪN XEM LOG LARAVEL

## 📍 Vị trí file log

Laravel log được lưu tại: `storage/logs/`

- **Log mới nhất**: `storage/logs/laravel-YYYY-MM-DD.log` (theo ngày)
- **Log tổng hợp**: `storage/logs/laravel.log`

## 🔍 Các cách xem log

### 1. PowerShell (Windows)

#### Xem log mới nhất (100 dòng cuối):
```powershell
cd c:\laragon\www\lica
Get-Content storage\logs\laravel-2026-01-14.log -Tail 100
```

#### Xem toàn bộ log:
```powershell
Get-Content storage\logs\laravel-2026-01-14.log
```

#### Tìm kiếm lỗi cụ thể:
```powershell
# Tìm lỗi checkout
Get-Content storage\logs\laravel-2026-01-14.log | Select-String -Pattern "Checkout|cart|postCheckout" -Context 5

# Tìm lỗi Product not found
Get-Content storage\logs\laravel-2026-01-14.log | Select-String -Pattern "Product not found" -Context 10

# Tìm tất cả ERROR
Get-Content storage\logs\laravel-2026-01-14.log | Select-String -Pattern "ERROR"
```

#### Xem log real-time (theo dõi log mới):
```powershell
Get-Content storage\logs\laravel-2026-01-14.log -Wait -Tail 50
```

### 2. Command Prompt (CMD)

```cmd
cd c:\laragon\www\lica
type storage\logs\laravel-2026-01-14.log | more
```

### 3. Mở file trực tiếp

Mở file bằng Notepad++ hoặc VS Code:
```
c:\laragon\www\lica\storage\logs\laravel-2026-01-14.log
```

## 🐛 Các lỗi thường gặp và cách tìm

### Lỗi Checkout (500 Error)
```powershell
Get-Content storage\logs\laravel-2026-01-14.log | Select-String -Pattern "Checkout Error|Error processing cart item|Product not found" -Context 10
```

### Lỗi Database
```powershell
Get-Content storage\logs\laravel-2026-01-14.log | Select-String -Pattern "SQLSTATE|PDOException|QueryException" -Context 5
```

### Lỗi Facebook CAPI
```powershell
Get-Content storage\logs\laravel-2026-01-14.log | Select-String -Pattern "Facebook CAPI Error"
```

## 📝 Log format

Laravel log có format:
```
[YYYY-MM-DD HH:MM:SS] environment.LEVEL: Message
```

Ví dụ:
```
[2026-01-14 23:32:37] local.ERROR: Product not found: 9190
```

## ⚠️ Lưu ý

1. **Thay đổi ngày**: Thay `2026-01-14` bằng ngày bạn muốn xem
2. **Xem log mới nhất**: Luôn xem file có ngày gần nhất
3. **Xóa log cũ**: Nếu log quá lớn, có thể xóa file cũ để giải phóng dung lượng

## 🔧 Lệnh hữu ích khác

### Xem kích thước file log:
```powershell
Get-ChildItem storage\logs\*.log | Select-Object Name, Length, LastWriteTime | Format-Table
```

### Xóa log cũ hơn 7 ngày:
```powershell
Get-ChildItem storage\logs\*.log | Where-Object {$_.LastWriteTime -lt (Get-Date).AddDays(-7)} | Remove-Item
```

### Xem log của ngày hôm nay:
```powershell
$today = Get-Date -Format "yyyy-MM-dd"
Get-Content "storage\logs\laravel-$today.log" -Tail 50
```
