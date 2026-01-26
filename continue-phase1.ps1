# Continue Phase 1 Upgrade - Auto verify and proceed
Write-Host "=== Continuing Phase 1 Upgrade ===" -ForegroundColor Cyan
Write-Host ""

# Try to find PHP 8.2+ in Laragon
$phpPaths = @(
    "C:\laragon\bin\php\php-8.3\php.exe",
    "C:\laragon\bin\php\php-8.3.0\php.exe",
    "C:\laragon\bin\php\php-8.2\php.exe",
    "C:\laragon\bin\php\php-8.2.0\php.exe"
)

$phpExe = $null
foreach ($path in $phpPaths) {
    if (Test-Path $path) {
        $version = & $path -r "echo PHP_VERSION;" 2>&1
        if ($version -ge "8.2.0") {
            $phpExe = $path
            Write-Host "✅ Found PHP $version at: $path" -ForegroundColor Green
            break
        }
    }
}

if (-not $phpExe) {
    Write-Host "❌ PHP 8.2+ not found in Laragon" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please:" -ForegroundColor Yellow
    Write-Host "1. Open Laragon" -ForegroundColor White
    Write-Host "2. Menu → PHP → Version → Select PHP 8.3 (or download if not available)" -ForegroundColor White
    Write-Host "3. Click 'Restart All'" -ForegroundColor White
    Write-Host "4. Restart terminal and run this script again" -ForegroundColor White
    exit 1
}

# Use the found PHP
$env:Path = (Split-Path $phpExe -Parent) + ";" + $env:Path

# Verify
Write-Host ""
Write-Host "Verifying PHP version..." -ForegroundColor Cyan
php -v
Write-Host ""

$versionCheck = php -r "echo version_compare(PHP_VERSION, '8.2.0', '>=') ? 'OK' : 'FAIL';"
if ($versionCheck -eq "OK") {
    Write-Host "✅ PHP version is compatible!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Continuing with composer update..." -ForegroundColor Cyan
    Write-Host ""
    
    # Check composer
    composer --version
    Write-Host ""
    
    # Dry run
    Write-Host "Running composer update --dry-run..." -ForegroundColor Cyan
    composer update --dry-run 2>&1 | Select-Object -First 50
    
} else {
    Write-Host "❌ PHP version check failed" -ForegroundColor Red
    exit 1
}

