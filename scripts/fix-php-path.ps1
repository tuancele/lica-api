# Fix PHP Path for Laragon - Auto set PHP 8.3 as priority
# This script updates PATH to prioritize PHP 8.3.28

$php83Path = "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64"
$php81Path = "C:\laragon\bin\php\php-8.1.32-nts-Win32-vs16-x64"

# Remove PHP 8.1 from PATH if exists
$currentPath = $env:PATH -split ';'
$newPath = $currentPath | Where-Object { $_ -ne $php81Path }

# Add PHP 8.3 to the beginning of PATH
$env:PATH = "$php83Path;" + ($newPath -join ';')

Write-Host "✅ PHP PATH updated successfully!" -ForegroundColor Green
Write-Host "PHP Version: " -NoNewline
php -v | Select-Object -First 1
Write-Host "PHP Path: " -NoNewline
where.exe php | Select-Object -First 1

