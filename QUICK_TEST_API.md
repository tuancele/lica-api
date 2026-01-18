# API 快速测试指南

## 重要提示

当前 API 使用 `auth:api` 中间件，需要认证。有两种测试方式：

### 方式 1: 临时禁用认证（仅用于开发测试）

修改 `app/Modules/ApiAdmin/routes.php`，临时移除 `auth:api`：

```php
Route::group([
    'middleware' => ['api'], // 移除 'auth:api'
    'prefix' => 'admin/api',
    'namespace' => 'App\Modules\ApiAdmin\Controllers'
], function () {
    // ... routes
});
```

**⚠️ 警告：** 测试完成后务必恢复认证中间件！

### 方式 2: 使用有效的 API Token

1. 创建 API token（如果使用 Laravel Sanctum/Passport）
2. 在请求头中添加：`Authorization: Bearer YOUR_TOKEN`

---

## 快速测试步骤

### 1. 检查路由是否注册

```bash
php artisan route:list --path=admin/api
```

应该看到 13 个路由。

### 2. 启动 Laravel 服务器

```bash
php artisan serve
```

或使用 Laragon/XAMPP。

### 3. 测试各个端点

#### 测试 1: 获取产品列表

```bash
curl -X GET "http://localhost:8000/admin/api/products?limit=5" \
  -H "Accept: application/json"
```

**预期响应：**
```json
{
  "success": true,
  "data": [...],
  "pagination": {...}
}
```

#### 测试 2: 获取单个产品

```bash
# 先获取一个产品ID，然后测试
curl -X GET "http://localhost:8000/admin/api/products/1" \
  -H "Accept: application/json"
```

#### 测试 3: 创建产品

```bash
curl -X POST "http://localhost:8000/admin/api/products" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test Product",
    "slug": "test-product-'$(date +%s)'",
    "description": "Test description",
    "status": "1",
    "price": 100000,
    "sale": 80000,
    "sku": "TEST-'$(date +%s)'",
    "has_variants": 0,
    "stock_qty": 100
  }'
```

#### 测试 4: 更新产品

```bash
curl -X PUT "http://localhost:8000/admin/api/products/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "id": 1,
    "name": "Updated Product",
    "slug": "updated-product",
    "status": "1"
  }'
```

#### 测试 5: 更新产品状态

```bash
curl -X PATCH "http://localhost:8000/admin/api/products/1/status" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"status": "0"}'
```

#### 测试 6: 批量操作

```bash
curl -X POST "http://localhost:8000/admin/api/products/bulk-action" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "checklist": [1, 2, 3],
    "action": 1
  }'
```

#### 测试 7: 更新排序

```bash
curl -X PATCH "http://localhost:8000/admin/api/products/sort" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "sort": {
      "1": 10,
      "2": 20
    }
  }'
```

#### 测试 8: 获取变体列表

```bash
curl -X GET "http://localhost:8000/admin/api/products/1/variants" \
  -H "Accept: application/json"
```

#### 测试 9: 创建变体

```bash
curl -X POST "http://localhost:8000/admin/api/products/1/variants" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "sku": "VARIANT-'$(date +%s)'",
    "product_id": 1,
    "price": 120000,
    "sale": 100000,
    "stock": 50
  }'
```

#### 测试 10: 更新变体

```bash
curl -X PUT "http://localhost:8000/admin/api/products/1/variants/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "price": 130000
  }'
```

#### 测试 11: 删除变体

```bash
curl -X DELETE "http://localhost:8000/admin/api/products/1/variants/1" \
  -H "Accept: application/json"
```

#### 测试 12: 删除产品

```bash
curl -X DELETE "http://localhost:8000/admin/api/products/1" \
  -H "Accept: application/json"
```

---

## 使用 Postman 测试

### 导入 Postman Collection

1. 打开 Postman
2. 点击 Import
3. 创建新 Collection: "Product API"
4. 添加以下请求：

**Base URL 变量：**
- 变量名：`base_url`
- 初始值：`http://localhost:8000`

**请求示例：**

```
GET {{base_url}}/admin/api/products?limit=10
POST {{base_url}}/admin/api/products
PUT {{base_url}}/admin/api/products/1
DELETE {{base_url}}/admin/api/products/1
```

---

## 常见错误及解决方案

### 错误 1: 401 Unauthorized

**原因：** 需要 API 认证

**解决：**
1. 临时移除 `auth:api` 中间件（仅开发环境）
2. 或提供有效的 API token

### 错误 2: 404 Not Found

**原因：** 路由未注册或 URL 错误

**解决：**
```bash
php artisan route:clear
php artisan route:cache
php artisan route:list --path=admin/api
```

### 错误 3: 422 Validation Error

**原因：** 请求数据验证失败

**解决：** 检查请求数据是否符合要求：
- `name`: 必填，1-250字符
- `slug`: 必填，唯一，格式：小写字母、数字、连字符
- `price`: 数字，>= 0
- 等等...

### 错误 4: 500 Internal Server Error

**原因：** 服务器内部错误

**解决：**
1. 检查 Laravel 日志：`storage/logs/laravel.log`
2. 确保数据库连接正常
3. 检查所有依赖服务

---

## 测试检查清单

### 产品管理 API (8个端点)

- [ ] GET /admin/api/products - 列表
- [ ] GET /admin/api/products/{id} - 详情
- [ ] POST /admin/api/products - 创建
- [ ] PUT /admin/api/products/{id} - 更新
- [ ] DELETE /admin/api/products/{id} - 删除
- [ ] PATCH /admin/api/products/{id}/status - 更新状态
- [ ] POST /admin/api/products/bulk-action - 批量操作
- [ ] PATCH /admin/api/products/sort - 更新排序

### 变体管理 API (5个端点)

- [ ] GET /admin/api/products/{id}/variants - 变体列表
- [ ] GET /admin/api/products/{id}/variants/{code} - 变体详情
- [ ] POST /admin/api/products/{id}/variants - 创建变体
- [ ] PUT /admin/api/products/{id}/variants/{code} - 更新变体
- [ ] DELETE /admin/api/products/{id}/variants/{code} - 删除变体

---

## 自动化测试脚本

运行提供的测试脚本：

```bash
php test_api_endpoints.php
```

**注意：** 需要先配置 API token 或临时禁用认证。

---

**最后更新：** 2024-01-XX
