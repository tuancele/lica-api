@echo off
setlocal enabledelayedexpansion

REM Phase 2 safe migration for production-like environments
REM - Backup first
REM - Only runs additive migrations
REM - Does NOT reset/truncate data

cd /d %~dp0\..

echo.
echo == Phase 2: SAFE MIGRATE (backup -> migrate) ==
echo.

echo [1/2] Backup database...
php artisan db:backup --path=storage/backups
if errorlevel 1 (
  echo ERROR: db:backup failed
  exit /b 1
)

echo.
echo [2/2] Run migrations (force)...
php artisan migrate --force
if errorlevel 1 (
  echo ERROR: migrate failed
  exit /b 1
)

echo.
echo DONE: backup + migrate completed.
echo.
endlocal


