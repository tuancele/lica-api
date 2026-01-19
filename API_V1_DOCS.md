# API V1 Documentation

本文档记录所有 Public API V1 端点的详细信息，按照 RESTful 标准设计。

---

## Brand API V1

### 1. GET /api/v1/brands/featured

**Mục tiêu:** 获取首页推荐品牌列表（用于首页显示）

**Tham số đầu vào (Query Params):**
- `limit` (integer, optional): 返回品牌数量，默认 14，最大 50

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "品牌名称",
      "slug": "brand-slug",
      "image": "https://cdn.lica.vn/uploads/image/brand.jpg",
      "banner": "https://cdn.lica.vn/uploads/image/banner.jpg",
      "logo": "https://cdn.lica.vn/uploads/image/logo.jpg",
      "content": "品牌描述内容",
      "gallery": [
        "https://cdn.lica.vn/uploads/image/gallery1.jpg"
      ],
      "status": "1",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "count": 14
}
```

**Trạng thái:** Hoàn thành

---

### 2. GET /api/v1/brands

**Mục tiêu:** 获取所有品牌列表，支持分页和过滤

**Tham số đầu vào (Query Params):**
- `page` (integer, optional): 页码，默认 1
- `limit` (integer, optional): 每页数量，默认 20，最大 100
- `status` (string, optional): 状态过滤 (0=未激活, 1=激活)。如果不提供，默认只返回激活的品牌
- `keyword` (string, optional): 关键词搜索（品牌名称）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "品牌名称",
      "slug": "brand-slug",
      "image": "https://cdn.lica.vn/uploads/image/brand.jpg",
      "banner": "https://cdn.lica.vn/uploads/image/banner.jpg",
      "logo": "https://cdn.lica.vn/uploads/image/logo.jpg",
      "content": "品牌描述内容",
      "gallery": [
        "https://cdn.lica.vn/uploads/image/gallery1.jpg",
        "https://cdn.lica.vn/uploads/image/gallery2.jpg"
      ],
      "status": "1",
      "total_products": 150,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "last_page": 3
  }
}
```

**Trạng thái:** Hoàn thành

---

### 3. GET /api/v1/brands/{slug}

**Mục tiêu:** 获取单个品牌详情（包含产品总数）

**Tham số đầu vào:**
- `slug` (string, required): 品牌 slug（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "品牌名称",
    "slug": "brand-slug",
    "image": "https://cdn.lica.vn/uploads/image/brand.jpg",
    "banner": "https://cdn.lica.vn/uploads/image/banner.jpg",
    "logo": "https://cdn.lica.vn/uploads/image/logo.jpg",
    "content": "品牌详细描述内容",
    "gallery": [
      "https://cdn.lica.vn/uploads/image/gallery1.jpg",
      "https://cdn.lica.vn/uploads/image/gallery2.jpg"
    ],
    "status": "1",
    "total_products": 150,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Phản hồi lỗi (404):**
```json
{
  "success": false,
  "message": "Thương hiệu không tồn tại"
}
```

**Trạng thái:** Hoàn thành

---

### 4. GET /api/v1/brands/{slug}/products

**Mục tiêu:** 获取品牌下的产品列表，支持分页、库存过滤和排序

**Tham số đầu vào:**
- `slug` (string, required): 品牌 slug（URL参数）

