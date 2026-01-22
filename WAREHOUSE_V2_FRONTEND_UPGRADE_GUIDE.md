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

## Logic Xử Lý Kho Hàng Tập Trung (WarehouseService V2)

### Tổng quan

Toàn bộ logic xử lý kho hàng đã được tập trung hóa tại `WarehouseService` với nguồn dữ liệu duy nhất là bảng `inventory_stocks`. Hệ thống không còn sử dụng `product_warehouse` để tính toán tồn kho.

### 1. Nguồn Dữ Liệu Kho (Single Source of Truth)

**File:** `app/Services/Warehouse/WarehouseService.php`

- ✅ **Bảng chính:** `inventory_stocks` (warehouse_id = 1)
- ✅ **Các cột quan trọng:**
  - `physical_stock`: Tồn kho vật lý thực tế
  - `reserved_stock`: Tồn kho đang giữ cho đơn hàng
  - `available_stock`: Tồn kho khả dụng (GENERATED COLUMN = `GREATEST(0, physical_stock - reserved_stock)`)
  - `flash_sale_hold`: Số lượng giữ cho Flash Sale (lifetime tracking)
  - `deal_hold`: Số lượng giữ cho Deal (lifetime tracking)

**Lưu ý quan trọng:**
- `available_stock` là GENERATED COLUMN, không thể UPDATE trực tiếp
- MySQL tự động tính lại khi `physical_stock` hoặc `reserved_stock` thay đổi
- Không được gọi `increment('available_stock')` hoặc `decrement('available_stock')`

### 2. Lấy Tồn Kho (`getVariantStock`)

**Method:** `WarehouseService::getVariantStock(int $variantId): array`

**Logic:**
1. Query `inventory_stocks` theo `variant_id` và `warehouse_id = 1`
2. **Auto-cleanup:** Tự động xóa `flash_sale_hold` hoặc `deal_hold` nếu:
   - Không còn Flash Sale/Deal đang active
   - Flash Sale/Deal đã bán hết (buy >= number)
3. Trả về array với các giá trị:
   ```php
   [
       'physical_stock' => (int),
       'flash_sale_stock' => (int),  // flash_sale_hold
       'deal_stock' => (int),        // deal_hold
       'available_stock' => (int),   // available_stock (generated)
   ]
   ```

**Ví dụ:**
```php
$warehouseService = app(\App\Services\Warehouse\WarehouseServiceInterface::class);
$stock = $warehouseService->getVariantStock($variantId);
// Returns: ['physical_stock' => 100, 'flash_sale_stock' => 20, 'deal_stock' => 10, 'available_stock' => 70]
```

### 3. Trừ Kho Khi Đặt Hàng (`processOrderStock`)

**Method:** `WarehouseService::processOrderStock(int $orderId): bool`

**File tích hợp:** `app/Themes/Website/Controllers/CartController.php`

**Luồng xử lý:**
1. Được gọi trong `CartController::postCheckout()` sau khi tạo Order và OrderDetail
2. Nằm trong DB transaction để đảm bảo atomicity
3. Xử lý từng `OrderDetail`:

**Logic trừ kho:**
- **Tất cả sản phẩm:**
  - Trừ `physical_stock` (luôn luôn)
  
- **Sản phẩm Flash Sale:**
  - Trừ `flash_sale_hold` (lifetime tracking)
  - Tăng `ProductSale.buy` (real-time tracking)
  - Sử dụng `productsale_id` từ `OrderDetail` để xác định
  
- **Sản phẩm Deal:**
  - Trừ `deal_hold` (lifetime tracking)
  - Tăng `SaleDeal.buy` (real-time tracking)
  - Sử dụng `dealsale_id` từ `OrderDetail` để xác định

**Ví dụ code trong CartController:**
```php
// Trong DB::transaction()
foreach ($processedItems as $processed) {
    // ... Tìm active ProductSale/SaleDeal ...
    
    OrderDetail::insert([
        // ... other fields ...
        'dealsale_id' => $dealsale_id,
        'productsale_id' => $productsale_id, // Lưu để rollback sau này
    ]);
}

// Gọi trừ kho tập trung
$this->warehouseService->processOrderStock($order_id);
```

**Lưu ý:**
- Sử dụng `lockForUpdate()` để tránh race condition
- Kiểm tra `available_stock >= quantity` trước khi trừ
- Log chi tiết `before` và `after` values cho debugging

### 4. Hoàn Kho Khi Hủy Đơn (`rollbackOrderStock`)

