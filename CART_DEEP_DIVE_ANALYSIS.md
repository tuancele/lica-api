# Deep Dive: Logic Giỏ Hàng (Cart) & Kế Hoạch Nâng Cấp API

## 📊 Cấu Trúc Database

### 1. **Giỏ Hàng (Session-based, không có bảng riêng)**
- Giỏ hàng được lưu trong **Session** (`Session::put('cart', $cart)`)
- Cấu trúc dữ liệu trong Session:
```php
$cart = [
    'items' => [
        '{variant_id}' => [
            'qty' => 2,
            'price' => 100000,  // Giá cuối cùng (sau Flash Sale/Campaign)
            'item' => Variant object/array,  // Variant data
            'is_deal' => 0/1  // 1 = sản phẩm mua kèm deal sốc
        ]
    ],
    'totalQty' => 5,
    'totalPrice' => 500000
]
```

### 2. **Bảng `orders` (Order Model)**
- `id`: ID đơn hàng
- `code`: Mã đơn hàng (timestamp)
- `name`: Tên người nhận
- `phone`: Số điện thoại
- `email`: Email
- `address`: Địa chỉ chi tiết
- `provinceid`, `districtid`, `wardid`: Địa chỉ
- `remark`: Ghi chú
- `member_id`: ID thành viên (0 nếu guest)
- `ship`: Phương thức vận chuyển
- `sale`: Giảm giá từ coupon
- `total`: Tổng tiền (trước giảm giá)
- `promotion_id`: ID mã giảm giá
- `fee_ship`: Phí vận chuyển
- `status`: Trạng thái đơn hàng (0=chờ xử lý, ...)
- `created_at`, `updated_at`

### 3. **Bảng `orderdetail` (OrderDetail Model)**
- `id`: ID chi tiết
- `order_id`: ID đơn hàng
- `product_id`: ID sản phẩm
- `variant_id`: ID phân loại
- `name`: Tên sản phẩm (có thể có prefix "[DEAL SỐC]")
- `color_id`, `size_id`: Màu sắc, kích thước
- `price`: Giá bán (đã áp dụng Flash Sale/Campaign/Deal)
- `qty`: Số lượng
- `image`: Hình ảnh
- `weight`: Trọng lượng (tổng)
- `subtotal`: Thành tiền (price * qty)
- `created_at`

### 4. **Bảng `promotions` (Promotion Model - Coupon)**
- `id`: ID mã giảm giá
- `code`: Mã coupon
- `name`: Tên chương trình
- `value`: Giá trị giảm
- `unit`: Đơn vị (0=%, 1=VND)
- `order_sale`: Đơn hàng tối thiểu
- `number`: Số lượng sử dụng tối đa
- `start`: Ngày bắt đầu
- `end`: Ngày kết thúc
- `status`: Trạng thái (0/1)
- `user_id`: Người tạo
- `created_at`, `updated_at`

## 🔄 Logic Hoạt Động

### Flow 1: Thêm Sản Phẩm Vào Giỏ Hàng

**Endpoint hiện tại:** `POST /cart/add-to-cart`

**Logic:**
1. **Validate variant tồn tại:**
   ```php
   $variant = Variant::with('product')->find($req->id);
   ```

2. **Kiểm tra tồn kho:**
   - Nếu `variant->stock` có giá trị → dùng `variant->stock`
   - Nếu `variant->stock` NULL → dùng `product->stock` (1 = có hàng, 0 = hết hàng)
   - Kiểm tra `qty <= stock`

3. **Xử lý Deal Sốc (nếu có):**
   ```php
   if ($is_deal == 1) {
       // Tìm SaleDeal active
       $saledeal = SaleDeal::where('product_id', $variant->product_id)
           ->whereHas('deal', function($query) use ($now) {
               $query->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]]);
           })->where('status', '1')->first();
       
       if ($saledeal) {
           $variant->price = $saledeal->price;  // Override giá
           $variant->sale = 0;
       }
   }
   ```

4. **Thêm vào Cart (Cart Model):**
   - Tính giá theo thứ tự ưu tiên: **Flash Sale > Marketing Campaign > Sale Price > Normal Price**
   - Lưu vào Session

