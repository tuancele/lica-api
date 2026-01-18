# Product API Admin 实现计划

基于 `API_ADMIN_DOCS.md` 的详细代码实现计划

## 一、项目结构

### 1.1 需要创建的目录结构

```
app/Modules/ApiAdmin/
├── Controllers/
│   └── ProductController.php
└── routes.php
```

### 1.2 需要创建/修改的文件清单

**新建文件：**
1. `app/Modules/ApiAdmin/Controllers/ProductController.php` - 产品管理API控制器
2. `app/Modules/ApiAdmin/routes.php` - API路由配置
3. `app/Http/Requests/Product/StoreVariantRequest.php` - 变体创建请求验证
4. `app/Http/Requests/Product/UpdateVariantRequest.php` - 变体更新请求验证

**修改文件：**
1. `app/Http/Resources/Product/VariantResource.php` - 扩展变体资源类（添加缺失字段）

**文档更新：**
1. `API_ADMIN_DOCS.md` - 更新API状态为"已完成"

---

## 二、详细实现步骤

### 步骤 1: 创建 ApiAdmin 模块目录结构

**操作：** 创建目录
- `app/Modules/ApiAdmin/Controllers/`

---

### 步骤 2: 创建变体请求验证类

#### 2.1 StoreVariantRequest.php

**位置：** `app/Http/Requests/Product/StoreVariantRequest.php`

**功能：** 验证创建变体的请求数据

**验证规则：**
- `sku` (required, string, max:100, unique:variants,sku)
- `product_id` (required, integer, exists:posts,id)
- `option1_value` (nullable, string, max:255)
- `image` (nullable, url, max:500)
- `size_id` (nullable, integer, exists:sizes,id)
- `color_id` (nullable, integer, exists:colors,id)
- `weight` (nullable, numeric, min:0)
- `price` (required, numeric, min:0)
- `sale` (nullable, numeric, min:0)
- `stock` (nullable, integer, min:0)
- `position` (nullable, integer, min:0)

**关键代码结构：**
```php
<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Product\Models\Product;

class StoreVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:100', 'unique:variants,sku'],
            'product_id' => ['required', 'integer', 'exists:posts,id'],
            'option1_value' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'url', 'max:500'],
            'size_id' => ['nullable', 'integer', 'exists:sizes,id'],
            'color_id' => ['nullable', 'integer', 'exists:colors,id'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'SKU không được bỏ trống',
            'sku.unique' => 'SKU đã tồn tại',
            'product_id.exists' => 'Sản phẩm không tồn tại',
            'price.required' => 'Giá không được bỏ trống',
            'price.numeric' => 'Giá phải là số',
        ];
    }
}
```

#### 2.2 UpdateVariantRequest.php

**位置：** `app/Http/Requests/Product/UpdateVariantRequest.php`

**功能：** 验证更新变体的请求数据

**验证规则：** 与 StoreVariantRequest 类似，但：
- `sku` 需要排除当前变体ID：`unique:variants,sku,{variant_id}`
- `product_id` 可选（从URL获取）
- 所有字段都是可选的（除了需要更新的字段）

---

### 步骤 3: 扩展 VariantResource

**位置：** `app/Http/Resources/Product/VariantResource.php`

**需要添加的字段：**
- `product_id`
- `option1_value`
- `size_id`, `color_id`
- `stock`
- `position`
- `created_at`, `updated_at`

**修改后的 toArray 方法应包含：**
```php
public function toArray($request): array
{
    return [
        'id' => $this->id,
        'sku' => $this->sku,
        'product_id' => $this->product_id,
        'option1_value' => $this->option1_value,
        'image' => $this->image,
        'size_id' => $this->size_id,
        'color_id' => $this->color_id,
        'weight' => (float) $this->weight,
        'price' => (float) $this->price,
        'sale' => (float) $this->sale,
        'stock' => (int) $this->stock,
        'position' => (int) $this->position,
        'color' => new ColorResource($this->whenLoaded('color')),
        'size' => new SizeResource($this->whenLoaded('size')),
        'created_at' => $this->created_at?->toISOString(),
        'updated_at' => $this->updated_at?->toISOString(),
    ];
}
```

