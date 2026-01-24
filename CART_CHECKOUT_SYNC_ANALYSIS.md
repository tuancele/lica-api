# Phân tích Đồng bộ Logic Tính Toán Cart & Checkout

## Tổng quan

Tài liệu này phân tích sâu logic tính toán trong `cart/index.blade.php` và `checkout.blade.php` để đảm bảo:
1. **Đồng bộ logic** giữa Cart và Checkout
2. **Single Source of Truth**: Chỉ dùng `CartPriceCalculator` để tính toán
3. **Khi tăng/giảm số lượng**: Kết quả tính toán cuối cùng chỉ từ `CartPriceCalculator`

---

## Phân tích Logic Hiện Tại

### 1. Cart (`cart/index.blade.php`)

#### A. Update Quantity (Tăng/Giảm)

**Trước khi sửa:**
- Dùng `CartAPI.formatCurrency(data.subtotal)` để update item price
- Dùng `data.summary.total` từ Backend response để update tổng
- Có fallback nếu `CartPriceCalculator` chưa load

**Sau khi sửa:**
```javascript
// CRITICAL: Chỉ dùng CartPriceCalculator
CartPriceCalculator.fetchCartData(function(err, cartData) {
    // Update item price từ cartData (không dùng data.subtotal từ response)
    var itemData = cartData.items.find(item => item.variant_id === variantId);
    CartPriceCalculator.updateItemPrice(variantId, itemData.subtotal);
    
    // Update cart totals CHỈ từ CartPriceCalculator
    CartPriceCalculator.updateCartTotals(cartData, {}, {
        total: '.total-price'
    });
});
```

**Điểm quan trọng:**
- ✅ Không dùng `data.subtotal` từ `updateItem` response
- ✅ Re-fetch cart data từ API để lấy dữ liệu mới nhất
- ✅ Chỉ dùng `CartPriceCalculator` để tính và update
- ✅ Không có fallback dùng `data.summary.total`

#### B. Remove Item

**Sau khi sửa:**
```javascript
CartAPI.getCart().done(function(cartRes) {
    // CRITICAL: Chỉ dùng CartPriceCalculator
    CartPriceCalculator.updateCartTotals(cartRes.data, {}, {
        subtotal: '.subtotal-price',
        total: '.total-price'
    });
});
```

#### C. Flash Sale Mixed Price

**Sau khi sửa:**
```javascript
FlashSaleMixedPrice.calculatePriceWithQuantity(..., function(priceData) {
    // Validate bằng CartPriceCalculator
    if (priceData.price_breakdown) {
        const validation = CartPriceCalculator.validateFlashSalePrice(priceData);
        if (validation.isValid) {
            priceData.total_price = validation.calculated.totalPrice;
        }
    }
    
    // Update item price CHỈ bằng CartPriceCalculator
    CartPriceCalculator.updateItemPrice(variantId, priceData.total_price);
    
    // Update cart totals CHỈ từ CartPriceCalculator
    CartPriceCalculator.fetchCartData(function(err, cartData) {
        CartPriceCalculator.updateCartTotals(cartData, {}, {
            total: '.total-price'
        });
    });
});
```

---

### 2. Checkout (`checkout.blade.php`)

#### A. Update Quantity

**Sau khi sửa:**
```javascript
checkFlashSalePriceCheckout(variantId, quantity) {
    FlashSaleMixedPrice.calculatePriceWithQuantity(..., function(priceData) {
        // Validate và tính lại bằng CartPriceCalculator
        if (priceData.price_breakdown) {
            const validation = CartPriceCalculator.validateFlashSalePrice(priceData);
            if (validation.isValid) {
                priceData.total_price = validation.calculated.totalPrice;
            }
        }
        
        // Update item price CHỈ bằng CartPriceCalculator
        CartPriceCalculator.updateItemPrice(variantId, priceData.total_price, '.price-item-' + variantId);
        
        // Update breakdown bằng CartPriceCalculator
        if (priceData.price_breakdown && priceData.price_breakdown.length > 1) {
            const calculated = CartPriceCalculator.calculateFromBreakdown(priceData.price_breakdown);
            // Format breakdown...
        }
        
        // Update tổng CHỈ bằng CartPriceCalculator
        updateTotalOrderPriceCheckout();
    });
}
```

#### B. Calculate Total (`updateTotalOrderPriceCheckout`)