**Query Params:**
- `page` (integer, optional): 页码，默认 1
- `limit` (integer, optional): 每页数量，默认 30，最大 100
- `stock` (string, optional): 库存过滤 (0=缺货, 1=有货, all=全部)。默认 "all"
- `sort` (string, optional): 排序方式
  - `newest` (默认): 最新创建
  - `oldest`: 最早创建
  - `price_asc`: 价格从低到高
  - `price_desc`: 价格从高到低
  - `name_asc`: 名称 A-Z
  - `name_desc`: 名称 Z-A

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "产品名称",
      "slug": "product-slug",
      "image": "https://cdn.lica.vn/uploads/image/product.jpg",
      "gallery": ["https://..."],
      "content": "产品内容",
      "description": "产品描述",
      "price_info": {
        "price": 80000,
        "original_price": 100000,
        "type": "sale",
        "label": "Giảm giá"
      },
      "status": "1",
      "feature": "0",
      "best": "0",
      "stock": "1",
      "verified": "1",
      "sort": 0,
      "brand": {
        "id": 1,
        "name": "品牌名称",
        "slug": "brand-slug",
        "image": "https://...",
        "logo": "https://..."
      },
      "origin": {
        "id": 1,
        "name": "产地名称"
      },
      "variants": [
        {
          "id": 1,
          "sku": "SKU-001",
          "product_id": 1,
          "option1_value": "500ml",
          "image": "https://...",
          "size_id": 1,
          "color_id": 1,
          "weight": 500.0,
          "price": 100000.0,
          "sale": 80000.0,
          "stock": 50,
          "position": 0
        }
      ],
      "category": {
        "id": 1,
        "name": "分类名称",
        "slug": "category-slug"
      },
      "categories": [1, 2, 3],
      "seo_title": "SEO标题",
      "seo_description": "SEO描述",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "brand": {
    "id": 1,
    "name": "品牌名称",
    "slug": "brand-slug"
  },
  "pagination": {
    "current_page": 1,
    "per_page": 30,
    "total": 150,
    "last_page": 5
  }
}
```

**Phản hồi lỗi (404):**
```json
{
  "success": false,
  "message": "Thương hiệu không tồn tại"
}
```

**Trạng thái:** Hoàn thành

---

### 5. GET /api/v1/brands/{slug}/products/available

**Mục tiêu:** 获取品牌下有货的产品列表（快捷方式，等同于 `stock=1`）

**Tham số đầu vào:**
- `slug` (string, required): 品牌 slug（URL参数）

**Query Params:**
- `page` (integer, optional): 页码，默认 1
- `limit` (integer, optional): 每页数量，默认 30
- `sort` (string, optional): 排序方式（同 endpoint 3）

**Phản hồi:** 同 endpoint 3，但只返回 `stock=1` 的产品

**Trạng thái:** Hoàn thành

---

### 6. GET /api/v1/brands/{slug}/products/out-of-stock

**Mục tiêu:** 获取品牌下缺货的产品列表（快捷方式，等同于 `stock=0`）

**Tham số đầu vào:**
- `slug` (string, required): 品牌 slug（URL参数）

**Query Params:**
- `page` (integer, optional): 页码，默认 1
- `limit` (integer, optional): 每页数量，默认 30
- `sort` (string, optional): 排序方式（同 endpoint 3）

**Phản hồi:** 同 endpoint 3，但只返回 `stock=0` 的产品

**Trạng thái:** Hoàn thành

---

## Performance Optimization

### Eager Loading
所有产品查询都使用 Eager Loading 来优化性能，避免 N+1 查询问题：

```php
Product::with([
    'brand:id,name,slug',
    'variants:id,product_id,price,sale,stock,sku',
    'rates:id,product_id,rate',
    'origin:id,name',
    'category:id,name,slug'
])
```

这确保了在获取产品列表时，所有关联数据都在一次查询中加载，特别适合移动应用使用。

---

## Error Handling

所有 API 端点都遵循统一的错误响应格式：

**500 Internal Server Error:**
```json
{
  "success": false,
  "message": "错误消息（用户友好）",
  "error": "详细错误信息（仅在 debug 模式下显示）"
}
```

**404 Not Found:**
```json
{
  "success": false,
  "message": "资源不存在"
}
```

---

## Notes

1. **Backward Compatibility:** Web route `/thuong-hieu/{url}` 保持不变，不影响现有前端
2. **Image URLs:** 所有图片 URL 都通过 `getImage()` helper 格式化，自动使用 R2 CDN
3. **Pagination:** 所有列表端点都支持分页，默认每页 20-30 条记录
4. **Filtering:** 支持按状态、库存、关键词等多种方式过滤
5. **Sorting:** 支持多种排序方式，满足不同业务需求

---

## Flash Sale API V1

### 1. GET /api/v1/flash-sales/active

**Mục tiêu:** 获取正在进行的 Flash Sale 列表（用于移动应用显示）

**Tham số đầu vào (Query Params):**
- `limit` (integer, optional): 返回数量，默认 10，最大 50

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
  "count": 1
}
```

**Trạng thái:** Hoàn thành

---

### 2. GET /api/v1/flash-sales/{id}/products

**Mục tiêu:** 获取 Flash Sale 中的产品列表（支持分页和 Eager Loading）

**Tham số đầu vào:**
- `id` (integer, required): Flash Sale ID（URL参数）