---

### 步骤 4: 创建 ProductController

**位置：** `app/Modules/ApiAdmin/Controllers/ProductController.php`

**命名空间：** `App\Modules\ApiAdmin\Controllers`

**依赖注入：**
- `ProductServiceInterface` - 产品业务逻辑服务
- 使用现有的 `ProductService` 实现

**需要实现的方法：**

#### 4.1 index() - GET /admin/api/products

**功能：** 获取产品列表（分页+过滤）

**查询参数：**
- `page` (int, default: 1)
- `limit` (int, default: 10)
- `status` (string: '0'|'1')
- `cat_id` (int)
- `keyword` (string)
- `feature` (string: '0'|'1')
- `best` (string: '0'|'1')

**实现逻辑：**
1. 从请求获取过滤参数
2. 调用 `ProductService::getProducts($filters, $perPage)`
3. 使用 `ProductCollection` 格式化响应
4. 返回统一JSON格式

**响应格式：**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 100,
    "last_page": 10
  }
}
```

#### 4.2 show($id) - GET /admin/api/products/{id}

**功能：** 获取单个产品详情

**实现逻辑：**
1. 调用 `ProductService::getProductWithRelations($id)`
2. 使用 `ProductResource` 格式化响应
3. 处理 `ProductNotFoundException` 异常

**响应格式：**
```json
{
  "success": true,
  "data": {...}
}
```

#### 4.3 store(StoreProductRequest $request) - POST /admin/api/products

**功能：** 创建新产品

**实现逻辑：**
1. 使用 `StoreProductRequest` 验证请求
2. 调用 `ProductService::createProduct($request->validated())`
3. 使用 `ProductResource` 格式化响应
4. 处理 `ProductCreationException` 异常

**响应格式：**
```json
{
  "success": true,
  "message": "产品创建成功",
  "data": {...}
}
```

#### 4.4 update(UpdateProductRequest $request, $id) - PUT /admin/api/products/{id}

**功能：** 更新现有产品

**实现逻辑：**
1. 合并URL参数 `id` 到请求数据
2. 使用 `UpdateProductRequest` 验证请求
3. 调用 `ProductService::updateProduct($id, $request->validated())`
4. 使用 `ProductResource` 格式化响应
5. 处理 `ProductNotFoundException` 和 `ProductUpdateException` 异常

#### 4.5 destroy($id) - DELETE /admin/api/products/{id}

**功能：** 删除产品

**实现逻辑：**
1. 调用 `ProductService::deleteProduct($id)`
2. 处理 `ProductNotFoundException` 和 `ProductDeletionException` 异常

**响应格式：**
```json
{
  "success": true,
  "message": "产品删除成功"
}
```

#### 4.6 updateStatus(Request $request, $id) - PATCH /admin/api/products/{id}/status

**功能：** 更新产品状态

**实现逻辑：**
1. 验证 `status` 参数 (0|1)
2. 直接更新数据库（或通过Service）
3. 返回成功响应

**请求体：**
```json
{
  "status": 1
}
```

#### 4.7 bulkAction(Request $request) - POST /admin/api/products/bulk-action

**功能：** 批量操作产品

**实现逻辑：**
1. 验证 `checklist` (array) 和 `action` (0|1|2)
2. 根据 action 执行：
   - 0: 批量隐藏 (status = 0)
   - 1: 批量显示 (status = 1)
   - 2: 批量删除（调用 `ProductService::deleteProduct` 循环）
3. 返回受影响数量

**请求体：**
```json
{
  "checklist": [1, 2, 3],
  "action": 1
}
```

#### 4.8 updateSort(Request $request) - PATCH /admin/api/products/sort

**功能：** 更新产品排序

**实现逻辑：**
1. 验证 `sort` 对象（产品ID => 排序值）
2. 循环更新每个产品的 `sort` 字段
3. 返回成功响应

**请求体：**
```json
{
  "sort": {
    "1": 10,
    "2": 20,
    "3": 30
  }
}
```

---

### 步骤 5: 实现变体管理方法

#### 5.1 getVariants($id) - GET /admin/api/products/{id}/variants

**功能：** 获取产品的所有变体

**实现逻辑：**
1. 验证产品存在
2. 加载变体关系（包含 color, size）
3. 使用 `VariantResource::collection()` 格式化响应

#### 5.2 getVariant($id, $code) - GET /admin/api/products/{id}/variants/{code}

**功能：** 获取单个变体详情

**实现逻辑：**
1. 验证产品和变体存在
2. 验证变体属于该产品
3. 使用 `VariantResource` 格式化响应

#### 5.3 createVariant(StoreVariantRequest $request, $id) - POST /admin/api/products/{id}/variants

**功能：** 创建新变体

**实现逻辑：**
1. 验证产品存在
2. 合并 `product_id` 到请求数据
3. 使用 `StoreVariantRequest` 验证
4. 创建变体记录
5. 检查订单关联（如有订单则不允许创建？根据业务需求）
6. 使用 `VariantResource` 格式化响应

#### 5.4 updateVariant(UpdateVariantRequest $request, $id, $code) - PUT /admin/api/products/{id}/variants/{code}

**功能：** 更新变体

**实现逻辑：**
1. 验证产品和变体存在
2. 验证变体属于该产品
3. 合并 `product_id` 和 `id` 到请求数据
4. 使用 `UpdateVariantRequest` 验证（排除当前变体ID的SKU唯一性）
5. 更新变体记录
6. 使用 `VariantResource` 格式化响应

#### 5.5 deleteVariant($id, $code) - DELETE /admin/api/products/{id}/variants/{code}

**功能：** 删除变体

**实现逻辑：**
1. 验证产品和变体存在
2. 验证变体属于该产品
3. 检查订单关联（`OrderDetail::where('product_id', $code)->exists()`）
4. 如有订单则返回错误
5. 删除变体记录
6. 返回成功响应

---

### 步骤 6: 创建路由配置

**位置：** `app/Modules/ApiAdmin/routes.php`

**路由结构：**
```php
<?php

