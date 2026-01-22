# WAREHOUSE ACCOUNTING V2 - CHECKLIST & FIXES

## ✅ ĐÃ SỬA

### 1. Menu Sidebar
- ✅ Đã thêm menu "Kho hàng" vào sidebar (dòng 84-93)
- ✅ Submenu: "Nhập/Xuất hàng" và "Tồn kho"
- ✅ Route: `{{route('warehouse.accounting')}}`

### 2. CSS Loading
- ✅ Đã thêm `@stack('styles')` vào layout.blade.php (dòng 37)
- ✅ Đã sửa CSS path từ `asset('admin/css/...')` sang `/public/admin/css/warehouse-accounting.css`
- ✅ File CSS tồn tại tại `public/admin/css/warehouse-accounting.css`

### 3. JavaScript Loading
- ✅ Đã thêm `@stack('scripts')` vào layout.blade.php (trước </body>)
- ✅ View sử dụng `@push('scripts')` để load Select2 và QRCode.js

### 4. API Calls
- ✅ Đã bỏ Authorization header (API Admin dùng session, không cần Bearer token)
- ✅ API endpoints đúng: `/admin/api/v1/warehouse/products/search`, `/products/{id}/variants`, `/variants/{id}/price`

### 5. Service Methods
- ✅ Đã sửa `exportStock()` thành `manualExportStock()` trong StockReceiptService
- ✅ Method signature đúng: `importStock(int, int, string): array` và `manualExportStock(int, int, string): array`

## 📋 KIỂM TRA LẠI

### Files Created/Modified

1. **Service:**
   - ✅ `app/Services/Warehouse/StockReceiptService.php` - OK
   - ✅ Đăng ký trong `AppServiceProvider.php` - OK

2. **Controller:**
   - ✅ `app/Modules/Warehouse/Controllers/WarehouseAccountingController.php` - OK
   - ✅ Error handling với try-catch - OK

3. **View:**
   - ✅ `app/Modules/Warehouse/Views/accounting.blade.php` - OK
   - ✅ CSS path đã sửa - OK
   - ✅ JavaScript đã bỏ Authorization header - OK

4. **CSS:**
   - ✅ `public/admin/css/warehouse-accounting.css` - OK
   - ✅ Print styles với @media print - OK

5. **Routes:**
   - ✅ `app/Modules/Warehouse/routes.php` - OK
   - ✅ Route name: `warehouse.accounting` - OK

6. **Menu:**
   - ✅ `app/Modules/Layout/Views/sidebar.blade.php` - OK
   - ✅ Menu "Kho hàng" với submenu - OK

7. **Layout:**
   - ✅ `app/Modules/Layout/Views/layout.blade.php` - OK
   - ✅ `@stack('styles')` và `@stack('scripts')` - OK

## 🔍 CẦN KIỂM TRA THỰC TẾ

1. **CSS Loading:**
   - Mở DevTools → Network tab
   - Truy cập `/admin/warehouse/accounting`
   - Kiểm tra file `warehouse-accounting.css` có được load không
   - Nếu không load, kiểm tra path: `/public/admin/css/warehouse-accounting.css`

2. **Menu:**
   - Refresh trang admin
   - Kiểm tra sidebar có menu "Kho hàng" không
   - Click vào "Nhập/Xuất hàng" xem có redirect đúng không

3. **Form:**
   - Kiểm tra form hiển thị đúng layout A4 không
   - Test search sản phẩm với Select2
   - Test load variants sau khi chọn sản phẩm
   - Test tính toán thành tiền tự động
   - Test QR Code có hiển thị không

4. **API:**
   - Test API `/admin/api/v1/warehouse/products/search?q=test`
   - Test API `/admin/api/v1/warehouse/products/{id}/variants`
   - Test API `/admin/api/v1/warehouse/variants/{id}/price?type=import`

5. **Save & Complete:**
   - Test lưu phiếu (status = draft)
   - Test complete phiếu (cập nhật tồn kho)
   - Kiểm tra tồn kho có được cập nhật đúng không

## 🐛 CÁC LỖI ĐÃ SỬA

1. ✅ CSS path: Đổi từ `asset('admin/css/...')` sang `/public/admin/css/...`
2. ✅ Layout: Thêm `@stack('styles')` và `@stack('scripts')`
3. ✅ API calls: Bỏ Authorization header (không cần Bearer token)
4. ✅ Service method: Đổi `exportStock()` thành `manualExportStock()`
5. ✅ Error handling: Thêm try-catch khi load receipt

## 📝 NOTES

- CSS chỉ load khi truy cập route `/admin/warehouse/accounting` (dynamic loading)
- API Admin routes dùng `web` + `auth` middleware (session), không cần Bearer token
- InventoryService có cả V2 interface và legacy methods (importStock, manualExportStock)
- StockReceiptService sử dụng legacy methods để tương thích với hệ thống hiện tại


