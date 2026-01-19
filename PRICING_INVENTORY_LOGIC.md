## Remove legacy sale price - migrate to Marketing-only pricing

### Scope

- Legacy "sale" field is deprecated across Product/Variant/Admin UI.
- Database columns are NOT removed to avoid breaking existing queries.
- Pricing must be computed by Marketing channels only.

### Single Source of Truth (Pricing)

Pricing priority:

- Flash Sale
- Marketing Campaign (Promo)
- Deal (only for deal bundle sale items, and only when current price type is normal)
- Base price (variants.price)

Rules:

- Never read `variants.sale` or `posts.sale` for pricing.
- If no Flash Sale / Campaign applies, show one price only (base price).

### Refactor notes (2026-01-19)

- Models:
  - Removed `sale` from `Variant::$fillable`.
  - Removed legacy sale logic from `Product::getPriceInfoAttribute`.
- Admin UI:
  - Removed `<input name="sale">` from Product create/edit and variant create view.
- Requests/Validation:
  - Removed `sale` validation rules and normalization.
- Services:
  - `PriceCalculationService`: removed legacy sale branch.
  - `WarehouseService`: export suggested price uses base price only.
  - `ProductService`: stopped writing `sale` during create/update/sync variants.
- Frontend:
  - Helper functions (`checkSale`, `getPrice`, `getVariantFinalPrice`, `getSale`) ignore legacy sale.
  - Variant price ajax in `HomeController` uses `PriceEngineServiceInterface::calculateDisplayPrice`.

### Compatibility

- Keep DB columns for now.
- Any remaining old templates that pass `$product->sale` are tolerated but ignored by helpers.
# Logic Tính Giá và Quản lý Tồn kho - Hệ thống E-commerce

## Tổng quan

Tài liệu này mô tả logic tính giá và quản lý tồn kho cho hệ thống E-commerce, bao gồm:
- **Price Engine**: Tính toán giá hiển thị theo độ ưu tiên
- **Inventory Management**: Quản lý tồn kho vật lý và tồn kho ảo Flash Sale

---

## 1. Phân cấp Giá (Price Engine)

### 1.1 Độ ưu tiên tính giá

Hệ thống tính giá theo thứ tự ưu tiên sau:

**Priority 1: Flash Sale**
- Điều kiện: `Current_Time` nằm trong `Flashsale_Window` VÀ `Flashsale_Stock > 0`
- Nếu `flash_stock_sold >= flash_stock_limit`: Tự động chuyển sang Priority 2

**Priority 2: Promotion/Marketing Campaign**
- Điều kiện: `Current_Time` nằm trong `Promotion_Window`
- Áp dụng giá khuyến mãi từ Marketing Campaign

**Priority 3: Base Price (Giá gốc)**
- Lấy giá gốc từ bảng `variants.price` hoặc `product.price`

### 1.2 Sử dụng PriceEngineService

```php
use App\Services\Pricing\PriceEngineServiceInterface;

// Tính giá cho sản phẩm
$priceInfo = $priceEngine->calculateDisplayPrice($productId, $variantId);

// Kết quả:
[
    'price' => 150000,              // Giá hiển thị
    'original_price' => 200000,     // Giá gốc
    'type' => 'flashsale',          // Loại giá: flashsale|promotion|normal
    'label' => 'Flash Sale',        // Nhãn hiển thị
    'discount_percent' => 25,       // Phần trăm giảm giá
    'flash_sale_id' => 1,           // ID Flash Sale (nếu có)
    'product_sale_id' => 5,         // ID ProductSale (nếu có)
    'remaining_stock' => 50,        // Số lượng còn lại (nếu Flash Sale)
]
```

### 1.3 Ví dụ sử dụng trong Controller

```php
public function getProductPrice(Request $request, int $productId)
{
    $variantId = $request->get('variant_id');
    $priceInfo = $this->priceEngine->calculateDisplayPrice($productId, $variantId);
    
    return response()->json([
        'success' => true,
        'data' => $priceInfo
    ]);
}
```

