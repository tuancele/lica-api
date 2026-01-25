@echo off
echo Starting Queue Worker for dictionary-crawl...
echo.
cd /d %~dp0
php artisan queue:work --queue=dictionary-crawl --tries=3 --timeout=3600
pause





