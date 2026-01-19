# 促销活动库存验证升级计划

## 📋 项目概述

**目标：** 确保库存为0的产品无法参与任何促销活动（FlashSale、Deal、MarketingCampaign）

**影响范围：** 
- FlashSale（闪购活动）
- Deal（交易活动）
- MarketingCampaign（营销活动）

---

## 🔍 深度分析结果

### 1. 现有促销活动系统架构

#### 1.1 FlashSale（闪购）
- **模型位置：** `app/Modules/FlashSale/Models/FlashSale.php`
- **产品关联：** `app/Modules/FlashSale/Models/ProductSale.php`
- **API控制器：** `app/Modules/ApiAdmin/Controllers/FlashSaleController.php`
- **关键字段：**
  - `productsales` 表：`product_id`, `variant_id`, `price_sale`, `number`, `buy`
- **当前验证：** ✅ 验证variant是否属于product，❌ **未验证库存**

#### 1.2 Deal（交易）
- **模型位置：** `app/Modules/Deal/Models/Deal.php`
- **产品关联：** `app/Modules/Deal/Models/ProductDeal.php`, `SaleDeal`
- **API控制器：** `app/Modules/ApiAdmin/Controllers/DealController.php`
- **关键字段：**
  - `deal_products` 表：`product_id`, `variant_id`, `status`
  - `sale_deals` 表：`product_id`, `variant_id`, `price`, `qty`, `status`
- **当前验证：** ✅ 验证variant是否属于product，✅ 检查产品冲突，❌ **未验证库存**

#### 1.3 MarketingCampaign（营销活动）
- **模型位置：** `app/Modules/Marketing/Models/MarketingCampaign.php`
- **产品关联：** `app/Modules/Marketing/Models/MarketingCampaignProduct.php`
- **控制器：** `app/Modules/Marketing/Controllers/MarketingCampaignController.php`
- **关键字段：**
  - `marketing_campaign_products` 表：`product_id`, `price`, `limit`
- **管理方式：** Web界面（非API），通过 `store()` 和 `update()` 方法管理
- **当前验证：** ✅ 检查产品时间重叠，❌ **未验证库存**

### 2. 库存管理系统分析

#### 2.1 库存存储位置
- **Product模型：** `posts` 表的 `stock` 字段（用于无variant的产品）
- **Variant模型：** `variants` 表的 `stock` 字段（用于有variant的产品）

#### 2.2 实际库存计算
- **服务类：** `app/Services/Warehouse/WarehouseService.php`
- **方法：** `getVariantStock(int $variantId): array`
- **计算逻辑：**
  ```php
  $importTotal = countProduct($variantId, 'import');
  $exportTotal = countProduct($variantId, 'export');
  $currentStock = max(0, $importTotal - $exportTotal);
  ```
- **辅助函数：** `countProduct($variantId, $type)` - 位于 `app/Modules/Warehouse/Helpers/helper.php`，计算导入/导出总量

#### 2.3 库存获取方式
- **有variant的产品：** 使用 `WarehouseService::getVariantStock($variantId)` 获取 `current_stock`
- **无variant的产品：** 使用 `Product::stock` 字段（但建议也通过warehouse系统获取）

---

## 📝 升级计划

### 阶段1：创建库存验证服务类

#### 1.1 创建 `ProductStockValidator` Service
**文件路径：** `app/Services/Promotion/ProductStockValidator.php`

**职责：**
- 验证产品/变体的库存是否大于0
- 支持批量验证
- 返回详细的验证错误信息

**方法签名：**
```php
public function validateProductStock(int $productId, ?int $variantId = null): bool
public function validateProductsStock(array $products): array // 返回验证错误列表
public function getProductStock(int $productId, ?int $variantId = null): int
```

---

### 阶段2：更新FlashSale API

#### 2.1 修改 `FlashSaleController::store()`
**文件：** `app/Modules/ApiAdmin/Controllers/FlashSaleController.php`
**位置：** 约第148-238行

**修改点：**
1. 在创建ProductSale之前，验证每个产品的库存
2. 如果库存为0，返回422错误，包含具体产品信息

