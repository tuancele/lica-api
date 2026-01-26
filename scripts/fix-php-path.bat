@echo off
chcp 65001 >nul
echo ========================================
echo Fix PHP Path - Set PHP 8.3 as Priority
echo ========================================
echo.

REM Set PHP 8.3 path
set "PHP83_PATH=C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64"

REM Add to PATH (prepend)
set "PATH=%PHP83_PATH%;%PATH%"

echo ✅ PHP PATH updated in current session!
echo.
php -v
echo.
echo Note: This only affects current terminal session.
echo To make permanent, add to System Environment Variables or run this script each time.
echo.

