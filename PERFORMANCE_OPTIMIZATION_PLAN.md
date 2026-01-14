# Lica网站性能优化方案

## 📊 当前性能问题分析

基于对 https://lica.test/ 的分析，发现以下主要性能问题：

### 1. 资源加载问题

#### CSS文件（4个）
- `/public/website/font-awesome/css/font-awesome.min.css` - 未优化
- `/public/website/css/bootstrap.min.css` - 未优化
- `/public/website/owl-carousel/owl.carousel-2.0.0.css` - 在多个页面重复加载
- `/public/website/css/style.css` - 有preload但未完全优化

#### JavaScript文件（5+个）
- `/public/website/js/jquery.min.js` - **阻塞渲染**，应使用defer或async
- `/public/website/js/bootstrap.bundle.min.js` - 已有defer，良好
- `/public/website/owl-carousel/owl.carousel-2.0.0.min.js` - 在多个页面重复加载
- `/public/js/jquery.validate.min.js` - 未优化
- Facebook SDK - 异步加载，良好

#### 图片资源（100+个）
- 大量产品图片从 `cdn.lica.vn` 加载
- **未使用懒加载（lazy loading）**
- 图片文件名过长，影响URL解析
- 未使用WebP格式优化
- 未使用响应式图片（srcset）

#### 字体文件（4个）
- `SVN-Mont-Regular.ttf`
- `SVN-Mont-Bold.ttf`
- `SVN-Mont-SemiBold.ttf`
- `fontawesome-webfont.woff2`
- **未使用font-display: swap**

### 2. 网络请求问题
- 总请求数：**150+个请求**
- 未使用HTTP/2 Server Push
- 未使用资源合并（concatenation）
- 未使用CDN加速静态资源

---

## 🚀 优化方案

### 方案1：CSS优化（高优先级）

#### 1.1 合并和压缩CSS
```php
// 在 layout.blade.php 中合并CSS文件
<link rel="stylesheet" href="/public/website/css/combined.min.css">
```

**实施步骤：**
1. 创建CSS合并脚本
2. 合并 font-awesome, bootstrap, owl.carousel, style.css
3. 压缩合并后的CSS
4. 添加版本号用于缓存控制

#### 1.2 使用preload和prefetch
```html
<!-- 关键CSS使用preload -->
<link rel="preload" href="/public/website/css/critical.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="/public/website/css/critical.css"></noscript>

<!-- 非关键CSS使用prefetch -->
<link rel="prefetch" href="/public/website/css/non-critical.css">
```

#### 1.3 内联关键CSS
将首屏渲染所需的关键CSS内联到HTML中，减少HTTP请求。

---

### 方案2：JavaScript优化（高优先级）

#### 2.1 异步加载非关键JS
```html
<!-- 修改 layout.blade.php -->
<script src="/public/website/js/jquery.min.js" defer></script>
<script src="/public/website/js/bootstrap.bundle.min.js" defer></script>
```

#### 2.2 合并JavaScript文件
```html
<!-- 创建合并的JS文件 -->
<script src="/public/website/js/combined.min.js" defer></script>
```

#### 2.3 按需加载
- Owl Carousel只在需要的页面加载
- 使用动态import加载非关键功能

---

### 方案3：图片优化（高优先级）

#### 3.1 实现懒加载
```html
<!-- 在图片标签中添加loading="lazy" -->
<img src="{{$image}}" loading="lazy" alt="{{$alt}}">
```

#### 3.2 使用WebP格式
```php
// 在Function.php中添加WebP支持
function getWebPImage($image) {
    $webp = str_replace(['.jpg', '.png'], '.webp', $image);
    if (file_exists(public_path($webp))) {
        return $webp;
    }
    return $image;
}
```

#### 3.3 响应式图片
```html
<img srcset="
    {{$image}}?w=400 400w,
    {{$image}}?w=800 800w,
    {{$image}}?w=1200 1200w
" sizes="(max-width: 768px) 100vw, 50vw" 
src="{{$image}}" alt="{{$alt}}">
```

