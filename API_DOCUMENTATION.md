# LICA API Documentation

Complete API documentation for LICA eCommerce platform.

---

## Table of Contents

1. [Public API](#public-api)
   - [Categories](#categories-api)
   - [Products (Legacy)](#products-legacy-api)
   - [Recommendations](#recommendations-api)
   - [Analytics](#analytics-api)
2. [Public API V1](#public-api-v1)
   - [Brands](#brands-api)
   - [Products](#products-api)
   - [Origins](#origins-api)
   - [Media](#media-api)
   - [Flash Sales](#flash-sales-api)
   - [Cart](#cart-api)
   - [Orders](#orders-api)
   - [Sliders](#sliders-api)
   - [Deals](#deals-api)
   - [Price Calculation](#price-calculation-api)
3. [Inventory API V2](#inventory-api-v2)
4. [Admin API](#admin-api)
   - [Products Management](#products-management)
   - [Warehouse Management](#warehouse-management)
   - [Warehouse Accounting V2](#warehouse-accounting-v2)
   - [Flash Sales Management](#flash-sales-management)
   - [Orders Management](#orders-management)
   - [Deals Management](#deals-management)
   - [Sliders Management](#sliders-management)
   - [Ingredient Dictionary](#ingredient-dictionary)
   - [Google Merchant Center](#google-merchant-center)
   - [Taxonomy Management](#taxonomy-management)

---

## Public API

Base URL: `/api`

### Categories API

#### GET /api/categories

Get all categories.

**Query Parameters:**
- `page` (integer, optional): Page number
- `limit` (integer, optional): Items per page

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Category Name",
      "slug": "category-slug",
      "parent_id": null,
      "status": "1"
    }
  ]
}
```

#### GET /api/categories/featured

Get featured categories.

#### GET /api/categories/hierarchical

Get categories in hierarchical structure.

#### GET /api/categories/{id}

Get single category details.

---

### Products (Legacy) API

#### GET /api/products/top-selling

Get top selling products.

**Query Parameters:**
- `limit` (integer, optional): Number of products. Default: 10

#### GET /api/products/by-category/{id}

Get products by category.

**Path Parameters:**
- `id` (integer, required): Category ID

**Query Parameters:**
- `page` (integer, optional): Page number
- `limit` (integer, optional): Items per page

#### GET /api/products/flash-sale

Get flash sale products.

#### GET /api/products/{slug}/detail

Get product detail by slug (legacy endpoint).

#### GET /api/products/{id}/price-info

Get product price information.

**Path Parameters:**
- `id` (integer, required): Product ID

---

### Recommendations API

Base URL: `/api/recommendations`

#### GET /api/recommendations

Get product recommendations.

**Query Parameters:**
- `user_id` (integer, optional): User ID
- `product_id` (integer, optional): Product ID for related recommendations
- `limit` (integer, optional): Number of recommendations. Default: 10

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Recommended Product",
      "slug": "recommended-product",
      "image": "https://cdn.lica.vn/uploads/image/product.jpg",
      "price": 100000,
      "score": 0.85
    }
  ]
}
```

#### POST /api/recommendations/track

Track user behavior for recommendations.

**Request Body:**
```json
{
  "user_id": 1,
  "product_id": 10,
  "action": "view",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

#### GET /api/recommendations/history

Get user view history.

**Query Parameters:**
- `user_id` (integer, required): User ID
- `limit` (integer, optional): Number of items. Default: 20

---

### Analytics API

Base URL: `/api/analytics`

**Authentication:** Required (API token)

#### GET /api/analytics/user-history

Get user browsing history.

**Query Parameters:**
- `user_id` (integer, required): User ID
- `date_from` (date, optional): Start date
- `date_to` (date, optional): End date

#### GET /api/analytics/user-preferences

Get user preferences based on behavior.

**Query Parameters:**
- `user_id` (integer, required): User ID

#### GET /api/analytics/export-ai

Export data for AI training.

**Query Parameters:**
- `format` (string, optional): Export format (json/csv). Default: json
- `date_from` (date, optional): Start date
- `date_to` (date, optional): End date

#### GET /api/analytics/product-ingredients

Get product ingredient analysis.

**Query Parameters:**
- `product_id` (integer, optional): Filter by product ID
- `ingredient_id` (integer, optional): Filter by ingredient ID

---

## Public API V1

Base URL: `/api/v1`

### Brands API

#### GET /api/v1/brands/featured

Get featured brands for homepage display.

**Query Parameters:**
- `limit` (integer, optional): Number of brands to return. Default: 14, Max: 50

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Brand Name",
      "slug": "brand-slug",
      "image": "https://cdn.lica.vn/uploads/image/brand.jpg",
      "banner": "https://cdn.lica.vn/uploads/image/banner.jpg",
      "logo": "https://cdn.lica.vn/uploads/image/logo.jpg",
      "content": "Brand description",
      "gallery": ["https://cdn.lica.vn/uploads/image/gallery1.jpg"],
      "status": "1",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "count": 14
}
```

#### GET /api/v1/brands

Get all brands with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 20, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active). Default: only active brands
- `keyword` (string, optional): Search by brand name

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Brand Name",
      "slug": "brand-slug",
      "image": "https://cdn.lica.vn/uploads/image/brand.jpg",
      "total_products": 150,
      "status": "1"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "last_page": 3
  }
}
```

#### GET /api/v1/brands/{slug}

Get single brand details.

**Path Parameters:**
- `slug` (string, required): Brand slug

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Brand Name",
    "slug": "brand-slug",
    "image": "https://cdn.lica.vn/uploads/image/brand.jpg",
    "content": "Brand description",
    "total_products": 150,
    "status": "1"
  }
}
```

#### GET /api/v1/brands/options

Get brands as select options (for dropdowns).

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "value": 1,
      "label": "Brand Name"
    }
  ]
}
```

#### GET /api/v1/brands/{slug}/products

Get products by brand with pagination and filters.

**Path Parameters:**
- `slug` (string, required): Brand slug

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 30, Max: 100
- `stock` (string, optional): Filter by stock (0=out of stock, 1=in stock, all=all). Default: "all"
- `sort` (string, optional): Sort order
  - `newest` (default): Latest created
  - `oldest`: Oldest created
  - `price_asc`: Price low to high
  - `price_desc`: Price high to low
  - `name_asc`: Name A-Z
  - `name_desc`: Name Z-A

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "slug": "product-slug",
      "image": "https://cdn.lica.vn/uploads/image/product.jpg",
      "price_info": {
        "price": 80000,
        "original_price": 100000,
        "type": "sale",
        "label": "On Sale"
      },
      "stock": "1",
      "brand": {
        "id": 1,
        "name": "Brand Name",
        "slug": "brand-slug"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 30,
    "total": 150,
    "last_page": 5
  }
}
```

#### GET /api/v1/brands/{slug}/products/available

Get available products by brand (shortcut, equivalent to `stock=1`).

**Path Parameters:**
- `slug` (string, required): Brand slug

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 30
- `sort` (string, optional): Sort order (same as products endpoint)

#### GET /api/v1/brands/{slug}/products/out-of-stock

Get out of stock products by brand (shortcut, equivalent to `stock=0`).

**Path Parameters:**
- `slug` (string, required): Brand slug

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 30
- `sort` (string, optional): Sort order (same as products endpoint)

---

### Origins API

#### GET /api/v1/origins/options

Get origins as select options (for dropdowns).

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "value": 1,
      "label": "Origin Name"
    }
  ]
}
```

---

### Media API

#### POST /api/v1/media/upload

Upload media file to Cloudflare R2.

**Request:**
- Content-Type: `multipart/form-data`
- `file` (file, required): Image file

**Response (200):**
```json
{
  "success": true,
  "data": {
    "url": "https://cdn.lica.vn/uploads/image/filename.jpg",
    "path": "uploads/image/filename.jpg"
  }
}
```

---

### Products API

#### GET /api/v1/products/{slug}

Get product details including variants, pricing, flash sales, deals, and related products.

**Path Parameters:**
- `slug` (string, required): Product slug

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Product Name",
    "slug": "product-slug",
    "image": "https://cdn.lica.vn/uploads/images/product.jpg",
    "gallery": ["https://cdn.lica.vn/uploads/images/gallery1.jpg"],
    "description": "Short description",
    "content": "Full HTML content",
    "price_info": {
      "price": 80000,
      "original_price": 100000,
      "type": "flashsale",
      "label": "Flash Sale",
      "discount_percent": 20
    },
    "variants": [
      {
        "id": 10,
        "sku": "SKU-001",
        "option1_value": "100ml",
        "price": 100000,
        "sale": 80000,
        "stock": 50,
        "price_info": {
          "final_price": 70000,
          "original_price": 100000,
          "type": "flashsale",
          "label": "Flash Sale"
        }
      }
    ],
    "flash_sale": {
      "id": 1,
      "name": "Flash Sale January",
      "price_sale": 60000,
      "number": 100,
      "buy": 50,
      "remaining": 50
    },
    "deal": {
      "id": 1,
      "name": "Deal Bundle",
      "limited": 2,
      "sale_deals": [
        {
          "id": 1,
          "product_id": 2,
          "product_name": "Bundle Product",
          "variant_id": 2,
          "price": 50000,
          "original_price": 80000
        }
      ]
    },
    "rating": {
      "average": 4.5,
      "count": 120
    },
    "total_sold": 1500
  }
}
```

**Features:**
- Eager loading for performance optimization
- 30-minute cache (1800 seconds)
- Automatic price calculation: Flash Sale > Marketing Campaign > Sale > Normal
- Automatic image URL formatting with R2 CDN

---

### Flash Sales API

#### GET /api/v1/flash-sales/active

Get active Flash Sale campaigns.

**Query Parameters:**
- `limit` (integer, optional): Number of campaigns. Default: 10, Max: 50

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Flash Sale January",
      "start": "2024-01-15T00:00:00.000000Z",
      "end": "2024-01-20T23:59:59.000000Z",
      "start_timestamp": 1705276800,
      "end_timestamp": 1705708799,
      "status": "1",
      "is_active": true,
      "countdown_seconds": 432000,
      "total_products": 25
    }
  ],
  "count": 1
}
```

#### GET /api/v1/flash-sales/{id}/products

Get products in a Flash Sale campaign.

**Path Parameters:**
- `id` (integer, required): Flash Sale ID

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 20, Max: 100
- `available_only` (boolean, optional): Only return products with stock. Default: true

**Response (200):**
```json
{
  "success": true,
  "data": {
    "flash_sale": {
      "id": 1,
      "name": "Flash Sale January",
      "is_active": true,
      "countdown_seconds": 432000
    },
    "products": [
      {
        "id": 10,
        "name": "Product Name",
        "slug": "product-slug",
        "image": "https://cdn.lica.vn/uploads/image/product.jpg",
        "variants": [
          {
            "id": 5,
            "sku": "SKU-001",
            "price": 200000,
            "stock": 50,
            "flash_sale_info": {
              "price_sale": 150000,
              "original_price": 200000,
              "discount_percent": 25,
              "number": 100,
              "buy": 45,
              "remaining": 55
            }
          }
        ]
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 25,
      "last_page": 2
    }
  }
}
```

---

### Deals API

#### GET /api/v1/deals/active-bundles

Get active deal bundles.

**Query Parameters:**
- `limit` (integer, optional): Number of bundles. Default: 10

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Deal Bundle",
      "limited": 2,
      "products": [
        {
          "product_id": 10,
          "product_name": "Main Product",
          "variant_id": 1,
          "price": 50000
        }
      ],
      "sale_products": [
        {
          "product_id": 2,
          "product_name": "Bundle Product",
          "variant_id": 2,
          "price": 30000,
          "original_price": 50000
        }
      ]
    }
  ]
}
```

#### GET /api/v1/deals/{id}/bundle

Get single deal bundle details.

**Path Parameters:**
- `id` (integer, required): Deal ID

---

### Sliders API

#### GET /api/v1/sliders

Get active sliders.

**Query Parameters:**
- `limit` (integer, optional): Number of sliders. Default: 10

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Slider Title",
      "image": "https://cdn.lica.vn/uploads/image/slider.jpg",
      "link": "/product/slug",
      "status": "1",
      "sort": 0
    }
  ]
}
```

---

### Cart API

Base URL: `/api/v1/cart`

**Authentication:** Optional (uses session for guest users, database for authenticated users)

#### GET /api/v1/cart

Get current cart with items, totals, coupons, and available deals.

**Authentication:** Optional (uses session for guest users, database for authenticated users)

#### GET /api/v1/cart/gio-hang

Get full cart page data including sidebar, items, deals, and formatted prices.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "variant_id": 1,
        "product_id": 10,
        "product_name": "Product Name",
        "product_slug": "product-slug",
        "product_image": "https://cdn.lica.vn/uploads/images/product.jpg",
        "variant": {
          "id": 1,
          "sku": "SKU-001",
          "option1_value": "500ml"
        },
        "qty": 2,
        "price": 100000,
        "original_price": 150000,
        "subtotal": 200000,
        "price_info": {
          "price": 100000,
          "original_price": 150000,
          "type": "flashsale",
          "label": "Flash Sale",
          "discount_percent": 33
        },
        "stock": 50,
        "available": true
      }
    ],
    "summary": {
      "total_qty": 5,
      "subtotal": 500000,
      "discount": 50000,
      "shipping_fee": 0,
      "total": 450000
    },
    "coupon": {
      "id": 1,
      "code": "SALE10",
      "discount": 50000
    },
    "available_deals": [],
    "products_with_price": [],
    "deal_counts": [],
    "sidebar": {
      "title": "CART SUMMARY",
      "total_price": 500000,
      "total_price_formatted": "500,000đ",
      "discount": 50000,
      "discount_formatted": "-50,000đ",
      "shipping_fee": 0,
      "shipping_fee_formatted": "0đ",
      "total": 450000,
      "total_formatted": "450,000đ",
      "checkout_url": "/cart/thanh-toan"
    },
    "checkout_url": "/cart/thanh-toan",
    "is_empty": false
  }
}
```

#### POST /api/v1/cart/items

Add item(s) to cart.

**Request Body:**
```json
{
  "variant_id": 1,
  "qty": 2,
  "is_deal": 0
}
```

**Or Combo (multiple items):**
```json
{
  "combo": [
    {
      "variant_id": 1,
      "qty": 2
    },
    {
      "variant_id": 2,
      "qty": 1
    }
  ]
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Added to cart successfully",
  "data": {
    "total_qty": 5,
    "item": {
      "variant_id": 1,
      "qty": 2,
      "price": 100000
    }
  }
}
```

#### PUT /api/v1/cart/items/{variant_id}

Update item quantity in cart.

**Path Parameters:**
- `variant_id` (integer, required): Variant ID

**Request Body:**
```json
{
  "qty": 3
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "variant_id": 1,
    "qty": 3,
    "subtotal": 300000,
    "summary": {
      "total_qty": 6,
      "subtotal": 600000,
      "total": 550000
    }
  }
}
```

#### DELETE /api/v1/cart/items/{variant_id}

Remove item from cart.

**Path Parameters:**
- `variant_id` (integer, required): Variant ID

**Response (200):**
```json
{
  "success": true,
  "message": "Item removed successfully",
  "data": {
    "summary": {
      "total_qty": 4,
      "subtotal": 400000,
      "total": 350000
    }
  }
}
```

#### POST /api/v1/cart/coupon/apply

Apply coupon code.

**Request Body:**
```json
{
  "code": "SALE10"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Coupon applied successfully",
  "data": {
    "coupon": {
      "id": 1,
      "code": "SALE10",
      "discount": 50000
    },
    "summary": {
      "subtotal": 500000,
      "discount": 50000,
      "total": 450000
    }
  }
}
```

#### DELETE /api/v1/cart/coupon

Remove coupon from cart.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "summary": {
      "subtotal": 500000,
      "discount": 0,
      "total": 500000
    }
  }
}
```

#### POST /api/v1/cart/shipping-fee

Calculate shipping fee.

**Request Body:**
```json
{
  "province_id": 1,
  "district_id": 1,
  "ward_id": 1,
  "address": "Street address"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "shipping_fee": 30000,
    "free_ship": false,
    "summary": {
      "subtotal": 500000,
      "discount": 50000,
      "shipping_fee": 30000,
      "total": 480000
    }
  }
}
```

#### POST /api/v1/cart/checkout

Create order from cart.

**Request Body:**
```json
{
  "full_name": "John Doe",
  "phone": "0123456789",
  "email": "john@example.com",
  "address": "Street address",
  "province_id": 1,
  "district_id": 1,
  "ward_id": 1,
  "remark": "Optional note",
  "shipping_fee": 30000
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "order_code": "1704067200",
    "order_id": 123,
    "redirect_url": "/cart/order-success?code=1704067200"
  }
}
```

---

### Orders API

Base URL: `/api/v1/orders`

**Authentication:** Required (member authentication)

#### GET /api/v1/orders

Get user's orders.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 20

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "code": "1704067200",
      "total": 450000,
      "status": "1",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "last_page": 3
  }
}
```

#### GET /api/v1/orders/{code}

Get order details.

**Path Parameters:**
- `code` (string, required): Order code

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "code": "1704067200",
    "total": 450000,
    "status": "1",
    "items": [
      {
        "product_name": "Product Name",
        "variant_sku": "SKU-001",
        "qty": 2,
        "price": 200000
      }
    ],
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

### Price Calculation API

Base URL: `/api/price`

#### GET /api/price/{productId}

Get price information for a product.

**Path Parameters:**
- `productId` (integer, required): Product ID

**Query Parameters:**
- `variant_id` (integer, optional): Variant ID
- `quantity` (integer, optional): Quantity. Default: 1

**Response (200):**
```json
{
  "success": true,
  "data": {
    "price": 100000,
    "original_price": 150000,
    "discount_percent": 33,
    "type": "flashsale"
  }
}
```

#### POST /api/price/calculate

Calculate price with mixed pricing support (Flash Sale + Normal price).

**Request Body:**
```json
{
  "product_id": 10,
  "variant_id": 1,
  "quantity": 7
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total_price": 650000,
    "price_breakdown": [
      {
        "type": "flashsale",
        "quantity": 5,
        "unit_price": 100000,
        "subtotal": 500000
      },
      {
        "type": "normal",
        "quantity": 2,
        "unit_price": 75000,
        "subtotal": 150000
      }
    ],
    "flash_sale_remaining": 5,
    "warning": "Only 5 items available at Flash Sale price, remaining 2 items will be charged at normal price",
    "total_physical_stock": 20,
    "is_available": true,
    "stock_error": null
  }
}
```

**Price Priority:**
1. Flash Sale price (within limit)
2. Marketing Campaign price
3. Sale price
4. Normal price

**Stock Validation:**
- Checks physical stock from Warehouse
- Returns `is_available: false` if quantity exceeds available stock
- Returns `stock_error` message if stock is insufficient

#### POST /api/orders/process

Process order (legacy endpoint).

**Request Body:**
```json
{
  "full_name": "John Doe",
  "phone": "0123456789",
  "address": "Street address",
  "province_id": 1,
  "district_id": 1,
  "ward_id": 1
}
```

---

## Inventory API V2

Base URL: `/api/v2/inventory`

**Authentication:** Required (API token via `auth:api` middleware)

### Stock Queries

#### GET /api/v2/inventory/stocks

Get stock list.

**Query Parameters:**
- `variant_id` (integer, optional): Filter by variant ID
- `product_id` (integer, optional): Filter by product ID
- `warehouse_id` (integer, optional): Filter by warehouse ID
- `page` (integer, optional): Page number
- `limit` (integer, optional): Items per page

#### GET /api/v2/inventory/stocks/{variantId}

Get stock details for a variant.

#### POST /api/v2/inventory/stocks/check-availability

Check stock availability.

**Request Body:**
```json
{
  "variant_id": 1,
  "quantity": 10,
  "warehouse_id": 1
}
```

#### GET /api/v2/inventory/stocks/low-stock

Get low stock items.

**Query Parameters:**
- `threshold` (integer, optional): Low stock threshold. Default: 10

### Stock Mutations

#### POST /api/v2/inventory/receipts/import

Create import receipt.

**Request Body:**
```json
{
  "warehouse_id": 1,
  "items": [
    {
      "variant_id": 1,
      "quantity": 20,
      "price": 100000
    }
  ],
  "notes": "Import notes"
}
```

#### POST /api/v2/inventory/receipts/export

Create export receipt.

#### POST /api/v2/inventory/receipts/transfer

Transfer stock between warehouses.

**Request Body:**
```json
{
  "from_warehouse_id": 1,
  "to_warehouse_id": 2,
  "items": [
    {
      "variant_id": 1,
      "quantity": 10
    }
  ]
}
```

#### POST /api/v2/inventory/receipts/adjust

Adjust stock (inventory adjustment).

**Request Body:**
```json
{
  "warehouse_id": 1,
  "items": [
    {
      "variant_id": 1,
      "quantity": 5,
      "reason": "Stock adjustment"
    }
  ]
}
```

### Receipts

#### GET /api/v2/inventory/receipts

Get receipts list.

#### GET /api/v2/inventory/receipts/{id}

Get receipt details.

#### DELETE /api/v2/inventory/receipts/{id}

Delete receipt.

### Warehouses

#### GET /api/v2/inventory/warehouses

Get warehouses list.

### Movements & Reports

#### GET /api/v2/inventory/movements

Get stock movements.

**Query Parameters:**
- `variant_id` (integer, optional): Filter by variant ID
- `warehouse_id` (integer, optional): Filter by warehouse ID
- `date_from` (date, optional): Start date
- `date_to` (date, optional): End date

#### GET /api/v2/inventory/reports/valuation

Get inventory valuation report.

**Query Parameters:**
- `warehouse_id` (integer, optional): Filter by warehouse ID
- `date` (date, optional): Valuation date

---

## Admin API

Base URL: `/admin/api`

**Authentication:** Required (admin authentication via session)

### Products Management

#### GET /admin/api/products

Get products list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `cat_id` (integer, optional): Filter by category ID
- `keyword` (string, optional): Search by product name
- `feature` (string, optional): Filter featured products (0/1)
- `best` (string, optional): Filter best products (0/1)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "slug": "product-slug",
      "image": "https://example.com/image.jpg",
      "status": "1",
      "feature": "0",
      "best": "0",
      "stock": "1",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 100,
    "last_page": 10
  }
}
```

#### POST /admin/api/products

Create new product.

#### PUT /admin/api/products/{id}

Update product.

#### DELETE /admin/api/products/{id}

Delete product.

#### PATCH /admin/api/products/{id}/status

Update product status.

#### POST /admin/api/products/bulk-action

Bulk actions (hide/show/delete).

### Variant Management

**Last Updated:** 2025-01-20  
**Language Supported:** English

LICA supports defining variant structures with up to two levels of specifications (option1 and option2). The total number of variants per product cannot exceed 50.

**Variant Structure:**
- **1-tier variation:** Product has one specification (e.g., Size: S, M, L)
- **2-tier variation:** Product has two specifications (e.g., Color: Red, Blue + Size: S, M, L)

**Variant Fields:**
- `option1_value`: First tier option value (e.g., "500ml", "Red")
- `option2_value`: Second tier option value (e.g., "L", "XL") - optional
- `sku`: Stock Keeping Unit (unique identifier)
- `price`: Original price
- `sale`: Sale price (optional)
- `stock`: Available stock quantity
- `position`: Display order

#### GET /admin/api/products/{id}/variants

Get all variants for a product.

**Path Parameters:**
- `id` (integer, required): Product ID

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product_id": 10,
      "sku": "SKU-001",
      "option1_value": "500ml",
      "option2_value": null,
      "price": 100000,
      "sale": 80000,
      "stock": 50,
      "position": 0,
      "weight": 0.5,
      "image": "https://cdn.lica.vn/uploads/image/variant.jpg"
    }
  ]
}
```

#### GET /admin/api/products/{id}/variants/{code}

Get single variant details.

**Path Parameters:**
- `id` (integer, required): Product ID
- `code` (integer, required): Variant ID

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "product_id": 10,
    "sku": "SKU-001",
    "option1_value": "500ml",
    "option2_value": null,
    "price": 100000,
    "sale": 80000,
    "stock": 50,
    "position": 0
  }
}
```

#### POST /admin/api/products/{id}/variants

Create a new variant.

**Path Parameters:**
- `id` (integer, required): Product ID

**Request Body:**
```json
{
  "sku": "SKU-002",
  "option1_value": "1000ml",
  "option2_value": null,
  "price": 150000,
  "sale": 120000,
  "stock": 30,
  "position": 1,
  "weight": 1.0,
  "image": "https://cdn.lica.vn/uploads/image/variant2.jpg"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Variant created successfully",
  "data": {
    "id": 2,
    "product_id": 10,
    "sku": "SKU-002",
    "option1_value": "1000ml",
    "price": 150000,
    "sale": 120000,
    "stock": 30
  }
}
```

#### PUT /admin/api/products/{id}/variants/{code}

Update an existing variant.

**Path Parameters:**
- `id` (integer, required): Product ID
- `code` (integer, required): Variant ID

**Request Body:**
```json
{
  "price": 160000,
  "sale": 130000,
  "stock": 40
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Variant updated successfully",
  "data": {
    "id": 2,
    "price": 160000,
    "sale": 130000,
    "stock": 40
  }
}
```

#### DELETE /admin/api/products/{id}/variants/{code}

Delete a variant.

**Path Parameters:**
- `id` (integer, required): Product ID
- `code` (integer, required): Variant ID

**Response (200):**
```json
{
  "success": true,
  "message": "Variant deleted successfully"
}
```

---

## Variant Management Scenarios

### Scenario 1: Adding a Variant with Same Tier Structure

**Situation:** Product has Size specification with "500ml" and "1000ml", now you need to add "750ml" in the middle.

**Current Variants:**

| Variant ID | option1_value | SKU | Price | Stock |
|------------|---------------|-----|-------|-------|
| 1 | 500ml | SKU-500 | 100000 | 50 |
| 2 | 1000ml | SKU-1000 | 150000 | 30 |

**API:** `POST /admin/api/products/{id}/variants`

**Request Example:**
```json
{
  "sku": "SKU-750",
  "option1_value": "750ml",
  "price": 125000,
  "sale": 110000,
  "stock": 40,
  "position": 1
}
```

**Result:**

| Variant ID | option1_value | SKU | Price | Stock |
|------------|---------------|-----|-------|-------|
| 1 | 500ml | SKU-500 | 100000 | 50 |
| 3 | 750ml | SKU-750 | 125000 | 40 |
| 2 | 1000ml | SKU-1000 | 150000 | 30 |

---

### Scenario 2: Adding Variants that Change Tier Structure

**Situation:** Product has only Size specification (500ml, 1000ml), now you need to add Color specification (Red, Blue).

**Original Variant Situation:**

| Variant ID | option1_value | option2_value | SKU | Price |
|------------|---------------|---------------|-----|-------|
| 1 | 500ml | null | SKU-500 | 100000 |
| 2 | 1000ml | null | SKU-1000 | 150000 |

**Variant Situation After Adding Color:**

| Variant ID | option1_value | option2_value | SKU | Price |
|------------|---------------|---------------|-----|-------|
| 3 | 500ml | Red | SKU-500-RED | 100000 |
| 4 | 500ml | Blue | SKU-500-BLUE | 100000 |
| 5 | 1000ml | Red | SKU-1000-RED | 150000 |
| 6 | 1000ml | Blue | SKU-1000-BLUE | 150000 |

**Note:** When changing tier structure, you need to create new variants for all combinations. The original variants (ID 1, 2) should be deleted first.

**Steps:**
1. Delete existing variants: `DELETE /admin/api/products/{id}/variants/1` and `/variants/2`
2. Create new variants with 2-tier structure using `POST /admin/api/products/{id}/variants`

**Request Examples:**
```json
// Variant 1: 500ml + Red
{
  "sku": "SKU-500-RED",
  "option1_value": "500ml",
  "option2_value": "Red",
  "price": 100000,
  "stock": 25
}

// Variant 2: 500ml + Blue
{
  "sku": "SKU-500-BLUE",
  "option1_value": "500ml",
  "option2_value": "Blue",
  "price": 100000,
  "stock": 25
}

// Variant 3: 1000ml + Red
{
  "sku": "SKU-1000-RED",
  "option1_value": "1000ml",
  "option2_value": "Red",
  "price": 150000,
  "stock": 15
}

// Variant 4: 1000ml + Blue
{
  "sku": "SKU-1000-BLUE",
  "option1_value": "1000ml",
  "option2_value": "Blue",
  "price": 150000,
  "stock": 15
}
```

---

### Scenario 3: Deleting a Variant

**Situation:** Product has Size specification with "500ml", "750ml", and "1000ml". You need to delete "750ml".

**Original Variant Situation:**

| Variant ID | option1_value | SKU | Price | Stock |
|------------|---------------|-----|-------|-------|
| 1 | 500ml | SKU-500 | 100000 | 50 |
| 2 | 750ml | SKU-750 | 125000 | 40 |
| 3 | 1000ml | SKU-1000 | 150000 | 30 |

**API:** `DELETE /admin/api/products/{id}/variants/2`

**Result:**

| Variant ID | option1_value | SKU | Price | Stock |
|------------|---------------|-----|-------|-------|
| 1 | 500ml | SKU-500 | 100000 | 50 |
| 3 | 1000ml | SKU-1000 | 150000 | 30 |

---

### Scenario 4: Deleting All Variants of One Option

**Situation:** Product has Color (Red, Blue) and Size (S, M, L) specifications. You need to delete all Red variants.

**Original Variant Situation:**

| Variant ID | option1_value | option2_value | SKU | Price |
|------------|---------------|---------------|-----|-------|
| 1 | Red | S | SKU-RED-S | 100000 |
| 2 | Red | M | SKU-RED-M | 100000 |
| 3 | Red | L | SKU-RED-L | 100000 |
| 4 | Blue | S | SKU-BLUE-S | 100000 |
| 5 | Blue | M | SKU-BLUE-M | 100000 |
| 6 | Blue | L | SKU-BLUE-L | 100000 |

**Steps:**
1. Delete variants 1, 2, 3: `DELETE /admin/api/products/{id}/variants/1`, `/variants/2`, `/variants/3`

**Result:**

| Variant ID | option1_value | option2_value | SKU | Price |
|------------|---------------|---------------|-----|-------|
| 4 | Blue | S | SKU-BLUE-S | 100000 |
| 5 | Blue | M | SKU-BLUE-M | 100000 |
| 6 | Blue | L | SKU-BLUE-L | 100000 |

---

### Scenario 5: Changing Variant Order

**Situation:** Product has Size specification with "500ml" and "1000ml". You need to change the order so "1000ml" appears first.

**Original Variant Situation:**

| Variant ID | option1_value | position | SKU |
|------------|---------------|----------|-----|
| 1 | 500ml | 0 | SKU-500 |
| 2 | 1000ml | 1 | SKU-1000 |

**API:** `PUT /admin/api/products/{id}/variants/{code}`

**Steps:**
1. Update variant 2 position to 0: `PUT /admin/api/products/{id}/variants/2` with `{"position": 0}`
2. Update variant 1 position to 1: `PUT /admin/api/products/{id}/variants/1` with `{"position": 1}`

**Result:**

| Variant ID | option1_value | position | SKU |
|------------|---------------|----------|-----|
| 2 | 1000ml | 0 | SKU-1000 |
| 1 | 500ml | 1 | SKU-500 |

---

### Scenario 6: Updating Variant Option Name

**Situation:** Product has Size specification with "500ml" and "1000ml". You need to rename "500ml" to "500ml (Small)".

**Original Variant Situation:**

| Variant ID | option1_value | SKU |
|------------|---------------|-----|
| 1 | 500ml | SKU-500 |
| 2 | 1000ml | SKU-1000 |

**API:** `PUT /admin/api/products/{id}/variants/1`

**Request:**
```json
{
  "option1_value": "500ml (Small)"
}
```

**Result:**

| Variant ID | option1_value | SKU |
|------------|---------------|-----|
| 1 | 500ml (Small) | SKU-500 |
| 2 | 1000ml | SKU-1000 |

---

### Scenario 7: Updating Variant Price and Stock

**Situation:** Update price and stock for a specific variant.

**API:** `PUT /admin/api/products/{id}/variants/{code}`

**Request:**
```json
{
  "price": 120000,
  "sale": 100000,
  "stock": 60
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Variant updated successfully",
  "data": {
    "id": 1,
    "price": 120000,
    "sale": 100000,
    "stock": 60
  }
}
```

---

## Variant Packaging Management

#### GET /admin/api/products/{id}/variants/{code}/packaging

Get variant packaging dimensions.

**Path Parameters:**
- `id` (integer, required): Product ID
- `code` (integer, required): Variant ID

**Response (200):**
```json
{
  "success": true,
  "data": {
    "variant_id": 1,
    "length": 10,
    "width": 10,
    "height": 10,
    "weight": 0.5
  }
}
```

#### PUT /admin/api/products/{id}/variants/{code}/packaging

Update variant packaging dimensions.

**Path Parameters:**
- `id` (integer, required): Product ID
- `code` (integer, required): Variant ID

**Request Body:**
```json
{
  "length": 15,
  "width": 12,
  "height": 8,
  "weight": 0.6
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Packaging updated successfully",
  "data": {
    "variant_id": 1,
    "length": 15,
    "width": 12,
    "height": 8,
    "weight": 0.6
  }
}
```

---

## Summary

1. **Adding Variants:** Use `POST /admin/api/products/{id}/variants` to add new variants. When changing tier structure (1-tier to 2-tier), delete existing variants first, then create new ones for all combinations.

2. **Updating Variants:** Use `PUT /admin/api/products/{id}/variants/{code}` to update variant information including price, stock, option values, and position.

3. **Deleting Variants:** Use `DELETE /admin/api/products/{id}/variants/{code}` to remove a variant. When deleting all variants of one option, delete them individually.

4. **Variant Order:** Control display order using the `position` field. Lower numbers appear first.

5. **Packaging:** Manage variant-specific packaging dimensions separately from product-level packaging.

6. **Limitations:** Maximum 50 variants per product. SKU must be unique across all products.

#### GET /admin/api/products/{id}/packaging

Get product packaging dimensions.

#### PUT /admin/api/products/{id}/packaging

Update product packaging dimensions.

#### PATCH /admin/api/products/sort

Update product sort order.

---

### Warehouse Management

#### GET /admin/api/v1/warehouse/inventory

Get realtime inventory for variants.

**Query Parameters:**
- `keyword` (string, optional): Search by product name / SKU
- `variant_id` (integer, optional): Filter by variant ID
- `product_id` (integer, optional): Filter by product ID
- `min_stock` (integer, optional): Minimum available stock
- `max_stock` (integer, optional): Maximum available stock
- `sort_by` (string, optional): Sort by `product_name`, `variant_name`, or `stock`
- `sort_order` (string, optional): `asc` or `desc`
- `limit` (integer, optional): Items per page. Default: 10, Max: 100

**Stock Logic:**
- `physical_stock`: Physical stock from Warehouse (latest snapshot from `product_warehouse.physical_stock`, fallback to `variants.stock`)
- `flash_sale_stock`: Remaining Flash Sale stock (SUM of `number - buy` for active Flash Sales)
- `deal_stock`: Remaining Deal stock (SUM of `qty - buy` for active Deals)
- `available_stock`: `physical_stock - flash_sale_stock - deal_stock` (non-negative)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "variant_id": 123,
      "variant_sku": "SKU-123",
      "variant_option": "Default",
      "product_id": 10,
      "product_name": "Product A",
      "product_image": "https://cdn/...",
      "physical_stock": 120,
      "flash_sale_stock": 5,
      "deal_stock": 50,
      "available_stock": 65
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 200,
    "last_page": 10
  }
}
```

**Note:** Response includes `Cache-Control: no-store` header to prevent caching.

#### GET /admin/api/v1/warehouse/inventory/by-product/{productId}

Get realtime inventory for all variants of a product.

#### POST /admin/api/v1/warehouse/import-receipts

Create import receipt.

**Request Body:**
```json
{
  "code": "NH-TEST-001",
  "subject": "Import test",
  "content": "Notes",
  "vat_invoice": "VAT-2026-001",
  "items": [
    {
      "variant_id": 1,
      "price": 100000,
      "quantity": 20
    }
  ]
}
```

#### GET /admin/api/v1/warehouse/import-receipts

Get import receipts list.

#### GET /admin/api/v1/warehouse/import-receipts/{id}

Get import receipt details.

#### PUT /admin/api/v1/warehouse/import-receipts/{id}

Update import receipt.

#### DELETE /admin/api/v1/warehouse/import-receipts/{id}

Delete import receipt.

#### POST /admin/api/v1/warehouse/export-receipts

Create export receipt (validates stock availability).

#### GET /admin/api/v1/warehouse/inventory/{variantId}

Get inventory details for a variant.

**Path Parameters:**
- `variantId` (integer, required): Variant ID

#### GET /admin/api/v1/warehouse/export-receipts

Get export receipts list.

#### GET /admin/api/v1/warehouse/export-receipts/{id}

Get export receipt details.

#### PUT /admin/api/v1/warehouse/export-receipts/{id}

Update export receipt.

#### DELETE /admin/api/v1/warehouse/export-receipts/{id}

Delete export receipt.

#### GET /admin/api/v1/warehouse/export-receipts/{id}/print

Get export receipt print data.

#### GET /admin/api/v1/warehouse/statistics/quantity

Get quantity statistics.

#### GET /admin/api/v1/warehouse/statistics/revenue

Get revenue statistics.

#### GET /admin/api/v1/warehouse/statistics/summary

Get warehouse statistics summary.

**Query Parameters:**
- `date_from` (date, optional): Start date
- `date_to` (date, optional): End date

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total_products": 150,
    "total_variants": 300,
    "total_import_receipts": 50,
    "total_export_receipts": 30,
    "total_import_value": 1000000000,
    "total_export_value": 900000000,
    "total_profit": 100000000,
    "current_stock_value": 250000000,
    "low_stock_items": 15,
    "out_of_stock_items": 5
  }
}
```

---

### Flash Sales Management

#### GET /admin/api/flash-sales

Get Flash Sales list.

#### POST /admin/api/flash-sales

Create Flash Sale.

#### PUT /admin/api/flash-sales/{id}

Update Flash Sale.

#### DELETE /admin/api/flash-sales/{id}

Delete Flash Sale.

#### POST /admin/api/flash-sales/{id}/status

Update Flash Sale status.

#### POST /admin/api/flash-sales/search-products

Search products for Flash Sale.

**Request Body:**
```json
{
  "keyword": "product name",
  "limit": 20
}
```

---

### Warehouse Accounting V2

Base URL: `/admin/api/v2/warehouse/accounting`

#### GET /admin/api/v2/warehouse/accounting/receipts

Get accounting receipts list.

**Query Parameters:**
- `page` (integer, optional): Page number
- `limit` (integer, optional): Items per page
- `type` (string, optional): Receipt type (import/export)
- `status` (string, optional): Receipt status
- `date_from` (date, optional): Start date
- `date_to` (date, optional): End date

#### GET /admin/api/v2/warehouse/accounting/receipts/{id}

Get accounting receipt details.

#### POST /admin/api/v2/warehouse/accounting/receipts

Create accounting receipt.

**Request Body:**
```json
{
  "type": "import",
  "warehouse_id": 1,
  "items": [
    {
      "variant_id": 1,
      "quantity": 20,
      "price": 100000
    }
  ],
  "notes": "Receipt notes"
}
```

#### PUT /admin/api/v2/warehouse/accounting/receipts/{id}

Update accounting receipt.

#### POST /admin/api/v2/warehouse/accounting/receipts/{id}/complete

Complete accounting receipt.

#### POST /admin/api/v2/warehouse/accounting/receipts/{id}/void

Void accounting receipt.

#### GET /admin/api/v2/warehouse/accounting/statistics

Get accounting statistics.

**Query Parameters:**
- `date_from` (date, optional): Start date
- `date_to` (date, optional): End date
- `warehouse_id` (integer, optional): Filter by warehouse ID

---

### Orders Management

#### GET /admin/api/orders

Get orders list with filters.

**Query Parameters:**
- `page` (integer, optional): Page number
- `limit` (integer, optional): Items per page
- `status` (string, optional): Filter by status
- `keyword` (string, optional): Search by order code
- `date_from` (date, optional): Start date
- `date_to` (date, optional): End date

#### GET /admin/api/orders/{id}

Get order details.

#### PATCH /admin/api/orders/{id}/status

Update order status.

#### PUT /admin/api/orders/{id}

Update order.

**Request Body:**
```json
{
  "full_name": "Updated Name",
  "phone": "0987654321",
  "address": "Updated address"
}
```

---

### Deals Management

#### GET /admin/api/deals

Get deals list.

#### POST /admin/api/deals

Create deal.

**Request Body:**
```json
{
  "name": "Deal Bundle",
  "start": "2024-01-01 00:00:00",
  "end": "2024-01-31 23:59:59",
  "status": "1",
  "limited": 2,
  "products": [
    {
      "product_id": 10,
      "variant_id": 1,
      "price": 50000
    }
  ],
  "sale_products": [
    {
      "product_id": 2,
      "variant_id": 2,
      "price": 30000,
      "original_price": 50000
    }
  ]
}
```

#### PUT /admin/api/deals/{id}

Update deal.

#### DELETE /admin/api/deals/{id}

Delete deal.

#### PATCH /admin/api/deals/{id}/status

Update deal status.

---

### Sliders Management

#### GET /admin/api/sliders

Get sliders list.

#### POST /admin/api/sliders

Create slider.

#### PUT /admin/api/sliders/{id}

Update slider.

#### DELETE /admin/api/sliders/{id}

Delete slider.

#### PATCH /admin/api/sliders/{id}/status

Update slider status.

---

### Ingredient Dictionary

#### GET /admin/api/ingredients

Get ingredients list.

#### POST /admin/api/ingredients

Create ingredient.

#### PUT /admin/api/ingredients/{id}

Update ingredient.

#### DELETE /admin/api/ingredients/{id}

Delete ingredient.

#### PATCH /admin/api/ingredients/{id}/status

Update ingredient status.

#### POST /admin/api/ingredients/bulk-action

Bulk actions.

#### GET /admin/api/ingredients/crawl/summary

Get crawl summary.

#### POST /admin/api/ingredients/crawl/run

Run ingredient crawl.

### Ingredient Categories Management

#### GET /admin/api/ingredient-categories

Get ingredient categories list.

#### POST /admin/api/ingredient-categories

Create ingredient category.

#### PUT /admin/api/ingredient-categories/{id}

Update ingredient category.

#### DELETE /admin/api/ingredient-categories/{id}

Delete ingredient category.

#### PATCH /admin/api/ingredient-categories/{id}/status

Update ingredient category status.

#### POST /admin/api/ingredient-categories/bulk-action

Bulk actions for ingredient categories.

### Ingredient Benefits Management

#### GET /admin/api/ingredient-benefits

Get ingredient benefits list.

#### POST /admin/api/ingredient-benefits

Create ingredient benefit.

#### PUT /admin/api/ingredient-benefits/{id}

Update ingredient benefit.

#### DELETE /admin/api/ingredient-benefits/{id}

Delete ingredient benefit.

#### PATCH /admin/api/ingredient-benefits/{id}/status

Update ingredient benefit status.

#### POST /admin/api/ingredient-benefits/bulk-action

Bulk actions for ingredient benefits.

### Ingredient Rates Management

#### GET /admin/api/ingredient-rates

Get ingredient rates list.

#### POST /admin/api/ingredient-rates

Create ingredient rate.

#### PUT /admin/api/ingredient-rates/{id}

Update ingredient rate.

#### DELETE /admin/api/ingredient-rates/{id}

Delete ingredient rate.

#### PATCH /admin/api/ingredient-rates/{id}/status

Update ingredient rate status.

#### POST /admin/api/ingredient-rates/bulk-action

Bulk actions for ingredient rates.

---

### Google Merchant Center

#### GET /admin/api/gmc/products/preview

Preview GMC payload for a variant.

**Query Parameters:**
- `variant_id` (integer, required): Variant ID

**Response (200):**
```json
{
  "success": true,
  "data": {
    "offerId": "SKU-123",
    "title": "Product name",
    "availability": "in stock",
    "price": {
      "value": "120000",
      "currency": "VND"
    }
  }
}
```

#### POST /admin/api/gmc/products/sync

Sync variants to GMC.

**Request Body:**
```json
{
  "variant_ids": [123, 124],
  "dry_run": false
}
```

**Auto-push Triggers:**
1. Product/Variant saved (if `is_gmc_enabled` is checked)
2. Product added to active Marketing Campaign
3. Only variants are pushed (not parent products for VARIABLE products)

---

### Taxonomy Management

#### GET /admin/api/taxonomies

Get taxonomies (product categories) list.

#### POST /admin/api/taxonomies

Create taxonomy.

#### PUT /admin/api/taxonomies/{id}

Update taxonomy.

#### DELETE /admin/api/taxonomies/{id}

Delete taxonomy (only if no child categories).

#### PATCH /admin/api/taxonomies/{id}/status

Update taxonomy status.

#### POST /admin/api/taxonomies/bulk-action

Bulk actions.

#### PATCH /admin/api/taxonomies/sort

Update taxonomy hierarchy and sort order.

---

## Error Responses

All APIs follow a consistent error response format:

**400 Bad Request:**
```json
{
  "success": false,
  "message": "Invalid request data"
}
```

**401 Unauthorized:**
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

**404 Not Found:**
```json
{
  "success": false,
  "message": "Resource not found"
}
```

**422 Validation Error:**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

**500 Internal Server Error:**
```json
{
  "success": false,
  "message": "Server error",
  "error": "Detailed error (only in debug mode)"
}
```

---

## Notes

1. **Image URLs:** All image URLs are automatically formatted using R2 CDN
2. **Pagination:** All list endpoints support pagination
3. **Eager Loading:** Product queries use eager loading to optimize performance
4. **Price Calculation:** Backend always recalculates prices (don't trust frontend prices)
5. **Stock Validation:** Always validate stock from Warehouse before checkout
6. **Mixed Pricing:** Flash Sale supports mixed pricing when quantity exceeds Flash Sale limit

---

**Last Updated:** 2025-01-20  
**Version:** 1.0

