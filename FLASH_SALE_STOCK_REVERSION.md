# Flash Sale Stock Reversion Documentation

## Overview

This module handles automatic stock reversion from Flash Sale campaigns back to Warehouse V2 when:
- Flash Sale ends (expires)
- Flash Sale is deleted
- ProductSale quantity is decreased
- ProductSale is deleted

## Architecture

### 1. FlashSaleStockService (`app/Services/FlashSale/FlashSaleStockService.php`)

Main service that handles stock reversion logic:

- **`revertStock($productSale)`**: Reverts remaining stock (number - buy) for a single ProductSale
- **`revertStockForCampaign($flashSale)`**: Reverts stock for all ProductSales in a FlashSale campaign
- **`handleQuantityChange($productSale, $oldQuantity)`**: Releases stock when ProductSale quantity is decreased

### 2. Observers

#### ProductSaleObserver (`app/Modules/FlashSale/Observers/ProductSaleObserver.php`)
- **`deleting()`**: Automatically reverts stock before ProductSale deletion
- **`updating()`**: Releases stock when quantity decreases

#### FlashSaleObserver (`app/Modules/FlashSale/Observers/FlashSaleObserver.php`)
- **`deleting()`**: Reverts stock for all ProductSales before FlashSale campaign deletion

### 3. Cronjob Command

**ExpireFlashSales** (`app/Console/Commands/ExpireFlashSales.php`)

Automatically processes expired Flash Sales:
- Finds Flash Sales with `end < now()` and `status = 1`
- Releases remaining stock for each ProductSale
- Updates Flash Sale status to inactive (0)

**Usage:**
```bash
# Dry run (test without making changes)
php artisan flashsale:expire --dry-run

# Force expire (even if status is not active)
php artisan flashsale:expire --force

# Normal run
php artisan flashsale:expire
```

**Scheduled:** Runs every hour via `app/Console/Kernel.php`

## How It Works

### Stock Allocation (When Flash Sale is Created/Updated)

When a Flash Sale is created or ProductSale quantity is set:
1. `InventoryService::allocateStockForPromotion()` is called
2. Stock is moved from `available_stock` to `flash_sale_hold`
3. `physical_stock` remains unchanged

### Stock Reversion (When Flash Sale Ends/Deleted)

When stock needs to be reverted:
1. Calculate remaining quantity: `remaining = number - buy`
2. Call `InventoryService::releaseStockFromPromotion()`
3. Stock is moved from `flash_sale_hold` back to `available_stock`
4. `physical_stock` remains unchanged

### Example Flow

```
Initial State:
- physical_stock: 100
- flash_sale_hold: 0
- available_stock: 100

After Flash Sale Created (quantity = 50):
- physical_stock: 100 (unchanged)
- flash_sale_hold: 50
- available_stock: 50 (100 - 50)

After 30 items sold:
- physical_stock: 70 (reduced by sales)
- flash_sale_hold: 50 (unchanged)
- available_stock: 20 (70 - 50)

After Flash Sale Expires (revert remaining 20):
- physical_stock: 70 (unchanged)
- flash_sale_hold: 0 (50 - 30 sold = 20 reverted)
- available_stock: 70 (70 - 0)
```

## Database Tables

### inventory_stocks (Warehouse V2)
- `physical_stock`: Actual physical inventory (only changes with import/export)
- `flash_sale_hold`: Stock reserved for Flash Sale
- `available_stock`: Calculated as `physical_stock - reserved_stock - flash_sale_hold - deal_hold`
- `deal_hold`: Stock reserved for Deal campaigns

### productsales (Flash Sale)
- `number`: Total quantity allocated for Flash Sale
- `buy`: Quantity already sold
- `variant_id`: Links to Warehouse V2 variant

## Important Notes

1. **Physical Stock Never Changes**: Stock reversion only affects `flash_sale_hold` and `available_stock`, never `physical_stock`

2. **Variant Required**: Stock reversion only works for ProductSales with `variant_id`. ProductSales without variants are skipped with a warning log.

3. **Transaction Safety**: All operations use `DB::transaction()` to ensure data consistency

4. **Error Handling**: If stock reversion fails, it's logged but doesn't prevent deletion/update. The cronjob will handle it later.

5. **Cronjob Safety**: The cronjob uses `withoutOverlapping()` to prevent concurrent executions

## Testing

### Manual Testing

1. **Test ProductSale Deletion:**
```php
$productSale = ProductSale::find($id);
$productSale->delete(); // Observer will automatically revert stock
```

2. **Test Quantity Decrease:**
```php
$productSale = ProductSale::find($id);
$oldQuantity = $productSale->number;
$productSale->number = $oldQuantity - 10; // Decrease by 10
$productSale->save(); // Observer will release 10 units
```

3. **Test Flash Sale Deletion:**
```php
$flashSale = FlashSale::with('products')->find($id);
$flashSale->delete(); // Observer will revert stock for all products
```

4. **Test Expiration:**
```bash
php artisan flashsale:expire --dry-run  # Preview
php artisan flashsale:expire            # Execute
```

## Monitoring

Check logs for:
- `[FlashSaleStockService]`: Stock reversion operations
- `[ProductSaleObserver]`: Observer events
- `[FlashSaleObserver]`: Campaign deletion events
- `[ExpireFlashSales]`: Cronjob execution

## Troubleshooting

### Stock Not Reverting

1. Check if ProductSale has `variant_id`
2. Check logs for errors
3. Verify InventoryService is working correctly
4. Run cronjob manually: `php artisan flashsale:expire`

### Duplicate Reversion

- Observers and cronjob both handle expiration
- Observers handle immediate deletion/update
- Cronjob handles missed expirations
- Both are idempotent (safe to run multiple times)