**Query Params:**
- `page` (integer, optional): 页码，默认 1
- `limit` (integer, optional): 每页数量，默认 20，最大 100
- `available_only` (boolean, optional): 只返回有货产品（buy < number），默认 true

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "flash_sale": {
      "id": 1,
      "name": "Flash Sale Tháng 1",
      "start": "2024-01-15T00:00:00.000000Z",
      "end": "2024-01-20T23:59:59.000000Z",
      "start_timestamp": 1705276800,
      "end_timestamp": 1705708799,
      "status": "1",
      "is_active": true,
      "countdown_seconds": 432000,
      "total_products": 25
    },
    "products": [
      {
        "id": 10,
        "name": "Sản phẩm Flash Sale",
        "slug": "san-pham-flash-sale",
        "image": "https://cdn.lica.vn/uploads/image/product.jpg",
        "has_variants": true,
        "brand": {
          "id": 1,
          "name": "Brand Name"
        },
        "variants": [
          {
            "id": 5,
            "sku": "SKU-001",
            "option1_value": "500ml",
            "price": 200000,
            "stock": 50,
            "flash_sale_info": {
              "price_sale": 150000,
              "original_price": 200000,
              "discount_percent": 25,
              "number": 100,
              "buy": 45,
              "remaining": 55
            },
            "price_info": {
              "price": 150000,
              "original_price": 200000,
              "type": "flashsale",
              "label": "Flash Sale"
            }
          }
        ],
        "flash_sale_info": {
          "price_sale": 150000,
          "original_price": 200000,
          "discount_percent": 25,
          "number": 100,
          "buy": 45,
          "remaining": 55
        },
        "price_info": {
          "price": 150000,
          "original_price": 200000,
          "type": "flashsale",
          "label": "Flash Sale"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 25,
      "last_page": 2
    },
    "total_unique_products": 20
  }
}
```

**Phản hồi lỗi (404):**
```json
{
  "success": false,
  "message": "Chương trình Flash Sale không tồn tại"
}
```

**Phản hồi lỗi (400):**
```json
{
  "success": false,
  "message": "Chương trình Flash Sale không đang diễn ra"
}
```

**Trạng thái:** Hoàn thành

---

## Product API V1

### 1. GET /api/v1/products/{slug}

**Mục tiêu:** 获取产品详情信息（包括基本信息、图片库、变体、品牌、分类、评分、销量、Flash Sale、Deal、Ingredients等）

**Tham số đầu vào:**
- `slug` (string, required): 产品 slug（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Nước hoa Vùng Kín Foellie Bijou Chính Hãng 100ml",
    "slug": "nuoc-hoa-vung-kin-foellie-bijou-chinh-hang-100",
    "image": "https://cdn.lica.vn/uploads/images/product.jpg",
    "video": null,
    "gallery": [
      "https://cdn.lica.vn/uploads/images/gallery1.jpg",
      "https://cdn.lica.vn/uploads/images/gallery2.jpg"
    ],
    "description": "Mô tả ngắn",
    "content": "Nội dung chi tiết HTML",
    "ingredient": {
      "raw": "Water, Glycerin, Fragrance...",
      "html": "<p>Water, <a href='javascript:;' class='item_ingredient' data-id='glycerin'>Glycerin</a>...</p>",
      "ingredients_list": [
        {
          "name": "Glycerin",
          "slug": "glycerin",
          "link": "/ingredient-dictionary/glycerin"
        }
      ]
    },
    "seo_title": "SEO Title",
    "seo_description": "SEO Description",
    "stock": 1,
    "best": 1,
    "is_new": 0,
    "cbmp": "CBMP123456",
    "option1_name": "Phân loại",
    "has_variants": 1,
    "brand": {
      "id": 1,
      "name": "Foellie",
      "slug": "foellie",
      "image": "https://...",
      "logo": "https://..."
    },
    "origin": {
      "id": 1,
      "name": "Pháp"
    },
    "categories": [5, 12, 15],
    "category": {
      "id": 5,
      "name": "Nước hoa",
      "slug": "nuoc-hoa"
    },
    "first_variant": {
      "id": 10,
      "sku": "SKU-001",
      "price": 100000,
      "sale": 80000,
      "stock": 50
    },
    "variants": [
      {
        "id": 10,
        "sku": "SKU-001",
        "option1_value": "100ml",
        "image": "https://...",
        "price": 100000,
        "sale": 80000,
        "stock": 50,
        "weight": 0.1,
        "size_id": 1,
        "color_id": null,
        "color": null,
        "size": {
          "id": 1,
          "name": "100ml",
          "unit": "ml"
        },
        "price_info": {
          "final_price": 70000,
          "original_price": 100000,
          "type": "flashsale",
          "label": "Flash Sale",
          "discount_percent": 30,
          "html": "<p>70,000đ</p><del>100,000đ</del><div class='tag'><span>-30%</span></div>"
        },
        "option_label": "100ml"
      }
    ],
    "variants_count": 3,
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
        "comment": "Sản phẩm rất tốt",
        "user_name": "Nguyễn Văn A",
        "created_at": "2024-01-15T10:30:00.000000Z"
      }
    ],
    "flash_sale": {
      "id": 1,
      "name": "Flash Sale Tháng 1",
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
      "name": "Deal sốc",
      "limited": 2,
      "sale_deals": [
        {
          "id": 1,
          "product_id": 2,
          "product_name": "Sản phẩm kèm theo",
          "product_image": "https://...",
          "variant_id": 2,
          "price": 50000,
          "original_price": 80000
        }
      ]
    },
    "related_products": [
      {
        "id": 2,
        "name": "Sản phẩm liên quan",
        "slug": "san-pham-lien-quan",
        "image": "https://...",
        "brand": {
          "id": 1,
          "name": "Foellie",
          "slug": "foellie"
        },
        "price_info": {
          "price": 90000,
          "original_price": 120000,
          "type": "sale",
          "label": "Giảm giá",
          "discount_percent": 25
        },
        "stock": 1,
        "best": 0,
        "is_new": 1
      }
    ]
  }
}
```

