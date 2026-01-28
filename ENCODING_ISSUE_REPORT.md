# BÁO CÁO PHÂN TÍCH LỖI ENCODING TIẾNG VIỆT

**Ngày:** 2026-01-27  
**Trạng thái:** ⚠️ NGHIÊM TRỌNG - Dữ liệu đã bị mất dấu

---

## 🔍 PHÂN TÍCH SÂU (DEEP DIVE)

### 1. Kết quả kiểm tra hệ thống

#### ✅ Cấu hình đúng:
- **Database charset:** `utf8mb4` ✓
- **Database collation:** `utf8mb4_unicode_ci` ✓
- **Table charset:** `utf8mb4_unicode_ci` ✓
- **Column charset:** `utf8mb4_unicode_ci` ✓
- **PHP default_charset:** `UTF-8` ✓
- **PDO connection:** Đã set `SET NAMES utf8mb4` ✓
- **Response headers:** Đã có middleware SetCharset ✓

#### ❌ Vấn đề phát hiện:
- **Dữ liệu trong database đã bị mất dấu tiếng Việt**
- Các ký tự tiếng Việt đã bị thay thế bằng `?` (0x3F)
- **13,167 records** bị ảnh hưởng:
  - `posts.name`: 6,592 records
  - `posts.content`: 6,537 records
  - `posts.description`: 15 records
  - `brands.name`: 23 records

### 2. Nguyên nhân

Dữ liệu đã bị lưu sai encoding từ trước khi hệ thống được cấu hình đúng:
- Dữ liệu có thể đã được import/lưu với charset `latin1` hoặc `cp1252`
- Khi MySQL cố gắng lưu ký tự tiếng Việt với charset sai, nó thay thế bằng `?`
- **Đây là lỗi KHÔNG THỂ PHỤC HỒI** vì dữ liệu gốc đã mất

### 3. Bằng chứng

```
Product Name (hex): 4b656d2047693f6d2054683f6d...
                    Kem Gi?m Th?m...
                    
Expected:          Kem Giảm Thâm...
Actual:            Kem Gi?m Th?m...
```

- Hex `3F` = ký tự `?`
- Không có pattern UTF-8 của tiếng Việt trong hex data
- Dữ liệu đã bị mất từ khi lưu vào database

---

## 💡 GIẢI PHÁP

### Giải pháp 1: Khôi phục từ Backup (KHUYẾN NGHỊ)

Nếu có backup với encoding đúng:
1. Export dữ liệu từ backup với charset `utf8mb4`
2. Import lại vào database hiện tại
3. Đảm bảo connection charset là `utf8mb4` khi import

**Command:**
```bash
# Export từ backup
mysqldump -u user -p --default-character-set=utf8mb4 database_name > backup.sql

# Import lại
mysql -u user -p --default-character-set=utf8mb4 database_name < backup.sql
```

### Giải pháp 2: Pattern Matching (GIẢI PHÁP TẠM THỜI)

Sử dụng pattern matching để fix một số từ phổ biến:

**Chạy script:**
```bash
php fix_vietnamese_encoding.php --fix
```

**Lưu ý:**
- Chỉ fix được một phần nhỏ dữ liệu
- Cần backup trước khi chạy
- Cần review và sửa thủ công phần còn lại

### Giải pháp 3: Re-enter Data (CHO DỮ LIỆU QUAN TRỌNG)

Đối với dữ liệu quan trọng:
1. Xác định các records quan trọng
2. Re-enter thủ công với encoding đúng
3. Sử dụng admin panel hoặc import CSV với UTF-8

### Giải pháp 4: Export/Import từ Source

Nếu có source data gốc (CSV, Excel, etc.):
1. Đảm bảo file source là UTF-8
2. Export dữ liệu hiện tại để mapping
3. Import lại với encoding đúng

---

## 🛠️ CÁC THAY ĐỔI ĐÃ THỰC HIỆN

### 1. Database Configuration
- ✅ Thêm `PDO::MYSQL_ATTR_INIT_COMMAND` để set charset khi connect
- ✅ Migration convert tables sang `utf8mb4`

### 2. Middleware
- ✅ Tạo `SetCharset` middleware để set Content-Type header
- ✅ Đăng ký vào `web` middleware group

### 3. Scripts
- ✅ `check_encoding.php` - Kiểm tra cấu hình encoding
- ✅ `check_data_encoding.php` - Phân tích dữ liệu
- ✅ `fix_vietnamese_encoding.php` - Script fix pattern matching

---

## 📋 CHECKLIST KHẮC PHỤC

- [ ] Backup database hiện tại
- [ ] Kiểm tra xem có backup với encoding đúng không
- [ ] Nếu có backup: Import lại với charset đúng
- [ ] Nếu không có backup: Chạy pattern matching fix
- [ ] Review và sửa thủ công dữ liệu quan trọng
- [ ] Test hiển thị trên website
- [ ] Đảm bảo dữ liệu mới được lưu với encoding đúng

---

## ⚠️ LƯU Ý QUAN TRỌNG

1. **Dữ liệu đã bị mất không thể phục hồi hoàn toàn**
2. **Pattern matching chỉ fix được một phần**
3. **Cần backup trước khi chạy bất kỳ script fix nào**
4. **Đảm bảo dữ liệu mới được lưu với encoding đúng**

---

## 🔄 NGĂN CHẶN TƯƠNG LAI

1. ✅ Database charset đã được cấu hình đúng
2. ✅ PDO connection đã set charset
3. ✅ Response headers đã có charset
4. ⚠️ Cần đảm bảo khi import dữ liệu mới phải dùng UTF-8
5. ⚠️ Cần kiểm tra encoding của file CSV/Excel trước khi import

---

**Kết luận:** Hệ thống đã được cấu hình đúng encoding, nhưng dữ liệu cũ đã bị mất dấu. Cần khôi phục từ backup hoặc re-enter dữ liệu quan trọng.

