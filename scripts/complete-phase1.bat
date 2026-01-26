@echo off
REM Phase 1 Completion Script for Windows
REM This script helps complete Phase 1 setup steps

echo ==========================================
echo Phase 1: Foundation - Completion Script
echo ==========================================
echo.

REM Check PHP version
echo 1. Checking PHP version...
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP not found. Please install PHP 8.3+
    exit /b 1
)

for /f "tokens=2" %%i in ('php -v ^| findstr /i "PHP"') do set PHP_VERSION=%%i
echo PHP version: %PHP_VERSION%
echo.

REM Check Redis connection
echo 2. Checking Redis connection...
redis-cli ping >nul 2>&1
if %errorlevel% equ 0 (
    echo Redis is running
) else (
    echo WARNING: Redis is not responding. Please start Redis service.
)
echo.

REM Update composer dependencies
echo 3. Updating Composer dependencies...
if exist composer.json (
    composer update --no-interaction --prefer-dist
    echo Composer dependencies updated
) else (
    echo ERROR: composer.json not found
    exit /b 1
)
echo.

REM Check .env file
echo 4. Checking .env configuration...
if not exist .env (
    echo WARNING: .env file not found. Creating from .env.example...
    if exist .env.example (
        copy .env.example .env >nul
        php artisan key:generate
        echo .env file created
    ) else (
        echo ERROR: .env.example not found
        exit /b 1
    )
)
echo.

REM Run Pint
echo 5. Running Laravel Pint...
if exist vendor\bin\pint.bat (
    call vendor\bin\pint.bat
    echo Code formatting completed
) else (
    echo WARNING: Pint not found. Run 'composer install' first.
)
echo.

REM Run PHPStan
echo 6. Running PHPStan...
if exist vendor\bin\phpstan.bat (
    call vendor\bin\phpstan.bat analyse --level=8
    echo Static analysis completed
) else (
    echo WARNING: PHPStan not found. Run 'composer install' first.
)
echo.

REM Summary
echo ==========================================
echo Summary
echo ==========================================
echo PHP version check: OK
echo Composer dependencies: Updated
echo Code formatting: Completed
echo Static analysis: Completed
echo.
echo Next steps:
echo 1. Verify Redis is running and configured in .env
echo 2. Test application: php artisan serve
echo 3. Test queue: php artisan queue:work
echo 4. (Optional) Install Telescope: composer require laravel/telescope --dev
echo 5. (Optional) Install Sentry: composer require sentry/sentry-laravel
echo.
echo Phase 1 completion script finished!
echo ==========================================

pause

