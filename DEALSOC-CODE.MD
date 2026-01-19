## Deal Sốc - Code & Algorithm History

### 1. Mục tiêu nghiệp vụ
- Hiển thị các gói Deal Sốc (bundle: sản phẩm chính + sản phẩm mua kèm) cho FE.
- Đảm bảo quy tắc ưu tiên giá: **Flash Sale > Promo/Marketing > Deal Sốc > Giá gốc**.
- Tôn trọng tồn kho vật lý (S_phy) từ Warehouse; không ép mua vượt kho.
- Đồng bộ dữ liệu giá/tồn giữa Backend và Frontend (FE chỉ đọc từ API, không tự tính giá).

### 2. Kiến trúc & Vị trí code
- Controller public:
  - `app/Http/Controllers/Api/V1/DealController.php`
  - Routes:
    - `GET /api/v1/deals/active-bundles`
    - `GET /api/v1/deals/{id}/bundle`
- Model sử dụng lại:
  - `App\Modules\Deal\Models\Deal`
  - `App\Modules\Deal\Models\ProductDeal`
  - `App\Modules\Deal\Models\SaleDeal`
- Service dùng chung:
  - `App\Services\Pricing\PriceEngineServiceInterface`
  - `App\Services\Warehouse\WarehouseServiceInterface`

### 3. Giải thuật tính giá Deal Sốc (hiển thị FE)

#### 3.1. Lọc Deal đang hoạt động
- Điều kiện active:
  - `status = '1'`
  - `start <= now()`
  - `end >= now()`
- Eager load quan hệ:
  - `products.product`, `products.variant`
  - `sales.product`, `sales.variant`

#### 3.2. Format bundle trả về FE
- Mỗi Deal trả về:
  - Thông tin Deal: `id`, `name`, `start`, `end`, `status`, `limited`, `is_active`.
  - `products`: danh sách sản phẩm chính với:
    - `product` (id, name, slug, image, has_variants)
    - `variant` (id, sku, option1_value) nếu có.
  - `sale_products`: danh sách sản phẩm mua kèm với:
    - Thông tin product/variant như trên.
    - `deal_price`: giá Deal cấu hình trong `deal_sales.price`.
    - `qty`: số lượng áp dụng Deal cho mỗi đơn (`limited` per order xử lý ở tầng khác).
    - `effective_price`: giá cuối cùng sau khi áp dụng thứ tự ưu tiên.

#### 3.3. Quy tắc chọn `effective_price` cho từng sale product
Pseudocode:
```php
// 1) Lấy giá ưu tiên hiện tại từ PriceEngine (Flash Sale / Promo / Normal)
$displayPrice = PriceEngine::calculateDisplayPrice(product_id, variant_id);

$finalPrice  = $displayPrice['price'];
$finalType   = $displayPrice['type'];   // flashsale | promotion | normal
$finalLabel  = $displayPrice['label'];
$original    = $displayPrice['original_price'];

// 2) Chỉ khi type = normal mới xét tới Deal Sốc
if ($displayPrice['type'] === 'normal') {
    $dealPrice = sale_deal.price;

    // Deal Sốc chỉ thay thế nếu thật sự rẻ hơn giá gốc
    if ($dealPrice > 0 && $dealPrice < $original) {
        $finalPrice  = $dealPrice;
        $finalType   = 'deal';
        $finalLabel  = 'Deal Sốc';
        $discount    = round((($original - $dealPrice) / $original) * 100);
    }
}
```

**Kết luận:**
- Nếu **có Flash Sale hoặc Promo**: FE luôn thấy giá Flash Sale / Promo (Deal Sốc bị “bỏ qua” về giá).
- Nếu **không có Flash Sale / Promo** và có Deal Sốc rẻ hơn giá gốc: dùng giá Deal Sốc.
- Điều này đảm bảo đúng thứ tự ưu tiên giá đã chốt:
  - `flashsale` → `promotion` → `deal` → `normal`.

#### 3.4. Tồn kho (stock) trong bundle
- Với mỗi sale product có `variant_id`:
  - Gọi `WarehouseService::getVariantStock($variantId)` để lấy `current_stock`.
  - Trả về field `stock` (int) trong `sale_products[*]`.