**Phản hồi lỗi (404):**
```json
{
  "success": false,
  "message": "Sản phẩm không tồn tại"
}
```

**Phản hồi lỗi (500):**
```json
{
  "success": false,
  "message": "Lấy thông tin sản phẩm thất bại",
  "error": "Chi tiết lỗi (chỉ trong debug mode)"
}
```

**Đặc điểm:**
- ✅ Sử dụng Eager Loading để tối ưu performance (giảm từ ~20 queries xuống ~3-5 queries)
- ✅ Cache 30 phút (1800 giây) để giảm tải database
- ✅ Tự động xử lý ingredients/paulas linking
- ✅ Tính toán giá theo thứ tự ưu tiên: Flash Sale > Marketing Campaign > Sale > Normal
- ✅ Format image URLs tự động sử dụng R2 CDN
- ✅ Trả về đầy đủ thông tin: variants, rating, flash_sale, deal, related_products

**Trạng thái:** Hoàn thành

---

**Trạng thái:** Hoàn thành

---

## Cart API V1

### 1. GET /api/v1/cart

**Mục tiêu:** 获取当前购物车信息（包括商品列表、总价、优惠券、可用Deal等）

**Tham số đầu vào:** 无

**Authentication:** Optional (如果已登录，从数据库获取；否则从Session获取)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "variant_id": 1,
        "product_id": 10,
        "product_name": "产品名称",
        "product_slug": "product-slug",
        "product_image": "https://cdn.lica.vn/uploads/images/product.jpg",
        "variant": {
          "id": 1,
          "sku": "SKU-001",
          "option1_value": "500ml",
          "color": {"id": 1, "name": "红色"},
          "size": {"id": 1, "name": "500ml", "unit": "ml"}
        },
        "qty": 2,
        "price": 100000,
        "original_price": 150000,
        "subtotal": 200000,
        "is_deal": 0,
        "price_info": {
          "price": 100000,
          "original_price": 150000,
          "type": "flashsale",
          "label": "Flash Sale",
          "discount_percent": 33
        },
        "stock": 50,
        "available": true
      }
    ],
    "summary": {
      "total_qty": 5,
      "subtotal": 500000,
      "discount": 50000,
      "shipping_fee": 0,
      "total": 450000
    },
    "coupon": {
      "id": 1,
      "code": "SALE10",
      "discount": 50000
    },
    "available_deals": [
      {
        "id": 1,
        "name": "Deal sốc",
        "limited": 2,
        "sale_deals": [
          {
            "id": 1,
            "product_id": 2,
            "product_name": "搭配产品",
            "product_image": "https://...",
            "variant_id": 2,
            "price": 50000,
            "original_price": 80000
          }
        ]
      }
    ]
  }
}
```

**Trạng thái:** 待实现

---

### 2. POST /api/v1/cart/items

**Mục tiêu:** 添加商品到购物车

**Tham số đầu vào (Body - JSON):**
- `variant_id` (integer, required): 变体ID
- `qty` (integer, required): 数量，最小1
- `is_deal` (integer, optional): 是否为Deal商品 (0/1)，默认0

**Hoặc Combo (添加多个商品):**
- `combo` (array, optional): 商品数组
  - `variant_id` (integer, required)
  - `qty` (integer, required)
  - `is_deal` (integer, optional)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Thêm vào giỏ hàng thành công",
  "data": {
    "total_qty": 5,
    "item": {
      "variant_id": 1,
      "qty": 2,
      "price": 100000
    }
  }
}
```

