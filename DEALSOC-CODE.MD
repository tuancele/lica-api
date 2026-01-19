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
  - Nếu **tất cả** `deal-checkbox-custom` đều `disabled` (hết suất) → **Loại bỏ class `btnBuyDealSốc`**, `enabled` + text `"Mua ngay"` → Nút quay về function mua hàng thường (chỉ add sản phẩm chính, không kèm dealsale_id).
  - Nếu còn deal khả dụng nhưng chưa chọn item → `disabled` + text `"MUA DEAL SỐC"`.
  - Khi có ít nhất một item được check → `enabled` + text `"MUA DEAL SỐC"`.
- Logic ràng buộc giỏ hàng:
  - Xóa sản phẩm chính → tự động xóa toàn bộ sản phẩm mua kèm thuộc cùng deal (CartService::removeItem gọi removeRelatedDealItems).
  - Giới hạn mua kèm: kiểm tra hạn mức (qty của sale_deals được dùng như limited_per_order) ngay khi add; nếu vượt, báo lỗi `"Bạn đã đạt giới hạn số lượng quà tặng cho ưu đãi này"`.
  - Sau khi remove/add qua API, nếu server trả `removed_variant_ids`, FE reload trang giỏ để làm sạch sản phẩm phụ.

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

#### 2026-01-23 (Update 2)
- Fix: Logic hiển thị nút "MUA DEAL SỐC" khi tất cả deal hết suất:
  - Root cause: Khi tất cả deal hết, nút bị `disabled` và hiển thị "HẾT QUÀ", khiến khách không thể mua sản phẩm chính.
  - Fix (frontend):
    - Sửa hàm `updateBuyDealButton()` trong `app/Themes/Website/Views/product/detail.blade.php`:
      - Khi tất cả deal hết (`allDealsOutOfStock`): Loại bỏ class `btnBuyDealSốc`, `enabled` nút, đổi text về `"Mua ngay"`.
      - Logic click tự động fallback về mua hàng thường (không còn class `btnBuyDealSốc` → chỉ add sản phẩm chính, không kèm dealsale_id).
    - Sửa hàm `initializeDealControls()`: Tìm nút bằng `.buyNowDetail` thay vì `.btnBuyDealSốc` để đảm bảo luôn tìm thấy nút ngay cả khi tất cả deal hết.
  - Quy tắc hiển thị nút:
    - Tất cả deal hết → Nút "Mua ngay" (enabled, mua hàng thường).
    - Còn deal available nhưng chưa chọn → Nút "MUA DEAL SỐC" (disabled, yêu cầu chọn).
    - Đã chọn deal → Nút "MUA DEAL SỐC" (enabled, mua deal).

