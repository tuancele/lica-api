# Flash Sale Race Condition Protection & Effective Stock Implementation

## Tổng Quan

Đã implement 2 tính năng quan trọng để đạt mức "chuẩn tuyệt đối" cho Flash Sale:

1. **Cơ chế khóa dữ liệu (Race Condition Protection)** - Sử dụng DB transaction và `lockForUpdate()`
2. **Logic Effective Stock** - Tính `min(flash_sale_remaining, warehouse_stock)`

---

## 1. Race Condition Protection

### 1.1 Service Mới: `FlashSaleStockService`

**File:** `app/Services/FlashSale/FlashSaleStockService.php`

Service này xử lý việc cập nhật tồn kho Flash Sale với cơ chế khóa dữ liệu để tránh race condition.

#### Logic Flow:

```
1. Start DB Transaction
2. SELECT * FROM productsales WHERE ... FOR UPDATE (lockForUpdate())
3. Check if buy < number (still available)
4. Check if requested qty <= remaining
5. UPDATE buy = buy + qty
6. Commit Transaction
```

#### Methods:

**`incrementBuy()`** - Tăng `buy` với race condition protection:
```php
public function incrementBuy(
    int $flashSaleId,
    int $productId,
    ?int $variantId,
    int $qty
): array
```

**`decrementBuy()`** - Giảm `buy` (cho order cancellation/refund):
```php
public function decrementBuy(
    int $flashSaleId,
    int $productId,
    ?int $variantId,
    int $qty
): array
```

**`checkAvailability()`** - Kiểm tra tồn kho với lock:
```php
public function checkAvailability(
    int $flashSaleId,
    int $productId,
    ?int $variantId,
    int $requestedQty = 1
): array
```

### 1.2 Cập Nhật CartService

**File:** `app/Services/Cart/CartService.php`

Đã cập nhật method `checkout()` để sử dụng `FlashSaleStockService`:

```php
// Update Flash Sale stock with race condition protection
if ($flash) {
    try {
        // Check if this is variant-specific Flash Sale first
        $variantProductSale = ProductSale::where([
            ['flashsale_id', $flash->id],
            ['product_id', $product->id],
            ['variant_id', $variant->id],
        ])->first();
        
        $variantId = $variantProductSale ? $variant->id : null;
        
        // Use FlashSaleStockService to safely increment buy count
        $this->flashSaleStockService->incrementBuy(
            $flash->id,
            $product->id,
            $variantId,
            $item['qty']
        );
    } catch (\Exception $e) {
        // Log error but don't fail the order creation
        Log::error('Failed to update Flash Sale stock during checkout', [...]);
    }
}
```

---

## 2. Effective Stock Logic

### 2.1 Công Thức

**Effective Stock = min(Flash Sale Remaining, Warehouse Stock)**

- `Flash Sale Remaining = number - buy`
- `Warehouse Stock = current_stock từ warehouse system`

### 2.2 Cập Nhật PriceCalculationService

**File:** `app/Services/PriceCalculationService.php`

#### Thêm Method:

```php
public function calculateEffectiveStock(?int $flashSaleRemaining, int $warehouseStock): int
{
    // If no Flash Sale, use warehouse stock
    if ($flashSaleRemaining === null) {
        return $warehouseStock;
    }
    
    // Return minimum of Flash Sale remaining and warehouse stock
    return min($flashSaleRemaining, $warehouseStock);
}
```

#### Cập Nhật Response:

Tất cả các method tính giá (`calculateProductPrice`, `calculateVariantPrice`) đã được cập nhật để trả về:

```php
'flash_sale_info' => (object) [
    'flashsale_id' => $productSale->flashsale_id,
    'price_sale' => $productSale->price_sale,
    'number' => $productSale->number,
    'buy' => $productSale->buy,
    'remaining' => $flashSaleRemaining, // Flash Sale remaining
    'effective_stock' => $effectiveStock, // ← MỚI: min(remaining, warehouse_stock)
    'warehouse_stock' => $warehouseStock, // ← MỚI: Warehouse stock for reference
],
```

### 2.3 Dependency Injection

`PriceCalculationService` đã được cập nhật để nhận `WarehouseServiceInterface`:

```php
protected ?WarehouseServiceInterface $warehouseService;

public function __construct(?WarehouseServiceInterface $warehouseService = null)
{
    $this->warehouseService = $warehouseService;
}

public function setWarehouseService(WarehouseServiceInterface $warehouseService): void
{
    $this->warehouseService = $warehouseService;
}
```

---

## 3. API Response Format

### 3.1 Product Detail API

**Endpoint:** `GET /api/products/{slug}/detail`

Response sẽ bao gồm:

```json
{
  "success": true,
  "data": {
    "variants": [
      {
        "id": 1,
        "price_info": {
          "type": "flashsale",
          "flash_sale_info": {
            "remaining": 50,
            "effective_stock": 30,  // ← min(50, 30) = 30
            "warehouse_stock": 30
          }
        }
      }
    ]
  }
}
```

### 3.2 Flash Sale Products API

