# Cart API V1 Test Guide

## Test Scripts

### 1. Cart API V1 Test
**File:** `test_cart_api_v1.php`

**Usage:**
```bash
php test_cart_api_v1.php
```

**Tests:**
1. ✅ GET /api/v1/cart (Empty cart)
2. ✅ POST /api/v1/cart/items (Add single item)
3. ✅ GET /api/v1/cart (Cart with items)
4. ✅ POST /api/v1/cart/items (Add combo)
5. ✅ PUT /api/v1/cart/items/{id} (Update quantity)
6. ✅ POST /api/v1/cart/coupon/apply (Apply coupon)
7. ✅ GET /api/v1/cart (Cart with coupon)
8. ✅ POST /api/v1/cart/shipping-fee (Calculate shipping fee)
9. ✅ DELETE /api/v1/cart/coupon (Remove coupon)
10. ⚠️ POST /api/v1/cart/checkout (Checkout - commented out)
11. ✅ DELETE /api/v1/cart/items/{id} (Remove item)
12. ✅ GET /api/v1/cart (Final cart state)
13. ✅ Error: Invalid variant_id
14. ✅ Error: Invalid qty
15. ✅ Error: Invalid coupon
16. ✅ Error: Missing required fields

### 2. Order Admin API Test
**File:** `test_order_admin_api.php`

**Usage:**
```bash
php test_order_admin_api.php
```

**Note:** Requires authentication token. Update `$authToken` variable in the script.

**Tests:**
1. ✅ GET /admin/api/orders (List orders)
2. ✅ GET /admin/api/orders?status=0 (Filter by status)
3. ✅ GET /admin/api/orders?keyword=123 (Search by keyword)
4. ✅ GET /admin/api/orders?date_from=...&date_to=... (Filter by date)
5. ✅ GET /admin/api/orders?page=1&limit=5 (Pagination)
6. ✅ GET /admin/api/orders/{id} (Get order detail)
7. ⚠️ PUT /admin/api/orders/{id}/status (Update status - commented out)
8. ✅ Error: Invalid order ID
9. ✅ Error: Invalid status
10. ✅ Error: Without authentication

## Manual Testing with cURL

### Cart API V1

#### 1. Get Cart
```bash
curl -X GET "http://lica.test/api/v1/cart" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

#### 2. Add Item to Cart
```bash
curl -X POST "http://lica.test/api/v1/cart/items" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "variant_id": 1,
    "qty": 2,
    "is_deal": false
  }'
```

#### 3. Add Combo to Cart
```bash
curl -X POST "http://lica.test/api/v1/cart/items" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "combo": [
      {
        "variant_id": 1,
        "qty": 2,
        "is_deal": false
      },
      {
        "variant_id": 2,
        "qty": 1,
        "is_deal": true
      }
    ]
  }'
```

#### 4. Update Item Quantity
```bash
curl -X PUT "http://lica.test/api/v1/cart/items/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "qty": 3
  }'
```

#### 5. Remove Item
```bash
curl -X DELETE "http://lica.test/api/v1/cart/items/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

#### 6. Apply Coupon
```bash
curl -X POST "http://lica.test/api/v1/cart/coupon/apply" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "code": "SALE10"
  }'
```

#### 7. Remove Coupon
```bash
curl -X DELETE "http://lica.test/api/v1/cart/coupon" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

#### 8. Calculate Shipping Fee
```bash
curl -X POST "http://lica.test/api/v1/cart/shipping-fee" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "province_id": 1,
    "district_id": 1,
    "ward_id": 1,
    "address": "123 Đường ABC"
  }'
```

#### 9. Checkout
```bash
curl -X POST "http://lica.test/api/v1/cart/checkout" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "full_name": "Nguyễn Văn A",
    "phone": "0123456789",
    "email": "test@example.com",
    "address": "123 Đường ABC",
    "province_id": 1,
    "district_id": 1,
    "ward_id": 1,
    "remark": "Test order",
    "shipping_fee": 30000
  }'
```

### Order Admin API

#### 1. List Orders
```bash
curl -X GET "http://lica.test/admin/api/orders" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 2. List Orders with Filters
```bash
curl -X GET "http://lica.test/admin/api/orders?status=0&keyword=123&page=1&limit=10" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 3. Get Order Detail
```bash
curl -X GET "http://lica.test/admin/api/orders/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 4. Update Order Status
```bash
curl -X PUT "http://lica.test/admin/api/orders/1/status" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "status": "1"
  }'
```

## Testing Checklist

### Cart API V1
- [ ] GET /api/v1/cart - Empty cart returns empty items array
- [ ] POST /api/v1/cart/items - Add single item successfully
- [ ] POST /api/v1/cart/items - Add combo successfully
- [ ] PUT /api/v1/cart/items/{id} - Update quantity successfully
- [ ] DELETE /api/v1/cart/items/{id} - Remove item successfully
- [ ] POST /api/v1/cart/coupon/apply - Apply valid coupon
- [ ] POST /api/v1/cart/coupon/apply - Reject invalid coupon
- [ ] DELETE /api/v1/cart/coupon - Remove coupon successfully
- [ ] POST /api/v1/cart/shipping-fee - Calculate shipping fee
- [ ] POST /api/v1/cart/shipping-fee - Return 0 for free ship
- [ ] POST /api/v1/cart/checkout - Create order successfully
- [ ] POST /api/v1/cart/checkout - Reject empty cart
- [ ] Error handling - Invalid variant_id returns 400
- [ ] Error handling - Invalid qty returns 400
- [ ] Error handling - Missing required fields returns 400

### Order Admin API
- [ ] GET /admin/api/orders - Returns paginated list
- [ ] GET /admin/api/orders?status=0 - Filters by status
- [ ] GET /admin/api/orders?keyword=123 - Searches by keyword
- [ ] GET /admin/api/orders?date_from=...&date_to=... - Filters by date
- [ ] GET /admin/api/orders/{id} - Returns order detail
- [ ] PUT /admin/api/orders/{id}/status - Updates status
- [ ] Error handling - Invalid order ID returns 404
- [ ] Error handling - Invalid status returns 400
- [ ] Error handling - Without auth returns 401

## Expected Responses

### Success Response Format
```json
{
  "success": true,
  "data": { ... },
  "message": "..." // Optional
}
```

### Error Response Format
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }, // Optional validation errors
  "error": "..." // Only in debug mode
}
```

## Notes

1. **Session-based Cart**: Cart is stored in session, so you need to maintain session cookies when testing
2. **Authentication**: Order Admin API requires authentication token
3. **Test Data**: Update `$testVariantId`, `$testProductId`, `$testCouponCode` in test scripts
4. **Checkout**: Commented out in test script to avoid creating real orders during testing
5. **GHTK**: Shipping fee calculation requires GHTK configuration and Pick address setup

---

**Ngày tạo:** 2025-01-18  
**Trạng thái:** ✅ Test scripts ready
