# API Migration Analysis Report

**Last Updated:** 2025-01-20  
**Purpose:** Identify modules that need RESTful API migration

---

## Executive Summary

This report analyzes all modules in `app/Modules` to identify which ones still use traditional web routes and need to be migrated to RESTful API endpoints.

**Status Overview:**
- ✅ **Migrated to RESTful API:** 9 modules
- ⚠️ **Partially Migrated:** 3 modules (have public API but no admin API)
- ❌ **Not Migrated:** 33+ modules

---

## ✅ Modules with Full RESTful API

These modules have complete RESTful API implementation in `app/Modules/ApiAdmin`:

### 1. Products Management
- **Status:** ✅ Complete
- **Base URL:** `/admin/api/products`
- **Endpoints:** GET, POST, PUT, DELETE, PATCH (status, bulk-action, sort)
- **Special Features:** Variant management, Packaging management
- **Documentation:** ✅ Documented

### 2. Warehouse Management
- **Status:** ✅ Complete
- **Base URL:** `/admin/api/v1/warehouse`
- **Endpoints:** Inventory, Import/Export receipts, Statistics
- **Documentation:** ✅ Documented

### 3. Warehouse Accounting V2
- **Status:** ✅ Complete
- **Base URL:** `/admin/api/v2/warehouse/accounting`
- **Endpoints:** Receipts management, Statistics
- **Documentation:** ✅ Documented

### 4. Flash Sales Management
- **Status:** ✅ Complete
- **Base URL:** `/admin/api/flash-sales`
- **Endpoints:** Full CRUD + search products
- **Documentation:** ✅ Documented

### 5. Orders Management
- **Status:** ✅ Complete
- **Base URL:** `/admin/api/orders`
- **Endpoints:** List, Show, Update, Update Status
- **Documentation:** ✅ Documented

### 6. Deals Management
- **Status:** ✅ Complete
- **Base URL:** `/admin/api/deals`
- **Endpoints:** Full CRUD + status update
- **Documentation:** ✅ Documented

### 7. Sliders Management
- **Status:** ✅ Complete
- **Base URL:** `/admin/api/sliders`
- **Endpoints:** Full CRUD + status update
- **Documentation:** ✅ Documented

### 8. Ingredient Dictionary
- **Status:** ✅ Complete
- **Base URL:** `/admin/api/ingredients`
- **Endpoints:** Full CRUD + bulk actions + crawl
- **Sub-modules:** Categories, Benefits, Rates (all have API)
- **Documentation:** ✅ Documented

### 9. Taxonomy Management
- **Status:** ✅ Complete
- **Base URL:** `/admin/api/taxonomies`
- **Endpoints:** Full CRUD + bulk actions + sort
- **Documentation:** ✅ Documented

### 10. Google Merchant Center
- **Status:** ✅ Complete
- **Base URL:** `/admin/api/gmc`
- **Endpoints:** Preview, Sync
- **Documentation:** ✅ Documented

---

## ⚠️ Modules with Partial API (Public API Only)

These modules have public API endpoints but lack admin management API:

### 1. Brands
- **Public API:** ✅ `/api/v1/brands/*` (featured, list, detail, products)
- **Admin API:** ❌ Missing
- **Current Admin Routes:** `/admin/brand/*` (web routes only)
- **Priority:** 🔴 High (core eCommerce feature)
- **Estimated Endpoints Needed:**
  - `GET /admin/api/brands` - List with filters
  - `POST /admin/api/brands` - Create
  - `PUT /admin/api/brands/{id}` - Update
  - `DELETE /admin/api/brands/{id}` - Delete
  - `PATCH /admin/api/brands/{id}/status` - Update status
  - `POST /admin/api/brands/bulk-action` - Bulk actions
  - `POST /admin/api/brands/upload` - Upload image