**Hỗ trợ Combo (thêm nhiều sản phẩm cùng lúc):**
```php
if ($req->combo && is_array($req->combo)) {
    foreach ($req->combo as $item) {
        // Xử lý từng item
    }
}
```

### Flow 2: Tính Giá Trong Cart Model

**File:** `app/Themes/Website/Models/Cart.php`

**Thứ tự ưu tiên giá:**
1. **Flash Sale** (ưu tiên cao nhất)
   ```php
   $flash = FlashSale::where([['status', '1'], ['start', '<=', $date], ['end', '>=', $date]])->first();
   if ($flash) {
       $product = ProductSale::where([['flashsale_id', $flash->id], ['product_id', $item->product_id]])->first();
       if ($product && $product->buy < $product->number) {
           $unit_price = $product->price_sale;
       }
   }
   ```

2. **Marketing Campaign**
   ```php
   $campaignProduct = MarketingCampaignProduct::where('product_id', $item->product_id)
       ->whereHas('campaign', function ($q) use ($nowDate) {
           $q->where('status', 1)
             ->where('start_at', '<=', $nowDate)
             ->where('end_at', '>=', $nowDate);
       })->first();
   if ($campaignProduct) {
       $unit_price = $campaignProduct->price;
   }
   ```

3. **Sale Price / Normal Price**
   ```php
   $unit_price = ($item->sale != 0) ? $item->sale : $item->price;
   ```

### Flow 3: Xem Giỏ Hàng

**Endpoint hiện tại:** `GET /cart/gio-hang`

**Logic:**
1. Lấy cart từ Session
2. Đếm số lượng deal theo từng `deal_id` (để validate)
3. Tìm các deal sốc có thể mua kèm với sản phẩm chính trong giỏ
4. Hiển thị danh sách sản phẩm

**Deal Validation:**
- Đếm số lượng sản phẩm deal theo từng `deal_id`
- Kiểm tra `limited` (1 = chỉ chọn 1, >1 = chọn nhiều)
- Gợi ý deal sốc cho sản phẩm chính

### Flow 4: Cập Nhật Số Lượng

**Endpoint hiện tại:** `POST /cart/update-cart`

**Logic:**
1. Validate `qty > 0`
2. Nếu `qty <= 0` → xóa sản phẩm
3. Cập nhật số lượng
4. Validate deals (nếu xóa sản phẩm chính → xóa deal tương ứng)
5. Tính lại tổng tiền

**Validate Deals:**
```php
private function validateDeals(&$cart) {
    // 1. Lấy danh sách sản phẩm chính
    // 2. Tìm các Deal ID active
    // 3. Xóa các sản phẩm Deal Sốc không còn sản phẩm chính tương ứng
}
```

### Flow 5: Xóa Sản Phẩm

**Endpoint hiện tại:** `POST /cart/del-item-cart`

**Logic:**
1. Xóa item khỏi cart
2. Validate deals
3. Nếu cart rỗng → xóa session cart và coupon

### Flow 6: Áp Dụng Coupon

**Endpoint hiện tại:** `POST /cart/applyCoupon`

**Validation:**
1. Kiểm tra chưa có coupon khác
2. Kiểm tra coupon tồn tại và active
3. Kiểm tra `order_sale <= cart->totalPrice`
4. Kiểm tra số lượng sử dụng (`count < number`)
5. Tính giảm giá:
   - `unit == 0`: `sale = (totalPrice / 100) * value` (%)
   - `unit == 1`: `sale = value` (VND)
6. Lưu vào Session: `Session::put('ss_counpon', [...])`

### Flow 7: Tính Phí Vận Chuyển

**Endpoint hiện tại:** `POST /cart/fee-ship`

**Logic:**
1. Kiểm tra free ship: `free_ship && totalPrice >= free_order`
2. Nếu có GHTK:
   - Lấy địa chỉ kho hàng (Pick)
   - Tính tổng trọng lượng từ cart items
   - Gọi API GHTK để tính phí
3. Trả về phí vận chuyển

### Flow 8: Checkout

