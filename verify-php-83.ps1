# Script tu dong verify va switch PHP 8.3
# LICA Backend V2 - Phase 1

Write-Host "=== PHP 8.3 Verification Script ===" -ForegroundColor Cyan
Write-Host ""

# Check if Laragon is installed
$laragonPath = "C:\laragon"
if (-not (Test-Path $laragonPath)) {
    Write-Host "ERROR: Laragon not found at $laragonPath" -ForegroundColor Red
    exit 1
}

# Check PHP 8.3 exists
$php83Path = Join-Path $laragonPath "bin\php\php-8.3.28-Win32-vs16-x64"
if (-not (Test-Path $php83Path)) {
    Write-Host "ERROR: PHP 8.3.28 not found at $php83Path" -ForegroundColor Red
    exit 1
}

Write-Host "PHP 8.3.28 found at: $php83Path" -ForegroundColor Green

# Add PHP 8.3 to PATH for current session
$php83Bin = $php83Path
$currentPath = $env:PATH
$env:PATH = "$php83Bin;$currentPath"

Write-Host ""
Write-Host "=== Verifying PHP Version ===" -ForegroundColor Cyan
$phpExe = Join-Path $php83Bin "php.exe"
$phpVersion = & $phpExe -v 2>&1 | Select-Object -First 1
Write-Host $phpVersion

if ($phpVersion -match "PHP 8\.3") {
    Write-Host "PHP 8.3 detected!" -ForegroundColor Green
} else {
    Write-Host "PHP version mismatch!" -ForegroundColor Red
    exit 1
}

# Verify PHP extensions
Write-Host ""
Write-Host "=== Checking Required PHP Extensions ===" -ForegroundColor Cyan
$requiredExts = @("pdo_mysql", "mbstring", "xml", "curl", "zip", "gd", "opcache", "bcmath")
$missingExts = @()

$loadedExts = & $phpExe -m 2>&1
foreach ($ext in $requiredExts) {
    if ($loadedExts -match "^$ext$") {
        Write-Host "$ext - OK" -ForegroundColor Green
    } else {
        Write-Host "$ext - MISSING" -ForegroundColor Yellow
        $missingExts += $ext
    }
}

if ($missingExts.Count -gt 0) {
    Write-Host ""
    Write-Host "WARNING: Missing extensions: $($missingExts -join ', ')" -ForegroundColor Yellow
    Write-Host "Note: Some extensions may need to be enabled in php.ini" -ForegroundColor Yellow
}

# Verify Composer
Write-Host ""
Write-Host "=== Verifying Composer ===" -ForegroundColor Cyan
try {
    $composerVersion = & composer --version 2>&1
    Write-Host $composerVersion
} catch {
    Write-Host "Composer not found in PATH" -ForegroundColor Yellow
}

# Test PHP CLI
Write-Host ""
Write-Host "=== Testing PHP CLI ===" -ForegroundColor Cyan
$testResult = & $phpExe -r "echo 'PHP Version: ' . PHP_VERSION . PHP_EOL;"
if ($LASTEXITCODE -eq 0) {
    Write-Host $testResult
    Write-Host "PHP CLI working correctly" -ForegroundColor Green
} else {
    Write-Host "PHP CLI test failed" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "=== Summary ===" -ForegroundColor Cyan
Write-Host "PHP 8.3.28: Ready" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Restart your terminal to ensure PATH is updated" -ForegroundColor White
Write-Host "2. Run: composer update" -ForegroundColor White
Write-Host "3. Run: php artisan migrate:status" -ForegroundColor White
Write-Host ""