#### 2026-01-23 (Update 3)
- Fix: Logic giỏ hàng (tổng tiền, ràng buộc xóa, giới hạn mua kèm)
  - Price sync: `CartService::removeItem` trả về summary/items sau khi gọi `getCart()` để luôn dùng PriceEngine; `CartController` vẫn tính giá từng item bằng PriceEngine.
  - Ràng buộc xóa: Xóa sản phẩm chính sẽ tự động xóa toàn bộ sản phẩm mua kèm cùng deal (CartService::removeItem → removeRelatedDealItems).
  - Giới hạn mua kèm:
    - Khi add deal item, kiểm tra hạn mức (dùng `sale_deals.qty` như limited per order).
    - Nếu **variant đã có trong giỏ** và khách bấm thêm vượt limit: không throw cứng, clamp/no-op để tránh UX lỗi khi click lặp.
    - Chỉ throw `"Bạn đã đạt giới hạn số lượng quà tặng cho ưu đãi này"` khi khách cố tình thêm mới vượt limit.
  - Sidebar tổng tiền:
    - `public/js/cart-api-v1.js::updateCartUI` bắt buộc set `.total-price` (và `.subtotal-price` nếu có) từ `summary.total/summary.subtotal` từ backend, không tự cộng trừ bằng JS.
    - `cart/index.blade.php` các chỗ update sidebar sau AJAX phải dùng `summary.total` (fallback `summary.subtotal`).
    - Fix SSR/F5: `cart/index.blade.php` không được dùng biến `$totalPrice` bên trong vòng lặp item (tránh ghi đè sidebar total). Dùng biến riêng như `$lineTotalPrice`.
  - Xóa & sync:
    - Sau `removeItem`, FE gọi lại `getCart()` để đồng bộ sidebar/tổng tiền theo backend.
  - Fix "Phải F5 mới thêm được quà sau khi xóa" (Memory Flush - Architecture Fix):
    - Root cause: Service có thể giữ dữ liệu cũ trong memory thông qua biến trung gian `$cart`, không tự động refresh từ session.
    - Solution (Architecture-level):
      - Thêm thuộc tính `protected $items = []` vào `CartService` để lưu items array synced từ session.
      - Tạo hàm `syncWithSession()`: Sync `$this->items` từ `Session::get('cart')`, đảm bảo luôn đọc dữ liệu mới nhất, không cache trong Service.
      - Gọi `syncWithSession()` ở đầu mỗi hàm: `addItem`, `removeItem`, `getCart`.
      - Trong `addItem` khi kiểm tra deal limit: **Dùng `$this->items` (đã sync từ session)** để đếm deal items, không dùng biến `$cart` trung gian.
      - Trong `removeItem`: Sau khi persist session (`Session::put('cart', $cart) + session()->save()`), gọi lại `syncWithSession()` để cập nhật `$this->items`.
    - Backend (`CartService::removeItem`): 
      - Gọi `syncWithSession()` ở đầu hàm.
      - Sau khi xóa, persist session ngay (`Session::put/forget + session()->save()`).
      - Sau khi persist, gọi lại `syncWithSession()` để sync `$this->items`.
      - Log cart state sau xóa để debug.
    - Backend (`CartService::addItem`): 
      - Gọi `syncWithSession()` ở đầu hàm.
      - Khi kiểm tra deal limit: Dùng `$this->items` (đã sync từ session) thay vì biến `$cart` trung gian.
      - Thêm log `[Cart_Final_Check] Items count: ' . count($this->items)` ngay trước dòng check limit_deal.
    - Frontend (`cart/index.blade.php`):
      - Set `window.cartProcessing = true` khi bắt đầu remove, `false` sau khi getCart() hoàn tất.
      - Disable tất cả `.addDealCart` buttons trong khi processing.
      - Thêm delay 150ms trước khi gọi `getCart()` sau remove để đảm bảo session flush hoàn tất.
      - Handler `addDealCart` kiểm tra `window.cartProcessing` và chặn nếu đang processing.
    - Session Driver: Kiểm tra `config/session.php` - driver là `file`, thư mục `storage/framework/sessions` có quyền ghi (BUILTIN\Users có ReadAndExecute, Authenticated Users có Modify).
    - Logging: Thêm log `[CartService] removeItem - Cart state after removal`, `[CartService] addItem - Cart state before deal limit check`, và `[Cart_Final_Check] Items count: X` để debug memory sync.

