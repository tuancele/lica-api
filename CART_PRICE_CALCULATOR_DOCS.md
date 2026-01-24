# Cart Price Calculator - Tài liệu Module JavaScript

## Tổng quan

Module `cart-price-calculator.js` cung cấp logic tính toán giá tiền toàn diện cho giỏ hàng và thanh toán, sử dụng chung cho cả `/cart` (giỏ hàng) và `/cart/thanh-toan` (checkout).

## Các tính năng chính

1. **Tiered Pricing (Giá lũy tiến)**: Tính giá sản phẩm với hạn mức khuyến mãi
2. **Voucher Ship**: Giảm giá phí vận chuyển
3. **Voucher Sản phẩm**: Giảm giá cho từng dòng sản phẩm
4. **Voucher Đơn hàng**: Giảm giá cho toàn bộ đơn hàng
5. **Tính tổng thanh toán**: Tổng hợp tất cả các thành phần

## Cấu trúc Module

### 1. `calculateItemPrice(quantity, limit, promoPrice, rootPrice)`

Tính giá sản phẩm với Tiered Pricing.

**Công thức:**
- Nếu `Q ≤ L`: `P = Q × P_km`
- Nếu `Q > L`: `P = (L × P_km) + ((Q - L) × P_root)`

**Tham số:**
- `quantity` (number): Số lượng sản phẩm
- `limit` (number): Hạn mức khuyến mãi (L)
- `promoPrice` (number): Giá khuyến mãi (P_km)
- `rootPrice` (number): Giá gốc (P_root)

**Trả về:**
```javascript
{
    totalPrice: number,  // Tổng giá
    breakdown: [        // Chi tiết breakdown
        {
            type: 'promo' | 'normal',
            quantity: number,
            unitPrice: number,
            subtotal: number
        }
    ]
}
```

**Ví dụ:**
```javascript
// Mua 15 sản phẩm, hạn mức 10, giá KM 50.000đ, giá gốc 60.000đ
const result = CartPriceCalculator.calculateItemPrice(15, 10, 50000, 60000);
// result.totalPrice = (10 × 50000) + (5 × 60000) = 800.000đ
// result.breakdown = [
//     { type: 'promo', quantity: 10, unitPrice: 50000, subtotal: 500000 },
//     { type: 'normal', quantity: 5, unitPrice: 60000, subtotal: 300000 }
// ]
```

---

### 2. `calculateShippingVoucher(shippingFee, shippingDiscount)`

Tính phí ship sau khi áp dụng voucher ship.

**Công thức:** `max(0, phí ship - giảm giá ship)`

**Tham số:**
- `shippingFee` (number): Phí ship gốc
- `shippingDiscount` (number): Giảm giá ship

**Trả về:** `number` - Phí ship thực tế (không âm)

**Ví dụ:**
```javascript
const actualShipping = CartPriceCalculator.calculateShippingVoucher(30000, 10000);
// actualShipping = max(0, 30000 - 10000) = 20000
```

---

### 3. `calculateItemVoucher(itemSubtotal, voucher)`

Tính giảm giá voucher sản phẩm (áp dụng vào 1 dòng sản phẩm).

**Tham số:**
- `itemSubtotal` (number): Tổng tiền dòng sản phẩm
- `voucher` (Object): Voucher schema
  ```javascript
  {
      type: 'PERCENT' | 'FIXED',
      value: number,              // 10 (10%) hoặc 10000 (10.000đ)
      maxDiscount?: number,       // Trần tối đa (chỉ cho PERCENT)
      targetProductId?: number    // ID sản phẩm áp dụng
  }
  ```

**Trả về:** `number` - Số tiền giảm giá

**Ví dụ:**
```javascript
// Voucher giảm 10%, tối đa 50.000đ, áp dụng vào sản phẩm 500.000đ
const voucher = { type: 'PERCENT', value: 10, maxDiscount: 50000 };
const discount = CartPriceCalculator.calculateItemVoucher(500000, voucher);
// discount = min(500000 × 10%, 50000) = 50000
```

