# Hướng dẫn Nâng cấp Frontend Flash Sale & Deal lên Warehouse V2

## Tổng quan nâng cấp

Đã hoàn tất nâng cấp tính năng Flash Sale và Deal từ Warehouse V1 lên Warehouse V2 (Inventory Module).

## Các thay đổi chính

### 1. Controller Updates

#### FlashSaleController
**File:** `app/Modules/FlashSale/Controllers/FlashsaleController.php`

**Thay đổi:**
- ✅ Đã inject `InventoryServiceInterface` (Warehouse V2) vào constructor
- ✅ Thay thế `WarehouseServiceInterface::getVariantStock()` (V1) bằng `InventoryServiceInterface::getStock()` (V2)
- ✅ Sử dụng `StockDTO` object từ V2 thay vì array
- ✅ Lấy thêm field `sellableStock` (tồn khả dụng - FlashSale - Deal)

**Trước đây (V1):**
```php
$warehouseStock = app(\App\Services\Warehouse\WarehouseServiceInterface::class)->getVariantStock($variant->id);
$variant->actual_stock = $warehouseStock['physical_stock'] ?? 0;
$variant->available_stock = $warehouseStock['available_stock'] ?? 0;
```

**Sau khi nâng cấp (V2):**
```php
$stockDto = $this->inventoryService->getStock($variant->id);
$variant->actual_stock = $stockDto->physicalStock;
$variant->available_stock = $stockDto->availableStock;
$variant->sellable_stock = $stockDto->sellableStock; // Mới: tồn kho có thể bán thực sự
```

#### DealController
**File:** `app/Modules/Deal/Controllers/DealController.php`

**Thay đổi:**
- ✅ Tương tự FlashSaleController, đã sử dụng `InventoryServiceInterface` V2
- ✅ Sử dụng `StockDTO` cho tồn kho sản phẩm chính và sản phẩm mua kèm

### 2. View Updates

#### FlashSale Product Rows
**File:** `app/Modules/FlashSale/Views/product_rows.blade.php`

**Thay đổi:**
```php
// Trước đây (V1):
$warehouseStock = app(\App\Services\Warehouse\WarehouseServiceInterface::class)->getVariantStock($variant->id);
$actual_stock = $warehouseStock['physical_stock'] ?? 0;

// Sau khi nâng cấp (V2):
$stockDto = app(\App\Services\Inventory\Contracts\InventoryServiceInterface::class)->getStock($variant->id);
$actual_stock = $stockDto->physicalStock;
$sellable_stock = $stockDto->sellableStock;
```

#### Deal Product Rows
**Files:** 
- `app/Modules/Deal/Views/product_rows.blade.php`
- `app/Modules/Deal/Views/sale_product_rows.blade.php`

**Thay đổi:**
- ✅ Các view này đã sử dụng `actual_stock` từ controller nên không cần thay đổi
- ✅ Controller đã cung cấp dữ liệu V2

### 3. JavaScript - KHÔNG CẦN THAY ĐỔI

**File:** `public/js/marketing-product-search.js`

✅ **KHÔNG cần thay đổi** - File JavaScript này hoàn toàn agnostic, chỉ xử lý UI và gửi request tới backend. Backend (Controller) đã được nâng cấp lên V2.

## API Stock Data Structure Changes

### V1 (Warehouse Service)
```php
$stock = $warehouseService->getVariantStock($variantId);
// Returns array:
// [
//   'physical_stock' => 100,
//   'available_stock' => 80
// ]
```

### V2 (Inventory Service)
```php
$stock = $inventoryService->getStock($variantId);
// Returns StockDTO object:
// {
//   physicalStock: 100,        // Tồn kho vật lý
//   reservedStock: 10,         // Đang giữ cho đơn hàng
//   availableStock: 90,        // Khả dụng (physical - reserved)
//   flashSaleHold: 20,         // Giữ cho Flash Sale
//   dealHold: 10,              // Giữ cho Deal
//   sellableStock: 60,         // Có thể bán (available - flashSale - deal)
//   lowStockThreshold: 10,
//   reorderPoint: 20,
//   averageCost: 150000,
//   lastCost: 160000,
//   locationCode: 'A1-B2-C3'
// }
```

## Validation Logic Changes

### Flash Sale Stock Validation
**Trước (V1):**
- Validate: `number <= physical_stock`
- Warning: `number > available_stock`

**Sau (V2):**
- Validate: `number <= physicalStock`
- Warning: `number > availableStock`
- ✅ Thêm: Hiển thị `sellableStock` để admin biết tồn kho thực sự có thể bán (trừ cả FlashSale và Deal hiện tại)

### Deal Stock Validation
**Tương tự Flash Sale**, sử dụng `StockDTO` từ V2.

## Testing Checklist

### Flash Sale Admin
- [ ] Tạo Flash Sale mới với sản phẩm có variants
- [ ] Kiểm tra validation số lượng Flash Sale <= tồn kho vật lý
- [ ] Kiểm tra warning khi số lượng > tồn kho khả dụng
- [ ] Chỉnh sửa Flash Sale hiện có
- [ ] Xóa Flash Sale