#### 2026-01-23 (Update 4) - Frontend State Sync để xử lý triệt để độ trễ Session
- Fix: Vẫn phải F5 mới thêm được Deal Sốc sau khi xóa (mặc dù đã có memory sync ở backend).
- Root cause: Backend đã sync từ session nhưng vẫn có độ trễ giữa các request HTTP. Frontend không có cơ chế quản lý trạng thái Deal riêng, phụ thuộc hoàn toàn vào backend response.
- Solution (Frontend State Management):
  - **Bước 1: Quản lý trạng thái Deal bằng JavaScript**
    - Tạo mảng toàn cục `window.activeDeals = []` để theo dõi các Deal đang có trong giỏ.
    - Khởi tạo khi trang load (`DOMContentLoaded`): Duyệt toàn bộ `.is-deal-row`, lấy `variantId` và `dealId`, đẩy vào mảng.
    - File: `app/Themes/Website/Views/cart/index.blade.php`.
  - **Bước 2: Chặn và Cập nhật tức thì khi Xóa (Frontend Sync)**
    - Khi xóa thành công: Xóa deal khỏi `window.activeDeals` ngay lập tức (không đợi session).
    - Cập nhật UI các nút "THÊM NGAY" ngay lập tức: Enable button nếu deal đã được xóa khỏi giỏ.
    - Xóa DOM ngay để tránh rác dữ liệu trong UI.
    - File: `app/Themes/Website/Views/cart/index.blade.php` (handler `removeItem`).
  - **Bước 3: Kiểm tra tại nút "THÊM NGAY" (The Gatekeeper)**
    - Thay vì chỉ dựa vào Response của Server, kiểm tra mảng `window.activeDeals`.
    - Nếu deal không còn trong mảng (vừa xóa xong), cho phép gửi Request `addItem` đi.
    - Thêm tham số `force_refresh=1` vào API call để ép backend đọc lại session từ storage.
    - File: `app/Themes/Website/Views/cart/index.blade.php` (handler `.addDealCart`).
  - **Bước 4: Vá lỗi Backend (addItem Force Refresh)**
    - `CartController::addItem()` nhận tham số `force_refresh` từ query string.
    - `CartService::addItem()` nhận tham số `$forceRefresh` (bool).
    - Nếu `force_refresh=true`: Gọi `session()->save()` và `session()->regenerate(false)` để ép Laravel đọc lại session từ storage, bỏ qua mọi cache nội bộ của PHP.
    - Files: `app/Http/Controllers/Api/V1/CartController.php`, `app/Services/Cart/CartService.php`.
  - **Bước 5: Loại bỏ thuộc tính $items khỏi bộ nhớ Service (Architecture Cleanup)**
    - Xóa `protected array $items = []` khỏi `CartService`.
    - Xóa hàm `syncWithSession()` (không cần nữa).
    - Thêm hàm `getCartItemsFromSession()` để đọc trực tiếp từ session khi cần.
    - Tất cả các hàm (`addItem`, `removeItem`, `getCart`) đọc trực tiếp từ `session()->get('cart', [])` thay vì dùng biến tạm trong memory.
    - File: `app/Services/Cart/CartService.php`.
  - **Bước 6: Ép ghi Session vật lý ngay khi Xóa**
    - Trong `removeItem()`: Thứ tự `removeItem()` → `session()->put('cart', $cart)` → `session()->save()`.
    - Đảm bảo session được flush ngay lập tức, không đợi request kết thúc.
    - File: `app/Services/Cart/CartService.php`.
  - **Bước 7: Sửa logic Validation trong addItem**
    - Đọc trực tiếp từ session: `$currentCart = session()->get('cart', null)`.
    - Extract `items` array từ Cart object.
    - Đếm deal items từ `$currentCartItems` (không dùng biến tạm trong memory).
    - Logging chi tiết: `[CART_VALIDATION] Kiểm tra Deal ID: X | Số lượng trong giỏ FRESH: Y`.
    - File: `app/Services/Cart/CartService.php`.
  - **Frontend API Update:**
    - `public/js/cart-api-v1.js::addItem()` nhận tham số `forceRefresh` và thêm vào query string nếu `true`.
  - **Kết quả:**
    - Frontend quản lý trạng thái Deal độc lập, không phụ thuộc hoàn toàn vào backend response.
    - Khi xóa, UI cập nhật ngay lập tức, không cần đợi session flush.
    - Khi thêm, kiểm tra trạng thái frontend trước, chỉ gửi request khi deal không còn trong giỏ.
    - Backend force refresh session khi nhận `force_refresh=1` để đảm bảo đọc dữ liệu mới nhất.
    - Không còn biến tạm trong memory Service, tất cả đọc trực tiếp từ session.
- Files modified:
  - `app/Themes/Website/Views/cart/index.blade.php` - Frontend state management
  - `public/js/cart-api-v1.js` - API call với force_refresh
  - `app/Http/Controllers/Api/V1/CartController.php` - Nhận force_refresh
  - `app/Services/Cart/CartService.php` - Force refresh session, loại bỏ memory cache

