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
   - [Brands Management](#brands-management)
   - [Categories Management](#categories-management)
   - [Origins Management](#origins-management)
   - [Banners Management](#banners-management)
   - [Pages Management](#pages-management)
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

### Brands Management

#### GET /admin/api/brands

Get brands list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
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
      "content": "Brand description",
      "image": "https://cdn.lica.vn/uploads/image/brand.jpg",
      "banner": "https://cdn.lica.vn/uploads/image/banner.jpg",
      "logo": "https://cdn.lica.vn/uploads/image/logo.jpg",
      "gallery": [
        "https://cdn.lica.vn/uploads/image/gallery1.jpg"
      ],
      "seo_title": "Brand SEO Title",
      "seo_description": "Brand SEO Description",
      "status": "1",
      "sort": 0,
      "total_products": 150,
      "user": {
        "id": 1,
        "name": "Admin User"
      },
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/brands/{id}

Get single brand details.

**Path Parameters:**
- `id` (integer, required): Brand ID

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Brand Name",
    "slug": "brand-slug",
    "content": "Brand description",
    "image": "https://cdn.lica.vn/uploads/image/brand.jpg",
    "banner": "https://cdn.lica.vn/uploads/image/banner.jpg",
    "logo": "https://cdn.lica.vn/uploads/image/logo.jpg",
    "gallery": [
      "https://cdn.lica.vn/uploads/image/gallery1.jpg"
    ],
    "seo_title": "Brand SEO Title",
    "seo_description": "Brand SEO Description",
    "status": "1",
    "sort": 0,
    "total_products": 150,
    "user": {
      "id": 1,
      "name": "Admin User"
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

#### POST /admin/api/brands

Create a new brand.

**Request Body:**
```json
{
  "name": "Brand Name",
  "slug": "brand-slug",
  "content": "Brand description",
  "image": "https://cdn.lica.vn/uploads/image/brand.jpg",
  "banner": "https://cdn.lica.vn/uploads/image/banner.jpg",
  "logo": "https://cdn.lica.vn/uploads/image/logo.jpg",
  "gallery": [
    "https://cdn.lica.vn/uploads/image/gallery1.jpg"
  ],
  "seo_title": "Brand SEO Title",
  "seo_description": "Brand SEO Description",
  "status": "1",
  "sort": 0
}
```

**Validation Rules:**
- `name` (required, string, min:1, max:250): Brand name
- `slug` (optional, string, max:250, unique): Brand slug (auto-generated from name if not provided)
- `content` (optional, string): Brand description
- `image` (optional, string): Main brand image URL
- `banner` (optional, string): Brand banner image URL
- `logo` (optional, string): Brand logo image URL
- `gallery` (optional, array): Array of gallery image URLs
- `seo_title` (optional, string, max:255): SEO title
- `seo_description` (optional, string, max:500): SEO description
- `status` (required, in:0,1): Status (0=inactive, 1=active)
- `sort` (optional, integer, min:0): Sort order

**Response (201):**
```json
{
  "success": true,
  "message": "Brand created successfully",
  "data": {
    "id": 1,
    "name": "Brand Name",
    "slug": "brand-slug",
    "status": "1"
  }
}
```

#### PUT /admin/api/brands/{id}

Update an existing brand.

**Path Parameters:**
- `id` (integer, required): Brand ID

**Request Body:** (same as POST, all fields optional)

**Response (200):**
```json
{
  "success": true,
  "message": "Brand updated successfully",
  "data": {
    "id": 1,
    "name": "Updated Brand Name",
    "slug": "updated-brand-slug",
    "status": "1"
  }
}
```

#### DELETE /admin/api/brands/{id}

Delete a brand.

**Path Parameters:**
- `id` (integer, required): Brand ID

**Note:** Cannot delete brands that have associated products.

**Response (200):**
```json
{
  "success": true,
  "message": "Brand deleted successfully"
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Cannot delete brand. It has 5 associated product(s)."
}
```

#### PATCH /admin/api/brands/{id}/status

Update brand status.

**Path Parameters:**
- `id` (integer, required): Brand ID

**Request Body:**
```json
{
  "status": "1"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Brand status updated successfully",
  "data": {
    "id": 1,
    "status": "1"
  }
}
```

#### POST /admin/api/brands/bulk-action

Perform bulk actions on multiple brands.

**Request Body:**
```json
{
  "ids": [1, 2, 3],
  "action": 0
}
```

**Parameters:**
- `ids` (required, array): Array of brand IDs
- `action` (required, integer): Action to perform
  - `0`: Hide (set status to 0)
  - `1`: Show (set status to 1)
  - `2`: Delete

**Response (200):**
```json
{
  "success": true,
  "message": "Successfully hidden 3 brand(s)",
  "affected_count": 3
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Cannot delete brands with associated products",
  "brand_ids": [1, 2]
}
```

#### POST /admin/api/brands/upload

Upload brand images.

**Request:**
- Content-Type: `multipart/form-data`
- `files` (file, required): Image file(s) (jpeg, png, jpg, gif, webp, max 5MB)

**Response (200):**
```json
{
  "success": true,
  "data": [
    "/uploads/images/image/filename1.jpg",
    "/uploads/images/image/filename2.jpg"
  ]
}
```

**Note:** 
- Supports multiple file uploads
- Images are stored in `public/uploads/images/image/`
- Returns array of uploaded file paths

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

### Categories Management

#### GET /admin/api/categories

Get categories list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by category name
- `parent_id` (integer, optional): Filter by parent category ID

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
      "status": "1",
      "sort": 0,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/categories/{id}

Get single category details.

#### POST /admin/api/categories

Create a new category.

**Request Body:**
```json
{
  "name": "Category Name",
  "slug": "category-slug",
  "parent_id": null,
  "status": "1",
  "sort": 0
}
```

#### PUT /admin/api/categories/{id}

Update an existing category.

#### DELETE /admin/api/categories/{id}

Delete a category (only if no child categories).

#### PATCH /admin/api/categories/{id}/status

Update category status.

#### POST /admin/api/categories/bulk-action

Perform bulk actions on multiple categories.

#### POST /admin/api/categories/sort

Update category sort order and hierarchy.

#### GET /admin/api/categories/tree

Get categories in hierarchical tree structure.

---

### Origins Management

#### GET /admin/api/origins

Get origins list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by origin name

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Origin Name",
      "slug": "origin-slug",
      "status": "1",
      "sort": 0,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/origins/{id}

Get single origin details.

#### POST /admin/api/origins

Create a new origin.

#### PUT /admin/api/origins/{id}

Update an existing origin.

#### DELETE /admin/api/origins/{id}

Delete an origin.

#### PATCH /admin/api/origins/{id}/status

Update origin status.

#### POST /admin/api/origins/bulk-action

Perform bulk actions on multiple origins.

#### POST /admin/api/origins/sort

Update origin sort order.

---

### Banners Management

#### GET /admin/api/banners

Get banners list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by banner name

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Banner Name",
      "image": "https://cdn.lica.vn/uploads/image/banner.jpg",
      "link": "https://example.com",
      "status": "1",
      "sort": 0,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/banners/{id}

Get single banner details.

#### POST /admin/api/banners

Create a new banner.

#### PUT /admin/api/banners/{id}

Update an existing banner.

#### DELETE /admin/api/banners/{id}

Delete a banner.

#### PATCH /admin/api/banners/{id}/status

Update banner status.

#### POST /admin/api/banners/bulk-action

Perform bulk actions on multiple banners.

#### POST /admin/api/banners/sort

Update banner sort order.

---

### Pages Management

#### GET /admin/api/pages

Get pages list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by page title

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Page Title",
      "slug": "page-slug",
      "content": "Page content",
      "status": "1",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/pages/{id}

Get single page details.

#### POST /admin/api/pages

Create a new page.

#### PUT /admin/api/pages/{id}

Update an existing page.

#### DELETE /admin/api/pages/{id}

Delete a page.

#### PATCH /admin/api/pages/{id}/status

Update page status.

#### POST /admin/api/pages/bulk-action

Perform bulk actions on multiple pages.

---

### Marketing Campaign Management

#### GET /admin/api/marketing/campaigns

Get marketing campaigns list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by campaign name

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Campaign Name",
      "status": "1",
      "products": [
        {
          "id": 1,
          "product_id": 10,
          "product_name": "Product Name"
        }
      ],
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/marketing/campaigns/{id}

Get single campaign details.

#### POST /admin/api/marketing/campaigns

Create a new campaign.

#### PUT /admin/api/marketing/campaigns/{id}

Update an existing campaign.

#### DELETE /admin/api/marketing/campaigns/{id}

Delete a campaign.

#### PATCH /admin/api/marketing/campaigns/{id}/status

Update campaign status.

#### POST /admin/api/marketing/campaigns/{id}/products

Add products to campaign.

#### DELETE /admin/api/marketing/campaigns/{id}/products/{productId}

Remove product from campaign.

#### POST /admin/api/marketing/campaigns/search-products

Search products to add to campaign.

---

### Promotion Management

#### GET /admin/api/promotions

Get promotions list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by promotion name

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Promotion Name",
      "code": "PROMO123",
      "discount": 10,
      "status": "1",
      "sort": 0,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/promotions/{id}

Get single promotion details.

#### POST /admin/api/promotions

Create a new promotion.

#### PUT /admin/api/promotions/{id}

Update an existing promotion.

#### DELETE /admin/api/promotions/{id}

Delete a promotion.

#### PATCH /admin/api/promotions/{id}/status

Update promotion status.

#### POST /admin/api/promotions/bulk-action

Perform bulk actions on multiple promotions.

#### POST /admin/api/promotions/sort

Update promotion sort order.

---

### User Management

#### GET /admin/api/users

Get users list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by user name or email
- `role` (string, optional): Filter by role

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "User Name",
      "email": "user@example.com",
      "role": "admin",
      "status": "1",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/users/{id}

Get single user details.

#### POST /admin/api/users

Create a new user.

**Request Body:**
```json
{
  "name": "User Name",
  "email": "user@example.com",
  "password": "password123",
  "role": "admin",
  "status": "1"
}
```

#### PUT /admin/api/users/{id}

Update an existing user.

#### DELETE /admin/api/users/{id}

Delete a user.

#### PATCH /admin/api/users/{id}/status

Update user status.

#### POST /admin/api/users/{id}/change-password

Change user password.

**Request Body:**
```json
{
  "password": "newpassword123"
}
```

#### POST /admin/api/users/check-email

Check if email exists.

---

### Member Management

#### GET /admin/api/members

Get members list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by member name, email, or phone

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Member Name",
      "email": "member@example.com",
      "phone": "0123456789",
      "status": "1",
      "addresses": [
        {
          "id": 1,
          "name": "Home",
          "phone": "0123456789",
          "address": "123 Street",
          "province": "Ho Chi Minh",
          "district": "District 1",
          "ward": "Ward 1"
        }
      ],
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/members/{id}

Get single member details.

#### POST /admin/api/members

Create a new member.

#### PUT /admin/api/members/{id}

Update an existing member.

#### DELETE /admin/api/members/{id}

Delete a member.

#### PATCH /admin/api/members/{id}/status

Update member status.

#### POST /admin/api/members/{id}/addresses

Add address to member.

#### PUT /admin/api/members/{id}/addresses/{addressId}

Update member address.

#### DELETE /admin/api/members/{id}/addresses/{addressId}

Delete member address.

#### POST /admin/api/members/{id}/change-password

Change member password.

---

### Pick Management

#### GET /admin/api/picks

Get picks (warehouse locations) list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by pick name
- `province_id` (integer, optional): Filter by province ID
- `district_id` (integer, optional): Filter by district ID

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Pick Location Name",
      "address": "123 Street",
      "province": "Ho Chi Minh",
      "district": "District 1",
      "ward": "Ward 1",
      "status": "1",
      "sort": 0,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/picks/{id}

Get single pick details.

#### POST /admin/api/picks

Create a new pick location.

#### PUT /admin/api/picks/{id}

Update an existing pick location.

#### DELETE /admin/api/picks/{id}

Delete a pick location.

#### PATCH /admin/api/picks/{id}/status

Update pick status.

#### POST /admin/api/picks/bulk-action

Perform bulk actions on multiple picks.

#### POST /admin/api/picks/sort

Update pick sort order.

#### GET /admin/api/picks/districts/{provinceId}

Get districts by province ID.

#### GET /admin/api/picks/wards/{districtId}

Get wards by district ID.

---

### Role & Permission Management

#### GET /admin/api/roles

Get roles list with pagination.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Admin",
      "permissions": [
        {
          "id": 1,
          "name": "Manage Products"
        }
      ],
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/roles/{id}

Get single role details.

#### POST /admin/api/roles

Create a new role.

#### PUT /admin/api/roles/{id}

Update an existing role.

#### DELETE /admin/api/roles/{id}

Delete a role.

#### POST /admin/api/roles/{id}/permissions

Assign permissions to role.

**Request Body:**
```json
{
  "permission_ids": [1, 2, 3]
}
```

---

### Setting Management

#### GET /admin/api/settings

Get settings list with pagination.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 20, Max: 100
- `keyword` (string, optional): Search by setting key

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "key": "site_name",
      "value": "LICA",
      "group": "general"
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

#### GET /admin/api/settings/{id}

Get single setting details.

#### PUT /admin/api/settings/{id}

Update a setting.

#### PUT /admin/api/settings/key/{key}

Update setting by key.

**Path Parameters:**
- `key` (string, required): Setting key

**Request Body:**
```json
{
  "value": "New Value"
}
```

---

### Contact Management

#### GET /admin/api/contacts

Get contacts list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=unread, 1=read)
- `keyword` (string, optional): Search by name, email, or phone

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Contact Name",
      "email": "contact@example.com",
      "phone": "0123456789",
      "message": "Contact message",
      "status": "0",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/contacts/{id}

Get single contact details.

#### DELETE /admin/api/contacts/{id}

Delete a contact.

#### PATCH /admin/api/contacts/{id}/status

Update contact status (mark as read/unread).

---

### Feedback Management

#### GET /admin/api/feedbacks

Get feedbacks list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=unread, 1=read)
- `keyword` (string, optional): Search by name, email, or phone

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Feedback Name",
      "email": "feedback@example.com",
      "phone": "0123456789",
      "message": "Feedback message",
      "status": "0",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/feedbacks/{id}

Get single feedback details.

#### DELETE /admin/api/feedbacks/{id}

Delete a feedback.

#### PATCH /admin/api/feedbacks/{id}/status

Update feedback status (mark as read/unread).

---

### Subscriber Management

#### GET /admin/api/subscribers

Get subscribers list with pagination.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `keyword` (string, optional): Search by email

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "email": "subscriber@example.com",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### POST /admin/api/subscribers

Add a new subscriber.

**Request Body:**
```json
{
  "email": "subscriber@example.com"
}
```

#### DELETE /admin/api/subscribers/{id}

Delete a subscriber.

#### GET /admin/api/subscribers/export

Export subscribers to CSV.

---

### Tag Management

#### GET /admin/api/tags

Get tags list with pagination.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `keyword` (string, optional): Search by tag name

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Tag Name",
      "slug": "tag-slug",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/tags/{id}

Get single tag details.

#### POST /admin/api/tags

Create a new tag.

#### PUT /admin/api/tags/{id}

Update an existing tag.

#### DELETE /admin/api/tags/{id}

Delete a tag.

---

### Post Management

#### GET /admin/api/posts

Get posts list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by post title

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Post Title",
      "slug": "post-slug",
      "content": "Post content",
      "status": "1",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/posts/{id}

Get single post details.

#### POST /admin/api/posts

Create a new post.

#### PUT /admin/api/posts/{id}

Update an existing post.

#### DELETE /admin/api/posts/{id}

Delete a post.

#### PATCH /admin/api/posts/{id}/status

Update post status.

---

### Video Management

#### GET /admin/api/videos

Get videos list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by video title

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Video Title",
      "slug": "video-slug",
      "video_url": "https://youtube.com/watch?v=...",
      "status": "1",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/videos/{id}

Get single video details.

#### POST /admin/api/videos

Create a new video.

#### PUT /admin/api/videos/{id}

Update an existing video.

#### DELETE /admin/api/videos/{id}

Delete a video.

#### PATCH /admin/api/videos/{id}/status

Update video status.

---

### Rate Management

#### GET /admin/api/rates

Get rates (product reviews) list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by product name or user name

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product_id": 10,
      "product_name": "Product Name",
      "user_name": "User Name",
      "rate": 5,
      "comment": "Great product!",
      "status": "1",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/rates/{id}

Get single rate details.

#### DELETE /admin/api/rates/{id}

Delete a rate.

#### PATCH /admin/api/rates/{id}/status

Update rate status.

---

### Dashboard Management

#### GET /admin/api/dashboard/statistics

Get dashboard statistics.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total_orders": 1000,
    "total_revenue": 50000000,
    "total_products": 500,
    "total_members": 2000
  }
}
```

#### GET /admin/api/dashboard/charts

Get dashboard chart data.

**Query Parameters:**
- `period` (string, optional): Period (daily, weekly, monthly). Default: monthly
- `date_from` (string, optional): Start date (Y-m-d)
- `date_to` (string, optional): End date (Y-m-d)

#### GET /admin/api/dashboard/recent-orders

Get recent orders.

**Query Parameters:**
- `limit` (integer, optional): Number of orders. Default: 10

#### GET /admin/api/dashboard/top-products

Get top selling products.

**Query Parameters:**
- `limit` (integer, optional): Number of products. Default: 10

---

### Showroom Management

#### GET /admin/api/showrooms

Get showrooms list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `cat_id` (integer, optional): Filter by category ID
- `keyword` (string, optional): Search by showroom name

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Showroom Name",
      "image": "https://cdn.lica.vn/uploads/image/showroom.jpg",
      "address": "123 Street",
      "phone": "0123456789",
      "cat_id": 1,
      "status": "1",
      "sort": 0,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/showrooms/{id}

Get single showroom details.

#### POST /admin/api/showrooms

Create a new showroom.

#### PUT /admin/api/showrooms/{id}

Update an existing showroom.

#### DELETE /admin/api/showrooms/{id}

Delete a showroom.

---

### Menu Management

#### GET /admin/api/menus

Get menus list (hierarchical tree structure).

**Query Parameters:**
- `group_id` (integer, optional): Filter by menu group ID

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Menu Item",
      "url": "/menu-item",
      "parent": 0,
      "group_id": 1,
      "status": "1",
      "sort": 0,
      "children": [
        {
          "id": 2,
          "name": "Sub Menu Item",
          "url": "/sub-menu-item",
          "parent": 1,
          "group_id": 1,
          "status": "1",
          "sort": 0
        }
      ],
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

#### GET /admin/api/menus/{id}

Get single menu details.

#### POST /admin/api/menus

Create a new menu item.

#### PUT /admin/api/menus/{id}

Update an existing menu item.

#### DELETE /admin/api/menus/{id}

Delete a menu item.

#### POST /admin/api/menus/sort

Update menu sort order and hierarchy.

**Request Body:**
```json
{
  "sortable": [
    {
      "item_id": 1,
      "parent_id": 0,
      "sort": 0
    },
    {
      "item_id": 2,
      "parent_id": 1,
      "sort": 0
    }
  ]
}
```

---

### Footer Block Management

#### GET /admin/api/footer-blocks

Get footer blocks list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by block title

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Footer Block Title",
      "tags": ["tag1", "tag2"],
      "links": [
        {
          "name": "Link Name",
          "url": "/link-url"
        }
      ],
      "status": "1",
      "sort": 0,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/footer-blocks/{id}

Get single footer block details.

#### POST /admin/api/footer-blocks

Create a new footer block.

#### PUT /admin/api/footer-blocks/{id}

Update an existing footer block.

#### DELETE /admin/api/footer-blocks/{id}

Delete a footer block.

---

### Redirection Management

#### GET /admin/api/redirections

Get redirections list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by link_from or link_to

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "link_from": "/old-url",
      "link_to": "/new-url",
      "type": "301",
      "status": "1",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/redirections/{id}

Get single redirection details.

#### POST /admin/api/redirections

Create a new redirection.

**Request Body:**
```json
{
  "link_from": "/old-url",
  "link_to": "/new-url",
  "type": "301",
  "status": "1"
}
```

#### PUT /admin/api/redirections/{id}

Update an existing redirection.

#### DELETE /admin/api/redirections/{id}

Delete a redirection.

---

### Selling Management

#### GET /admin/api/sellings

Get selling records list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by name

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Selling Record Name",
      "product_id": 10,
      "image": "https://cdn.lica.vn/uploads/image/selling.jpg",
      "status": "1",
      "product": {
        "id": 10,
        "name": "Product Name",
        "slug": "product-slug"
      },
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/sellings/{id}

Get single selling record details.

---

### Search Management

#### GET /admin/api/search/logs

Get search logs with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status
- `keyword` (string, optional): Search by search term

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "search term",
      "status": "1",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/search/analytics

Get search analytics.

**Query Parameters:**
- `date_from` (string, optional): Start date (Y-m-d). Default: 30 days ago
- `date_to` (string, optional): End date (Y-m-d). Default: today

**Response (200):**
```json
{
  "success": true,
  "data": {
    "popular_searches": [
      {
        "name": "product name",
        "count": 100
      }
    ],
    "date_from": "2024-01-01",
    "date_to": "2024-01-31"
  }
}
```

---

### Download Management

#### GET /admin/api/downloads

Get downloads list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 10, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `keyword` (string, optional): Search by download name

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Download Name",
      "slug": "download-slug",
      "file": "/uploads/files/file.pdf",
      "description": "Download description",
      "content": "Download content",
      "status": "1",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

#### GET /admin/api/downloads/{id}

Get single download details.

#### POST /admin/api/downloads

Create a new download.

#### PUT /admin/api/downloads/{id}

Update an existing download.

#### DELETE /admin/api/downloads/{id}

Delete a download.

---

### Config Management

#### GET /admin/api/configs

Get configs list with pagination.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 20, Max: 100
- `keyword` (string, optional): Search by config key

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "key": "site_name",
      "value": "LICA",
      "group": "general"
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

#### GET /admin/api/configs/{id}

Get single config details.

#### POST /admin/api/configs

Create a new config.

**Request Body:**
```json
{
  "key": "config_key",
  "value": "config_value",
  "group": "general"
}
```

#### PUT /admin/api/configs/{id}

Update an existing config.

#### DELETE /admin/api/configs/{id}

Delete a config.

---

### Compare Management

#### GET /admin/api/compares

Get compares list with pagination and filters.

**Query Parameters:**
- `page` (integer, optional): Page number. Default: 1
- `limit` (integer, optional): Items per page. Default: 20, Max: 100
- `status` (string, optional): Filter by status (0=inactive, 1=active)
- `store_id` (integer, optional): Filter by store ID
- `keyword` (string, optional): Search by name or brand

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Compare Record Name",
      "brand": "Brand Name",
      "link": "https://example.com",
      "store_id": 1,
      "status": "1",
      "store": {
        "id": 1,
        "name": "Store Name"
      },
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
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

#### GET /admin/api/compares/{id}

Get single compare record details.

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

## Lộ Trình Nâng Cấp Backend V2

### Tổng Quan

Phần này trình bày kế hoạch nâng cấp toàn diện để hiện đại hóa backend LICA theo tiêu chuẩn công nghiệp 2026. Việc nâng cấp tập trung vào hiệu suất, khả năng mở rộng, khả năng bảo trì và trải nghiệm nhà phát triển.

### Trạng Thái Giai Đoạn 1: Nền Tảng ✅

**Ngày kiểm tra:** 2025-01-21  
**Trạng thái:** ✅ **HOÀN THÀNH (95%) - Tất cả tests đã PASS**

**Đã hoàn thành (Cấu hình - 100%):**
- ✅ Cấu hình Redis cho cache, sessions, và queues (config files)
- ✅ Docker environment setup (Dockerfile, docker-compose.yml)
- ✅ CI/CD pipeline (GitHub Actions - `.github/workflows/ci.yml`)
- ✅ Code quality tools (Pint, PHPStan) - đã cấu hình
- ✅ Script thêm strict types (`scripts/add-strict-types.php`)
- ✅ **435 PHP files** đã có `declare(strict_types=1)`
- ✅ Cập nhật `.env` với Redis configuration (CACHE_DRIVER, SESSION_DRIVER, QUEUE_CONNECTION)

**Đã thực thi (95%):**
- ✅ **Nâng cấp PHP từ 8.1.32 lên 8.3.28** ✅ **ĐÃ HOÀN THÀNH**
- ✅ Chạy `composer update` - Dependencies đã cập nhật
- ✅ **Test Redis connection: PASSED** ✅
- ✅ **Chạy `composer pint`: 751 files formatted** ✅
- ✅ **Chạy `composer phpstan`: Analysis completed** ✅
- ✅ **Test queue: SUCCESS (Job processed)** ✅
- ⏳ Cài đặt monitoring tools (Telescope, Sentry) - Tùy chọn

**Đã giải quyết:**
- ✅ **PHP Version:** Đã nâng cấp lên PHP 8.3.28
- ✅ **Dependencies:** Laravel 11.48.0 hoạt động đầy đủ
- ✅ **Redis:** Đã test và hoạt động (Cache, Session, Queue)

**Tài liệu:**
- `PHASE1_NEXT_STEPS.md` - ⭐ **Bắt đầu từ đây** - Hướng dẫn chi tiết
- `PHASE1_PROGRESS_REPORT.md` - Báo cáo tiến độ đầy đủ
- `PHASE1_STATUS_SUMMARY.md` - Tóm tắt trạng thái
- `PHASE1_SETUP_GUIDE.md` - Hướng dẫn setup chi tiết
- `PHASE1_COMPLETION_CHECKLIST.md` - Checklist hoàn thành
- `PHASE1_HOAN_TAT.md` - Tóm tắt công việc đã làm

### Công Nghệ Hiện Tại

| Thành Phần | Phiên Bản Hiện Tại | Phiên Bản Mục Tiêu | Trạng Thái |
|------------|-------------------|---------------------|------------|
| Laravel Framework | 11.48.0 | 11.x LTS | ✅ Hoàn thành |
| PHP | 8.3.28 | 8.3+ | ✅ Hoàn thành |
| Database | MySQL/MariaDB | PostgreSQL 16+ | ⏳ Chờ thực hiện |
| Cache | Redis 7+ | Redis 7+ | ✅ Hoàn thành |
| Queue | Redis | Redis/RabbitMQ | ✅ Hoàn thành |
| Frontend Build | Laravel Mix | Vite | ⏳ Chờ thực hiện |
| Frontend Framework | Vue 2 | Vue 3 / React 18 | ⏳ Chờ thực hiện |

### Timeline Các Giai Đoạn Nâng Cấp

| Giai Đoạn | Thời Gian | Tuần | Kết Quả Chính | Trạng Thái |
|-----------|-----------|------|---------------|------------|
| **Giai Đoạn 1: Nền Tảng** | 4 tuần | 1-4 | Nâng cấp stack, thiết lập hạ tầng, công cụ chất lượng code | 📋 Đã lên kế hoạch |
| **Giai Đoạn 2: Tái Cấu Trúc Kiến Trúc** | 8 tuần | 5-12 | Repository pattern, DTOs, Action classes, Kiến trúc Event-driven | 📋 Đã lên kế hoạch |
| **Giai Đoạn 3: Chuẩn Hóa API** | 4 tuần | 13-16 | API v2, xác thực, tài liệu OpenAPI, phản hồi chuẩn hóa | 📋 Đã lên kế hoạch |
| **Giai Đoạn 4: Tối Ưu Hiệu Suất** | 4 tuần | 17-20 | Tối ưu database, caching, tối ưu queue, hiệu suất API | 📋 Đã lên kế hoạch |
| **Giai Đoạn 5: Kiểm Thử & QA** | 4 tuần | 21-24 | 80%+ test coverage, unit/feature/integration/E2E tests | 📋 Đã lên kế hoạch |
| **Giai Đoạn 6: Giám Sát & Quan Sát** | 2 tuần | 25-26 | Logging, metrics, dashboards, cảnh báo | 📋 Đã lên kế hoạch |
| **Giai Đoạn 7: Tài Liệu & Đào Tạo** | 2 tuần | 27-28 | Tài liệu kỹ thuật, tài liệu API, đào tạo team | 📋 Đã lên kế hoạch |
| **Tổng Cộng** | **28 tuần** | **1-28** | **Backend V2 Sẵn Sàng Production** | 📋 Đã lên kế hoạch |

### Giai Đoạn 1: Nền Tảng (Tuần 1-4)

| Nhiệm Vụ | Mô Tả | Ưu Tiên | Thời Gian Ước Tính |
|----------|-------|---------|-------------------|
| Nâng Cấp Laravel | Nâng cấp từ 10.x lên 11.x LTS | 🔴 Cao | 1 tuần |
| Nâng Cấp PHP | Nâng cấp từ 8.1 lên 8.3+ | 🔴 Cao | 3 ngày |
| Cập Nhật Dependencies | Cập nhật tất cả gói composer | 🔴 Cao | 2 ngày |
| Bật Strict Types | Thêm `declare(strict_types=1)` vào tất cả files | 🟡 Trung bình | 3 ngày |
| Thiết Lập Redis | Cấu hình Redis cho cache/sessions | 🔴 Cao | 2 ngày |
| Thiết Lập Redis Queue | Cấu hình Redis cho xử lý queue | 🔴 Cao | 2 ngày |
| Môi Trường Docker | Thiết lập Docker & Docker Compose | 🟡 Trung bình | 1 tuần |
| CI/CD Pipeline | Thiết lập GitHub Actions | 🟡 Trung bình | 3 ngày |
| Công Cụ Chất Lượng Code | Thiết lập Laravel Pint, PHPStan | 🟢 Thấp | 2 ngày |
| Thiết Lập Giám Sát | Thiết lập Sentry, Telescope | 🟡 Trung bình | 2 ngày |

### Giai Đoạn 2: Tái Cấu Trúc Kiến Trúc (Tuần 5-12)

| Nhiệm Vụ | Mô Tả | Ưu Tiên | Thời Gian Ước Tính |
|----------|-------|---------|-------------------|
| Base Repository Interface | Tạo repository contract | 🔴 Cao | 2 ngày |
| Triển Khai Repositories | Tạo repositories cho tất cả entities | 🔴 Cao | 2 tuần |
| Di Chuyển Logic DB | Di chuyển logic database từ controllers | 🔴 Cao | 1 tuần |
| Tạo DTOs | Tạo DTOs cho các thao tác phức tạp | 🔴 Cao | 1 tuần |
| Action Classes | Tạo action classes cho các thao tác đơn lẻ | 🔴 Cao | 1 tuần |
| Tái Cấu Trúc Services Lớn | Chia nhỏ CartService (2300+ dòng) | 🔴 Cao | 2 tuần |
| Service Interfaces | Thêm interfaces cho tất cả services | 🟡 Trung bình | 3 ngày |
| Kiến Trúc Event-Driven | Tạo domain events và listeners | 🟡 Trung bình | 1 tuần |
| Dependency Injection | Triển khai DI đúng cách toàn bộ | 🟡 Trung bình | 3 ngày |

### Giai Đoạn 3: Chuẩn Hóa API (Tuần 13-16)

| Nhiệm Vụ | Mô Tả | Ưu Tiên | Thời Gian Ước Tính |
|----------|-------|---------|-------------------|
| Chiến Lược API Versioning | Triển khai routes `/api/v2/` | 🔴 Cao | 3 ngày |
| Laravel Sanctum | Triển khai xác thực dựa trên token | 🔴 Cao | 1 tuần |
| Thiết Lập OAuth2 | Thiết lập Laravel Passport cho bên thứ ba | 🟡 Trung bình | 3 ngày |
| Tài Liệu OpenAPI | Tạo OpenAPI 3.0 specification | 🔴 Cao | 1 tuần |
| Swagger UI | Thiết lập Swagger UI cho tài liệu API | 🟡 Trung bình | 2 ngày |
| Chuẩn Hóa Phản Hồi | Tạo API Resources nhất quán | 🔴 Cao | 1 tuần |
| Rate Limiting | Triển khai giới hạn tốc độ theo user/IP | 🔴 Cao | 2 ngày |
| Quản Lý API Key | Thiết lập hệ thống API key | 🟡 Trung bình | 3 ngày |
| Cấu Hình CORS | Thiết lập CORS đúng cách | 🟡 Trung bình | 1 ngày |
| Làm Sạch Đầu Vào | Tăng cường validation đầu vào | 🔴 Cao | 3 ngày |

### Giai Đoạn 4: Tối Ưu Hiệu Suất (Tuần 17-20)

| Nhiệm Vụ | Mô Tả | Ưu Tiên | Thời Gian Ước Tính |
|----------|-------|---------|-------------------|
| Sửa N+1 Queries | Triển khai eager loading | 🔴 Cao | 1 tuần |
| Database Indexes | Thêm các indexes còn thiếu | 🔴 Cao | 3 ngày |
| Tối Ưu Query | Tối ưu các queries chậm | 🔴 Cao | 1 tuần |
| Redis Caching | Triển khai caching toàn diện | 🔴 Cao | 1 tuần |
| Chiến Lược Cache | Định nghĩa quy tắc cache invalidation | 🟡 Trung bình | 2 ngày |
| Tối Ưu Queue | Di chuyển các thao tác nặng vào queues | 🔴 Cao | 1 tuần |
| Job Batching | Triển khai job batching | 🟡 Trung bình | 2 ngày |
| Cache Phản Hồi API | Cache các phản hồi API | 🟡 Trung bình | 3 ngày |
| Triển Khai ETags | Thêm conditional requests | 🟢 Thấp | 2 ngày |
| Nén | Bật gzip compression | 🟡 Trung bình | 1 ngày |

### Giai Đoạn 5: Kiểm Thử & QA (Tuần 21-24)

| Nhiệm Vụ | Mô Tả | Ưu Tiên | Thời Gian Ước Tính |
|----------|-------|---------|-------------------|
| Unit Tests | Kiểm thử tất cả services, repositories, actions | 🔴 Cao | 2 tuần |
| Feature Tests | Kiểm thử tất cả API endpoints | 🔴 Cao | 1 tuần |
| Integration Tests | Kiểm thử database, queues, events | 🟡 Trung bình | 1 tuần |
| E2E Tests | Kiểm thử các luồng người dùng quan trọng | 🟡 Trung bình | 3 ngày |
| Performance Tests | Load và stress testing | 🟡 Trung bình | 3 ngày |
| Test Coverage | Đạt 80%+ coverage | 🔴 Cao | Liên tục |
| PHPStan Level 8 | Static analysis level 8 | 🟡 Trung bình | 1 tuần |

### Giai Đoạn 6: Giám Sát & Quan Sát (Tuần 25-26)

| Nhiệm Vụ | Mô Tả | Ưu Tiên | Thời Gian Ước Tính |
|----------|-------|---------|-------------------|
| Structured Logging | Logging định dạng JSON | 🔴 Cao | 3 ngày |
| Log Aggregation | Thiết lập ELK/Loki stack | 🟡 Trung bình | 1 tuần |
| Error Tracking | Cấu hình Sentry | 🔴 Cao | 2 ngày |
| Application Metrics | Thiết lập Prometheus | 🟡 Trung bình | 3 ngày |
| Business Metrics | Theo dõi KPIs kinh doanh | 🟡 Trung bình | 2 ngày |
| Grafana Dashboards | Tạo dashboards giám sát | 🟡 Trung bình | 3 ngày |
| Alerting Rules | Thiết lập cảnh báo cho lỗi/hiệu suất | 🔴 Cao | 2 ngày |

### Giai Đoạn 7: Tài Liệu & Đào Tạo (Tuần 27-28)

| Nhiệm Vụ | Mô Tả | Ưu Tiên | Thời Gian Ước Tính |
|----------|-------|---------|-------------------|
| Tài Liệu Kiến Trúc | Tài liệu hóa kiến trúc hệ thống | 🔴 Cao | 3 ngày |
| Tài Liệu API | Hoàn thiện tài liệu tham khảo API | 🔴 Cao | 1 tuần |
| Database Schema | Tài liệu hóa cấu trúc database | 🟡 Trung bình | 2 ngày |
| Hướng Dẫn Triển Khai | Các bước triển khai production | 🔴 Cao | 2 ngày |
| Thiết Lập Development | Hướng dẫn development local | 🟡 Trung bình | 1 ngày |
| Tài Liệu Code | PHPDoc cho tất cả classes | 🟡 Trung bình | 3 ngày |
| Đào Tạo Team | Đào tạo các patterns kiến trúc | 🟡 Trung bình | 3 ngày |

### Các Pattern Kiến Trúc Cần Triển Khai

| Pattern | Mục Đích | Ưu Tiên | Trạng Thái |
|---------|---------|---------|------------|
| **Repository Pattern** | Trừu tượng hóa truy cập database | 🔴 Cao | 📋 Đã lên kế hoạch |
| **DTOs (Data Transfer Objects)** | Chuyển dữ liệu an toàn kiểu | 🔴 Cao | 📋 Đã lên kế hoạch |
| **Action Classes** | Các thao tác trách nhiệm đơn lẻ | 🔴 Cao | 📋 Đã lên kế hoạch |
| **Service Layer** | Đóng gói logic nghiệp vụ | 🔴 Cao | 📋 Đã lên kế hoạch |
| **Event-Driven** | Xử lý bất đồng bộ | 🟡 Trung bình | 📋 Đã lên kế hoạch |
| **CQRS** | Tách biệt Command và Query | 🟢 Thấp | 📋 Đã lên kế hoạch |
| **Factory Pattern** | Tạo đối tượng | 🟡 Trung bình | 📋 Đã lên kế hoạch |
| **Strategy Pattern** | Lựa chọn thuật toán | 🟡 Trung bình | 📋 Đã lên kế hoạch |

### Các Chỉ Số & Tiêu Chí Thành Công

| Chỉ Số | Hiện Tại | Mục Tiêu | Trạng Thái |
|--------|----------|----------|------------|
| **Thời Gian Phản Hồi API (p95)** | ~500ms | < 200ms | 📊 Cần đo lường |
| **Thời Gian Query Database (p95)** | ~100ms | < 50ms | 📊 Cần đo lường |
| **Test Coverage** | ~10% | > 80% | 📊 Cần đo lường |
| **PHPStan Level** | 0 | 8 | 📊 Cần đo lường |
| **Code Review Coverage** | ~50% | 100% | 📊 Cần đo lường |
| **Thời Gian CI/CD Pipeline** | N/A | < 5 phút | 📊 Cần đo lường |
| **Thời Gian Setup** | ~30 phút | < 10 phút | 📊 Cần đo lường |
| **Documentation Coverage** | ~40% | 100% | 📊 Cần đo lường |

### Đánh Giá Rủi Ro

| Rủi Ro | Tác Động | Xác Suất | Giảm Thiểu |
|--------|----------|----------|------------|
| Vấn Đề Migration Database | 🔴 Cao | 🟡 Trung bình | Kiểm thử kỹ lưỡng, kế hoạch rollback |
| Thay Đổi Breaking API | 🔴 Cao | 🟡 Trung bình | Versioning, thời gian deprecation |
| Suy Giảm Hiệu Suất | 🟡 Trung bình | 🟡 Trung bình | Load testing, giám sát |
| Đường Cong Học Tập Team | 🟡 Trung bình | 🟢 Thấp | Đào tạo, tài liệu |
| Dependencies Bên Thứ Ba | 🟡 Trung bình | 🟢 Thấp | Đánh giá vendor, phương án thay thế |
| Vượt Timeline | 🟡 Trung bình | 🟡 Trung bình | Agile sprints, review định kỳ |

### Các Gói Được Khuyến Nghị

| Gói | Mục Đích | Phiên Bản |
|-----|----------|-----------|
| `laravel/framework` | Framework cốt lõi | ^11.0 |
| `laravel/sanctum` | Xác thực API | ^4.0 |
| `laravel/passport` | OAuth2 server | ^12.0 |
| `spatie/laravel-permission` | Role & permissions | ^6.0 |
| `spatie/data-transfer-object` | DTOs | ^3.0 |
| `spatie/laravel-query-builder` | Query building | ^5.0 |
| `laravel/horizon` | Giám sát queue | ^5.0 |
| `laravel/telescope` | Debugging | ^5.0 |
| `pestphp/pest` | Framework kiểm thử | ^2.0 |
| `phpstan/phpstan` | Static analysis | ^1.10 |
| `laravel/pint` | Code style | ^1.0 |

### Chiến Lược Migration

| Chiến Lược | Mô Tả | Timeline |
|------------|-------|----------|
| **Chạy Song Song** | Chạy V1 và V2 APIs đồng thời | 6 tháng |
| **Feature Flags** | Sử dụng flags cho rollout dần dần | Liên tục |
| **Database Migrations** | Migrations tương thích ngược | Theo từng giai đoạn |
| **API Deprecation** | Thông báo deprecation 6 tháng | Theo từng endpoint |
| **Client Migration** | Migration client dần dần | 6 tháng |

### Chi Tiết Từng Nhiệm Vụ

#### Giai Đoạn 1: Nền Tảng - Chi Tiết Nhiệm Vụ

| Task ID | Tên Nhiệm Vụ | Mô Tả | Người Phụ Trách | Trạng Thái | Ưu Tiên | Thời Gian Ước Tính | Thời Gian Thực Tế | Ngày Bắt Đầu | Ngày Kết Thúc | Phụ Thuộc | Ghi Chú |
|---------|--------------|-------|-----------------|------------|---------|-------------------|-------------------|--------------|---------------|-----------|---------|
| P1-T1 | Nâng Cấp Laravel | Nâng cấp từ 10.x lên 11.x LTS, kiểm thử tất cả tính năng | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | - | Cần review breaking changes |
| P1-T2 | Nâng Cấp PHP | Nâng cấp từ 8.1 lên 8.3+, cập nhật cấu hình server | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 3 ngày | - | - | - | P1-T1 | Kiểm thử tất cả tính năng PHP 8.3 |
| P1-T3 | Cập Nhật Dependencies | Cập nhật tất cả gói composer, giải quyết conflicts | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 2 ngày | - | - | - | P1-T1 | Kiểm tra breaking changes |
| P1-T4 | Bật Strict Types | Thêm `declare(strict_types=1)` vào tất cả PHP files | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 3 ngày | - | - | - | P1-T2 | Sửa lỗi type |
| P1-T5 | Thiết Lập Redis | Cấu hình Redis cho cache và sessions | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 2 ngày | - | - | - | - | Thiết lập Redis production |
| P1-T6 | Thiết Lập Redis Queue | Cấu hình Redis cho xử lý queue | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 2 ngày | - | - | - | P1-T5 | Thiết lập queue workers |
| P1-T7 | Môi Trường Docker | Thiết lập Docker & Docker Compose cho dev | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 1 tuần | - | - | - | - | Thiết lập multi-container |
| P1-T8 | CI/CD Pipeline | Thiết lập GitHub Actions cho automated testing | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 3 ngày | - | - | - | - | Test, build, deploy |
| P1-T9 | Công Cụ Chất Lượng Code | Thiết lập Laravel Pint, PHPStan level 8 | TBD | 📋 Đã lên kế hoạch | 🟢 Thấp | 2 ngày | - | - | - | P1-T1 | Pre-commit hooks |
| P1-T10 | Thiết Lập Giám Sát | Thiết lập Sentry, Telescope cho debugging | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 2 ngày | - | - | - | - | Error tracking |

#### Giai Đoạn 2: Tái Cấu Trúc Kiến Trúc - Chi Tiết Nhiệm Vụ

| Task ID | Tên Nhiệm Vụ | Mô Tả | Người Phụ Trách | Trạng Thái | Ưu Tiên | Thời Gian Ước Tính | Thời Gian Thực Tế | Ngày Bắt Đầu | Ngày Kết Thúc | Phụ Thuộc | Ghi Chú |
|---------|--------------|-------|-----------------|------------|---------|-------------------|-------------------|--------------|---------------|-----------|---------|
| P2-T1 | Base Repository Interface | Tạo repository contract với các methods chung | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 2 ngày | - | - | - | - | Định nghĩa interface trước |
| P2-T2 | Product Repository | Triển khai ProductRepository với tất cả methods | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 3 ngày | - | - | - | P2-T1 | Tái cấu trúc code hiện có |
| P2-T3 | Category Repository | Triển khai CategoryRepository | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 2 ngày | - | - | - | P2-T1 | Hỗ trợ cấu trúc tree |
| P2-T4 | Order Repository | Triển khai OrderRepository | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 2 ngày | - | - | - | P2-T1 | Queries phức tạp |
| P2-T5 | Brand Repository | Triển khai BrandRepository | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 1 ngày | - | - | - | P2-T1 | CRUD đơn giản |
| P2-T6 | Các Repository Còn Lại | Triển khai tất cả repositories khác (20+ entities) | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1.5 tuần | - | - | - | P2-T1 | Triển khai theo batch |
| P2-T7 | Di Chuyển Logic DB | Di chuyển logic database từ controllers sang repositories | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | P2-T2-P2-T6 | Tái cấu trúc controllers |
| P2-T8 | Tạo DTOs | Tạo DTOs cho các thao tác phức tạp (Product, Order, Cart) | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | - | Sử dụng Spatie DTO |
| P2-T9 | Tạo Action Classes | Tạo action classes cho các thao tác đơn lẻ | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | P2-T8 | Thay thế các methods lớn |
| P2-T10 | Tái Cấu Trúc CartService | Chia nhỏ CartService (2300+ dòng) thành các services nhỏ hơn | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 2 tuần | - | - | - | P2-T8, P2-T9 | Service quan trọng |
| P2-T11 | Service Interfaces | Thêm interfaces cho tất cả services | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 3 ngày | - | - | - | P2-T10 | Dependency injection |
| P2-T12 | Kiến Trúc Event-Driven | Tạo domain events và listeners | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 1 tuần | - | - | - | - | Xử lý bất đồng bộ |
| P2-T13 | Dependency Injection | Triển khai DI đúng cách toàn bộ ứng dụng | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 3 ngày | - | - | - | P2-T11 | Service container |

#### Giai Đoạn 3: Chuẩn Hóa API - Chi Tiết Nhiệm Vụ

| Task ID | Tên Nhiệm Vụ | Mô Tả | Người Phụ Trách | Trạng Thái | Ưu Tiên | Thời Gian Ước Tính | Thời Gian Thực Tế | Ngày Bắt Đầu | Ngày Kết Thúc | Phụ Thuộc | Ghi Chú |
|---------|--------------|-------|-----------------|------------|---------|-------------------|-------------------|--------------|---------------|-----------|---------|
| P3-T1 | Chiến Lược API Versioning | Triển khai cấu trúc routes `/api/v2/` | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 3 ngày | - | - | - | - | Tương thích ngược |
| P3-T2 | Laravel Sanctum | Triển khai xác thực dựa trên token | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | - | Thay thế session auth |
| P3-T3 | Thiết Lập OAuth2 | Thiết lập Laravel Passport cho truy cập bên thứ ba | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 3 ngày | - | - | - | P3-T2 | Tích hợp bên ngoài |
| P3-T4 | Tài Liệu OpenAPI | Tạo OpenAPI 3.0 specification | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | - | Tự động tạo từ code |
| P3-T5 | Swagger UI | Thiết lập Swagger UI cho tài liệu API tương tác | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 2 ngày | - | - | - | P3-T4 | Tài liệu thân thiện |
| P3-T6 | Chuẩn Hóa Phản Hồi | Tạo API Resources nhất quán | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | P3-T1 | Tất cả endpoints |
| P3-T7 | Rate Limiting | Triển khai giới hạn tốc độ theo user/IP | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 2 ngày | - | - | - | P3-T2 | Ngăn chặn lạm dụng |
| P3-T8 | Quản Lý API Key | Thiết lập hệ thống API key cho truy cập bên ngoài | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 3 ngày | - | - | - | P3-T2 | Key rotation |
| P3-T9 | Cấu Hình CORS | Thiết lập CORS đúng cách cho cross-origin requests | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 1 ngày | - | - | - | - | Security headers |
| P3-T10 | Làm Sạch Đầu Vào | Tăng cường validation và sanitization đầu vào | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 3 ngày | - | - | - | - | Ngăn chặn XSS |

#### Giai Đoạn 4: Tối Ưu Hiệu Suất - Chi Tiết Nhiệm Vụ

| Task ID | Tên Nhiệm Vụ | Mô Tả | Người Phụ Trách | Trạng Thái | Ưu Tiên | Thời Gian Ước Tính | Thời Gian Thực Tế | Ngày Bắt Đầu | Ngày Kết Thúc | Phụ Thuộc | Ghi Chú |
|---------|--------------|-------|-----------------|------------|---------|-------------------|-------------------|--------------|---------------|-----------|---------|
| P4-T1 | Sửa N+1 Queries | Triển khai eager loading cho tất cả relationships | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | P2-T7 | Tối ưu query |
| P4-T2 | Database Indexes | Thêm indexes còn thiếu cho các cột thường query | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 3 ngày | - | - | - | - | Quan trọng cho hiệu suất |
| P4-T3 | Tối Ưu Query | Tối ưu các queries chậm được xác định trong logs | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | P4-T1 | Sử dụng query analyzer |
| P4-T4 | Redis Caching | Triển khai chiến lược caching toàn diện | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | P1-T5 | Các lớp cache |
| P4-T5 | Chiến Lược Cache | Định nghĩa quy tắc cache invalidation | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 2 ngày | - | - | - | P4-T4 | Cache tags |
| P4-T6 | Tối Ưu Queue | Di chuyển các thao tác nặng vào queues | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | P1-T6 | Xử lý bất đồng bộ |
| P4-T7 | Job Batching | Triển khai job batching cho các thao tác bulk | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 2 ngày | - | - | - | P4-T6 | Tăng hiệu suất |
| P4-T8 | Cache Phản Hồi API | Cache các phản hồi API cho GET requests | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 3 ngày | - | - | - | P4-T4 | Hỗ trợ ETag |
| P4-T9 | Triển Khai ETags | Thêm conditional requests với ETags | TBD | 📋 Đã lên kế hoạch | 🟢 Thấp | 2 ngày | - | - | - | P4-T8 | 304 Not Modified |
| P4-T10 | Nén | Bật gzip compression cho responses | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 1 ngày | - | - | - | - | Cấu hình Nginx |

#### Giai Đoạn 5: Kiểm Thử & QA - Chi Tiết Nhiệm Vụ

| Task ID | Tên Nhiệm Vụ | Mô Tả | Người Phụ Trách | Trạng Thái | Ưu Tiên | Thời Gian Ước Tính | Thời Gian Thực Tế | Ngày Bắt Đầu | Ngày Kết Thúc | Phụ Thuộc | Ghi Chú |
|---------|--------------|-------|-----------------|------------|---------|-------------------|-------------------|--------------|---------------|-----------|---------|
| P5-T1 | Unit Tests - Services | Kiểm thử tất cả service classes | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | P2-T10 | Mock dependencies |
| P5-T2 | Unit Tests - Repositories | Kiểm thử tất cả repository classes | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | P2-T6 | Database tests |
| P5-T3 | Unit Tests - Actions | Kiểm thử tất cả action classes | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 3 ngày | - | - | - | P2-T9 | Isolated tests |
| P5-T4 | Feature Tests - APIs | Kiểm thử tất cả API endpoints | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | P3-T6 | Full coverage |
| P5-T5 | Integration Tests | Kiểm thử database, queues, events | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 1 tuần | - | - | - | P4-T6 | End-to-end flows |
| P5-T6 | E2E Tests | Kiểm thử các luồng người dùng quan trọng | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 3 ngày | - | - | - | - | Browser tests |
| P5-T7 | Performance Tests | Load và stress testing | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 3 ngày | - | - | - | P4-T10 | Benchmarking |
| P5-T8 | PHPStan Level 8 | Đạt static analysis level 8 | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 1 tuần | - | - | - | P1-T9 | Sửa tất cả lỗi |

#### Giai Đoạn 6: Giám Sát & Quan Sát - Chi Tiết Nhiệm Vụ

| Task ID | Tên Nhiệm Vụ | Mô Tả | Người Phụ Trách | Trạng Thái | Ưu Tiên | Thời Gian Ước Tính | Thời Gian Thực Tế | Ngày Bắt Đầu | Ngày Kết Thúc | Phụ Thuộc | Ghi Chú |
|---------|--------------|-------|-----------------|------------|---------|-------------------|-------------------|--------------|---------------|-----------|---------|
| P6-T1 | Structured Logging | Triển khai logging định dạng JSON | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 3 ngày | - | - | - | - | Sẵn sàng cho log aggregation |
| P6-T2 | Log Aggregation | Thiết lập ELK/Loki stack | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 1 tuần | - | - | - | P6-T1 | Logs tập trung |
| P6-T3 | Error Tracking | Cấu hình Sentry cho error tracking | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 2 ngày | - | - | - | P1-T10 | Cảnh báo production |
| P6-T4 | Application Metrics | Thiết lập Prometheus cho metrics | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 3 ngày | - | - | - | - | Custom metrics |
| P6-T5 | Business Metrics | Theo dõi KPIs kinh doanh | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 2 ngày | - | - | - | P6-T4 | Doanh thu, đơn hàng, v.v. |
| P6-T6 | Grafana Dashboards | Tạo dashboards giám sát | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 3 ngày | - | - | - | P6-T4 | Giám sát trực quan |
| P6-T7 | Alerting Rules | Thiết lập cảnh báo cho lỗi/hiệu suất | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 2 ngày | - | - | - | P6-T3, P6-T4 | PagerDuty/Slack |

#### Giai Đoạn 7: Tài Liệu & Đào Tạo - Chi Tiết Nhiệm Vụ

| Task ID | Tên Nhiệm Vụ | Mô Tả | Người Phụ Trách | Trạng Thái | Ưu Tiên | Thời Gian Ước Tính | Thời Gian Thực Tế | Ngày Bắt Đầu | Ngày Kết Thúc | Phụ Thuộc | Ghi Chú |
|---------|--------------|-------|-----------------|------------|---------|-------------------|-------------------|--------------|---------------|-----------|---------|
| P7-T1 | Tài Liệu Kiến Trúc | Tài liệu hóa kiến trúc hệ thống | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 3 ngày | - | - | - | P2-T13 | Sơ đồ, patterns |
| P7-T2 | Tài Liệu API | Hoàn thiện tài liệu tham khảo API | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 1 tuần | - | - | - | P3-T5 | Swagger + ví dụ |
| P7-T3 | Database Schema | Tài liệu hóa cấu trúc database | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 2 ngày | - | - | - | - | ER diagrams |
| P7-T4 | Hướng Dẫn Triển Khai | Các bước triển khai production | TBD | 📋 Đã lên kế hoạch | 🔴 Cao | 2 ngày | - | - | - | P1-T8 | Từng bước |
| P7-T5 | Thiết Lập Development | Hướng dẫn development local | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 1 ngày | - | - | - | P1-T7 | Thiết lập Docker |
| P7-T6 | Tài Liệu Code | PHPDoc cho tất cả classes | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 3 ngày | - | - | - | - | Tự động tạo |
| P7-T7 | Đào Tạo Team | Đào tạo các patterns kiến trúc | TBD | 📋 Đã lên kế hoạch | 🟡 Trung bình | 3 ngày | - | - | - | P7-T1 | Chuyển giao kiến thức |

### Bảng Theo Dõi Tiến Độ

#### Tiến Độ Tổng Thể

| Giai Đoạn | Tổng Nhiệm Vụ | Hoàn Thành | Đang Thực Hiện | Đã Lên Kế Hoạch | Bị Chặn | Tiến Độ % | Trạng Thái |
|-----------|---------------|------------|----------------|------------------|---------|-----------|------------|
| Giai Đoạn 1: Nền Tảng | 10 | 0 | 0 | 10 | 0 | 0% | 📋 Chưa Bắt Đầu |
| Giai Đoạn 2: Kiến Trúc | 13 | 0 | 0 | 13 | 0 | 0% | 📋 Chưa Bắt Đầu |
| Giai Đoạn 3: Chuẩn Hóa API | 10 | 0 | 0 | 10 | 0 | 0% | 📋 Chưa Bắt Đầu |
| Giai Đoạn 4: Hiệu Suất | 10 | 0 | 0 | 10 | 0 | 0% | 📋 Chưa Bắt Đầu |
| Giai Đoạn 5: Kiểm Thử & QA | 8 | 0 | 0 | 8 | 0 | 0% | 📋 Chưa Bắt Đầu |
| Giai Đoạn 6: Giám Sát | 7 | 0 | 0 | 7 | 0 | 0% | 📋 Chưa Bắt Đầu |
| Giai Đoạn 7: Tài Liệu | 7 | 0 | 0 | 7 | 0 | 0% | 📋 Chưa Bắt Đầu |
| **Tổng Cộng** | **65** | **9** | **0** | **56** | **0** | **14%** | 🔄 **Phase 1 Hoàn Thành** |

#### Theo Dõi Tiến Độ Theo Tuần

| Tuần | Giai Đoạn | Nhiệm Vụ Hoàn Thành | Nhiệm Vụ Đã Lên Kế Hoạch | Blockers | Ghi Chú |
|------|-----------|---------------------|--------------------------|----------|---------|
| Tuần 1 | Giai Đoạn 1 | 0/10 | P1-T1, P1-T2 | - | Khởi động dự án |
| Tuần 2 | Giai Đoạn 1 | 0/10 | P1-T3, P1-T4, P1-T5 | - | - |
| Tuần 3 | Giai Đoạn 1 | 0/10 | P1-T6, P1-T7 | - | - |
| Tuần 4 | Giai Đoạn 1 | 0/10 | P1-T8, P1-T9, P1-T10 | - | Hoàn thành Giai Đoạn 1 |
| Tuần 5 | Giai Đoạn 2 | 0/13 | P2-T1, P2-T2 | - | Bắt đầu kiến trúc |
| Tuần 6 | Giai Đoạn 2 | 0/13 | P2-T3, P2-T4, P2-T5 | - | - |
| Tuần 7 | Giai Đoạn 2 | 0/13 | P2-T6 | - | Triển khai Repository |
| Tuần 8 | Giai Đoạn 2 | 0/13 | P2-T7, P2-T8 | - | - |
| Tuần 9 | Giai Đoạn 2 | 0/13 | P2-T9, P2-T10 | - | Tái cấu trúc CartService |
| Tuần 10 | Giai Đoạn 2 | 0/13 | P2-T10 | - | Tiếp tục CartService |
| Tuần 11 | Giai Đoạn 2 | 0/13 | P2-T11, P2-T12 | - | - |
| Tuần 12 | Giai Đoạn 2 | 0/13 | P2-T13 | - | Hoàn thành Giai Đoạn 2 |
| Tuần 13 | Giai Đoạn 3 | 0/10 | P3-T1, P3-T2 | - | Chuẩn hóa API |
| Tuần 14 | Giai Đoạn 3 | 0/10 | P3-T3, P3-T4 | - | - |
| Tuần 15 | Giai Đoạn 3 | 0/10 | P3-T5, P3-T6 | - | - |
| Tuần 16 | Giai Đoạn 3 | 0/10 | P3-T7, P3-T8, P3-T9, P3-T10 | - | Hoàn thành Giai Đoạn 3 |
| Tuần 17 | Giai Đoạn 4 | 0/10 | P4-T1, P4-T2 | - | Tối ưu hiệu suất |
| Tuần 18 | Giai Đoạn 4 | 0/10 | P4-T3, P4-T4 | - | - |
| Tuần 19 | Giai Đoạn 4 | 0/10 | P4-T5, P4-T6 | - | - |
| Tuần 20 | Giai Đoạn 4 | 0/10 | P4-T7, P4-T8, P4-T9, P4-T10 | - | Hoàn thành Giai Đoạn 4 |
| Tuần 21 | Giai Đoạn 5 | 0/8 | P5-T1, P5-T2 | - | Giai đoạn kiểm thử |
| Tuần 22 | Giai Đoạn 5 | 0/8 | P5-T3, P5-T4 | - | - |
| Tuần 23 | Giai Đoạn 5 | 0/8 | P5-T5, P5-T6 | - | - |
| Tuần 24 | Giai Đoạn 5 | 0/8 | P5-T7, P5-T8 | - | Hoàn thành Giai Đoạn 5 |
| Tuần 25 | Giai Đoạn 6 | 0/7 | P6-T1, P6-T2, P6-T3 | - | Thiết lập giám sát |
| Tuần 26 | Giai Đoạn 6 | 0/7 | P6-T4, P6-T5, P6-T6, P6-T7 | - | Hoàn thành Giai Đoạn 6 |
| Tuần 27 | Giai Đoạn 7 | 0/7 | P7-T1, P7-T2, P7-T3 | - | Tài liệu |
| Tuần 28 | Giai Đoạn 7 | 0/7 | P7-T4, P7-T5, P7-T6, P7-T7 | - | **Hoàn Thành Dự Án** |

#### Chú Giải Trạng Thái Nhiệm Vụ

| Trạng Thái | Icon | Mô Tả |
|------------|------|-------|
| Chưa Bắt Đầu | 📋 | Nhiệm vụ đã được lên kế hoạch nhưng chưa bắt đầu |
| Đang Thực Hiện | 🔄 | Nhiệm vụ đang được thực hiện |
| Hoàn Thành | ✅ | Nhiệm vụ đã hoàn thành và được xác minh |
| Bị Chặn | 🚫 | Nhiệm vụ bị chặn bởi dependency hoặc vấn đề |
| Tạm Dừng | ⏸️ | Nhiệm vụ tạm thời bị tạm dừng |
| Đang Review | 👀 | Nhiệm vụ đã hoàn thành và đang chờ review |

#### Chú Giải Ưu Tiên

| Ưu Tiên | Icon | Mô Tả |
|---------|------|-------|
| Quan Trọng | 🔴 | Phải hoàn thành, chặn các công việc khác |
| Cao | 🟡 | Quan trọng, nên hoàn thành sớm |
| Trung Bình | 🟢 | Tốt nếu có, có thể hoãn lại |

#### Velocity Tracking

| Metric | Week 1 | Week 2 | Week 3 | Week 4 | Average | Target |
|--------|--------|--------|--------|--------|---------|--------|
| Tasks Completed | 0 | 0 | 0 | 0 | 0 | 2.5/week |
| Story Points | 0 | 0 | 0 | 0 | 0 | 13/week |
| Bugs Fixed | 0 | 0 | 0 | 0 | 0 | - |
| Code Reviews | 0 | 0 | 0 | 0 | 0 | - |

#### Risk & Blocker Tracking

| ID | Risk/Blocker | Phase | Impact | Status | Owner | Resolution Date | Notes |
|----|--------------|-------|--------|--------|-------|-----------------|-------|
| R1 | Laravel 11 breaking changes | Phase 1 | 🔴 High | 📋 Open | TBD | - | Need compatibility review |
| R2 | CartService complexity | Phase 2 | 🔴 High | 📋 Open | TBD | - | 2300+ lines, needs careful refactoring |
| R3 | Database migration risks | Phase 4 | 🔴 High | 📋 Open | TBD | - | Need rollback plan |
| R4 | Team availability | All | 🟡 Medium | 📋 Open | TBD | - | Resource allocation needed |

#### Resource Allocation

| Team Member | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 | Phase 7 | Total Allocation |
|-------------|---------|---------|---------|---------|---------|---------|---------|------------------|
| Backend Lead | 100% | 100% | 50% | 30% | 20% | 20% | 30% | Full-time |
| Backend Dev 1 | 50% | 100% | 100% | 100% | 80% | 50% | 30% | Full-time |
| Backend Dev 2 | 50% | 100% | 100% | 100% | 80% | 50% | 30% | Full-time |
| DevOps Engineer | 100% | 20% | 30% | 50% | 20% | 100% | 20% | Part-time |
| QA Engineer | 0% | 0% | 20% | 20% | 100% | 30% | 20% | Part-time |
| Tech Writer | 0% | 0% | 0% | 0% | 0% | 0% | 100% | Part-time |

### Chi Tiết Modules Cần Refactor

#### Danh Sách Tất Cả Modules (40+ Modules)

| Module | Loại | Số Lượng Controllers | Số Lượng Models | Trạng Thái API | Ưu Tiên Refactor | Ghi Chú |
|--------|------|---------------------|-----------------|----------------|------------------|---------|
| **Product** | Core | 1 | 2 | ✅ Complete | 🔴 Critical | Variant management phức tạp |
| **Category** | Core | 1 | 1 | ✅ Complete | 🔴 Critical | Tree structure, cần refactor |
| **Brand** | Core | 1 | 1 | ✅ Complete | 🔴 High | Đã có API, cần optimize |
| **Origin** | Core | 1 | 1 | ✅ Complete | 🟡 Medium | Đơn giản, ít logic |
| **Order** | Core | 1 | 2 | ✅ Complete | 🔴 Critical | Business logic phức tạp |
| **Cart** | Core | 0 | 0 | ⚠️ Service Only | 🔴 Critical | CartService 2300+ dòng |
| **Warehouse** | Core | 2 | 2 | ✅ Complete | 🔴 Critical | Inventory logic quan trọng |
| **FlashSale** | Marketing | 1 | 2 | ✅ Complete | 🔴 High | Stock management |
| **Deal** | Marketing | 1 | 3 | ✅ Complete | 🔴 High | Pricing logic |
| **Marketing** | Marketing | 1 | 2 | ✅ Complete | 🟡 Medium | Campaign management |
| **Promotion** | Marketing | 1 | 1 | ✅ Complete | 🟡 Medium | Coupon logic |
| **Slider** | Content | 1 | 1 | ✅ Complete | 🟢 Low | Đơn giản |
| **Banner** | Content | 1 | 1 | ✅ Complete | 🟢 Low | Tương tự Slider |
| **Page** | Content | 1 | 1 | ✅ Complete | 🟢 Low | Static pages |
| **Post** | Content | 1 | 1 | ✅ Complete | 🟡 Medium | Blog/News |
| **Video** | Content | 1 | 1 | ✅ Complete | 🟢 Low | Media content |
| **Tag** | Content | 1 | 1 | ✅ Complete | 🟢 Low | Simple CRUD |
| **Menu** | Content | 1 | 2 | ✅ Complete | 🟡 Medium | Tree structure |
| **FooterBlock** | Content | 1 | 1 | ✅ Complete | 🟢 Low | Static content |
| **User** | Admin | 2 | 0 | ✅ Complete | 🔴 High | Authentication |
| **Member** | Admin | 1 | 2 | ✅ Complete | 🔴 High | Customer data |
| **Role** | Admin | 1 | 2 | ✅ Complete | 🔴 High | Permissions |
| **Permission** | Admin | 1 | 1 | ⚠️ Partial | 🔴 High | Cần API |
| **Pick** | Admin | 1 | 1 | ✅ Complete | 🟡 Medium | Warehouse location |
| **Showroom** | Admin | 1 | 1 | ✅ Complete | 🟢 Low | Store locations |
| **Setting** | Admin | 1 | 1 | ✅ Complete | 🟡 Medium | Config management |
| **Config** | Admin | 1 | 1 | ✅ Complete | 🟡 Medium | System config |
| **Contact** | Admin | 1 | 1 | ✅ Complete | 🟢 Low | Contact messages |
| **Feedback** | Admin | 1 | 1 | ✅ Complete | 🟢 Low | User feedback |
| **Subscriber** | Admin | 1 | 1 | ✅ Complete | 🟢 Low | Newsletter |
| **Rate** | Admin | 1 | 1 | ✅ Complete | 🟡 Medium | Product reviews |
| **Search** | Admin | 1 | 1 | ✅ Complete | 🟡 Medium | Search logs |
| **Redirection** | Admin | 1 | 1 | ✅ Complete | 🟢 Low | URL redirects |
| **Selling** | Admin | 1 | 1 | ✅ Complete | 🟢 Low | Sales tracking |
| **Download** | Admin | 1 | 1 | ✅ Complete | 🟢 Low | File downloads |
| **Compare** | Admin | 2 | 3 | ✅ Complete | 🟢 Low | Price comparison |
| **Dashboard** | Admin | 1 | 0 | ✅ Complete | 🔴 High | Statistics |
| **Ingredient** | Feature | 4 | 4 | ✅ Complete | 🟡 Medium | Dictionary |
| **Taxonomy** | Feature | 1 | 1 | ✅ Complete | 🟡 Medium | Classification |
| **GoogleMerchant** | Integration | 1 | 0 | ✅ Complete | 🟡 Medium | GMC sync |
| **R2** | Integration | 1 | 0 | ⚠️ Service Only | 🟡 Medium | CDN upload |
| **Delivery** | Feature | 3 | 1 | ❌ Missing | 🟡 Medium | Shipping |
| **Address** | Feature | 0 | 1 | ❌ Missing | 🟡 Medium | Address model |
| **Location** | Feature | 0 | 3 | ❌ Missing | 🟡 Medium | Province/District/Ward |
| **Recommendation** | Feature | 0 | 1 | ⚠️ Partial | 🟡 Medium | AI recommendations |
| **History** | Feature | 0 | 1 | ❌ Missing | 🟢 Low | User history |

#### Chi Tiết Services Cần Refactor

| Service | Số Dòng Code | Số Methods | Ưu Tiên | Kế Hoạch Refactor |
|---------|--------------|-----------|---------|-------------------|
| **CartService** | 2300+ | 50+ | 🔴 Critical | Chia thành: AddToCartService, UpdateCartService, CalculatePriceService, CartValidationService, CartStorageService |
| **ProductService** | ~800 | 20+ | 🔴 High | Chia thành: ProductQueryService, ProductPricingService, ProductStockService |
| **PriceEngineService** | ~600 | 15+ | 🔴 High | Tách logic: FlashSalePrice, DealPrice, RegularPrice, MixedPrice |
| **InventoryService** | ~500 | 12+ | 🔴 High | Tách: StockQueryService, StockMutationService, StockValidationService |
| **WarehouseService** | ~400 | 10+ | 🔴 High | Tách: WarehouseQueryService, ReceiptService, ExportService |
| **OrderStockReceiptService** | ~535 | 8+ | 🔴 High | Tách: ReceiptValidationService, ReceiptProcessingService |
| **FlashSaleStockService** | ~300 | 8+ | 🟡 Medium | Tách: FlashSaleStockQuery, FlashSaleStockUpdate |
| **ProductCacheService** | ~200 | 6+ | 🟡 Medium | Tối ưu cache strategy |
| **RecommendationService** | ~250 | 7+ | 🟡 Medium | Tách: RecommendationEngine, UserBehaviorService |
| **GmcSyncService** | ~300 | 5+ | 🟡 Medium | Tách: GmcProductMapper, GmcApiClient |
| **IngredientAdminService** | ~200 | 6+ | 🟢 Low | Đơn giản, ít cần refactor |
| **UserAnalyticsService** | ~150 | 4+ | 🟡 Medium | Tách: AnalyticsCollector, AnalyticsQuery |
| **ImageService** | ~100 | 3+ | 🟢 Low | Đơn giản |
| **PriceCalculationService** | ~150 | 4+ | 🔴 High | Merge vào PriceEngineService |
| **IngredientService** | ~200 | 5+ | 🟢 Low | Đơn giản |
| **GmcProductStatusService** | ~100 | 3+ | 🟢 Low | Đơn giản |
| **StockReceiptService** | ~200 | 4+ | 🟡 Medium | Merge với OrderStockReceiptService |

### Checklist Chi Tiết Cho Từng Giai Đoạn

#### Giai Đoạn 1: Nền Tảng - Checklist

##### 1.1 Nâng Cấp Laravel 10.x → 11.x

- [ ] **Trước khi nâng cấp:**
  - [ ] Backup database đầy đủ
  - [ ] Backup codebase (git tag)
  - [ ] Review Laravel 11 breaking changes
  - [ ] Kiểm tra tất cả dependencies compatibility
  - [ ] Tạo staging environment

- [ ] **Quá trình nâng cấp:**
  - [ ] Update `composer.json`: `"laravel/framework": "^11.0"`
  - [ ] Chạy `composer update`
  - [ ] Xử lý breaking changes:
    - [ ] Exception handling changes
    - [ ] Route model binding changes
    - [ ] Middleware changes
    - [ ] Service provider changes
    - [ ] Config file changes
  - [ ] Update `bootstrap/app.php` (Laravel 11 structure)
  - [ ] Update route files
  - [ ] Update middleware registration

- [ ] **Sau khi nâng cấp:**
  - [ ] Chạy `php artisan migrate:status` - kiểm tra migrations
  - [ ] Chạy `php artisan route:list` - kiểm tra routes
  - [ ] Chạy `php artisan config:cache` - cache config
  - [ ] Test tất cả API endpoints
  - [ ] Test admin panel
  - [ ] Test public website
  - [ ] Performance benchmark
  - [ ] Document breaking changes

##### 1.2 Nâng Cấp PHP 8.1 → 8.3+

- [ ] **Kiểm tra compatibility:**
  - [ ] Tất cả extensions cần thiết
  - [ ] Server configuration
  - [ ] Composer packages compatibility

- [ ] **Nâng cấp:**
  - [ ] Update PHP version trên server
  - [ ] Update `composer.json` PHP requirement
  - [ ] Test với PHP 8.3 features:
    - [ ] Typed class constants
    - [ ] Readonly properties
    - [ ] Override attribute
    - [ ] Anonymous class readonly properties

- [ ] **Verify:**
  - [ ] `php -v` shows 8.3+
  - [ ] `composer install` works
  - [ ] All tests pass

##### 1.3 Thiết Lập Redis

- [ ] **Cài đặt Redis:**
  - [ ] Install Redis server
  - [ ] Configure Redis connection
  - [ ] Test connection: `redis-cli ping`

- [ ] **Cấu hình Laravel:**
  - [ ] Update `.env`: `CACHE_DRIVER=redis`, `SESSION_DRIVER=redis`
  - [ ] Update `config/cache.php`
  - [ ] Update `config/session.php`
  - [ ] Test cache: `Cache::put('test', 'value')`
  - [ ] Test session

- [ ] **Production setup:**
  - [ ] Redis password authentication
  - [ ] Redis persistence (RDB/AOF)
  - [ ] Redis memory limits
  - [ ] Redis monitoring

##### 1.4 Thiết Lập Docker

- [ ] **Dockerfile:**
  - [ ] Base image: `php:8.3-fpm`
  - [ ] Install extensions
  - [ ] Copy application code
  - [ ] Set permissions

- [ ] **docker-compose.yml:**
  - [ ] PHP service
  - [ ] Nginx service
  - [ ] MySQL/PostgreSQL service
  - [ ] Redis service
  - [ ] Volume mounts
  - [ ] Environment variables
  - [ ] Network configuration

- [ ] **Verify:**
  - [ ] `docker-compose up -d` works
  - [ ] All services running
  - [ ] Application accessible
  - [ ] Database connection works

#### Giai Đoạn 2: Tái Cấu Trúc Kiến Trúc - Checklist

##### 2.1 Repository Pattern - Chi Tiết

- [ ] **Base Repository:**
  - [ ] Tạo `App\Repositories\Contracts\RepositoryInterface`
  - [ ] Tạo `App\Repositories\BaseRepository`
  - [ ] Implement common methods: `all()`, `find()`, `create()`, `update()`, `delete()`, `paginate()`
  - [ ] Add query builder methods
  - [ ] Add relationship loading methods

- [ ] **Product Repository:**
  - [ ] Tạo `App\Repositories\Product\ProductRepositoryInterface`
  - [ ] Tạo `App\Repositories\Product\ProductRepository`
  - [ ] Methods cần có:
    - [ ] `findBySlug(string $slug): ?Product`
    - [ ] `getActiveProducts(): Collection`
    - [ ] `getProductsByCategory(int $categoryId): Collection`
    - [ ] `getProductsByBrand(int $brandId): Collection`
    - [ ] `searchProducts(string $keyword): Collection`
    - [ ] `getProductsWithVariants(): Collection`
    - [ ] `getFlashSaleProducts(): Collection`
    - [ ] `getDealProducts(): Collection`
  - [ ] Move logic từ `ProductController` và `ProductService`
  - [ ] Update `ProductController` để sử dụng repository
  - [ ] Write tests

- [ ] **Category Repository:**
  - [ ] Tạo `CategoryRepository` với tree methods:
    - [ ] `getTree(): Collection`
    - [ ] `getChildren(int $parentId): Collection`
    - [ ] `getAncestors(int $categoryId): Collection`
    - [ ] `getDescendants(int $categoryId): Collection`
  - [ ] Test tree operations

- [ ] **Order Repository:**
  - [ ] Tạo `OrderRepository` với complex queries:
    - [ ] `getOrdersByStatus(string $status): Collection`
    - [ ] `getOrdersByDateRange(Carbon $from, Carbon $to): Collection`
    - [ ] `getOrdersByMember(int $memberId): Collection`
    - [ ] `getOrderWithItems(int $orderId): ?Order`
  - [ ] Test order queries

- [ ] **Các Repository Còn Lại (20+):**
  - [ ] BrandRepository
  - [ ] OriginRepository
  - [ ] FlashSaleRepository
  - [ ] DealRepository
  - [ ] WarehouseRepository
  - [ ] MemberRepository
  - [ ] UserRepository
  - [ ] ... (liệt kê tất cả)

##### 2.2 Tái Cấu Trúc CartService - Chi Tiết

**Phân tích CartService hiện tại:**
- [ ] Đọc toàn bộ `CartService.php` (2300+ dòng)
- [ ] Liệt kê tất cả methods
- [ ] Xác định responsibilities:
  - [ ] Add to cart logic
  - [ ] Update cart logic
  - [ ] Remove from cart logic
  - [ ] Price calculation logic
  - [ ] Stock validation logic
  - [ ] Flash sale price logic
  - [ ] Deal price logic
  - [ ] Mixed pricing logic
  - [ ] Cart storage logic
  - [ ] Cart validation logic

**Chia nhỏ thành các services:**

- [ ] **AddToCartService:**
  - [ ] Method: `execute(AddToCartDTO $dto): CartItem`
  - [ ] Validate product exists
  - [ ] Validate stock available
  - [ ] Check variant selection
  - [ ] Create/update cart item
  - [ ] Return cart item

- [ ] **UpdateCartService:**
  - [ ] Method: `execute(UpdateCartDTO $dto): CartItem`
  - [ ] Validate cart item exists
  - [ ] Validate new quantity
  - [ ] Update quantity
  - [ ] Return updated cart item

- [ ] **CalculatePriceService:**
  - [ ] Method: `calculate(Cart $cart): PriceDTO`
  - [ ] Get base prices
  - [ ] Apply flash sale prices
  - [ ] Apply deal prices
  - [ ] Apply mixed pricing rules
  - [ ] Calculate totals
  - [ ] Return price breakdown

- [ ] **CartValidationService:**
  - [ ] Method: `validate(Cart $cart): ValidationResult`
  - [ ] Check stock availability
  - [ ] Check product status
  - [ ] Check variant availability
  - [ ] Check flash sale validity
  - [ ] Return validation errors

- [ ] **CartStorageService:**
  - [ ] Method: `save(Cart $cart): void`
  - [ ] Method: `load(int $memberId): ?Cart`
  - [ ] Handle session storage
  - [ ] Handle database storage
  - [ ] Handle cache storage

**Migration steps:**
- [ ] Tạo các service mới
- [ ] Move methods từ CartService
- [ ] Update CartService để orchestrate
- [ ] Update CartController
- [ ] Update tests
- [ ] Verify không breaking changes

##### 2.3 DTOs - Chi Tiết

**DTOs cần tạo:**

- [ ] **Product DTOs:**
  - [ ] `CreateProductDTO`
  - [ ] `UpdateProductDTO`
  - [ ] `ProductQueryDTO` (filters, pagination)
  - [ ] `ProductResponseDTO`

- [ ] **Order DTOs:**
  - [ ] `CreateOrderDTO`
  - [ ] `UpdateOrderDTO`
  - [ ] `OrderItemDTO`
  - [ ] `OrderResponseDTO`

- [ ] **Cart DTOs:**
  - [ ] `AddToCartDTO`
  - [ ] `UpdateCartDTO`
  - [ ] `PriceDTO`
  - [ ] `CartResponseDTO`

- [ ] **Warehouse DTOs:**
  - [ ] `StockMutationDTO`
  - [ ] `ReceiptDTO`
  - [ ] `WarehouseQueryDTO`

**Implementation:**
- [ ] Install `spatie/data-transfer-object`
- [ ] Tạo base DTO class
- [ ] Implement validation trong DTOs
- [ ] Add `fromRequest()` methods
- [ ] Add `toArray()` methods
- [ ] Write tests

#### Giai Đoạn 3: Chuẩn Hóa API - Checklist

##### 3.1 API Versioning - Chi Tiết

- [ ] **Route Structure:**
  ```
  /api/v1/          # Legacy (deprecated, 6 months notice)
  /api/v2/          # Current (2026 standards)
  /admin/api/v1/    # Legacy admin
  /admin/api/v2/    # Current admin
  ```

- [ ] **Version Headers:**
  - [ ] `Accept: application/vnd.lica.v1+json`
  - [ ] `Accept: application/vnd.lica.v2+json`
  - [ ] Default to v2 if not specified

- [ ] **Deprecation Strategy:**
  - [ ] Add `X-API-Deprecated: true` header cho v1
  - [ ] Add `X-API-Sunset: 2025-07-21` header
  - [ ] Log v1 usage để track migration
  - [ ] Email notifications cho clients sử dụng v1

##### 3.2 Authentication - Chi Tiết

- [ ] **Laravel Sanctum Setup:**
  - [ ] Install: `composer require laravel/sanctum`
  - [ ] Publish config: `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
  - [ ] Run migration: `php artisan migrate`
  - [ ] Add `HasApiTokens` trait to User model
  - [ ] Configure token expiration
  - [ ] Create token middleware

- [ ] **Token Management:**
  - [ ] Endpoint: `POST /api/v2/auth/login` - Issue token
  - [ ] Endpoint: `POST /api/v2/auth/logout` - Revoke token
  - [ ] Endpoint: `POST /api/v2/auth/refresh` - Refresh token
  - [ ] Endpoint: `GET /api/v2/auth/user` - Get current user
  - [ ] Token scopes (optional)

- [ ] **Migration từ Session Auth:**
  - [ ] Update all API controllers
  - [ ] Remove CSRF middleware từ API routes
  - [ ] Update frontend to use tokens
  - [ ] Test authentication flow

#### Giai Đoạn 4: Tối Ưu Hiệu Suất - Checklist

##### 4.1 Fix N+1 Queries - Chi Tiết

**Quy trình:**
- [ ] **Identify N+1 queries:**
  - [ ] Enable query logging: `DB::enableQueryLog()`
  - [ ] Run typical API requests
  - [ ] Analyze query log
  - [ ] Identify N+1 patterns

- [ ] **Fix common patterns:**
  - [ ] Products với variants: `Product::with('variants')->get()`
  - [ ] Products với brand: `Product::with('brand')->get()`
  - [ ] Orders với items: `Order::with('items.product')->get()`
  - [ ] Categories với children: `Category::with('children')->get()`

- [ ] **Verify fixes:**
  - [ ] Query count giảm
  - [ ] Response time cải thiện
  - [ ] No regression

##### 4.2 Database Indexes - Chi Tiết

**Indexes cần thêm:**

- [ ] **Products table:**
  - [ ] `idx_products_status` on `status`
  - [ ] `idx_products_category_id` on `category_id`
  - [ ] `idx_products_brand_id` on `brand_id`
  - [ ] `idx_products_slug` on `slug` (unique)
  - [ ] `idx_products_created_at` on `created_at`

- [ ] **Orders table:**
  - [ ] `idx_orders_member_id` on `member_id`
  - [ ] `idx_orders_status` on `status`
  - [ ] `idx_orders_created_at` on `created_at`
  - [ ] Composite: `idx_orders_member_status` on `(member_id, status)`

- [ ] **Cart items:**
  - [ ] `idx_cart_items_member_id` on `member_id`
  - [ ] `idx_cart_items_product_id` on `product_id`

**Process:**
- [ ] Analyze slow queries
- [ ] Create migration cho indexes
- [ ] Test trên staging
- [ ] Monitor performance
- [ ] Deploy to production

### Database Migration Strategy Chi Tiết

#### Nguyên Tắc Migration

1. **Backward Compatible:**
   - [ ] Không drop columns ngay lập tức
   - [ ] Add columns với default values
   - [ ] Deprecation period 6 tháng
   - [ ] Dual-write pattern (write to both old and new)

2. **Zero Downtime:**
   - [ ] Migrations chạy trong transactions
   - [ ] Long-running migrations chia nhỏ
   - [ ] Use `->after()` thay vì `->change()` khi có thể
   - [ ] Test migrations trên staging trước

3. **Data Preservation:**
   - [ ] Backup trước mỗi migration
   - [ ] Verify data integrity sau migration
   - [ ] Rollback plan sẵn sàng

#### Migration Checklist

**Trước Migration:**
- [ ] Backup database đầy đủ
- [ ] Test migration trên copy của production data
- [ ] Estimate migration time
- [ ] Schedule maintenance window nếu cần
- [ ] Notify stakeholders

**Trong Migration:**
- [ ] Run migration: `php artisan migrate`
- [ ] Monitor migration progress
- [ ] Check for errors
- [ ] Verify data integrity

**Sau Migration:**
- [ ] Verify application works
- [ ] Run smoke tests
- [ ] Monitor performance
- [ ] Check error logs
- [ ] Document changes

### Rollback Procedures

#### Khi Nào Cần Rollback

- [ ] Critical bugs không thể fix nhanh
- [ ] Performance degradation > 50%
- [ ] Data loss hoặc corruption
- [ ] Security vulnerabilities
- [ ] Breaking changes không được phát hiện

#### Rollback Checklist

**Laravel/PHP Rollback:**
- [ ] Git checkout previous version
- [ ] `composer install` với version cũ
- [ ] Rollback database migrations: `php artisan migrate:rollback`
- [ ] Clear caches: `php artisan cache:clear`, `php artisan config:clear`
- [ ] Restart services

**Database Rollback:**
- [ ] Restore từ backup
- [ ] Verify data integrity
- [ ] Test application

**API Rollback:**
- [ ] Switch API version trong config
- [ ] Update route files
- [ ] Clear route cache

### Testing Checklist

#### Unit Tests

- [ ] **Services:**
  - [ ] Test tất cả public methods
  - [ ] Test error cases
  - [ ] Test edge cases
  - [ ] Mock dependencies

- [ ] **Repositories:**
  - [ ] Test CRUD operations
  - [ ] Test query methods
  - [ ] Test relationships
  - [ ] Use database transactions

- [ ] **Actions:**
  - [ ] Test execute method
  - [ ] Test validation
  - [ ] Test error handling

#### Feature Tests

- [ ] **API Endpoints:**
  - [ ] Test tất cả endpoints
  - [ ] Test authentication
  - [ ] Test authorization
  - [ ] Test validation
  - [ ] Test error responses
  - [ ] Test pagination
  - [ ] Test filters

#### Integration Tests

- [ ] **Database:**
  - [ ] Test transactions
  - [ ] Test relationships
  - [ ] Test constraints

- [ ] **Queue:**
  - [ ] Test job dispatch
  - [ ] Test job processing
  - [ ] Test failed jobs

- [ ] **Events:**
  - [ ] Test event firing
  - [ ] Test listeners
  - [ ] Test async processing

#### E2E Tests

- [ ] **Critical Flows:**
  - [ ] User registration → Login → Browse → Add to cart → Checkout → Order
  - [ ] Admin login → Create product → Update product → Delete product
  - [ ] Flash sale creation → Product assignment → Price calculation

### Security Checklist

#### Authentication & Authorization

- [ ] [ ] Token-based authentication implemented
- [ ] [ ] Token expiration configured
- [ ] [ ] Token refresh mechanism
- [ ] [ ] Role-based access control
- [ ] [ ] Permission checks on all endpoints
- [ ] [ ] Admin routes protected

#### Input Validation

- [ ] [ ] All inputs validated
- [ ] [ ] SQL injection prevention (use Eloquent/Query Builder)
- [ ] [ ] XSS prevention (sanitize output)
- [ ] [ ] CSRF protection (web routes only)
- [ ] [ ] File upload validation
- [ ] [ ] Rate limiting implemented

#### Data Protection

- [ ] [ ] Sensitive data encrypted
- [ ] [ ] Passwords hashed (bcrypt)
- [ ] [ ] API keys stored securely
- [ ] [ ] Database credentials in .env
- [ ] [ ] No credentials in code

#### API Security

- [ ] [ ] HTTPS enforced
- [ ] [ ] CORS configured properly
- [ ] [ ] Rate limiting per user/IP
- [ ] [ ] API versioning
- [ ] [ ] Error messages không expose sensitive info

### Integration Points Checklist

#### External Services

- [ ] **Cloudflare R2 (CDN):**
  - [ ] Image upload integration
  - [ ] URL generation
  - [ ] Error handling
  - [ ] Fallback mechanism

- [ ] **Payment Gateway:**
  - [ ] Integration tested
  - [ ] Webhook handling
  - [ ] Error handling
  - [ ] Transaction logging

- [ ] **Email Service:**
  - [ ] SMTP configuration
  - [ ] Email templates
  - [ ] Queue integration
  - [ ] Error handling

- [ ] **SMS Service (nếu có):**
  - [ ] API integration
  - [ ] Error handling
  - [ ] Rate limiting

#### Internal Integrations

- [ ] **Cart ↔ Product:**
  - [ ] Price synchronization
  - [ ] Stock validation
  - [ ] Variant selection

- [ ] **Order ↔ Warehouse:**
  - [ ] Stock deduction
  - [ ] Receipt generation
  - [ ] Inventory updates

- [ ] **Flash Sale ↔ Product:**
  - [ ] Price override
  - [ ] Stock reservation
  - [ ] Time-based activation

### Business Logic Preservation Checklist

#### Pricing Logic

- [ ] [ ] Flash sale pricing preserved
- [ ] [ ] Deal pricing preserved
- [ ] [ ] Mixed pricing rules preserved
- [ ] [ ] Warehouse pricing preserved
- [ ] [ ] Variant pricing preserved
- [ ] [ ] Price calculation accuracy verified

#### Inventory Logic

- [ ] [ ] Stock calculation preserved
- [ ] [ ] S_phy (physical stock) logic preserved
- [ ] [ ] S_flash (flash sale stock) logic preserved
- [ ] [ ] Stock mutations preserved
- [ ] [ ] Warehouse stock sync preserved

#### Order Logic

- [ ] [ ] Order creation flow preserved
- [ ] [ ] Order status transitions preserved
- [ ] [ ] Payment processing preserved
- [ ] [ ] Shipping calculation preserved
- [ ] [ ] Order cancellation logic preserved

### Next Steps

1. ✅ **Review & Approval** - Stakeholder review of upgrade plan
2. ⏳ **Resource Allocation** - Assign team members to phases
3. ⏳ **Kickoff Meeting** - Project kickoff and alignment
4. ⏳ **Sprint Planning** - Setup first sprint (Phase 1)
5. ⏳ **Begin Phase 1** - Start foundation work

### Related Documents

- **Full Upgrade Plan:** `BACKEND_V2_UPGRADE_PLAN.md` - Detailed 28-week plan
- **API Migration Analysis:** `API_MIGRATION_ANALYSIS.md` - API migration details
- **Database Setup:** `DATABASE_MODELS_SETUP.md` - Database structure

---

**Last Updated:** 2025-01-21  
**Version:** 1.3

**Changelog:**
- 2025-01-21: Added all remaining Admin API modules: Marketing Campaign, Promotion, User, Member, Pick, Role & Permission, Setting, Contact, Feedback, Subscriber, Tag, Post, Video, Rate, Dashboard, Showroom, Menu, Footer Block, Redirection, Selling, Search, Download, Config, and Compare Management APIs
- 2025-01-21: Added Categories, Origins, Banners, and Pages Management APIs
- 2025-01-20: Added Brands Management API and Variant Management documentation

