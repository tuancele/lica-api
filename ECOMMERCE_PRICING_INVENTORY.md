## Giá & Tồn kho

### Ưu tiên giá
1) Flash Sale (`flash_price`) trong hạn mức `S_flash`.
2) Promo/khuyến mại (nếu có).
3) Giá gốc.

### Mixed Pricing (mua vượt hạn mức FS)
- Nếu `Q > S_flash_rem`: phần `S_flash_rem` dùng flash_price, phần còn lại dùng giá ưu tiên tiếp theo.
- Trả về `price_breakdown` (ví dụ: `5 x 100k + 2 x 150k`) và `warning`: "Chỉ còn X sản phẩm giá Flashsale, Y sản phẩm còn lại sẽ được tính theo giá thường".
- Api: `POST /api/price/calculate` (OrderProcessingController) → dùng PriceEngineService.
  - Request: `product_id`, `variant_id` (optional), `quantity`.
  - Response: `total_price`, `price_breakdown`, `flash_sale_remaining`, `warning`, `total_physical_stock`, `is_available`, `stock_error`.

### Kiểm tra tồn thực tế (Warehouse)
- PriceEngineService gọi `WarehouseService::getVariantStock($variantId)` để lấy `S_phy`.
- Nếu `quantity > S_phy`: `is_available=false`, trả về `stock_error`: "Rất tiếc, sản phẩm này chỉ còn tối đa ${S_phy}...".
- Frontend phải khóa nút Checkout/Add-to-cart, set lại quantity = `total_physical_stock`, ẩn breakdown.

### Trừ kho khi đặt hàng Flash Sale
- `flash_stock_sold` bị chặn trần `flash_stock_limit` khi user mua vượt.
- Toàn bộ `quantity` luôn trừ vào `total_stock` (S_phy) qua Warehouse.
- Transaction + row locking giữ nguyên như luồng cũ.

### Cảnh báo & logging
- Khi vượt hạn mức FS: warning vàng ở FE; log `[FlashSale_MixedPrice] Order_ID: {id}, Product: {name}, FS_Qty, Normal_Qty, Extra_Revenue`.
- Nếu thiếu tồn thực tế: cảnh báo đỏ, disable hành động.

### Bảng tình huống
| Tình huống | Số lượng mua | Cách tính tiền | Trừ kho |
|---|---|---|---|
| Trong hạn mức | Q ≤ S_flash | Q × Price_FS | Giảm S_flash & S_phy |
| Vượt hạn mức | Q > S_flash | (S_flash × Price_FS) + (Q - S_flash) × Price_Normal | S_flash → 0, Giảm S_phy |
