# Import Database Script
$dbName = "lica"
$mysqlUser = "root"
$mysqlPassword = "123456@@"
$sqlFile = "C:\laragon\www\lica_databasee.sql"
$mysqlPath = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"

Write-Host "=== Database Import ===" -ForegroundColor Cyan
Write-Host "Database: $dbName" -ForegroundColor Yellow
Write-Host "SQL File: $sqlFile" -ForegroundColor Yellow
Write-Host ""

# Step 1: Create database
Write-Host "Step 1: Creating database if not exists..." -ForegroundColor Yellow
$createDbArgs = @(
    "-u", $mysqlUser,
    "-p$mysqlPassword",
    "-e", "CREATE DATABASE IF NOT EXISTS `"$dbName`" CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
)
& $mysqlPath $createDbArgs 2>&1 | Out-Null
if ($LASTEXITCODE -eq 0) {
    Write-Host "  Database ready" -ForegroundColor Green
} else {
    Write-Host "  Warning: Check database creation" -ForegroundColor Yellow
}

# Step 2: Import SQL
Write-Host ""
Write-Host "Step 2: Importing SQL file..." -ForegroundColor Yellow
Write-Host "  This may take several minutes depending on file size..." -ForegroundColor Cyan
$startTime = Get-Date

$importArgs = @(
    "-u", $mysqlUser,
    "-p$mysqlPassword",
    $dbName
)

try {
    Get-Content $sqlFile -Encoding UTF8 | & $mysqlPath $importArgs 2>&1 | Tee-Object -Variable importOutput
    
    $endTime = Get-Date
    $duration = [math]::Round(($endTime - $startTime).TotalSeconds, 2)
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "Import completed successfully!" -ForegroundColor Green
        Write-Host "  Time taken: $duration seconds" -ForegroundColor Cyan
        Write-Host "  Database: $dbName" -ForegroundColor Cyan
    } else {
        Write-Host ""
        Write-Host "Import completed with exit code: $LASTEXITCODE" -ForegroundColor Yellow
        Write-Host "  Check output above for details" -ForegroundColor Yellow
    }
} catch {
    Write-Host ""
    Write-Host "Error: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "Done!" -ForegroundColor Green

