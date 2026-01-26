# Hướng Dẫn Start Redis và Test

**Ngày:** 2025-01-21

---

## ⚠️ Redis Service Chưa Chạy

Kết quả test: Redis connection bị từ chối trên port 6379.

---

## 🚀 Cách Start Redis

### Option 1: Sử dụng Laragon (Khuyến nghị cho Windows)

1. **Mở Laragon**
   - Tìm icon Laragon trên taskbar hoặc desktop
   - Click để mở ứng dụng

2. **Start Redis Service**
   - Trong Laragon, click menu **Services** (hoặc icon Services trên toolbar)
   - Tìm **Redis** trong danh sách services
   - Click **Start** (icon sẽ chuyển sang màu xanh khi chạy)

3. **Verify Redis đang chạy**
   - Icon Redis sẽ hiển thị màu xanh
   - Hoặc chạy: `netstat -an | findstr :6379` (sẽ có output)

### Option 2: Sử dụng Docker

```bash
# Start Redis container
docker-compose up -d redis

# Verify Redis đang chạy
docker ps | findstr redis
```

### Option 3: Command Line (nếu Redis đã cài đặt)

```bash
# Tìm Redis executable
where redis-server

# Start Redis (thường trong Laragon: C:\laragon\bin\redis\redis-server.exe)
redis-server

# Hoặc với config file
redis-server redis.conf
```

---

## ✅ Sau Khi Start Redis - Test Connection

### Sử dụng Script PHP:

```bash
php scripts\test-redis.php
```

### Hoặc Test Thủ Công:

```bash
php artisan tinker
```

Trong Tinker:
```php
// Test Cache
Cache::put('test', 'value', 60);
Cache::get('test'); // Should return 'value'

// Test Redis
Redis::connection()->ping(); // Should return 'PONG'
```

---

## 📋 Checklist

- [ ] Redis service đã được start
- [ ] Port 6379 đang listen (kiểm tra: `netstat -an | findstr :6379`)
- [ ] Cache test thành công
- [ ] Redis ping thành công
- [ ] Session test thành công (nếu có)

---

## 🔧 Troubleshooting

### Redis vẫn không kết nối được

1. **Kiểm tra port:**
   ```bash
   netstat -an | findstr :6379
   ```
   Nếu không có output, Redis chưa chạy.

2. **Kiểm tra firewall:**
   - Windows Firewall có thể chặn port 6379
   - Thêm exception cho Redis

3. **Kiểm tra .env:**
   ```
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   ```

4. **Thử restart Redis:**
   - Stop Redis trong Laragon
   - Start lại Redis

---

## 🎯 Bước Tiếp Theo

Sau khi Redis đã chạy và test thành công:

1. ✅ Test Queue: `scripts\test-queue.bat`
2. ✅ Push code lên GitHub để verify CI/CD

---

**Lưu ý:** Redis phải chạy trước khi test queue và các tính năng khác sử dụng Redis.