#### 2026-01-23 (Update 7) - Fix hiển thị Tổng tiền 0đ và bị âm do logic tính giá phân tầng Flash Sale
- Fix: Trang thanh toán (`/cart/thanh-toan`) hiển thị tổng giá trị đơn hàng là `0đ` hoặc bị âm do logic tính giá phân tầng Flash Sale (mua vượt hạn mức tính giá thường).
- Root cause:
  - JavaScript đang tự tính từ DOM thay vì dùng dữ liệu từ backend.
  - Hàm `updateTotalOrderPriceCheckout()` vẫn có logic lặp qua các dòng sản phẩm để cộng tiền.
  - Khi AJAX thay đổi (địa chỉ/coupon), không cập nhật `window.checkoutData` trước khi gọi `updateTotalOrderPriceCheckout()`.
- Fix (Backend):
  - **Bước 1: Đồng bộ hóa "Sự thật duy nhất" (Backend Data)**:
    - Biến `$totalPrice` truyền từ Controller xuống View phải là con số cuối cùng sau khi đã cộng dồn tất cả các tầng giá (giá flash sale + giá thường vượt mức + giá quà tặng).
    - Đảm bảo `PriceEngineService` trả về tổng tiền khớp với giỏ hàng (588,000đ).
    - File: `app/Themes/Website/Controllers/CartController.php` (đã sử dụng `CartService::getCart()`).
- Fix (Frontend):
  - **Bước 2: Khóa dữ liệu vào biến toàn cục (Global Data Lock)**:
    - Cập nhật `window.checkoutData` với comment rõ ràng:
      - `subtotal`: Tổng tiền hàng (đã tính gộp các mức flash sale/thường) từ Backend.
      - `sale`: Số tiền giảm giá từ Coupon.
      - `feeship`: Phí ship hiện tại.
      - `total`: Tổng thanh toán (không bao giờ âm).
    - JavaScript CHỈ ĐƯỢC PHÉP đọc số từ đây, không được tự tính toán lại.
  - **Bước 3: Sửa hàm JavaScript updateTotalOrderPriceCheckout()**:
    - Loại bỏ hoàn toàn việc lặp qua các dòng sản phẩm trên UI để cộng tiền.
    - Xóa bỏ logic `$('.price-item').each(...)`.
    - Ép hàm chỉ làm nhiệm vụ hiển thị:
      - `$('.subtotal-cart').text(formatPrice(data.subtotal) + 'đ')`.
      - `$('.sale-promotion').text('-' + formatPrice(data.sale) + 'đ')`.
      - `$('.total-order').text(formatPrice(grandTotal) + 'đ')`.
    - Đọc `feeship` từ `input[name="feeShip"]` để đồng bộ với AJAX.
  - **Bước 4: Cập nhật khi AJAX thay đổi (Địa chỉ/Coupon)**:
    - Đảm bảo khi gọi API áp dụng Coupon hoặc tính phí Ship, kết quả trả về từ Server phải cập nhật lại biến `window.checkoutData` trước khi gọi `updateTotalOrderPriceCheckout()`.
    - Áp dụng cho:
      - `.btn_save_address` (chọn địa chỉ).
      - `getFeeShip()` (tính phí ship).
      - `$('#ward').change()` (chọn tỉnh thành).
      - `.btn_coupon` (áp dụng coupon).
      - `.btn_apply` (áp dụng promotion).
      - `.btn_cancel_promotion` (hủy coupon).
  - File: `app/Themes/Website/Views/cart/checkout.blade.php`.
- Quy tắc tính giá:
  - Backend: Tất cả giá phải được tính từ `CartService::getCart()` (sử dụng `PriceEngineService`), bao gồm cả logic phân tầng Flash Sale.
  - Frontend: Chỉ đọc giá từ `window.checkoutData` (được khởi tạo từ backend), **KHÔNG tự tính từ DOM**.
  - Tổng cuối cùng không được phép nhỏ hơn 0: `Math.max(0, subtotal - sale + feeship)`.
