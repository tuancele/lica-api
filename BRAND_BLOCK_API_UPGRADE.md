# Nâng Cấp Brand Block Trang Chủ Lên API V1 RESTful

## Tóm Tắt Thay Đổi

Đã nâng cấp block "Thương hiệu nổi bật" trên trang chủ từ server-side rendering sang client-side API call sử dụng RESTful API V1.

---

## Thay Đổi Chi Tiết

### 1. Frontend (home.blade.php)

#### Trước (Server-Side Rendering):
```blade
@if(count($brands) > 0)
<section class="brand-shop mt-3" data-lazy-load="section">
    ...
    @foreach($brands->take(14) as $brand)
        <div class="item-brand">
            <a href="{{route('home.brand',['url' => $brand->slug])}}">
                <img src="{{getImage($brand->image)}}" alt="{{$brand->name}}">
            </a>
            <div class="brand-name">
                <a href="{{route('home.brand',['url' => $brand->slug])}}">{{$brand->name}}</a>
            </div>
        </div>
    @endforeach
</section>
@endif
```

#### Sau (API V1 Client-Side):
```blade
<section class="brand-shop mt-3" data-lazy-load="section" id="featured-brands-section">
    ...
    <div class="lazy-hidden-content" style="display: none;">
        <div class="list-brand brand-grid-no-carousel brand-grid-2x7" id="brands-list">
            <!-- 品牌将通过 API V1 动态加载 -->
        </div>
    </div>
</section>
```

**JavaScript Function:**
- Tạo function `loadFeaturedBrands()` tương tự `loadFeaturedCategories()`
- Gọi API: `GET /api/v1/brands/featured?limit=14`
- Tích hợp với lazy loading system hiện có
- Error handling và loading states

### 2. Backend (HomeController.php)

#### Trước:
```php
$data['brands'] = Cache::remember('home_brands_v1', 3600, function () {
    return Brand::select('name', 'slug', 'image')
        ->where('status', '1')
        ->orderBy('sort', 'asc')
        ->get();
});
```

#### Sau:
```php
// Brands are now loaded via API V1 (/api/v1/brands/featured)
// Removed server-side rendering to use RESTful API
// Commented out để giữ backward compatibility nếu cần
```

---

## Tính Năng

### ✅ Đã Triển Khai

1. **API Integration:**
   - Sử dụng endpoint `/api/v1/brands/featured`
   - Limit: 14 brands (giống logic cũ)
   - Format JSON chuẩn RESTful

2. **Lazy Loading Integration:**
   - Tích hợp với lazy loading system hiện có
   - Chỉ load khi section visible
   - Skeleton loading state

3. **Error Handling:**
   - Try-catch với error messages
   - Fallback UI khi load fail
   - Console logging cho debugging

4. **Performance:**
   - Client-side caching (browser cache)
   - API có server-side cache (1 hour)
   - Lazy load chỉ khi cần

### 📋 Code Structure

**Function `loadFeaturedBrands()`:**
- Tương tự `loadFeaturedCategories()`
- MutationObserver để detect lazy load
- AJAX call với timeout 10s
- Render HTML với brand data từ API

**HTML Structure:**
- Container: `#featured-brands-section`
- List: `#brands-list`
- Lazy loading: `data-lazy-load="section"`

---

## API Endpoint

**Endpoint:** `GET /api/v1/brands/featured`

**Query Parameters:**
- `limit` (integer, optional): Số lượng brands, default 14, max 50

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 8,
      "name": "MAPUTI",
      "slug": "maputi",
      "image": "https://cdn.lica.vn/uploads/images/maputi-bb.jpg",
      "status": null,
      "created_at": null,
      "updated_at": null
    }
  ],
  "count": 14
}
```

---

## Lợi Ích

### 1. Separation of Concerns
- ✅ Frontend và Backend tách biệt
- ✅ Dễ dàng cache và optimize riêng
- ✅ Có thể reuse API cho mobile app

### 2. Performance
- ✅ Server-side: Giảm query trong HomeController
- ✅ Client-side: Browser caching
- ✅ Lazy loading: Chỉ load khi cần

### 3. Maintainability
- ✅ Logic brand tập trung ở API endpoint
- ✅ Dễ test và debug
- ✅ Consistent với các blocks khác (categories, products)

### 4. Scalability
- ✅ Có thể dễ dàng thêm filters, sorting
- ✅ Có thể pagination nếu cần
- ✅ Dễ dàng extend cho mobile app

---

## Testing

### Manual Test Steps:

1. **Load trang chủ:**
   ```
   http://lica.test/
   ```

2. **Kiểm tra Network Tab:**
   - Xem request đến `/api/v1/brands/featured`
   - Verify response format
   - Check loading state

3. **Kiểm tra UI:**
   - Brands hiển thị đúng
   - Links hoạt động đúng
   - Images load đúng
   - Lazy loading hoạt động

4. **Kiểm tra Console:**
   - Không có errors
   - Log messages đúng

### Expected Behavior:

- ✅ Brands load khi section visible
- ✅ Skeleton loading hiển thị trước
- ✅ Brands render đúng format
- ✅ Links đến `/thuong-hieu/{slug}` hoạt động
- ✅ Error handling khi API fail

---

## Backward Compatibility

### Giữ Nguyên:
- ✅ Route web `/thuong-hieu/{slug}` vẫn hoạt động
- ✅ HTML structure tương tự (chỉ thay đổi data source)
- ✅ CSS classes không đổi
- ✅ Lazy loading system không đổi

### Đã Thay Đổi:
- ⚠️ Server-side `$brands` variable không còn được sử dụng
- ⚠️ Brands được load từ API thay vì server-side

---

## Files Changed

1. **app/Themes/Website/Views/page/home.blade.php**
   - Thay đổi HTML structure
   - Thêm JavaScript function `loadFeaturedBrands()`

2. **app/Themes/Website/Controllers/HomeController.php**
   - Comment out `$data['brands']` (giữ lại để backward compatibility)

---

## Next Steps (Optional)

1. **Remove commented code** sau khi verify hoạt động tốt
2. **Add loading indicator** nếu cần
3. **Add error retry** mechanism
4. **Monitor API performance** và optimize cache nếu cần

---

**Ngày nâng cấp:** 2025-01-18
**Trạng thái:** ✅ Hoàn thành
**API Endpoint:** `/api/v1/brands/featured`
**Impact:** Medium - Cải thiện separation of concerns, giảm server-side load
