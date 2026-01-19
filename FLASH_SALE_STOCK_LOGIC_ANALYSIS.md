# Phân Tích Logic Flash Sale và Tồn Kho

## Tổng Quan

Đối với sản phẩm tham gia Flash Sale, hệ thống cần đảm bảo:
1. **Số lượng tồn kho được lấy từ chương trình Flash Sale** thay vì tồn kho thông thường
2. **Chi tiết sản phẩm/phân loại hiển thị số lượng còn lại của Flash Sale**
3. **Khi số lượng Flash Sale hết (buy >= number), giá và tồn kho trở về như không có Flash Sale**

---

## 1. Cấu Trúc Dữ Liệu

### 1.1 Bảng `productsales`
- `id`: ID của ProductSale entry
- `flashsale_id`: ID của Flash Sale
- `product_id`: ID của sản phẩm
- `variant_id`: ID của phân loại (nullable - null nếu áp dụng cho toàn bộ sản phẩm)
- `price_sale`: Giá khuyến mãi Flash Sale
- `number`: Số lượng sản phẩm được cài đặt trong Flash Sale
- `buy`: Số lượng đã bán trong Flash Sale

### 1.2 Model `ProductSale`
```php
// app/Modules/FlashSale/Models/ProductSale.php

// Accessor: Kiểm tra sản phẩm còn khả dụng trong Flash Sale
public function getIsAvailableAttribute(): bool
{
    return $this->buy < $this->number; // Còn hàng khi buy < number
}

// Accessor: Tính số lượng còn lại
public function getRemainingAttribute(): int
{
    return max(0, $this->number - $this->buy);
}
```

---

## 2. Logic Tính Giá và Tồn Kho

### 2.1 PriceCalculationService

**File:** `app/Services/PriceCalculationService.php`

#### 2.1.1 Tính giá cho Product (không có phân loại)

```php
public function calculateProductPrice(Product $product, ?int $flashSaleId = null): object
{
    // 1. Kiểm tra Flash Sale (ưu tiên cao nhất)
    $flashSaleProduct = ProductSale::where('product_id', $product->id)
        ->whereNull('variant_id') // Product-level Flash Sale
        ->whereHas('flashsale', function ($q) use ($now, $flashSaleId) {
            $q->where('status', 1)
              ->where('start', '<=', $now)
              ->where('end', '>=', $now);
        })
        ->first();

    // ✅ QUAN TRỌNG: Chỉ áp dụng Flash Sale khi còn hàng
    if ($flashSaleProduct && $flashSaleProduct->is_available) {
        return (object) [
            'price' => $flashSaleProduct->price_sale,
            'original_price' => $originalPrice,
            'type' => 'flashsale',
            'label' => 'Flash Sale',
            'flash_sale_info' => (object) [
                'flashsale_id' => $flashSaleProduct->flashsale_id,
                'price_sale' => $flashSaleProduct->price_sale,
                'number' => $flashSaleProduct->number,
                'buy' => $flashSaleProduct->buy,
                'remaining' => $flashSaleProduct->remaining, // ← Số lượng còn lại
            ],
        ];
    }

    // 2. Nếu Flash Sale hết hàng, kiểm tra Marketing Campaign
    // 3. Nếu không có Campaign, kiểm tra Variant Sale Price
    // 4. Cuối cùng trả về giá gốc
}
```

#### 2.1.2 Tính giá cho Variant (có phân loại)

```php
public function calculateVariantPrice(Variant $variant, ?int $productId = null, ?int $flashSaleId = null): object
{
    // 1. Kiểm tra Flash Sale cho phân loại cụ thể (ưu tiên cao nhất)
    $productSale = ProductSale::where('product_id', $productId)
        ->where('variant_id', $variant->id) // Variant-specific Flash Sale
        ->whereHas('flashsale', function ($q) use ($now, $flashSaleId) {
            $q->where('status', 1)
              ->where('start', '<=', $now)
              ->where('end', '>=', $now);
        })
        ->first();

    // ✅ QUAN TRỌNG: Chỉ áp dụng Flash Sale khi còn hàng
    if ($productSale && $productSale->is_available) {
        return (object) [
            'price' => $productSale->price_sale,
            'original_price' => $originalPrice,
            'type' => 'flashsale',
            'flash_sale_info' => (object) [
                'remaining' => $productSale->remaining, // ← Số lượng còn lại
            ],
        ];
    }

    // 2. Fallback: Kiểm tra Flash Sale ở cấp product (nếu variant không có Flash Sale riêng)
    // 3. Nếu Flash Sale hết hàng, kiểm tra Marketing Campaign
    // 4. Nếu không có Campaign, kiểm tra Variant Sale Price
    // 5. Cuối cùng trả về giá gốc
}
```

