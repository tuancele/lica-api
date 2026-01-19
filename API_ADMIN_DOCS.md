# API Admin Documentation

本文档记录所有 Admin API 端点的详细信息，按照 `.cursorrules` 规范维护。

---

## Product Management API

### 1. GET /admin/api/products

**Mục tiêu:** 获取产品列表，支持分页和过滤

**Tham số đầu vào (Query Params):**
- `page` (integer, optional): 页码，默认 1
- `limit` (integer, optional): 每页数量，默认 10
- `status` (string, optional): 状态过滤 (0=未激活, 1=激活)
- `cat_id` (integer, optional): 分类ID过滤
- `keyword` (string, optional): 关键词搜索（产品名称）
- `feature` (string, optional): 是否特色产品 (0/1)
- `best` (string, optional): 是否最佳产品 (0/1)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "产品名称",
      "slug": "product-slug",
      "image": "https://example.com/image.jpg",
      "status": "1",
      "feature": "0",
      "best": "0",
      "stock": "1",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 100,
    "last_page": 10
  }
}
```

**Trạng thái:** Hoàn thành

---

### 2. GET /admin/api/products/{id}

**Mục tiêu:** 获取单个产品详情（包含关联数据：品牌、产地、分类、变体等）

**Tham số đầu vào:**
- `id` (integer, required): 产品ID（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "产品名称",
    "slug": "product-slug",
    "image": "https://example.com/image.jpg",
    "gallery": ["https://example.com/img1.jpg", "https://example.com/img2.jpg"],
    "content": "产品详细内容",
    "description": "产品描述",
    "status": "1",
    "feature": "0",
    "best": "0",
    "stock": "1",
    "has_variants": 1,
    "option1_name": "规格",
    "brand": {
      "id": 1,
      "name": "品牌名称"
    },
    "origin": {
      "id": 1,
      "name": "产地名称"
    },
    "categories": [1, 2, 3],
    "variants": [
      {
        "id": 1,
        "sku": "SKU-001",
        "price": 100000,
        "sale": 80000,
        "stock": 50,
        "option1_value": "500ml"
      }
    ],
    "seo_title": "SEO标题",
    "seo_description": "SEO描述",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Trạng thái:** Hoàn thành

---

### 3. POST /admin/api/products

**Mục tiêu:** 创建新产品

**Tham số đầu vào (Body - JSON):**
- `name` (string, required): 产品名称，1-250字符
- `slug` (string, required): URL友好标识，唯一，格式：小写字母、数字、连字符
- `content` (string, optional): 产品详细内容
- `description` (string, optional): 产品描述，最大500字符
- `video` (string, optional): 视频URL，最大500字符
- `imageOther` (array, optional): 图片URL数组
- `cat_id` (array, optional): 分类ID数组
- `brand_id` (integer, optional): 品牌ID
- `origin_id` (integer, optional): 产地ID
- `ingredient` (string, optional): 成分（文本，系统会自动链接到成分字典）
- `price` (numeric, optional): 价格（无变体模式）
- `sale` (numeric, optional): 促销价（无变体模式）
- `stock_qty` (integer, optional): 库存数量（无变体模式）
- `weight` (numeric, optional): 重量（无变体模式）
- `sku` (string, optional): SKU（无变体模式），最大100字符，必须唯一
- `has_variants` (integer, optional): 是否有变体 (0/1)，默认0
- `option1_name` (string, optional): 变体选项名称（当has_variants=1时必填），最大50字符
- `variants_json` (string, optional): 变体JSON数据（当has_variants=1时必填）
- `status` (integer, optional): 状态 (0/1)，默认1
- `feature` (integer, optional): 是否特色 (0/1)，默认0
- `best` (integer, optional): 是否最佳 (0/1)，默认0
- `stock` (integer, optional): 是否有库存 (0/1)，默认1
- `seo_title` (string, optional): SEO标题，最大250字符
- `seo_description` (string, optional): SEO描述，最大500字符
- `r2_session_key` (string, optional): R2上传会话密钥

**Logic tự động:**
- Nếu `stock_qty` > 0 (sản phẩm không có variants) hoặc variants có `stock` > 0, hệ thống sẽ tự động tạo phiếu nhập hàng trong kho hàng với:
  - Mã phiếu: `NH-PRODUCT-{product_id}-{timestamp}`
  - Tiêu đề: "Nhập hàng ban đầu cho sản phẩm: {product_name}"
  - Nội dung: "Tự động tạo phiếu nhập hàng khi tạo sản phẩm mới"
  - Giá nhập: Sử dụng giá từ variant (price)
  - Số lượng: Sử dụng stock_qty hoặc stock từ variants
  - VAT Invoice: Để trống

**Phản hồi mẫu (201):**
```json
{
  "success": true,
  "message": "产品创建成功",
  "data": {
    "id": 1,
    "name": "产品名称",
    "slug": "product-slug"
  }
}
```

**Trạng thái:** Hoàn thành

---

### 4. PUT /admin/api/products/{id}

**Mục tiêu:** 更新现有产品

**Tham số đầu vào:**
- `id` (integer, required): 产品ID（URL参数）
- Body参数同 POST /admin/api/products（除slug外，slug允许更新但需保持唯一）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "产品更新成功",
  "data": {
    "id": 1,
    "name": "更新后的产品名称",
    "slug": "updated-product-slug"
  }
}
```

**Trạng thái:** Hoàn thành

---

### 5. DELETE /admin/api/products/{id}

**Mục tiêu:** 删除产品（如果产品已有订单则不允许删除）

**Tham số đầu vào:**
- `id` (integer, required): 产品ID（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "产品删除成功"
}
```

**Phản hồi lỗi (400):**
```json
{
  "success": false,
  "message": "Không thể xóa sản phẩm đã có đơn hàng"
}
```

**Trạng thái:** Hoàn thành

---

### 6. PATCH /admin/api/products/{id}/status

**Mục tiêu:** 更新产品状态（激活/未激活）

**Tham số đầu vào:**
- `id` (integer, required): 产品ID（URL参数）
- `status` (integer, required): 状态值 (0=未激活, 1=激活)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "状态更新成功",
  "data": {
    "id": 1,
    "status": "1"
  }
}
```

**Trạng thái:** Hoàn thành

---

### 7. POST /admin/api/products/bulk-action

**Mục tiêu:** 批量操作产品（批量更新状态、批量删除）

**Tham số đầu vào (Body - JSON):**
- `checklist` (array, required): 产品ID数组
- `action` (integer, required): 操作类型 (0=隐藏, 1=显示, 2=删除)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "批量操作成功",
  "affected_count": 5
}
```

**Trạng thái:** Hoàn thành

---

### 8. PATCH /admin/api/products/sort

**Mục tiêu:** 更新产品排序

**Tham số đầu vào (Body - JSON):**
- `sort` (object, required): 产品ID => 排序值的映射，例如：`{"1": 10, "2": 20}`

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "排序更新成功"
}
```

**Trạng thái:** Hoàn thành

---

## Product Variant Management API

### 9. GET /admin/api/products/{id}/variants

**Mục tiêu:** 获取产品的所有变体列表

**Tham số đầu vào:**
- `id` (integer, required): 产品ID（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sku": "SKU-001",
      "product_id": 1,
      "option1_value": "500ml",
      "image": "https://example.com/variant1.jpg",
      "size_id": 1,
      "color_id": 1,
      "weight": 500,
      "price": 100000,
      "sale": 80000,
      "stock": 50,
      "position": 0,
      "color": {
        "id": 1,
        "name": "红色",
        "color": "#FF0000"
      },
      "size": {
        "id": 1,
        "name": "500ml",
        "unit": "ml"
      }
    }
  ]
}
```

**Trạng thái:** Hoàn thành

---

### 10. GET /admin/api/products/{id}/variants/{code}

**Mục tiêu:** 获取单个变体详情

**Tham số đầu vào:**
- `id` (integer, required): 产品ID（URL参数）
- `code` (integer, required): 变体ID（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "sku": "SKU-001",
    "product_id": 1,
    "option1_value": "500ml",
    "image": "https://example.com/variant1.jpg",
    "size_id": 1,
    "color_id": 1,
    "weight": 500,
    "price": 100000,
    "sale": 80000,
    "stock": 50,
    "position": 0
  }
}
```