### 2. Categories
- **Public API:** ✅ `/api/categories/*` (list, featured, hierarchical)
- **Admin API:** ❌ Missing
- **Current Admin Routes:** `/admin/category/*` (web routes only)
- **Priority:** 🔴 High (core eCommerce feature)
- **Estimated Endpoints Needed:**
  - `GET /admin/api/categories` - List with tree structure
  - `POST /admin/api/categories` - Create
  - `PUT /admin/api/categories/{id}` - Update
  - `DELETE /admin/api/categories/{id}` - Delete
  - `PATCH /admin/api/categories/{id}/status` - Update status
  - `POST /admin/api/categories/bulk-action` - Bulk actions
  - `PATCH /admin/api/categories/sort` - Update sort order
  - `POST /admin/api/categories/tree` - Update tree structure

### 3. Origins
- **Public API:** ✅ `/api/v1/origins/options` (options only)
- **Admin API:** ❌ Missing
- **Current Admin Routes:** `/admin/origin/*` (web routes only)
- **Priority:** 🟡 Medium
- **Estimated Endpoints Needed:**
  - `GET /admin/api/origins` - List
  - `POST /admin/api/origins` - Create
  - `PUT /admin/api/origins/{id}` - Update
  - `DELETE /admin/api/origins/{id}` - Delete
  - `PATCH /admin/api/origins/{id}/status` - Update status
  - `POST /admin/api/origins/bulk-action` - Bulk actions
  - `POST /admin/api/origins/sort` - Update sort order

---

## ❌ Modules Without RESTful API

### High Priority (Core Business Features)

#### 1. Marketing Campaign Management
- **Module:** `app/Modules/Marketing`
- **Current Routes:** `/admin/marketing/campaign/*` (web routes only)
- **Priority:** 🔴 High
- **Business Impact:** High (affects promotions and campaigns)
- **Estimated Endpoints:**
  - `GET /admin/api/marketing/campaigns` - List campaigns
  - `POST /admin/api/marketing/campaigns` - Create campaign
  - `PUT /admin/api/marketing/campaigns/{id}` - Update campaign
  - `DELETE /admin/api/marketing/campaigns/{id}` - Delete campaign
  - `PATCH /admin/api/marketing/campaigns/{id}/status` - Update status
  - `POST /admin/api/marketing/campaigns/{id}/products` - Add products
  - `DELETE /admin/api/marketing/campaigns/{id}/products/{productId}` - Remove product
  - `POST /admin/api/marketing/campaigns/search-products` - Search products

#### 2. Promotion Management
- **Module:** `app/Modules/Promotion`
- **Current Routes:** `/admin/promotion/*` (web routes only)
- **Priority:** 🔴 High
- **Business Impact:** High (coupons, discounts)
- **Estimated Endpoints:**
  - `GET /admin/api/promotions` - List promotions
  - `POST /admin/api/promotions` - Create promotion
  - `PUT /admin/api/promotions/{id}` - Update promotion
  - `DELETE /admin/api/promotions/{id}` - Delete promotion
  - `PATCH /admin/api/promotions/{id}/status` - Update status
  - `POST /admin/api/promotions/bulk-action` - Bulk actions
  - `POST /admin/api/promotions/sort` - Update sort order

#### 3. Banner Management
- **Module:** `app/Modules/Banner`
- **Current Routes:** `/admin/banner/*` (web routes only)
- **Priority:** 🔴 High
- **Business Impact:** Medium (marketing banners)
- **Estimated Endpoints:**
  - `GET /admin/api/banners` - List banners
  - `POST /admin/api/banners` - Create banner
  - `PUT /admin/api/banners/{id}` - Update banner
  - `DELETE /admin/api/banners/{id}` - Delete banner
  - `PATCH /admin/api/banners/{id}/status` - Update status
  - `POST /admin/api/banners/bulk-action` - Bulk actions
  - `POST /admin/api/banners/sort` - Update sort order

### Medium Priority (User & Content Management)

#### 4. User Management
- **Module:** `app/Modules/User`
- **Current Routes:** `/admin/user/*` (web routes only)
- **Priority:** 🟡 Medium
- **Business Impact:** Medium (admin users)
- **Estimated Endpoints:**
  - `GET /admin/api/users` - List users
  - `POST /admin/api/users` - Create user
  - `PUT /admin/api/users/{id}` - Update user
  - `DELETE /admin/api/users/{id}` - Delete user
  - `PATCH /admin/api/users/{id}/status` - Update status
  - `POST /admin/api/users/{id}/change-password` - Change password
  - `POST /admin/api/users/check-email` - Check email availability