**Endpoint hiện tại:** `GET /cart/thanh-toan` (view) và `POST /cart/thanh-toan` (submit)

**Validation:**
1. Security Token: `md5(Session::getId() . 'checkout_secure')`
2. Validate thông tin giao hàng
3. Re-validate coupon
4. Tính phí vận chuyển
5. Tạo Order
6. Tạo OrderDetail cho từng item
7. Update Flash Sale stock (`ProductSale::increment('buy', $qty)`)
8. Facebook Tracking
9. Send email notification
10. Xóa cart và coupon session

## 🐛 Vấn Đề Phát Hiện

### 1. **Session-based Cart - Không lưu database**
- **Vấn đề:** Giỏ hàng mất khi hết session hoặc đổi thiết bị
- **Giải pháp:** Hỗ trợ lưu cart vào database cho user đã đăng nhập

### 2. **Flash Sale chỉ check theo product_id**
- **Vấn đề:** Chưa hỗ trợ Flash Sale theo `variant_id` (theo phân tích FLASH_SALE_API_ANALYSIS.md)
- **Giải pháp:** Cập nhật logic check Flash Sale theo variant_id

### 3. **Deal Validation phức tạp**
- **Vấn đề:** Logic validate deal nằm trong Controller, khó tái sử dụng
- **Giải pháp:** Tạo Service Layer cho Deal validation

### 4. **Price Calculation rải rác**
- **Vấn đề:** Logic tính giá nằm ở Cart Model và Controller
- **Giải pháp:** Sử dụng `PriceCalculationService` (đã đề xuất trong FLASH_SALE_API_ANALYSIS.md)

## ✅ Kế Hoạch Nâng Cấp API

### 1. Public API V1 (Mobile App)

#### 1.1 GET /api/v1/cart

**Mục tiêu:** Lấy thông tin giỏ hàng hiện tại

**Authentication:** Optional (nếu có user_id → lấy từ DB, nếu không → lấy từ session)

**Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "variant_id": 1,
        "product_id": 10,
        "product_name": "Sản phẩm",
        "product_slug": "san-pham",
        "product_image": "https://...",
        "variant": {
          "id": 1,
          "sku": "SKU-001",
          "option1_value": "500ml",
          "color": {"id": 1, "name": "Đỏ"},
          "size": {"id": 1, "name": "500ml", "unit": "ml"}
        },
        "qty": 2,
        "price": 100000,
        "original_price": 150000,
        "subtotal": 200000,
        "is_deal": 0,
        "price_info": {
          "price": 100000,
          "original_price": 150000,
          "type": "flashsale",
          "label": "Flash Sale",
          "discount_percent": 33
        },
        "stock": 50,
        "available": true
      }
    ],
    "summary": {
      "total_qty": 5,
      "subtotal": 500000,
      "discount": 50000,
      "shipping_fee": 30000,
      "total": 480000
    },
    "coupon": {
      "id": 1,
      "code": "SALE10",
      "discount": 50000
    },
    "available_deals": [
      {
        "id": 1,
        "name": "Deal sốc",
        "limited": 2,
        "sale_deals": [...]
      }
    ]
  }
}
```

#### 1.2 POST /api/v1/cart/items

**Mục tiêu:** Thêm sản phẩm vào giỏ hàng

**Request Body:**
```json
{
  "variant_id": 1,
  "qty": 2,
  "is_deal": 0
}
```

**Hoặc Combo:**
```json
{
  "combo": [
    {"variant_id": 1, "qty": 2, "is_deal": 0},
    {"variant_id": 2, "qty": 1, "is_deal": 1}
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Thêm vào giỏ hàng thành công",
  "data": {
    "total_qty": 5,
    "item": {
      "variant_id": 1,
      "qty": 2,
      "price": 100000
    }
  }
}
```

#### 1.3 PUT /api/v1/cart/items/{variant_id}

**Mục tiêu:** Cập nhật số lượng sản phẩm

**Request Body:**
```json
{
  "qty": 3
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "variant_id": 1,
    "qty": 3,
    "subtotal": 300000,
    "summary": {
      "total_qty": 6,
      "subtotal": 600000,
      "total": 580000
    }
  }
}
```

#### 1.4 DELETE /api/v1/cart/items/{variant_id}

**Mục tiêu:** Xóa sản phẩm khỏi giỏ hàng

**Response:**
```json
{
  "success": true,
  "message": "Xóa sản phẩm thành công",
  "data": {
    "summary": {
      "total_qty": 4,
      "subtotal": 400000
    }
  }
}
```

#### 1.5 POST /api/v1/cart/coupon/apply

**Mục tiêu:** Áp dụng mã giảm giá

**Request Body:**
```json
{
  "code": "SALE10"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Áp dụng mã thành công",
  "data": {
    "coupon": {
      "id": 1,
      "code": "SALE10",
      "discount": 50000
    },
    "summary": {
      "subtotal": 500000,
      "discount": 50000,
      "total": 450000
    }
  }
}
```

#### 1.6 DELETE /api/v1/cart/coupon

**Mục tiêu:** Hủy mã giảm giá

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "subtotal": 500000,
      "discount": 0,
      "total": 500000
    }
  }
}
```

