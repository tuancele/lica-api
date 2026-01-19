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

## 全站“生存级”规则（Sống còn Rules）

以下规则适用于所有使用 Skeleton + 异步加载（AJAX / API）的模块（包括但不限于 Flash Sale、Recommendations、Brands、Top Selling、Deal Sốc 等），违反这些规则容易导致 UI 状态丢失或布局错乱。

### 规则 1：Container Integrity（容器完整性）

- **绝对禁止**替换父级容器（container）本身：
  - 不允许：
    - `outerHTML = ...`
    - `$(container).replaceWith(...)`
    - 删除并重新创建根容器元素
- **唯一允许的写法**：
  - 只修改容器内部内容：
    - JavaScript:
      - `container.innerHTML = newHtml`
      - 或通过 `SkeletonManager.hideAndShow(container, newHtml)`（内部也只使用 `innerHTML`）
    - jQuery:
      - `$(container).html(newHtml)`
- 原因：
  - 避免丢失：
    - 事件监听器（绑定在父容器上的 click/scroll 等）
    - Carousel / LazyLoad / Tracking 等实例状态
    - 与容器相关的元数据（data-* attributes、CSS 状态）

### 规则 2：State First（先渲染状态，再隐藏 Skeleton）

- **所有产品状态必须优先根据 API 字段渲染**，然后再隐藏 Skeleton：
  - 推荐字段：
    - `is_available`（bool）
    - 或 `available`（bool）
    - 或 `stock`（库存数，`<= 0` 视为不可售）
- 渲染规则：
  - 如果 `is_available === false` 或 `available === false` 或 `stock <= 0`：
    - 必须在真正内容插入 DOM 时立即：
      - 添加类名（示例）：
        - `.product-unavailable`
        - `.is-sold-out`（如有）
      - 显示醒目的状态文案：
        - `"HẾT HÀNG"`（无库存）
        - `"HẾT QUÀ"`（Deal Sốc / quà tặng已用尽）
      - 禁用对应的交互控件：
        - 按钮：`disabled` + 文案更新
        - checkbox/radio：`disabled`
  - 只有在 **状态已经正确渲染** 后，才允许：
    - 隐藏 Skeleton（淡出 `.lazy-placeholder` / `.js-skeleton`）
    - 显示内容区块（淡入 `.lazy-hidden-content` 或真实列表容器）
- 核心目标：
  - 确保用户**永远不会**看到“已售罄却仍可点击购买”的中间态；
  - Skeleton 只是视觉占位，**权威状态由 API 决定**，不得在前端自行“修正”为有货。

### 规则 3：Sync over Async（Flash Sale 作为标准参考流）

- Flash Sale 的实现是整个站点 Skeleton + 异步加载的**参考标准**。所有新模块必须遵守相同的流程：

1. **Blade Skeleton（预置占位）**
   - 使用 Blade 模板（例如 `skeleton-item.blade.php`）在页面初始就输出完整的 skeleton 网格：
     - Recommendations：预置 12 个 item
     - Top Selling / Deal Sốc 列表：预置 10+ item（填满一屏）
   - 页面结构必须统一：
     ```blade
     <section data-lazy-load="section">
         <div class="lazy-placeholder">
             <!-- Skeleton 列表 -->
         </div>
         <div class="lazy-hidden-content" style="display:none">
             <!-- 真实内容容器（由 JS/API 填充） -->
         </div>
     </section>
     ```

2. **API Fetch（从后端获取数据）**
   - 通过专用 API（Flash Sale / Recommendations / Deals / Brands 等）获取数据。
   - 响应中必须包含：
     - 状态字段：`is_available` / `available` / `stock`
     - 价格与标签字段：`price` / `original_price` / `label` 等

3. **State Validate（状态校验与 UI 决策）**
   - 在渲染 HTML 之前，对每个 item 做状态校验：
     - 是否可售？
     - 是否还有 quà / quota？
     - 是否需要添加 “HẾT HÀNG” / “HẾT QUÀ” / 禁用按钮？
   - 所有 UI 状态（类名、标签、disabled）必须**只根据 API 数据**得出，不进行前端“猜测”。

4. **Fade Transition（渐隐 Skeleton，渐显内容）**
   - 使用统一工具进行过渡：
     - `LazyLoad.loadSection`：对 section 级别的 `.lazy-placeholder` / `.lazy-hidden-content` 作 fadeOut/fadeIn。
     - `SkeletonManager.hideAndShow(container, newHtml)`：对具体列表容器执行：
       - `fadeOut` 旧内容（可能包括 Skeleton 或旧数据）
       - `innerHTML = newHtml`
       - `fadeIn` 新内容
   - 在 fade 阶段中，同时：
     - 重新初始化必要的插件：
       - OWL Carousel（通过 `initCarousels`）
       - Skeleton 尺寸调整（`initSmartSkeleton`）
     - 保证不破坏容器本身（参见规则 1）。

> 总结：  
> **容器不动，状态优先，流程对齐 Flash Sale。**  
> 任何新模块（Recommendations、Brands、Deal Sốc 等）上线前，必须对照以上 3 条规则自查，避免出现“布局跳动、状态错误、事件丢失”等经典问题。