**验证逻辑：**
```php
// 在添加产品循环中（约第187-212行）
foreach ($request->products as $index => $productData) {
    // ... 现有variant验证 ...
    
    // 新增：库存验证
    $stock = $this->productStockValidator->getProductStock(
        $productData['product_id'],
        $productData['variant_id'] ?? null
    );
    
    if ($stock <= 0) {
        return response()->json([
            'success' => false,
            'message' => "Sản phẩm ID {$productData['product_id']}" . 
                        ($productData['variant_id'] ? " (Variant ID {$productData['variant_id']})" : '') . 
                        " không có tồn kho, không thể tham gia Flash Sale",
            'errors' => [
                "products.{$index}.stock" => ["Tồn kho phải lớn hơn 0"]
            ]
        ], 422);
    }
}
```

#### 2.2 修改 `FlashSaleController::update()`
**文件：** `app/Modules/ApiAdmin/Controllers/FlashSaleController.php`
**位置：** 约第249-392行

**修改点：**
- 在更新/创建ProductSale时，同样验证库存
- 如果库存为0，拒绝更新

---

### 阶段3：更新Deal API

#### 3.1 修改 `DealController::store()`
**文件：** `app/Modules/ApiAdmin/Controllers/DealController.php`
**位置：** 约第151-289行

**修改点：**
1. 验证 `products` 数组中的产品库存
2. 验证 `sale_products` 数组中的产品库存
3. 在 `validateProductsAndVariants()` 方法后添加库存验证

**验证逻辑：**
```php
// 在validateProductsAndVariants()之后（约第200行）
// 验证库存
$stockErrors = $this->productStockValidator->validateProductsStock(
    array_merge(
        $request->get('products', []),
        $request->get('sale_products', [])
    )
);

if (!empty($stockErrors)) {
    return response()->json([
        'success' => false,
        'message' => 'Một số sản phẩm không có tồn kho, không thể tham gia Deal',
        'errors' => $stockErrors
    ], 422);
}
```

#### 3.2 修改 `DealController::update()`
**文件：** `app/Modules/ApiAdmin/Controllers/DealController.php`
**位置：** 约第300-460行

**修改点：**
- 在更新产品时验证库存
- 如果库存为0，拒绝更新

---

### 阶段4：更新MarketingCampaign（Web界面）

#### 4.1 修改 `MarketingCampaignController::store()`
**文件：** `app/Modules/Marketing/Controllers/MarketingCampaignController.php`
**位置：** 约第44-112行

**修改点：**
- 在创建 `MarketingCampaignProduct` 之前（约第92行），验证产品库存
- 如果库存为0，跳过该产品并记录日志

**验证逻辑：**
```php
// 在创建MarketingCampaignProduct之前（约第87-98行）
foreach ($pricesale as $productId => $price) {
    if ($this->checkProductOverlap($productId, $start, $end)) {
        continue; 
    }
    
    // 新增：库存验证
    $product = Product::find($productId);
    if ($product) {
        $stock = $this->productStockValidator->getProductStock($productId);
        if ($stock <= 0) {
            // 记录日志但不阻止整个操作
            Log::warning("Product ID {$productId} has no stock, skipped from MarketingCampaign", [
                'product_id' => $productId,
                'campaign_id' => $id
            ]);
            continue; // 跳过该产品
        }
    }

    MarketingCampaignProduct::create([
        'campaign_id' => $id,
        'product_id' => $productId,
        'price' => ($price != "") ? str_replace(',', '', $price) : 0,
        'limit' => 0
    ]);
}
```

#### 4.2 修改 `MarketingCampaignController::update()`
**文件：** `app/Modules/Marketing/Controllers/MarketingCampaignController.php`
**位置：** 约第128-249行

**修改点：**
- 在创建新的 `MarketingCampaignProduct` 时（约第218行），验证库存
- 如果库存为0，跳过该产品

---

### 阶段5：创建辅助方法

#### 5.1 在 `ProductStockValidator` 中实现库存获取逻辑