---

## 2. Logic Tồn kho (Inventory Management)

### 2.1 Hai loại tồn kho

**Physical Stock (S_phy)**: Tổng số lượng thực tế trong kho
- Lấy từ Warehouse System: `import_total - export_total`

**Flash Sale Virtual Stock (S_flash)**: Số lượng được "cắt" ra dành riêng cho Flash Sale
- `S_flash = flash_stock_limit - flash_stock_sold`
- `flash_stock_limit` = `ProductSale.number`
- `flash_stock_sold` = `ProductSale.buy`

### 2.2 Công thức vận hành

**Khi tạo Flash Sale:**
```
S_flash = N (flash_stock_limit)
Tồn kho khả dụng để bán thường = S_phy - S_flash
```

**Khi User mua Flash Sale:**
```
S_flash giảm (buy tăng)
S_phy giảm (tạo export receipt)
```

**Khi S_flash = 0:**
```
Giá tự động nhảy về giá Promotion
Đơn hàng tiếp theo trừ trực tiếp vào S_phy
```

### 2.3 Sử dụng InventoryService

```php
use App\Services\Inventory\InventoryServiceInterface;

// Tính tồn kho khả dụng
$availableStock = $inventoryService->getAvailableStock($productId, $variantId);
// Kết quả: S_phy - S_flash

// Xử lý đơn hàng
$result = $inventoryService->processOrder([
    [
        'product_id' => 10,
        'variant_id' => 5,
        'quantity' => 2,
        'order_type' => 'flashsale' // hoặc 'normal'
    ]
]);

// Kiểm tra khi tạo Flash Sale
$validation = $inventoryService->validateFlashSaleStock(
    $productId, 
    $variantId, 
    $flashStockLimit
);
```

### 2.4 Ví dụ xử lý đơn hàng

```php
public function processOrder(Request $request)
{
    $orderItems = [];
    
    foreach ($request->items as $item) {
        // Tính giá để xác định order_type
        $priceInfo = $this->priceEngine->calculateDisplayPrice(
            $item['product_id'],
            $item['variant_id'] ?? null
        );
        
        $orderItems[] = [
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'] ?? null,
            'quantity' => $item['quantity'],
            'order_type' => $priceInfo['type'] === 'flashsale' ? 'flashsale' : 'normal',
        ];
    }
    
    // Xử lý đơn hàng và trừ tồn kho
    $result = $this->inventoryService->processOrder($orderItems);
    
    if (!$result['success']) {
        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 400);
    }
    
    return response()->json([
        'success' => true,
        'message' => 'Xử lý đơn hàng thành công'
    ]);
}
```

---

## 3. Ràng buộc và Xử lý Race Condition

### 3.1 Atomic Operations (Thao tác nguyên tử)

Tất cả thao tác trừ tồn kho đều sử dụng **Database Transaction** và **Row Locking**:

```php
// Sử dụng Row Locking để tránh Race Condition
$productSale = ProductSale::where('id', $productSale->id)
    ->lockForUpdate()  // Khóa hàng để tránh concurrent access
    ->first();

// Cập nhật trong transaction
DB::beginTransaction();
try {
    $productSale->buy += $quantity;
    $productSale->save();
    $this->warehouseService->deductStock($variantId, $quantity);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### 3.2 Validation khi tạo Flash Sale

Khi tạo Flash Sale, hệ thống kiểm tra:
- `total_stock >= flash_stock_limit`
- Nếu không đủ, trả về lỗi 422

```php
$validation = $this->inventoryService->validateFlashSaleStock(
    $productId,
    $variantId,
    $flashStockLimit
);