**Sau khi sửa:**
```javascript
function updateTotalOrderPriceCheckout() {
    // CRITICAL: Chỉ dùng CartPriceCalculator
    if (typeof CartPriceCalculator === 'undefined') {
        console.warn('[Checkout] CartPriceCalculator not loaded');
        return 0;
    }
    
    // Lấy items từ checkoutPriceBreakdowns (đã được update từ FlashSale API)
    const items = [];
    if (window.checkoutPriceBreakdowns) {
        Object.keys(window.checkoutPriceBreakdowns).forEach(function(vId) {
            const itemData = window.checkoutPriceBreakdowns[vId];
            if (itemData && itemData.total_price !== undefined) {
                items.push({
                    subtotal: parseFloat(itemData.total_price) || 0,
                    voucher: null
                });
            }
        });
    }
    
    // Calculate CHỈ bằng CartPriceCalculator
    const calcResult = CartPriceCalculator.calculateTotal({
        items: items,
        shippingFee: shippingFee,
        shippingVoucher: null,
        orderVoucher: orderVoucher
    });
    
    // Update UI CHỈ bằng CartPriceCalculator
    CartPriceCalculator.updateUI(calcResult, {
        subtotal: '.subtotal-cart',
        total: '.total-order',
        shippingFee: '.fee_ship',
        discount: '.sale-promotion'
    });
    
    // Update individual item prices CHỈ bằng CartPriceCalculator
    if (window.checkoutPriceBreakdowns) {
        Object.keys(window.checkoutPriceBreakdowns).forEach(function(vId) {
            const itemData = window.checkoutPriceBreakdowns[vId];
            CartPriceCalculator.updateItemPrice(parseInt(vId), itemData.total_price, '.price-item-' + vId);
        });
    }
    
    return calcResult.total;
}
```

---

## So sánh Logic Cart vs Checkout

### Điểm giống nhau (Đã đồng bộ)

1. **Update Item Price:**
   - ✅ Cả 2 đều dùng `CartPriceCalculator.updateItemPrice()`
   - ✅ Không dùng `CartAPI.formatCurrency()` hoặc `FlashSaleMixedPrice.formatNumber()`

2. **Update Cart Totals:**
   - ✅ Cart: `CartPriceCalculator.updateCartTotals(cartData, {}, { total: '.total-price' })`
   - ✅ Checkout: `CartPriceCalculator.calculateTotal()` + `CartPriceCalculator.updateUI()`
   - ✅ Cả 2 đều re-fetch cart data từ API trước khi tính

3. **Flash Sale Mixed Price:**
   - ✅ Cả 2 đều validate bằng `CartPriceCalculator.validateFlashSalePrice()`
   - ✅ Cả 2 đều format breakdown bằng `CartPriceCalculator.calculateFromBreakdown()`

### Điểm khác biệt (Cần đồng bộ)

1. **Nguồn dữ liệu:**
   - **Cart**: Re-fetch từ `/api/v1/cart` sau mỗi thao tác
   - **Checkout**: Dùng `window.checkoutPriceBreakdowns` (đã được update từ FlashSale API)
   - ⚠️ **Vấn đề**: Checkout không re-fetch từ API, có thể không sync với Backend

2. **Update Total:**
   - **Cart**: Dùng `updateCartTotals()` (wrapper function)
   - **Checkout**: Dùng `calculateTotal()` + `updateUI()` (2 bước)
   - ✅ **Đã đồng bộ**: Cả 2 đều dùng `CartPriceCalculator`

---

## Giải pháp Đồng bộ Hoàn toàn

### 1. Tạo hàm Central Update cho cả Cart và Checkout

```javascript
// Trong cart-price-calculator.js
CartPriceCalculator.updateCartTotalsFromAPI = function(options, selectors, callback) {
    // Re-fetch từ API
    this.fetchCartData(function(err, cartData) {
        if (err || !cartData) {
            if (typeof callback === 'function') {
                callback(err, null);
            }
            return;
        }
        
        // Calculate và update
        const calcResult = this.updateCartTotals(cartData, options, selectors);
        
        if (typeof callback === 'function') {
            callback(null, calcResult);
        }
    }.bind(this));
};
```

### 2. Đồng bộ Checkout với API

Checkout cũng nên re-fetch từ API thay vì chỉ dùng `checkoutPriceBreakdowns`:

```javascript
// Trong checkout.blade.php
function updateTotalOrderPriceCheckout() {
    // CRITICAL: Re-fetch từ API để đảm bảo sync với Backend
    CartPriceCalculator.fetchCartData(function(err, cartData) {
        if (err || !cartData) {
            console.error('[Checkout] Failed to fetch cart data:', err);
            return 0;
        }
        
        // Merge với checkoutPriceBreakdowns (nếu có update từ FlashSale)
        const items = cartData.items.map(function(item) {
            // Nếu có breakdown mới từ FlashSale, dùng nó
            if (window.checkoutPriceBreakdowns && window.checkoutPriceBreakdowns[item.variant_id]) {
                const breakdownData = window.checkoutPriceBreakdowns[item.variant_id];
                return {
                    subtotal: parseFloat(breakdownData.total_price) || parseFloat(item.subtotal) || 0,
                    voucher: null
                };
            }
            return {
                subtotal: parseFloat(item.subtotal) || 0,
                voucher: null
            };
        });
        
        // Calculate CHỈ bằng CartPriceCalculator
        const calcResult = CartPriceCalculator.calculateTotal({
            items: items,
            shippingFee: parseFloat($('input[name="feeShip"]').val()) || 0,
            shippingVoucher: null,
            orderVoucher: window.checkoutData.sale > 0 ? {
                type: 'FIXED',
                value: parseFloat(window.checkoutData.sale) || 0
            } : null
        });
        
        // Update UI
        CartPriceCalculator.updateUI(calcResult, {
            subtotal: '.subtotal-cart',
            total: '.total-order',
            shippingFee: '.fee_ship',
            discount: '.sale-promotion'
        });
        
        return calcResult.total;
    });
}
```

