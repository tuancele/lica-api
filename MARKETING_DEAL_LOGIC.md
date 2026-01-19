# Marketing Deal Logic (Flash Sale-like modal & ràng buộc sản phẩm)

## Chọn sản phẩm (create/edit)
- JS chung: `public/js/marketing-product-search.js` (modal + search + append).
- Route search: `deal.search_product` với query `type=main|sale` và `deal_id` khi edit.
- Khi AJAX chọn, backend trả về **chỉ rows mới** để append vào `tbody`; JS lọc duplicate trước khi append.
- Session:
  - `ss_product_deal`: sản phẩm chính.
  - `ss_sale_product`: sản phẩm phụ.
  - Backend merge session, nhưng AJAX chỉ trả sản phẩm mới.

## Ràng buộc nghiệp vụ
1) Sản phẩm đã là **chính** trong deal hiện tại → không được thêm làm **phụ** trong cùng deal.
2) Sản phẩm đang là **chính** của deal khác đang active → không được làm **chính** deal mới (có thể làm phụ).
3) Sản phẩm phụ ở deal A có thể làm sản phẩm chính ở deal B.

## Render view (admin)
- `loadproduct.blade.php` & `product_rows.blade.php`: danh sách sản phẩm chính (Flash Sale-like).
- `load_product.blade.php` & `sale_product_rows.blade.php`: danh sách sản phẩm phụ.
- Append target: `.updateSale tbody` (chính), `.updateSale2 tbody` (phụ); không replace container.

## Sửa lỗi ghi đè danh sách
- JS: chỉ append rows; không `.html()` container.
- Backend: với AJAX trả rows mới; full reload trả toàn bộ session.
- Xử lý duplicate: JS loại trùng trước khi append.

## Sự cố cần chú ý
- Lỗi “Call to a member function variant() on null” xuất hiện khi product null → kiểm tra tồn tại product/variant trước khi dùng (cần fix khi phát sinh).