**Trạng thái:** Hoàn thành

---

### 11. POST /admin/api/products/{id}/variants

**Mục tiêu:** 为产品创建新变体

**Tham số đầu vào (Body - JSON):**
- `id` (integer, required): 产品ID（URL参数）
- `sku` (string, required): SKU，必须唯一
- `option1_value` (string, optional): 变体选项值
- `image` (string, optional): 变体图片URL
- `size_id` (integer, optional): 尺寸ID
- `color_id` (integer, optional): 颜色ID
- `weight` (numeric, optional): 重量
- `price` (numeric, required): 价格
- `sale` (numeric, optional): 促销价
- `stock` (integer, optional): 库存数量

**Phản hồi mẫu (201):**
```json
{
  "success": true,
  "message": "变体创建成功",
  "data": {
    "id": 1,
    "sku": "SKU-001",
    "product_id": 1
  }
}
```

**Trạng thái:** Hoàn thành

---

### 12. PUT /admin/api/products/{id}/variants/{code}

**Mục tiêu:** 更新变体信息

**Tham số đầu vào:**
- `id` (integer, required): 产品ID（URL参数）
- `code` (integer, required): 变体ID（URL参数）
- Body参数同 POST /admin/api/products/{id}/variants

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "变体更新成功",
  "data": {
    "id": 1,
    "sku": "SKU-001-UPDATED"
  }
}
```

**Trạng thái:** Hoàn thành

---

### 13. DELETE /admin/api/products/{id}/variants/{code}

**Mục tiêu:** 删除变体（如果变体已有订单则不允许删除）

**Tham số đầu vào:**
- `id` (integer, required): 产品ID（URL参数）
- `code` (integer, required): 变体ID（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "变体删除成功"
}
```

**Phản hồi lỗi (400):**
```json
{
  "success": false,
  "message": "Sản phẩm đã có đơn hàng không thể xóa!"
}
```

**Trạng thái:** Hoàn thành

---

## Product Module Structure Analysis

### 当前模块结构

**位置:** `app/Modules/Product/`

**文件结构:**
```
Product/
├── Controllers/
│   └── ProductController.php (管理后台控制器 - 用于Web界面)
├── Models/
│   ├── Product.php (产品模型，使用posts表)
│   └── Variant.php (变体模型，使用variants表)
├── routes.php (管理后台路由配置)
└── Views/ (Blade模板文件)
    ├── index.blade.php
    ├── create.blade.php
    ├── edit.blade.php
    ├── variant.blade.php
    └── variantnew.blade.php
```

### 相关服务层

**位置:** `app/Services/Product/`

- `ProductService.php` - 产品业务逻辑服务
- `ProductServiceInterface.php` - 服务接口

**主要方法:**
- `createProduct(array $data): Product` - 创建产品
- `updateProduct(int $id, array $data): Product` - 更新产品
- `deleteProduct(int $id): bool` - 删除产品
- `getProductWithRelations(int $id): Product` - 获取产品及关联数据
- `getProducts(array $filters = [], int $perPage = 10)` - 获取产品列表（分页）

### 相关资源类

**位置:** `app/Http/Resources/Product/`

- `ProductResource.php` - 产品资源格式化类
- `ProductCollection.php` - 产品集合资源类

### 相关请求验证类

**位置:** `app/Http/Requests/Product/`

- `StoreProductRequest.php` - 创建产品请求验证
- `UpdateProductRequest.php` - 更新产品请求验证

### 数据库表结构

**posts 表 (Product模型):**
- `id`, `name`, `slug`, `image`, `gallery` (JSON), `video`
- `content`, `description`
- `status`, `type` (product/taxonomy), `has_variants`, `option1_name`
- `cat_id` (JSON数组), `brand_id`, `origin_id`
- `feature`, `best`, `stock`, `verified`
- `ingredient`, `seo_title`, `seo_description`, `cbmp`
- `sort`, `view`, `user_id`
- `created_at`, `updated_at`

**variants 表 (Variant模型):**
- `id`, `sku` (唯一), `product_id`
- `option1_value`, `image`
- `size_id`, `color_id`, `weight`
- `price`, `sale`, `stock`
- `position`, `user_id`
- `created_at`, `updated_at`

### 关联关系

- Product `belongsTo` Brand
- Product `belongsTo` Origin
- Product `hasMany` Variants
- Variant `belongsTo` Product
- Variant `belongsTo` Color
- Variant `belongsTo` Size

### 业务逻辑要点

1. **变体管理:**
   - 支持单产品模式（无变体）和多变体模式
   - 多变体模式使用 `variants_json` 格式同步变体
   - SKU必须唯一，系统会自动处理冲突

2. **图片处理:**
   - 支持R2云存储上传
   - 使用 `ImageService` 处理图片库
   - 自动从图片库中选择主图

3. **成分处理:**
   - 自动将文本成分链接到成分字典（IngredientPaulas）
   - 支持已处理HTML格式的成分内容

4. **订单检查:**
   - 删除产品/变体前检查是否已有订单
   - 有订单的产品/变体不允许删除

5. **Slug重定向:**
   - 产品slug变更时自动创建301重定向

---

## 待实现 API 端点

根据 `.cursorrules` 规范，需要在 `App\Modules\ApiAdmin\Controllers` 中创建以下控制器：

1. `ProductController` - 产品管理API控制器
2. 路由注册在 `app/Modules/ApiAdmin/routes.php`，使用前缀 `admin/api`

**实现计划:**
- 复用现有的 `ProductService` 处理业务逻辑
- 使用 `ProductResource` 格式化响应
- 遵循 RESTful 标准
- 统一错误处理和响应格式

---

## Public Product API (用于首页和公开页面)

### 14. GET /api/products/top-selling

**Mục tiêu:** 获取热销产品列表（Top sản phẩm bán chạy）

**Logic tính toán:**
- Tính tổng số lượng đã bán từ tất cả đơn hàng (trừ đơn hàng đã hủy - status = 4)
- Sắp xếp theo tổng số lượng đã bán giảm dần
- Nếu không đủ sản phẩm, bổ sung từ sản phẩm best hoặc sản phẩm mới nhất

**Tham số đầu vào (Query Params):**
- `limit` (integer, optional): 返回数量，默认 10

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "产品名称",
      "slug": "product-slug",
      "image": "https://example.com/image.jpg",
      "brand_id": 1,
      "brand_name": "品牌名称",
      "brand_slug": "brand-slug",
      "price": 100000,
      "sale": 80000,
      "price_info": {
        "price": 80000,
        "original_price": 100000,
        "type": "normal",
        "label": "",
        "discount_percent": 20
      },
      "stock": 1,
      "best": 1,
      "is_new": 0,
      "total_sold": 150,
      "total_sold_month": 25
    }
  ],
  "count": 10
}
```

**Response Fields:**
- `total_sold` (integer): Tổng số lượng đã bán từ tất cả đơn hàng (trừ đơn hàng đã hủy)
- `total_sold_month` (integer): Số lượng đã bán trong tháng hiện tại

**Trạng thái:** Hoàn thành (已更新 - Tính toán dựa trên tất cả đơn hàng)

---

### 15. GET /api/products/by-category/{id}

**Mục tiêu:** 根据分类ID获取产品列表

**Tham số đầu vào:**
- `id` (integer, required): 分类ID（URL参数）
- `limit` (integer, optional): 返回数量，默认 20

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "产品名称",
      "slug": "product-slug",
      "image": "https://example.com/image.jpg",
      "brand_id": 1,
      "price": 100000,
      "sale": 80000,
      "stock": 1,
      "best": 0,
      "is_new": 1
    }
  ],
  "count": 20
}
```