#### 5. Member Management
- **Module:** `app/Modules/Member`
- **Current Routes:** `/admin/member/*` (web routes only)
- **Priority:** 🟡 Medium
- **Business Impact:** Medium (customer management)
- **Estimated Endpoints:**
  - `GET /admin/api/members` - List members
  - `POST /admin/api/members` - Create member
  - `PUT /admin/api/members/{id}` - Update member
  - `DELETE /admin/api/members/{id}` - Delete member
  - `PATCH /admin/api/members/{id}/status` - Update status
  - `GET /admin/api/members/{id}` - Get member details
  - `POST /admin/api/members/{id}/addresses` - Add address
  - `PUT /admin/api/members/{id}/addresses/{addressId}` - Update address
  - `DELETE /admin/api/members/{id}/addresses/{addressId}` - Delete address
  - `POST /admin/api/members/{id}/change-password` - Change password

#### 6. Page Management
- **Module:** `app/Modules/Page`
- **Current Routes:** `/admin/page/*` (web routes only)
- **Priority:** 🟡 Medium
- **Business Impact:** Low (static pages)
- **Estimated Endpoints:**
  - `GET /admin/api/pages` - List pages
  - `POST /admin/api/pages` - Create page
  - `PUT /admin/api/pages/{id}` - Update page
  - `DELETE /admin/api/pages/{id}` - Delete page
  - `PATCH /admin/api/pages/{id}/status` - Update status
  - `POST /admin/api/pages/bulk-action` - Bulk actions

#### 7. Pick (Warehouse Location) Management
- **Module:** `app/Modules/Pick`
- **Current Routes:** `/admin/pick/*` (web routes only)
- **Priority:** 🟡 Medium
- **Business Impact:** Medium (shipping locations)
- **Estimated Endpoints:**
  - `GET /admin/api/picks` - List pick locations
  - `POST /admin/api/picks` - Create pick location
  - `PUT /admin/api/picks/{id}` - Update pick location
  - `DELETE /admin/api/picks/{id}` - Delete pick location
  - `PATCH /admin/api/picks/{id}/status` - Update status
  - `POST /admin/api/picks/bulk-action` - Bulk actions
  - `POST /admin/api/picks/sort` - Update sort order
  - `GET /admin/api/picks/{id}/district/{districtId}` - Get district
  - `GET /admin/api/picks/{id}/ward/{wardId}` - Get ward

### Low Priority (Supporting Features)

#### 8. Role & Permission Management
- **Module:** `app/Modules/Role`, `app/Modules/Permission`, `app/Modules/Right`
- **Current Routes:** `/admin/role/*`, `/admin/permission/*`, `/admin/right/*`
- **Priority:** 🟢 Low
- **Business Impact:** Low (access control)
- **Estimated Endpoints:**
  - `GET /admin/api/roles` - List roles
  - `POST /admin/api/roles` - Create role
  - `PUT /admin/api/roles/{id}` - Update role
  - `DELETE /admin/api/roles/{id}` - Delete role
  - `GET /admin/api/permissions` - List permissions
  - `POST /admin/api/roles/{id}/permissions` - Assign permissions

#### 9. Setting Management
- **Module:** `app/Modules/Setting`
- **Current Routes:** `/admin/setting/*` (web routes only)
- **Priority:** 🟢 Low
- **Business Impact:** Low (system settings)
- **Estimated Endpoints:**
  - `GET /admin/api/settings` - Get all settings
  - `PUT /admin/api/settings` - Update settings (bulk)
  - `GET /admin/api/settings/{key}` - Get specific setting
  - `PUT /admin/api/settings/{key}` - Update specific setting

#### 10. Contact Management
- **Module:** `app/Modules/Contact`
- **Current Routes:** `/admin/contact/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/contacts` - List contact messages
  - `GET /admin/api/contacts/{id}` - Get contact details
  - `DELETE /admin/api/contacts/{id}` - Delete contact
  - `PATCH /admin/api/contacts/{id}/status` - Update status (read/unread)

