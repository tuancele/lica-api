# Cart API Implementation Summary

## ✅ Đã Hoàn Thành

### 1. CartService Layer
**File:** `app/Services/Cart/CartService.php`

**Methods đã implement:**
- ✅ `getCart(?int $userId = null): array` - Lấy thông tin giỏ hàng
- ✅ `addItem(int $variantId, int $qty, bool $isDeal = false, ?int $userId = null): array` - Thêm sản phẩm
- ✅ `updateItem(int $variantId, int $qty, ?int $userId = null): array` - Cập nhật số lượng
- ✅ `removeItem(int $variantId, ?int $userId = null): array` - Xóa sản phẩm
- ✅ `applyCoupon(string $code, ?int $userId = null): array` - Áp dụng coupon
- ✅ `removeCoupon(?int $userId = null): array` - Hủy coupon
- ✅ `calculateShippingFee(array $address, ?int $userId = null): float` - Tính phí vận chuyển (placeholder)
- ✅ `checkout(array $data, ?int $userId = null): array` - Đặt hàng

**Tính năng:**
- Tích hợp với `PriceCalculationService` để tính giá theo thứ tự ưu tiên
- Hỗ trợ Deal Sốc validation tự động
- Format image URLs với R2 CDN
- Xử lý Flash Sale stock update khi checkout

### 2. CartController V1
**File:** `app/Http/Controllers/Api/V1/CartController.php`

**Endpoints đã implement:**
- ✅ `GET /api/v1/cart` - Lấy giỏ hàng
- ✅ `POST /api/v1/cart/items` - Thêm sản phẩm (hỗ trợ combo)
- ✅ `PUT /api/v1/cart/items/{variant_id}` - Cập nhật số lượng
- ✅ `DELETE /api/v1/cart/items/{variant_id}` - Xóa sản phẩm
- ✅ `POST /api/v1/cart/coupon/apply` - Áp dụng coupon
- ✅ `DELETE /api/v1/cart/coupon` - Hủy coupon
- ✅ `POST /api/v1/cart/shipping-fee` - Tính phí vận chuyển
- ✅ `POST /api/v1/cart/checkout` - Đặt hàng

**Tính năng:**
- Error handling với try-catch
- Validation với Validator
- Logging errors
- Debug mode support

### 3. OrderController Admin
**File:** `app/Modules/ApiAdmin/Controllers/OrderController.php`

**Endpoints đã implement:**
- ✅ `GET /admin/api/orders` - Danh sách đơn hàng (với pagination, filters)
- ✅ `GET /admin/api/orders/{id}` - Chi tiết đơn hàng
- ✅ `PUT /admin/api/orders/{id}/status` - Cập nhật trạng thái

**Tính năng:**
- Filter theo status, keyword, date range
- Pagination
- Eager loading relationships
- Format image URLs

### 4. Routes Registration
**Files:**
- ✅ `routes/api.php` - Đã thêm Cart API V1 routes
- ✅ `app/Modules/ApiAdmin/routes.php` - Đã thêm Order Management routes

## 📝 Cần Hoàn Thiện

### 1. Shipping Fee Calculation ✅
**File:** `app/Services/Cart/CartService.php`

**Method:** `calculateShippingFee()`

**Đã implement:**
- ✅ Tích hợp với GHTK API
- ✅ Tính tổng trọng lượng từ cart items
- ✅ Lấy địa chỉ kho hàng (Pick)
- ✅ Gọi GHTK API để tính phí
- ✅ Xử lý free ship nếu đơn hàng đủ điều kiện
- ✅ Error handling và logging
- ✅ Timeout protection (10 seconds)

**Logic:**
1. Kiểm tra free ship: Nếu `free_ship = 1` và `totalPrice >= free_order` → return 0
2. Kiểm tra GHTK status: Nếu `ghtk_status != 1` → return 0
3. Lấy Pick address (warehouse) từ database
4. Tính tổng trọng lượng từ cart items (weight * qty)
5. Lấy thông tin địa chỉ giao hàng (Province, District, Ward)
6. Gọi GHTK API với thông tin đầy đủ
7. Trả về phí vận chuyển hoặc 0 nếu có lỗi

**Code mẫu từ CartController cũ:**
```php
$pick = Pick::where('status', '1')->orderBy('sort', 'asc')->first();
if ($pick) {
    $weight = 0;
    foreach ($cart->items as $variant) {
        $item = $variant['item'];
        $itemWeight = is_object($item) ? ($item->weight ?? 0) : ($item['weight'] ?? 0);
        $weight += ($itemWeight * ($variant['qty'] ?? 1));
    }
    
    $info = [
        "pick_province" => $pick->province->name ?? '',
        "pick_district" => $pick->district->name ?? '',
        "pick_ward" => $pick->ward->name ?? '',
        "pick_street" => $pick->street,
        "pick_address" => $pick->address,
        "province" => $address['province_name'],
        "district" => $address['district_name'],
        "ward" => $address['ward_name'],
        "address" => $address['address'],
        "weight" => $weight,
        "value" => $cart->totalPrice - $sale,
        "transport" => 'road',
        "deliver_option" => 'none',
        "tags" => [0],
    ];
    
    // Call GHTK API
    $getFee = json_decode($this->getFee($info));
    if ($getFee && $getFee->success) {
        return $getFee->fee->fee;
    }
}
```