**Trạng thái:** Hoàn thành

---

### 16. GET /api/products/flash-sale

**Mục tiêu:** 获取当前 Flash Sale 产品列表

**Tham số đầu vào:** 无

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "产品名称",
      "slug": "product-slug",
      "image": "https://example.com/image.jpg",
      "brand_id": 1,
      "price": 100000,
      "sale": 80000,
      "price_sale": 70000,
      "stock": 1,
      "best": 1,
      "is_new": 0,
      "flash_sale": {
        "number": 100,
        "buy": 50,
        "remaining": 50
      }
    }
  ],
  "flash_sale": {
    "id": 1,
    "name": "Flash Sale 名称",
    "start": 1704067200,
    "end": 1704153600,
    "end_date": "2024-01-02 00:00:00",
    "end_timestamp": 1704153600,
    "total_products": 25
  },
  "count": 5
}
```

**Phản hồi mẫu (无 Flash Sale):**
```json
{
  "success": true,
  "data": [],
  "flash_sale": null,
  "count": 0
}
```

**Trạng thái:** Hoàn thành

---

### 17. GET /api/products/{id}/price-info

**Mục tiêu:** 获取产品价格信息（包括 Flash Sale、Marketing Campaign 等）

**Tham số đầu vào:**
- `id` (integer, required): 产品ID（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "price": 100000,
    "sale": 80000,
    "price_info": {
      "price": 70000,
      "original_price": 100000,
      "type": "flashsale",
      "label": "Flash Sale"
    }
  }
}
```

**Trạng thái:** Hoàn thành

---

### 18. GET /api/products/{slug}/detail

**Mục tiêu:** 获取产品详情信息（包括基本信息、图片库、变体、品牌、分类、评分、销量、Flash Sale、Deal 等）

**Tham số đầu vào:**
- `slug` (string, required): 产品 slug（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "产品名称",
    "slug": "product-slug",
    "image": "https://cdn.lica.vn/uploads/images/product.jpg",
    "video": null,
    "gallery": [
      "https://cdn.lica.vn/uploads/images/gallery1.jpg",
      "https://cdn.lica.vn/uploads/images/gallery2.jpg"
    ],
    "description": "产品描述",
    "content": "产品详细内容",
    "seo_title": "SEO标题",
    "seo_description": "SEO描述",
    "stock": 100,
    "best": 1,
    "is_new": 0,
    "cbmp": "CBMP123456",
    "option1_name": "Phân loại",
    "has_variants": 1,
    "brand": {
      "id": 1,
      "name": "品牌名称",
      "slug": "brand-slug"
    },
    "origin": {
      "id": 1,
      "name": "产地名称"
    },
    "category": {
      "id": 1,
      "name": "分类名称",
      "slug": "category-slug"
    },
    "first_variant": {
      "id": 1,
      "sku": "SKU123",
      "price": 100000,
      "sale": 80000,
      "stock": 50
    },
    "variants": [
      {
        "id": 1,
        "sku": "SKU123",
        "option1_value": "红色 / 大号",
        "image": "https://cdn.lica.vn/uploads/images/variant1.jpg",
        "price": 100000,
        "sale": 80000,
        "stock": 50,
        "weight": 0.5,
        "size_id": 1,
        "color_id": 1,
        "color": {
          "id": 1,
          "name": "红色"
        },
        "size": {
          "id": 1,
          "name": "大号",
          "unit": "ml"
        },
        "price_info": {
          "final_price": 70000,
          "original_price": 100000,
          "html": "<p>70,000đ</p><del>100,000đ</del><div class=\"tag\"><span>-30%</span></div>"
        },
        "option_label": "红色 / 大号"
      }
    ],
    "variants_count": 1,
    "rating": {
      "average": 4.5,
      "count": 120,
      "sum": 540
    },
    "total_sold": 1500,
    "rates": [
      {
        "id": 1,
        "rate": 5,
        "comment": "很好用",
        "user_name": "用户A",
        "created_at": "2024-01-01T00:00:00.000000Z"
      }
    ],
    "flash_sale": {
      "id": 1,
      "name": "Flash Sale 名称",
      "start": 1704067200,
      "end": 1704153600,
      "end_date": "2024/01/02 00:00:00",
      "price_sale": 60000,
      "number": 100,
      "buy": 50,
      "remaining": 50
    },
    "deal": {
      "id": 1,
      "name": "Deal 名称",
      "limited": 2,
      "sale_deals": [
        {
          "id": 1,
          "product_id": 2,
          "product_name": "搭配产品",
          "product_image": "https://cdn.lica.vn/uploads/images/deal-product.jpg",
          "variant_id": 2,
          "price": 50000,
          "original_price": 80000
        }
      ]
    },
    "related_products": [
      {
        "id": 2,
        "name": "相关产品",
        "slug": "related-product-slug",
        "image": "https://cdn.lica.vn/uploads/images/related.jpg",
        "brand_id": 1,
        "brand_name": "品牌名称",
        "brand_slug": "brand-slug",
        "price": 90000,
        "sale": 70000,
        "stock": 30,
        "best": 0,
        "is_new": 1,
        "deal": null
      }
    ]
  }
}
```

**Phản hồi mẫu (404):**
```json
{
  "success": false,
  "message": "产品不存在"
}
```

**Trạng thái:** Hoàn thành

---

---

## Flash Sale Management API

### 1. GET /admin/api/flash-sales

**Mục tiêu:** 获取 Flash Sale 列表，支持分页和过滤

**Tham số đầu vào (Query Params):**
- `page` (integer, optional): 页码，默认 1
- `limit` (integer, optional): 每页数量，默认 10，最大 100
- `status` (string, optional): 状态过滤 (0=未激活, 1=激活)
- `keyword` (string, optional): 关键词搜索

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Flash Sale Tháng 1",
      "start": "2024-01-15T00:00:00.000000Z",
      "end": "2024-01-20T23:59:59.000000Z",
      "start_timestamp": 1705276800,
      "end_timestamp": 1705708799,
      "status": "1",
      "is_active": true,
      "countdown_seconds": 432000,
      "total_products": 25,
      "created_at": "2024-01-10T00:00:00.000000Z",
      "updated_at": "2024-01-10T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

**Trạng thái:** Hoàn thành

---

### 2. GET /admin/api/flash-sales/{id}

**Mục tiêu:** 获取 Flash Sale 详情（包含产品列表）

**Tham số đầu vào:**
- `id` (integer, required): Flash Sale ID（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "start": "2024-01-15T00:00:00.000000Z",
    "end": "2024-01-20T23:59:59.000000Z",
    "status": "1",
    "is_active": true,
    "countdown_seconds": 432000,
    "products": [
      {
        "id": 1,
        "flashsale_id": 1,
        "product_id": 10,
        "variant_id": 5,
        "price_sale": 150000,
        "number": 100,
        "buy": 45,
        "remaining": 55,
        "is_available": true,
        "original_price": 200000,
        "discount_percent": 25,
        "variant": {
          "id": 5,
          "sku": "SKU-001",
          "option1_value": "500ml",
          "price": 200000
        },
        "product": {
          "id": 10,
          "name": "Sản phẩm",
          "slug": "san-pham",
          "image": "https://..."
        }
      }
    ]
  }
}
```

**Trạng thái:** Hoàn thành

---

### 3. POST /admin/api/flash-sales

**Mục tiêu:** 创建新的 Flash Sale

**Request Body:**
```json
{
  "start": "2024-01-15 00:00:00",
  "end": "2024-01-20 23:59:59",
  "status": "1",
  "products": [
    {
      "product_id": 10,
      "variant_id": 5,
      "price_sale": 150000,
      "number": 100
    },
    {
      "product_id": 10,
      "variant_id": null,
      "price_sale": 140000,
      "number": 50
    }
  ]
}
```

