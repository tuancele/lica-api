# PHP Version Check Script
Write-Host "=== PHP Version Check ===" -ForegroundColor Cyan
Write-Host ""

# Check PHP in PATH
Write-Host "1. PHP from PATH:" -ForegroundColor Yellow
$phpPath = Get-Command php -ErrorAction SilentlyContinue
if ($phpPath) {
    Write-Host "   Location: $($phpPath.Source)" -ForegroundColor Green
    & php -v
} else {
    Write-Host "   PHP not found in PATH" -ForegroundColor Red
}

Write-Host ""
Write-Host "2. PHP in Laragon:" -ForegroundColor Yellow
$laragonPhpPaths = @(
    "C:\laragon\bin\php\php-8.3.0-nts-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.3.1-nts-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.3.2-nts-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.3.3-nts-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.3.4-nts-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.3.5-nts-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.3.6-nts-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.3.7-nts-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.3.8-nts-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.3.9-nts-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.3.10-nts-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.3.11-nts-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe"
)

$found = $false
foreach ($path in $laragonPhpPaths) {
    if (Test-Path $path) {
        Write-Host "   Found: $path" -ForegroundColor Green
        & $path -v
        $found = $true
        break
    }
}

if (-not $found) {
    Write-Host "   Checking Laragon PHP directory..." -ForegroundColor Yellow
    $laragonPhpDir = "C:\laragon\bin\php"
    if (Test-Path $laragonPhpDir) {
        $phpDirs = Get-ChildItem $laragonPhpDir -Directory | Where-Object { $_.Name -like "php-8.3*" } | Sort-Object Name -Descending
        if ($phpDirs) {
            $latestPhp = $phpDirs[0]
            $phpExe = Join-Path $latestPhp.FullName "php.exe"
            if (Test-Path $phpExe) {
                Write-Host "   Found: $phpExe" -ForegroundColor Green
                & $phpExe -v
            }
        } else {
            Write-Host "   No PHP 8.3 found in Laragon" -ForegroundColor Red
        }
    }
}

Write-Host ""
Write-Host "3. Composer PHP:" -ForegroundColor Yellow
& composer diagnose 2>&1 | Select-String -Pattern "PHP version"

Write-Host ""
Write-Host "=== Recommendation ===" -ForegroundColor Cyan
Write-Host "If PHP 8.3 is installed but not detected:"
Write-Host "1. Restart terminal completely"
Write-Host "2. Or use Laragon terminal (Menu -> Terminal)"
Write-Host "3. Or manually set PATH to PHP 8.3"