#### 11. Feedback Management
- **Module:** `app/Modules/Feedback`
- **Current Routes:** `/admin/feedback/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/feedbacks` - List feedbacks
  - `GET /admin/api/feedbacks/{id}` - Get feedback details
  - `DELETE /admin/api/feedbacks/{id}` - Delete feedback
  - `PATCH /admin/api/feedbacks/{id}/status` - Update status

#### 12. Subscriber Management
- **Module:** `app/Modules/Subcriber`
- **Current Routes:** `/admin/subcriber/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/subscribers` - List subscribers
  - `POST /admin/api/subscribers` - Add subscriber
  - `DELETE /admin/api/subscribers/{id}` - Delete subscriber
  - `POST /admin/api/subscribers/export` - Export subscribers

#### 13. Tag Management
- **Module:** `app/Modules/Tag`
- **Current Routes:** `/admin/tag/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/tags` - List tags
  - `POST /admin/api/tags` - Create tag
  - `PUT /admin/api/tags/{id}` - Update tag
  - `DELETE /admin/api/tags/{id}` - Delete tag

#### 14. Post Management
- **Module:** `app/Modules/Post`
- **Current Routes:** `/admin/post/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/posts` - List posts
  - `POST /admin/api/posts` - Create post
  - `PUT /admin/api/posts/{id}` - Update post
  - `DELETE /admin/api/posts/{id}` - Delete post
  - `PATCH /admin/api/posts/{id}/status` - Update status

#### 15. Video Management
- **Module:** `app/Modules/Video`
- **Current Routes:** `/admin/video/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/videos` - List videos
  - `POST /admin/api/videos` - Create video
  - `PUT /admin/api/videos/{id}` - Update video
  - `DELETE /admin/api/videos/{id}` - Delete video
  - `PATCH /admin/api/videos/{id}/status` - Update status