**实现细节：**
```php
public function getProductStock(int $productId, ?int $variantId = null): int
{
    if ($variantId) {
        // 有variant：从warehouse系统获取
        $warehouseService = app(WarehouseServiceInterface::class);
        $stockInfo = $warehouseService->getVariantStock($variantId);
        return (int) ($stockInfo['current_stock'] ?? 0);
    } else {
        // 无variant：检查product的stock字段
        $product = Product::find($productId);
        if (!$product) {
            return 0;
        }
        
        // 如果有默认variant，从warehouse获取
        if ($product->has_variants == 1) {
            $defaultVariant = $product->variant($productId);
            if ($defaultVariant) {
                $warehouseService = app(WarehouseServiceInterface::class);
                $stockInfo = $warehouseService->getVariantStock($defaultVariant->id);
                return (int) ($stockInfo['current_stock'] ?? 0);
            }
        }
        
        // 否则使用product的stock字段
        return (int) ($product->stock ?? 0);
    }
}
```

---

## 🔧 技术实现细节

### 依赖注入
在Controller构造函数中注入 `ProductStockValidator`：
```php
protected ProductStockValidator $productStockValidator;

public function __construct(ProductStockValidator $productStockValidator)
{
    $this->productStockValidator = $productStockValidator;
}
```

### 错误响应格式
统一使用以下格式：
```json
{
    "success": false,
    "message": "Sản phẩm không có tồn kho, không thể tham gia [促销类型]",
    "errors": {
        "products.0.stock": ["Tồn kho phải lớn hơn 0"],
        "products.1.stock": ["Tồn kho phải lớn hơn 0"]
    }
}
```

---

## 📊 影响评估

### 正面影响
- ✅ 防止无库存产品参与促销，避免用户下单后无法发货
- ✅ 提升用户体验，减少订单取消
- ✅ 保护商家利益，避免超卖问题

### 潜在风险
- ⚠️ 如果库存系统有延迟，可能误判
- ⚠️ 需要确保warehouse系统稳定可用

### 兼容性
- ✅ 向后兼容：不影响现有已创建的促销活动
- ✅ 仅影响新创建/更新的促销活动

---

## ✅ 测试计划

### 单元测试
1. 测试 `ProductStockValidator::getProductStock()` 方法
   - 有variant的产品
   - 无variant的产品
   - 不存在的产品

2. 测试 `ProductStockValidator::validateProductStock()` 方法
   - 库存 > 0：返回true
   - 库存 = 0：返回false
   - 库存 < 0：返回false

### 集成测试
1. 测试FlashSale创建API
   - 库存 > 0：成功创建
   - 库存 = 0：返回422错误

2. 测试Deal创建API
   - 库存 > 0：成功创建
   - 库存 = 0：返回422错误

3. 测试更新API
   - 更新时库存变为0：拒绝更新

---

## 📚 API文档更新

### FlashSale API
**POST /admin/api/flash-sales**
- 新增验证：产品库存必须 > 0
- 错误码：422（库存为0时）

**PUT /admin/api/flash-sales/{id}**
- 新增验证：更新产品时库存必须 > 0

### Deal API
**POST /admin/api/deals**
- 新增验证：products和sale_products中的产品库存必须 > 0

**PUT /admin/api/deals/{id}**
- 新增验证：更新产品时库存必须 > 0

---

## 🚀 实施步骤

1. ✅ **完成深度分析**（当前阶段）
2. ⏳ **创建ProductStockValidator服务类**
3. ⏳ **更新FlashSaleController**
4. ⏳ **更新DealController**
5. ⏳ **检查并更新MarketingCampaign（如需要）**
6. ⏳ **编写单元测试**
7. ⏳ **更新API文档（API_ADMIN_DOCS.md）**
8. ⏳ **代码审查和测试**

---

## 📝 注意事项

1. **性能考虑：** 批量验证时，考虑使用批量查询优化性能
2. **缓存：** 如果库存数据更新频繁，考虑缓存策略
3. **日志：** 记录库存验证失败的日志，便于排查问题
4. **用户体验：** 错误消息要清晰，指明具体哪个产品库存不足

---

**创建时间：** 2024-12-19
**状态：** 计划阶段