#### 1.7 POST /api/v1/cart/shipping-fee

**Mục tiêu:** Tính phí vận chuyển

**Request Body:**
```json
{
  "province_id": 1,
  "district_id": 1,
  "ward_id": 1,
  "address": "123 Đường ABC"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "shipping_fee": 30000,
    "free_ship": false,
    "summary": {
      "subtotal": 500000,
      "discount": 50000,
      "shipping_fee": 30000,
      "total": 480000
    }
  }
}
```

#### 1.8 POST /api/v1/cart/checkout

**Mục tiêu:** Đặt hàng

**Request Body:**
```json
{
  "full_name": "Nguyễn Văn A",
  "phone": "0123456789",
  "email": "email@example.com",
  "address": "123 Đường ABC",
  "province_id": 1,
  "district_id": 1,
  "ward_id": 1,
  "remark": "Ghi chú",
  "shipping_fee": 30000
}
```

**Response:**
```json
{
  "success": true,
  "message": "Đặt hàng thành công",
  "data": {
    "order_code": "1704067200",
    "order_id": 123,
    "redirect_url": "/cart/dat-hang-thanh-cong?code=1704067200"
  }
}
```

### 2. Admin API (Quản lý đơn hàng)

#### 2.1 GET /admin/api/orders

**Mục tiêu:** Lấy danh sách đơn hàng

**Query Params:**
- `page`, `limit`: Phân trang
- `status`: Lọc theo trạng thái
- `keyword`: Tìm kiếm theo mã đơn hàng, tên, SĐT
- `date_from`, `date_to`: Lọc theo ngày

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "code": "1704067200",
      "name": "Nguyễn Văn A",
      "phone": "0123456789",
      "email": "email@example.com",
      "address": "123 Đường ABC",
      "province": {"id": 1, "name": "Hà Nội"},
      "district": {"id": 1, "name": "Quận 1"},
      "ward": {"id": 1, "name": "Phường 1"},
      "total": 500000,
      "sale": 50000,
      "fee_ship": 30000,
      "status": "0",
      "status_label": "Chờ xử lý",
      "promotion": {
        "id": 1,
        "code": "SALE10"
      },
      "member": {
        "id": 1,
        "name": "Nguyễn Văn A"
      },
      "items_count": 3,
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {...}
}
```

#### 2.2 GET /admin/api/orders/{id}

**Mục tiêu:** Lấy chi tiết đơn hàng

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "code": "1704067200",
    // ... order info ...
    "items": [
      {
        "id": 1,
        "product_id": 10,
        "product_name": "Sản phẩm",
        "variant_id": 1,
        "variant": {
          "id": 1,
          "sku": "SKU-001",
          "option1_value": "500ml"
        },
        "price": 100000,
        "qty": 2,
        "subtotal": 200000,
        "image": "https://..."
      }
    ]
  }
}
```

#### 2.3 PUT /admin/api/orders/{id}/status

**Mục tiêu:** Cập nhật trạng thái đơn hàng

