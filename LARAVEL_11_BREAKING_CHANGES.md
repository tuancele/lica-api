# Laravel 11 Breaking Changes Review

**Ngày Review:** 2025-01-21  
**Từ:** Laravel 10.50.0  
**Đến:** Laravel 11.x LTS

---

## Tổng Quan

Laravel 11 có nhiều thay đổi lớn về cấu trúc và cách tổ chức code. Dưới đây là các breaking changes chính cần xử lý:

---

## 1. Cấu Trúc Bootstrap (bootstrap/app.php)

### Laravel 10 (Hiện Tại):
```php
$app = new Illuminate\Foundation\Application(...);
$app->singleton(...);
return $app;
```

### Laravel 11 (Mới):
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware configuration
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Exception handling
    })->create();
```

**Action Required:**
- [ ] Tạo file `bootstrap/app.php` mới theo Laravel 11 structure
- [ ] Di chuyển middleware configuration
- [ ] Di chuyển exception handling

---

## 2. Service Providers

### Laravel 10:
- Service providers được đăng ký trong `config/app.php`
- `AppServiceProvider`, `AuthServiceProvider`, etc.

### Laravel 11:
- Service providers vẫn hoạt động nhưng có thể tối ưu hơn
- Một số providers có thể được merge vào `bootstrap/app.php`

**Action Required:**
- [ ] Review `AppServiceProvider` - có thể giữ nguyên
- [ ] Review `AuthServiceProvider` - có thể cần update
- [ ] Kiểm tra các custom service providers

---

## 3. Middleware Registration

### Laravel 10 (Http/Kernel.php):
```php
protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    ...
];
```

### Laravel 11:
- Middleware được đăng ký trong `bootstrap/app.php`
- Hoặc sử dụng `$middlewareAliases` trong Kernel (nếu giữ cấu trúc cũ)

**Action Required:**
- [ ] Di chuyển middleware từ `$routeMiddleware` sang `bootstrap/app.php`
- [ ] Hoặc update `Http/Kernel.php` để sử dụng `$middlewareAliases`

---

## 4. Exception Handling

### Laravel 10:
- Exception handling trong `app/Exceptions/Handler.php`

### Laravel 11:
- Có thể cấu hình trong `bootstrap/app.php`
- Hoặc giữ nguyên Handler.php

**Action Required:**
- [ ] Review `app/Exceptions/Handler.php`
- [ ] Kiểm tra xem có cần update không

---

## 5. Route Model Binding

### Laravel 10:
- Route model binding trong routes hoặc RouteServiceProvider

### Laravel 11:
- Có thể cấu hình trong `bootstrap/app.php`

**Action Required:**
- [ ] Kiểm tra route model binding hiện tại
- [ ] Update nếu cần

---

## 6. Config Files

### Các Config Files Có Thể Thay Đổi:
- `config/app.php` - Một số keys có thể thay đổi
- `config/auth.php` - Có thể có updates
- `config/cache.php` - Cần check Redis config
- `config/session.php` - Cần check Redis config

**Action Required:**
- [ ] Backup tất cả config files
- [ ] So sánh với Laravel 11 default configs
- [ ] Merge custom configs

---

## 7. Database & Migrations

### Laravel 11:
- Migrations vẫn hoạt động tương tự
- Có thể có một số thay đổi nhỏ về schema builder

**Action Required:**
- [ ] Test migrations trên Laravel 11
- [ ] Kiểm tra các custom migration methods

---

## 8. Dependencies Compatibility

### Packages Cần Kiểm Tra:

| Package | Version | Laravel 11 Compatible? | Notes |
|---------|---------|------------------------|-------|
| `laravel/framework` | ^10.0 | ❌ | Cần ^11.0 |
| `laravel/socialite` | ^5.0 | ⚠️ | Cần check version mới |
| `unisharp/laravel-filemanager` | ^2.12 | ⚠️ | Cần check Laravel 11 support |
| `drnxloc/laravel-simple-html-dom` | ^1.9 | ⚠️ | Cần check |
| `league/flysystem-aws-s3-v3` | ^3.0 | ✅ | Nên OK |
| `phpmailer/phpmailer` | ^6.4 | ✅ | Nên OK |
| `phpoffice/phpspreadsheet` | ^1.12 | ✅ | Nên OK |

**Action Required:**
- [ ] Check từng package compatibility
- [ ] Update packages nếu cần
- [ ] Tìm alternatives nếu không compatible

---

## 9. PHP 8.3 Features

Laravel 11 yêu cầu PHP 8.2+, khuyến nghị PHP 8.3+.

### PHP 8.3 Features Có Thể Sử Dụng:
- Typed class constants
- Readonly properties
- Override attribute
- Anonymous class readonly properties

**Action Required:**
- [ ] Update PHP lên 8.3+
- [ ] Test code với PHP 8.3
- [ ] Sử dụng PHP 8.3 features nếu có thể

---

## 10. Testing

### Laravel 11:
- PHPUnit 11.x
- Pest PHP 2.x (optional)

**Action Required:**
- [ ] Update PHPUnit nếu cần
- [ ] Test tất cả test cases
- [ ] Fix broken tests

---

## Migration Strategy

### Bước 1: Preparation
1. ✅ Backup codebase (git tag)
2. ⏳ Review breaking changes
3. ⏳ Check dependencies
4. ⏳ Create staging environment

### Bước 2: Upgrade
1. ⏳ Update composer.json
2. ⏳ Run composer update
3. ⏳ Fix breaking changes
4. ⏳ Update bootstrap/app.php
5. ⏳ Update middleware
6. ⏳ Update config files

### Bước 3: Testing
1. ⏳ Run migrations
2. ⏳ Test routes
3. ⏳ Test APIs
4. ⏳ Test admin panel
5. ⏳ Performance benchmark

---

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Breaking changes không được phát hiện | 🔴 High | Test kỹ lưỡng, staging environment |
| Dependencies không compatible | 🔴 High | Check trước, tìm alternatives |
| Performance regression | 🟡 Medium | Benchmark trước và sau |
| Data loss | 🔴 High | Backup database đầy đủ |

---

**Last Updated:** 2025-01-21

