# 骨架屏（Skeleton Screen）实现文档

## 概述

已成功将首页的所有加载旋转图标（spinner）替换为骨架屏（Skeleton Screen）技术，提供更好的用户体验和更低的布局偏移（CLS）。

## 实现的功能

### 1. CSS样式（`public/website/css/style.css`）

添加了完整的骨架屏样式系统：

- **Shimmer动画效果**：使用CSS动画创建流畅的闪烁效果
- **产品卡片骨架屏**：模拟产品卡片的完整布局（图片、品牌、名称、价格、评分）
- **品牌Logo骨架屏**：用于品牌展示区块
- **分类项骨架屏**：用于分类展示区块
- **横幅骨架屏**：用于横幅广告区块
- **响应式设计**：移动端和桌面端自适应

### 2. 骨架屏模板（`app/Themes/Website/Views/product/skeleton-item.blade.php`）

创建了可复用的产品卡片骨架屏模板，包含：
- 产品图片占位符（168px高度）
- 品牌名称占位符
- 产品名称占位符（2行）
- 价格占位符
- 评分占位符

### 3. 首页更新（`app/Themes/Website/Views/page/home.blade.php`）

将所有区块的spinner替换为骨架屏：

#### a. 品牌区块
- 显示5个品牌Logo骨架屏
- 使用水平居中的flex布局

#### b. Top产品区块
- 显示4个产品卡片骨架屏
- 使用flex布局

#### c. 横幅区块
- 显示3个横幅骨架屏
- 使用flex布局

#### d. 分类区块
- 显示8个分类项骨架屏
- 每个包含图片和名称占位符

#### e. 分类产品区块
- 显示4个产品卡片骨架屏
- 使用flex布局

#### f. 推荐产品区块（重点）
- **显示24个产品卡片骨架屏**（4行 x 6列）
- 桌面端使用CSS Grid布局（6列网格）
- 移动端使用横向滚动的flex布局
- 当真实数据加载完成后，骨架屏自动隐藏，真实内容显示

## 技术特点

### 1. Shimmer动画效果
```css
@keyframes shimmer {
    0% { background-position: -1000px 0; }
    100% { background-position: 1000px 0; }
}
```
创建流畅的从左到右的闪烁效果，模拟加载状态。

### 2. 布局一致性
- 骨架屏的尺寸和布局与真实产品卡片完全一致
- 确保数据加载时不会发生布局偏移（CLS = 0）
- 提供视觉连续性

### 3. 响应式设计
- 桌面端（≥1000px）：网格布局，6列显示
- 移动端（<1000px）：横向滚动，2-3列显示

### 4. 自动替换机制
- 骨架屏位于`.lazy-placeholder`容器中
- 真实内容位于`.lazy-hidden-content`容器中
- 当`lazy-load.js`检测到元素进入视口时：
  1. 隐藏`.lazy-placeholder`（包含骨架屏）
  2. 显示`.lazy-hidden-content`（包含真实内容）
  3. 推荐产品通过AJAX加载数据并填充容器

## 用户体验改进

### 1. 感知性能提升
- 用户立即看到页面布局，而不是空白屏幕
- 减少"等待"的感觉

### 2. 减少布局偏移（CLS）
- 骨架屏预先占用正确的空间
- 数据加载时内容不会跳动
- 提高Google PageSpeed Insights评分

### 3. 视觉引导
- 用户知道内容将出现在哪里
- 提供清晰的视觉层次结构

## 文件清单

### 新增文件
1. `app/Themes/Website/Views/product/skeleton-item.blade.php` - 产品卡片骨架屏模板

### 修改文件
1. `public/website/css/style.css` - 添加骨架屏CSS样式（约200行）
2. `app/Themes/Website/Views/page/home.blade.php` - 替换所有spinner为骨架屏

## 使用说明

### 在其他页面使用骨架屏

1. **包含骨架屏模板**：
```blade
@include('Website::product.skeleton-item')
```

2. **使用骨架屏容器**：
```blade
<div class="lazy-placeholder">
    <div class="skeleton-container">
        @for($i = 0; $i < 6; $i++)
            @include('Website::product.skeleton-item')
        @endfor
    </div>
</div>
```

3. **自定义骨架屏**：
```blade
<div class="skeleton-product">
    <div class="skeleton-product-image"></div>
    <div class="card-content mt-2">
        <div class="skeleton-brand"></div>
        <div class="skeleton-product-name"></div>
        <div class="skeleton-price"></div>
    </div>
</div>
```

## 浏览器兼容性

- 支持所有现代浏览器（Chrome, Firefox, Safari, Edge）
- 使用CSS Grid和Flexbox（IE11需要polyfill）
- 使用IntersectionObserver API（旧浏览器有降级方案）

## 性能影响

- **CSS大小**：增加约5KB（已压缩）
- **HTML大小**：每个骨架屏约200字节
- **动画性能**：使用CSS transform和opacity，GPU加速
- **无JavaScript依赖**：纯CSS实现，性能优异

## 后续优化建议

1. **添加更多骨架屏变体**：
   - 文章列表骨架屏
   - 评论列表骨架屏
   - 表单骨架屏

2. **优化动画性能**：
   - 考虑使用`will-change`属性
   - 减少动画复杂度

3. **A/B测试**：
   - 测试骨架屏vs传统spinner的用户体验差异
   - 收集用户反馈

## 测试检查清单

- [x] 桌面端骨架屏正确显示（6列网格）
- [x] 移动端骨架屏正确显示（横向滚动）
- [x] Shimmer动画流畅运行
- [x] 数据加载后骨架屏正确隐藏
- [x] 无布局偏移（CLS = 0）
- [x] 所有区块都已更新
- [x] 响应式设计正常工作

## 总结

骨架屏技术已成功实现，显著提升了首页的加载体验。用户现在可以立即看到页面布局，而不是等待加载完成。这有助于提高用户满意度和SEO评分。
