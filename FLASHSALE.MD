# Flash Sale Logic Documentation

Tài liệu kỹ thuật về logic xử lý Flash Sale, bao gồm tính giá hỗn hợp khi mua vượt hạn mức và quản lý tồn kho.

---

## 1. Logic Giá (Pricing Logic)

### 1.1. Thứ tự ưu tiên giá

Hệ thống tính giá theo thứ tự ưu tiên sau:

1. **Priority 1: Flash Sale** (nếu trong khung giờ và còn stock)
2. **Priority 2: Promotion/Marketing Campaign** (nếu trong khung giờ)
3. **Priority 3: Base Price** (giá gốc từ variant hoặc product)

### 1.2. Cơ chế tính giá hỗn hợp (Mixed Pricing)

Khi khách hàng mua số lượng `Q` lớn hơn tồn kho Flash Sale còn lại (`S_flash_rem`), hệ thống sẽ áp dụng giá hỗn hợp:

#### Công thức tính giá:

```
Nếu Q ≤ S_flash_rem:
    Total = Q × Price_FlashSale

Nếu Q > S_flash_rem:
    Total = (S_flash_rem × Price_FlashSale) + ((Q - S_flash_rem) × Price_Normal)
```

Trong đó:
- `Q`: Số lượng khách hàng muốn mua
- `S_flash_rem`: Số lượng Flash Sale còn lại = `flash_stock_limit - flash_stock_sold`
- `Price_FlashSale`: Giá Flash Sale
- `Price_Normal`: Giá ưu tiên tiếp theo (Promotion hoặc Original Price)

#### Bảng logic tính giá:

| Tình huống | Số lượng mua | Cách tính tiền | Trừ kho |
|------------|-------------|----------------|---------|
| **Trong hạn mức** | `Q ≤ S_flash_rem` | `Q × Price_FlashSale` | Giảm `S_flash` & `S_phy` |
| **Vượt hạn mức** | `Q > S_flash_rem` | `(S_flash_rem × Price_FlashSale) + (Q - S_flash_rem) × Price_Normal` | `S_flash → 0`, Giảm `S_phy` |

### 1.3. Price Breakdown Response

Khi tính giá với số lượng, API trả về chi tiết bảng giá:

```json
{
  "success": true,
  "data": {
    "total_price": 1500000,
    "price_breakdown": [
      {
        "type": "flashsale",
        "quantity": 5,
        "unit_price": 100000,
        "subtotal": 500000
      },
      {
        "type": "promotion",
        "quantity": 10,
        "unit_price": 100000,
        "subtotal": 1000000
      }
    ],
    "flash_sale_remaining": 5,
    "warning": "Chỉ còn 5 sản phẩm giá Flash Sale, 10 sản phẩm còn lại sẽ được tính theo giá khuyến mãi"
  }
}
```

---

## 2. Logic Kho (Inventory Logic)

### 2.1. Các loại tồn kho

Hệ thống quản lý 2 loại tồn kho:

1. **Physical Stock (S_phy)**: Tổng số lượng thực tế trong kho (từ Warehouse System)
2. **Flash Sale Virtual Stock (S_flash)**: Số lượng được "cắt" ra dành riêng cho Flash Sale

### 2.2. Công thức tính tồn kho

#### Physical Stock (S_phy)
```
S_phy = Import_Total - Export_Total
```
Lấy từ Warehouse System thông qua `WarehouseService::getVariantStock()`

**QUAN TRỌNG**: `S_phy` là giới hạn tuyệt đối cho mọi đơn hàng. Không được phép đặt hàng vượt quá `S_phy`.

#### Flash Sale Virtual Stock (S_flash)
```
S_flash = flash_stock_limit - flash_stock_sold
         = number - buy
```

Trong đó:
- `number` (flash_stock_limit): Tổng số lượng Flash Sale được phân bổ
- `buy` (flash_stock_sold): Số lượng đã bán trong Flash Sale

**Lưu ý**: `S_flash` chỉ là giới hạn về giá rẻ, không phải giới hạn mua hàng. Khách hàng vẫn có thể mua vượt `S_flash` nhưng phần vượt sẽ tính theo giá thường.

### 2.3. Quy tắc chặn tồn kho thực tế

