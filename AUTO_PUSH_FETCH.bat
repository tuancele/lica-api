@echo off
echo ========================================
echo Auto Push and Fetch GitHub Logs
echo ========================================
echo.

cd /d "%~dp0"

echo [Step 1] Checking git status...
git status --short

echo.
echo [Step 2] Staging changes...
git add Dockerfile .dockerignore 2>&1

echo.
echo [Step 3] Committing...
git commit -m "Fix Docker build: Create bootstrap/cache directory before chmod" 2>&1

echo.
echo [Step 4] Getting branch...
for /f "tokens=*" %%i in ('git branch --show-current') do set BRANCH=%%i
echo   Branch: %BRANCH%

echo.
echo [Step 5] Pushing to GitHub...
git push origin %BRANCH% 2>&1

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo   Push failed. Trying to fix...
    git pull --rebase origin %BRANCH% 2>&1
    git push origin %BRANCH% 2>&1
)

echo.
echo [Step 6] Running PHP script to fetch logs...
php scripts/auto-push-fetch-logs.php

echo.
echo ========================================
echo Process Complete
echo ========================================
echo.
pause