---

### 4. `calculateOrderVoucher(subtotal, shippingFee, voucher)`

Tính giảm giá voucher đơn hàng (trừ vào tổng sau khi cộng phí ship).

**Công thức:** Áp dụng vào `(subtotal + shippingFee)`, kiểm tra Min Spend

**Tham số:**
- `subtotal` (number): Tổng tiền hàng
- `shippingFee` (number): Phí ship thực tế
- `voucher` (Object): Voucher schema
  ```javascript
  {
      type: 'PERCENT' | 'FIXED',
      value: number,
      maxDiscount?: number,
      minOrder?: number           // Đơn hàng tối thiểu
  }
  ```

**Trả về:**
```javascript
{
    discount: number,     // Số tiền giảm giá
    isValid: boolean,     // true nếu đạt Min Spend
    reason?: string       // Lý do nếu không hợp lệ
}
```

**Ví dụ:**
```javascript
// Voucher giảm 10%, tối đa 50.000đ, Min Spend 200.000đ
// Subtotal: 1.000.000đ, Shipping: 30.000đ
const voucher = { type: 'PERCENT', value: 10, maxDiscount: 50000, minOrder: 200000 };
const result = CartPriceCalculator.calculateOrderVoucher(1000000, 30000, voucher);
// result.discount = min((1030000 × 10%), 50000) = 50000
// result.isValid = true
```

---

### 5. `applyVoucher(vouchers, newVoucher)`

Validate và áp dụng voucher mới.

**Quy tắc:**
- Tối đa 2 voucher: 1 Ship + (1 SP hoặc 1 Đơn)
- Voucher SP và Voucher Đơn loại trừ lẫn nhau
- Voucher Ship có thể dùng song song với 1 loại voucher khác

**Tham số:**
- `vouchers` (Array): Mảng voucher hiện tại
- `newVoucher` (Object): Voucher mới muốn áp dụng
  ```javascript
  {
      code: string,
      scope: 'SHIPPING' | 'ITEM' | 'GLOBAL' | 'ORDER',
      type: 'PERCENT' | 'FIXED',
      value: number,
      // ... các thuộc tính khác
  }
  ```

**Trả về:**
```javascript
{
    success: boolean,
    message: string,
    vouchers: Array
}
```

**Ví dụ:**
```javascript
const vouchers = [];
const newVoucher = {
    code: 'SUMMER2026',
    scope: 'GLOBAL',
    type: 'PERCENT',
    value: 10,
    maxDiscount: 50000,
    minOrder: 200000
};

const result = CartPriceCalculator.applyVoucher(vouchers, newVoucher);
if (result.success) {
    vouchers = result.vouchers;
}
```

---

### 6. `removeVoucher(vouchers, voucherCode)`

Xóa voucher khỏi danh sách.

**Tham số:**
- `vouchers` (Array): Mảng voucher
- `voucherCode` (string): Mã voucher cần xóa

**Trả về:** `Array` - Mảng voucher sau khi xóa

---

### 7. `calculateTotal(params)`

Tính tổng thanh toán cuối cùng.

**Công thức:** `max(0, (Tiền hàng - Voucher) + Phí ship thực tế)`

**Tham số:**
```javascript
{
    items: [
        {
            subtotal: number,        // Tổng tiền dòng
            voucher?: Object        // Voucher SP (nếu có)
        }
    ],
    shippingFee: number,             // Phí ship gốc
    shippingVoucher?: Object,        // Voucher ship (nếu có)
    orderVoucher?: Object           // Voucher đơn hàng (nếu có)
}
```

