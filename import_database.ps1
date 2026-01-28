# Import SQL Database Script
# Usage: .\import_database.ps1

$sqlFile = "C:\laragon\www\lica_databasee.sql"
$mysqlPath = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"

# Check if SQL file exists
if (-not (Test-Path $sqlFile)) {
    Write-Host "Error: SQL file not found at $sqlFile" -ForegroundColor Red
    exit 1
}

# Check if MySQL exists
if (-not (Test-Path $mysqlPath)) {
    Write-Host "Error: MySQL not found at $mysqlPath" -ForegroundColor Red
    exit 1
}

# Try to find database name from SQL file
Write-Host "Searching for database name in SQL file..." -ForegroundColor Yellow
$dbName = $null
$sqlContent = Get-Content $sqlFile -Raw -Encoding UTF8

# Try to find CREATE DATABASE or USE statement
if ($sqlContent -match "(?i)CREATE DATABASE\s+(?:IF NOT EXISTS\s+)?[`"']?(\w+)[`"']?") {
    $dbName = $matches[1]
    Write-Host "Found database name: $dbName" -ForegroundColor Green
} elseif ($sqlContent -match "(?i)USE\s+[`"']?(\w+)[`"']?") {
    $dbName = $matches[1]
    Write-Host "Found database name: $dbName" -ForegroundColor Green
} else {
    # Default database name
    $dbName = "lica"
    Write-Host "Using default database name: $dbName" -ForegroundColor Yellow
}

# Get MySQL credentials
Write-Host "`nMySQL Connection:" -ForegroundColor Cyan
$mysqlUser = Read-Host "Enter MySQL username (default: root)"
if ([string]::IsNullOrWhiteSpace($mysqlUser)) {
    $mysqlUser = "root"
}

$mysqlPassword = Read-Host "Enter MySQL password" -AsSecureString
$mysqlPasswordPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR($mysqlPassword)
)

# Create database if not exists
Write-Host "`nCreating database if not exists: $dbName" -ForegroundColor Yellow
$createDbCmd = "CREATE DATABASE IF NOT EXISTS `"$dbName`" CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
$createDbArgs = @(
    "-u", $mysqlUser,
    "-p$mysqlPasswordPlain",
    "-e", $createDbCmd
)

try {
    & $mysqlPath $createDbArgs 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Database created/verified successfully" -ForegroundColor Green
    } else {
        Write-Host "Warning: Database creation may have failed" -ForegroundColor Yellow
    }
} catch {
    Write-Host "Error creating database: $_" -ForegroundColor Red
}

# Import SQL file
Write-Host "`nImporting SQL file: $sqlFile" -ForegroundColor Yellow
Write-Host "This may take a few minutes depending on file size..." -ForegroundColor Yellow

$importArgs = @(
    "-u", $mysqlUser,
    "-p$mysqlPasswordPlain",
    $dbName
)

try {
    Get-Content $sqlFile -Encoding UTF8 | & $mysqlPath $importArgs 2>&1
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "`n✓ Database imported successfully!" -ForegroundColor Green
        Write-Host "Database: $dbName" -ForegroundColor Cyan
    } else {
        Write-Host "`n✗ Import completed with warnings/errors. Check output above." -ForegroundColor Yellow
    }
} catch {
    Write-Host "`n✗ Error importing database: $_" -ForegroundColor Red
    exit 1
}

# Clear password from memory
$mysqlPasswordPlain = $null
[System.GC]::Collect()

Write-Host "`nDone!" -ForegroundColor Green

