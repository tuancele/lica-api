@echo off
chcp 65001 >nul
echo ========================================
echo Chuyển Đổi Laragon Sang PHP 8.3
echo ========================================
echo.

echo Đang kiểm tra PHP hiện tại...
php -v
echo.

echo ========================================
echo HƯỚNG DẪN CHUYỂN ĐỔI:
echo ========================================
echo.
echo 1. Mở Laragon
echo 2. Click Menu ^> PHP ^> Select version
echo 3. Chọn: php-8.3.28-Win32-vs16-x64
echo 4. Click "Stop All" rồi "Start All"
echo    HOẶC restart Laragon hoàn toàn
echo 5. Kiểm tra: php -v
echo.

echo Sau khi chuyển đổi, chạy:
echo   composer update
echo.

pause