**Điểm quan trọng:**
- ✅ Logic đã kiểm tra `$productSale->is_available` (tức là `buy < number`)
- ✅ Khi `buy >= number`, Flash Sale không được áp dụng, giá sẽ fallback về Marketing Campaign hoặc giá gốc
- ✅ `remaining` được tính tự động thông qua accessor `getRemainingAttribute()`

---

## 3. Logic Hiển Thị Trong API

### 3.1 API `/api/products/flash-sale` (Trang chủ)

**File:** `app/Http/Controllers/Api/ProductController.php::getFlashSale()`

```php
// ✅ Chỉ lấy sản phẩm còn hàng trong Flash Sale
$productSales = ProductSale::where('flashsale_id', $flash->id)
    ->whereRaw('buy < number') // ← Chỉ lấy sản phẩm còn hàng
    ->get();

// Format response
$formattedProducts = $products->map(function($product) {
    $additionalData = [
        'flash_sale' => [
            'number' => (int) ($product->number ?? 0),
            'buy' => (int) ($product->buy ?? 0),
            'remaining' => (int) (($product->number ?? 0) - ($product->buy ?? 0)), // ← Số lượng còn lại
        ],
    ];
    return $this->formatProductForResponse($product, $variantPrice, $additionalData);
});
```

**Kết quả:**
- ✅ Chỉ hiển thị sản phẩm còn hàng (`buy < number`)
- ✅ Response bao gồm `remaining` (số lượng còn lại)

---

### 3.2 API `/api/products/{slug}/detail` (Chi tiết sản phẩm)

**File:** `app/Http/Controllers/Api/ProductController.php::getDetailBySlug()`

```php
// Get Flash Sale info
$flashSale = null;
$date = strtotime(date('Y-m-d H:i:s'));
$flash = FlashSale::where([['status', '1'], ['start', '<=', $date], ['end', '>=', $date]])->first();
if ($flash) {
    $productSale = ProductSale::where([['flashsale_id', $flash->id], ['product_id', $product->id]])->first();
    
    // ✅ QUAN TRỌNG: Chỉ trả về Flash Sale khi còn hàng
    if ($productSale && $productSale->buy < $productSale->number) {
        $flashSale = [
            'id' => $flash->id,
            'name' => $flash->name,
            'price_sale' => (float) $productSale->price_sale,
            'number' => (int) $productSale->number,
            'buy' => (int) $productSale->buy,
            'remaining' => (int) ($productSale->number - $productSale->buy), // ← Số lượng còn lại
        ];
    }
}

// Get variants with price info
$variants = $product->variants->map(function($variant) use ($product) {
    $variantPriceInfo = $this->getVariantPriceInfo($variant->id, $product->id);
    
    // ✅ variantPriceInfo sẽ chứa flash_sale_info nếu variant đang trong Flash Sale và còn hàng
    return [
        'id' => $variant->id,
        'price_info' => $variantPriceInfo, // ← Chứa flash_sale_info với remaining nếu có
        'warehouse_stock' => $warehouseStock, // ← Tồn kho từ warehouse
    ];
});
```

**Kết quả:**
- ✅ `flash_sale` chỉ được trả về khi `buy < number`
- ✅ Mỗi variant có `price_info` chứa `flash_sale_info.remaining` nếu variant đang trong Flash Sale và còn hàng
- ✅ Nếu Flash Sale hết hàng, `flash_sale` sẽ là `null` và giá sẽ fallback về Marketing Campaign hoặc giá gốc

---

### 3.3 API `/api/v1/flash-sales/{id}/products` (API V1)

**File:** `app/Http/Controllers/Api/V1/FlashSaleController.php::getProducts()`

