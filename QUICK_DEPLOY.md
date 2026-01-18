# Quick Deploy Guide - Flash Sale API

## 🚀 Deploy trong 5 phút

### Option 1: Sử dụng Deployment Script (Khuyến nghị)

#### Windows:
```cmd
deploy_flash_sale.bat production
```

#### Linux/Mac:
```bash
chmod +x deploy_flash_sale.sh
./deploy_flash_sale.sh production
```

### Option 2: Deploy Thủ Công

#### 1. Backup Database (QUAN TRỌNG!)
```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

#### 2. Enable Maintenance Mode
```bash
php artisan down --message="Deploying Flash Sale API" --retry=60
```

#### 3. Pull Code & Install Dependencies
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
```

#### 4. Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

#### 5. Run Migration
```bash
php artisan migrate --force
```

#### 6. Optimize
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 7. Verify Routes
```bash
php artisan route:list --path=api/v1/flash-sales
php artisan route:list --path=admin/api/flash-sales
```

#### 8. Disable Maintenance Mode
```bash
php artisan up
```

#### 9. Test
```bash
# Test Public API
curl http://your-domain.com/api/v1/flash-sales/active

# Test Admin Panel
# Login và tạo Flash Sale với variants
```

---

## ✅ Quick Verification

### 1. Check Database
```sql
DESCRIBE productsales;
-- Should see variant_id column
```

### 2. Check Routes
```bash
php artisan route:list | grep flash-sales
-- Should see 9 routes total
```

### 3. Check Logs
```bash
tail -f storage/logs/laravel.log
-- Should see no errors
```

---

## 🆘 Nếu Có Lỗi

### Rollback Migration
```bash
php artisan migrate:rollback --step=1
```

### Rollback Code
```bash
git reset --hard PREVIOUS_COMMIT
php artisan optimize:clear
```

### Restore Database
```bash
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql
```

---

## 📞 Cần Giúp Đỡ?

Xem chi tiết trong:
- `FLASH_SALE_DEPLOYMENT_GUIDE.md` - Hướng dẫn đầy đủ
- `DEPLOYMENT_CHECKLIST.md` - Checklist chi tiết

---

**Good luck với deployment! 🚀**
