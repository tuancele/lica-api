@echo off
REM Flash Sale API Deployment Script for Windows
REM Usage: deploy_flash_sale.bat [environment]
REM Example: deploy_flash_sale.bat production

setlocal enabledelayedexpansion

set ENVIRONMENT=%1
if "%ENVIRONMENT%"=="" set ENVIRONMENT=production

set TIMESTAMP=%date:~-4,4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set LOG_FILE=deploy_%TIMESTAMP%.log

echo [%date% %time%] Starting Flash Sale API deployment to %ENVIRONMENT% > %LOG_FILE%
echo [%date% %time%] Timestamp: %TIMESTAMP% >> %LOG_FILE%

REM Step 1: Pre-deployment checks
echo.
echo Step 1: Pre-deployment checks
echo [%date% %time%] Step 1: Pre-deployment checks >> %LOG_FILE%

if not exist "artisan" (
    echo ERROR: Laravel artisan file not found. Are you in the correct directory?
    echo [%date% %time%] ERROR: Laravel artisan file not found >> %LOG_FILE%
    exit /b 1
)

where php >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP is not installed or not in PATH
    echo [%date% %time%] ERROR: PHP is not installed >> %LOG_FILE%
    exit /b 1
)

where composer >nul 2>&1
if errorlevel 1 (
    echo ERROR: Composer is not installed or not in PATH
    echo [%date% %time%] ERROR: Composer is not installed >> %LOG_FILE%
    exit /b 1
)

echo [%date% %time%] Pre-deployment checks passed >> %LOG_FILE%
echo ✓ Pre-deployment checks passed

REM Step 2: Backup database (manual step - user should do this)
echo.
echo Step 2: Database backup
echo WARNING: Please backup your database manually before proceeding!
echo [%date% %time%] WARNING: Manual database backup required >> %LOG_FILE%
pause

REM Step 3: Enable maintenance mode
echo.
echo Step 3: Enabling maintenance mode
echo [%date% %time%] Step 3: Enabling maintenance mode >> %LOG_FILE%
php artisan down --message="Deploying Flash Sale API updates" --retry=60
if errorlevel 1 (
    echo WARNING: Failed to enable maintenance mode
    echo [%date% %time%] WARNING: Failed to enable maintenance mode >> %LOG_FILE%
)

REM Step 4: Pull latest code (if using git)
echo.
echo Step 4: Pulling latest code
echo [%date% %time%] Step 4: Pulling latest code >> %LOG_FILE%
if exist ".git" (
    git pull origin main
    if errorlevel 1 git pull origin master
    if errorlevel 1 (
        echo WARNING: Git pull failed
        echo [%date% %time%] WARNING: Git pull failed >> %LOG_FILE%
    )
) else (
    echo Skipping git pull (not a git repository)
    echo [%date% %time%] Skipping git pull >> %LOG_FILE%
)

REM Step 5: Install dependencies
echo.
echo Step 5: Installing dependencies
echo [%date% %time%] Step 5: Installing dependencies >> %LOG_FILE%
if "%ENVIRONMENT%"=="production" (
    composer install --no-dev --optimize-autoloader --no-interaction
) else (
    composer install --optimize-autoloader --no-interaction
)
if errorlevel 1 (
    echo ERROR: Composer install failed
    echo [%date% %time%] ERROR: Composer install failed >> %LOG_FILE%
    php artisan up
    exit /b 1
)

REM Step 6: Clear caches
echo.
echo Step 6: Clearing caches
echo [%date% %time%] Step 6: Clearing caches >> %LOG_FILE%
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

REM Step 7: Run migrations
echo.
echo Step 7: Running migrations
echo [%date% %time%] Step 7: Running migrations >> %LOG_FILE%
php artisan migrate --force
if errorlevel 1 (
    echo ERROR: Migration failed
    echo [%date% %time%] ERROR: Migration failed >> %LOG_FILE%
    php artisan up
    exit /b 1
)

REM Step 8: Optimize
echo.
echo Step 8: Optimizing application
echo [%date% %time%] Step 8: Optimizing application >> %LOG_FILE%
php artisan config:cache
php artisan route:cache
php artisan view:cache

REM Step 9: Verify routes
echo.
echo Step 9: Verifying routes
echo [%date% %time%] Step 9: Verifying routes >> %LOG_FILE%
php artisan route:list --path=api/v1/flash-sales
php artisan route:list --path=admin/api/flash-sales

REM Step 10: Disable maintenance mode
echo.
echo Step 10: Disabling maintenance mode
echo [%date% %time%] Step 10: Disabling maintenance mode >> %LOG_FILE%
php artisan up
if errorlevel 1 (
    echo ERROR: Failed to disable maintenance mode
    echo [%date% %time%] ERROR: Failed to disable maintenance mode >> %LOG_FILE%
    exit /b 1
)

REM Summary
echo.
echo ==========================================
echo Deployment completed successfully!
echo ==========================================
echo Timestamp: %TIMESTAMP%
echo Environment: %ENVIRONMENT%
echo Log file: %LOG_FILE%
echo.
echo Next steps:
echo 1. Test API endpoints
echo 2. Test Admin Panel
echo 3. Monitor logs: type storage\logs\laravel.log
echo.

echo [%date% %time%] Deployment completed successfully >> %LOG_FILE%
echo [%date% %time%] Next steps: Test API endpoints and Admin Panel >> %LOG_FILE%