### 2. Request Validation Classes (Optional)
Có thể tạo các Request classes để code clean hơn:

- `app/Http/Requests/Cart/AddCartItemRequest.php`
- `app/Http/Requests/Cart/UpdateCartItemRequest.php`
- `app/Http/Requests/Cart/ApplyCouponRequest.php`
- `app/Http/Requests/Cart/CheckoutRequest.php`

**Hiện tại:** Đã dùng Validator trong Controller, nhưng Request classes sẽ tốt hơn.

### 3. Resources (Optional)
Có thể tạo Resources để format response:

- `app/Http/Resources/Cart/CartResource.php`
- `app/Http/Resources/Cart/CartItemResource.php`
- `app/Http/Resources/Order/OrderResource.php`
- `app/Http/Resources/Order/OrderDetailResource.php`

**Hiện tại:** Đã format response trực tiếp trong Controller, nhưng Resources sẽ tốt hơn cho maintainability.

### 4. Database Cart Table (Future Enhancement)
Theo phân tích, có thể tạo bảng `carts` để lưu giỏ hàng cho user đã đăng nhập:

**Migration:**
```php
Schema::create('carts', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('user_id')->nullable();
    $table->string('session_id')->nullable();
    $table->json('items');
    $table->integer('total_qty')->default(0);
    $table->decimal('total_price', 15, 2)->default(0);
    $table->unsignedInteger('promotion_id')->nullable();
    $table->timestamps();
    
    $table->index(['user_id']);
    $table->index(['session_id']);
});
```

**Lợi ích:**
- Giỏ hàng không mất khi hết session
- Sync giữa các thiết bị
- Lưu lịch sử giỏ hàng

## 🔗 Tích Hợp

### 1. PriceCalculationService
✅ Đã tích hợp đầy đủ
- Tính giá theo thứ tự: Flash Sale > Marketing Campaign > Sale > Normal
- Hỗ trợ Flash Sale theo variant_id

### 2. Deal Sốc
✅ Đã tích hợp
- Validate deal khi thêm/xóa sản phẩm
- Tự động xóa deal khi xóa sản phẩm chính
- Hiển thị available deals trong cart response

### 3. Flash Sale
✅ Đã tích hợp
- Update stock khi checkout
- Tính giá Flash Sale trong cart

### 4. Promotion/Coupon
✅ Đã tích hợp
- Validate coupon
- Tính giảm giá theo % hoặc VND
- Kiểm tra số lượng sử dụng

## 📋 Testing Checklist

### Cart API V1
- [ ] GET /api/v1/cart - Lấy giỏ hàng trống
- [ ] GET /api/v1/cart - Lấy giỏ hàng có sản phẩm
- [ ] POST /api/v1/cart/items - Thêm 1 sản phẩm
- [ ] POST /api/v1/cart/items - Thêm combo (nhiều sản phẩm)
- [ ] POST /api/v1/cart/items - Thêm sản phẩm với deal
- [ ] POST /api/v1/cart/items - Thêm sản phẩm hết hàng (error)
- [ ] PUT /api/v1/cart/items/{id} - Cập nhật số lượng
- [ ] PUT /api/v1/cart/items/{id} - Cập nhật số lượng = 0 (xóa)
- [ ] DELETE /api/v1/cart/items/{id} - Xóa sản phẩm
- [ ] POST /api/v1/cart/coupon/apply - Áp dụng coupon hợp lệ
- [ ] POST /api/v1/cart/coupon/apply - Áp dụng coupon không hợp lệ (error)
- [ ] DELETE /api/v1/cart/coupon - Hủy coupon
- [ ] POST /api/v1/cart/shipping-fee - Tính phí vận chuyển
- [ ] POST /api/v1/cart/checkout - Đặt hàng thành công
- [ ] POST /api/v1/cart/checkout - Đặt hàng với giỏ hàng trống (error)

### Order Admin API
- [ ] GET /admin/api/orders - Lấy danh sách
- [ ] GET /admin/api/orders?status=0 - Lọc theo status
- [ ] GET /admin/api/orders?keyword=123 - Tìm kiếm
- [ ] GET /admin/api/orders/{id} - Lấy chi tiết
- [ ] PUT /admin/api/orders/{id}/status - Cập nhật trạng thái

## 🚀 Next Steps

1. **Implement Shipping Fee Calculation** - Tích hợp GHTK API
2. **Test API endpoints** - Test tất cả các endpoints
3. **Create Request Validation Classes** - Optional, nhưng nên làm
4. **Create Resources** - Optional, nhưng nên làm
5. **Update API Documentation** - Đã cập nhật trong API_V1_DOCS.md và API_ADMIN_DOCS.md
6. **Database Cart Table** - Future enhancement

---

**Ngày tạo:** 2025-01-18  
**Trạng thái:** Đã triển khai cơ bản, cần hoàn thiện Shipping Fee Calculation
