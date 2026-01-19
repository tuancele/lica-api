## Giỏ hàng & Thanh toán

### Cart Service
- File: `app/Services/Cart/CartService.php`.
- Khi `getCart()`: luôn gọi `PriceEngineService::calculatePriceWithQuantity()` cho từng item → trả về `total_price`, `price_breakdown`, `warning`, `flash_sale_remaining`, `total_physical_stock`, `is_available`, `stock_error`.
- Khi update số lượng: gọi WarehouseService check `S_phy`; nếu `newQty > S_phy` trả 422.
- Lưu thêm vào item: `price_breakdown`, `warning`, `is_available`, `stock_error` để FE render ngay.

### Checkout
- Controller: `app/Themes/Website/Controllers/CartController.php`.
- Trước render checkout: tái tính toàn bộ cart bằng PriceEngine (giống cart).
- `postCheckout()`: tính `backendTotal` qua PriceEngine; so sánh với session và giá FE gửi lên; nếu lệch (>0.01) dùng số backend và log cảnh báo.

### Frontend đồng bộ
- Cart page: hiển thị breakdown + warning ngay khi load (từ backend data), update total bằng `updateTotalOrderPrice()`.
- Checkout page: tương tự, yellow warning box ẩn, vẫn có placeholder cho JS; tổng tiền bên sidebar sử dụng mixed pricing mới.
- Nút checkout bị disable nếu `is_available=false` hoặc quantity > `total_stock`.

### API dùng cho FE
- `POST /api/price/calculate`: dùng khi người dùng đổi quantity (Product Detail, Cart, Checkout). FE hiển thị warning vàng nếu vượt FS, đỏ nếu vượt tồn thực tế.
- Trả về `is_available` + `stock_error` để khóa thao tác.

### Logging
- Log tại Cart & Checkout để đối chiếu tổng tiền (backend vs frontend).
- Flash Sale vượt hạn mức: log `[FlashSale_MixedPrice] ...` kèm FS_Qty, Normal_Qty, Extra_Revenue.
