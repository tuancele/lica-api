# 🏭 Inventory Module v2.0

## Hệ thống quản lý kho hàng hiện đại cho E-commerce

### ✨ Tính năng mới

| Feature | Mô tả |
|---------|-------|
| **Single Source of Truth** | Một bảng `inventory_stocks` quản lý tất cả tồn kho |
| **Stock Reservation** | Giữ hàng khi đặt đơn, tự động release khi hết hạn |
| **Audit Trail** | Log đầy đủ mọi biến động kho trong `stock_movements` |
| **Multi-warehouse** | Hỗ trợ nhiều kho hàng |
| **Race Condition Safe** | Sử dụng DB locks để tránh oversell |
| **Event-driven** | Dễ dàng tích hợp notifications, sync marketplace |

---

## 📦 Cài đặt

### Bước 1: Copy files vào project

```bash
# Copy toàn bộ thư mục vào project Laravel
cp -r app/* /path/to/your/project/app/
cp -r database/* /path/to/your/project/database/
cp -r routes/* /path/to/your/project/routes/
cp -r config/* /path/to/your/project/config/
```

### Bước 2: Đăng ký Service Provider

Thêm vào `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\InventoryServiceProvider::class,
],
```

Hoặc nếu dùng Laravel 11+, thêm vào `bootstrap/providers.php`:

```php
return [
    // ...
    App\Providers\InventoryServiceProvider::class,
];
```

### Bước 3: Chạy migrations

```bash
php artisan migrate
```

### Bước 4: Migrate dữ liệu cũ (nếu có)

```bash
php artisan inventory:migrate-legacy-data
```

### Bước 5: Setup Scheduled Jobs

Thêm vào `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Release expired stock reservations every 5 minutes
    $schedule->job(new \App\Jobs\Inventory\ReleaseExpiredReservationsJob)
        ->everyFiveMinutes()
        ->name('inventory:release-expired')
        ->withoutOverlapping();
    
    // Check low stock daily at 8am
    $schedule->job(new \App\Jobs\Inventory\CheckLowStockJob)
        ->dailyAt('08:00')
        ->name('inventory:check-low-stock');
}
```

---

## 🔧 Cấu hình

File `config/inventory.php`:

```php
return [
    // Thời gian giữ hàng cho cart (phút)
    'cart_reservation_minutes' => 30,
    
    // Thời gian giữ hàng cho order pending (giờ)
    'order_reservation_hours' => 24,
    
    // Bật/tắt dual-write (ghi cả hệ thống cũ và mới)
    'dual_write_enabled' => false,
    
    // Default warehouse ID
    'default_warehouse_id' => 1,
    
    // Low stock threshold mặc định
    'default_low_stock_threshold' => 10,
];
```

---

## 📚 API Endpoints

### Stock Queries

```
GET    /api/v2/inventory/stocks                    # Danh sách tồn kho
GET    /api/v2/inventory/stocks/{variantId}        # Chi tiết tồn kho 1 variant
POST   /api/v2/inventory/stocks/check-availability # Kiểm tra còn hàng
GET    /api/v2/inventory/stocks/low-stock          # Danh sách sắp hết hàng
```

### Stock Receipts (Import/Export)

```
GET    /api/v2/inventory/receipts                  # Danh sách phiếu
POST   /api/v2/inventory/receipts/import           # Tạo phiếu nhập
POST   /api/v2/inventory/receipts/export           # Tạo phiếu xuất
POST   /api/v2/inventory/receipts/transfer         # Chuyển kho
POST   /api/v2/inventory/receipts/adjust           # Điều chỉnh
GET    /api/v2/inventory/receipts/{id}             # Chi tiết phiếu
DELETE /api/v2/inventory/receipts/{id}             # Xóa phiếu (draft only)
```

### Warehouses

```
GET    /api/v2/inventory/warehouses                # Danh sách kho
POST   /api/v2/inventory/warehouses                # Tạo kho mới
GET    /api/v2/inventory/warehouses/{id}           # Chi tiết kho
PUT    /api/v2/inventory/warehouses/{id}           # Cập nhật kho
DELETE /api/v2/inventory/warehouses/{id}           # Xóa kho
```

### Stock Movements (History)

```
GET    /api/v2/inventory/movements                 # Lịch sử biến động
GET    /api/v2/inventory/movements/variant/{id}    # Lịch sử theo variant
```

---

## 💡 Sử dụng Service

### Inject Service

```php
use App\Services\Inventory\Contracts\InventoryServiceInterface;

class OrderController extends Controller
{
    public function __construct(
        private InventoryServiceInterface $inventory
    ) {}
}
```

### Kiểm tra tồn kho

```php
// Lấy thông tin stock
$stock = $this->inventory->getStock($variantId);
echo $stock->availableStock;

// Kiểm tra còn hàng
if ($this->inventory->isAvailable($variantId, $quantity)) {
    // Còn hàng
}
```

### Nhập kho

