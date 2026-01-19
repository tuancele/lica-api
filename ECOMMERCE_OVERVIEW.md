## Tổng quan hệ thống eCommerce

- **Kiến trúc**: Laravel đa module (`app/Modules`), front Website theme (`app/Themes/Website`), Admin modules (FlashSale, Deal, ApiAdmin).
- **Nguồn dữ liệu kho**: WarehouseService cung cấp tồn thực tế `S_phy`; Flash Sale quản lý `S_flash`.
- **Dòng giá**: Ưu tiên Flash Sale → Promo → Giá gốc; hỗ trợ mixed pricing khi mua vượt hạn mức FS.
- **Đồng bộ backend-first**: Mọi giá/tồn phải tái tính ở backend (CartService, PriceEngine, Checkout) dù frontend đã hiển thị.
- **Tài liệu chi tiết**: 
  - Giá & kho: `ECOMMERCE_PRICING_INVENTORY.md`
  - Giỏ hàng & thanh toán: `ECOMMERCE_CART_CHECKOUT.md`
  - Flash Sale & Deal: `ECOMMERCE_MARKETING.md`

### Thuật ngữ nhanh
- `S_phy`: tồn kho vật lý từ Warehouse.
- `S_flash`: hạn mức Flash Sale (ảo).
- `available_flash_stock`: phần FS còn lại cho giá rẻ.
- `price_breakdown`: mảng chi tiết (FS + thường).
- `is_available`: còn đủ tồn thực tế (so với `quantity`).

### Luồng chính
1) Product Detail / Cart / Checkout gọi `POST /api/price/calculate` để lấy giá, breakdown, warning, check tồn thực tế.
2) CartService luôn tái tính giá từng item qua PriceEngine (không tin giá session).
3) Checkout re-calc toàn bộ, so sánh với frontend; nếu lệch dùng số backend.
4) Đặt hàng Flash Sale: trừ full `quantity` vào `S_phy`, `flash_stock_sold` bị chặn trần `flash_stock_limit`.

### Logging & test
- Log `[FlashSale_MixedPrice]` khi vượt hạn mức; ghi FS_Qty, Normal_Qty, Extra_Revenue.
- Unit test mẫu: `tests/Unit/FlashSaleMixedPriceTest.php` kiểm tra tổng tiền, tồn, warning.
