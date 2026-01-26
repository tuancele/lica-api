@echo off
echo ========================================
echo LICA - Phase 1 Final Steps
echo ========================================
echo.

REM Step 1: Check Redis
echo [Step 1] Checking Redis service...
netstat -an | findstr :6379 >nul
if %errorlevel% == 0 (
    echo ✅ Redis is running on port 6379
    goto :test_redis
) else (
    echo ❌ Redis is NOT running
    echo.
    echo ========================================
    echo PLEASE START REDIS SERVICE
    echo ========================================
    echo.
    echo Option 1: Using Laragon (Recommended)
    echo   1. Open Laragon
    echo   2. Click on "Services" menu
    echo   3. Find "Redis" and click "Start"
    echo   4. Wait for Redis icon to turn green
    echo.
    echo Option 2: Using Docker
    echo   docker-compose up -d redis
    echo.
    echo Press any key after starting Redis to continue...
    pause >nul
)

:test_redis
echo.
echo [Step 2] Testing Redis connection...
php scripts\test-redis.php
if %errorlevel% != 0 (
    echo.
    echo ❌ Redis test failed. Please check Redis service.
    pause
    exit /b 1
)

echo.
echo [Step 3] Testing Queue...
echo.
echo Dispatching test job...
php artisan tinker --execute="dispatch(new App\Jobs\TestQueueJob()); echo 'Job dispatched successfully!' . PHP_EOL;"

if %errorlevel% != 0 (
    echo ❌ Failed to dispatch job
    pause
    exit /b 1
)

echo.
echo ✅ Job dispatched successfully!
echo.
echo ========================================
echo Queue Worker Instructions
echo ========================================
echo.
echo To process the job, run in a NEW terminal:
echo   php artisan queue:work --verbose
echo.
echo Press Ctrl+C to stop the worker after testing.
echo.
pause

echo.
echo [Step 4] Preparing Git commit...
echo.
echo Files ready to commit:
echo   - Phase 1 documentation files
echo   - Test scripts
echo   - Test queue job
echo   - Formatted code (Pint)
echo.
echo Do you want to prepare git commit? (Y/N)
set /p commit_choice=

if /i "%commit_choice%"=="Y" (
    echo.
    echo Adding Phase 1 files...
    git add PHASE1_*.md
    git add scripts/test-redis.php
    git add scripts/test-queue.bat
    git add scripts/start-redis-and-test.bat
    git add scripts/complete-phase1-final.bat
    git add app/Jobs/TestQueueJob.php
    git add scripts/verify-cicd.md
    
    echo.
    echo Files added. Review with: git status
    echo.
    echo To commit and push:
    echo   git commit -m "Phase 1: Complete - Redis config, Queue setup, CI/CD pipeline"
    echo   git push origin main
    echo.
) else (
    echo Skipping git commit preparation.
)

echo.
echo ========================================
echo Phase 1 Final Steps Summary
echo ========================================
echo.
echo ✅ Redis: Tested
echo ✅ Queue: Job dispatched
echo ⏳ Queue Worker: Run manually in separate terminal
echo ⏳ Git Commit: Ready (if you chose Y)
echo ⏳ CI/CD: Push code to GitHub to verify
echo.
echo Next steps:
echo   1. Start queue worker: php artisan queue:work --verbose
echo   2. Commit and push code to GitHub
echo   3. Check Actions tab on GitHub to verify CI/CD
echo.
pause