### Deal Admin
- [ ] Tạo Deal mới với sản phẩm chính + mua kèm
- [ ] Kiểm tra validation số lượng Deal <= tồn kho vật lý
- [ ] Chỉnh sửa Deal hiện có
- [ ] Xóa Deal

### User Interface (Frontend Website)
- [ ] Xem sản phẩm Flash Sale trên trang chủ
- [ ] Mua sản phẩm Flash Sale
- [ ] Xem Deal Sốc trên trang chủ
- [ ] Mua sản phẩm Deal

## API Endpoints Used

### Flash Sale
- ✅ `GET /api/v2/inventory/stocks/{variantId}` - Lấy tồn kho chi tiết
- ✅ `POST /api/v2/inventory/stocks/check-availability` - Kiểm tra khả dụng batch

### Deal
- ✅ `GET /api/v2/inventory/stocks/{variantId}` - Lấy tồn kho chi tiết
- ✅ `POST /api/v2/inventory/stocks/check-availability` - Kiểm tra khả dụng batch

## Rollback Plan (Nếu cần)

Nếu gặp vấn đề, có thể rollback bằng cách:

1. **Revert Controller changes:**
```bash
git checkout HEAD -- app/Modules/FlashSale/Controllers/FlashsaleController.php
git checkout HEAD -- app/Modules/Deal/Controllers/DealController.php
```

2. **Revert View changes:**
```bash
git checkout HEAD -- app/Modules/FlashSale/Views/product_rows.blade.php
git checkout HEAD -- app/Modules/Deal/Views/product_rows.blade.php
git checkout HEAD -- app/Modules/Deal/Views/sale_product_rows.blade.php
```

3. **Ensure V1 Warehouse Service is still available** (nếu chưa xóa)

## Benefits of V2

1. ✅ **Multi-warehouse support** - Hỗ trợ đa kho
2. ✅ **Detailed stock tracking** - Theo dõi chi tiết biến động tồn kho
3. ✅ **Flash Sale & Deal stock holding** - Giữ hàng riêng cho Flash Sale/Deal
4. ✅ **Better stock calculation** - Tính toán chính xác hơn:
   - `physicalStock`: Tồn kho vật lý
   - `reservedStock`: Đang giữ cho đơn hàng
   - `availableStock`: Khả dụng (physical - reserved)
   - `flashSaleHold`: Giữ cho Flash Sale
   - `dealHold`: Giữ cho Deal
   - `sellableStock`: Có thể bán thực sự (available - flashSale - deal)
5. ✅ **Stock movement logging** - Ghi log mọi biến động
6. ✅ **Low stock alerts** - Cảnh báo tồn kho thấp
7. ✅ **Inventory valuation** - Báo cáo định giá tồn kho

## Documentation Reference

- **API Documentation:** `API_ADMIN_DOCS.md` (Section: Warehouse Management API V2)
- **Warehouse V2 Overview:** `ECOMMERCE_PRICING_INVENTORY.md`
- **Migration Guide:** `database/migrations/2025_01_01_*.php`

## Maintenance Notes

1. **Cache:** Inventory V2 có hỗ trợ cache (config: `config/inventory.php`)
2. **Logging:** Mọi thay đổi stock được log vào `stock_movements` table
3. **Alerts:** Tự động tạo alert khi tồn kho thấp (config: `config/inventory.php`)

## Contact & Support

Nếu gặp vấn đề, kiểm tra:
1. Log file: `storage/logs/laravel.log`
2. Database: Kiểm tra table `inventory_stocks`, `stock_movements`
3. Config: `config/inventory.php`

## Đồng bộ danh sách sản phẩm hiển thị trong Warehouse (V2)

Nếu trang Warehouse V2 hiển thị thiếu sản phẩm, nguyên nhân thường gặp là **chưa có đủ bản ghi `inventory_stocks` cho tất cả `variants`** (những variant chưa từng nhập/xuất sẽ không có dòng stock để join hiển thị).

Đã bổ sung artisan command để tạo các dòng `inventory_stocks` còn thiếu (stock = 0) cho toàn bộ variants:

- Dry-run (khong ghi DB):
  - `php artisan inventory:sync-stocks --dry-run`
- Thuc thi dong bo (ghi DB, co confirm):
  - `php artisan inventory:sync-stocks`
- Thuc thi khong hoi (non-interactive):
  - `php artisan inventory:sync-stocks --force`
- Neu muon dong bo cho kho cu the:
  - `php artisan inventory:sync-stocks --warehouse_id=1 --force`

## Flash Sale -> Warehouse V2 hold sync

Khi tao/sua Flash Sale, he thong se dong bo so luong (remaining = number - buy) sang Warehouse V2 bang cac ham:
- `allocateStockForPromotion(variantId, qty, 'flash_sale')`
- `releaseStockFromPromotion(variantId, qty, 'flash_sale')`

Muc tieu: cap nhat `inventory_stocks.flash_sale_hold` de Phy/Avail/Sell hien thi dung.

Ghi chu FE: them `data-warehouse-id="1"` (hoac kho mac dinh) vao `<section class="content">` cua Flash Sale/Deal de JS goi dung kho khi lay ton.

---

**Ngày nâng cấp:** 2026-01-21  
**Người thực hiện:** AI Assistant  
**Trạng thái:** ✅ Hoàn thành