**Method:** `WarehouseService::rollbackOrderStock(int $orderId): bool`

**File tích hợp:** `app/Modules/Order/Controllers/OrderController.php`

**Luồng xử lý:**
1. Được gọi khi Order status chuyển sang trạng thái hủy (status = 2 hoặc 4)
2. Xử lý từng `OrderDetail`:

**Logic hoàn kho:**
- **Tất cả sản phẩm:**
  - Cộng lại `physical_stock` (luôn luôn)
  
- **Sản phẩm Flash Sale:**
  - **Nếu campaign còn active:** Cộng lại `flash_sale_hold` (trả lại suất mua cho chương trình)
  - **Nếu campaign đã kết thúc:** KHÔNG cộng lại `flash_sale_hold` (chương trình đã kết thúc)
  - **Luôn luôn:** Giảm `ProductSale.buy` (để báo cáo chính xác)
  
- **Sản phẩm Deal:**
  - **Nếu campaign còn active:** Cộng lại `deal_hold` (trả lại suất mua cho chương trình)
  - **Nếu campaign đã kết thúc:** KHÔNG cộng lại `deal_hold` (chương trình đã kết thúc)
  - **Luôn luôn:** Giảm `SaleDeal.buy` (để báo cáo chính xác)

**Ví dụ code trong OrderController:**
```php
if (in_array((int) $req->status, $cancelStatuses, true)) {
    try {
        $warehouseService = app(\App\Services\Warehouse\WarehouseServiceInterface::class);
        $warehouseService->rollbackOrderStock($order->id);
        // ... createImportReceiptFromOrder($order);
    } catch (\Exception $e) {
        DB::rollBack();
        // ... error handling ...
    }
}
```

**Lưu ý quan trọng:**
- Chỉ restore `hold` nếu campaign còn active (đảm bảo suất mua được trả lại cho chương trình)
- Luôn decrement `buy` để báo cáo chính xác, kể cả khi campaign đã kết thúc
- Sử dụng `productsale_id`/`dealsale_id` từ `OrderDetail` làm source of truth (không query lại)

### 5. Lưu Trữ Promotion ID trong OrderDetail

**File:** `app/Modules/Order/Models/OrderDetail.php`

**Các trường mới:**
- `productsale_id`: ID của `ProductSale` record (nullable)
- `dealsale_id`: ID của `SaleDeal` record (nullable)
- `deal_id`: ID của `Deal` record (nullable)
- `is_flash_sale`: Flag đánh dấu sản phẩm Flash Sale (nullable)
- `is_deal`: Flag đánh dấu sản phẩm Deal (nullable)

**Migration:** `database/migrations/2026_01_22_101751_add_productsale_id_to_orderdetail_table.php`

**Mục đích:**
- Lưu trữ thông tin promotion tại thời điểm đặt hàng
- Cho phép rollback chính xác ngay cả khi promotion đã kết thúc
- Hỗ trợ báo cáo và audit trail

### 6. Logic Hiển Thị Tồn Kho (Stock Display Priority)

**File:** `app/Modules/Product/Controllers/ProductController.php`

**Priority logic:**
1. **Flash Sale:** Nếu có Flash Sale active → Hiển thị `flash_sale_stock`
2. **Deal:** Nếu có Deal active → Hiển thị `deal_stock`
3. **Available:** Nếu không có promotion → Hiển thị `available_stock`

**Công thức tính:**
```php
$stockDisplay = 0;
if ($flashSaleStock > 0) {
    $stockDisplay = $flashSaleStock; // Priority 1: Flash Sale
} elseif ($dealStock > 0) {
    $stockDisplay = $dealStock;      // Priority 2: Deal
} else {
    $stockDisplay = $availableStock; // Priority 3: Available
}
```

**Lưu ý:**
- `physical_stock` là giá trị khởi nguyên để tính toán, KHÔNG BAO GIỜ là giá trị hiển thị
- Chỉ hiển thị `physical_stock` trong Admin Warehouse interface

### 7. Auto-Cleanup Logic

**Trong `getVariantStock()`:**

Tự động xóa `flash_sale_hold` hoặc `deal_hold` nếu:
- Không còn Flash Sale/Deal active (status != '1' hoặc ngoài thời gian start-end)
- Flash Sale/Deal đã bán hết (buy >= number)

**Mục đích:**
- Đảm bảo hiển thị chính xác tồn kho
- Tránh hiển thị hold cho promotion đã kết thúc
- Tự động dọn dẹp dữ liệu không hợp lệ

