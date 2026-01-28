@echo off
setlocal enabledelayedexpansion

REM Phase 2 data preparation (Warehouse V2 / Inventory V2)
REM - Non-destructive by default
REM - Requires: PHP + Composer deps installed, DB configured

cd /d %~dp0\..

echo.
echo == Phase 2: Prepare data (Warehouse V2) ==
echo.

REM 1) Run migrations
echo [1/4] Running migrations...
php artisan migrate --force
if errorlevel 1 (
  echo ERROR: migrate failed
  exit /b 1
)

REM 2) Try migrate legacy data (safe guard inside command)
echo.
echo [2/4] Try migrate legacy warehouse data -> V2 (will skip if data exists)...
php artisan inventory:migrate-legacy-data
echo NOTE: If it says data already exists, that's OK.

REM 3) Ensure inventory_stocks rows exist for all variants (non-interactive)
echo.
echo [3/4] Sync inventory_stocks rows for all variants (force)...
php artisan inventory:sync-stocks --force
if errorlevel 1 (
  echo ERROR: inventory:sync-stocks failed
  exit /b 1
)

REM 4) Quick sanity checks
echo.
echo [4/4] Sanity checks...
php artisan tinker --execute="echo 'warehouses_v2='.\App\Models\WarehouseV2::count().PHP_EOL; echo 'inventory_stocks='.\App\Models\InventoryStock::count().PHP_EOL;"

echo.
echo DONE: Phase 2 data preparation completed.
echo.
endlocal


