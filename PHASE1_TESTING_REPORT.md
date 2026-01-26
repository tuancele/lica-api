# Phase 1: Testing Report - Redis, Queue & CI/CD

**Ngày test:** 2025-01-21  
**Mục đích:** Kiểm tra Redis connection, Queue và CI/CD pipeline

---

## 📊 Tổng Quan Kết Quả

| Hạng Mục | Trạng Thái | Ghi Chú |
|----------|------------|---------|
| **Redis Connection** | ⚠️ Redis service chưa chạy | Cần start Redis service |
| **Queue Test** | ⏳ Chờ Redis | Phụ thuộc vào Redis |
| **CI/CD Pipeline** | ✅ Đã có file | `.github/workflows/ci.yml` tồn tại |

---

## 1. Redis Connection Test ⚠️

### Kết Quả:
```
Predis\Connection\Resource\Exception\StreamInitException  
No connection could be made because the target machine actively refused it [tcp://127.0.0.1:6379].
```

### Phân Tích:
- Redis service **chưa đang chạy** trên port 6379
- Config đã đúng: `CACHE_DRIVER=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`
- Laravel đã được cấu hình để sử dụng Redis

### Cách Khắc Phục:

#### Option 1: Start Redis trong Laragon (Khuyến nghị)
1. Mở Laragon
2. Click vào menu **Services**
3. Tìm **Redis** và click **Start**
4. Verify: Redis icon sẽ chuyển sang màu xanh

#### Option 2: Start Redis bằng Command Line
```bash
# Nếu Redis đã được cài đặt
redis-server

# Hoặc nếu dùng Laragon
# Redis thường nằm trong: C:\laragon\bin\redis\redis-server.exe
```

#### Option 3: Dùng Docker (Nếu đã setup)
```bash
docker-compose up -d redis
```

### Test Sau Khi Start Redis:
```bash
php artisan tinker
```

Trong Tinker:
```php
// Test Cache
Cache::put('test', 'value', 60);
Cache::get('test'); // Should return 'value'

// Test Redis connection
Redis::connection()->ping(); // Should return 'PONG'

// Test Session (nếu có)
Session::put('test', 'session_value');
Session::get('test'); // Should return 'session_value'
```

---

## 2. Queue Test ⏳

### Trạng Thái:
- ⏳ **Chưa thể test** - Phụ thuộc vào Redis service

### Config Đã Đúng:
- ✅ `config/queue.php` - Default connection: `redis`
- ✅ `QUEUE_CONNECTION=redis` trong `.env`
- ✅ Queue config đã được cấu hình đúng

### Test Sau Khi Start Redis:

#### Bước 1: Tạo Test Job (nếu chưa có)
```bash
php artisan make:job TestQueueJob
```

#### Bước 2: Dispatch Job
```bash
php artisan tinker
```

Trong Tinker:
```php
use App\Jobs\TestQueueJob;
dispatch(new TestQueueJob());
```

#### Bước 3: Start Queue Worker
```bash
php artisan queue:work
```

**Lưu ý:** Queue worker sẽ chạy liên tục, nhấn `Ctrl+C` để dừng.

#### Bước 4: Kiểm Tra Queue
- Kiểm tra trong Redis: `redis-cli` → `KEYS *`
- Kiểm tra failed jobs: `php artisan queue:failed`

### Queue Commands Hữu Ích:
```bash
# Start queue worker
php artisan queue:work

# Start queue worker với verbose
php artisan queue:work --verbose

# Process specific queue
php artisan queue:work --queue=high,default

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

---

## 3. CI/CD Pipeline ✅

### Kết Quả:
- ✅ **File tồn tại:** `.github/workflows/ci.yml`

### Cần Kiểm Tra:
- [ ] Nội dung file CI/CD có đúng không
- [ ] Workflow có chạy được không
- [ ] Tests có được chạy trong CI không

### Các Bước Tiếp Theo:

1. **Review CI/CD file:**
   ```bash
   cat .github/workflows/ci.yml
   ```

2. **Test workflow locally (nếu có act):**
   ```bash
   # Cài act (GitHub Actions local runner)
   # https://github.com/nektos/act
   act -l
   ```

3. **Push lên GitHub để test:**
   - Commit và push code
   - Kiểm tra Actions tab trên GitHub
   - Xem workflow có chạy không

### CI/CD Best Practices Cần Có:

- ✅ PHP version: 8.3+
- ✅ Composer install
- ✅ Environment setup
- ✅ Database migrations
- ✅ Run tests
- ✅ Code quality checks (Pint, PHPStan)
- ✅ Build artifacts (nếu cần)

---

## 📋 Checklist Hoàn Thành

### Redis:
- [x] Config đã đúng (`config/cache.php`, `config/session.php`, `config/queue.php`)
- [x] `.env` đã cập nhật (`CACHE_DRIVER=redis`, etc.)
- [ ] Redis service đang chạy
- [ ] Cache test thành công
- [ ] Session test thành công
- [ ] Redis connection ping thành công

### Queue:
- [x] Config đã đúng (`config/queue.php`)
- [x] `.env` đã cập nhật (`QUEUE_CONNECTION=redis`)
- [ ] Redis service đang chạy
- [ ] Queue worker có thể start
- [ ] Job có thể dispatch
- [ ] Job có thể process

### CI/CD:
- [x] File `.github/workflows/ci.yml` tồn tại
- [ ] Nội dung file đúng
- [ ] Workflow có thể chạy
- [ ] Tests được chạy trong CI

---

## 🚀 Bước Tiếp Theo

### Ưu Tiên 1: Start Redis Service
1. Mở Laragon
2. Start Redis service
3. Test Redis connection
4. Test Queue

### Ưu Tiên 2: Review CI/CD
1. Đọc file `.github/workflows/ci.yml`
2. Verify workflow configuration
3. Test workflow (push lên GitHub hoặc dùng act)

### Ưu Tiên 3: Complete Testing
1. Test tất cả Redis features (cache, session)
2. Test queue với real jobs
3. Verify CI/CD pipeline chạy thành công

---

## 📝 Ghi Chú

1. **Redis là bắt buộc** cho cache, session và queue trong Phase 1
2. **Queue testing** cần Redis đang chạy
3. **CI/CD pipeline** có thể test sau khi push code lên GitHub
4. **Docker** có thể được dùng để chạy Redis nếu không có Laragon

---

## 🔗 Tài Liệu Tham Khảo

- Laravel Redis: https://laravel.com/docs/11.x/redis
- Laravel Queue: https://laravel.com/docs/11.x/queues
- GitHub Actions: https://docs.github.com/en/actions
- Laragon Redis: https://laragon.org/docs/redis.html

---

**Cập nhật lần cuối:** 2025-01-21

