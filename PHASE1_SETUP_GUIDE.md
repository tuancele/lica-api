# Phase 1 Setup Guide - Backend V2 Upgrade

## Overview

This guide will help you complete Phase 1 of the Backend V2 upgrade plan. Phase 1 focuses on modernizing the core infrastructure.

## Prerequisites

- PHP 8.3+ installed
- Composer installed
- Docker & Docker Compose (for local development)
- Redis server (or use Docker)

## Step-by-Step Setup

### 1. Update Dependencies

```bash
# Update all composer packages
composer update

# Check for any breaking changes
composer why-not laravel/framework 11.0
```

### 2. Enable Strict Types

Run the script to add `declare(strict_types=1)` to all PHP files:

```bash
php scripts/add-strict-types.php
```

This will:
- Add `declare(strict_types=1);` after `<?php` in all PHP files
- Skip files in `app/Themes`, `vendor`, `storage`, and `bootstrap/cache`
- Show summary of processed files

**Note:** After running, you may need to fix type errors. Run PHPStan to identify issues:

```bash
composer phpstan
```

### 3. Configure Redis

#### Update .env file

Add/update these variables:

```env
# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Use Redis for cache, sessions, and queues
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

#### Test Redis Connection

```bash
# Using Laravel Tinker
php artisan tinker

# Test cache
Cache::put('test', 'value', 60);
Cache::get('test'); // Should return 'value'

# Test Redis directly
Redis::connection()->ping(); // Should return 'PONG'
```

### 4. Setup Docker Environment (Optional but Recommended)

```bash
# Start all services
docker-compose up -d

# Check services are running
docker-compose ps

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

The Docker setup includes:
- PHP 8.3 FPM application
- Nginx web server (port 8080)
- MySQL 8.0 database (port 3307)
- Redis 7 (port 6379)
- Queue worker

### 5. Setup Code Quality Tools

#### Laravel Pint (Code Formatting)

```bash
# Format all files
composer pint

# Check formatting without changing files
composer pint:test
```

#### PHPStan (Static Analysis)

```bash
# Run PHPStan analysis
composer phpstan

# Fix errors reported by PHPStan
# Level 8 is configured in phpstan.neon
```

### 6. Setup Monitoring Tools

#### Laravel Telescope (Development)

```bash
# Install Telescope
composer require laravel/telescope --dev

# Publish assets
php artisan telescope:install

# Run migrations
php artisan migrate

# Access at: http://your-app.test/telescope
```

Add to `.env`:
```env
TELESCOPE_ENABLED=true
```

#### Sentry (Error Tracking)

```bash
# Install Sentry
composer require sentry/sentry-laravel

# Publish config
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```

Add to `.env`:
```env
SENTRY_LARAVEL_DSN=your-sentry-dsn-here
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_ENVIRONMENT=production
```

### 7. Setup CI/CD Pipeline

The GitHub Actions workflow is already configured in `.github/workflows/ci.yml`.

It will automatically:
- Run tests on push/PR
- Check code quality (Pint, PHPStan)
- Build Docker image on main branch

Make sure to:
- Enable GitHub Actions in repository settings
- Add required secrets if needed

### 8. Verify Everything Works

#### Test Redis

```bash
php artisan tinker
Cache::put('test', 'works', 60);
Cache::get('test'); // Should return 'works'
```

#### Test Queue

```bash
# Create a test job
php artisan make:job TestJob

# Dispatch it
php artisan tinker
dispatch(new \App\Jobs\TestJob());

# Process queue
php artisan queue:work
```

#### Test Sessions

1. Login to admin panel
2. Verify session persists
3. Check Redis: `redis-cli KEYS *session*`

#### Run Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage
```

## Verification Checklist

- [ ] All dependencies updated (`composer update`)
- [ ] Strict types added to all PHP files
- [ ] Redis configured and working
- [ ] Cache using Redis
- [ ] Sessions using Redis
- [ ] Queue using Redis
- [ ] Docker environment working (if using)
- [ ] Code formatted with Pint
- [ ] PHPStan passes (or errors documented)
- [ ] Telescope installed and accessible
- [ ] Sentry configured (if using)
- [ ] CI/CD pipeline working
- [ ] All tests passing

## Troubleshooting

### Redis Connection Failed

```bash
# Check Redis is running
redis-cli ping

# Check Redis config in .env
# Make sure REDIS_HOST and REDIS_PORT are correct
```

### Queue Not Processing

```bash
# Make sure queue worker is running
php artisan queue:work

# Or with Docker
docker-compose exec queue php artisan queue:work
```

### PHPStan Errors

```bash
# Run PHPStan to see errors
composer phpstan

# Fix errors one by one
# Some may be false positives - add to phpstan.neon ignoreErrors
```

### Docker Issues

```bash
# Rebuild containers
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Check logs
docker-compose logs app
docker-compose logs nginx
docker-compose logs mysql
docker-compose logs redis
```

## Next Steps

After completing Phase 1:

1. Review `PHASE1_COMPLETION_CHECKLIST.md` to ensure everything is done
2. Document any issues or deviations
3. Proceed to Phase 2: Architecture Refactoring

## Resources

- [Laravel 11 Documentation](https://laravel.com/docs/11.x)
- [PHP 8.3 Features](https://www.php.net/releases/8.3/en.php)
- [Redis Documentation](https://redis.io/docs/)
- [Laravel Telescope](https://laravel.com/docs/telescope)
- [Sentry Laravel](https://docs.sentry.io/platforms/php/guides/laravel/)

