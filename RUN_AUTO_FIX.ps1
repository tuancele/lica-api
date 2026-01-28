# Auto CI/CD Fix Script
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Auto CI/CD Fix - Automatic Process" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Set-Location $PSScriptRoot

# Step 1: Verify Dockerfile fix
Write-Host "[1/5] Verifying Dockerfile fix..." -ForegroundColor Yellow
$dockerfile = Get-Content "Dockerfile" -Raw
if ($dockerfile -match "mkdir -p /var/www/html/bootstrap/cache") {
    Write-Host "  ✅ Dockerfile already fixed" -ForegroundColor Green
} else {
    Write-Host "  ❌ Dockerfile needs fixing" -ForegroundColor Red
    exit 1
}

# Step 2: Stage changes
Write-Host ""
Write-Host "[2/5] Staging changes..." -ForegroundColor Yellow
git add Dockerfile .dockerignore 2>&1 | Out-Null
if ($LASTEXITCODE -eq 0) {
    Write-Host "  ✅ Changes staged" -ForegroundColor Green
} else {
    Write-Host "  ⚠️  Staging may have issues" -ForegroundColor Yellow
}

# Step 3: Commit
Write-Host ""
Write-Host "[3/5] Committing..." -ForegroundColor Yellow
$commitOutput = git commit -m "Fix Docker build: Create bootstrap/cache directory before chmod" 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "  ✅ Committed" -ForegroundColor Green
} else {
    if ($commitOutput -match "nothing to commit") {
        Write-Host "  ℹ️  No changes to commit (already committed)" -ForegroundColor Cyan
    } else {
        Write-Host "  ⚠️  Commit may have issues" -ForegroundColor Yellow
        Write-Host $commitOutput
    }
}

# Step 4: Push
Write-Host ""
Write-Host "[4/5] Pushing to GitHub..." -ForegroundColor Yellow
$pushOutput = git push 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "  ✅ Pushed successfully" -ForegroundColor Green
} else {
    Write-Host "  ⚠️  Push may have issues" -ForegroundColor Yellow
    Write-Host $pushOutput
}

# Step 5: Wait and fetch logs
Write-Host ""
Write-Host "[5/5] Waiting 60 seconds for CI/CD to start..." -ForegroundColor Yellow
Start-Sleep -Seconds 60

Write-Host ""
Write-Host "Fetching CI/CD logs..." -ForegroundColor Yellow
php scripts/fetch-and-fix-ci.php

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Process Complete" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Check GitHub Actions to verify build status" -ForegroundColor Yellow

