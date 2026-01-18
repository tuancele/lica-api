#!/bin/bash

# Flash Sale API Deployment Script
# Usage: ./deploy_flash_sale.sh [environment]
# Example: ./deploy_flash_sale.sh production

set -e  # Exit on error

ENVIRONMENT=${1:-production}
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups"
LOG_FILE="deploy_${TIMESTAMP}.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

# Check if running as correct user (optional)
# if [ "$USER" != "www-data" ]; then
#     warning "Not running as www-data user"
# fi

log "Starting Flash Sale API deployment to $ENVIRONMENT"
log "Timestamp: $TIMESTAMP"

# Step 1: Pre-deployment checks
log "Step 1: Pre-deployment checks"

# Check if Laravel is installed
if [ ! -f "artisan" ]; then
    error "Laravel artisan file not found. Are you in the correct directory?"
fi

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    error "Composer is not installed"
fi

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    error "PHP is not installed"
fi

log "✓ Pre-deployment checks passed"

# Step 2: Backup database
log "Step 2: Creating database backup"

if [ -z "$DB_DATABASE" ]; then
    # Try to get from .env
    if [ -f ".env" ]; then
        DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    fi
fi

if [ -n "$DB_DATABASE" ]; then
    mkdir -p "$BACKUP_DIR"
    BACKUP_FILE="$BACKUP_DIR/flashsale_backup_${TIMESTAMP}.sql"
    
    if [ -n "$DB_PASSWORD" ]; then
        mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null || \
        warning "Database backup failed. Please backup manually!"
    else
        mysqldump -u "$DB_USERNAME" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null || \
        warning "Database backup failed. Please backup manually!"
    fi
    
    if [ -f "$BACKUP_FILE" ]; then
        log "✓ Database backup created: $BACKUP_FILE"
    fi
else
    warning "Database credentials not found. Please backup manually!"
fi

# Step 3: Enable maintenance mode
log "Step 3: Enabling maintenance mode"
php artisan down --message="Deploying Flash Sale API updates" --retry=60 || warning "Failed to enable maintenance mode"

# Step 4: Pull latest code (if using git)
if [ -d ".git" ]; then
    log "Step 4: Pulling latest code from git"
    git pull origin main || git pull origin master || warning "Git pull failed"
else
    log "Step 4: Skipping git pull (not a git repository)"
fi

# Step 5: Install/Update dependencies
log "Step 5: Installing dependencies"
if [ "$ENVIRONMENT" = "production" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction || error "Composer install failed"
else
    composer install --optimize-autoloader --no-interaction || error "Composer install failed"
fi

# Step 6: Clear caches
log "Step 6: Clearing caches"
php artisan config:clear || error "Failed to clear config cache"
php artisan cache:clear || error "Failed to clear cache"
php artisan route:clear || error "Failed to clear route cache"
php artisan view:clear || error "Failed to clear view cache"

# Step 7: Run migrations
log "Step 7: Running migrations"
php artisan migrate --force || error "Migration failed"

# Verify migration
log "Verifying migration..."
php artisan migrate:status | grep "add_variant_id_to_productsales_table" || warning "Migration verification failed"

# Step 8: Optimize
log "Step 8: Optimizing application"
php artisan config:cache || warning "Failed to cache config"
php artisan route:cache || warning "Failed to cache routes"
php artisan view:cache || warning "Failed to cache views"

# Step 9: Verify routes
log "Step 9: Verifying routes"
PUBLIC_ROUTES=$(php artisan route:list --path=api/v1/flash-sales | wc -l)
ADMIN_ROUTES=$(php artisan route:list --path=admin/api/flash-sales | wc -l)

if [ "$PUBLIC_ROUTES" -lt 2 ]; then
    warning "Public API routes may not be registered correctly"
fi

if [ "$ADMIN_ROUTES" -lt 7 ]; then
    warning "Admin API routes may not be registered correctly"
fi

log "✓ Found $PUBLIC_ROUTES public routes and $ADMIN_ROUTES admin routes"

# Step 10: Disable maintenance mode
log "Step 10: Disabling maintenance mode"
php artisan up || error "Failed to disable maintenance mode"

# Step 11: Final verification
log "Step 11: Final verification"

# Check if variant_id column exists
php artisan tinker --execute="
    try {
        \$hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('productsales', 'variant_id');
        echo \$hasColumn ? 'YES' : 'NO';
    } catch (\Exception \$e) {
        echo 'ERROR';
    }
" | grep -q "YES" && log "✓ variant_id column exists" || error "variant_id column not found"

# Summary
log ""
log "=========================================="
log "Deployment completed successfully!"
log "=========================================="
log "Timestamp: $TIMESTAMP"
log "Environment: $ENVIRONMENT"
log "Backup file: $BACKUP_FILE (if created)"
log "Log file: $LOG_FILE"
log ""
log "Next steps:"
log "1. Test API endpoints"
log "2. Test Admin Panel"
log "3. Monitor logs: tail -f storage/logs/laravel.log"
log ""
