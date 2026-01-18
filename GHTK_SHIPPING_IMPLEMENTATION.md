# GHTK Shipping Fee Implementation

## ✅ Đã Hoàn Thành

### Implementation Details

**File:** `app/Services/Cart/CartService.php`

**Method:** `calculateShippingFee(array $address, ?int $userId = null): float`

### Logic Flow

1. **Check Free Ship**
   ```php
   if (free_ship == 1 && totalPrice >= free_order) {
       return 0;
   }
   ```

2. **Check GHTK Status**
   ```php
   if (ghtk_status != 1) {
       return 0;
   }
   ```

3. **Get Pick Address (Warehouse)**
   ```php
   $pick = Pick::where('status', '1')
       ->orderBy('sort', 'asc')
       ->first();
   ```

4. **Calculate Total Weight**
   ```php
   foreach ($cart->items as $variant) {
       $itemWeight = is_object($item) 
           ? ($item->weight ?? 0) 
           : ($item['weight'] ?? 0);
       $weight += ($itemWeight * ($variant['qty'] ?? 1));
   }
   ```

5. **Get Delivery Location**
   ```php
   $province = Province::find($address['province_id']);
   $district = District::find($address['district_id']);
   $ward = Ward::find($address['ward_id']);
   ```

6. **Call GHTK API**
   ```php
   $info = [
       "pick_province" => $pick->province->name,
       "pick_district" => $pick->district->name,
       "pick_ward" => $pick->ward->name,
       "pick_street" => $pick->street,
       "pick_address" => $pick->address,
       "province" => $province->name,
       "district" => $district->name,
       "ward" => $ward->name,
       "address" => $address['address'],
       "weight" => $weight,
       "value" => $subtotal - $sale,
       "transport" => 'road',
       "deliver_option" => 'none',
       "tags" => [0],
   ];
   
   $response = $client->request('GET', $ghtkUrl . "/services/shipment/fee", [
       'headers' => ['Token' => $ghtkToken],
       'query' => $info,
       'timeout' => 10,
   ]);
   ```

7. **Parse Response**
   ```php
   $result = json_decode($response->getBody()->getContents());
   if ($result && $result->success && isset($result->fee->fee)) {
       return (float)$result->fee->fee;
   }
   ```

### Error Handling

- ✅ Log warnings khi không tìm thấy Pick address
- ✅ Log warnings khi địa chỉ giao hàng không hợp lệ
- ✅ Log errors khi GHTK API call thất bại
- ✅ Return 0 thay vì throw exception (graceful degradation)
- ✅ Timeout protection (10 seconds)

### Configuration

Các config cần thiết (lưu trong bảng `configs`):
- `free_ship`: 0/1 - Bật/tắt free ship
- `free_order`: Số tiền tối thiểu để được free ship
- `ghtk_status`: 0/1 - Bật/tắt GHTK
- `ghtk_url`: URL của GHTK API
- `ghtk_token`: Token xác thực GHTK

### API Endpoint

**POST /api/v1/cart/shipping-fee**

**Request:**
```json
{
  "province_id": 1,
  "district_id": 1,
  "ward_id": 1,
  "address": "123 Đường ABC"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "shipping_fee": 30000,
    "free_ship": false,
    "summary": {
      "subtotal": 500000,
      "discount": 50000,
      "shipping_fee": 30000,
      "total": 480000
    }
  }
}
```

### Dependencies

- **GuzzleHttp\Client**: Đã có sẵn trong Laravel
- **Pick Model**: `App\Modules\Pick\Models\Pick`
- **Location Models**: `Province`, `District`, `Ward`
- **Config Helper**: `getConfig()` function

### Testing Checklist

- [ ] Test với free ship enabled và đơn hàng đủ điều kiện
- [ ] Test với free ship disabled
- [ ] Test với GHTK disabled
- [ ] Test với Pick address không tồn tại
- [ ] Test với địa chỉ giao hàng không hợp lệ
- [ ] Test với GHTK API timeout
- [ ] Test với GHTK API error
- [ ] Test với cart có nhiều items (tính weight đúng)
- [ ] Test với cart trống (should return 0)

### Notes

1. **Free Ship Priority**: Free ship được kiểm tra trước GHTK API call để tiết kiệm request
2. **Weight Calculation**: Tính tổng weight từ tất cả items trong cart (weight * qty)
3. **Error Handling**: Luôn return 0 thay vì throw exception để không làm gián đoạn checkout flow
4. **Logging**: Tất cả errors và warnings đều được log để debug

---

**Ngày hoàn thành:** 2025-01-18  
**Trạng thái:** ✅ Đã implement đầy đủ