**Request Body:**
```json
{
  "status": "1"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cập nhật trạng thái thành công",
  "data": {
    "id": 123,
    "status": "1",
    "status_label": "Đã xác nhận"
  }
}
```

## 📝 Implementation Plan

### Phase 1: Tạo Cart Service Layer

**File:** `app/Services/Cart/CartService.php`

**Methods:**
- `getCart(?int $userId = null): array` - Lấy cart (từ DB hoặc Session)
- `addItem(int $variantId, int $qty, bool $isDeal = false, ?int $userId = null): array`
- `updateItem(int $variantId, int $qty, ?int $userId = null): array`
- `removeItem(int $variantId, ?int $userId = null): array`
- `applyCoupon(string $code, ?int $userId = null): array`
- `removeCoupon(?int $userId = null): array`
- `calculateShippingFee(array $address, ?int $userId = null): float`
- `checkout(array $data, ?int $userId = null): array`

### Phase 2: Tạo Cart Database Table (Optional)

**Migration:** `YYYY_MM_DD_HHMMSS_create_carts_table.php`

```php
Schema::create('carts', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('user_id')->nullable();
    $table->string('session_id')->nullable();
    $table->json('items');  // Lưu items dạng JSON
    $table->integer('total_qty')->default(0);
    $table->decimal('total_price', 15, 2)->default(0);
    $table->unsignedInteger('promotion_id')->nullable();
    $table->timestamps();
    
    $table->index(['user_id']);
    $table->index(['session_id']);
});
```

**Lưu ý:** 
- Nếu `user_id` có giá trị → lưu vào DB
- Nếu `user_id` NULL → dùng `session_id` (guest)
- Sync giữa Session và DB khi user login

### Phase 3: Tạo API Controllers

**Files:**
- `app/Http/Controllers/Api/V1/CartController.php` (Public API)
- `app/Modules/ApiAdmin/Controllers/OrderController.php` (Admin API)

### Phase 4: Tạo Resources

**Files:**
- `app/Http/Resources/Cart/CartResource.php`
- `app/Http/Resources/Cart/CartItemResource.php`
- `app/Http/Resources/Order/OrderResource.php`
- `app/Http/Resources/Order/OrderDetailResource.php`

### Phase 5: Tạo Request Validation

**Files:**
- `app/Http/Requests/Cart/AddCartItemRequest.php`
- `app/Http/Requests/Cart/UpdateCartItemRequest.php`
- `app/Http/Requests/Cart/ApplyCouponRequest.php`
- `app/Http/Requests/Cart/CheckoutRequest.php`

## 🔗 Tích Hợp Với Các Module Khác

### 1. **Flash Sale**
- Sử dụng `PriceCalculationService` để tính giá Flash Sale
- Hỗ trợ Flash Sale theo `variant_id` (theo FLASH_SALE_API_ANALYSIS.md)
- Update stock khi checkout: `ProductSale::increment('buy', $qty)`

### 2. **Deal Sốc**
- Validate deal khi thêm/xóa sản phẩm
- Tự động xóa deal khi xóa sản phẩm chính
- Hiển thị gợi ý deal trong cart response

### 3. **Marketing Campaign**
- Tích hợp vào `PriceCalculationService`
- Ưu tiên: Flash Sale > Campaign > Sale > Normal

### 4. **Promotion (Coupon)**
- Validate coupon trong CartService
- Tính giảm giá theo % hoặc VND
- Kiểm tra số lượng sử dụng

## 📋 Checklist Implementation

- [ ] Tạo CartService
- [ ] Tạo Cart Database Table (optional)
- [ ] Tạo API V1 CartController
- [ ] Tạo Admin API OrderController
- [ ] Tạo Resources
- [ ] Tạo Request Validation
- [ ] Tích hợp PriceCalculationService
- [ ] Tích hợp Deal validation
- [ ] Test API endpoints
- [ ] Cập nhật API documentation
- [ ] Đảm bảo backward compatibility với Blade routes

---

**Ngày tạo:** 2025-01-18  
**Người phân tích:** AI Assistant  
**Trạng thái:** Đang chờ phê duyệt