- Files modified:
  - `app/Themes/Website/Views/cart/checkout.blade.php` - Khóa dữ liệu sạch, sửa hàm tính tổng, cập nhật AJAX handlers

#### 2026-01-23 (Update 6) - Fix hiển thị Tổng tiền 0đ và bị âm tại trang Thanh toán
- Fix: Trang thanh toán (`/cart/thanh-toan`) hiển thị tổng giá trị đơn hàng là `0đ` hoặc bị âm.
- Root cause:
  - Biến `$totalPrice` trong Controller đang được tính thủ công từ vòng lặp, có thể bị sai hoặc bằng 0.
  - JavaScript đang tự tính từ DOM thay vì dùng dữ liệu từ backend.
  - Không có chốt chặn để ngăn coupon áp dụng khi subtotal chưa đúng.
- Fix (Backend):
  - **Bước 1: Sửa hàm checkout() trong Controller**:
    - Inject `CartService` vào constructor.
    - Gọi `CartService::getCart()` để lấy tổng tiền chuẩn từ `summary.subtotal`.
    - CÔNG THỨC: `$totalPrice = Sum(PriceEngine::calculate(item) * qty)` - được tính bởi CartService.
    - Lấy `productsWithPrice` từ CartService để đảm bảo tính nhất quán.
    - Thêm logging: `[CartController::checkout] Total price from CartService` với `totalPrice`, `summary`, `items_count`.
    - File: `app/Themes/Website/Controllers/CartController.php`.
- Fix (Frontend):
  - **Bước 2: Khóa dữ liệu "Sạch" vào JavaScript**:
    - Cập nhật `window.checkoutData` với `max(0, ...)` để đảm bảo tổng cuối cùng không nhỏ hơn 0.
    - File: `app/Themes/Website/Views/cart/checkout.blade.php`.
  - **Bước 3: Sửa hàm updateTotalOrderPriceCheckout()**:
    - Buộc giao diện phải hiển thị theo `window.checkoutData`.
    - Gán: `$('.subtotal-cart').text(formatPrice(window.checkoutData.subtotal) + 'đ')`.
    - Gán: `$('.total-order').text(formatPrice(window.checkoutData.total) + 'đ')`.
    - **CẤM**: Không được dùng hàm lặp `each()` qua danh sách sản phẩm để cộng tiền ở trang Checkout nữa.
    - Dữ liệu từ Backend gửi xuống là duy nhất.
    - Gọi `updateTotalOrderPriceCheckout()` khi trang load để hiển thị giá đúng ngay từ đầu.
  - **Bước 4: Thêm Chốt chặn Coupon**:
    - Nếu `window.checkoutData.subtotal` chưa được tính đúng (<= 0), tuyệt đối không cho phép hàm `applyCoupon` chạy.
    - Hiển thị cảnh báo: "Không thể áp dụng coupon: Tổng tiền đơn hàng chưa được tính đúng. Vui lòng làm mới trang."
    - Áp dụng cho cả `.btn_coupon` và `.btn_apply`.
  - File: `app/Themes/Website/Views/cart/checkout.blade.php`.
- Quy tắc tính giá:
  - Backend: Tất cả giá phải được tính từ `CartService::getCart()` (sử dụng `PriceEngineService`).
  - Frontend: Chỉ đọc giá từ `window.checkoutData` (được khởi tạo từ backend), **KHÔNG tự tính từ DOM**.
  - Tổng cuối cùng không được phép nhỏ hơn 0: `Math.max(0, subtotal - sale + feeship)`.
- Files modified:
  - `app/Themes/Website/Controllers/CartController.php` - Sử dụng CartService::getCart()
  - `app/Themes/Website/Views/cart/checkout.blade.php` - Khóa dữ liệu sạch, sửa hàm tính tổng, thêm chốt chặn coupon

