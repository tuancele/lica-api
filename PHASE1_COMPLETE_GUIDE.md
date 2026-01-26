# Phase 1: Hướng Dẫn Hoàn Thành

**Ngày:** 2025-01-21  
**Mục đích:** Hướng dẫn start Redis, test connection, test queue và verify CI/CD

---

## 🚀 Quick Start

### Bước 1: Start Redis Service

#### Option A: Sử dụng Laragon (Khuyến nghị)
1. Mở **Laragon**
2. Click menu **Services** (hoặc icon Services trên toolbar)
3. Tìm **Redis** trong danh sách
4. Click **Start** (icon sẽ chuyển sang màu xanh khi chạy)

#### Option B: Sử dụng Script
```bash
scripts\start-redis-and-test.bat
```

#### Option C: Sử dụng Docker
```bash
docker-compose up -d redis
```

#### Option D: Command Line (nếu Redis đã cài)
```bash
redis-server
```

---

### Bước 2: Test Redis Connection

#### Sử dụng Script (Tự động):
```bash
scripts\start-redis-and-test.bat
```

#### Hoặc Test Thủ Công:
```bash
php artisan tinker
```

Trong Tinker:
```php
// Test Cache
Cache::put('test_key', 'test_value', 60);
Cache::get('test_key'); // Should return 'test_value'

// Test Redis Connection
Redis::connection()->ping(); // Should return 'PONG'

// Test Session (nếu có)
Session::put('test', 'session_value');
Session::get('test'); // Should return 'session_value'
```

**Kết quả mong đợi:**
- ✅ Cache put/get: Thành công
- ✅ Redis ping: Trả về 'PONG'
- ✅ Không có lỗi connection

---

### Bước 3: Test Queue

#### Sử dụng Script (Tự động):
```bash
scripts\test-queue.bat
```

#### Hoặc Test Thủ Công:

**3.1. Dispatch Test Job:**
```bash
php artisan tinker
```

Trong Tinker:
```php
use App\Jobs\TestQueueJob;
dispatch(new TestQueueJob());
```

**3.2. Start Queue Worker:**
```bash
php artisan queue:work --verbose
```

**Kết quả mong đợi:**
- ✅ Job được dispatch thành công
- ✅ Queue worker nhận và xử lý job
- ✅ Không có lỗi

**Lưu ý:** Queue worker sẽ chạy liên tục, nhấn `Ctrl+C` để dừng.

---

### Bước 4: Verify CI/CD trên GitHub

#### 4.1. Kiểm Tra File CI/CD

File `.github/workflows/ci.yml` đã có và cấu hình đầy đủ:
- ✅ Tests job với MySQL và Redis services
- ✅ Code quality checks (Pint, PHPStan)
- ✅ Docker build job
- ✅ PHP 8.3 setup

#### 4.2. Push Code Lên GitHub

```bash
# Kiểm tra git status
git status

# Add các thay đổi
git add .

# Commit
git commit -m "Phase 1: Complete setup - Redis, Queue, CI/CD"

# Push lên GitHub
git push origin main
# hoặc
git push origin develop
```

#### 4.3. Kiểm Tra Workflow trên GitHub

1. Mở repository trên GitHub
2. Click tab **Actions**
3. Tìm workflow run mới nhất
4. Click vào workflow run để xem chi tiết

**Kết quả mong đợi:**
- ✅ Workflow chạy thành công
- ✅ Tests job pass
- ✅ Code quality checks pass (hoặc có errors như mong đợi)
- ✅ Docker build thành công (nếu push lên main branch)

---

## 📋 Checklist Hoàn Thành

### Redis:
- [ ] Redis service đang chạy
- [ ] Cache test thành công
- [ ] Redis ping thành công
- [ ] Session test thành công (nếu có)

### Queue:
- [ ] Redis service đang chạy
- [ ] Job có thể dispatch
- [ ] Queue worker có thể start
- [ ] Job được xử lý thành công

### CI/CD:
- [ ] File `.github/workflows/ci.yml` đã có
- [ ] Code đã được push lên GitHub
- [ ] Workflow chạy trên GitHub
- [ ] Tests pass trong CI
- [ ] Code quality checks chạy

---

## 🔧 Troubleshooting

### Redis Connection Failed

**Lỗi:**
```
No connection could be made because the target machine actively refused it [tcp://127.0.0.1:6379]
```

**Giải pháp:**
1. Kiểm tra Redis service có đang chạy không:
   ```bash
   netstat -an | findstr :6379
   ```
2. Nếu không có output, start Redis service
3. Kiểm tra `.env` có đúng config không:
   ```
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   ```

### Queue Worker Không Nhận Job

**Giải pháp:**
1. Đảm bảo Redis đang chạy
2. Kiểm tra `QUEUE_CONNECTION=redis` trong `.env`
3. Clear queue cache:
   ```bash
   php artisan queue:clear
   ```
4. Restart queue worker

### CI/CD Workflow Không Chạy

**Giải pháp:**
1. Kiểm tra file `.github/workflows/ci.yml` có tồn tại không
2. Kiểm tra branch name (phải là `main` hoặc `develop`)
3. Kiểm tra syntax YAML có đúng không
4. Xem Actions tab trên GitHub để xem lỗi chi tiết

---

## 📊 Scripts Đã Tạo

1. **`scripts/start-redis-and-test.bat`**
   - Hướng dẫn start Redis
   - Tự động test Redis connection
   - Test Cache và Redis ping

2. **`scripts/test-queue.bat`**
   - Kiểm tra Redis đang chạy
   - Tạo test job (nếu chưa có)
   - Dispatch job và start queue worker

---

## ✅ Kết Quả Mong Đợi

Sau khi hoàn thành tất cả các bước:

1. **Redis:**
   - ✅ Service đang chạy
   - ✅ Connection test thành công
   - ✅ Cache hoạt động
   - ✅ Session hoạt động (nếu có)

2. **Queue:**
   - ✅ Job có thể dispatch
   - ✅ Queue worker xử lý job thành công

3. **CI/CD:**
   - ✅ Workflow chạy trên GitHub
   - ✅ Tests pass
   - ✅ Code quality checks chạy

---

## 📚 Tài Liệu Tham Khảo

- `PHASE1_TESTING_REPORT.md` - Báo cáo testing chi tiết
- `PHASE1_FINAL_REPORT.md` - Báo cáo tổng hợp Phase 1
- `PHASE1_PROGRESS_CHECK.md` - Báo cáo tiến độ
- Laravel Redis: https://laravel.com/docs/11.x/redis
- Laravel Queue: https://laravel.com/docs/11.x/queues
- GitHub Actions: https://docs.github.com/en/actions

---

**Cập nhật lần cuối:** 2025-01-21

