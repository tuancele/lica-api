# BÁO CÁO PHÂN TÍCH SÂU: MODULE NHẬP/XUẤT HÀNG V2 (ACCOUNTING WAREHOUSE V2)

## 1. PHÂN TÍCH CẤU TRÚC DATABASE

### 1.1. Bảng `stock_receipts` (Phiếu nhập/xuất hàng)

**Schema chính:**
- `id`: Primary key
- `receipt_code`: Mã phiếu (unique, format: PREFIX-YYYYMMDD-XXXXXX)
- `type`: Loại phiếu (`import`, `export`, `transfer`, `adjustment`, `return`)
- `status`: Trạng thái (`draft`, `pending`, `approved`, `completed`, `cancelled`)
- `from_warehouse_id`: Kho xuất (NULL nếu là phiếu nhập)
- `to_warehouse_id`: Kho nhập (NULL nếu là phiếu xuất)
- `reference_type`, `reference_id`, `reference_code`: Tham chiếu đến đơn hàng/PO
- `supplier_id`, `supplier_name`: Thông tin nhà cung cấp
- `customer_id`, `customer_name`: Thông tin khách hàng
- `subject`: Tiêu đề/Nội dung chính
- `content`: Ghi chú chi tiết
- `vat_invoice`: Số hóa đơn VAT
- `total_items`, `total_quantity`, `total_value`: Tổng hợp (cached)
- `created_by`, `approved_by`, `completed_by`, `cancelled_by`: Workflow
- `metadata`: JSON metadata

**Quan hệ:**
- `fromWarehouse()` → `WarehouseV2`
- `toWarehouse()` → `WarehouseV2`
- `items()` → `StockReceiptItem[]`
- `creator()`, `approver()`, `completer()`, `canceller()` → `User`

**Model:** `App\Models\StockReceipt`

### 1.2. Bảng `stock_receipt_items` (Chi tiết phiếu)

**Schema chính:**
- `id`: Primary key
- `receipt_id`: Foreign key → `stock_receipts.id`
- `variant_id`: Foreign key → `variants.id`
- `quantity`: Số lượng
- `unit_price`: Đơn giá
- `total_price`: Thành tiền (stored as `quantity * unit_price`)
- `stock_before`, `stock_after`: Snapshot tồn kho (audit trail)
- `batch_number`: Số lô (optional)
- `manufacturing_date`, `expiry_date`: Ngày SX/HH (optional)
- `serial_numbers`: JSON array (optional)
- `condition`: Tình trạng (`new`, `used`, `damaged`, `refurbished`)
- `notes`: Ghi chú

**Quan hệ:**
- `receipt()` → `StockReceipt`
- `variant()` → `Variant`

**Model:** `App\Models\StockReceiptItem`

### 1.3. Bảng `product_warehouse` (Lịch sử nhập/xuất - Legacy)

**Schema chính:**
- `id`: Primary key
- `warehouse_id`: Foreign key → `warehouses.id` (legacy)
- `variant_id`: Foreign key → `variants.id`
- `price`: Giá
- `qty`: Số lượng (legacy, có thể không chính xác)
- `type`: Loại (`import`, `export`)
- `physical_stock`: Tồn kho vật lý (snapshot mới nhất)
- `flash_sale_stock`: Tồn kho Flash Sale (snapshot)
- `deal_stock`: Tồn kho Deal (snapshot)
- `created_at`, `updated_at`

**Lưu ý quan trọng:**
- Bảng này là **legacy**, được sử dụng để lưu lịch sử nhập/xuất
- `physical_stock` là snapshot tại thời điểm tạo bản ghi
- Hệ thống mới sử dụng `inventory_stocks` làm single source of truth

**Model:** `App\Modules\Warehouse\Models\ProductWarehouse`

### 1.4. Bảng `inventory_stocks` (Single Source of Truth - V2)

**Logic từ WarehouseService:**
- `physical_stock`: Tồn kho vật lý thực tế
- `flash_sale_hold`: Số lượng đã giữ cho Flash Sale
- `deal_hold`: Số lượng đã giữ cho Deal
- `available_stock`: Generated column = `physical_stock - flash_sale_hold - deal_hold`
- `warehouse_id`: ID kho (mặc định = 1)

**Cập nhật:**
- Khi nhập hàng: `InventoryService::importStock()` → tăng `physical_stock`
- Khi xuất hàng: `InventoryService::exportStock()` → giảm `physical_stock`
- Khi tạo Flash Sale/Deal: Giữ tồn kho → tăng `flash_sale_hold`/`deal_hold`

