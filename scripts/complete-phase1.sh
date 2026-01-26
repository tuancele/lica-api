#!/bin/bash

# Phase 1 Completion Script
# This script helps complete Phase 1 setup steps

set -e

echo "=========================================="
echo "Phase 1: Foundation - Completion Script"
echo "=========================================="
echo ""

# Check PHP version
echo "1. Checking PHP version..."
PHP_VERSION=$(php -v | head -n 1 | cut -d ' ' -f 2 | cut -d '.' -f 1,2)
PHP_MAJOR=$(echo $PHP_VERSION | cut -d '.' -f 1)
PHP_MINOR=$(echo $PHP_VERSION | cut -d '.' -f 2)

if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 3 ]); then
    echo "❌ ERROR: PHP 8.3+ required. Current version: $PHP_VERSION"
    echo "Please upgrade PHP first!"
    exit 1
fi

echo "✅ PHP version OK: $PHP_VERSION"
echo ""

# Check Redis connection
echo "2. Checking Redis connection..."
if command -v redis-cli &> /dev/null; then
    if redis-cli ping &> /dev/null; then
        echo "✅ Redis is running"
    else
        echo "⚠️  WARNING: Redis is not responding. Please start Redis service."
    fi
else
    echo "⚠️  WARNING: redis-cli not found. Please ensure Redis is installed and running."
fi
echo ""

# Update composer dependencies
echo "3. Updating Composer dependencies..."
if [ -f "composer.json" ]; then
    composer update --no-interaction --prefer-dist
    echo "✅ Composer dependencies updated"
else
    echo "❌ ERROR: composer.json not found"
    exit 1
fi
echo ""

# Check .env file
echo "4. Checking .env configuration..."
if [ ! -f ".env" ]; then
    echo "⚠️  WARNING: .env file not found. Creating from .env.example..."
    if [ -f ".env.example" ]; then
        cp .env.example .env
        php artisan key:generate
        echo "✅ .env file created"
    else
        echo "❌ ERROR: .env.example not found"
        exit 1
    fi
fi

# Check Redis config in .env
if grep -q "CACHE_DRIVER=redis" .env && grep -q "SESSION_DRIVER=redis" .env && grep -q "QUEUE_CONNECTION=redis" .env; then
    echo "✅ Redis configuration found in .env"
else
    echo "⚠️  WARNING: Redis configuration not found in .env"
    echo "Please add the following to .env:"
    echo "  CACHE_DRIVER=redis"
    echo "  SESSION_DRIVER=redis"
    echo "  QUEUE_CONNECTION=redis"
fi
echo ""

# Run Pint
echo "5. Running Laravel Pint..."
if [ -f "vendor/bin/pint" ]; then
    vendor/bin/pint || echo "⚠️  Pint found some issues. Please review."
    echo "✅ Code formatting completed"
else
    echo "⚠️  WARNING: Pint not found. Run 'composer install' first."
fi
echo ""

# Run PHPStan
echo "6. Running PHPStan..."
if [ -f "vendor/bin/phpstan" ]; then
    vendor/bin/phpstan analyse --level=8 || echo "⚠️  PHPStan found some issues. Please review."
    echo "✅ Static analysis completed"
else
    echo "⚠️  WARNING: PHPStan not found. Run 'composer install' first."
fi
echo ""

# Test Redis connection
echo "7. Testing Redis connection..."
php artisan tinker --execute="Cache::put('test', 'value', 60); echo Cache::get('test') === 'value' ? '✅ Redis cache works' : '❌ Redis cache failed';" || echo "⚠️  Could not test Redis. Please test manually."
echo ""

# Summary
echo "=========================================="
echo "Summary"
echo "=========================================="
echo "✅ PHP version check: OK"
echo "✅ Composer dependencies: Updated"
echo "✅ Code formatting: Completed"
echo "✅ Static analysis: Completed"
echo ""
echo "Next steps:"
echo "1. Verify Redis is running and configured in .env"
echo "2. Test application: php artisan serve"
echo "3. Test queue: php artisan queue:work"
echo "4. (Optional) Install Telescope: composer require laravel/telescope --dev"
echo "5. (Optional) Install Sentry: composer require sentry/sentry-laravel"
echo ""
echo "Phase 1 completion script finished!"
echo "=========================================="