```php
use App\Services\Inventory\DTOs\ImportStockDTO;

$receipt = $this->inventory->import(new ImportStockDTO(
    code: 'PO-001',
    subject: 'Nhập hàng từ NCC ABC',
    warehouseId: 1,
    items: [
        ['variant_id' => 1, 'quantity' => 100, 'unit_price' => 50000],
        ['variant_id' => 2, 'quantity' => 50, 'unit_price' => 75000],
    ],
    createdBy: auth()->id(),
));
```

### Xuất kho

```php
use App\Services\Inventory\DTOs\ExportStockDTO;

$receipt = $this->inventory->export(new ExportStockDTO(
    code: 'EXP-001',
    subject: 'Xuất hàng cho đơn #123',
    warehouseId: 1,
    referenceType: 'order',
    referenceId: 123,
    items: [
        ['variant_id' => 1, 'quantity' => 5, 'unit_price' => 100000],
    ],
    createdBy: auth()->id(),
));
```

### Giữ hàng (Reservation)

```php
use App\Services\Inventory\DTOs\ReserveStockDTO;

// Giữ hàng khi tạo order
$reservation = $this->inventory->reserve(new ReserveStockDTO(
    variantId: 1,
    quantity: 2,
    referenceType: 'order',
    referenceId: $order->id,
    expiresAt: now()->addHours(24),
));

// Xác nhận khi thanh toán (trừ stock thật)
$this->inventory->confirmReservation($reservation->id);

// Hoặc hủy reservation
$this->inventory->releaseReservation($reservation->id);
```

### Tích hợp Order

```php
// Khi order được thanh toán
$this->inventory->deductForOrder($orderId);

// Khi order bị hủy
$this->inventory->restoreForOrder($orderId);

// Khi có return
$this->inventory->processReturn($orderId, [
    ['variant_id' => 1, 'quantity' => 1],
]);
```

---

## 🔄 Migration từ hệ thống cũ

### Cách 1: Dùng command

```bash
php artisan inventory:migrate-legacy-data
```

### Cách 2: Manual

1. Tạo default warehouse
2. Tính toán stock hiện tại từ `product_warehouse`
3. Insert vào `inventory_stocks`
4. Migrate receipts sang `stock_receipts`

---

## 📊 Database Schema

```
warehouses
├── id
├── code (unique)
├── name
├── is_default
└── is_active

inventory_stocks
├── id
├── warehouse_id → warehouses
├── variant_id → variants
├── physical_stock
├── reserved_stock
├── available_stock (computed)
├── flash_sale_hold
├── deal_hold
└── low_stock_threshold

stock_receipts
├── id
├── receipt_code (unique)
├── type (import/export/transfer/adjustment)
├── status (draft/pending/completed/cancelled)
├── from_warehouse_id
├── to_warehouse_id
├── reference_type
├── reference_id
└── created_by

stock_receipt_items
├── id
├── receipt_id → stock_receipts
├── variant_id → variants
├── quantity
├── unit_price
├── stock_before
└── stock_after

stock_movements (audit log)
├── id
├── warehouse_id
├── variant_id
├── movement_type
├── quantity
├── physical_before/after
├── reserved_before/after
├── reference_type/id
└── created_by

stock_reservations
├── id
├── warehouse_id
├── variant_id
├── quantity
├── reference_type
├── reference_id
├── status (active/confirmed/released/expired)
└── expires_at
```

---

## 🧪 Testing

```bash
# Run all inventory tests
php artisan test --filter=Inventory

# Run specific test
php artisan test --filter=InventoryServiceTest
```

---

## 🚀 Hướng dẫn xóa module cũ với Cursor

Sau khi cài đặt module mới thành công, bạn có thể yêu cầu Cursor xóa module cũ:

### Prompt cho Cursor:

```
Hãy giúp tôi xóa module Warehouse cũ và cleanup code:

1. Xóa các files sau:
   - app/Modules/Warehouse/Controllers/EgoodsController.php
   - app/Modules/Warehouse/Controllers/IgoodsController.php  
   - app/Modules/Warehouse/Controllers/WarehouseController.php
   - app/Modules/Warehouse/Views/export/* (toàn bộ thư mục)
   - app/Modules/Warehouse/Views/import/* (toàn bộ thư mục)
   - app/Modules/Warehouse/Helpers/helper.php

2. Giữ lại (KHÔNG XÓA):
   - app/Modules/Warehouse/Models/Warehouse.php (tạm giữ để backward compatible)
   - app/Modules/Warehouse/Models/ProductWarehouse.php (tạm giữ)

3. Cập nhật routes.php:
   - Xóa tất cả routes của export-goods và import-goods cũ
   - Giữ lại route warehouse nếu cần

4. Tìm và thay thế các references đến:
   - countProduct() → sử dụng InventoryService::getStock()
   - countPrice() → sử dụng InventoryService
   - EgoodsController → InventoryController
   - IgoodsController → InventoryController

5. Sau khi xóa, chạy:
   - php artisan route:clear
   - php artisan view:clear
   - php artisan cache:clear
```

---

## 📝 Changelog

### v2.0.0 (2025-xx-xx)
- Complete rewrite với architecture mới
- Single source of truth cho stock
- Stock reservation system
- Full audit trail
- Multi-warehouse support
- API-first design

---

## 📄 License

MIT