- FE dùng `stock` để disable lựa chọn bundle nếu hết hàng.
- **Không** trừ kho trong API bundle (chỉ hiển thị), mọi trừ kho thực hiện ở luồng đặt hàng/Checkout.

### 4. Auto-Fallback & Locking (Cart + Checkout)

- **Cart (Session/FE refresh)**: Khi item có `is_deal` nhưng quỹ deal đã hết (`buy + qty > sale_deals.qty`) hoặc tồn kho vật lý `S_phy <= 0`, backend tự động hạ về giá thường/khuyến mại và trả `deal_unavailable = true`, `deal_warning = "Quà tặng Deal Sốc đã hết, giá được chuyển về giá thường/khuyến mại."`.
- **Checkout / processOrder**:
  - Bọc toàn bộ trong `DB::beginTransaction()`.
  - Với item `order_type = deal`: `SELECT ... FOR UPDATE` trên dòng `sale_deals` (theo `product_id`, `variant_id` (nếu có), deal active). Chỉ cho phép thanh toán nếu `buy + quantity <= qty`. Nếu không, trả 400 `"Quà tặng đã hết hạn"`.
  - Tăng `buy` ngay trong transaction, trước khi gọi `InventoryService::processOrder`.
  - Vẫn tuân thủ ưu tiên giá: nếu Flash Sale/Promo tồn tại, giá deal không được áp dụng.

### 5. Quy trình “Validate trước khi Render” (Frontend + API)

**5.1. API Detail & Bundle – Data chuẩn hóa (không cache sai trạng thái)**

- `/api/v1/products/{slug}`:
  - Block `deal.sale_deals[]` bắt buộc trả về:
    - `remaining_quota`: `qty - buy` (không âm).
    - `physical_stock`: tồn kho vật lý S_phy từ `WarehouseService::getVariantStock(variant_id)` (fallback variant/product stock nếu lỗi).
    - `available`: `remaining_quota > 0 && physical_stock > 0`.
- `/api/v1/deals/active-bundles`:
  - Mỗi `sale_products[]` cũng có `remaining_quota`, `stock`, `available` với ý nghĩa tương tự.

**5.2. JS Render Rule – KHÔNG tự suy diễn lại, chỉ dựa trên API**

- Khi render block “Mua kèm deal sốc” (`buy-x-get-y-wrapper`):
  - Trước khi append `item_deal_row`, luôn kiểm tra `available`:
    - Nếu `available === false`:
      - Thêm class `.text-muted` hoặc `.deal-sold-out`.
      - Input chọn deal:
        - Radio: `<input type="radio" ... disabled>`
        - Checkbox: `<input type="checkbox" ... disabled>`
      - Hiển thị text đỏ ngay trong item: `"Deal đã hết quà hoặc hết kho"`.
    - Nếu `available === true`: hiển thị bình thường, cho phép click.
- Nút `MUA DEAL SỐC` (`.btnBuyDealSốc`):
  - Nếu **không còn bất kỳ** `deal-checkbox-custom` nào khả dụng (`!disabled`) → `disabled` + text `"HẾT QUÀ"`.
  - Nếu còn deal khả dụng nhưng chưa chọn item → `disabled` + text `"MUA DEAL SỐC"`.
  - Khi có ít nhất một item được check → `enabled`.

**5.3. Chống ghi đè trạng thái (State Persistence)**

- Sau khi render HTML từ API:
  - Tuyệt đối **không** chạy JS nào reset `disabled` trên `.deal-checkbox-custom` đã được set theo API.
  - Hàm `initializeDealControls(deal)` chỉ:
    - Lắng nghe sự kiện `change` để enforce `limited`.
    - Cập nhật trạng thái nút `MUA DEAL SỐC` dựa trên:
      - `hasAvailableDeal = some(!checkbox.disabled)`
      - Số lượng item được check.
- Nếu cần, có thể thêm hàm `reCheckDealStatus()`:
  - Quét lại toàn bộ `.deal-checkbox-custom` và `.item_deal_row` sau khi render; nếu API sau này bổ sung thêm fields (ví dụ `is_expired`), có thể apply thêm các rule mà **không thay đổi cấu trúc DOM**.

### 6. API Response mẫu

