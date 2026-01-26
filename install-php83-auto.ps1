# Auto Install PHP 8.3 for Laragon
$ErrorActionPreference = "Stop"

Write-Host "=== Auto Install PHP 8.3 ===" -ForegroundColor Cyan
Write-Host ""

$laragonPath = "C:\laragon"
$phpPath = "$laragonPath\bin\php"
$php83Path = "$phpPath\php-8.3"

# Check if already installed
if (Test-Path "$php83Path\php.exe") {
    $version = & "$php83Path\php.exe" -r "echo PHP_VERSION;" 2>&1
    if ($version -ge "8.3.0") {
        Write-Host "✅ PHP 8.3 already installed: $version" -ForegroundColor Green
        exit 0
    }
}

# Create directory
if (-not (Test-Path $phpPath)) {
    New-Item -ItemType Directory -Path $phpPath -Force | Out-Null
}

# Download PHP 8.3 (latest stable)
Write-Host "📥 Downloading PHP 8.3..." -ForegroundColor Cyan
$php83Url = "https://windows.php.net/downloads/releases/php-8.3.13-Win32-vs16-x64.zip"
$zipPath = "$env:TEMP\php-8.3-latest.zip"

try {
    Invoke-WebRequest -Uri $php83Url -OutFile $zipPath -UseBasicParsing
    Write-Host "✅ Download complete" -ForegroundColor Green
    
    Write-Host "📦 Extracting..." -ForegroundColor Cyan
    $extractPath = "$env:TEMP\php-8.3-extract"
    if (Test-Path $extractPath) {
        Remove-Item $extractPath -Recurse -Force
    }
    Expand-Archive -Path $zipPath -DestinationPath $extractPath -Force
    
    # Find PHP folder
    $phpFolder = Get-ChildItem $extractPath -Directory | Where-Object { $_.Name -like "php-8.3*" } | Select-Object -First 1
    
    if ($phpFolder) {
        Write-Host "📁 Installing to Laragon..." -ForegroundColor Cyan
        if (Test-Path $php83Path) {
            Remove-Item $php83Path -Recurse -Force
        }
        Move-Item $phpFolder.FullName $php83Path -Force
        
        # Copy php.ini if needed
        if (-not (Test-Path "$php83Path\php.ini")) {
            Copy-Item "$php83Path\php.ini-development" "$php83Path\php.ini" -Force
        }
        
        Write-Host "✅ PHP 8.3 installed successfully!" -ForegroundColor Green
        Write-Host ""
        Write-Host "📝 Please:" -ForegroundColor Yellow
        Write-Host "1. Open Laragon" -ForegroundColor White
        Write-Host "2. Menu → PHP → Version → Select PHP 8.3" -ForegroundColor White
        Write-Host "3. Click Restart All" -ForegroundColor White
        Write-Host "4. Restart terminal and run: php verify-php-version.php" -ForegroundColor White
        
        # Cleanup
        Remove-Item $zipPath -Force -ErrorAction SilentlyContinue
        Remove-Item $extractPath -Recurse -Force -ErrorAction SilentlyContinue
        
    } else {
        Write-Host "❌ Error: PHP folder not found" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "❌ Error: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please download PHP 8.3 manually from:" -ForegroundColor Yellow
    Write-Host "https://windows.php.net/download/" -ForegroundColor Cyan
    Write-Host "Extract to: $php83Path" -ForegroundColor White
    exit 1
}
