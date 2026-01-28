@echo off
setlocal enabledelayedexpansion

echo ========================================
echo Complete Auto CI/CD Fix
echo ========================================
echo.

cd /d "%~dp0"

echo [1/6] Verifying Dockerfile fix...
findstr /C:"mkdir -p /var/www/html/bootstrap/cache" Dockerfile >nul
if %ERRORLEVEL% EQU 0 (
    echo   ✅ Dockerfile already fixed
) else (
    echo   ❌ Dockerfile needs fixing
    echo   Applying fix...
    powershell -Command "(Get-Content Dockerfile) -replace 'RUN chown -R www-data:www-data /var/www/html     && chmod -R 755 /var/www/html/storage     && chmod -R 755 /var/www/html/bootstrap/cache', 'RUN chown -R www-data:www-data /var/www/html     && mkdir -p /var/www/html/storage/framework/cache     && mkdir -p /var/www/html/storage/framework/sessions     && mkdir -p /var/www/html/storage/framework/views     && mkdir -p /var/www/html/storage/logs     && mkdir -p /var/www/html/bootstrap/cache     && chmod -R 755 /var/www/html/storage     && chmod -R 755 /var/www/html/bootstrap/cache' | Set-Content Dockerfile"
    echo   ✅ Dockerfile fixed
)

echo.
echo [2/6] Staging changes...
git add Dockerfile .dockerignore 2>&1 | findstr /V "^$"

echo.
echo [3/6] Committing...
git commit -m "Fix Docker build: Create bootstrap/cache directory before chmod" 2>&1 | findstr /V "^$"

echo.
echo [4/6] Pushing to GitHub...
git push 2>&1 | findstr /V "^$"

if %ERRORLEVEL% EQU 0 (
    echo   ✅ Pushed successfully
) else (
    echo   ⚠️  Push may have issues, but continuing...
)

echo.
echo [5/6] Waiting 60 seconds for CI/CD to start and complete...
timeout /t 60 /nobreak >nul

echo.
echo [6/6] Fetching CI/CD logs and checking for errors...
php scripts/fetch-and-fix-ci.php

echo.
echo ========================================
echo Auto Fix Complete
echo ========================================
echo.
echo Next steps:
echo 1. Check GitHub Actions to verify build status
echo 2. If still failing, run this script again
echo.
pause