**Validation:**
- `start`: required, date format
- `end`: required, date format, after:start
- `status`: required, in:0,1
- `products`: array, optional
- `products.*.product_id`: required, exists:posts,id
- `products.*.variant_id`: nullable, exists:variants,id (必须属于 product_id)
- `products.*.price_sale`: required, numeric, min:0
- `products.*.number`: required, integer, min:1

**Stock Validation (新增):**
- 所有产品的库存必须 > 0 才能参与 Flash Sale
- 有 variant 的产品：从 warehouse 系统获取实际库存
- 无 variant 的产品：使用 product.stock 字段或默认 variant 的库存
- 如果产品库存为 0，返回 422 错误

**Phản hồi mẫu (201):**
```json
{
  "success": true,
  "message": "Tạo Flash Sale thành công",
  "data": {
    // FlashSaleDetailResource
  }
}
```

**Trạng thái:** Hoàn thành

---

### 4. PUT /admin/api/flash-sales/{id}

**Mục tiêu:** 更新 Flash Sale

**Request Body:** 同 POST，但所有字段都是可选的

**Logic:**
- 更新 flashsales 表
- 删除不在 request 中的 productsales
- 更新/创建新的 productsales

**Stock Validation (新增):**
- 更新产品时，所有产品的库存必须 > 0
- 如果产品库存为 0，返回 422 错误

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Cập nhật Flash Sale thành công",
  "data": {
    // FlashSaleDetailResource
  }
}
```

**Trạng thái:** Hoàn thành

---

### 5. DELETE /admin/api/flash-sales/{id}

**Mục tiêu:** 删除 Flash Sale

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Xóa Flash Sale thành công"
}
```

**Trạng thái:** Hoàn thành

---

### 6. POST /admin/api/flash-sales/{id}/status

**Mục tiêu:** 更新 Flash Sale 状态

**Request Body:**
```json
{
  "status": "1"
}
```

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Cập nhật trạng thái thành công",
  "data": {
    // FlashSaleResource
  }
}
```

**Trạng thái:** Hoàn thành

---

### 7. POST /admin/api/flash-sales/search-products

**Mục tiêu:** 搜索产品以添加到 Flash Sale（Admin）

**Request Body (JSON):**
- `keyword` (string, required): 搜索关键词

**Query Parameters:**
- `page` (integer, optional): 页码，默认 1
- `limit` (integer, optional): 每页数量，默认 50，最大 100

**Stock Filtering (新增):**
- 自动过滤掉库存为 0 的产品
- 使用 warehouse 系统获取实际库存（而非 product.stock 字段）
- 对于有 variant 的产品：
  - 只显示库存 > 0 的 variant
  - 如果所有 variant 的库存都为 0，则完全过滤掉该产品
- 对于无 variant 的产品：
  - 如果库存为 0，则过滤掉该产品

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "name": "产品名称",
      "image": "https://...",
      "has_variants": true,
      "price": 200000,
      "stock": 50,
      "variants": [
        {
          "id": 5,
          "sku": "SKU-001",
          "option1_value": "500ml",
          "price": 200000,
          "stock": 50
        }
      ]
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 100,
    "last_page": 2
  }
}
```

**Lưu ý:**
- `stock` 字段显示的是从 warehouse 系统获取的实际库存
- 返回的产品列表已自动过滤掉库存为 0 的产品和 variant
- 分页信息基于原始查询结果，实际返回的数据可能少于分页显示的数量（因为过滤）

**Trạng thái:** Hoàn thành (已升级 - 添加库存过滤)

---

---

## Order Management API

### 1. GET /admin/api/orders

**Mục tiêu:** 获取订单列表，支持分页和过滤

**Tham số đầu vào (Query Params):**
- `page` (integer, optional): 页码，默认 1
- `limit` (integer, optional): 每页数量，默认 10，最大 100
- `status` (string, optional): 状态过滤 (0=待处理, 1=已确认, 2=已发货, 3=已完成, 4=已取消)
- `payment` (string, optional): 支付状态过滤 (0=未支付, 1=已支付, 2=已退款)
- `ship` (string, optional): 运输状态过滤 (0=未发货, 1=已发货, 2=已收货, 3=已退货, 4=已取消)
- `keyword` (string, optional): 关键词搜索（订单号、姓名、电话）
- `date_from` (string, optional): 开始日期 (YYYY-MM-DD)
- `date_to` (string, optional): 结束日期 (YYYY-MM-DD)
- `user_id` (integer, optional): 客户ID过滤

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "code": "1704067200",
      "name": "Nguyễn Văn A",
      "phone": "0123456789",
      "email": "email@example.com",
      "address": "123 Đường ABC",
      "province": {
        "id": 1,
        "name": "Hà Nội"
      },
      "district": {
        "id": 1,
        "name": "Quận 1"
      },
      "ward": {
        "id": 1,
        "name": "Phường 1"
      },
      "total": 500000,
      "sale": 50000,
      "fee_ship": 30000,
      "status": "0",
      "status_label": "Chờ xử lý",
      "promotion": {
        "id": 1,
        "code": "SALE10"
      },
      "member": {
        "id": 1,
        "name": "Nguyễn Văn A"
      },
      "items_count": 3,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 100,
    "last_page": 10
  }
}
```

**Trạng thái:** Hoàn thành

---

### 2. GET /admin/api/orders/{id}

**Mục tiêu:** 获取订单详情（包含订单明细）

**Tham số đầu vào:**
- `id` (integer, required): 订单ID（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "code": "1704067200",
    "name": "Nguyễn Văn A",
    "phone": "0123456789",
    "email": "email@example.com",
    "address": "123 Đường ABC",
    "province": {
      "id": 1,
      "name": "Hà Nội"
    },
    "district": {
      "id": 1,
      "name": "Quận 1"
    },
    "ward": {
      "id": 1,
      "name": "Phường 1"
    },
    "remark": "Ghi chú",
    "total": 500000,
    "sale": 50000,
    "fee_ship": 30000,
    "status": "0",
    "status_label": "Chờ xử lý",
    "promotion": {
      "id": 1,
      "code": "SALE10",
      "name": "Giảm 10%"
    },
    "member": {
      "id": 1,
      "name": "Nguyễn Văn A",
      "email": "email@example.com"
    },
    "items": [
      {
        "id": 1,
        "product_id": 10,
        "product_name": "Sản phẩm",
        "product_slug": "san-pham",
        "variant_id": 1,
        "variant": {
          "id": 1,
          "sku": "SKU-001",
          "option1_value": "500ml"
        },
        "color": {
          "id": 1,
          "name": "Đỏ"
        },
        "size": {
          "id": 1,
          "name": "500ml",
          "unit": "ml"
        },
        "price": 100000,
        "qty": 2,
        "subtotal": 200000,
        "image": "https://cdn.lica.vn/uploads/images/product.jpg",
        "weight": 1.0
      }
    ],
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Trạng thái:** Hoàn thành

---

### 3. PATCH /admin/api/orders/{id}/status

**Mục tiêu:** 更新订单状态（包括库存管理逻辑）

**Tham số đầu vào:**
- `id` (integer, required): 订单ID（URL参数）

**Body - JSON:**
- `status` (string, required): 状态值 (0=待处理, 1=已确认, 2=已发货, 3=已完成, 4=已取消)
- `payment` (string, optional): 支付状态 (0=未支付, 1=已支付, 2=已退款)
- `ship` (string, optional): 运输状态 (0=未发货, 1=已发货, 2=已收货, 3=已退货, 4=已取消)
- `content` (string, optional): 管理员备注

**Logic xử lý:**
- Khi chuyển từ trạng thái khác sang "Đã hủy" (status=4): Hoàn lại tồn kho cho tất cả sản phẩm
- Khi chuyển từ "Đã hủy" sang trạng thái khác: Trừ lại tồn kho (nếu đủ tồn kho)
- Sử dụng Database Transaction và Row Locking để đảm bảo tính nhất quán

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Cập nhật trạng thái thành công",
  "data": {
    "id": 123,
    "code": "1704067200",
    "status": "1",
    "status_label": "Đã xác nhận",
    "payment": "0",
    "ship": "0",
    "items": [...]
  }
}
```