## 2. PHÂN TÍCH WAREHOUSESERVICE VÀ LOGIC TỒN KHO

### 2.1. Logic Tồn Kho Vật Lý (PhyAvailSellAct)

**Công thức tính tồn kho:**

```
physical_stock = Tổng nhập - Tổng xuất (từ inventory_stocks)
flash_sale_stock = SUM(number - buy) của Flash Sale đang active
deal_stock = SUM(qty - buy) của Deal đang active
available_stock = MAX(0, physical_stock - flash_sale_stock - deal_stock)
sellable_stock = available_stock (có thể bán được)
```

**Nguồn dữ liệu:**
1. **Primary:** `inventory_stocks` table (warehouse_id = 1)
2. **Fallback:** `product_warehouse.physical_stock` (snapshot mới nhất)
3. **Legacy:** `variants.stock` (nếu chưa có snapshot)

**Các phương thức quan trọng:**

#### `getInventory(array $filters, int $perPage)`
- Lấy danh sách tồn kho với filter
- Hỗ trợ cả V2 (có `physical_stock` column) và legacy
- Query từ `variants` + `product_warehouse` (left join)

#### `getVariantStock(int $variantId)`
- Lấy thông tin tồn kho chi tiết cho 1 variant
- Query từ `inventory_stocks` (warehouse_id = 1)
- Auto cleanup: Xóa hold nếu Flash Sale/Deal đã kết thúc hoặc đã bán hết

#### `getStockSnapshot(int $variantId)`
- Wrapper gọi `InventoryService::getStock()`
- Trả về array: `physical`, `reserved`, `flash`, `deal`, `available`, `sellable`

#### `createImportReceipt(array $data)`
- Tạo phiếu nhập
- Tạo bản ghi `ProductWarehouse` (type = 'import')
- Gọi `InventoryService::importStock()` để tăng `physical_stock`

#### `createExportReceipt(array $data)`
- Tạo phiếu xuất
- Validate tồn kho: `quantity <= available_stock`
- Tạo bản ghi `ProductWarehouse` (type = 'export')
- Gọi `InventoryService::exportStock()` để giảm `physical_stock`

#### `processOrderStock(int $orderId)`
- Xử lý trừ tồn kho khi đơn hàng được xác nhận
- Logic:
  1. Xác định loại sản phẩm: Deal > Flash Sale > Normal
  2. Trừ `physical_stock` (luôn luôn)
  3. Trừ `flash_sale_hold`/`deal_hold` nếu là Flash Sale/Deal
  4. Tăng `ProductSale.buy`/`SaleDeal.buy`

#### `rollbackOrderStock(int $orderId)`
- Hoàn trả tồn kho khi đơn hàng bị hủy
- Logic:
  1. Tăng lại `physical_stock` (luôn luôn)
  2. Tăng lại `flash_sale_hold`/`deal_hold` **CHỈ KHI** campaign còn active
  3. Giảm `ProductSale.buy`/`SaleDeal.buy`

### 2.2. Tích hợp với InventoryService

**Interface:** `App\Services\Inventory\Contracts\InventoryServiceInterface`

**Các phương thức:**
- `importStock(int $variantId, int $quantity, string $reason)`: Tăng tồn kho
- `exportStock(int $variantId, int $quantity, string $reason)`: Giảm tồn kho
- `getStock(int $variantId)`: Lấy thông tin tồn kho

**Lưu ý:** InventoryService làm việc với `inventory_stocks` table, không phải `product_warehouse`.

## 3. PHÂN TÍCH CẤU TRÚC API ADMIN HIỆN TẠI

### 3.1. Cấu trúc Module

**Location:** `app/Modules/ApiAdmin/`

**Cấu trúc:**
```
ApiAdmin/
├── Controllers/
│   ├── WarehouseController.php (V1 - Legacy)
│   ├── ProductController.php
│   ├── OrderController.php
│   └── ...
├── routes.php
└── (Resources/ - nếu có)
```

### 3.2. Pattern Controller

**Base Controller:** `App\Http\Controllers\Controller`

**Pattern:**
1. Constructor injection: `WarehouseServiceInterface`
2. Methods trả về `JsonResponse`
3. Try-catch với logging
4. Response format:
   ```json
   {
     "success": true/false,
     "data": {...},
     "pagination": {...},
     "message": "...",
     "error": "..."
   }
   ```

