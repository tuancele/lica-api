@echo off
echo Restarting Queue Worker for dictionary-crawl...
echo.

cd /d %~dp0

echo Stopping existing queue workers...
for /f "tokens=2" %%a in ('tasklist /FI "IMAGENAME eq php.exe" /FO LIST ^| findstr /I "PID"') do (
    for /f "tokens=*" %%b in ('wmic process where "ProcessId=%%a" get CommandLine /format:list ^| findstr "CommandLine"') do (
        echo %%b | findstr /I "queue:work.*dictionary-crawl" >nul
        if !errorlevel! == 0 (
            echo Stopping process %%a
            taskkill /F /PID %%a >nul 2>&1
        )
    )
)

timeout /t 2 /nobreak >nul

echo Clearing cache...
php artisan cache:clear
php artisan config:clear

timeout /t 1 /nobreak >nul

echo Starting queue worker...
start "Queue Worker - dictionary-crawl" php artisan queue:work --queue=dictionary-crawl --tries=3 --timeout=3600

echo.
echo Queue worker started!
echo Check the new window for queue worker output.
pause