**Phản hồi lỗi (400):**
```json
{
  "success": false,
  "message": "Không đủ tồn kho cho sản phẩm: Tên sản phẩm"
}
```

**Trạng thái:** Hoàn thành

---

### 4. PUT /admin/api/orders/{id}

**Mục tiêu:** 更新订单信息（客户信息、地址、备注、运费、产品列表）

**Tham số đầu vào:**
- `id` (integer, required): 订单ID（URL参数）

**Body - JSON:**
- `name` (string, optional): 客户姓名
- `phone` (string, optional): 电话号码
- `email` (string, optional): 邮箱
- `address` (string, optional): 详细地址
- `provinceid` (integer, optional): 省份ID
- `districtid` (integer, optional): 区县ID
- `wardid` (integer, optional): 街道ID
- `remark` (string, optional): 客户备注
- `content` (string, optional): 管理员备注
- `fee_ship` (numeric, optional): 运费
- `items` (array, optional): 产品列表
  - `id` (integer, optional): OrderDetail ID（如果存在则更新，不存在则新增）
  - `product_id` (integer, required): 产品ID
  - `variant_id` (integer, optional): 变体ID
  - `qty` (integer, required): 数量
  - `price` (numeric, optional): 单价（如果不提供则使用当前价格）

**Logic xử lý:**
- 更新客户信息和地址
- 如果提供 `items` 数组：
  - 更新现有 items（如果提供 `id`）
  - 添加新 items（如果不提供 `id`）
  - 删除不在数组中的 items
  - 自动调整库存（增加/减少数量时）
  - 重新计算订单总金额