#### Sự khác biệt giữa S_flash và S_phy

| Loại tồn kho | Ký hiệu | Mục đích | Giới hạn |
|--------------|---------|----------|----------|
| **Flash Sale Virtual Stock** | `S_flash` | Giới hạn số lượng được bán với giá Flash Sale | Chỉ ảnh hưởng đến giá, không chặn đặt hàng |
| **Physical Stock** | `S_phy` | Tổng số lượng thực tế trong kho | **Giới hạn tuyệt đối** - Chặn mọi đơn hàng vượt quá |

#### Available_Stock cho Flash Sale
```
Available_Stock = S_flash_remaining = flash_stock_limit - flash_stock_sold
```
- Đây là số lượng còn lại có thể mua với giá Flash Sale
- Nếu khách mua vượt, phần vượt sẽ tính theo giá thường

#### Absolute_Limit cho đơn hàng
```
Absolute_Limit = S_phy = Import_Total - Export_Total
```
- Đây là giới hạn tuyệt đối cho mọi đơn hàng
- **Không được phép** đặt hàng vượt quá `S_phy`
- Lấy trực tiếp từ Warehouse System để đảm bảo tính chính xác

#### Tích hợp với Warehouse API

**Tại sao cần tích hợp với Warehouse API?**

1. **Dữ liệu luôn đúng**: Tránh trường hợp cột `stock` ở bảng `products` chưa kịp cập nhật khi có phiếu xuất kho mới
2. **Đồng bộ hóa**: Đảm bảo mọi kênh bán hàng đều nhìn thấy một con số tồn kho duy nhất
3. **Quản lý chặt chẽ**: Warehouse System có phiếu nhập/xuất, đảm bảo tính minh bạch và kiểm soát

**Cách hoạt động:**

1. **Tại PriceEngineService**: Mỗi lần tính giá, gọi `WarehouseService::getVariantStock($variantId)` để lấy `S_phy` mới nhất
2. **Kiểm tra tại Frontend**: Nếu `quantity > S_phy`, hiển thị lỗi đỏ và tự động điều chỉnh số lượng
3. **Chốt chặn tại Backend**: Trước khi cập nhật giỏ hàng, kiểm tra lại `S_phy` và trả về lỗi 422 nếu vượt quá

#### Response từ API khi vượt quá tồn kho

```json
{
  "success": true,
  "data": {
    "total_price": 0,
    "price_breakdown": [],
    "flash_sale_remaining": 0,
    "warning": null,
    "total_physical_stock": 100,
    "is_available": false,
    "stock_error": "Rất tiếc, sản phẩm này chỉ còn tối đa 100 sản phẩm trong kho. Vui lòng điều chỉnh lại số lượng."
  }
}
```

#### Xử lý tại Frontend

Khi nhận được `is_available === false`:

1. **Hiển thị thông báo lỗi màu đỏ** (thay vì màu vàng của Flash Sale warning)
2. **Tự động điều chỉnh số lượng** về `total_physical_stock`
3. **Vô hiệu hóa nút thanh toán** và nút "Thêm vào giỏ hàng"
4. **Ẩn breakdown giá** vì đơn hàng không hợp lệ

#### Available Stock (Tồn kho khả dụng)
```
Available_Stock = S_phy - S_flash
```

Tồn kho khả dụng là số lượng có thể bán cho đơn hàng thường (không phải Flash Sale).

### 2.3. Luồng trừ kho khi đặt hàng

#### Trường hợp 1: Mua trong hạn mức Flash Sale (Q ≤ S_flash_rem)

```
1. Tăng flash_stock_sold: buy += Q
2. Trừ physical stock: S_phy -= Q
3. Kiểm tra: Nếu buy >= number → Flash Sale hết
```

#### Trường hợp 2: Mua vượt hạn mức Flash Sale (Q > S_flash_rem)

```
1. Đặt flash_stock_sold = flash_stock_limit: buy = number (chạm trần)
2. Trừ toàn bộ quantity vào physical stock: S_phy -= Q
3. Flash Sale đã hết (buy >= number)
```