Route::group(['middleware' => ['api', 'auth:api'], 'prefix' => 'admin/api', 'namespace' => 'App\Modules\ApiAdmin\Controllers'], function () {
    
    // Product Management Routes
    Route::prefix('products')->group(function () {
        // List products
        Route::get('/', 'ProductController@index');
        
        // Single product operations
        Route::get('/{id}', 'ProductController@show');
        Route::post('/', 'ProductController@store');
        Route::put('/{id}', 'ProductController@update');
        Route::delete('/{id}', 'ProductController@destroy');
        
        // Status update
        Route::patch('/{id}/status', 'ProductController@updateStatus');
        
        // Bulk operations
        Route::post('/bulk-action', 'ProductController@bulkAction');
        
        // Sort update
        Route::patch('/sort', 'ProductController@updateSort');
        
        // Variant management (nested routes)
        Route::prefix('{id}/variants')->group(function () {
            Route::get('/', 'ProductController@getVariants');
            Route::get('/{code}', 'ProductController@getVariant');
            Route::post('/', 'ProductController@createVariant');
            Route::put('/{code}', 'ProductController@updateVariant');
            Route::delete('/{code}', 'ProductController@deleteVariant');
        });
    });
});
```

**注意：**
- 使用 `api` 中间件组（不是 `web`）
- 使用 `auth:api` 进行API认证
- 路由前缀：`admin/api`
- 命名空间：`App\Modules\ApiAdmin\Controllers`

---

## 三、错误处理规范

### 3.1 统一错误响应格式

```json
{
  "success": false,
  "message": "错误消息",
  "error": "详细错误信息（仅开发环境）"
}
```

### 3.2 HTTP状态码

- `200` - 成功
- `201` - 创建成功
- `400` - 请求错误（验证失败、业务逻辑错误）
- `404` - 资源不存在
- `500` - 服务器内部错误

### 3.3 异常处理

**捕获的异常：**
- `ProductNotFoundException` → 404
- `ProductCreationException` → 400/500
- `ProductUpdateException` → 400/500
- `ProductDeletionException` → 400
- `ValidationException` → 400（Laravel自动处理）
- `\Exception` → 500（通用异常）

**实现模式：**
```php
try {
    // 业务逻辑
    return response()->json([
        'success' => true,
        'data' => $data
    ], 200);
} catch (ProductNotFoundException $e) {
    return response()->json([
        'success' => false,
        'message' => '产品不存在'
    ], 404);
} catch (\Exception $e) {
    Log::error('API Error: ' . $e->getMessage(), [
        'method' => __METHOD__,
        'trace' => $e->getTraceAsString()
    ]);
    
    return response()->json([
        'success' => false,
        'message' => '操作失败',
        'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
    ], 500);
}
```

---

## 四、代码质量要求

### 4.1 Type Hinting

所有方法必须使用 PHP 8.2+ 类型提示：
- 参数类型
- 返回类型（`: JsonResponse`, `: Product`, 等）

### 4.2 代码注释

- 类和方法使用英文注释
- 业务逻辑使用英文注释
- 用户可见消息使用越南语

### 4.3 代码复用

- 复用现有的 `ProductService` 处理业务逻辑
- 复用现有的 `ProductResource` 和 `VariantResource`
- 复用现有的 `StoreProductRequest` 和 `UpdateProductRequest`

### 4.4 最小干预原则

- 不修改现有的 `ProductController`（管理后台）
- 不修改现有的 `ProductService`
- 不修改现有的模型和资源类（除非必要扩展）

---

## 五、测试要点

### 5.1 功能测试

1. **产品列表：**
   - 分页功能
   - 过滤功能（status, cat_id, keyword, feature, best）
   - 空结果处理

2. **产品详情：**
   - 存在产品
   - 不存在产品（404）
   - 关联数据加载（brand, origin, variants, categories）

3. **创建产品：**
   - 成功创建
   - 验证失败
   - SKU重复
   - Slug重复

4. **更新产品：**
   - 成功更新
   - 产品不存在（404）
   - Slug变更（重定向创建）

5. **删除产品：**
   - 成功删除
   - 有订单的产品（不允许删除）
   - 产品不存在（404）

6. **批量操作：**
   - 批量隐藏/显示
   - 批量删除
   - 空列表处理

7. **变体管理：**
   - 创建/更新/删除变体
   - SKU唯一性验证
   - 订单检查

### 5.2 边界情况

- 空数据
- 无效ID
- 无效参数类型
- 超长字符串
- 负数/零值
- SQL注入防护（使用Eloquent，已自动防护）

---

## 六、实现顺序建议

1. **第一阶段：基础结构**
   - 创建目录结构
   - 创建路由文件
   - 创建控制器骨架

2. **第二阶段：产品CRUD**
   - 实现 index, show, store, update, destroy
   - 测试基本功能

3. **第三阶段：扩展功能**
   - 实现 updateStatus, bulkAction, updateSort
   - 测试批量操作

4. **第四阶段：变体管理**
   - 创建变体请求验证类
   - 扩展 VariantResource
   - 实现变体管理方法
   - 测试变体功能

5. **第五阶段：完善和文档**
   - 完善错误处理
   - 添加日志记录
   - 更新 API_ADMIN_DOCS.md

---

## 七、依赖关系图

```
ProductController
    ├── ProductService (业务逻辑)
    │   ├── ProductRepository (数据访问)
    │   └── ImageService (图片处理)
    ├── StoreProductRequest (验证)
    ├── UpdateProductRequest (验证)
    ├── StoreVariantRequest (验证)
    ├── UpdateVariantRequest (验证)
    ├── ProductResource (响应格式化)
    ├── VariantResource (响应格式化)
    └── ProductCollection (列表响应格式化)
```

---

## 八、注意事项

1. **认证授权：**
   - 当前使用 `auth:api` 中间件
   - 后续可能需要添加权限检查（Policy）

2. **性能优化：**
   - 列表查询使用分页
   - 关联数据使用 `with()` 预加载
   - 考虑缓存热门数据

3. **安全性：**
   - 所有输入验证
   - SQL注入防护（Eloquent）
   - XSS防护（资源类自动转义）

4. **向后兼容：**
   - 保持现有管理后台功能不变
   - API不影响现有Web界面

---

**计划创建时间：** 2024-01-XX
**预计实现时间：** 2-3小时
**维护者：** AI Assistant
