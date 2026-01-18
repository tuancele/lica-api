# Flash Sale API - Production Deployment Checklist

## ⚠️ CRITICAL: Pre-Deployment

### 1. Backup Database (MANDATORY)
- [ ] **Backup database trước khi deploy**
  ```bash
  # MySQL
  mysqldump -u username -p database_name > backup_before_flashsale_$(date +%Y%m%d_%H%M%S).sql
  
  # Hoặc sử dụng Laravel backup
  php artisan backup:run
  ```
- [ ] Verify backup file exists và có kích thước hợp lý
- [ ] Store backup ở nơi an toàn (không phải trên server production)

### 2. Verify Current State
- [ ] Check current Laravel version: `php artisan --version`
- [ ] Check PHP version: `php -v` (should be 8.0+)
- [ ] Check database connection: `php artisan migrate:status`
- [ ] Check disk space: `df -h` (Linux) hoặc check manually (Windows)

### 3. Review Changes
- [ ] Review all changed files:
  - Migration: `database/migrations/2026_01_18_120338_add_variant_id_to_productsales_table.php`
  - Models: `app/Modules/FlashSale/Models/*.php`
  - Controllers: `app/Http/Controllers/Api/V1/FlashSaleController.php`
  - Resources: `app/Http/Resources/FlashSale/*.php`
  - Views: `app/Modules/FlashSale/Views/product_rows.blade.php`
- [ ] Verify no breaking changes to existing functionality

---

## 🚀 Deployment Steps

### Step 1: Enable Maintenance Mode
```bash
php artisan down --message="Deploying Flash Sale API updates" --retry=60
```
- [ ] Maintenance mode enabled
- [ ] Verify site shows maintenance page

### Step 2: Pull Latest Code
```bash
git pull origin main
# hoặc
git pull origin master
```
- [ ] Code pulled successfully
- [ ] No merge conflicts

### Step 3: Install Dependencies
```bash
composer install --no-dev --optimize-autoloader --no-interaction
```
- [ ] Dependencies installed
- [ ] No errors in output

### Step 4: Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```
- [ ] All caches cleared

### Step 5: Run Migration
```bash
php artisan migrate --force
```
- [ ] Migration completed successfully
- [ ] Verify: `php artisan migrate:status` shows migration as "Ran"

### Step 6: Verify Database Schema
```sql
DESCRIBE productsales;
-- Should show variant_id column
```
- [ ] `variant_id` column exists
- [ ] Type is `int(11) unsigned`
- [ ] Nullable is `YES`

### Step 7: Optimize Application
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
- [ ] All caches created successfully

### Step 8: Verify Routes
```bash
php artisan route:list --path=api/v1/flash-sales
php artisan route:list --path=admin/api/flash-sales
```
- [ ] Public API routes: 2 routes found
- [ ] Admin API routes: 7 routes found

### Step 9: Disable Maintenance Mode
```bash
php artisan up
```
- [ ] Maintenance mode disabled
- [ ] Site accessible again

---

## ✅ Post-Deployment Verification

### 1. API Endpoints Test

#### Public API (No Auth Required)
- [ ] `GET /api/v1/flash-sales/active`
  ```bash
  curl -X GET "https://your-domain.com/api/v1/flash-sales/active" -H "Accept: application/json"
  ```
  - Expected: 200 OK with JSON response
  - Check: `success: true`, `data` array exists

- [ ] `GET /api/v1/flash-sales/{id}/products`
  ```bash
  curl -X GET "https://your-domain.com/api/v1/flash-sales/1/products" -H "Accept: application/json"
  ```
  - Expected: 200 OK with products array
  - Check: Products include variant info if applicable

#### Admin API (Auth Required)
- [ ] `GET /admin/api/flash-sales`
  - Expected: 200 OK with list
  - Check: Requires authentication

- [ ] `POST /admin/api/flash-sales`
  - Expected: 201 Created
  - Check: Can create Flash Sale with variants

### 2. Admin Panel Test

- [ ] Login to Admin Panel: `/admin/flashsale`
- [ ] Create new Flash Sale:
  - [ ] Select product without variants → Works correctly
  - [ ] Select product with variants → Shows all variants
  - [ ] Set price for each variant → Saves correctly
  - [ ] Save Flash Sale → Success message

- [ ] Edit existing Flash Sale:
  - [ ] Load Flash Sale with variants → Shows all variants
  - [ ] Update variant prices → Saves correctly
  - [ ] Remove variant → Deletes correctly

### 3. Database Verification

```sql
-- Check old data still exists (variant_id = NULL)
SELECT COUNT(*) FROM productsales WHERE variant_id IS NULL;
-- Should return count > 0 if there's old data

-- Check new data can be created (variant_id IS NOT NULL)
SELECT * FROM productsales WHERE variant_id IS NOT NULL LIMIT 5;
-- Should return results if Flash Sale with variants was created
```

- [ ] Old data (variant_id = NULL) still exists
- [ ] New data (variant_id IS NOT NULL) can be created
- [ ] No data corruption

### 4. Performance Check

- [ ] API response time < 500ms (for simple queries)
- [ ] No slow queries in logs
- [ ] Database indexes working correctly

### 5. Error Monitoring

```bash
# Check for errors in logs
tail -f storage/logs/laravel.log
# or
grep -i "error\|exception" storage/logs/laravel-*.log
```

- [ ] No critical errors in logs
- [ ] No exceptions related to Flash Sale
- [ ] No database errors

---

## 🔄 Rollback Plan

If something goes wrong:

### Quick Rollback (Last 5 minutes)
```bash
# 1. Enable maintenance mode
php artisan down

# 2. Rollback migration
php artisan migrate:rollback --step=1

# 3. Revert code
git revert HEAD
# hoặc
git reset --hard PREVIOUS_COMMIT_HASH

# 4. Clear caches
php artisan optimize:clear

# 5. Disable maintenance mode
php artisan up
```

### Full Rollback (Restore from backup)
```bash
# 1. Enable maintenance mode
php artisan down

# 2. Restore database
mysql -u username -p database_name < backup_before_flashsale_YYYYMMDD_HHMMSS.sql

# 3. Revert code
git reset --hard PREVIOUS_COMMIT_HASH

# 4. Clear caches
php artisan optimize:clear

# 5. Disable maintenance mode
php artisan up
```

- [ ] Rollback plan documented
- [ ] Backup file accessible
- [ ] Previous commit hash noted

---

## 📊 Monitoring (First 24 Hours)

### Hour 1
- [ ] Monitor error logs every 15 minutes
- [ ] Check API response times
- [ ] Verify no user complaints

### Hour 2-4
- [ ] Monitor error logs every 30 minutes
- [ ] Check database performance
- [ ] Verify Admin Panel usage

### Hour 5-24
- [ ] Monitor error logs hourly
- [ ] Check for any anomalies
- [ ] Collect user feedback

---

## 📝 Deployment Log

**Deployment Date:** _______________
**Deployed By:** _______________
**Environment:** _______________
**Version/Commit:** _______________

**Issues Encountered:**
- 

**Resolution:**
- 

**Post-Deployment Notes:**
- 

---

## ✅ Sign-off

- [ ] All pre-deployment checks completed
- [ ] Deployment completed successfully
- [ ] All post-deployment tests passed
- [ ] No critical errors
- [ ] Team notified of deployment

**Deployed by:** _______________  
**Date:** _______________  
**Time:** _______________

---

**Last Updated:** 2025-01-18  
**Version:** 1.0
