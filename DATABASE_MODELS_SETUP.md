# Database & Models Setup Summary

**Date:** 2025-01-21  
**Status:** ✅ Completed

## Overview

Đã cấu hình đầy đủ database migrations và models cho tất cả các API modules.

## Migrations Created

### New Tables Created

1. **brands** - Brands management
   - Fields: name, slug, content, image, banner, logo, gallery (JSON), seo_title, seo_description, status, sort, user_id
   - Indexes: status, sort, slug (unique)

2. **origins** - Origins management
   - Fields: name, slug, content, image, seo_title, seo_description, status, sort, user_id
   - Indexes: status, sort, slug (unique)

3. **promotions** - Promotions management
   - Fields: name, code (unique), value, unit, number, start, end, order_sale, endow, content, payment, status, sort, user_id
   - Indexes: code (unique), status, sort

4. **roles** - Roles management
   - Fields: name, description, status, user_id
   - Indexes: status

5. **picks** - Pick locations (warehouse locations)
   - Fields: name, address, tel, province_id, district_id, ward_id, cat_id, status, sort, user_id
   - Indexes: status, sort, province_id, district_id

6. **showrooms** - Showrooms management
   - Fields: name, image, address, phone, cat_id, status, sort, user_id
   - Indexes: status, sort, cat_id

7. **redirections** - URL redirections
   - Fields: link_from, link_to, type (301/302), status, user_id
   - Indexes: link_from, status

8. **searchs** - Search logs
   - Fields: name, status, sort, user_id
   - Indexes: status, sort

9. **subcribers** - Email subscribers
   - Fields: email (unique)
   - Indexes: email (unique)

10. **rates** - Product ratings/reviews
    - Fields: product_id, user_id, rate (1-5), comment, status
    - Indexes: product_id, user_id, status

11. **compares** - Product comparisons
    - Fields: store_id, name, brand, price, link, is_link, status, user_id
    - Indexes: store_id, status

### Existing Tables Updated

1. **configs** - Added key, value, group columns
   - Migration: `2026_01_26_003559_update_configs_table_add_key_value_group.php`
   - New fields: key (unique), value, group (default: 'general')
   - Data migration: code -> key, content -> value

### Existing Tables (Already Created)

- **posts** - Used for Categories, Pages, Posts, Videos, Downloads
- **medias** - Used for Banners, Sellings
- **members** - Members management
- **menus** - Menu management
- **footer_blocks** - Footer blocks
- **contacts** - Contact messages
- **feedbacks** - Feedback messages
- **tags** - Tags management
- **marketing_campaigns** - Marketing campaigns
- **marketing_campaign_products** - Campaign products

## Models Updated

### Models with Full Configuration

1. **Brand** (`app/Modules/Brand/Models/Brand.php`)
   - ✅ Fillable: name, slug, content, image, banner, logo, gallery, seo_title, seo_description, status, sort, user_id
   - ✅ Casts: gallery (array), status, sort
   - ✅ Relationships: user, product

2. **Origin** (`app/Modules/Origin/Models/Origin.php`)
   - ✅ Fillable: name, slug, content, image, seo_title, seo_description, status, sort, user_id
   - ✅ Casts: status, sort
   - ✅ Relationships: user

3. **Promotion** (`app/Modules/Promotion/Models/Promotion.php`)
   - ✅ Fillable: name, code, value, unit, number, start, end, order_sale, endow, content, payment, status, sort, user_id
   - ✅ Casts: value, order_sale (decimal), number, status, sort, start, end (datetime)
   - ✅ Relationships: user, order

4. **Role** (`app/Modules/Role/Models/Role.php`)
   - ✅ Fillable: name, description, status, user_id
   - ✅ Casts: status
   - ✅ Relationships: permissions, user

5. **Pick** (`app/Modules/Pick/Models/Pick.php`)
   - ✅ Fillable: name, address, tel, province_id, district_id, ward_id, cat_id, status, sort, user_id
   - ✅ Casts: status, sort, province_id, district_id, ward_id, cat_id
   - ✅ Relationships: user, ward, district, province

6. **Showroom** (`app/Modules/Showroom/Models/Showroom.php`)
   - ✅ Fillable: name, image, address, phone, cat_id, status, sort, user_id
   - ✅ Casts: status, sort, cat_id
   - ✅ Relationships: user

7. **Redirection** (`app/Modules/Redirection/Models/Redirection.php`)
   - ✅ Fillable: link_from, link_to, type, status, user_id
   - ✅ Casts: status
   - ✅ Relationships: user

8. **Search** (`app/Modules/Search/Models/Search.php`)
   - ✅ Fillable: name, status, sort, user_id
   - ✅ Casts: status, sort
   - ✅ Relationships: user

