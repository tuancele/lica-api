# GMC Compliance Check - Đối chiếu quy tắc push GMC

## Quy tắc cần tuân thủ

1. **offerId stable**: offerId không đổi khi sản phẩm tham gia campaign mới
2. **price = giá gốc, salePrice = giá campaign**: Luôn có cả 2 trường
3. **VARIABLE product**: Chỉ push variants, không push parent product
4. **insert() = UPSERT**: Google API insert() tự động update nếu offerId đã tồn tại

---

## Điểm push GMC và kiểm tra tuân thủ

### 1. GoogleMerchantService (Module GoogleMerchant)
**File**: `app/Modules/GoogleMerchant/Services/GoogleMerchantService.php`

**Sử dụng trong**:
- `PushProductToGmcJob`
- `PushVariantToGmcJob`
- `GoogleMerchantController::sync()`

**Kiểm tra**:
- ✅ **offerId**: Dùng `GmcOfferId::forVariant()` (stable, theo SKU hoặc variant_id)
- ✅ **price**: `resolveOriginalPrice()` → luôn là giá gốc
- ✅ **salePrice**: `resolveSalePriceInfo()` → giá campaign (FlashSale > Deal > Marketing Campaign)
- ✅ **VARIABLE rule**: Check `has_variants === 1` → skip nếu không có variant
- ✅ **insert()**: Dùng `$service->products->insert()` (UPSERT)

**Status**: ✅ **TUÂN THỦ**

---

### 2. GmcSyncService + GmcProductMapper (Services/Gmc)
**Files**:
- `app/Services/Gmc/GmcSyncService.php`
- `app/Services/Gmc/GmcProductMapper.php`

**Sử dụng trong**:
- `GmcSyncProducts` Command (`php artisan gmc:sync-products`)
- `ApiAdmin\Controllers\GmcController::sync()`

**Kiểm tra**:
- ✅ **offerId**: Dùng `GmcOfferId::forVariant()` (stable, theo SKU hoặc variant_id)
- ✅ **price**: `$originalPrice` từ variant/product → luôn là giá gốc
- ✅ **salePrice**: `resolveSalePriceInfo()` → giá campaign (FlashSale > Deal > Marketing Campaign)
- ✅ **VARIABLE rule**: Chỉ nhận Variant → không có vấn đề VARIABLE
- ✅ **insert()**: Dùng `$service->products->insert()` (UPSERT)

**Status**: ✅ **TUÂN THỦ**

---

### 3. ProductObserver
**File**: `app/Modules/GoogleMerchant/Observers/ProductObserver.php`

**Trigger**: Product saved event

**Kiểm tra**:
- ✅ **VARIABLE rule**: Check `has_variants === 1` → skip push parent, chỉ dispatch job cho variants
- ✅ **SIMPLE rule**: Chỉ push nếu `has_variants === 0`
- ✅ **Service**: Dùng `PushProductToGmcJob` → gọi `GoogleMerchantService` (đã tuân thủ)

**Status**: ✅ **TUÂN THỦ**

---

### 4. VariantObserver
**File**: `app/Modules/GoogleMerchant/Observers/VariantObserver.php`

**Trigger**: Variant saved/deleted events

**Kiểm tra**:
- ✅ **saved()**: Dispatch `PushVariantToGmcJob` → gọi `GoogleMerchantService` (đã tuân thủ)
- ✅ **deleted()**: Dùng `GmcOfferId::forVariant()` để generate offerId → consistent với upsert
- ✅ **Service**: Dùng `GoogleMerchantService::deleteProduct()` → đúng offerId

**Status**: ✅ **TUÂN THỦ**

---

### 5. MarketingCampaignProductObserver
**File**: `app/Modules/Marketing/Observers/MarketingCampaignProductObserver.php`

**Trigger**: MarketingCampaignProduct created/updated events

**Kiểm tra**:
- ✅ **VARIABLE rule**: Check `has_variants === 1` → push tất cả variants, không push parent
- ✅ **SIMPLE rule**: Push product nếu `has_variants === 0`
- ✅ **Service**: Dùng `PushVariantToGmcJob` / `PushProductToGmcJob` → gọi `GoogleMerchantService` (đã tuân thủ)
- ✅ **Auto-trigger**: Tự động push khi product tham gia campaign → đúng yêu cầu

**Status**: ✅ **TUÂN THỦ**

---

### 6. GoogleMerchantController
**File**: `app/Modules/GoogleMerchant/Controllers/GoogleMerchantController.php`

**Endpoints**:
- `GET /admin/google-merchant` (index)
- `POST /admin/google-merchant/sync`
- `GET /admin/google-merchant/status`
- `POST /admin/google-merchant/batch-status`