- 使用 Database Transaction 和 Row Locking
- 如果订单已取消（status=4），不允许更新

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Cập nhật đơn hàng thành công",
  "data": {
    "id": 123,
    "code": "1704067200",
    "name": "Nguyễn Văn A",
    "phone": "0123456789",
    "items": [...],
    "total": 500000,
    "fee_ship": 30000,
    "final_total": 480000
  }
}
```

**Phản hồi lỗi (400):**
```json
{
  "success": false,
  "message": "Không thể chỉnh sửa đơn hàng đã hủy"
}
```

**Phản hồi lỗi (400 - 库存不足):**
```json
{
  "success": false,
  "message": "Không đủ tồn kho cho sản phẩm: Tên sản phẩm"
}
```

**Trạng thái:** Hoàn thành

---

## Slider Management API

### 1. GET /admin/api/sliders

**Mục tiêu:** 获取slider列表，支持分页和过滤

**Tham số đầu vào (Query Params):**
- `page` (integer, optional): 页码，默认 1
- `limit` (integer, optional): 每页数量，默认 10，最大 100
- `status` (string, optional): 状态过滤 (0=隐藏, 1=显示)
- `display` (string, optional): 设备类型过滤 (desktop/mobile)
- `keyword` (string, optional): 关键词搜索（slider名称）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Slider Tiêu Đề",
      "link": "https://example.com",
      "image": "https://r2-domain.com/uploads/sliders/image.jpg",
      "display": "desktop",
      "status": "1",
      "sort": 1,
      "user": {
        "id": 1,
        "name": "Admin User"
      },
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

**Trạng thái:** Hoàn thành

---

### 2. GET /admin/api/sliders/{id}

**Mục tiêu:** 获取单个slider详情

**Tham số đầu vào:**
- `id` (integer, required): Slider ID（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Slider Tiêu Đề",
    "link": "https://example.com",
    "image": "https://r2-domain.com/uploads/sliders/image.jpg",
    "display": "desktop",
    "status": "1",
    "sort": 1,
    "user": {
      "id": 1,
      "name": "Admin User"
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Trạng thái:** Hoàn thành

---

### 3. POST /admin/api/sliders

**Mục tiêu:** 创建新slider

**Tham số đầu vào (Body - JSON):**
- `name` (string, required): Slider标题，1-250字符
- `link` (string, optional): 链接URL
- `image` (string, optional): 图片路径
- `display` (string, required): 设备类型 (desktop/mobile)
- `status` (string, required): 状态 (0/1)

**Phản hồi mẫu (201):**
```json
{
  "success": true,
  "message": "Tạo slider thành công",
  "data": {
    "id": 1,
    "name": "Slider Tiêu Đề",
    "link": "https://example.com",
    "image": "https://r2-domain.com/uploads/sliders/image.jpg",
    "display": "desktop",
    "status": "1",
    "sort": 0,
    "user": {
      "id": 1,
      "name": "Admin User"
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Trạng thái:** Hoàn thành

---

### 4. PUT /admin/api/sliders/{id}

**Mục tiêu:** 更新slider

**Tham số đầu vào:**
- `id` (integer, required): Slider ID（URL参数）

**Body - JSON:** (同POST，所有字段)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Cập nhật slider thành công",
  "data": {
    "id": 1,
    "name": "Slider Tiêu Đề Updated",
    "link": "https://example.com/new",
    "image": "https://r2-domain.com/uploads/sliders/image-new.jpg",
    "display": "mobile",
    "status": "1",
    "sort": 1,
    "user": {
      "id": 1,
      "name": "Admin User"
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Trạng thái:** Hoàn thành

---

### 5. DELETE /admin/api/sliders/{id}

**Mục tiêu:** 删除slider

**Tham số đầu vào:**
- `id` (integer, required): Slider ID（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Xóa slider thành công"
}
```

**Trạng thái:** Hoàn thành

---

### 6. PATCH /admin/api/sliders/{id}/status

**Mục tiêu:** 更新slider状态

**Tham số đầu vào:**
- `id` (integer, required): Slider ID（URL参数）

**Body - JSON:**
- `status` (string, required): 状态值 (0=隐藏, 1=显示)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Cập nhật trạng thái thành công",
  "data": {
    "id": 1,
    "name": "Slider Tiêu Đề",
    "status": "1",
    "display": "desktop",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Trạng thái:** Hoàn thành

---

## Public Slider API V1

### 1. GET /api/v1/sliders

**Mục tiêu:** 获取活跃slider列表（公开API，无需认证）

**Tham số đầu vào (Query Params):**
- `display` (string, optional): 设备类型过滤
  - `desktop` - 仅获取desktop slider
  - `mobile` - 仅获取mobile slider
  - 不提供 - 获取所有slider

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Slider Tiêu Đề",
      "link": "https://example.com",
      "image": "https://r2-domain.com/uploads/sliders/image.jpg",
      "display": "desktop",
      "status": "1",
      "sort": 1,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

**Lưu ý:** 
- 仅返回 `status = 1` 的slider
- 按 `sort` ASC排序，然后按 `created_at` DESC排序
- 无需认证，公开访问

**Trạng thái:** Hoàn thành

---

---

## User Order API V1 (Authenticated Users)

### 1. GET /api/v1/orders

**Mục tiêu:** 获取用户订单列表（需要登录认证）

**Authentication:** Required (auth:member)

**Tham số đầu vào (Query Params):**
- `page` (integer, optional): 页码，默认 1
- `limit` (integer, optional): 每页数量，默认 10，最大 50
- `status` (string, optional): 状态过滤 (0=待处理, 1=已确认, 2=已发货, 3=已完成, 4=已取消)
- `payment` (string, optional): 支付状态过滤 (0=未支付, 1=已支付, 2=已退款)
- `ship` (string, optional): 运输状态过滤 (0=未发货, 1=已发货, 2=已收货, 3=已退货, 4=已取消)
- `date_from` (string, optional): 开始日期 (YYYY-MM-DD)
- `date_to` (string, optional): 结束日期 (YYYY-MM-DD)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "code": "1680426297",
      "date": "02-04-2023",
      "date_raw": "2023-04-02T00:00:00.000000Z",
      "address": "Hà Đông, Mỗ Lao",
      "total": 430000,
      "total_formatted": "430,000₫",
      "payment_status": "0",
      "payment_label": "Chưa thanh toán",
      "ship_status": "0",
      "ship_label": "Chưa giao hàng",
      "status": "0",
      "status_label": "Chờ xử lý"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 5,
    "last_page": 1
  }
}
```

**Phản hồi lỗi (401):**
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

**Trạng thái:** Hoàn thành

---

### 2. GET /api/v1/orders/{code}

**Mục tiêu:** 获取用户订单详情（需要登录认证）

**Authentication:** Required (auth:member)

**Tham số đầu vào:**
- `code` (string, required): 订单号（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "code": "1680426297",
    "name": "Nguyễn Văn A",
    "phone": "0123456789",
    "email": "email@example.com",
    "address": "123 Đường ABC",
    "province": {
      "id": 1,
      "name": "Hà Nội"
    },
    "district": {
      "id": 1,
      "name": "Quận 1"
    },
    "ward": {
      "id": 1,
      "name": "Phường 1"
    },
    "remark": "Ghi chú",
    "total": 430000,
    "sale": 0,
    "fee_ship": 30000,
    "final_total": 460000,
    "status": "0",
    "status_label": "Chờ xử lý",
    "payment": "0",
    "payment_label": "Chưa thanh toán",
    "ship": "0",
    "ship_label": "Chưa giao hàng",
    "items": [
      {
        "id": 1,
        "product_id": 10,
        "product_name": "Sản phẩm",
        "product_slug": "san-pham",
        "variant_id": 1,
        "variant": {
          "id": 1,
          "sku": "SKU-001",
          "option1_value": "500ml"
        },
        "price": 200000,
        "qty": 2,
        "subtotal": 400000,
        "image": "https://cdn.lica.vn/uploads/images/product.jpg",
        "weight": 1.0
      }
    ],
    "created_at": "2023-04-02T00:00:00.000000Z",
    "updated_at": "2023-04-02T00:00:00.000000Z"
  }
}
```

**Phản hồi lỗi (404):**
```json
{
  "success": false,
  "message": "Đơn hàng không tồn tại hoặc không thuộc về bạn"
}
```

**Phản hồi lỗi (401):**
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

**Trạng thái:** Hoàn thành

---

---

## Deal Management API

### 1. GET /admin/api/deals

**Mục tiêu:** 获取Deal列表，支持分页和过滤

**Tham số đầu vào (Query Params):**
- `page` (integer, optional): 页码，默认 1
- `limit` (integer, optional): 每页数量，默认 10
- `status` (string, optional): 状态过滤 (0=未激活, 1=激活)
- `keyword` (string, optional): 关键词搜索（Deal名称）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Deal sốc tháng 1",
      "start": "2024-01-01T00:00:00+00:00",
      "end": "2024-01-31T23:59:59+00:00",
      "start_timestamp": 1704067200,
      "end_timestamp": 1706745599,
      "status": "1",
      "status_text": "Kích hoạt",
      "limited": 3,
      "is_active": true,
      "created_by": {
        "id": 1,
        "name": "Admin User"
      },
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 25,
    "last_page": 3
  }
}
```

**Trạng thái:** Hoàn thành

---

### 2. GET /admin/api/deals/{id}

**Mục tiêu:** 获取单个Deal详情（包含产品列表和促销产品列表）

**Tham số đầu vào:**
- `id` (integer, required): Deal ID（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Deal sốc tháng 1",
    "start": "2024-01-01T00:00:00+00:00",
    "end": "2024-01-31T23:59:59+00:00",
    "start_timestamp": 1704067200,
    "end_timestamp": 1706745599,
    "status": "1",
    "status_text": "Kích hoạt",
    "limited": 3,
    "is_active": true,
    "created_by": {
      "id": 1,
      "name": "Admin User"
    },
    "products": [
      {
        "id": 10,
        "product_id": 5,
        "variant_id": 12,
        "product": {
          "id": 5,
          "name": "Sản phẩm chính A",
          "image": "https://example.com/image.jpg",
          "has_variants": true,
          "price": 300000,
          "stock": 50
        },
        "variant": {
          "id": 12,
          "sku": "SKU-001",
          "option1_value": "500ml",
          "price": 300000,
          "stock": 50
        },
        "status": "1",
        "status_text": "Kích hoạt"
      }
    ],
    "sale_products": [
      {
        "id": 20,
        "product_id": 8,
        "variant_id": 15,
        "product": {
          "id": 8,
          "name": "Sản phẩm khuyến mãi B",
          "image": "https://example.com/image2.jpg",
          "has_variants": true,
          "price": 200000,
          "stock": 30
        },
        "variant": {
          "id": 15,
          "sku": "SKU-002",
          "option1_value": "250ml",
          "price": 200000,
          "stock": 30
        },
        "deal_price": 150000,
        "original_price": 200000,
        "savings_amount": 50000,
        "qty": 2,
        "status": "1",
        "status_text": "Kích hoạt"
      }
    ],
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

**Trạng thái:** Hoàn thành

---

### 3. POST /admin/api/deals

**Mục tiêu:** 创建新Deal

**Tham số đầu vào (Body JSON):**
```json
{
  "name": "Deal sốc tháng 2",
  "start": "2024-02-01T00:00:00",
  "end": "2024-02-29T23:59:59",
  "status": "1",
  "limited": 3,
  "products": [
    {
      "product_id": 5,
      "variant_id": 12,
      "status": "1"
    },
    {
      "product_id": 6,
      "variant_id": null,
      "status": "1"
    }
  ],
  "sale_products": [
    {
      "product_id": 8,
      "variant_id": 15,
      "price": 150000,
      "qty": 2,
      "status": "1"
    }
  ]
}
```

**Validation Rules:**
- `name`: required|string|max:255
- `start`: required|date
- `end`: required|date|after:start
- `status`: required|in:0,1
- `limited`: required|integer|min:1
- `products.*.product_id`: required|exists:posts,id
- `products.*.variant_id`: nullable|exists:variants,id
- `products.*.status`: required|in:0,1
- `sale_products.*.product_id`: required|exists:posts,id
- `sale_products.*.variant_id`: nullable|exists:variants,id
- `sale_products.*.price`: required|numeric|min:0
- `sale_products.*.qty`: required|integer|min:1
- `sale_products.*.status`: required|in:0,1

**Custom Validation:**
- Nếu sản phẩm có `has_variants = 1`, thì `variant_id` bắt buộc phải có
- Nếu sản phẩm có `has_variants = 0`, thì `variant_id` phải là NULL
- `variant_id` phải thuộc về `product_id` tương ứng

**Stock Validation (新增):**
- 所有产品的库存必须 > 0 才能参与 Deal
- 验证 `products` 和 `sale_products` 数组中的所有产品
- 有 variant 的产品：从 warehouse 系统获取实际库存
- 无 variant 的产品：使用 product.stock 字段或默认 variant 的库存
- 如果产品库存为 0，返回 422 错误

**Phản hồi mẫu (201):**
```json
{
  "success": true,
  "message": "Tạo Deal thành công",
  "data": {
    "id": 1,
    "name": "Deal sốc tháng 2",
    ...
  }
}
```

**Phản hồi lỗi (409 - Conflict):**
```json
{
  "success": false,
  "message": "Một số sản phẩm đã thuộc Deal khác đang hoạt động",
  "conflicts": [
    {
      "product_id": 5,
      "variant_id": 12,
      "conflict_deal_id": 3
    }
  ]
}
```

**Phản hồi lỗi (422 - Stock Validation):**
```json
{
  "success": false,
  "message": "Một số sản phẩm không có tồn kho, không thể tham gia Deal",
  "errors": {
    "products.0.stock": ["Tồn kho phải lớn hơn 0"],
    "products.1.stock": ["Tồn kho phải lớn hơn 0"]
  }
}
```

**Trạng thái:** Hoàn thành

---

### 4. PUT /admin/api/deals/{id}

**Mục tiêu:** 更新Deal信息

**Tham số đầu vào:** Tương tự POST, nhưng tất cả fields đều optional (sử dụng `sometimes`)

**Stock Validation (新增):**
- 更新产品时，所有产品的库存必须 > 0
- 如果产品库存为 0，返回 422 错误

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Cập nhật Deal thành công",
  "data": {
    "id": 1,
    ...
  }
}
```

**Trạng thái:** Hoàn thành

---

### 5. DELETE /admin/api/deals/{id}

**Mục tiêu:** 删除Deal

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Xóa Deal thành công"
}
```

**Trạng thái:** Hoàn thành

---

### 6. PATCH /admin/api/deals/{id}/status

**Mục tiêu:** 更新Deal状态

**Tham số đầu vào (Body JSON):**
```json
{
  "status": "1"
}
```

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Cập nhật trạng thái thành công",
  "data": {
    "id": 1,
    "status": "1",
    "status_text": "Kích hoạt",
    ...
  }
}
```

**Trạng thái:** Hoàn thành

---

## Recent Updates & Bug Fixes

### 2025-01-18

#### 1. Top Selling Products API - Logic Improvement
- **Updated:** `GET /api/products/top-selling`
- **Change:** Tính toán dựa trên tất cả đơn hàng (trừ đơn hàng đã hủy `status = 4`) thay vì chỉ đơn hàng đã hoàn thành
- **Added Fields:** `total_sold`, `total_sold_month` trong response
- **Files Updated:**
  - `app/Http/Controllers/Api/ProductController.php` - Method `getTopSelling()`
  - Added method `getTotalSoldThisMonth()`

#### 2. Route Parameter Fix - GHTK Print
- **Issue:** Missing required parameter for route `ghtk.print`
- **Root Cause:** Route định nghĩa parameter `{label}` nhưng code đang truyền `id`
- **Fixed Files:**
  - `app/Modules/Order/Views/index.blade.php` (line 223)
  - `app/Modules/Order/Views/view.blade.php` (line 204)
  - `app/Modules/Delivery/Views/ghtk/index.blade.php` (line 88)
- **Change:** Đổi từ `route('ghtk.print',['id' => $label_id])` sang `route('ghtk.print',['label' => $label_id])`
- **Status:** ✅ Fixed

---

### 2025-01-18 (Continued)

#### 3. Deal Management API - New Implementation
- **Added:** Complete Deal Management API với hỗ trợ variants
- **Endpoints:**
  - `GET /admin/api/deals` - Danh sách Deal
  - `GET /admin/api/deals/{id}` - Chi tiết Deal
  - `POST /admin/api/deals` - Tạo Deal
  - `PUT /admin/api/deals/{id}` - Cập nhật Deal
  - `DELETE /admin/api/deals/{id}` - Xóa Deal
  - `PATCH /admin/api/deals/{id}/status` - Cập nhật trạng thái
- **Features:**
  - Hỗ trợ variants cho cả sản phẩm chính và sản phẩm mua kèm
  - Validation tự động kiểm tra variant thuộc về product
  - Kiểm tra xung đột Deal dựa trên cặp (product_id, variant_id)
  - Tính toán số tiền tiết kiệm tự động
- **Files Created:**
  - `app/Modules/ApiAdmin/Controllers/DealController.php`
  - `app/Http/Resources/Deal/DealResource.php`
  - `app/Http/Resources/Deal/DealDetailResource.php`
  - `app/Http/Resources/Deal/ProductDealResource.php`
  - `app/Http/Resources/Deal/SaleDealResource.php`
  - `database/migrations/2026_01_18_172527_add_variant_id_to_deal_products_and_deal_sales_tables.php`
- **Files Updated:**
  - `app/Modules/Deal/Models/ProductDeal.php` - Thêm relationship với Variant
  - `app/Modules/Deal/Models/SaleDeal.php` - Thêm relationship với Variant
  - `app/Modules/ApiAdmin/routes.php` - Đăng ký Deal routes
- **Status:** ✅ Completed

---

## Warehouse Management API (V1)

### Overview
Module Quản lý Kho hàng đã được nâng cấp sang RESTful API V1 với đầy đủ các chức năng quản lý tồn kho, phiếu nhập/xuất hàng và thống kê.

**Base URL:** `/admin/api/v1/warehouse`

**Xem chi tiết:** Xem file `WAREHOUSE_API_CONVERSION_PLAN.md` để biết đầy đủ thông tin về các endpoints.

---

### A. Inventory Management (Quản lý Tồn kho)

#### 1. GET /admin/api/v1/warehouse/inventory
**Mục tiêu:** Lấy danh sách tồn kho với phân trang và bộ lọc

**Tham số đầu vào (Query Params):**
- `page` (integer, optional): Trang hiện tại, mặc định 1
- `limit` (integer, optional): Số lượng mỗi trang, mặc định 10, tối đa 100
- `keyword` (string, optional): Tìm kiếm theo tên sản phẩm hoặc SKU
- `variant_id` (integer, optional): Lọc theo variant ID
- `product_id` (integer, optional): Lọc theo product ID
- `min_stock` (integer, optional): Lọc tồn kho tối thiểu
- `max_stock` (integer, optional): Lọc tồn kho tối đa
- `sort_by` (string, optional): Sắp xếp theo (stock, product_name, variant_name), mặc định 'product_name'
- `sort_order` (string, optional): Thứ tự sắp xếp (asc, desc), mặc định 'asc'

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "variant_id": 1,
      "variant_sku": "SKU-001",
      "variant_option": "500ml",
      "product_id": 10,
      "product_name": "Sản phẩm A",
      "product_image": "https://example.com/image.jpg",
      "import_total": 1000,
      "export_total": 750,
      "current_stock": 250,
      "last_import_date": "2026-01-18T10:30:00.000000Z",
      "last_export_date": "2026-01-19T14:20:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 150,
    "last_page": 15
  }
}
```