9. **Subcriber** (`app/Modules/Subcriber/Models/Subcriber.php`)
   - ✅ Fillable: email

10. **Rate** (`app/Modules/Rate/Models/Rate.php`)
    - ✅ Fillable: product_id, user_id, rate, comment, status
    - ✅ Casts: product_id, user_id, rate, status
    - ✅ Relationships: user, product

11. **Compare** (`app/Modules/Compare/Models/Compare.php`)
    - ✅ Fillable: store_id, name, price, link, is_link, brand, user_id, status
    - ✅ Relationships: user, store

12. **Config** (`app/Modules/Config/Models/Config.php`)
    - ✅ Fillable: name, code, key, value, content, group, status, user_id
    - ✅ Casts: status
    - ✅ Relationships: user

13. **Banner** (`app/Modules/Banner/Models/Banner.php`)
    - ✅ Fillable: name, link, image, content, status, type, sort, display, user_id
    - ✅ Casts: status, sort
    - ✅ Relationships: user

14. **Page** (`app/Modules/Page/Models/Page.php`)
    - ✅ Fillable: name, slug, image, description, content, status, type, view, seo_title, seo_description, cat_id, user_id
    - ✅ Casts: status, view, cat_id
    - ✅ Relationships: user

15. **Contact** (`app/Modules/Contact/Models/Contact.php`)
    - ✅ Fillable: name, email, phone, address, content, status, user_id
    - ✅ Casts: status

16. **Feedback** (`app/Modules/Feedback/Models/Feedback.php`)
    - ✅ Fillable: name, position, image, content, status, user_id
    - ✅ Casts: status
    - ✅ Relationships: user

17. **Tag** (`app/Modules/Tag/Models/Tag.php`)
    - ✅ Fillable: name, slug, description, status, seo_title, seo_description, user_id
    - ✅ Casts: status
    - ✅ Relationships: user

18. **Video** (`app/Modules/Video/Models/Video.php`)
    - ✅ Fillable: name, slug, image, description, content, video, status, type, view, seo_title, seo_description, cat_id, user_id
    - ✅ Casts: status, view, cat_id
    - ✅ Relationships: user

19. **Menu** (`app/Modules/Menu/Models/Menu.php`)
    - ✅ Fillable: name, url, parent, sort, status, group_id, user_id
    - ✅ Casts: parent, sort, status, group_id
    - ✅ Relationships: user, children

20. **Member** (`app/Modules/Member/Models/Member.php`)
    - ✅ Fillable: name, email, phone, address, password, status
    - ✅ Casts: email_verified_at (datetime), status
    - ✅ Relationships: order, address

21. **MarketingCampaign** (`app/Modules/Marketing/Models/MarketingCampaign.php`)
    - ✅ Fillable: name, start_at, end_at, status, user_id
    - ✅ Relationships: user, products

## Migration Files

All migration files are located in `database/migrations/`:

- `2026_01_26_003402_create_brands_table.php`
- `2026_01_26_003405_create_origins_table.php`
- `2026_01_26_003408_create_promotions_table.php`
- `2026_01_26_003411_create_roles_table.php`
- `2026_01_26_003414_create_picks_table.php`
- `2026_01_26_003417_create_showrooms_table.php`
- `2026_01_26_003420_create_redirections_table.php`
- `2026_01_26_003422_create_searchs_table.php`
- `2026_01_26_003426_create_subcribers_table.php`
- `2026_01_26_003430_create_rates_table.php`
- `2026_01_26_003433_create_compares_table.php`
- `2026_01_26_003559_update_configs_table_add_key_value_group.php`

## Next Steps

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Verify Database Structure

```bash
php artisan migrate:status
```

### 3. Test APIs

```bash
php tests/api-dry-run-test.php
```

## Notes

1. **Shared Tables:**
   - `posts` table is used for: Categories, Pages, Posts, Videos, Downloads (differentiated by `type` field)
   - `medias` table is used for: Banners, Sellings (differentiated by `type` field)

2. **Relationships:**
   - All models have `user` relationship (belongsTo User)
   - Models with hierarchical structure: Category (parent/children), Menu (parent/children)
   - Models with location data: Pick (province, district, ward)

3. **Indexes:**
   - All tables have indexes on `status` for filtering
   - Sortable tables have indexes on `sort`
   - Foreign keys have indexes for performance

4. **Data Types:**
   - Status fields: `smallInteger` (0=inactive, 1=active)
   - Sort fields: `integer` (default: 0)
   - Decimal fields: `decimal(15, 2)` for prices/values
   - JSON fields: `text` with JSON casting in models

## Status

✅ **All migrations created**  
✅ **All models updated with fillable and casts**  
✅ **All relationships defined**  
✅ **Ready for migration execution**