### 8. Database Schema

**Bảng `inventory_stocks`:**
```sql
CREATE TABLE `inventory_stocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `physical_stock` int NOT NULL DEFAULT '0',
  `reserved_stock` int NOT NULL DEFAULT '0',
  `available_stock` int GENERATED ALWAYS AS (GREATEST(0, physical_stock - reserved_stock)) STORED,
  `flash_sale_hold` int NOT NULL DEFAULT '0',
  `deal_hold` int NOT NULL DEFAULT '0',
  -- ... other columns ...
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_stocks_warehouse_variant_unique` (`warehouse_id`,`variant_id`),
  KEY `idx_inventory_stocks_variant` (`variant_id`),
  KEY `idx_inventory_stocks_warehouse` (`warehouse_id`)
);
```

**Bảng `orderdetail`:**
```sql
ALTER TABLE `orderdetail` 
ADD COLUMN `productsale_id` int unsigned NULL AFTER `dealsale_id`,
ADD INDEX `idx_orderdetail_productsale` (`productsale_id`);
```

### 9. Logging và Debugging

**Tất cả operations đều được log:**
- `processOrderStock`: Log `before` và `after` values cho mỗi item
- `rollbackOrderStock`: Log `before` và `after` values, campaign status
- `getVariantStock`: Log auto-cleanup actions

**Log location:** `storage/logs/laravel.log`

**Search patterns:**
- `WarehouseService: processOrderStock`
- `WarehouseService: rollbackOrderStock`
- `WarehouseService: Auto-cleared invalid`

### 10. Testing Checklist

**Đặt hàng:**
- [ ] Đặt hàng sản phẩm thường → Kiểm tra `physical_stock` giảm
- [ ] Đặt hàng Flash Sale → Kiểm tra `physical_stock` và `flash_sale_hold` giảm, `ProductSale.buy` tăng
- [ ] Đặt hàng Deal → Kiểm tra `physical_stock` và `deal_hold` giảm, `SaleDeal.buy` tăng
- [ ] Kiểm tra `OrderDetail.productsale_id` và `OrderDetail.dealsale_id` được lưu đúng

**Hủy đơn:**
- [ ] Hủy đơn Flash Sale (campaign còn active) → Kiểm tra `physical_stock` và `flash_sale_hold` tăng, `ProductSale.buy` giảm
- [ ] Hủy đơn Flash Sale (campaign đã kết thúc) → Kiểm tra chỉ `physical_stock` tăng, `flash_sale_hold` KHÔNG tăng, `ProductSale.buy` giảm
- [ ] Hủy đơn Deal (tương tự Flash Sale)

**Hiển thị:**
- [ ] Sản phẩm có Flash Sale → Hiển thị `flash_sale_stock`
- [ ] Sản phẩm có Deal (không có Flash Sale) → Hiển thị `deal_stock`
- [ ] Sản phẩm thường → Hiển thị `available_stock`

**Auto-cleanup:**
- [ ] Xóa Flash Sale → Kiểm tra `flash_sale_hold` tự động về 0
- [ ] Flash Sale bán hết → Kiểm tra `flash_sale_hold` tự động về 0

### 11. Commands và Utilities

**Reset Stock History:**
```bash
# Dry-run (xem trước)
php artisan stock:reset-history --dry-run

# Thực thi (có xác nhận)
php artisan stock:reset-history

# Thực thi (bỏ qua xác nhận)
php artisan stock:reset-history --confirm
```

**Sync Inventory Stocks:**
```bash
# Đồng bộ tạo inventory_stocks cho variants còn thiếu
php artisan inventory:sync-stocks --force
```

### 12. Best Practices

1. **Luôn sử dụng `WarehouseService`** thay vì query trực tiếp `inventory_stocks`
2. **Không UPDATE `available_stock`** trực tiếp (là GENERATED COLUMN)
3. **Luôn sử dụng DB transaction** khi tạo Order và gọi `processOrderStock`
4. **Lưu `productsale_id`/`dealsale_id`** trong `OrderDetail` để rollback chính xác
5. **Kiểm tra campaign active status** trước khi restore hold trong rollback
6. **Sử dụng `lockForUpdate()`** khi cần thread-safety

---

**Ngày nâng cấp:** 2026-01-21  
**Ngày cập nhật logic kho:** 2026-01-22  
**Người thực hiện:** AI Assistant  
**Trạng thái:** ✅ Hoàn thành