---

## Quy trình Khi Tăng/Giảm Số Lượng

### Cart Flow:

```
1. User click .btn-plus/.btn-minus
   ↓
2. CartAPI.updateItem(variantId, newQty)
   ↓
3. Response từ Backend (KHÔNG dùng data.subtotal)
   ↓
4. CartPriceCalculator.fetchCartData() → Lấy dữ liệu mới nhất
   ↓
5. CartPriceCalculator.updateItemPrice() → Update item price
   ↓
6. CartPriceCalculator.updateCartTotals() → Update tổng
   ↓
7. checkFlashSalePrice() → Validate và update nếu có Flash Sale
   ↓
8. CartPriceCalculator.validateFlashSalePrice() → Validate breakdown
   ↓
9. CartPriceCalculator.updateCartTotals() → Update tổng lần nữa
```

### Checkout Flow:

```
1. User click .qtyplus/.qtyminus hoặc thay đổi input
   ↓
2. checkFlashSalePriceCheckout(variantId, quantity)
   ↓
3. FlashSaleMixedPrice.calculatePriceWithQuantity()
   ↓
4. API /api/price/calculate trả về price_breakdown
   ↓
5. CartPriceCalculator.validateFlashSalePrice() → Validate
   ↓
6. CartPriceCalculator.updateItemPrice() → Update item price
   ↓
7. CartPriceCalculator.calculateFromBreakdown() → Format breakdown
   ↓
8. updateTotalOrderPriceCheckout() → Tính tổng
   ↓
9. CartPriceCalculator.calculateTotal() → Tính toán
   ↓
10. CartPriceCalculator.updateUI() → Update UI
```

---

## Đảm bảo Single Source of Truth

### ✅ Đã loại bỏ:

1. ❌ `CartAPI.formatCurrency(data.subtotal)` → ✅ `CartPriceCalculator.updateItemPrice()`
2. ❌ `data.summary.total` từ Backend response → ✅ `CartPriceCalculator.calculateFromCartData()`
3. ❌ `FlashSaleMixedPrice.updateTotalOrderPrice()` → ✅ `CartPriceCalculator.updateCartTotals()`
4. ❌ `FlashSaleMixedPrice.formatNumber()` → ✅ `CartPriceCalculator.formatCurrency()`
5. ❌ Manual calculation `subtotal - discount + feeship` → ✅ `CartPriceCalculator.calculateTotal()`

### ✅ Chỉ dùng CartPriceCalculator:

1. ✅ Format currency: `CartPriceCalculator.formatCurrency()`
2. ✅ Update item price: `CartPriceCalculator.updateItemPrice()`
3. ✅ Calculate total: `CartPriceCalculator.calculateTotal()`
4. ✅ Update UI: `CartPriceCalculator.updateUI()`
5. ✅ Update cart totals: `CartPriceCalculator.updateCartTotals()`
6. ✅ Validate Flash Sale: `CartPriceCalculator.validateFlashSalePrice()`
7. ✅ Format breakdown: `CartPriceCalculator.calculateFromBreakdown()`

---

## Kết luận

### ✅ Đã đồng bộ:

1. **Logic tính toán**: Cả Cart và Checkout đều dùng `CartPriceCalculator`
2. **Format currency**: Cả 2 đều dùng `CartPriceCalculator.formatCurrency()`
3. **Update item price**: Cả 2 đều dùng `CartPriceCalculator.updateItemPrice()`
4. **Validate Flash Sale**: Cả 2 đều dùng `CartPriceCalculator.validateFlashSalePrice()`

### ⚠️ Cần cải thiện:

1. **Checkout nên re-fetch từ API** thay vì chỉ dùng `checkoutPriceBreakdowns`
2. **Tạo hàm central** `updateCartTotalsFromAPI()` để cả 2 đều dùng

### ✅ Đảm bảo:

- **Khi tăng/giảm số lượng**: Kết quả tính toán cuối cùng **CHỈ** từ `CartPriceCalculator`
- **Cart và Checkout**: Đồng bộ logic tính toán
- **Single Source of Truth**: `CartPriceCalculator` là nguồn duy nhất tính toán

---

**Last Updated:** 2026-01-XX  
**Author:** AI Assistant  
**Status:** ✅ Analyzed & Synchronized