#### 4.1. `GET /api/v1/deals/active-bundles`
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Deal sốc tháng 2",
      "start": 1706745600,
      "end": 1709337599,
      "status": "1",
      "limited": 3,
      "is_active": true,
      "products": [
        {
          "id": 10,
          "product_id": 5,
          "variant_id": 12,
          "status": "1",
          "product": {
            "id": 5,
            "name": "Sản phẩm chính A",
            "slug": "san-pham-chinh-a",
            "image": "https://cdn.lica.vn/uploads/images/a.jpg",
            "has_variants": 1
          },
          "variant": {
            "id": 12,
            "sku": "SKU-A-500",
            "option1_value": "500ml"
          }
        }
      ],
      "sale_products": [
        {
          "id": 20,
          "product_id": 8,
          "variant_id": 15,
          "qty": 2,
          "status": "1",
          "product": {
            "id": 8,
            "name": "Sản phẩm mua kèm B",
            "slug": "san-pham-b",
            "image": "https://cdn.lica.vn/uploads/images/b.jpg",
            "has_variants": 1
          },
          "variant": {
            "id": 15,
            "sku": "SKU-B-250",
            "option1_value": "250ml"
          },
          "deal_price": 150000,
          "effective_price": {
            "price": 150000,
            "original_price": 200000,
            "type": "deal",
            "label": "Deal Sốc",
            "discount_percent": 25
          },
          "stock": 30
        }
      ]
    }
  ],
  "count": 1
}
```

### 7. Lịch sử thay đổi

#### 2026-01-20
- **Thêm mới** public API cho Deal Sốc:
  - `GET /api/v1/deals/active-bundles`
  - `GET /api/v1/deals/{id}/bundle`
- **Gia cố** Deal Sốc ở Cart/Checkout:
  - Cart: fallback khi quỹ/hết kho, trả `deal_unavailable` + warning đỏ.
  - Checkout: transaction + `FOR UPDATE` trên `sale_deals`, check `buy + quantity <= qty`, tăng `buy` trong transaction.
- **Files created:**
  - `app/Http/Controllers/Api/V1/DealController.php`
  - `DEALSOC-CODE.MD`
- **Files updated:**
  - `routes/api.php` – đăng ký route v1/deals.
  - `app/Services/Cart/CartService.php` – fallback Deal Sốc trong cart.
  - `app/Http/Controllers/OrderProcessingController.php` – lock + check quỹ deal khi thanh toán.
  - `database/migrations/2026_01_20_000000_add_buy_to_deal_sales_table.php` – thêm cột `buy`.
- **Ghi chú:** Ưu tiên giá vẫn: Flash Sale → Promo → Deal Sốc → Base. Nếu Flash Sale/Promo tồn tại, giá Deal Sốc bị bỏ qua.

#### 2026-01-19
- Fix: Marketing Campaign price was ignored and UI fell back to base price (e.g. 550000) after JS finished loading.
- Root cause: `PriceEngineService::getPromotionPrice()` selected the first active campaign, then looked up product inside it. If the product belongs to a different active campaign, promotion was skipped => returned `normal` price.
- Fix (server): query `MarketingCampaignProduct` by `product_id` and `whereHas(campaign active)`; do NOT pick an arbitrary active campaign first.
- Fix (API): `/api/v1/products/{slug}` must expose pricing from PriceEngine for every variant:
  - `price` should be `final_price` (single source of truth for FE)
  - also return `final_price`, `original_price`, `price_type`, and keep `price_info.html`
- Fix (FE): product detail JS must render price from `price_info.html` / `final_price` only; never recompute or overwrite with raw DB `price`.
- Debug: add `console.log('Price Sync:', final_price)` to confirm API->UI consistency.

#### 2026-01-21
- Deal Sốc quota safety + refund:
  - Table name: `deal_sales`; quota column now treated as **remaining slots**: `qty`; consumed column: `buy` (reporting).
  - Available rule: `available = (physical_stock > 0) && (qty > 0)`.
  - Order placement: inside DB transaction, `SaleDeal::lockForUpdate()`; re-check `qty >= quantity`; then `decrement('qty', quantity)` + `increment('buy', quantity)`; else throw `"Suất quà tặng vừa hết"`.
  - Missing link fix (persist dealsale_id):
    - Cart session must carry `dealsale_id` for deal items.
    - Save `orderdetail.dealsale_id` when creating order details; otherwise cancel/refund and auditing cannot map the consumed slot.
    - FE checkout path: `app/Themes/Website/Controllers/CartController.php::postCheckout` inserts `orderdetail` and also decrements `deal_sales.qty` (Shopee style) in the same DB transaction.
    - Model gate: `app/Modules/Order/Models/OrderDetail.php` must include `dealsale_id` in `$fillable`.
  - Order cancel (status in [2,4], once per order):
    - Chốt chặn hoàn quỹ trùng: chỉ hoàn quỹ khi `oldStatus` **không** thuộc nhóm cancel `[2,4]` (tránh hoàn quỹ 2 lần).
    - Trong `DB::transaction`, iterate `orderdetail.dealsale_id`:
      - `SaleDeal::lockForUpdate()` để tránh race condition.
      - `increment('qty', detail.qty)` - cộng lại suất vào numbersale.
      - `decrement('buy', detail.qty)` hoặc `buy = max(0, buy - detail.qty)` - trừ số lượng đã mua.
    - Đồng thời: `createImportReceiptFromOrder()` để trả kho vật lý (Warehouse).
    - Logging: `[DEAL_REFUND] Order: {code} | DealID: {id} | Suất được trả lại: {qty} | Qty trước: {old} → Qty sau: {new} | Buy trước: {old} → Buy sau: {new}`.
  - FE (product detail): if API `available=false`, auto uncheck, disable input, add `.text-muted` and toast `"Rất tiếc, quà tặng này vừa hết suất ưu đãi!"`.
  - Debug logging:
    - `[DEAL_SALE_UPDATE] DealID: {dealsale_id} | Suất cũ: {old_qty} | Suất mới sau khi trừ: {new_qty}`
    - `[DealQuota] before decrement/after decrement` for server-side verification.
    - `[Order_Cancel_Debug] Checking detail for refund {detail_id, dealsale_id, qty}` for cancel/refund tracing.

#### 2026-01-23
- Fix: Hoàn quỹ Deal Sốc khi hủy đơn không hoạt động đúng:
  - Root cause: Hàm `rollbackDealQuota` đang dùng `save()` thay vì `increment/decrement` trực tiếp, và thiếu logging chi tiết.
  - Fix (server): 
    - Sửa `app/Modules/Order/Controllers/OrderController.php::rollbackDealQuota()`:
      - Dùng `increment('qty', $qty)` để cộng lại suất vào numbersale.
      - Dùng `decrement('buy', $qty)` hoặc `buy = max(0, buy - qty)` để trừ số lượng đã mua.
      - Thêm logging chi tiết: `[DEAL_REFUND] Order: {code} | DealID: {id} | Suất được trả lại: {qty} | Qty trước: {old} → Qty sau: {new} | Buy trước: {old} → Buy sau: {new}`.
    - Chốt chặn hoàn quỹ trùng: Logic trong `postUpdate()` sử dụng nhóm trạng thái cancel `[2,4]` và điều kiện `oldStatus` **không** thuộc nhóm này để đảm bảo chỉ hoàn quỹ 1 lần duy nhất.
    - Thêm debug: `[Order_Cancel_Debug] Status Change {old => new}` để audit đúng mã trạng thái hủy (2 hay 4 tùy DB).
  - Quy trình hoàn quỹ:
    - Khi đơn hàng chuyển sang trạng thái thuộc nhóm cancel (2 hoặc 4) và `oldStatus` không thuộc nhóm này:
      - Trong `DB::transaction`, gọi `rollbackDealQuota($order)` để hoàn suất Deal.
      - Đồng thời gọi `createImportReceiptFromOrder($order)` để trả kho vật lý.
      - Nếu lỗi, `DB::rollBack()` để đảm bảo tính nhất quán dữ liệu.
  - Manual test:
    - Xem numbersale hiện tại (ví dụ: 3).
    - Hủy đơn hàng có chứa Deal Sốc.
    - Kiểm tra: numbersale phải tăng lên (ví dụ: 4), `deal_sales.qty` tăng, `deal_sales.buy` giảm.