#### 2026-01-23 (Update 5) - Fix tính tiền trang thanh toán
- Fix: Trang thanh toán (`/cart/thanh-toan`) hiển thị tổng giá trị đơn hàng là `0đ` mặc dù có items trong giỏ.
- Root cause: 
  - `$recalculatedTotal` có thể bằng 0 nếu `PriceEngineService::calculatePriceWithQuantity` trả về giá trị không hợp lệ.
  - JavaScript `updateTotalOrderPriceCheckout()` đang tự tính từ DOM (lặp qua `.price-item-...`), gây sai lệch với Deal Sốc 0đ.
- Fix (Backend):
  - Thêm validation và fallback trong `CartController::checkout()`:
    - Kiểm tra `total_price` từ `PriceEngineService`, nếu không hợp lệ thì fallback về giá variant.
    - Đảm bảo `$recalculatedTotal` luôn có giá trị hợp lệ (nếu bằng 0 nhưng có items, fallback về `$cart->totalPrice`).
    - Thêm logging chi tiết để debug: `[CartController::checkout] Price recalculated` với `items_count`, `products_with_price`.
  - File: `app/Themes/Website/Controllers/CartController.php`.
- Fix (Frontend):
  - **Bước 1: Khóa dữ liệu giá vào JavaScript**:
    - Khởi tạo `window.checkoutData` từ backend khi trang load:
      ```javascript
      window.checkoutData = {
          subtotal: {{ $totalPrice }},
          total: {{ $totalPrice - $sale + $feeship }},
          sale: {{ $sale }},
          feeship: {{ $feeship }}
      };
      ```
    - Khởi tạo `window.checkoutPriceBreakdowns` từ `$productsWithPrice` để lưu chi tiết từng item.
  - **Bước 2: Sửa hàm tính tiền JavaScript**:
    - `updateTotalOrderPriceCheckout()`:
      - **Ưu tiên 1**: Lấy từ `window.checkoutData.subtotal` (từ backend).
      - **Ưu tiên 2**: Tính từ `window.checkoutPriceBreakdowns` (từ backend).
      - **CẤM**: Tuyệt đối không dùng hàm lặp qua các class `.price-item-...` để tự cộng bằng JS vì Deal Sốc 0đ sẽ làm sai lệch logic cộng dồn.
    - Gán giá trị vào UI: `$('.subtotal-cart').text(formatPrice(subtotal) + 'đ')` và `$('.total-order').text(formatPrice(finalTotal) + 'đ')`.
  - **Bước 3: Đồng bộ phí vận chuyển**:
    - Khi phí ship thay đổi (chọn tỉnh thành), cập nhật `window.checkoutData.feeship` và gọi `updateTotalOrderPriceCheckout()`.
    - Áp dụng cho các hàm: `getFeeShip()`, `$('#ward').change()`, `btn_save_address`.
  - **Bước 4: Đồng bộ giảm giá (Coupon)**:
    - Khi áp dụng/hủy coupon, cập nhật `window.checkoutData.sale` và gọi `updateTotalOrderPriceCheckout()`.
    - Áp dụng cho các hàm: `.btn_coupon`, `.btn_apply`, `.btn_cancel_promotion`.
  - **Bước 5: Thêm Log Debug**:
    - Console log: `[Checkout_Price] subtotal from backend: ...`, `[Checkout_Price] Final calculation: {...}`.
  - File: `app/Themes/Website/Views/cart/checkout.blade.php`.
- Quy tắc tính giá:
  - Backend: Tất cả giá phải được tính từ `PriceEngineService::calculatePriceWithQuantity`.
  - Frontend: Đọc giá từ `window.checkoutData` hoặc `window.checkoutPriceBreakdowns` (được khởi tạo từ backend), **KHÔNG tự tính từ DOM**.
  - Đảm bảo giá Deal Sốc (kể cả 0đ) được tính đúng và hiển thị đúng.
- Files modified:
  - `app/Themes/Website/Controllers/CartController.php` - Validation và fallback cho giá
  - `app/Themes/Website/Views/cart/checkout.blade.php` - Khóa dữ liệu vào JS, sửa hàm tính tổng, đồng bộ phí ship và coupon