#### 3.4 图片压缩
- 使用工具压缩所有图片（TinyPNG, ImageOptim）
- 产品图片建议压缩到80%质量

---

### 方案4：字体优化（中优先级）

#### 4.1 使用font-display
```css
@font-face {
    font-family: 'SVN-Mont';
    src: url('/public/website/fonts/SVN-Mont-Regular.ttf');
    font-display: swap; /* 添加此行 */
}
```

#### 4.2 字体子集化
只加载需要的字符集，减少字体文件大小。

#### 4.3 使用系统字体作为后备
```css
font-family: 'SVN-Mont', -apple-system, BlinkMacSystemFont, sans-serif;
```

---

### 方案5：资源合并和压缩（中优先级）

#### 5.1 启用Gzip/Brotli压缩
在服务器配置中启用压缩：
```apache
# .htaccess
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

#### 5.2 资源版本控制
```html
<link rel="stylesheet" href="/public/website/css/style.css?v={{config('app.version')}}">
```

---

### 方案6：CDN和缓存优化（中优先级）

#### 6.1 静态资源CDN
- 将CSS、JS、字体文件放到CDN
- 使用多个CDN域名实现并行下载

#### 6.2 浏览器缓存
```php
// 在.htaccess中设置缓存头
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

---

### 方案7：代码优化（低优先级）

#### 7.1 减少DOM操作
- 缓存jQuery选择器
- 批量更新DOM

#### 7.2 优化数据库查询
- 使用Eager Loading减少N+1查询
- 添加数据库索引

---

## 📈 预期效果

实施以上优化后，预期可以达到：

| 指标 | 优化前 | 优化后 | 改善 |
|------|--------|--------|------|
| 页面加载时间 | ~5-8秒 | ~2-3秒 | **60%+** |
| 首屏渲染时间 | ~3-4秒 | ~1-1.5秒 | **65%+** |
| HTTP请求数 | 150+ | 50-70 | **50%+** |
| 页面大小 | ~5-8MB | ~2-3MB | **60%+** |
| Lighthouse分数 | 40-50 | 80-90 | **100%+** |

---

## 🛠️ 实施优先级

### 第一阶段（立即实施 - 1-2天）
1. ✅ JavaScript添加defer/async
2. ✅ 图片懒加载
3. ✅ 字体添加font-display: swap
4. ✅ 启用Gzip压缩

### 第二阶段（短期 - 3-5天）
1. ✅ CSS/JS合并和压缩
2. ✅ 图片WebP转换
3. ✅ 关键CSS内联
4. ✅ 浏览器缓存配置

### 第三阶段（中期 - 1-2周）
1. ✅ 响应式图片
2. ✅ CDN配置
3. ✅ 资源版本控制
4. ✅ 代码优化

---

## 📝 实施检查清单

- [ ] 修改layout.blade.php添加defer/async
- [ ] 实现图片懒加载
- [ ] 添加font-display: swap
- [ ] 配置.htaccess启用压缩
- [ ] 创建CSS合并脚本
- [ ] 创建JS合并脚本
- [ ] 转换图片为WebP格式
- [ ] 配置浏览器缓存
- [ ] 测试所有页面功能
- [ ] 性能测试和验证

---

## 🔍 性能测试工具

1. **Google PageSpeed Insights** - https://pagespeed.web.dev/
2. **GTmetrix** - https://gtmetrix.com/
3. **WebPageTest** - https://www.webpagetest.org/
4. **Chrome DevTools** - Network和Performance面板
5. **Lighthouse** - Chrome内置工具

---

## 📚 参考资料

- [Web.dev Performance](https://web.dev/performance/)
- [Google PageSpeed Insights](https://developers.google.com/speed/pagespeed/insights/)
- [MDN Web Performance](https://developer.mozilla.org/en-US/docs/Web/Performance)