**Trả về:**
```javascript
{
    subtotal: number,           // Tổng tiền hàng
    itemDiscount: number,        // Tổng giảm giá voucher SP
    shippingFee: number,        // Phí ship thực tế
    shippingDiscount: number,    // Giảm giá voucher ship
    orderDiscount: number,       // Giảm giá voucher đơn hàng
    total: number,               // Tổng thanh toán (không âm)
    orderVoucherValid: boolean   // Voucher đơn hàng có hợp lệ không
}
```

**Ví dụ:**
```javascript
const params = {
    items: [
        { subtotal: 500000, voucher: { type: 'PERCENT', value: 10, maxDiscount: 50000 } },
        { subtotal: 300000 }
    ],
    shippingFee: 30000,
    shippingVoucher: { value: 10000 },
    orderVoucher: { type: 'PERCENT', value: 5, maxDiscount: 20000, minOrder: 500000 }
};

const result = CartPriceCalculator.calculateTotal(params);
// result.subtotal = 800000
// result.itemDiscount = 50000 (10% của 500000, max 50000)
// result.shippingFee = 20000 (30000 - 10000)
// result.orderDiscount = 38500 (5% của (800000 - 50000 + 20000), max 20000) = 20000
// result.total = max(0, (800000 - 50000 - 20000) + 20000) = 750000
```

---

### 8. `formatCurrency(amount)`

Format số tiền thành chuỗi VND.

**Ví dụ:**
```javascript
CartPriceCalculator.formatCurrency(1000000); // "1.000.000đ"
```

---

### 9. `parseCurrency(currencyString)`

Parse chuỗi tiền thành số.

**Ví dụ:**
```javascript
CartPriceCalculator.parseCurrency("1.000.000đ"); // 1000000
```

---

## Kịch bản sử dụng

### Kịch bản 1: Mua quá hạn mức

```javascript
// Khách mua 15 sản phẩm (Hạn mức 10)
const priceResult = CartPriceCalculator.calculateItemPrice(15, 10, 50000, 60000);
// 10 sản phẩm đầu tính giá rẻ (50.000đ), 5 sản phẩm sau tính giá cao (60.000đ)
console.log(priceResult.totalPrice); // 800.000đ
```

### Kịch bản 2: Áp mã chồng chéo

```javascript
let vouchers = [];

// Khách đang dùng mã giảm giá SP
const itemVoucher = { code: 'SP10', scope: 'ITEM', type: 'PERCENT', value: 10 };
let result = CartPriceCalculator.applyVoucher(vouchers, itemVoucher);
vouchers = result.vouchers; // [{ code: 'SP10', ... }]

// Sau đó nhập mã giảm giá Đơn hàng
const orderVoucher = { code: 'ORDER20', scope: 'GLOBAL', type: 'PERCENT', value: 20 };
result = CartPriceCalculator.applyVoucher(vouchers, orderVoucher);
// result.success = false
// result.message = 'Voucher đơn hàng không thể dùng cùng voucher sản phẩm'

// Hệ thống gỡ mã SP, áp dụng mã Đơn hàng mới
vouchers = CartPriceCalculator.removeVoucher(vouchers, 'SP10');
result = CartPriceCalculator.applyVoucher(vouchers, orderVoucher);
vouchers = result.vouchers; // [{ code: 'ORDER20', ... }]
```

### Kịch bản 3: Hủy sản phẩm dẫn đến < Min Spend

```javascript
// Sau khi áp mã Đơn hàng, khách xóa bớt hàng
const params = {
    items: [{ subtotal: 150000 }], // Tổng đơn < Min Spend 200.000đ
    shippingFee: 30000,
    orderVoucher: { type: 'PERCENT', value: 10, minOrder: 200000 }
};

const result = CartPriceCalculator.calculateTotal(params);
// result.orderVoucherValid = false
// result.orderDiscount = 0
// Hệ thống tự động gỡ Voucher Đơn hàng và tính lại giá gốc
```

### Kịch bản 4: Voucher % vượt trần

