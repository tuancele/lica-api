# Deployment Guide - Crawl Improvements

## 📋 Pre-Deployment Checklist

### ✅ Code Ready
- [x] Code improvements đã được implement
- [x] No linter errors
- [x] All methods tested in code review
- [x] Logging đã được thêm đầy đủ

### ⚠️ Before Deployment
- [ ] Backup database (optional but recommended)
- [ ] Check current running jobs (nếu có job đang chạy, chờ hoàn thành)
- [ ] Verify queue worker status
- [ ] Check disk space cho logs

## 🚀 Deployment Steps

### Step 1: Stop Queue Worker (nếu đang chạy)

```bash
# Tìm process queue worker
ps aux | grep "queue:work"

# Hoặc trên Windows (PowerShell)
Get-Process | Where-Object {$_.ProcessName -like "*php*" -and $_.CommandLine -like "*queue:work*"}

# Kill process (thay PID bằng process ID thực tế)
kill <PID>

# Hoặc graceful stop (nếu queue worker hỗ trợ)
# Ctrl+C trong terminal đang chạy queue worker
```

### Step 2: Clear Application Cache

```bash
# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Clear application cache
php artisan cache:clear

# Clear opcache (nếu có)
php artisan opcache:clear
```

### Step 3: Verify Code Changes

```bash
# Check file đã được update
git status

# Hoặc check file trực tiếp
cat app/Jobs/DictionaryIngredientCrawlJob.php | grep "loadMappingMaps"
```

### Step 4: Restart Queue Worker

```bash
# Start queue worker với queue name
php artisan queue:work --queue=dictionary-crawl --tries=3 --timeout=3600

# Hoặc với supervisor/systemd (production)
# Supervisor config sẽ tự động restart
```

### Step 5: Test Deployment

1. **Test với batch nhỏ**:
   - Truy cập: `https://lica.test/admin/dictionary/ingredient/crawl`
   - Chọn offset nhỏ (ví dụ: 0-2000)
   - Click "Lay du lieu"
   - Monitor logs

2. **Verify improvements**:
   ```bash
   # Check log có "map loaded"
   tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep "map loaded"
   
   # Check mapping success rate
   tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep "mapping details"
   ```

## 📊 Post-Deployment Verification

### Expected Logs

Sau khi deploy, bạn sẽ thấy các log mới:

1. **Map Loading**:
   ```
   DictionaryIngredientCrawlJob rate map loaded
   DictionaryIngredientCrawlJob category map loaded
   DictionaryIngredientCrawlJob benefit map loaded
   ```

2. **Mapping Details** (mỗi 50 items):
   ```
   DictionaryIngredientCrawlJob updateFromRemote mapping details
   ```

3. **Improved Performance**:
   - Mapping queries giảm từ N×3 xuống 3
   - Processing time có thể giảm nhẹ

### Metrics to Monitor

1. **Mapping Success Rate**:
   - Rate mapping: 0% → 70-90% (expected)
   - Category mapping: <1% → 50-80% (expected)
   - Benefit mapping: <1% → 50-80% (expected)

2. **Performance**:
   - Processing time: ~410ms → ~380-400ms (expected)
   - Database queries: Giảm đáng kể

3. **Error Rate**:
   - Vẫn giữ 0% (không có lỗi)

## 🔧 Troubleshooting

### Issue 1: Queue Worker không nhận code mới

**Solution**:
```bash
# Restart queue worker
# Clear cache
php artisan cache:clear
php artisan config:clear
```

### Issue 2: Mapping vẫn không hoạt động

**Check**:
1. Verify log có "map loaded"
2. Check database có data trong `ingredient_rate`, `ingredient_category`, `ingredient_benefit`
3. Check log "mapping details" để xem raw data từ API

**Solution**:
- Nếu không có "map loaded" → Code chưa được load
- Nếu có "map loaded" nhưng mapping fail → Cần analyze log để fix mapping logic

### Issue 3: Performance không cải thiện

**Check**:
1. Verify static maps được load (check log)
2. Check processing time trong log
3. Verify không có N+1 queries

**Solution**:
- Nếu maps không load → Check code deployment
- Nếu vẫn chậm → Có thể do network latency (không liên quan đến code)

## 📝 Quick Deploy Script

Tạo file `deploy_crawl.sh` (Linux/Mac) hoặc `deploy_crawl.bat` (Windows):

### Linux/Mac (deploy_crawl.sh)
```bash
#!/bin/bash

echo "🚀 Deploying Crawl Improvements..."

# Stop queue worker
echo "⏹️  Stopping queue worker..."
pkill -f "queue:work.*dictionary-crawl" || true

# Clear cache
echo "🧹 Clearing cache..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Wait a bit
sleep 2

# Start queue worker
echo "▶️  Starting queue worker..."
php artisan queue:work --queue=dictionary-crawl --tries=3 --timeout=3600 > /dev/null 2>&1 &

echo "✅ Deployment complete!"
echo "📊 Monitor logs: tail -f storage/logs/laravel-$(date +%Y-%m-%d).log"
```

### Windows (deploy_crawl.bat)
```batch
@echo off
echo 🚀 Deploying Crawl Improvements...

echo ⏹️  Stopping queue worker...
taskkill /F /FI "WINDOWTITLE eq *queue:work*dictionary-crawl*" 2>nul

echo 🧹 Clearing cache...
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

timeout /t 2 /nobreak >nul

echo ▶️  Starting queue worker...
start "Queue Worker" php artisan queue:work --queue=dictionary-crawl --tries=3 --timeout=3600

echo ✅ Deployment complete!
echo 📊 Monitor logs in storage\logs\laravel-%date:~-4,4%-%date:~-7,2%-%date:~-10,2%.log
pause
```

## 🎯 Deployment Commands (Quick Reference)

```bash
# 1. Stop queue worker
pkill -f "queue:work.*dictionary-crawl"

# 2. Clear cache
php artisan config:clear && php artisan cache:clear

# 3. Start queue worker
php artisan queue:work --queue=dictionary-crawl --tries=3 --timeout=3600 &

# 4. Monitor logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep "DictionaryIngredientCrawlJob"
```

## ✅ Post-Deployment Checklist

- [ ] Queue worker đã restart
- [ ] Log có "map loaded" messages
- [ ] Test crawl với batch nhỏ thành công
- [ ] Mapping success rate cải thiện
- [ ] Không có errors trong log
- [ ] Performance metrics tốt

## 📞 Support

Nếu gặp vấn đề:
1. Check logs: `storage/logs/laravel-YYYY-MM-DD.log`
2. Verify code: `app/Jobs/DictionaryIngredientCrawlJob.php`
3. Check queue status: `php artisan queue:work --help`










