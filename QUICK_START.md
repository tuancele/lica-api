# 推荐系统快速开始

## ✅ 系统已部署完成

所有组件已成功创建并集成！

## 📍 推荐显示位置

### 1. 首页 ✅
- **位置**：在"Top sản phẩm bán chạy"下方
- **文件**：`app/Themes/Website/Views/page/home.blade.php`
- **自动加载**：是

### 2. 产品详情页 ✅
- **位置**：页面底部，在"Các mẫu bạn đã xem"下方
- **文件**：`app/Themes/Website/Views/product/detail.blade.php`
- **自动加载**：是

## 🚀 立即使用

### 在其他位置添加推荐

在任何Blade文件中添加：

```blade
<section class="product_home mt-5">
    <div class="container-lg">
        <h2 class="fs-25 fw-bold text-uppercase text-center">Sản phẩm đề xuất</h2>
        <div class="list-watch mt-3 product-recommendations-home" 
             data-exclude="" 
             data-limit="12">
            <div class="recommendations-loading text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
            </div>
        </div>
    </div>
</section>
```

### 自定义选项

```blade
<!-- 显示8个产品，排除ID 1,2,3 -->
<div class="product-recommendations-home" 
     data-exclude="1,2,3" 
     data-limit="8">
</div>
```

## 📊 API使用

### 获取推荐产品
```javascript
fetch('/api/recommendations?limit=12&exclude=1,2,3')
    .then(response => response.json())
    .then(data => {
        console.log(data.data); // 推荐产品数组
    });
```

### 跟踪用户行为
```javascript
fetch('/api/recommendations/track', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        product_id: 123,
        behavior_type: 'view',
        duration: 30,
        scroll_depth: 75
    })
});
```

## 🔍 测试系统

1. **访问首页**：查看"Top sản phẩm bán chạy"下方是否有"Sản phẩm đề xuất cho bạn"
2. **访问产品页**：查看页面底部是否有推荐产品
3. **检查控制台**：打开浏览器开发者工具，查看是否有错误
4. **测试API**：访问 `/api/recommendations` 查看是否返回数据

## 📝 已创建的文件

- ✅ `database/migrations/2026_01_20_000001_create_user_behaviors_table.php`
- ✅ `app/Modules/Recommendation/Models/UserBehavior.php`
- ✅ `app/Services/Analytics/UserAnalyticsService.php`
- ✅ `app/Services/Recommendation/RecommendationService.php`
- ✅ `app/Http/Controllers/Api/RecommendationController.php`
- ✅ `app/Http/Controllers/Api/AnalyticsController.php`
- ✅ `public/js/product-recommendation.js`
- ✅ `routes/api.php` (已更新)
- ✅ `app/Themes/Website/Views/layout.blade.php` (已更新)
- ✅ `app/Themes/Website/Views/page/home.blade.php` (已更新)
- ✅ `app/Themes/Website/Views/product/detail.blade.php` (已更新)

## ✨ 系统特点

- ✅ 自动跟踪用户行为
- ✅ 智能推荐算法（协同过滤 + 内容过滤）
- ✅ 支持R2媒体URL
- ✅ 响应式设计
- ✅ 性能优化（缓存、批量处理）
- ✅ 为AI分析准备数据

---

**系统已就绪，可以开始使用！** 🎉
