# TỰ ĐỘNG NHẬN DIỆN KÍCH THƯỚC MÀN HÌNH DI ĐỘNG - FIX SKELETON

## 📱 Tổng quan

Đã tối ưu hệ thống skeleton images để tự động nhận diện kích thước màn hình di động và tự động điều chỉnh, tránh các lỗi về kích thước trên mobile.

## ✅ Các tính năng đã thêm

### 1. Tự động nhận diện thiết bị di động

**Hàm `detectMobileDevice()`** sử dụng 4 phương pháp:
- ✅ Kiểm tra kích thước màn hình (width ≤ 768px)
- ✅ Kiểm tra User-Agent (Android, iOS, etc.)
- ✅ Kiểm tra Touch Events
- ✅ Kiểm tra CSS Media Query

**Thông tin trả về:**
```javascript
{
    isMobile: true/false,
    screenWidth: 375,
    screenHeight: 667,
    isPortrait: true/false,
    isLandscape: true/false,
    deviceType: 'phone' | 'tablet' | 'desktop'
}
```

### 2. Phân loại thiết bị thông minh

- **Phone**: width ≤ 480px
- **Tablet**: width 481px - 768px  
- **Desktop**: width > 768px

### 3. Điều chỉnh tự động theo thiết bị

#### Phone (Điện thoại)
- `skeleton--img-sm`: 40-60px (responsive theo 15% viewport)
- `skeleton--img-md`: 100% width, aspect-ratio 1:1
- `skeleton--img-lg`: 100% width, min-height 200px
- Tất cả đều có `overflow: hidden` để tránh tràn

#### Tablet (Máy tính bảng)
- `skeleton--img-sm`: Giữ 60px
- Các loại khác: 100% width, responsive

#### Desktop
- Giữ nguyên logic cũ
- Điều chỉnh theo aspect ratio của ảnh

### 4. Fix các lỗi phổ biến

#### ✅ Tránh Overflow
```css
.js-skeleton {
    max-width: 100% !important;
    overflow: hidden !important;
    box-sizing: border-box !important;
}
```

#### ✅ Responsive Images
```css
.js-skeleton img.js-skeleton-img {
    max-width: 100% !important;
    height: auto !important;
    object-fit: cover !important;
}
```

#### ✅ Layout Shift Prevention
```css
.js-skeleton {
    contain: layout style paint;
}
```

### 5. Xử lý thay đổi hướng màn hình

- ✅ Tự động detect khi xoay màn hình
- ✅ Tự động điều chỉnh lại kích thước
- ✅ Debounce 250ms để tối ưu performance

## 🔧 Các cải tiến kỹ thuật

### JavaScript

1. **Device Detection Function**
   - Multi-method detection
   - Real-time screen size tracking
   - Orientation change handling

2. **Smart Container Adjustment**
   - Auto aspect ratio calculation
   - Device-specific sizing
   - Overflow prevention

3. **Performance Optimization**
   - Debounced resize handler
   - One-time device detection per batch
   - Efficient DOM queries

### CSS

1. **Responsive Media Queries**
   - Mobile: ≤ 768px
   - Small phones: ≤ 480px
   - Tablet: 481px - 768px

2. **Important Rules**
   - `!important` flags để override inline styles
   - `max-width: 100%` để tránh overflow
   - `overflow: hidden` để tránh tràn

3. **Layout Stability**
   - `contain: layout style paint`
   - `box-sizing: border-box`
   - `object-fit: cover`

## 📋 Các skeleton classes được hỗ trợ

| Class | Mobile Behavior | Desktop Behavior |
|-------|----------------|------------------|
| `skeleton--img-sm` | 40-60px (responsive) | 60px fixed |
| `skeleton--img-md` | 100% width, 1:1 ratio | 212px fixed |
| `skeleton--img-lg` | 100% width, min 200px | 100% width |
| `skeleton--img-banner` | 100% width, 4.4:1 ratio | 100% width, 265px height |
| `skeleton--img-logo` | Auto, max 100% width | Auto size |
| `skeleton--img-square` | 100% width, 1:1 ratio | 100% width, 1:1 ratio |

## 🧪 Testing Checklist

### Mobile Devices
- [ ] iPhone (various sizes)
- [ ] Android phones (various sizes)
- [ ] iPad
- [ ] Android tablets

### Screen Orientations
- [ ] Portrait mode
- [ ] Landscape mode
- [ ] Orientation change

### Screen Sizes
- [ ] Small phones (320px - 375px)
- [ ] Large phones (375px - 480px)
- [ ] Tablets (768px - 1024px)

### Edge Cases
- [ ] Very small screens (< 320px)
- [ ] Very large tablets (> 1024px)
- [ ] Different aspect ratios
- [ ] Images with unusual dimensions

## 🐛 Các lỗi đã fix

1. ✅ **Overflow trên mobile**: Container tràn ra ngoài viewport
2. ✅ **Kích thước sai**: Ảnh không match với container
3. ✅ **Layout shift**: Ảnh load gây layout jump
4. ✅ **Aspect ratio sai**: Container không match với ảnh
5. ✅ **Responsive issues**: Không responsive trên các screen size khác nhau
6. ✅ **Orientation change**: Không tự điều chỉnh khi xoay màn hình

## 📝 Usage

Code tự động chạy khi:
- Page load
- Images load
- Window resize
- Orientation change

Không cần thêm code gì, chỉ cần sử dụng các class skeleton như bình thường:
```html
<div class="skeleton--img-md js-skeleton">
    <img src="..." class="js-skeleton-img" alt="...">
</div>
```

## 🚀 Performance

- Debounced resize: 250ms
- Batch processing: Xử lý tất cả images cùng lúc
- One-time device detection: Chỉ detect một lần
- CSS containment: Tối ưu rendering

## 📱 Browser Support

- ✅ Chrome/Edge (latest)
- ✅ Safari (iOS/macOS)
- ✅ Firefox
- ✅ Samsung Internet
- ✅ Opera

## 🔍 Debug

Để debug, mở console và xem:
```javascript
// Xem device info
detectMobileDevice()

// Xem tất cả skeleton containers
$('.js-skeleton')

// Xem skeleton images
$('.js-skeleton-img')
```
