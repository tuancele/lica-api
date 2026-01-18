# API 端点测试指南

本文档提供测试 Product API 端点的详细指南。

## 前置条件

1. **Laravel 应用正在运行**
   ```bash
   php artisan serve
   # 或使用 Laragon/XAMPP 等
   ```

2. **API 认证配置**
   - 当前 API 使用 `auth:api` 中间件
   - 需要有效的 API token 或临时禁用认证进行测试

## 测试方法

### 方法 1: 使用测试脚本

运行提供的测试脚本：

```bash
php test_api_endpoints.php
```

**注意：** 如果启用了认证，需要先获取 API token 并修改脚本中的 `$token` 变量。

### 方法 2: 使用 cURL

#### 1. 获取产品列表
```bash
curl -X GET "http://localhost/admin/api/products?limit=10" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 2. 获取单个产品详情
```bash
curl -X GET "http://localhost/admin/api/products/1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 3. 创建产品
```bash
curl -X POST "http://localhost/admin/api/products" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Test Product",
    "slug": "test-product",
    "description": "Test description",
    "status": "1",
    "price": 100000,
    "sale": 80000,
    "sku": "TEST-SKU-001",
    "has_variants": 0,
    "stock_qty": 100
  }'
```

#### 4. 更新产品
```bash
curl -X PUT "http://localhost/admin/api/products/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "id": 1,
    "name": "Updated Product",
    "slug": "updated-product",
    "status": "1"
  }'
```

#### 5. 删除产品
```bash
curl -X DELETE "http://localhost/admin/api/products/1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 6. 更新产品状态
```bash
curl -X PATCH "http://localhost/admin/api/products/1/status" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "status": "0"
  }'
```

#### 7. 批量操作
```bash
curl -X POST "http://localhost/admin/api/products/bulk-action" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "checklist": [1, 2, 3],
    "action": 1
  }'
```

#### 8. 更新排序
```bash
curl -X PATCH "http://localhost/admin/api/products/sort" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "sort": {
      "1": 10,
      "2": 20,
      "3": 30
    }
  }'
```

#### 9. 获取产品变体列表
```bash
curl -X GET "http://localhost/admin/api/products/1/variants" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 10. 获取单个变体详情
```bash
curl -X GET "http://localhost/admin/api/products/1/variants/1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 11. 创建变体
```bash
curl -X POST "http://localhost/admin/api/products/1/variants" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "sku": "VARIANT-SKU-001",
    "product_id": 1,
    "price": 120000,
    "sale": 100000,
    "stock": 50,
    "weight": 600
  }'
```

#### 12. 更新变体
```bash
curl -X PUT "http://localhost/admin/api/products/1/variants/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "price": 130000,
    "sale": 110000
  }'
```

#### 13. 删除变体
```bash
curl -X DELETE "http://localhost/admin/api/products/1/variants/1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 方法 3: 使用 Postman

1. 导入以下集合配置：

```json
{
  "info": {
    "name": "Product API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Get Products",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Accept",
            "value": "application/json"
          }
        ],
        "url": {
          "raw": "{{base_url}}/admin/api/products?limit=10",
          "host": ["{{base_url}}"],
          "path": ["admin", "api", "products"],
          "query": [
            {"key": "limit", "value": "10"}
          ]
        }
      }
    }
  ]
}
```

2. 设置环境变量：
   - `base_url`: `http://localhost`
   - `token`: 你的 API token

### 方法 4: 使用 PHPUnit 测试

创建测试文件 `tests/Feature/ApiAdmin/ProductApiTest.php`:

```php
<?php

namespace Tests\Feature\ApiAdmin;

use Tests\TestCase;
use App\Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_products_list()
    {
        $response = $this->getJson('/admin/api/products');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                     'pagination'
                 ]);
    }
    
    // 添加更多测试...
}
```

## 测试检查清单

### 产品管理 API

- [ ] GET /admin/api/products - 列表（分页、过滤）
- [ ] GET /admin/api/products/{id} - 详情
- [ ] POST /admin/api/products - 创建
- [ ] PUT /admin/api/products/{id} - 更新
- [ ] DELETE /admin/api/products/{id} - 删除
- [ ] PATCH /admin/api/products/{id}/status - 更新状态
- [ ] POST /admin/api/products/bulk-action - 批量操作
- [ ] PATCH /admin/api/products/sort - 更新排序

### 变体管理 API

- [ ] GET /admin/api/products/{id}/variants - 变体列表
- [ ] GET /admin/api/products/{id}/variants/{code} - 变体详情
- [ ] POST /admin/api/products/{id}/variants - 创建变体
- [ ] PUT /admin/api/products/{id}/variants/{code} - 更新变体
- [ ] DELETE /admin/api/products/{id}/variants/{code} - 删除变体

## 常见问题

### 1. 401 Unauthorized

**原因：** API 需要认证但未提供 token

**解决：**
- 获取有效的 API token
- 或在路由中临时移除 `auth:api` 中间件进行测试

### 2. 404 Not Found

**原因：** 路由未正确注册

**解决：**
```bash
php artisan route:clear
php artisan route:cache
php artisan route:list --path=admin/api
```

### 3. 422 Validation Error

**原因：** 请求数据验证失败

**解决：** 检查请求数据是否符合验证规则（参考 `API_ADMIN_DOCS.md`）

### 4. 500 Internal Server Error

**原因：** 服务器内部错误

**解决：**
- 检查 Laravel 日志：`storage/logs/laravel.log`
- 确保数据库连接正常
- 检查所有依赖服务是否运行

## 性能测试

使用 Apache Bench (ab) 进行性能测试：

```bash
ab -n 100 -c 10 -H "Accept: application/json" \
   http://localhost/admin/api/products
```

## 安全测试

1. **SQL 注入测试：** 在参数中尝试 SQL 代码
2. **XSS 测试：** 在输入字段中尝试脚本代码
3. **CSRF 测试：** 验证 API 是否正确处理跨站请求
4. **认证测试：** 验证未授权访问是否被拒绝

## 测试报告模板

```
测试日期: YYYY-MM-DD
测试人员: [姓名]
环境: [开发/测试/生产]

测试结果:
- 总测试数: 13
- 通过: X
- 失败: Y
- 跳过: Z

详细结果:
[列出每个端点的测试结果]
```

---

**最后更新：** 2024-01-XX
