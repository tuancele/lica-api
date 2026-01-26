# PowerShell Script to Upgrade PHP to 8.3+ for Laragon
# Usage: .\upgrade-php-83.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "PHP 8.3 Upgrade Script for Laragon" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check current PHP version
Write-Host "Checking current PHP version..." -ForegroundColor Yellow
$currentVersion = php -v 2>&1 | Select-String "PHP (\d+\.\d+\.\d+)" | ForEach-Object { $_.Matches.Groups[1].Value }
Write-Host "Current PHP version: $currentVersion" -ForegroundColor Yellow

if ($currentVersion -match "^8\.3") {
    Write-Host "PHP 8.3+ is already installed!" -ForegroundColor Green
    exit 0
}

# Laragon paths
$laragonPath = "C:\laragon"
$phpBinPath = Join-Path $laragonPath "bin\php"

# Check if Laragon exists
if (-not (Test-Path $laragonPath)) {
    Write-Host "Laragon not found at $laragonPath" -ForegroundColor Red
    Write-Host "Please update the path in this script or install Laragon first." -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Laragon found at: $laragonPath" -ForegroundColor Green
Write-Host "PHP bin path: $phpBinPath" -ForegroundColor Green
Write-Host ""

# Check for existing PHP 8.3
$php83Path = Join-Path $phpBinPath "php-8.3*"
$existingPHP83 = Get-ChildItem -Path $phpBinPath -Directory -Filter "php-8.3*" -ErrorAction SilentlyContinue

if ($existingPHP83) {
    Write-Host "PHP 8.3 found: $($existingPHP83.FullName)" -ForegroundColor Green
    Write-Host ""
    $useExisting = Read-Host "Use existing PHP 8.3? (Y/N)"
    if ($useExisting -eq "Y" -or $useExisting -eq "y") {
        Write-Host ""
        Write-Host "To switch to PHP 8.3 in Laragon:" -ForegroundColor Yellow
        Write-Host "1. Open Laragon" -ForegroundColor Yellow
        Write-Host "2. Click Menu > PHP > Select version" -ForegroundColor Yellow
        Write-Host "3. Choose php-8.3.x" -ForegroundColor Yellow
        Write-Host "4. Restart Laragon" -ForegroundColor Yellow
        exit 0
    }
}

# Download PHP 8.3
Write-Host ""
Write-Host "Downloading PHP 8.3..." -ForegroundColor Yellow
Write-Host ""

$phpVersion = "8.3.15"  # Latest stable 8.3 version
$phpZipName = "php-$phpVersion-Win32-vs16-x64.zip"
$phpDownloadUrl = "https://windows.php.net/downloads/releases/$phpZipName"
$downloadPath = Join-Path $env:TEMP $phpZipName
$extractPath = Join-Path $phpBinPath "php-$phpVersion"

# Check if already downloaded
if (Test-Path $downloadPath) {
    Write-Host "PHP zip already exists: $downloadPath" -ForegroundColor Yellow
    $redownload = Read-Host "Re-download? (Y/N)"
    if ($redownload -ne "Y" -and $redownload -ne "y") {
        $useExisting = $true
    }
}

if (-not $useExisting) {
    Write-Host "Downloading from: $phpDownloadUrl" -ForegroundColor Cyan
    try {
        Invoke-WebRequest -Uri $phpDownloadUrl -OutFile $downloadPath -UseBasicParsing
        Write-Host "Download completed!" -ForegroundColor Green
    } catch {
        Write-Host "Download failed: $_" -ForegroundColor Red
        Write-Host ""
        Write-Host "Manual download:" -ForegroundColor Yellow
        Write-Host "1. Visit: https://windows.php.net/download/" -ForegroundColor Yellow
        Write-Host "2. Download PHP 8.3 Thread Safe (VS16 x64 Non Thread Safe)" -ForegroundColor Yellow
        Write-Host "3. Extract to: $extractPath" -ForegroundColor Yellow
        exit 1
    }
}

# Extract PHP
Write-Host ""
Write-Host "Extracting PHP..." -ForegroundColor Yellow

if (Test-Path $extractPath) {
    Write-Host "Extract path already exists: $extractPath" -ForegroundColor Yellow
    $overwrite = Read-Host "Overwrite? (Y/N)"
    if ($overwrite -ne "Y" -and $overwrite -ne "y") {
        Write-Host "Skipping extraction." -ForegroundColor Yellow
    } else {
        Remove-Item -Path $extractPath -Recurse -Force
        Expand-Archive -Path $downloadPath -DestinationPath $phpBinPath -Force
        Write-Host "Extraction completed!" -ForegroundColor Green
    }
} else {
    Expand-Archive -Path $downloadPath -DestinationPath $phpBinPath -Force
    Write-Host "Extraction completed!" -ForegroundColor Green
}

# Find extracted folder (might have different name)
$extractedFolders = Get-ChildItem -Path $phpBinPath -Directory -Filter "php-$phpVersion*" | Sort-Object LastWriteTime -Descending
if ($extractedFolders) {
    $actualExtractPath = $extractedFolders[0].FullName
    Write-Host "PHP extracted to: $actualExtractPath" -ForegroundColor Green
} else {
    Write-Host "Could not find extracted PHP folder" -ForegroundColor Red
    exit 1
}

# Copy php.ini if needed
$phpIniPath = Join-Path $actualExtractPath "php.ini"
$phpIniDevelopment = Join-Path $actualExtractPath "php.ini-development"
$phpIniProduction = Join-Path $actualExtractPath "php.ini-production"

if (-not (Test-Path $phpIniPath)) {
    if (Test-Path $phpIniDevelopment) {
        Copy-Item $phpIniDevelopment $phpIniPath
        Write-Host "Created php.ini from development template" -ForegroundColor Green
    } elseif (Test-Path $phpIniProduction) {
        Copy-Item $phpIniProduction $phpIniPath
        Write-Host "Created php.ini from production template" -ForegroundColor Green
    }
}

# Enable required extensions
Write-Host ""
Write-Host "Configuring PHP extensions..." -ForegroundColor Yellow

$extensionsToEnable = @(
    "extension=curl",
    "extension=fileinfo",
    "extension=gd",
    "extension=mbstring",
    "extension=mysqli",
    "extension=openssl",
    "extension=pdo_mysql",
    "extension=redis",
    "extension=zip"
)

if (Test-Path $phpIniPath) {
    $phpIniContent = Get-Content $phpIniPath -Raw
    
    foreach ($extension in $extensionsToEnable) {
        $extName = $extension -replace "extension=", ""
        if ($phpIniContent -notmatch "extension=$extName") {
            # Try to uncomment if commented
            $phpIniContent = $phpIniContent -replace ";$extension", $extension
            # Or add if not exists
            if ($phpIniContent -notmatch $extension) {
                $phpIniContent += "`n$extension"
            }
        }
    }
    
    Set-Content -Path $phpIniPath -Value $phpIniContent -NoNewline
    Write-Host "Extensions configured!" -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "PHP 8.3 Installation Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Open Laragon" -ForegroundColor White
Write-Host "2. Click Menu > PHP > Select version" -ForegroundColor White
Write-Host "3. Choose: php-$phpVersion" -ForegroundColor White
Write-Host "4. Restart Laragon" -ForegroundColor White
Write-Host "5. Verify: php -v" -ForegroundColor White
Write-Host ""
Write-Host "PHP installed at: $actualExtractPath" -ForegroundColor Cyan
Write-Host ""