**Phản hồi lỗi (400):**
```json
{
  "success": false,
  "message": "Số lượng vượt quá tồn kho"
}
```

**Trạng thái:** 待实现

---

### 3. PUT /api/v1/cart/items/{variant_id}

**Mục tiêu:** 更新购物车中商品数量

**Tham số đầu vào:**
- `variant_id` (integer, required): 变体ID（URL参数）

**Body - JSON:**
- `qty` (integer, required): 新数量，最小1

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "variant_id": 1,
    "qty": 3,
    "subtotal": 300000,
    "summary": {
      "total_qty": 6,
      "subtotal": 600000,
      "discount": 50000,
      "total": 550000
    }
  }
}
```

**Trạng thái:** 待实现

---

### 4. DELETE /api/v1/cart/items/{variant_id}

**Mục tiêu:** 从购物车删除商品

**Tham số đầu vào:**
- `variant_id` (integer, required): 变体ID（URL参数）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Xóa sản phẩm thành công",
  "data": {
    "summary": {
      "total_qty": 4,
      "subtotal": 400000,
      "total": 350000
    }
  }
}
```

**Trạng thái:** 待实现

---

### 5. POST /api/v1/cart/coupon/apply

**Mục tiêu:** 应用优惠券

**Tham số đầu vào (Body - JSON):**
- `code` (string, required): 优惠券代码

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Áp dụng mã thành công",
  "data": {
    "coupon": {
      "id": 1,
      "code": "SALE10",
      "discount": 50000
    },
    "summary": {
      "subtotal": 500000,
      "discount": 50000,
      "total": 450000
    }
  }
}
```

**Phản hồi lỗi (400):**
```json
{
  "success": false,
  "message": "Mã khuyến mãi không khả dụng"
}
```

**Trạng thái:** 待实现

---

### 6. DELETE /api/v1/cart/coupon

**Mục tiêu:** 取消优惠券

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "summary": {
      "subtotal": 500000,
      "discount": 0,
      "total": 500000
    }
  }
}
```

**Trạng thái:** 待实现

---

### 7. POST /api/v1/cart/shipping-fee

**Mục tiêu:** 计算运费

**Tham số đầu vào (Body - JSON):**
- `province_id` (integer, required): 省份ID
- `district_id` (integer, required): 区县ID
- `ward_id` (integer, required): 街道ID
- `address` (string, optional): 详细地址

**Phản hồi mẫu (200):**
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

**Trạng thái:** 待实现

---

### 8. POST /api/v1/cart/checkout

**Mục tiêu:** 提交订单

**Tham số đầu vào (Body - JSON):**
- `full_name` (string, required): 收货人姓名
- `phone` (string, required): 电话号码
- `email` (string, optional): 邮箱
- `address` (string, required): 详细地址
- `province_id` (integer, required): 省份ID
- `district_id` (integer, required): 区县ID
- `ward_id` (integer, required): 街道ID
- `remark` (string, optional): 备注
- `shipping_fee` (numeric, optional): 运费（如果已计算）

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Đặt hàng thành công",
  "data": {
    "order_code": "1704067200",
    "order_id": 123,
    "redirect_url": "/cart/dat-hang-thanh-cong?code=1704067200"
  }
}
```

**Phản hồi lỗi (400):**
```json
{
  "success": false,
  "message": "Giỏ hàng trống"
}
```

**Trạng thái:** 待实现

---

**Ngày tạo:** 2025-01-18
**Phiên bản:** V1
**Trạng thái:** Hoàn thành