### 3.3. Routes Pattern

**File:** `app/Modules/ApiAdmin/routes.php`

**Pattern:**
```php
Route::group([
    'middleware' => ['web', 'auth'],
    'prefix' => 'admin/api',
    'namespace' => 'App\Modules\ApiAdmin\Controllers'
], function () {
    Route::prefix('v1/warehouse')->group(function () {
        // Routes here
    });
});
```

**URL Pattern:**
- List: `GET /admin/api/v1/warehouse/import-receipts`
- Show: `GET /admin/api/v1/warehouse/import-receipts/{id}`
- Create: `POST /admin/api/v1/warehouse/import-receipts`
- Update: `PUT /admin/api/v1/warehouse/import-receipts/{id}`
- Delete: `DELETE /admin/api/v1/warehouse/import-receipts/{id}`

### 3.4. Resources (API Resources)

**Location:** `app/Http/Resources/Warehouse/`

**Các Resource hiện có:**
- `InventoryResource`
- `ImportReceiptResource`
- `ImportReceiptCollection`
- `ExportReceiptResource`
- `ExportReceiptCollection`

## 4. KẾ HOẠCH XÂY DỰNG MODULE V2

### 4.1. Mục tiêu

**Giao diện tạo phiếu chuẩn A4:**
- Không sử dụng popup
- Search sản phẩm/phân loại inline
- Layout chuẩn A4 để in
- Hỗ trợ cả nhập và xuất hàng

### 4.2. Endpoints cần xây dựng

#### 4.2.1. Stock Receipts Management (V2)

**Base URL:** `/admin/api/v2/warehouse/stock-receipts`

**Endpoints:**
1. `GET /admin/api/v2/warehouse/stock-receipts`
   - List phiếu với filter (type, status, date range, keyword)
   - Pagination
   
2. `GET /admin/api/v2/warehouse/stock-receipts/{id}`
   - Chi tiết phiếu kèm items
   
3. `POST /admin/api/v2/warehouse/stock-receipts`
   - Tạo phiếu mới (draft)
   - Validate: items không rỗng, variant_id hợp lệ
   
4. `PUT /admin/api/v2/warehouse/stock-receipts/{id}`
   - Cập nhật phiếu (chỉ khi status = draft/pending)
   - Recalculate totals
   
5. `DELETE /admin/api/v2/warehouse/stock-receipts/{id}`
   - Xóa phiếu (chỉ khi status = draft)
   
6. `POST /admin/api/v2/warehouse/stock-receipts/{id}/approve`
   - Duyệt phiếu (draft → approved)
   
7. `POST /admin/api/v2/warehouse/stock-receipts/{id}/complete`
   - Hoàn thành phiếu (approved → completed)
   - **Quan trọng:** Khi complete, mới thực sự cập nhật tồn kho
   - Gọi `InventoryService::importStock()` hoặc `exportStock()`
   
8. `POST /admin/api/v2/warehouse/stock-receipts/{id}/cancel`
   - Hủy phiếu
   
9. `GET /admin/api/v2/warehouse/stock-receipts/{id}/print`
   - Lấy dữ liệu để in (format A4)

#### 4.2.2. Supporting Endpoints

1. `GET /admin/api/v2/warehouse/products/search`
   - Search sản phẩm inline (keyword, limit)
   - Trả về: id, name, image, slug
   
2. `GET /admin/api/v2/warehouse/products/{productId}/variants`
   - Lấy danh sách variants của sản phẩm
   - Kèm thông tin tồn kho: physical_stock, available_stock
   
3. `GET /admin/api/v2/warehouse/variants/{variantId}/stock`
   - Lấy thông tin tồn kho chi tiết của variant
   
4. `GET /admin/api/v2/warehouse/variants/{variantId}/price`
   - Lấy giá đề xuất (import: last import price, export: variant price)

### 4.3. Validation Logic

**Tạo phiếu nhập:**
- `receipt_code`: Required, unique
- `type`: Required, enum: import/export/transfer/adjustment/return
- `subject`: Required, max 255
- `items`: Required, array, min 1 item
- `items[].variant_id`: Required, exists:variants,id
- `items[].quantity`: Required, integer, min 1
- `items[].unit_price`: Required, numeric, min 0

**Tạo phiếu xuất:**
- Tất cả validation như nhập
- **Thêm:** `items[].quantity <= available_stock` (validate tồn kho)