```javascript
// Mã giảm 10% nhưng tối đa 50.000đ. Đơn 1 triệu (đúng ra giảm 100k)
const voucher = { type: 'PERCENT', value: 10, maxDiscount: 50000 };
const discount = CartPriceCalculator.calculateItemVoucher(1000000, voucher);
// discount = 50000 (Dùng Math.min)
```

### Kịch bản 5: Đổi địa chỉ nhận hàng

```javascript
// Phí ship thay đổi từ 30k lên 50k
let shippingFee = 50000;
const shippingVoucher = { value: 10000 };

// Cập nhật lại shippingFee, giữ nguyên shippingDiscount
const actualShipping = CartPriceCalculator.calculateShippingVoucher(shippingFee, shippingVoucher.value);
// actualShipping = 40000
```

---

## Tích hợp vào Frontend

### 1. Include script trong layout

```html
<script src="/public/js/cart-price-calculator.js"></script>
```

### 2. Sử dụng trong Cart (`/cart`)

```javascript
// Tính giá từng item
items.forEach(item => {
    const priceResult = CartPriceCalculator.calculateItemPrice(
        item.quantity,
        item.promoLimit,
        item.promoPrice,
        item.rootPrice
    );
    item.subtotal = priceResult.totalPrice;
});

// Tính tổng
const totalResult = CartPriceCalculator.calculateTotal({
    items: items,
    shippingFee: shippingFee,
    shippingVoucher: shippingVoucher,
    orderVoucher: orderVoucher
});

// Cập nhật UI
$('.subtotal-price').text(CartPriceCalculator.formatCurrency(totalResult.subtotal));
$('.total-price').text(CartPriceCalculator.formatCurrency(totalResult.total));
```

### 3. Sử dụng trong Checkout (`/cart/thanh-toan`)

```javascript
// Tương tự như Cart, nhưng có thêm validation
function updateCheckoutTotal() {
    const totalResult = CartPriceCalculator.calculateTotal({
        items: checkoutItems,
        shippingFee: currentShippingFee,
        shippingVoucher: currentShippingVoucher,
        orderVoucher: currentOrderVoucher
    });

    // Kiểm tra voucher đơn hàng có hợp lệ không
    if (!totalResult.orderVoucherValid) {
        // Hiển thị cảnh báo và gỡ voucher
        showWarning('Voucher không đạt điều kiện đơn hàng tối thiểu');
        removeOrderVoucher();
    }

    // Cập nhật UI
    updateCheckoutUI(totalResult);
}
```

---

## Lưu ý kỹ thuật

1. **Single Source of Truth**: Module này chỉ tính toán, không lưu trữ state. State nên được quản lý bởi Backend hoặc Data Store riêng.

2. **Validation**: Luôn validate input trước khi gọi hàm (kiểm tra số lượng > 0, giá >= 0, etc.)

3. **Error Handling**: Module trả về giá trị mặc định an toàn (0, [], false) khi input không hợp lệ.

4. **Currency Format**: Sử dụng `formatCurrency()` và `parseCurrency()` để đảm bảo format nhất quán.

5. **Negative Protection**: Tất cả kết quả đều được bảo vệ bằng `Math.max(0, value)` để tránh số âm.

---

## Tương thích

- **Browser**: Chrome, Firefox, Safari, Edge (ES5+)
- **Dependencies**: jQuery (cho một số hàm helper, có thể tách riêng)
- **Framework**: Vanilla JavaScript, không phụ thuộc framework

---

## Changelog

**2026-01-XX:**
- ✅ Tạo module CartPriceCalculator
- ✅ Implement Tiered Pricing
- ✅ Implement Voucher (Ship, SP, Đơn hàng)
- ✅ Implement giới hạn voucher (tối đa 2)
- ✅ Implement tính tổng thanh toán

---

**Last Updated:** 2026-01-XX  
**Author:** AI Assistant  
**Status:** ✅ Implemented

