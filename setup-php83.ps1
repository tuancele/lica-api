# PHP 8.3 Setup Script for Laragon
# This script will download and install PHP 8.3 if not present

$ErrorActionPreference = "Stop"

Write-Host "=== PHP 8.3 Setup Script ===" -ForegroundColor Cyan
Write-Host ""

$laragonPath = "C:\laragon"
$phpPath = "$laragonPath\bin\php"
$php83Path = "$phpPath\php-8.3"

# Check if PHP 8.3 already exists
if (Test-Path "$php83Path\php.exe") {
    $version = & "$php83Path\php.exe" -v 2>&1 | Select-String "PHP (\d+\.\d+)" | ForEach-Object { $_.Matches[0].Groups[1].Value }
    Write-Host "✅ PHP $version found at: $php83Path" -ForegroundColor Green
    
    if ([version]$version -ge [version]"8.3.0") {
        Write-Host "✅ PHP version is compatible!" -ForegroundColor Green
        exit 0
    }
}

Write-Host "⚠️ PHP 8.3 not found. Starting download..." -ForegroundColor Yellow
Write-Host ""

# Create php directory if not exists
if (-not (Test-Path $phpPath)) {
    New-Item -ItemType Directory -Path $phpPath -Force | Out-Null
}

# PHP 8.3 download URL (Thread Safe, NTS, x64)
$php83Url = "https://windows.php.net/downloads/releases/php-8.3.0-Win32-vs16-x64.zip"
$zipPath = "$env:TEMP\php-8.3.zip"
$extractPath = "$env:TEMP\php-8.3-extract"

try {
    Write-Host "📥 Downloading PHP 8.3..." -ForegroundColor Cyan
    Invoke-WebRequest -Uri $php83Url -OutFile $zipPath -UseBasicParsing
    
    Write-Host "📦 Extracting PHP 8.3..." -ForegroundColor Cyan
    if (Test-Path $extractPath) {
        Remove-Item $extractPath -Recurse -Force
    }
    Expand-Archive -Path $zipPath -DestinationPath $extractPath -Force
    
    # Find the actual PHP folder (usually php-8.3.0)
    $phpFolder = Get-ChildItem $extractPath -Directory | Where-Object { $_.Name -like "php-8.3*" } | Select-Object -First 1
    
    if ($phpFolder) {
        Write-Host "📁 Moving PHP to Laragon..." -ForegroundColor Cyan
        if (Test-Path $php83Path) {
            Remove-Item $php83Path -Recurse -Force
        }
        Move-Item $phpFolder.FullName $php83Path -Force
        
        Write-Host "✅ PHP 8.3 installed successfully!" -ForegroundColor Green
        Write-Host ""
        Write-Host "📝 Next steps:" -ForegroundColor Yellow
        Write-Host "1. Open Laragon" -ForegroundColor White
        Write-Host "2. Menu → PHP → Version → Select PHP 8.3" -ForegroundColor White
        Write-Host "3. Click 'Restart All'" -ForegroundColor White
        Write-Host "4. Restart this terminal" -ForegroundColor White
        Write-Host ""
        
        # Verify installation
        if (Test-Path "$php83Path\php.exe") {
            $version = & "$php83Path\php.exe" -v 2>&1 | Select-String "PHP (\d+\.\d+)" | ForEach-Object { $_.Matches[0].Groups[1].Value }
            Write-Host "✅ Installed PHP version: $version" -ForegroundColor Green
        }
    } else {
        Write-Host "❌ Error: Could not find PHP folder in extracted archive" -ForegroundColor Red
        exit 1
    }
    
    # Cleanup
    Remove-Item $zipPath -Force -ErrorAction SilentlyContinue
    Remove-Item $extractPath -Recurse -Force -ErrorAction SilentlyContinue
    
} catch {
    Write-Host "❌ Error: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "📝 Manual installation:" -ForegroundColor Yellow
    Write-Host "1. Download PHP 8.3 from: https://windows.php.net/download/" -ForegroundColor White
    Write-Host "2. Extract to: $php83Path" -ForegroundColor White
    Write-Host "3. Restart Laragon and select PHP 8.3" -ForegroundColor White
    exit 1
}

