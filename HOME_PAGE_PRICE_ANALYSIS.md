# Phân Tích Sâu Logic Tính Giá Trên Trang Chủ/Home

## Tổng Quan

Tài liệu này phân tích toàn bộ logic tính giá sản phẩm trên trang chủ `https://lica.test` để đảm bảo tất cả các khối sản phẩm đang gọi chính xác giá bán theo ưu tiên:

1. **Giá Flash Sale** (ưu tiên cao nhất)
2. **Giá Chương trình khuyến mại (Marketing Campaign)**
3. **Giá gốc (Normal Price)**

---

## Các Khối Sản Phẩm Trên Trang Chủ

### 1. Flash Sale Block
- **API Endpoint:** `GET /api/products/flash-sale`
- **Controller:** `App\Http\Controllers\Api\ProductController@getFlashSale`
- **JavaScript Function:** `loadFlashSaleProducts()`
- **Render Function:** `renderProductCard()`

**Logic hiện tại:**
- ✅ Đang dùng `formatProductForResponse()` với `PriceCalculationService`
- ✅ Trả về `price_info` với type `flashsale`
- ✅ JavaScript ưu tiên `price_info` từ API

**Status:** ✅ Hoàn thành - Logic đúng

---

### 2. Top Sản Phẩm Bán Chạy
- **API Endpoint:** `GET /api/products/top-selling`
- **Controller:** `App\Http\Controllers\Api\ProductController@getTopSelling`
- **JavaScript Function:** `loadTopSellingProducts()`
- **Render Function:** `renderProductCard()`

**Logic hiện tại:**
- ✅ Đang dùng `formatProductForResponse()` với `PriceCalculationService`
- ✅ Trả về `price_info` với đầy đủ thông tin
- ✅ JavaScript ưu tiên `price_info` từ API

**Status:** ✅ Hoàn thành - Logic đúng

---

### 3. Sản Phẩm Theo Danh Mục (Taxonomy)
- **API Endpoint:** `GET /api/products/by-category/{id}`
- **Controller:** `App\Http\Controllers\Api\ProductController@getByCategory`
- **JavaScript Function:** `loadTaxonomyProducts()`
- **Render Function:** `renderProductCard()`

**Logic hiện tại:**
- ✅ Đang dùng `formatProductForResponse()` với `PriceCalculationService`
- ✅ Trả về `price_info` với đầy đủ thông tin
- ✅ JavaScript ưu tiên `price_info` từ API

**Status:** ✅ Hoàn thành - Logic đúng

---

### 4. Gợi Ý Cho Bạn (Recommendations)
- **API Endpoint:** `GET /api/recommendations`
- **Controller:** `App\Http\Controllers\Api\RecommendationController@getRecommendations`
- **Service:** `App\Services\Recommendation\RecommendationService`
- **Render:** Sử dụng `price_info` từ Product model accessor

**Logic hiện tại:**
- ✅ Product model có accessor `getPriceInfoAttribute()` với logic đúng
- ✅ Ưu tiên: Flash Sale > Marketing Campaign > Sale > Normal
- ✅ Trả về `price_info` trong response

**Status:** ✅ Hoàn thành - Logic đúng

---

## PriceCalculationService

**Location:** `app/Services/PriceCalculationService.php`

**Logic Ưu Tiên:**
1. **Flash Sale** (highest priority)
   - Kiểm tra variant-level Flash Sale trước
   - Fallback về product-level Flash Sale
2. **Marketing Campaign**
   - Kiểm tra Marketing Campaign Product
3. **Variant Sale Price**
   - Kiểm tra variant sale price
4. **Normal Price**
   - Giá gốc từ variant

**Methods:**
- `calculateProductPrice(Product $product, ?int $flashSaleId = null): object`
- `calculateVariantPrice(Variant $variant, ?int $productId = null, ?int $flashSaleId = null): object`

**Status:** ✅ Logic đúng theo yêu cầu

---

## Product Model Accessor

**Location:** `app/Modules/Product/Models/Product.php`

**Method:** `getPriceInfoAttribute()`

**Logic Ưu Tiên:**
1. Flash Sale
2. Marketing Campaign
3. Variant Sale Price
4. Normal Price

