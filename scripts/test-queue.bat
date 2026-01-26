@echo off
echo ========================================
echo LICA - Test Queue with Redis
echo ========================================
echo.

REM Check if Redis is running
netstat -an | findstr :6379 >nul
if %errorlevel% != 0 (
    echo ❌ Redis is not running on port 6379
    echo Please start Redis first using start-redis-and-test.bat
    pause
    exit /b 1
)

echo [INFO] Redis is running. Testing queue...
echo.

REM Create a simple test job if it doesn't exist
if not exist "app\Jobs\TestQueueJob.php" (
    echo Creating test job...
    php artisan make:job TestQueueJob
    echo.
)

echo ========================================
echo Step 1: Dispatch Test Job
echo ========================================
echo.

php artisan tinker --execute="
use App\Jobs\TestQueueJob;
try {
    dispatch(new TestQueueJob());
    echo '✅ Job dispatched successfully!\n';
    echo 'Job ID should appear in Redis queue.\n';
} catch (Exception \$e) {
    echo '❌ Failed to dispatch job:\n';
    echo \$e->getMessage() . '\n';
    exit(1);
}
"

if %errorlevel% != 0 (
    echo.
    echo ❌ Failed to dispatch job
    pause
    exit /b 1
)

echo.
echo ========================================
echo Step 2: Start Queue Worker
echo ========================================
echo.
echo [INFO] Starting queue worker...
echo [INFO] Press Ctrl+C to stop the worker
echo.

php artisan queue:work --verbose

pause