```php
// Query ProductSales with Eager Loading
$query = ProductSale::where('flashsale_id', $id)
    ->with(['product', 'variant']);

// ✅ Filter by availability (mặc định chỉ lấy sản phẩm còn hàng)
if ($availableOnly) {
    $query->whereRaw('buy < number');
}

$productSales = $query->paginate($limit, ['*'], 'page', $page);

// Format products
foreach ($productSales->items() as $productSale) {
    if ($productSale->variant_id) {
        // Variant-specific Flash Sale
        $productData['variants'] = [
            [
                'flash_sale_info' => [
                    'price_sale' => (float) $productSale->price_sale,
                    'number' => (int) $productSale->number,
                    'buy' => (int) $productSale->buy,
                    'remaining' => $productSale->remaining, // ← Số lượng còn lại
                ],
            ]
        ];
    } else {
        // Product-level Flash Sale
        $productData['flash_sale_info'] = [
            'price_sale' => (float) $productSale->price_sale,
            'number' => (int) $productSale->number,
            'buy' => (int) $productSale->buy,
            'remaining' => $productSale->remaining, // ← Số lượng còn lại
        ];
    }
}
```

**Kết quả:**
- ✅ Mặc định chỉ trả về sản phẩm còn hàng (`available_only=true`)
- ✅ Mỗi sản phẩm/variant có `remaining` trong `flash_sale_info`

---

## 4. Logic Hiển Thị Trong Frontend (Blade)

### 4.1 Trang Chi Tiết Sản Phẩm

**File:** `app/Themes/Website/Views/product/detail.blade.php`

```php
// Kiểm tra Flash Sale cho sản phẩm liên quan
$productSale = App\Modules\FlashSale\Models\ProductSale::select('product_id','price_sale','number','buy')
    ->where([['flashsale_id',$flash->id],['product_id',$product->id]])
    ->first();

// ✅ Chỉ hiển thị Flash Sale khi còn hàng
if(isset($productSale) && !empty($productSale) && $productSale->buy < $productSale->number){
    // Hiển thị Flash Sale info
}
```

**JavaScript:**
```javascript
// Hiển thị số lượng còn lại từ Flash Sale
// remaining được lấy từ API response: flash_sale.remaining hoặc variant.price_info.flash_sale_info.remaining
```

---

## 5. Tóm Tắt Logic

### 5.1 Khi Sản Phẩm Còn Hàng Trong Flash Sale (`buy < number`)

1. **Giá bán:**
   - ✅ Áp dụng giá Flash Sale (`price_sale`)
   - ✅ Hiển thị giá gốc (`original_price`) để so sánh
   - ✅ Hiển thị phần trăm giảm giá

2. **Tồn kho:**
   - ✅ Hiển thị số lượng còn lại từ Flash Sale: `remaining = number - buy`
   - ✅ Không sử dụng `warehouse_stock` hoặc `variant.stock` thông thường
   - ✅ Frontend sẽ disable nút "Mua ngay" khi `remaining <= 0`

3. **API Response:**
   - ✅ `flash_sale` object chứa `remaining`
   - ✅ `price_info.flash_sale_info.remaining` cho từng variant
   - ✅ `type: 'flashsale'` trong `price_info`

---

### 5.2 Khi Sản Phẩm Hết Hàng Trong Flash Sale (`buy >= number`)

1. **Giá bán:**
   - ❌ **KHÔNG** áp dụng giá Flash Sale
   - ✅ Fallback về Marketing Campaign (nếu có)
   - ✅ Nếu không có Campaign, fallback về Variant Sale Price
   - ✅ Cuối cùng trả về giá gốc (`normal`)

2. **Tồn kho:**
   - ✅ Trả về tồn kho thông thường từ `warehouse_stock` hoặc `variant.stock`
   - ✅ Không hiển thị `flash_sale.remaining` (vì `flash_sale = null`)

3. **API Response:**
   - ✅ `flash_sale = null`
   - ✅ `price_info.type` không phải `'flashsale'`
   - ✅ `price_info` sẽ là `'campaign'`, `'sale'`, hoặc `'normal'`

---

## 6. Điểm Cần Lưu Ý

### 6.1 ✅ Logic Đã Được Implement Đúng

