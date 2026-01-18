# Deep Dive: Logic Mua Kèm Deal Sốc

## 📊 Cấu Trúc Database

### 1. **Bảng `deals`**
- `id`: ID deal
- `name`: Tên deal
- `start`: Thời gian bắt đầu (timestamp)
- `end`: Thời gian kết thúc (timestamp)
- `status`: Trạng thái (1 = active, 0 = inactive)
- `limited`: Giới hạn số sản phẩm mua kèm (1 = radio, >1 = checkbox)
- `user_id`: Người tạo

### 2. **Bảng `deal_products` (ProductDeal)**
- `id`: ID
- `deal_id`: ID deal
- `product_id`: ID sản phẩm chính (sản phẩm có deal)
- `status`: Trạng thái

### 3. **Bảng `deal_sales` (SaleDeal)**
- `id`: ID
- `deal_id`: ID deal
- `product_id`: ID sản phẩm mua kèm
- `price`: Giá deal (giá đặc biệt khi mua kèm)
- `qty`: Số lượng (có thể không dùng)
- `status`: Trạng thái

## 🔄 Logic Hoạt Động

### Flow:
1. **Admin tạo Deal:**
   - Chọn sản phẩm chính (ProductDeal) - sản phẩm có deal
   - Chọn sản phẩm mua kèm (SaleDeal) - sản phẩm được bán với giá deal
   - Set giá deal cho từng sản phẩm mua kèm
   - Set giới hạn (limited): 1 = chỉ chọn 1, >1 = chọn nhiều

2. **User xem sản phẩm:**
   - Nếu sản phẩm có deal đang active → Hiển thị section "Mua kèm deal sốc"
   - Hiển thị danh sách sản phẩm mua kèm với giá deal
   - User chọn sản phẩm mua kèm (radio nếu limited=1, checkbox nếu limited>1)

3. **User mua:**
   - Click "MUA DEAL SỐC"
   - Gửi combo: `[{id: variant_id_main, qty: 1, is_deal: 0}, {id: variant_id_deal, qty: 1, is_deal: 1}]`
   - Cart xử lý và tính giá theo deal

## 🐛 Vấn Đề Phát Hiện

### 1. **Variant ID không đúng trong API V1**

**Vấn đề:**
- Trong Blade template: `$product_deal->variant($product_deal->id)->id`
- Trong API V1: `Variant::where('product_id', $saleDeal->product_id)->first()`

**Phân tích:**
- Method `variant($id)` trong Product model: `Variant::where('product_id',$id)->first()`
- Logic giống nhau, nhưng có thể có vấn đề:
  - Không sắp xếp → có thể lấy variant không đúng
  - Không kiểm tra variant có tồn tại không
  - Không lấy variant đầu tiên theo thứ tự (position)

**Fix:**
- Sắp xếp variant theo `position` ASC, sau đó `id` ASC
- Đảm bảo lấy variant đầu tiên đúng

### 2. **Original Price có thể không đúng**

**Vấn đề:**
- Trong Blade: `$product_deal->variant($product_deal->id)->price ?? 0`
- Trong API V1: `$dealVariant ? (float) $dealVariant->price : 0`

**Phân tích:**
- Cần lấy giá gốc của variant (price), không phải sale price
- Có thể cần lấy giá từ variant đầu tiên hoặc variant mặc định

### 3. **Deal không hiển thị trong API-loaded content**

**Vấn đề:**
- Deal được render trong JavaScript từ API response
- Cần đảm bảo format đúng và event handlers hoạt động

## ✅ Giải Pháp

### 1. Sửa `getDealInfo()` trong ProductController V1

```php
private function getDealInfo(int $productId): ?array
{
    try {
        $now = strtotime(date('Y-m-d H:i:s'));
        $dealIds = ProductDeal::where('product_id', $productId)
            ->where('status', 1)
            ->pluck('deal_id')
            ->toArray();
        
        if (empty($dealIds)) {
            return null;
        }
        
        $activeDeal = Deal::whereIn('id', $dealIds)
            ->where('status', 1)
            ->where('start', '<=', $now)
            ->where('end', '>=', $now)
            ->first();
        
        if (!$activeDeal) {
            return null;
        }
        
        $saleDealsData = SaleDeal::where([['deal_id', $activeDeal->id], ['status', '1']])->get();
        
        $saleDeals = $saleDealsData->map(function($saleDeal) {
            $dealProduct = Product::find($saleDeal->product_id);
            if (!$dealProduct) {
                return null;
            }
            
            // Get first variant (sorted by position, then id) - same as Product::variant() but with ordering
            $dealVariant = Variant::where('product_id', $saleDeal->product_id)
                ->orderBy('position', 'asc')
                ->orderBy('id', 'asc')
                ->first();
            
            if (!$dealVariant) {
                return null;
            }
            
            return [
                'id' => $saleDeal->id,
                'product_id' => $saleDeal->product_id,
                'product_name' => $dealProduct->name,
                'product_image' => $this->formatImageUrl($dealProduct->image ?? null),
                'variant_id' => $dealVariant->id,
                'price' => (float) $saleDeal->price,
                'original_price' => (float) $dealVariant->price, // Use variant price, not sale price
            ];
        })->filter()->values()->toArray(); // Remove null values and reindex
        
        if (empty($saleDeals)) {
            return null;
        }
        
        return [
            'id' => $activeDeal->id,
            'name' => $activeDeal->name,
            'limited' => (int) $activeDeal->limited,
            'sale_deals' => $saleDeals,
        ];
    } catch (\Exception $e) {
        Log::warning('Get Deal info failed: ' . $e->getMessage(), [
            'product_id' => $productId
        ]);
        return null;
    }
}
```

### 2. Đảm bảo Deal hiển thị đúng trong JavaScript

- Kiểm tra `product.deal` có tồn tại không
- Kiểm tra `product.deal.sale_deals` có dữ liệu không
- Đảm bảo `variant_id` được set đúng trong HTML

### 3. Test Cases

1. **Deal với limited = 1 (radio):**
   - Chỉ chọn được 1 sản phẩm mua kèm
   - Button "MUA DEAL SỐC" chỉ enable khi đã chọn

2. **Deal với limited > 1 (checkbox):**
   - Chọn được nhiều sản phẩm (tối đa `limited`)
   - Button "MUA DEAL SỐC" chỉ enable khi đã chọn ít nhất 1

3. **Deal không có sale_deals:**
   - Không hiển thị section deal
   - Button "Mua ngay" bình thường

4. **Deal hết hạn:**
   - Không hiển thị deal
   - API trả về `deal: null`

## 📝 Checklist Fix

- [ ] Sửa `getDealInfo()` để lấy variant đúng (có sắp xếp)
- [ ] Đảm bảo `original_price` lấy từ variant price
- [ ] Filter null values trong sale_deals
- [ ] Kiểm tra deal hiển thị đúng trong JavaScript
- [ ] Test với deal limited = 1
- [ ] Test với deal limited > 1
- [ ] Test với deal không có sale_deals
- [ ] Test với deal hết hạn