**Lưu ý quan trọng:**
- Khi mua vượt mức, toàn bộ số lượng `Q` được trừ vào physical stock
- Phần vượt mức (`Q - S_flash_rem`) vẫn được trừ kho, nhưng tính giá theo giá thường

### 2.4. Bảng logic trừ kho

| Tình huống | Số lượng mua | Trừ Flash Stock | Trừ Physical Stock | Kết quả |
|------------|-------------|------------------|-------------------|---------|
| **Trong hạn mức** | `Q ≤ S_flash_rem` | `buy += Q` | `S_phy -= Q` | `buy < number` |
| **Vượt hạn mức** | `Q > S_flash_rem` | `buy = number` (chạm trần) | `S_phy -= Q` | `buy = number` (hết) |

---

## 3. Danh sách File đã tạo/chỉnh sửa

### 3.1. Service Layer

#### `app/Services/Pricing/PriceEngineService.php`
- **Thay đổi:**
  - Thêm method `calculatePriceWithQuantity()` để tính giá hỗn hợp với số lượng
  - Hỗ trợ logic giá hỗn hợp khi mua vượt hạn mức Flash Sale
  - Trả về `price_breakdown` chi tiết và cảnh báo

#### `app/Services/Pricing/PriceEngineServiceInterface.php`
- **Thay đổi:**
  - Thêm method `calculatePriceWithQuantity()` vào interface

#### `app/Services/Inventory/InventoryService.php`
- **Thay đổi:**
  - Cập nhật method `processFlashSaleOrder()` để hỗ trợ mua vượt hạn mức
  - Logic: Khi `Q > S_flash_rem`, đặt `buy = number` (chạm trần) và trừ toàn bộ `Q` vào physical stock
  - Trả về cảnh báo khi mua vượt mức
  - Cập nhật `processOrder()` để thu thập và trả về warnings

### 3.2. Controller Layer

#### `app/Http/Controllers/OrderProcessingController.php`
- **Thay đổi:**
  - Thêm method `calculatePrice()` để tính giá với số lượng (endpoint mới)
  - Cập nhật method `processOrder()` để:
    - Kiểm tra cảnh báo khi mua vượt hạn mức Flash Sale
    - Trả về warnings trong response
    - Sử dụng `calculatePriceWithQuantity()` để tính giá chi tiết

### 3.3. Provider

#### `app/Providers/AppServiceProvider.php`
- **Không thay đổi:** Interface binding đã có sẵn

---

## 4. Hướng dẫn API

### 4.1. Tính giá với số lượng (Hỗ trợ giá hỗn hợp)

**Endpoint:** `POST /api/price/calculate`

**Request Body:**
```json
{
  "product_id": 10,
  "variant_id": 5,
  "quantity": 15
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total_price": 1500000,
    "price_breakdown": [
      {
        "type": "flashsale",
        "quantity": 5,
        "unit_price": 100000,
        "subtotal": 500000
      },
      {
        "type": "promotion",
        "quantity": 10,
        "unit_price": 100000,
        "subtotal": 1000000
      }
    ],
    "flash_sale_remaining": 5,
    "warning": "Chỉ còn 5 sản phẩm giá Flash Sale, 10 sản phẩm còn lại sẽ được tính theo giá khuyến mãi",
    "flash_sale_id": 1,
    "product_sale_id": 10
  }
}
```

**Response khi không có Flash Sale:**
```json
{
  "success": true,
  "data": {
    "total_price": 1500000,
    "price_breakdown": [
      {
        "type": "promotion",
        "quantity": 15,
        "unit_price": 100000,
        "subtotal": 1500000
      }
    ],
    "flash_sale_remaining": 0,
    "warning": null
  }
}
```

### 4.2. Xử lý đơn hàng (Với cảnh báo mua vượt mức)

**Endpoint:** `POST /api/orders/process`

**Request Body:**
```json
{
  "items": [
    {
      "product_id": 10,
      "variant_id": 5,
      "quantity": 15,
      "order_type": "flashsale"
    }
  ]
}
```

