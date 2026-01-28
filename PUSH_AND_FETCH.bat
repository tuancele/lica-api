@echo off
setlocal enabledelayedexpansion

echo ========================================
echo Auto Push and Fetch GitHub Logs
echo ========================================
echo.

cd /d "%~dp0"

REM Step 1: Stage and commit
echo [1/4] Staging and committing...
git add Dockerfile .dockerignore
git commit -m "Fix Docker build: Create bootstrap/cache directory before chmod" 2>&1 | findstr /V "^$"

REM Step 2: Get branch and push
echo.
echo [2/4] Pushing to GitHub...
for /f "tokens=*" %%i in ('git branch --show-current 2^>nul') do set BRANCH=%%i
echo   Branch: !BRANCH!
git push origin !BRANCH! 2>&1 | findstr /V "^$"

REM Step 3: Wait
echo.
echo [3/4] Waiting 60 seconds for CI/CD...
timeout /t 60 /nobreak >nul

REM Step 4: Fetch logs
echo.
echo [4/4] Fetching logs from GitHub...
php scripts/auto-push-fetch-logs.php

echo.
echo ========================================
echo Complete
echo ========================================
pause

