# Script to switch Laragon to PHP 8.3
# Usage: .\switch-to-php83.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Switching Laragon to PHP 8.3" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check current PHP version
$currentVersion = php -v 2>&1 | Select-String "PHP (\d+\.\d+\.\d+)" | ForEach-Object { $_.Matches.Groups[1].Value }
Write-Host "Current PHP version: $currentVersion" -ForegroundColor Yellow

if ($currentVersion -match "^8\.3") {
    Write-Host "PHP 8.3 is already active!" -ForegroundColor Green
    php -v
    exit 0
}

# Laragon paths
$laragonPath = "C:\laragon"
$phpBinPath = Join-Path $laragonPath "bin\php"
$php83Path = Join-Path $phpBinPath "php-8.3.28-Win32-vs16-x64"

# Check if PHP 8.3 exists
if (-not (Test-Path $php83Path)) {
    Write-Host "PHP 8.3 not found at: $php83Path" -ForegroundColor Red
    Write-Host ""
    Write-Host "Available PHP versions:" -ForegroundColor Yellow
    Get-ChildItem -Path $phpBinPath -Directory | ForEach-Object {
        Write-Host "  - $($_.Name)" -ForegroundColor White
    }
    exit 1
}

Write-Host "PHP 8.3 found at: $php83Path" -ForegroundColor Green
Write-Host ""

# Check php.ini
$phpIniPath = Join-Path $php83Path "php.ini"
$phpIniDevelopment = Join-Path $php83Path "php.ini-development"

if (-not (Test-Path $phpIniPath)) {
    if (Test-Path $phpIniDevelopment) {
        Write-Host "Creating php.ini from development template..." -ForegroundColor Yellow
        Copy-Item $phpIniDevelopment $phpIniPath
        Write-Host "php.ini created!" -ForegroundColor Green
    }
}

# Enable required extensions
if (Test-Path $phpIniPath) {
    Write-Host "Configuring PHP extensions..." -ForegroundColor Yellow
    $phpIniContent = Get-Content $phpIniPath -Raw
    
    $extensions = @(
        "extension=curl",
        "extension=fileinfo",
        "extension=gd",
        "extension=mbstring",
        "extension=mysqli",
        "extension=openssl",
        "extension=pdo_mysql",
        "extension=zip"
    )
    
    foreach ($ext in $extensions) {
        $extName = $ext -replace "extension=", ""
        # Uncomment if commented
        $phpIniContent = $phpIniContent -replace ";$ext", $ext
    }
    
    Set-Content -Path $phpIniPath -Value $phpIniContent -NoNewline
    Write-Host "Extensions configured!" -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Manual Steps Required:" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Open Laragon" -ForegroundColor White
Write-Host "2. Click Menu > PHP > Select version" -ForegroundColor White
Write-Host "3. Choose: php-8.3.28-Win32-vs16-x64" -ForegroundColor White
Write-Host "4. Click 'Stop All' then 'Start All'" -ForegroundColor White
Write-Host "   OR restart Laragon completely" -ForegroundColor White
Write-Host "5. Verify: php -v" -ForegroundColor White
Write-Host ""

# Try to update PATH temporarily for verification
$php83Exe = Join-Path $php83Path "php.exe"
if (Test-Path $php83Exe) {
    Write-Host "Testing PHP 8.3 directly..." -ForegroundColor Yellow
    & $php83Exe -v
    Write-Host ""
    Write-Host "If version shows 8.3.28, PHP is ready!" -ForegroundColor Green
    Write-Host "Just switch in Laragon as shown above." -ForegroundColor Green
}

Write-Host ""
Write-Host "After switching, run:" -ForegroundColor Yellow
Write-Host "  composer update" -ForegroundColor Cyan
Write-Host ""