**Response (200 - Có cảnh báo):**
```json
{
  "success": true,
  "message": "Xử lý đơn hàng thành công",
  "data": {
    "items": [
      {
        "product_id": 10,
        "variant_id": 5,
        "quantity": 15,
        "order_type": "flashsale",
        "price_info": {
          "price": 100000,
          "type": "flashsale",
          "remaining_stock": 5
        },
        "price_with_quantity": {
          "total_price": 1500000,
          "price_breakdown": [...],
          "warning": "Chỉ còn 5 sản phẩm giá Flash Sale, 10 sản phẩm còn lại sẽ được tính theo giá thường"
        }
      }
    ],
    "flash_sale_exhausted": true,
    "warnings": [
      {
        "item_index": 0,
        "product_id": 10,
        "variant_id": 5,
        "message": "Chỉ còn 5 sản phẩm giá Flash Sale, 10 sản phẩm còn lại sẽ được tính theo giá thường"
      }
    ]
  }
}
```

**Response (400 - Lỗi):**
```json
{
  "success": false,
  "message": "Không đủ tồn kho. Tồn kho hiện tại: 10"
}
```

### 4.3. Tính giá hiển thị (Không đổi)

**Endpoint:** `GET /api/price/{productId}?variant_id={variantId}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "price": 100000,
    "original_price": 150000,
    "type": "flashsale",
    "label": "Flash Sale",
    "discount_percent": 33,
    "flash_sale_id": 1,
    "product_sale_id": 10,
    "remaining_stock": 5
  }
}
```

---

## 5. Transaction và Row Locking

### 5.1. Database Transaction

Tất cả các thao tác trừ kho được thực hiện trong Database Transaction:

```php
DB::beginTransaction();
try {
    // Xử lý trừ kho
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
}
```

### 5.2. Row Locking

Để tránh Race Condition khi nhiều user cùng mua Flash Sale, hệ thống sử dụng Row Locking:

```php
$productSale = ProductSale::where('id', $productSale->id)
    ->lockForUpdate()
    ->first();
```

**Lưu ý:** Row Locking chỉ hoạt động trong Transaction.

---

## 6. Cảnh báo Frontend

### 6.1. Khi tính giá

Khi gọi `POST /api/price/calculate`, nếu `quantity > flash_sale_remaining`, response sẽ có field `warning`:

```json
{
  "warning": "Chỉ còn X sản phẩm giá Flash Sale, Y sản phẩm còn lại sẽ được tính theo giá thường"
}
```

### 6.2. Khi xử lý đơn hàng

Khi gọi `POST /api/orders/process`, response sẽ có array `warnings`:

```json
{
  "warnings": [
    {
      "item_index": 0,
      "product_id": 10,
      "variant_id": 5,
      "message": "Chỉ còn 5 sản phẩm giá Flash Sale, 10 sản phẩm còn lại sẽ được tính theo giá thường"
    }
  ]
}
```

**Frontend nên:**
- Hiển thị cảnh báo trước khi user xác nhận đơn hàng
- Hiển thị breakdown giá chi tiết để user biết phần nào tính giá Flash Sale, phần nào tính giá thường
- Cho phép user điều chỉnh số lượng nếu muốn

---

## 7. Ví dụ thực tế

### Scenario 1: Mua trong hạn mức

**Input:**
- Flash Sale còn lại: 10 sản phẩm
- Số lượng mua: 5 sản phẩm
- Giá Flash Sale: 100,000đ
- Giá gốc: 150,000đ

**Output:**
- Total: 5 × 100,000 = 500,000đ
- Tất cả tính theo giá Flash Sale
- Trừ kho: `buy += 5`, `S_phy -= 5`

### Scenario 2: Mua vượt hạn mức

**Input:**
- Flash Sale còn lại: 5 sản phẩm
- Số lượng mua: 15 sản phẩm
- Giá Flash Sale: 100,000đ
- Giá Promotion: 120,000đ (có Promotion đang active)
- Giá gốc: 150,000đ

**Output:**
- Total: (5 × 100,000) + (10 × 120,000) = 500,000 + 1,200,000 = 1,700,000đ
- Breakdown:
  - 5 sản phẩm × 100,000đ (Flash Sale) = 500,000đ
  - 10 sản phẩm × 120,000đ (Promotion) = 1,200,000đ
- Trừ kho: `buy = number` (chạm trần), `S_phy -= 15`
- Warning: "Chỉ còn 5 sản phẩm giá Flash Sale, 10 sản phẩm còn lại sẽ được tính theo giá khuyến mãi"