#### 16. Showroom Management
- **Module:** `app/Modules/Showroom`, `app/Modules/GroupShowroom`
- **Current Routes:** `/admin/showroom/*`, `/admin/group-showroom/*`
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/showrooms` - List showrooms
  - `POST /admin/api/showrooms` - Create showroom
  - `PUT /admin/api/showrooms/{id}` - Update showroom
  - `DELETE /admin/api/showrooms/{id}` - Delete showroom

#### 17. Menu Management
- **Module:** `app/Modules/Menu`
- **Current Routes:** `/admin/menu/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/menus` - List menus
  - `POST /admin/api/menus` - Create menu
  - `PUT /admin/api/menus/{id}` - Update menu
  - `DELETE /admin/api/menus/{id}` - Delete menu
  - `POST /admin/api/menus/sort` - Update sort order

#### 18. Footer Block Management
- **Module:** `app/Modules/FooterBlock`
- **Current Routes:** `/admin/footer-block/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/footer-blocks` - List footer blocks
  - `POST /admin/api/footer-blocks` - Create footer block
  - `PUT /admin/api/footer-blocks/{id}` - Update footer block
  - `DELETE /admin/api/footer-blocks/{id}` - Delete footer block

#### 19. Redirection Management
- **Module:** `app/Modules/Redirection`
- **Current Routes:** `/admin/redirection/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/redirections` - List redirections
  - `POST /admin/api/redirections` - Create redirection
  - `PUT /admin/api/redirections/{id}` - Update redirection
  - `DELETE /admin/api/redirections/{id}` - Delete redirection

#### 20. Rate Management
- **Module:** `app/Modules/Rate`
- **Current Routes:** `/admin/rate/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/rates` - List product rates/reviews
  - `GET /admin/api/rates/{id}` - Get rate details
  - `DELETE /admin/api/rates/{id}` - Delete rate
  - `PATCH /admin/api/rates/{id}/status` - Update status (approve/reject)

#### 21. Selling Management
- **Module:** `app/Modules/Selling`
- **Current Routes:** `/admin/selling/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/sellings` - List selling records
  - `GET /admin/api/sellings/{id}` - Get selling details

#### 22. Search Management
- **Module:** `app/Modules/Search`
- **Current Routes:** `/admin/search/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/search/logs` - Search logs
  - `GET /admin/api/search/analytics` - Search analytics

#### 23. Download Management
- **Module:** `app/Modules/Download`
- **Current Routes:** `/admin/download/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/downloads` - List downloads
  - `POST /admin/api/downloads` - Create download
  - `PUT /admin/api/downloads/{id}` - Update download
  - `DELETE /admin/api/downloads/{id}` - Delete download

#### 24. Config Management
- **Module:** `app/Modules/Config`
- **Current Routes:** `/admin/config/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/configs` - List configs
  - `POST /admin/api/configs` - Create config
  - `PUT /admin/api/configs/{id}` - Update config
  - `DELETE /admin/api/configs/{id}` - Delete config

#### 25. Compare Management
- **Module:** `app/Modules/Compare`
- **Current Routes:** `/admin/compare/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/compares` - List compare records
  - `GET /admin/api/compares/{id}` - Get compare details

#### 26. Dashboard
- **Module:** `app/Modules/Dashboard`
- **Current Routes:** `/admin/dashboard/*` (web routes only)
- **Priority:** 🟢 Low
- **Estimated Endpoints:**
  - `GET /admin/api/dashboard/statistics` - Get dashboard statistics
  - `GET /admin/api/dashboard/charts` - Get chart data
  - `GET /admin/api/dashboard/recent-orders` - Get recent orders
  - `GET /admin/api/dashboard/top-products` - Get top products

---

## Migration Priority Matrix

| Priority | Module Count | Modules |
|----------|--------------|---------|
| 🔴 High | 6 | Brands, Categories, Origins, Marketing, Promotion, Banner |
| 🟡 Medium | 4 | User, Member, Page, Pick |
| 🟢 Low | 20+ | Role, Permission, Setting, Contact, Feedback, etc. |

---

## Recommended Migration Order

### Phase 1: Core eCommerce Features (High Priority)
1. **Brands Admin API** - Essential for product management
2. **Categories Admin API** - Essential for product organization
3. **Marketing Campaign API** - Critical for promotions
4. **Promotion API** - Critical for discounts/coupons
5. **Banner API** - Important for marketing

### Phase 2: User & Content Management (Medium Priority)
6. **Member API** - Customer management
7. **User API** - Admin user management
8. **Page API** - Content management
9. **Pick API** - Shipping location management

### Phase 3: Supporting Features (Low Priority)
10. **Setting API** - System configuration
11. **Contact/Feedback API** - Customer support
12. **Role/Permission API** - Access control
13. Other supporting modules

---

## Implementation Guidelines

### Standard RESTful Endpoints Pattern

For each module, implement these standard endpoints:

```php
// List with pagination and filters
GET /admin/api/{module}

// Get single item
GET /admin/api/{module}/{id}

// Create new item
POST /admin/api/{module}

// Update item
PUT /admin/api/{module}/{id}

// Delete item
DELETE /admin/api/{module}/{id}

// Update status
PATCH /admin/api/{module}/{id}/status

// Bulk actions
POST /admin/api/{module}/bulk-action

// Sort order (if applicable)
POST /admin/api/{module}/sort
```

### Response Format

All APIs should follow this standard format:

**Success Response:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
```

### Authentication

All admin APIs should use:
- Middleware: `['web', 'auth']`
- Base URL: `/admin/api`
- Namespace: `App\Modules\ApiAdmin\Controllers`

---

## Estimated Effort

| Priority | Modules | Estimated Days | Complexity |
|----------|---------|----------------|------------|
| High | 6 | 12-15 days | Medium-High |
| Medium | 4 | 6-8 days | Medium |
| Low | 20+ | 20-30 days | Low-Medium |
| **Total** | **30+** | **38-53 days** | - |

**Note:** Effort estimates assume:
- 1 developer working full-time
- Standard CRUD operations
- Basic validation and error handling
- Documentation included

---

## Next Steps

1. **Review and Approve Priority List** - Confirm migration order with stakeholders
2. **Create Migration Plan** - Detailed timeline and resource allocation
3. **Set Up Development Environment** - Ensure API testing tools are ready
4. **Start with Phase 1** - Begin with Brands and Categories APIs
5. **Update Documentation** - Keep `API_DOCUMENTATION.md` updated as APIs are migrated

---

**Report Generated:** 2025-01-20  
**Analyst:** AI Assistant  
**Version:** 1.0

