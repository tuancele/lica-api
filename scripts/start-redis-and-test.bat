@echo off
echo ========================================
echo LICA - Start Redis and Test Connection
echo ========================================
echo.

REM Check if Redis is already running
netstat -an | findstr :6379 >nul
if %errorlevel% == 0 (
    echo [INFO] Redis is already running on port 6379
    goto :test
)

echo [INFO] Redis is not running. Please start it manually:
echo.
echo Option 1: Using Laragon GUI
echo   1. Open Laragon
echo   2. Click on "Services" menu
echo   3. Find "Redis" and click "Start"
echo.
echo Option 2: Using Command Line (if Redis is installed)
echo   redis-server
echo.
echo Option 3: Using Docker
echo   docker-compose up -d redis
echo.
echo Press any key after starting Redis to continue testing...
pause >nul

:test
echo.
echo ========================================
echo Testing Redis Connection
echo ========================================
echo.

php artisan tinker --execute="
try {
    Cache::put('test_key', 'test_value', 60);
    \$value = Cache::get('test_key');
    if (\$value === 'test_value') {
        echo '✅ Cache test: PASSED\n';
    } else {
        echo '❌ Cache test: FAILED\n';
    }
    
    \$ping = Redis::connection()->ping();
    if (\$ping === 'PONG') {
        echo '✅ Redis ping: PASSED\n';
    } else {
        echo '❌ Redis ping: FAILED\n';
    }
    
    echo '✅ Redis connection test completed successfully!\n';
} catch (Exception \$e) {
    echo '❌ Redis connection test FAILED:\n';
    echo \$e->getMessage() . '\n';
    exit(1);
}
"

if %errorlevel% == 0 (
    echo.
    echo ========================================
    echo ✅ Redis Connection Test: SUCCESS
    echo ========================================
) else (
    echo.
    echo ========================================
    echo ❌ Redis Connection Test: FAILED
    echo ========================================
    echo Please make sure Redis service is running.
)

pause