1. **PriceCalculationService:**
   - ✅ Kiểm tra `is_available` (`buy < number`) trước khi áp dụng Flash Sale
   - ✅ Tự động fallback về Campaign/Sale/Normal khi Flash Sale hết hàng
   - ✅ Trả về `remaining` trong `flash_sale_info`

2. **API Endpoints:**
   - ✅ `/api/products/flash-sale`: Chỉ trả về sản phẩm còn hàng (`buy < number`)
   - ✅ `/api/products/{slug}/detail`: Chỉ trả về `flash_sale` khi còn hàng
   - ✅ `/api/v1/flash-sales/{id}/products`: Có option `available_only` (mặc định `true`)

3. **Model Accessors:**
   - ✅ `ProductSale::is_available`: Kiểm tra `buy < number`
   - ✅ `ProductSale::remaining`: Tính `max(0, number - buy)`

---

### 6.2 ⚠️ Cần Kiểm Tra và Cải Thiện

1. **Frontend Logic:**
   - ⚠️ Cần đảm bảo frontend sử dụng `remaining` từ Flash Sale thay vì `warehouse_stock` khi sản phẩm đang trong Flash Sale
   - ⚠️ Cần disable nút "Mua ngay" khi `remaining <= 0` trong Flash Sale
   - ⚠️ Cần hiển thị thông báo "Hết hàng Flash Sale" khi `remaining = 0`

2. **Variant-Specific Flash Sale:**
   - ⚠️ Cần đảm bảo logic hiển thị `remaining` cho từng variant riêng biệt
   - ⚠️ Cần kiểm tra logic fallback từ variant-level Flash Sale về product-level Flash Sale

3. **Warehouse Stock vs Flash Sale Stock:**
   - ⚠️ Hiện tại API trả về cả `warehouse_stock` và `flash_sale_info.remaining`
   - ⚠️ Frontend cần ưu tiên sử dụng `flash_sale_info.remaining` khi sản phẩm đang trong Flash Sale
   - ⚠️ Cần làm rõ: Khi Flash Sale hết hàng (`buy >= number`), có nên kiểm tra `warehouse_stock` không?

---

## 7. Khuyến Nghị

### 7.1 Cải Thiện API Response

**Thêm field `effective_stock` vào response:**

```php
// Trong formatProductForResponse hoặc getDetailBySlug
$effectiveStock = 0;
if ($flashSale && isset($flashSale['remaining'])) {
    // Sản phẩm đang trong Flash Sale và còn hàng
    $effectiveStock = $flashSale['remaining'];
} else {
    // Sử dụng warehouse_stock hoặc variant.stock
    $effectiveStock = $warehouseStock ?? $variant->stock ?? 0;
}

$result['effective_stock'] = $effectiveStock;
$result['stock_source'] = ($flashSale && isset($flashSale['remaining'])) ? 'flash_sale' : 'warehouse';
```

### 7.2 Cải Thiện Frontend Logic

**JavaScript để xử lý stock:**

```javascript
function getEffectiveStock(variant) {
    // Ưu tiên Flash Sale stock
    if (variant.price_info?.flash_sale_info?.remaining !== undefined) {
        return variant.price_info.flash_sale_info.remaining;
    }
    // Fallback về warehouse stock
    return variant.warehouse_stock ?? variant.stock ?? 0;
}

function isOutOfStock(variant) {
    return getEffectiveStock(variant) <= 0;
}
```

---

## 8. Kết Luận

### ✅ Logic Hiện Tại Đã Đúng

1. **Backend đã implement đúng:**
   - ✅ Kiểm tra `buy < number` trước khi áp dụng Flash Sale
   - ✅ Tự động fallback về Campaign/Sale/Normal khi Flash Sale hết hàng
   - ✅ Trả về `remaining` trong API response

2. **Cần cải thiện:**
   - ⚠️ Frontend cần sử dụng `remaining` từ Flash Sale thay vì `warehouse_stock`
   - ⚠️ Cần thêm `effective_stock` vào API response để frontend dễ xử lý
   - ⚠️ Cần làm rõ logic khi Flash Sale hết hàng: có kiểm tra `warehouse_stock` không?

---

**Tài liệu này được tạo để phân tích logic Flash Sale và tồn kho.**
**Ngày tạo:** {{ date('Y-m-d H:i:s') }}
**Phiên bản:** 1.0