if (!$validation['valid']) {
    return response()->json([
        'success' => false,
        'message' => $validation['message']
    ], 422);
}
```

---

## 4. Cấu trúc Database

### 4.1 Bảng liên quan

**Product (posts):**
- `id`: Product ID
- `original_price`: Giá gốc (lấy từ variant.price)

**Promotion (marketing_campaigns + marketing_campaign_products):**
- `id`: Campaign ID
- `product_id`: Product ID
- `price`: Giá khuyến mãi
- `start_at`: Thời gian bắt đầu
- `end_at`: Thời gian kết thúc

**FlashSale (flashsales + productsales):**
- `id`: Flash Sale ID
- `product_id`: Product ID
- `variant_id`: Variant ID (nullable)
- `flash_price`: Giá Flash Sale (`price_sale`)
- `flash_stock_limit`: Số lượng giới hạn (`number`)
- `flash_stock_sold`: Số lượng đã bán (`buy`)
- `start_time`: Thời gian bắt đầu (`start`)
- `end_time`: Thời gian kết thúc (`end`)

**DealShock (deals + product_deals + sale_deals):**
- `id`: Deal ID
- `product_id`: Sản phẩm chính
- `gift_product_id`: Sản phẩm tặng kèm
- `is_active`: Trạng thái

### 4.2 Mapping với Database hiện tại

| Logic Field | Database Field | Table |
|------------|----------------|-------|
| `original_price` | `variants.price` | `variants` |
| `promo_price` | `marketing_campaign_products.price` | `marketing_campaign_products` |
| `flash_price` | `productsales.price_sale` | `productsales` |
| `flash_stock_limit` | `productsales.number` | `productsales` |
| `flash_stock_sold` | `productsales.buy` | `productsales` |
| `total_stock` | `import_total - export_total` | Warehouse System |

---

## 5. API Endpoints

### 5.1 Tính giá sản phẩm

**GET /api/price/{productId}**

Query Parameters:
- `variant_id` (optional): Variant ID

Response:
```json
{
  "success": true,
  "data": {
    "price": 150000,
    "original_price": 200000,
    "type": "flashsale",
    "label": "Flash Sale",
    "discount_percent": 25,
    "remaining_stock": 50
  }
}
```

### 5.2 Xử lý đơn hàng

**POST /api/orders/process**

Request Body:
```json
{
  "items": [
    {
      "product_id": 10,
      "variant_id": 5,
      "quantity": 2
    }
  ]
}
```

Response:
```json
{
  "success": true,
  "message": "Xử lý đơn hàng thành công",
  "data": {
    "items": [...],
    "flash_sale_exhausted": false
  }
}
```

---

## 6. Luồng xử lý đơn hàng

### 6.1 Đơn hàng Flash Sale

```
1. User chọn sản phẩm → Gọi calculateDisplayPrice()
   → Trả về: type = 'flashsale', price = flash_price

2. User đặt hàng → Gọi processOrder()
   → Kiểm tra: flash_stock_sold + quantity <= flash_stock_limit
   → Kiểm tra: S_phy >= quantity
   → Tăng: flash_stock_sold (buy)
   → Giảm: S_phy (tạo export receipt)

3. Nếu flash_stock_sold == flash_stock_limit:
   → Thông báo: Flash Sale đã hết
   → Đơn hàng tiếp theo tự động dùng giá Promotion
```

### 6.2 Đơn hàng thường (Promotion/Normal)

```
1. User chọn sản phẩm → Gọi calculateDisplayPrice()
   → Nếu không có Flash Sale hoặc hết stock:
     → Trả về: type = 'promotion' hoặc 'normal'

2. User đặt hàng → Gọi processOrder()
   → Kiểm tra: Available Stock >= quantity
     (Available Stock = S_phy - S_flash)
   → Giảm: S_phy (tạo export receipt)
```

---

## 7. Xử lý Deal Sốc (Mua A tặng B)

Khi xử lý đơn hàng Deal Sốc:

```php
// Sản phẩm chính (A)
$orderItems[] = [
    'product_id' => $dealProductId,
    'variant_id' => $dealVariantId,
    'quantity' => $quantity,
    'order_type' => 'normal', // Hoặc 'promotion' nếu có
];