**Kiểm tra**:
- ✅ **sync()**: Dispatch `PushVariantToGmcJob` / `PushProductToGmcJob` → gọi `GoogleMerchantService` (đã tuân thủ)
- ✅ **offerId generation**: Dùng `GmcOfferId::forVariant()` cho variant, fallback cho simple product
- ✅ **VARIABLE rule**: Chỉ hiển thị variants trong list, không hiển thị parent

**Status**: ✅ **TUÂN THỦ**

---

### 7. ApiAdmin GmcController
**File**: `app/Modules/ApiAdmin/Controllers/GmcController.php`

**Endpoints**:
- `GET /admin/api/gmc/products/preview`
- `POST /admin/api/gmc/products/sync`

**Kiểm tra**:
- ✅ **preview()**: Dùng `GmcProductMapper::map()` → chỉ preview, không push
- ✅ **sync()**: Dùng `GmcSyncService::syncVariant()` → đã tuân thủ (xem #2)
- ✅ **VARIABLE rule**: Chỉ nhận variant_id → không có vấn đề VARIABLE

**Status**: ✅ **TUÂN THỦ**

---

### 8. GmcSyncProducts Command
**File**: `app/Console/Commands/GmcSyncProducts.php`

**Command**: `php artisan gmc:sync-products --variant_id=1 --dry-run`

**Kiểm tra**:
- ✅ **Service**: Dùng `GmcSyncService::syncVariant()` → đã tuân thủ (xem #2)
- ✅ **VARIABLE rule**: Chỉ nhận variant_id → không có vấn đề VARIABLE
- ✅ **Dry-run**: Hỗ trợ dry-run để test

**Status**: ✅ **TUÂN THỦ**

---

## Tổng kết

| Điểm push | offerId stable | price/salePrice | VARIABLE rule | insert() UPSERT | Status |
|-----------|----------------|-----------------|---------------|-----------------|--------|
| GoogleMerchantService | ✅ | ✅ | ✅ | ✅ | ✅ |
| GmcSyncService | ✅ | ✅ | ✅ | ✅ | ✅ |
| ProductObserver | ✅ | ✅ | ✅ | ✅ | ✅ |
| VariantObserver | ✅ | ✅ | ✅ | ✅ | ✅ |
| MarketingCampaignProductObserver | ✅ | ✅ | ✅ | ✅ | ✅ |
| GoogleMerchantController | ✅ | ✅ | ✅ | ✅ | ✅ |
| ApiAdmin GmcController | ✅ | ✅ | ✅ | ✅ | ✅ |
| GmcSyncProducts Command | ✅ | ✅ | ✅ | ✅ | ✅ |

**Kết luận**: ✅ **TẤT CẢ các điểm push GMC đều tuân thủ quy tắc**

---

## Chi tiết quy tắc

### 1. offerId Generation
- **Service**: `App\Services\Gmc\GmcOfferId::forVariant()`
- **Strategy**: Theo `config('gmc.offer_id_strategy')`:
  - `'variant_id'`: Dùng variant ID
  - `'sku'` (default): Dùng SKU nếu có, fallback variant ID
- **Format**: Stable, không đổi khi campaign thay đổi
- **Áp dụng**: Tất cả các điểm push đều dùng service này

### 2. Price Logic
- **price**: Luôn là giá gốc (original/base price từ variant/product)
- **salePrice**: Giá campaign đang hiệu lực (Priority: FlashSale > Deal > Marketing Campaign)
- **salePriceEffectiveDate**: Thời gian hiệu lực của campaign
- **Áp dụng**: Cả `GoogleMerchantService` và `GmcProductMapper` đều tuân thủ

### 3. VARIABLE Product Rule
- **Rule**: Nếu `has_variants === 1`, chỉ push variants, không push parent product
- **Enforcement**:
  - `ProductObserver`: Skip push parent nếu `has_variants === 1`
  - `GoogleMerchantService`: Skip nếu `has_variants === 1` và không có variant
  - `MarketingCampaignProductObserver`: Push tất cả variants nếu `has_variants === 1`
- **Áp dụng**: Tất cả observers và services đều check

### 4. insert() UPSERT
- **Google API**: `products->insert()` tự động UPSERT nếu offerId đã tồn tại
- **Behavior**: Update toàn bộ thông tin (price, salePrice, description, etc.) khi offerId trùng
- **Áp dụng**: Tất cả các điểm push đều dùng `insert()`, không dùng `update()`

---

## Notes

- **GmcProductMapper** và **GoogleMerchantService** có logic tương tự nhưng độc lập
- Cả 2 đều tuân thủ quy tắc price/salePrice
- Cả 2 đều dùng `GmcOfferId` để generate offerId
- **VariantObserver::deleted()** đã được sửa để dùng `GmcOfferId` thay vì hardcoded format

---

**Last Updated**: 2026-01-23
**Checked By**: AI Assistant