**Trạng thái:** Hoàn thành

---

#### 2. GET /admin/api/v1/warehouse/inventory/{variantId}
**Mục tiêu:** Lấy chi tiết tồn kho của một variant cụ thể

**Tham số đầu vào:**
- `variantId` (integer, required): ID của variant (URL parameter)

**Trạng thái:** Hoàn thành

---

### B. Import Receipts Management (Quản lý Phiếu Nhập hàng)

#### 3. GET /admin/api/v1/warehouse/import-receipts
**Mục tiêu:** Lấy danh sách phiếu nhập hàng với phân trang và bộ lọc

**Tham số đầu vào (Query Params):**
- `page`, `limit`, `keyword`, `code`, `user_id`, `date_from`, `date_to`, `sort_by`, `sort_order`

**Trạng thái:** Hoàn thành

---

#### 4. GET /admin/api/v1/warehouse/import-receipts/{id}
**Mục tiêu:** Lấy chi tiết phiếu nhập hàng bao gồm danh sách sản phẩm

**Trạng thái:** Hoàn thành

---

#### 5. POST /admin/api/v1/warehouse/import-receipts
**Mục tiêu:** Tạo phiếu nhập hàng mới

**Tham số đầu vào (Body - JSON):**
```json
{
  "code": "NH-ORDER001-1705564800",
  "subject": "Nhập hàng từ nhà cung cấp ABC",
  "content": "Ghi chú nhập hàng",
  "vat_invoice": "VAT-2026-001",
  "items": [
    {
      "variant_id": 10,
      "price": 100000,
      "quantity": 20
    }
  ]
}
```

**Validation Logic:**
- `code`: required, unique:warehouse,code
- `subject`: required, max:255
- `items`: required, array, min:1
- `items.*.variant_id`: required, exists:variants,id
- `items.*.price`: required, numeric, min:0
- `items.*.quantity`: required, integer, min:1

