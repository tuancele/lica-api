# Hướng Dẫn Restart Queue Worker

## ✅ Queue Worker Đã Được Start

Queue worker đã được start trong background với command:
```bash
php artisan queue:work --queue=dictionary-crawl --tries=3 --timeout=3600
```

## 📋 Các Cách Quản Lý Queue Worker

### Cách 1: Sử dụng Script (Recommended)

**Start Queue Worker:**
```bash
# Double-click file: start_queue_worker.bat
# Hoặc chạy trong terminal:
start_queue_worker.bat
```

**Restart Queue Worker:**
```bash
# Double-click file: restart_queue_worker.bat
# Hoặc chạy trong terminal:
restart_queue_worker.bat
```

### Cách 2: Manual Commands

**Start:**
```bash
php artisan queue:work --queue=dictionary-crawl --tries=3 --timeout=3600
```

**Stop:**
- Nhấn `Ctrl+C` trong terminal đang chạy queue worker
- Hoặc tìm process và kill:
```powershell
Get-Process php | Where-Object {$_.CommandLine -like "*queue:work*dictionary-crawl*"} | Stop-Process -Force
```

**Restart:**
1. Stop queue worker (Ctrl+C hoặc kill process)
2. Clear cache:
```bash
php artisan cache:clear
php artisan config:clear
```
3. Start lại:
```bash
php artisan queue:work --queue=dictionary-crawl --tries=3 --timeout=3600
```

## 🔍 Verify Queue Worker Đang Chạy

**Check process:**
```powershell
Get-Process php | Where-Object {$_.CommandLine -like "*queue:work*dictionary-crawl*"}
```

**Check logs:**
- Queue worker sẽ log vào `storage/logs/laravel-YYYY-MM-DD.log`
- Tìm log "DictionaryIngredientCrawlJob" để verify

## 📊 Queue Worker Status

Sau khi restart, queue worker sẽ:
1. ✅ Load mapping maps mới (bao gồm "Emollient")
2. ✅ Process jobs từ queue `dictionary-crawl`
3. ✅ Log chi tiết vào log file

## ⚠️ Lưu Ý

1. **Queue worker cần chạy liên tục** để process jobs
2. **Nếu stop queue worker**, jobs sẽ chờ trong queue
3. **Restart queue worker** sau khi:
   - Deploy code mới
   - Update database (như thêm category)
   - Clear cache

## 🎯 Next Steps

1. ✅ Queue worker đã được start
2. ⏳ Test crawl với batch nhỏ để verify improvements
3. ⏳ Monitor logs để check mapping success rate
4. ⏳ Verify "Emollient" mapping hoạt động

---

**Status**: ✅ Queue worker đã được start











