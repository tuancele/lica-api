## Flash Sale & Deal (Marketing)

### Flash Sale
- Mixed pricing áp dụng như `ECOMMERCE_PRICING_INVENTORY.md`.
- JS: `public/js/flash-sale-mixed-price.js` lắng nghe +/− và input ở Product Detail, Cart, Checkout; gọi `/api/price/calculate`.
- Warnings: 
  - Vượt FS: vàng "Vượt quá số lượng Flash Sale", hiển thị breakdown.
  - Vượt tồn thực: đỏ, tự hạ quantity về `S_phy`, disable checkout/add-to-cart.

### Deal (Sản phẩm chính / mua kèm)
- Controller: `app/Modules/Deal/Controllers/DealController.php`.
- JS chọn sp: `public/js/marketing-product-search.js`, dùng modal tìm kiếm, append vào bảng.
- **Quy tắc chọn sản phẩm**:
  1) Sản phẩm A đã là **sản phẩm chính** trong deal hiện tại → không được thêm làm **sản phẩm phụ** trong cùng deal.
  2) Sản phẩm A là **sản phẩm chính** của deal khác đang active → không được làm **sản phẩm chính** của deal mới (chỉ có thể làm phụ).
  3) Sản phẩm phụ ở deal 1 có thể là sản phẩm chính ở deal 2.
- Session:
  - `ss_product_deal`: lưu sản phẩm chính đã chọn; khi AJAX chọn mới chỉ append rows mới (không mất cũ).
  - `ss_sale_product`: lưu sản phẩm mua kèm; tương tự append.
- View rows:
  - Chính: `app/Modules/Deal/Views/product_rows.blade.php`.
  - Phụ: `app/Modules/Deal/Views/sale_product_rows.blade.php`.
- Validate store/update:
  - Backend check 3 quy tắc trên; trả error message kèm tên sản phẩm/variant.

### Modal tìm kiếm
- Routes: `deal.search_product`, `deal.chose_product`, `deal.chose_product2`.
- Tham số: `type=main|sale`, `deal_id` (khi edit) để filter conflict.
- Hiển thị tồn thực tế `actual_stock`, `available_stock`.