// Sản phẩm tặng kèm (B)
$orderItems[] = [
    'product_id' => $giftProductId,
    'variant_id' => $giftVariantId,
    'quantity' => $quantity, // Cùng số lượng
    'order_type' => 'normal',
];

// Xử lý cả hai sản phẩm
$result = $this->inventoryService->processOrder($orderItems);
```

---

## 8. Điều kiện chuyển đổi giá

**Tự động chuyển từ Flash Sale sang Promotion:**

Khi `flash_stock_sold == flash_stock_limit`:
1. Hệ thống tự động không trả về giá Flash Sale nữa
2. `calculateDisplayPrice()` sẽ trả về giá Promotion (nếu có) hoặc giá gốc
3. Đơn hàng tiếp theo sẽ trừ trực tiếp vào `S_phy` (không qua `S_flash`)

**Cập nhật UI/FE:**
- Frontend cần gọi lại `calculateDisplayPrice()` khi nhận được `flash_sale_exhausted = true`
- Hoặc sử dụng WebSocket/Real-time để cập nhật giá tự động

---

## 9. Files đã tạo

### Services
- `app/Services/Pricing/PriceEngineService.php` - Tính toán giá
- `app/Services/Pricing/PriceEngineServiceInterface.php` - Interface
- `app/Services/Inventory/InventoryService.php` - Quản lý tồn kho
- `app/Services/Inventory/InventoryServiceInterface.php` - Interface

### Controllers
- `app/Http/Controllers/OrderProcessingController.php` - Xử lý đơn hàng

### Updated Files
- `app/Providers/AppServiceProvider.php` - Đăng ký services
- `app/Services/Warehouse/WarehouseService.php` - Thêm method `deductStock()`
- `app/Services/Warehouse/WarehouseServiceInterface.php` - Thêm interface method
- `app/Modules/ApiAdmin/Controllers/FlashSaleController.php` - Thêm validation

---

## 10. Testing

### Test Case 1: Tính giá Flash Sale
```php
// Sản phẩm có Flash Sale đang active và còn stock
$priceInfo = $priceEngine->calculateDisplayPrice(10, 5);
// Expected: type = 'flashsale', price = flash_price
```

### Test Case 2: Flash Sale hết stock
```php
// Sản phẩm có Flash Sale nhưng flash_stock_sold >= flash_stock_limit
$priceInfo = $priceEngine->calculateDisplayPrice(10, 5);
// Expected: type = 'promotion' hoặc 'normal'
```

### Test Case 3: Xử lý đơn hàng Flash Sale
```php
$result = $inventoryService->processOrder([
    ['product_id' => 10, 'variant_id' => 5, 'quantity' => 2, 'order_type' => 'flashsale']
]);
// Expected: 
// - ProductSale.buy tăng 2
// - Warehouse tạo export receipt với quantity = 2
```

### Test Case 4: Validation khi tạo Flash Sale
```php
// Tồn kho = 50, flash_stock_limit = 100
$validation = $inventoryService->validateFlashSaleStock(10, 5, 100);
// Expected: valid = false, message = "Tồn kho thực tế (50) không đủ..."
```

---

## 11. Lưu ý quan trọng

1. **Race Condition**: Luôn sử dụng `lockForUpdate()` khi cập nhật `ProductSale.buy`
2. **Transaction**: Tất cả thao tác trừ tồn kho phải trong Database Transaction
3. **Validation**: Kiểm tra tồn kho trước khi tạo Flash Sale
4. **Auto-switch**: Khi Flash Sale hết stock, giá tự động chuyển sang Promotion
5. **Warehouse Integration**: Tất cả thao tác trừ tồn kho đều thông qua Warehouse Service

---

**最后更新:** 2025-01-20
**维护者:** AI Assistant