### Scenario 3: Mua vượt hạn mức (Không có Promotion)

**Input:**
- Flash Sale còn lại: 3 sản phẩm
- Số lượng mua: 8 sản phẩm
- Giá Flash Sale: 100,000đ
- Không có Promotion
- Giá gốc: 150,000đ

**Output:**
- Total: (3 × 100,000) + (5 × 150,000) = 300,000 + 750,000 = 1,050,000đ
- Breakdown:
  - 3 sản phẩm × 100,000đ (Flash Sale) = 300,000đ
  - 5 sản phẩm × 150,000đ (Giá gốc) = 750,000đ
- Trừ kho: `buy = number` (chạm trần), `S_phy -= 8`
- Warning: "Chỉ còn 3 sản phẩm giá Flash Sale, 5 sản phẩm còn lại sẽ được tính theo giá thường"

---

## 8. Lưu ý kỹ thuật

### 8.1. Performance

- Sử dụng Row Locking để đảm bảo tính nhất quán, nhưng có thể gây chậm khi có nhiều request đồng thời
- Nên cache thông tin Flash Sale active để giảm số lượng query

### 8.2. Error Handling

- Luôn kiểm tra tồn kho physical stock trước khi trừ kho
- Rollback transaction nếu có lỗi xảy ra
- Log chi tiết các lỗi để debug

### 8.3. Validation

- Validate số lượng mua phải > 0
- Validate product_id và variant_id phải tồn tại
- Validate tồn kho đủ trước khi xử lý đơn hàng

---

## 9. Testing Checklist

- [ ] Tính giá trong hạn mức Flash Sale
- [ ] Tính giá vượt hạn mức Flash Sale (có Promotion)
- [ ] Tính giá vượt hạn mức Flash Sale (không có Promotion)
- [ ] Trừ kho trong hạn mức
- [ ] Trừ kho vượt hạn mức (flash_stock_sold chạm trần)
- [ ] Cảnh báo hiển thị đúng khi mua vượt mức
- [ ] Transaction rollback khi có lỗi
- [ ] Row Locking hoạt động đúng (test concurrent requests)
- [ ] API response format đúng chuẩn

---

---

## 10. Frontend Integration (Tích hợp Frontend)

### 10.1. Admin Interface (Giao diện Admin)

**File:** `app/Modules/Order/Views/view.blade.php`

**Tính năng:**
- Hiển thị badge "Vượt hạn mức FS" khi đơn hàng có `price_breakdown` với nhiều hơn 1 loại giá
- Hiển thị chi tiết bảng giá hỗn hợp ngay trong danh sách sản phẩm
- Format: `5x100,000đ (Flash Sale) + 10x150,000đ (Giá thường)`

**Cách sử dụng:**
- Dữ liệu `price_breakdown` có thể được lưu trong JSON field của `orderdetail` table
- Hoặc truyền vào view thông qua controller

### 10.2. User Interface (Giao diện Người dùng)

**File:** `public/js/flash-sale-mixed-price.js`

**Tính năng:**
- Tự động gọi API `POST /api/price/calculate` khi user thay đổi số lượng
- Hiển thị cảnh báo màu vàng khi `quantity > flash_sale_remaining`
- Hiển thị breakdown giá khi click vào giá tiền
- Tooltip/Alert thông báo: "Số lượng vượt mức Flash Sale sẽ được tính giá thường"

**Cách tích hợp:**
```javascript
// Trong checkout.blade.php hoặc cart page
<script src="/public/js/flash-sale-mixed-price.js"></script>

// Khi user thay đổi số lượng
$('.qtyplus, .qtyminus').on('click', function() {
    const variantId = $(this).data('id');
    const quantity = $('#quantity-' + variantId).val();
    const productId = $(this).data('product-id'); // Cần thêm data attribute
    
    FlashSaleMixedPrice.calculatePriceWithQuantity(
        productId,
        variantId,
        quantity,
        '.price-item-' + variantId, // Selector hiển thị giá
        '.warning-container-' + variantId // Selector hiển thị warning
    );
});
```

### 10.3. Validation Frontend