**Complete phiếu:**
- Status phải = `approved`
- Validate tồn kho (nếu là export)
- Gọi `InventoryService` để cập nhật tồn kho
- Tạo snapshot `stock_before` và `stock_after` trong items

### 4.4. Service Layer

**Tạo Service mới:** `App\Services\Warehouse\StockReceiptService`

**Các phương thức:**
- `listReceipts(array $filters, int $perPage)`
- `getReceipt(int $id)`
- `createReceipt(array $data)`
- `updateReceipt(int $id, array $data)`
- `deleteReceipt(int $id)`
- `approveReceipt(int $id, int $userId)`
- `completeReceipt(int $id, int $userId)` - **Quan trọng:** Cập nhật tồn kho
- `cancelReceipt(int $id, int $userId, ?string $reason)`
- `generateReceiptCode(string $type)`

**Tích hợp với WarehouseService:**
- Sử dụng `WarehouseService::getVariantStock()` để lấy tồn kho
- Sử dụng `InventoryService` để cập nhật tồn kho khi complete

### 4.5. Resources

**Tạo Resources mới:**
- `app/Http/Resources/Warehouse/StockReceiptResource.php`
- `app/Http/Resources/Warehouse/StockReceiptCollection.php`
- `app/Http/Resources/Warehouse/StockReceiptItemResource.php`

### 4.6. Request Validation

**Tạo Form Requests:**
- `app/Http/Requests/Warehouse/StoreStockReceiptRequest.php`
- `app/Http/Requests/Warehouse/UpdateStockReceiptRequest.php`
- `app/Http/Requests/Warehouse/ApproveStockReceiptRequest.php`
- `app/Http/Requests/Warehouse/CompleteStockReceiptRequest.php`
- `app/Http/Requests/Warehouse/CancelStockReceiptRequest.php`

## 5. RỦI RO VÀ LƯU Ý

### 5.1. Rủi ro

1. **Đồng bộ tồn kho:**
   - Cần đảm bảo khi complete phiếu, tồn kho được cập nhật đúng
   - Tránh race condition khi nhiều phiếu cùng complete
   - Sử dụng DB transaction và lock

2. **Legacy compatibility:**
   - Hệ thống cũ sử dụng `Warehouse` (bảng `warehouses`)
   - Hệ thống mới sử dụng `StockReceipt` (bảng `stock_receipts`)
   - Cần đảm bảo không phá vỡ logic cũ

3. **Validation tồn kho:**
   - Khi tạo phiếu xuất, cần validate tồn kho
   - Nhưng chỉ khi complete mới trừ tồn kho
   - Có thể xảy ra trường hợp: Tạo phiếu xuất → Tồn kho đủ → Complete → Tồn kho không đủ (do phiếu khác đã complete trước)

### 5.2. Giải pháp

1. **Lock khi complete:**
   ```php
   DB::transaction(function () use ($receipt) {
       foreach ($receipt->items as $item) {
           $inventoryStock = InventoryStock::where('variant_id', $item->variant_id)
               ->where('warehouse_id', 1)
               ->lockForUpdate()
               ->first();
           
           // Validate và update
       }
   });
   ```

2. **Validate lại khi complete:**
   - Khi complete, validate lại tồn kho
   - Nếu không đủ, throw exception và không complete

3. **Audit trail:**
   - Lưu `stock_before` và `stock_after` trong `stock_receipt_items`
   - Dễ dàng trace và debug

## 6. KẾT LUẬN

### 6.1. Tóm tắt

1. **Database:** Đã có sẵn `stock_receipts` và `stock_receipt_items`
2. **Logic tồn kho:** Đã có sẵn trong `WarehouseService` và `InventoryService`
3. **API Admin:** Đã có pattern và structure sẵn
4. **Cần xây dựng:**
   - Controller V2: `StockReceiptController`
   - Service: `StockReceiptService`
   - Resources: `StockReceiptResource`, `StockReceiptItemResource`
   - Form Requests: Validation
   - Routes: Đăng ký routes V2

### 6.2. Next Steps

1. Tạo `StockReceiptService` với logic business
2. Tạo `StockReceiptController` với các endpoints
3. Tạo Resources để format response
4. Tạo Form Requests để validate
5. Đăng ký routes trong `app/Modules/ApiAdmin/routes.php`
6. Test và cập nhật `API_ADMIN_DOCS.md`

---

**Ngày tạo:** 2025-01-21  
**Phiên bản:** 1.0  
**Trạng thái:** Phân tích hoàn thành, sẵn sàng implement

