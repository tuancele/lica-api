@echo off
echo ========================================
echo Auto Push and Fix CI/CD
echo ========================================
echo.

cd /d "%~dp0"

echo [1/6] Checking git status...
git status --short

echo.
echo [2/6] Staging Dockerfile...
git add Dockerfile .dockerignore 2>&1

echo.
echo [3/6] Committing changes...
git commit -m "Fix Docker build: Create bootstrap/cache directory before chmod" 2>&1

echo.
echo [4/6] Getting current branch...
for /f "tokens=*" %%i in ('git branch --show-current') do set BRANCH=%%i
echo   Branch: %BRANCH%

echo.
echo [5/6] Pushing to GitHub...
git push origin %BRANCH% 2>&1

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo   Push failed. Trying to fix...
    echo   Pulling latest changes...
    git pull --rebase origin %BRANCH% 2>&1
    echo   Pushing again...
    git push origin %BRANCH% 2>&1
)

echo.
echo [6/6] Waiting 60 seconds for CI/CD...
timeout /t 60 /nobreak >nul

echo.
echo Fetching CI/CD logs...
php scripts/auto-push-and-fix.php

echo.
echo ========================================
echo Process Complete
echo ========================================
echo.
echo Check GitHub Actions for build status
echo.
pause

