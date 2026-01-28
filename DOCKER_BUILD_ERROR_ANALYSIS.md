# Docker Build Error Analysis - Phân Tích Lỗi CI/CD

**Ngày phân tích:** 2026-01-28  
**File log:** `docker-build-log/docker_build.log`

---

## 🔴 Lỗi Chính Đã Phát Hiện

### Error: `bootstrap/cache` directory not found

**Lỗi:**
```
#15 3.551 chmod: cannot access '/var/www/html/bootstrap/cache': No such file or directory
#15 ERROR: process "/bin/sh -c chown -R www-data:www-data /var/www/html     && chmod -R 755 /var/www/html/storage     && chmod -R 755 /var/www/html/bootstrap/cache" did not complete successfully: exit code: 1
```

**Nguyên nhân:**
- Dockerfile đang cố gắng chmod thư mục `bootstrap/cache` nhưng thư mục này không tồn tại
- Trong Laravel 11, thư mục `bootstrap/cache` có thể không được commit vào git
- Khi COPY files vào Docker image, thư mục này không có

**Vị trí lỗi:**
- Dockerfile dòng 35-37

---

## ✅ Giải Pháp

### Option 1: Tạo thư mục trước khi chmod (Khuyến nghị)

Sửa Dockerfile:
```dockerfile
# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache
```

### Option 2: Chmod với điều kiện (An toàn hơn)

Sửa Dockerfile:
```dockerfile
# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && ([ -d /var/www/html/bootstrap/cache ] && chmod -R 755 /var/www/html/bootstrap/cache || true)
```

### Option 3: Tạo thư mục trong .dockerignore và COPY

Đảm bảo `.dockerignore` không loại trừ `bootstrap/cache`, hoặc tạo thư mục trong Dockerfile.

---

## 📋 Phân Tích Chi Tiết

### Build Steps Thành Công

1. ✅ Load Dockerfile - OK
2. ✅ Load metadata for php:8.3-fpm - OK
3. ✅ Load metadata for composer:latest - OK
4. ✅ Load .dockerignore - OK
5. ✅ FROM php:8.3-fpm - OK
6. ✅ Install system dependencies - OK
7. ✅ Install PHP extensions - OK
8. ✅ Install Redis extension - OK (redis-6.3.0 installed successfully)
9. ✅ COPY composer - OK
10. ✅ COPY application files - OK
11. ❌ **Set permissions - FAILED** (bootstrap/cache not found)

### Build Context

- Build context size: 244.39MB (khá lớn)
- Có thể cần optimize với .dockerignore tốt hơn

---

## 🔧 Cách Sửa

### Bước 1: Sửa Dockerfile

Sửa dòng 35-37 trong Dockerfile:

**Trước:**
```dockerfile
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache
```

**Sau:**
```dockerfile
RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache
```

### Bước 2: Test Build Locally

```bash
docker build -t lica-backend:test .
```

### Bước 3: Commit và Push

```bash
git add Dockerfile
git commit -m "Fix Docker build: Create bootstrap/cache directory before chmod"
git push
```

---

## 📊 Tóm Tắt

| Vấn Đề | Trạng Thái | Giải Pháp |
|--------|------------|-----------|
| bootstrap/cache not found | ❌ Lỗi | Tạo thư mục trước khi chmod |
| Build context size (244MB) | ⚠️ Cảnh báo | Optimize với .dockerignore |
| Redis extension | ✅ OK | Đã install thành công |
| PHP extensions | ✅ OK | Đã install thành công |

---

## ✅ Sau Khi Sửa

Sau khi sửa Dockerfile, build sẽ thành công và CI/CD sẽ pass.

**Expected result:**
```
✅ Docker build successful
```

---

**File log:** `docker-build-log/docker_build.log`  
**Error line:** 1870-1875  
**Dockerfile line:** 35-37

