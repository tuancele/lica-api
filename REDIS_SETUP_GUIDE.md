# Redis Setup Guide - Giai Đoạn 1

**Mục tiêu:** Thiết lập Redis cho cache và sessions trong Laravel 11

---

## 1. Cài Đặt Redis Server

### Windows (Laragon)

Laragon đã có Redis sẵn. Kiểm tra:

```bash
# Kiểm tra Redis đã cài chưa
redis-cli ping
# Nếu trả về PONG thì Redis đã chạy
```

Nếu chưa có, cài đặt Redis cho Windows:
1. Download từ: https://github.com/microsoftarchive/redis/releases
2. Hoặc sử dụng WSL2 với Redis

### Linux/macOS

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install redis-server

# macOS (Homebrew)
brew install redis
brew services start redis

# Kiểm tra
redis-cli ping
```

---

## 2. Cấu Hình Laravel

### 2.1 Cài Đặt Predis Package

```bash
composer require predis/predis
```

### 2.2 Cấu Hình .env

Thêm/sửa các dòng sau trong `.env`:

```env
# Cache Driver
CACHE_DRIVER=redis
CACHE_PREFIX=lica_cache

# Session Driver
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis Connection
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Redis Database (0-15)
REDIS_DB=0
REDIS_CACHE_DB=1
```

### 2.3 Cấu Hình config/database.php

Đảm bảo có Redis connection trong `config/database.php`:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'predis'),
    
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
    
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
    ],
],
```

---

## 3. Kiểm Tra Kết Nối

### 3.1 Test Redis Connection

Tạo file test: `tests/Feature/RedisConnectionTest.php`

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RedisConnectionTest extends TestCase
{
    public function test_redis_connection(): void
    {
        try {
            Redis::connection()->ping();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Redis connection failed: ' . $e->getMessage());
        }
    }
    
    public function test_cache_works(): void
    {
        Cache::put('test_key', 'test_value', 60);
        $this->assertEquals('test_value', Cache::get('test_key'));
    }
}
```

Chạy test:
```bash
php artisan test --filter RedisConnectionTest
```

### 3.2 Test Thủ Công

```bash
# Từ Laravel Tinker
php artisan tinker

# Test cache
Cache::put('test', 'value', 60);
Cache::get('test');

# Test Redis trực tiếp
Redis::set('test', 'value');
Redis::get('test');
```

---

## 4. Cấu Hình Queue với Redis

### 4.1 Update .env

```env
QUEUE_CONNECTION=redis
```

### 4.2 Test Queue

```bash
# Tạo test job
php artisan make:job TestRedisQueue

# Dispatch job
php artisan tinker
dispatch(new \App\Jobs\TestRedisQueue());

# Chạy queue worker
php artisan queue:work redis
```

---

## 5. Production Setup

### 5.1 Redis Persistence

Cấu hình Redis để lưu dữ liệu:

```conf
# redis.conf
save 900 1
save 300 10
save 60 10000
appendonly yes
```

### 5.2 Redis Security

```env
# Production .env
REDIS_PASSWORD=your_strong_password_here
```

### 5.3 Redis Monitoring

Sử dụng Redis CLI để monitor:

```bash
redis-cli
> INFO stats
> MONITOR
```

---

## 6. Troubleshooting

### Lỗi: Connection refused

```bash
# Kiểm tra Redis đang chạy
redis-cli ping

# Nếu không, start Redis
# Windows (Laragon): Start từ Laragon menu
# Linux: sudo systemctl start redis
# macOS: brew services start redis
```

### Lỗi: Predis not found

```bash
composer require predis/predis
```

### Lỗi: Permission denied

```bash
# Kiểm tra Redis user
sudo chown -R redis:redis /var/lib/redis
```

---

## 7. Performance Tuning

### 7.1 Redis Memory

```conf
# redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

### 7.2 Laravel Cache Prefix

```env
CACHE_PREFIX=lica_prod_cache
```

---

## 8. Checklist

- [ ] Redis server đã cài và chạy
- [ ] `predis/predis` package đã cài
- [ ] `.env` đã cấu hình Redis
- [ ] `config/database.php` có Redis connection
- [ ] Test connection thành công
- [ ] Cache hoạt động với Redis
- [ ] Session hoạt động với Redis
- [ ] Queue hoạt động với Redis
- [ ] Production security đã setup

---

**Last Updated:** 2025-01-21