**Status:** ✅ Logic đúng (tương tự PriceCalculationService)

---

## formatProductForResponse() Method

**Location:** `app/Http/Controllers/Api/ProductController.php`

**Thay đổi:**
- ❌ Trước: Dùng `getActiveDeal()` - chỉ kiểm tra Deal, không kiểm tra Flash Sale và Marketing Campaign
- ✅ Sau: Dùng `PriceCalculationService` - kiểm tra đầy đủ Flash Sale > Marketing Campaign > Sale > Normal

**Response Format:**
```json
{
  "id": 1,
  "name": "Sản phẩm",
  "price": 100000,  // Original price
  "sale": 80000,    // Variant sale price (backward compatibility)
  "price_info": {
    "price": 70000,           // Final price (after all discounts)
    "original_price": 100000, // Original price
    "type": "flashsale",      // flashsale | campaign | sale | normal
    "label": "Flash Sale",    // Label for display
    "discount_percent": 30    // Discount percentage
  },
  "flash_sale": {  // Only if type is flashsale
    "number": 100,
    "buy": 50,
    "remaining": 50
  }
}
```

**Status:** ✅ Đã cập nhật - Logic đúng

---

## JavaScript renderProductCard()

**Location:** `app/Themes/Website/Views/page/home.blade.php`

**Thay đổi:**
- ❌ Trước: Chỉ kiểm tra `price_sale` và `sale`
- ✅ Sau: Ưu tiên `price_info` từ API, fallback về `price_sale` và `sale`

**Logic Ưu Tiên:**
1. `product.price_info` (từ API - đã tính đúng)
2. `product.price_sale` (backward compatibility)
3. `product.sale` (variant sale price)
4. `product.price` (normal price)

**Status:** ✅ Đã cập nhật - Logic đúng

---

## Tóm Tắt Thay Đổi

### Backend (PHP)

1. ✅ **ProductController:**
   - Thêm `PriceCalculationService` dependency injection
   - Cập nhật `formatProductForResponse()` để dùng `PriceCalculationService`
   - Tất cả endpoints (`getTopSelling`, `getByCategory`, `getFlashSale`) đều dùng `formatProductForResponse()`

2. ✅ **PriceCalculationService:**
   - Logic đúng: Flash Sale > Marketing Campaign > Sale > Normal
   - Hỗ trợ cả Product-level và Variant-level

3. ✅ **Product Model:**
   - Accessor `getPriceInfoAttribute()` đã có logic đúng
   - Được dùng bởi RecommendationController

### Frontend (JavaScript)

1. ✅ **renderProductCard():**
   - Ưu tiên `price_info` từ API
   - Fallback về `price_sale` và `sale` (backward compatibility)

---

## Kiểm Tra Các API Endpoints

### ✅ GET /api/products/top-selling
- Dùng `formatProductForResponse()` với `PriceCalculationService`
- Trả về `price_info` đầy đủ

### ✅ GET /api/products/by-category/{id}
- Dùng `formatProductForResponse()` với `PriceCalculationService`
- Trả về `price_info` đầy đủ

### ✅ GET /api/products/flash-sale
- Dùng `formatProductForResponse()` với `PriceCalculationService`
- Trả về `price_info` với type `flashsale`
- Có thêm `flash_sale` info (number, buy, remaining)

### ✅ GET /api/recommendations
- Dùng Product model accessor `price_info`
- Logic đúng: Flash Sale > Marketing Campaign > Sale > Normal

---

## Kết Luận

✅ **Tất cả các khối sản phẩm trên trang chủ đã được cập nhật để đảm bảo logic tính giá đúng theo ưu tiên:**

1. **Giá Flash Sale** (ưu tiên cao nhất) ✅
2. **Giá Chương trình khuyến mại (Marketing Campaign)** ✅
3. **Giá gốc (Normal Price)** ✅

**Tất cả các API endpoints đều:**
- Dùng `PriceCalculationService` hoặc Product model accessor
- Trả về `price_info` với đầy đủ thông tin
- JavaScript ưu tiên `price_info` từ API

**Ngày cập nhật:** 2025-01-XX
**Trạng thái:** ✅ Hoàn thành
