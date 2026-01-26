# Use PHP 8.3 from Laragon
$php83Path = "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe"

if (Test-Path $php83Path) {
    Write-Host "Using PHP 8.3.28 from Laragon" -ForegroundColor Green
    & $php83Path -v
    
    # Set environment variable for this session
    $env:PATH = "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64;" + $env:PATH
    
    Write-Host ""
    Write-Host "PHP 8.3 is now in PATH for this session" -ForegroundColor Green
    Write-Host "You can now run: composer update" -ForegroundColor Yellow
} else {
    Write-Host "PHP 8.3 not found at: $php83Path" -ForegroundColor Red
}

