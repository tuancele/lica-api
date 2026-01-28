# Simple Database Import Script
param(
    [string]$DatabaseName = "lica",
    [string]$MySQLUser = "root",
    [string]$MySQLPassword = ""
)

$sqlFile = "C:\laragon\www\lica_databasee.sql"
$mysqlPath = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"

Write-Host "Importing database..." -ForegroundColor Cyan
Write-Host "Database: $DatabaseName" -ForegroundColor Yellow
Write-Host "SQL File: $sqlFile" -ForegroundColor Yellow

# Create database first
Write-Host "`nCreating database if not exists..." -ForegroundColor Yellow
$createDbCmd = "CREATE DATABASE IF NOT EXISTS `"$DatabaseName`" CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if ($MySQLPassword) {
    $env:MYSQL_PWD = $MySQLPassword
    & $mysqlPath -u $MySQLUser -e $createDbCmd
} else {
    & $mysqlPath -u $MySQLUser -e $createDbCmd
}

# Import SQL
Write-Host "`nImporting SQL file (this may take a while)..." -ForegroundColor Yellow

if ($MySQLPassword) {
    Get-Content $sqlFile -Encoding UTF8 | & $mysqlPath -u $MySQLUser -p$MySQLPassword $DatabaseName
} else {
    Get-Content $sqlFile -Encoding UTF8 | & $mysqlPath -u $MySQLUser $DatabaseName
}

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n✓ Import completed successfully!" -ForegroundColor Green
} else {
    Write-Host "`n✗ Import failed. Check MySQL credentials." -ForegroundColor Red
}