**Trạng thái:** Hoàn thành

---

#### 6. PUT /admin/api/v1/warehouse/import-receipts/{id}
**Mục tiêu:** Cập nhật phiếu nhập hàng

**Trạng thái:** Hoàn thành

---

#### 7. DELETE /admin/api/v1/warehouse/import-receipts/{id}
**Mục tiêu:** Xóa phiếu nhập hàng

**Trạng thái:** Hoàn thành

---

#### 8. GET /admin/api/v1/warehouse/import-receipts/{id}/print
**Mục tiêu:** Lấy thông tin phiếu nhập hàng để in (bao gồm QR code, mã phiếu, tổng bằng chữ)

**Trạng thái:** Hoàn thành

---

### C. Export Receipts Management (Quản lý Phiếu Xuất hàng)

#### 9. GET /admin/api/v1/warehouse/export-receipts
**Mục tiêu:** Lấy danh sách phiếu xuất hàng với phân trang và bộ lọc

**Trạng thái:** Hoàn thành

---

#### 10. GET /admin/api/v1/warehouse/export-receipts/{id}
**Mục tiêu:** Lấy chi tiết phiếu xuất hàng

**Trạng thái:** Hoàn thành

---

#### 11. POST /admin/api/v1/warehouse/export-receipts
**Mục tiêu:** Tạo phiếu xuất hàng mới

**Validation Logic:** Tương tự import-receipts, nhưng thêm kiểm tra tồn kho

**Lỗi khi thiếu tồn kho (422):**
```json
{
  "success": false,
  "message": "Không đủ tồn kho để xuất hàng",
  "errors": {
    "items.0.quantity": [
      "Số lượng vượt quá tồn kho. Tồn kho hiện tại: 10"
    ]
  }
}
```

**Trạng thái:** Hoàn thành

---

#### 12. PUT /admin/api/v1/warehouse/export-receipts/{id}
**Mục tiêu:** Cập nhật phiếu xuất hàng

**Trạng thái:** Hoàn thành

---

#### 13. DELETE /admin/api/v1/warehouse/export-receipts/{id}
**Mục tiêu:** Xóa phiếu xuất hàng

**Trạng thái:** Hoàn thành

---

#### 14. GET /admin/api/v1/warehouse/export-receipts/{id}/print
**Mục tiêu:** Lấy thông tin phiếu xuất hàng để in

**Trạng thái:** Hoàn thành

---

### D. Supporting Endpoints (Các Endpoint Hỗ trợ)

#### 15. GET /admin/api/v1/warehouse/products/search
**Mục tiêu:** Tìm kiếm sản phẩm để chọn khi tạo phiếu nhập/xuất

**Tham số đầu vào (Query Params):**
- `q` (string, required, min:2): Từ khóa tìm kiếm
- `limit` (integer, optional): Số lượng kết quả, mặc định 50, tối đa 100

**Trạng thái:** Hoàn thành

---

#### 16. GET /admin/api/v1/warehouse/products/{productId}/variants
**Mục tiêu:** Lấy danh sách phân loại của một sản phẩm

**Trạng thái:** Hoàn thành

---

#### 17. GET /admin/api/v1/warehouse/variants/{variantId}/stock
**Mục tiêu:** Lấy thông tin tồn kho của một variant

**Trạng thái:** Hoàn thành

---

#### 18. GET /admin/api/v1/warehouse/variants/{variantId}/price
**Mục tiêu:** Lấy giá đề xuất cho variant (giá bán hoặc giá nhập gần nhất)

**Tham số đầu vào (Query Params):**
- `type` (string, optional): Loại giá (import|export), mặc định 'export'

**Trạng thái:** Hoàn thành

---

### E. Statistics (Thống kê)

#### 19. GET /admin/api/v1/warehouse/statistics/quantity
**Mục tiêu:** Thống kê số lượng tồn kho theo variant

**Trạng thái:** Hoàn thành

---

#### 20. GET /admin/api/v1/warehouse/statistics/revenue
**Mục tiêu:** Thống kê doanh thu theo variant

**Trạng thái:** Hoàn thành

---

#### 21. GET /admin/api/v1/warehouse/statistics/summary
**Mục tiêu:** Tổng hợp thống kê tổng quan kho hàng

**Tham số đầu vào (Query Params):**
- `date_from` (date, optional): Từ ngày (format: YYYY-MM-DD)
- `date_to` (date, optional): Đến ngày (format: YYYY-MM-DD)

**Trạng thái:** Hoàn thành

---

### Implementation Details

**Files Created:**
- `app/Services/Warehouse/WarehouseServiceInterface.php`
- `app/Services/Warehouse/WarehouseService.php`
- `app/Http/Requests/Warehouse/StoreImportReceiptRequest.php`
- `app/Http/Requests/Warehouse/UpdateImportReceiptRequest.php`
- `app/Http/Requests/Warehouse/StoreExportReceiptRequest.php`
- `app/Http/Requests/Warehouse/UpdateExportReceiptRequest.php`
- `app/Http/Resources/Warehouse/InventoryResource.php`
- `app/Http/Resources/Warehouse/ImportReceiptResource.php`
- `app/Http/Resources/Warehouse/ImportReceiptCollection.php`
- `app/Http/Resources/Warehouse/ExportReceiptResource.php`
- `app/Http/Resources/Warehouse/ExportReceiptCollection.php`
- `app/Http/Resources/Warehouse/ReceiptItemResource.php`
- `app/Modules/ApiAdmin/Controllers/WarehouseController.php`

**Files Updated:**
- `app/Modules/Warehouse/Models/Warehouse.php` - Thêm relationship `items()`
- `app/Modules/ApiAdmin/routes.php` - Đăng ký Warehouse routes
- `app/Providers/AppServiceProvider.php` - Đăng ký WarehouseService

**Features:**
- ✅ Đầy đủ CRUD cho Import/Export receipts
- ✅ Kiểm tra tồn kho tự động khi xuất hàng
- ✅ Hỗ trợ VAT invoice
- ✅ Tự động tạo mã phiếu nhập/xuất hàng
- ✅ QR code và tổng bằng chữ tiếng Việt
- ✅ Thống kê tồn kho, doanh thu, tổng hợp
- ✅ Tìm kiếm sản phẩm và lấy phân loại
- ✅ Validation đầy đủ với thông báo lỗi chi tiết

---

---

## Flash Sale Mixed Pricing API (Mua vượt hạn mức)

### 1. POST /api/price/calculate

**Mục tiêu:** Tính giá với số lượng (hỗ trợ giá hỗn hợp khi mua vượt hạn mức Flash Sale)

**Tham số đầu vào (Body - JSON):**
- `product_id` (integer, required): Product ID
- `variant_id` (integer, optional): Variant ID
- `quantity` (integer, required): Số lượng mua

**Logic:**
- Nếu `quantity ≤ flash_sale_remaining`: Tất cả tính theo giá Flash Sale
- Nếu `quantity > flash_sale_remaining`: 
  - Phần trong hạn mức tính theo giá Flash Sale
  - Phần vượt hạn mức tính theo giá ưu tiên tiếp theo (Promotion hoặc giá gốc)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "total_price": 1500000,
    "price_breakdown": [
      {
        "type": "flashsale",
        "quantity": 5,
        "unit_price": 100000,
        "subtotal": 500000
      },
      {
        "type": "promotion",
        "quantity": 10,
        "unit_price": 100000,
        "subtotal": 1000000
      }
    ],
    "flash_sale_remaining": 5,
    "warning": "Chỉ còn 5 sản phẩm giá Flash Sale, 10 sản phẩm còn lại sẽ được tính theo giá khuyến mãi",
    "flash_sale_id": 1,
    "product_sale_id": 10
  }
}
```

**Trạng thái:** Hoàn thành

---

**最后更新:** 2026-01-20
**维护者:** AI Assistant