- Kiểm tra `total_stock` trước khi cho phép thanh toán
- Hiển thị thông báo lỗi nếu không đủ tồn kho
- Disable nút "Thanh toán" nếu có lỗi validation

---

## 11. Logging & Marketing Data

### 11.1. Log Format

**File:** `app/Services/Inventory/InventoryService.php` (method `processFlashSaleOrder`)

**Log được ghi khi:** User mua vượt hạn mức Flash Sale (`Q > S_flash_rem`)

**Format:**
```
[FlashSale_MixedPrice] Order processed with mixed pricing
```

**Nội dung Log:**
```php
[
    'product_id' => int,
    'variant_id' => int|null,
    'product_name' => string,
    'flash_quantity' => int,        // Số lượng tính giá Flash Sale
    'normal_quantity' => int,       // Số lượng tính giá thường
    'total_quantity' => int,
    'flash_price' => float,
    'normal_price' => float,
    'extra_revenue' => float,       // Tiền chênh lệch = (normal_price - flash_price) × normal_quantity
    'flash_sale_id' => int,
    'product_sale_id' => int,
]
```

**Mục đích:**
- Thống kê khách hàng sẵn sàng trả thêm bao nhiêu tiền khi hết Flash Sale
- Phân tích hành vi mua hàng vượt mức
- Tối ưu hóa chiến lược giá Flash Sale

### 11.2. Cách truy vấn Log

```bash
# Tìm tất cả log mua vượt mức trong ngày
grep "[FlashSale_MixedPrice]" storage/logs/laravel.log | grep "$(date +%Y-%m-%d)"

# Tính tổng extra revenue
# Có thể viết script PHP để parse log và tính toán
```

---

## 12. Unit Testing

### 12.1. Test File

**File:** `tests/Unit/FlashSaleMixedPriceTest.php`

### 12.2. Test Scenario 2 (Mua vượt hạn mức)

**Kịch bản:**
- Sản phẩm A có 100 tồn kho
- Flash Sale 5 sản phẩm giá 100k, giá thường 150k
- User đặt mua 15 sản phẩm

**Assert (Kỳ vọng):**
1. ✅ Tổng tiền = (5 × 100k) + (10 × 150k) = 2.000k
2. ✅ Tồn kho Flash Sale (buy) = 5
3. ✅ Tồn kho thực tế (S_phy) = 85 (đã trừ 15)
4. ✅ Có trả về warning trong kết quả

**Chạy test:**
```bash
php artisan test --filter FlashSaleMixedPriceTest
```

### 12.3. Test Cases

- ✅ `test_mixed_pricing_when_exceeding_flash_sale_limit()` - Test mua vượt mức
- ✅ `test_normal_pricing_when_within_flash_sale_limit()` - Test mua trong hạn mức

---

## 13. Danh sách File đã tạo/chỉnh sửa (Cập nhật)

### 13.1. Backend

1. ✅ `app/Services/Pricing/PriceEngineService.php` - Thêm method `calculatePriceWithQuantity()`
2. ✅ `app/Services/Pricing/PriceEngineServiceInterface.php` - Cập nhật interface
3. ✅ `app/Services/Inventory/InventoryService.php` - Logic trừ kho + Logging
4. ✅ `app/Http/Controllers/OrderProcessingController.php` - Thêm endpoint và cảnh báo
5. ✅ `routes/api.php` - Thêm route `/api/price/calculate`

### 13.2. Frontend

6. ✅ `app/Modules/Order/Views/view.blade.php` - Hiển thị price_breakdown trong Admin
7. ✅ `public/js/flash-sale-mixed-price.js` - JavaScript xử lý cảnh báo và breakdown

### 13.3. Testing

8. ✅ `tests/Unit/FlashSaleMixedPriceTest.php` - Unit Test cho mixed pricing

### 13.4. Documentation

9. ✅ `FLASHSALE.MD` - Tài liệu kỹ thuật đầy đủ
10. ✅ `API_ADMIN_DOCS.md` - Cập nhật tài liệu API

---

**Cập nhật lần cuối:** 2026-01-20  
**Phiên bản:** 2.0 (Thêm Frontend & Logging)  
**Người duy trì:** AI Assistant