**Endpoint:** `GET /api/products/flash-sale`

Response sẽ bao gồm:

```json
{
  "success": true,
  "data": [
    {
      "price_info": {
        "type": "flashsale",
        "flash_sale_info": {
          "remaining": 100,
          "effective_stock": 50,  // ← min(100, 50) = 50
          "warehouse_stock": 50
        }
      }
    }
  ]
}
```

---

## 4. Frontend Usage

### 4.1 Hiển Thị Tồn Kho

Frontend nên sử dụng `effective_stock` thay vì `remaining` hoặc `warehouse_stock`:

```javascript
// ✅ ĐÚNG: Sử dụng effective_stock
const stock = product.price_info?.flash_sale_info?.effective_stock ?? product.warehouse_stock ?? 0;

// ❌ SAI: Chỉ sử dụng remaining
const stock = product.price_info?.flash_sale_info?.remaining ?? 0;
```

### 4.2 Disable Nút Mua Khi Hết Hàng

```javascript
const isOutOfStock = (product.price_info?.flash_sale_info?.effective_stock ?? 0) <= 0;
if (isOutOfStock) {
    // Disable "Mua ngay" button
}
```

---

## 5. Testing

### 5.1 Test Race Condition

**Scenario:** 2 khách hàng cùng mua sản phẩm cuối cùng của Flash Sale

**Expected:** Chỉ 1 đơn hàng thành công, đơn hàng thứ 2 sẽ nhận lỗi "Sản phẩm Flash Sale đã hết hàng"

**Test Script:**

```php
// Simulate concurrent requests
$flashSaleId = 1;
$productId = 10;
$variantId = 5;
$qty = 1;

// Request 1
$result1 = $flashSaleStockService->incrementBuy($flashSaleId, $productId, $variantId, $qty);
// Should succeed

// Request 2 (concurrent)
$result2 = $flashSaleStockService->incrementBuy($flashSaleId, $productId, $variantId, $qty);
// Should fail with "Sản phẩm Flash Sale đã hết hàng"
```

### 5.2 Test Effective Stock

**Scenario:** Flash Sale có 100 sản phẩm, nhưng warehouse chỉ còn 50

**Expected:** `effective_stock = 50` (min của 100 và 50)

**Test Script:**

```php
$priceInfo = $priceService->calculateVariantPrice($variant);
$effectiveStock = $priceInfo->flash_sale_info->effective_stock;
// Should be 50 (min of Flash Sale remaining and warehouse stock)
```

---

## 6. Migration & Deployment

### 6.1 Không Cần Migration

Không cần migration vì:
- Chỉ thêm logic mới, không thay đổi schema
- `effective_stock` được tính toán động, không lưu trong DB

### 6.2 Deployment Steps

1. **Deploy Code:**
   ```bash
   git pull origin main
   composer install --no-dev --optimize-autoloader
   ```

2. **Clear Cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

3. **Test:**
   - Test race condition với concurrent requests
   - Test effective stock với sản phẩm có warehouse stock < Flash Sale remaining
   - Test API responses có `effective_stock` và `warehouse_stock`

---

## 7. Monitoring & Logging

### 7.1 Logs

`FlashSaleStockService` đã có logging:

```php
Log::info('Flash Sale stock incremented', [
    'flash_sale_id' => $flashSaleId,
    'product_id' => $productId,
    'variant_id' => $variantId,
    'qty' => $qty,
    'buy_before' => $currentBuy,
    'buy_after' => $productSale->buy,
    'remaining' => $productSale->remaining,
]);
```

### 7.2 Error Handling

Nếu cập nhật Flash Sale stock thất bại trong checkout:
- Order vẫn được tạo (để không mất đơn hàng)
- Error được log để xử lý sau
- Admin có thể kiểm tra và cập nhật thủ công nếu cần

---

## 8. Tóm Tắt

### ✅ Đã Implement

1. ✅ **Race Condition Protection:**
   - `FlashSaleStockService` với `lockForUpdate()`
   - DB transaction để đảm bảo atomicity
   - Validation `buy < number` và `qty <= remaining`

2. ✅ **Effective Stock Logic:**
   - Method `calculateEffectiveStock()` trong `PriceCalculationService`
   - Formula: `min(flash_sale_remaining, warehouse_stock)`
   - Trả về trong `flash_sale_info.effective_stock`

3. ✅ **Integration:**
   - `CartService` sử dụng `FlashSaleStockService`
   - `PriceCalculationService` tính `effective_stock`
   - API responses bao gồm `effective_stock` và `warehouse_stock`

### 📝 Cần Làm Tiếp

1. ⚠️ **Frontend:** Cập nhật để sử dụng `effective_stock` thay vì `remaining`
2. ⚠️ **Testing:** Test race condition với concurrent requests
3. ⚠️ **Monitoring:** Monitor logs để phát hiện race condition issues

---

**Tài liệu này mô tả implementation của Race Condition Protection và Effective Stock Logic cho Flash Sale.**
**Ngày tạo:** {{ date('Y-m-d H:i:s') }}
**Phiên bản:** 1.0
