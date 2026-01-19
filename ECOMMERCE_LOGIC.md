# Tổng quan hệ thống eCommerce (tóm tắt ngắn)

## Định giá & ưu tiên
- Thứ tự giá: `flash_price` (nếu còn suất) → `promotion/promo_price` (nếu có) → `original_price`.
- Mixed Pricing Flash Sale: nếu `Q > S_flash_remaining`, phần trong hạn dùng `flash_price`, phần vượt tính giá tiếp theo (promo/normal). API trả `price_breakdown` và `warning`.
- Cart/Checkout luôn tính lại qua `PriceEngineService::calculatePriceWithQuantity`; không tin session/FE.
- Order lưu: backend re-calc, nếu lệch FE → dùng backend hoặc báo lỗi.

## Tồn kho & Warehouse
- `S_phy`: tồn kho thực tế lấy từ `WarehouseService::getVariantStock`.
- `S_flash`: tồn kho ảo Flash Sale (`number - buy`).
- `Available_Stock`: tùy ngữ cảnh = `S_flash_remaining` cho FS; kiểm tra `quantity <= S_phy`.
- Nếu `quantity > S_phy`: `is_available=false`, trả lỗi: “Rất tiếc, sản phẩm này chỉ còn tối đa ${S_phy}…”.
- Order Flash Sale: `flash_stock_sold` chạm trần `flash_stock_limit`; trừ toàn bộ `Q` vào `total_stock` (S_phy).

## API chính
- `POST /api/price/calculate`: nhập product/variant + quantity → trả `total`, `price_breakdown`, `warning`, `total_physical_stock`, `is_available`, `stock_error`.
- Order processing: dùng mixed pricing + kiểm tra Warehouse; log sự kiện vượt FS.

## Frontend (Cart/Checkout/Product Detail)
- JS `flash-sale-mixed-price.js`: gọi API khi đổi số lượng; cảnh báo vàng “Vượt quá số lượng Flash Sale”; nếu hết S_phy hiển thị cảnh báo đỏ, auto chỉnh quantity và disable checkout/add-to-cart.
- Cart/Checkout render sẵn breakdown & warning từ backend; JS cập nhật tổng tiền sau mỗi tính giá; ẩn warning vàng ở checkout theo yêu cầu.
- Nút +/- và input change đều trigger tính giá; Observer/MutationObserver tránh mất binding.

## Admin Deal & Flash Sale
- Deal product selection dùng `marketing-product-search.js` (modal) cho cả sản phẩm chính/phụ; lọc tồn kho > 0, append không ghi đè.
- Ràng buộc:
  1) Sản phẩm chính của deal không thể là sản phẩm phụ trong cùng deal.
  2) Sản phẩm chính của deal khác đang active không được chọn làm sản phẩm chính (chỉ có thể làm phụ).
  3) Sản phẩm phụ ở deal 1 có thể là sản phẩm chính ở deal 2.
- Khi AJAX chọn sp: chỉ trả rows mới để append; full load lấy toàn bộ từ session.

## Logging & Marketing
- `InventoryService::processFlashSaleOrder` log `[FlashSale_MixedPrice] Order_ID … FS_Qty … Normal_Qty … Extra_Revenue …`.
- Cart/Checkout log giá trị giỏ & kiểm tra lệch tổng.

## Testing
- `tests/Unit/FlashSaleMixedPriceTest.php`: mua vượt hạn mức → assert tổng tiền, flash_buy, physical stock, warning.

## Tài liệu liên quan
- `FLASHSALE.MD`: chi tiết mixed pricing, tồn kho thực tế, bảng kịch bản.
- `API_ADMIN_DOCS.md`: endpoint admin/API; cần cập nhật khi thêm API mới.
