@echo off
echo ========================================
echo Auto CI/CD Fix - Automatic Process
echo ========================================
echo.

cd /d "%~dp0"

echo [Step 1] Checking git status...
git status --short

echo.
echo [Step 2] Staging Dockerfile changes...
git add Dockerfile .dockerignore

echo.
echo [Step 3] Committing changes...
git commit -m "Fix Docker build: Create bootstrap/cache directory before chmod" 2>&1

if %ERRORLEVEL% NEQ 0 (
    echo Warning: Commit may have failed or no changes to commit
)

echo.
echo [Step 4] Pushing to GitHub...
git push 2>&1

if %ERRORLEVEL% NEQ 0 (
    echo Warning: Push may have failed
    pause
    exit /b 1
)

echo.
echo [Step 5] Waiting 45 seconds for CI/CD to start...
timeout /t 45 /nobreak >nul

echo.
echo [Step 6] Fetching and analyzing CI/CD logs...
php scripts/auto-fix-ci-cd.php

echo.
echo ========================================
echo Process Complete
echo ========================================
echo.
pause

