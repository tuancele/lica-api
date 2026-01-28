# Phase 2 - Data Preparation (Warehouse V2 / Inventory V2)

Scope: prepare data for Phase 2 capacity upgrade, focusing on stock consistency for Flash Sale, Deal, Cart/Checkout.

## Preconditions

- PHP 8.3+
- DB configured in `.env`
- Migrations are runnable
- Redis is optional for this data step

**Safety note:** Before any Phase 2 migration or data prep, ensure database has been backed up  
(`php artisan db:backup --path=storage/backups` or use `scripts\phase2-safe-migrate.bat` which runs backup + migrate).

## What this does

- Ensure `warehouses_v2` has a default warehouse (usually `code=MAIN`)
- Migrate legacy warehouse data from `warehouse` and `product_warehouse` to V2 tables (if not migrated yet)
- Ensure `inventory_stocks` has rows for all `variants` (missing rows will be created with zero values)

## Execute (Windows)

Run:

- `scripts\prepare-phase2-data.bat`

This will run:

- `php artisan migrate --force`
- `php artisan inventory:migrate-legacy-data` (skips if data already exists)
- `php artisan inventory:sync-stocks --force`

## Verify (SQL)

Run these queries:

- Default warehouse exists:

```sql
select id, code, name, is_default, is_active
from warehouses_v2
order by id asc;
```

- Inventory rows coverage (should be close to variants count for default warehouse):

```sql
select
  (select count(*) from variants) as variants_count,
  (select count(*) from inventory_stocks where warehouse_id = (select id from warehouses_v2 where is_default = 1 limit 1)) as inventory_rows_default_warehouse;
```

## Critical notes

- `inventory_stocks.available_stock` is a generated column. Do not update it directly.
- Flash Sale / Deal holds are stored in:
  - `inventory_stocks.flash_sale_hold`
  - `inventory_stocks.deal_hold`
- Default warehouse id is controlled by:
  - `config/inventory.php` -> `default_warehouse_id`
  - `.env` -> `INVENTORY_DEFAULT_WAREHOUSE`

If your default warehouse is NOT id=1, set `INVENTORY_DEFAULT_WAREHOUSE` accordingly.

Example `.env`:

```env
INVENTORY_DEFAULT_WAREHOUSE=1
```


