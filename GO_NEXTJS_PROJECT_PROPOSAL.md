# Go Backend + Next.js Frontend Project Proposal

**Project Name:** LICA E-Commerce Platform V2  
**Backend:** Go (Gin Framework)  
**Frontend:** Next.js 14+ (App Router)  
**Database:** PostgreSQL  
**Cache:** Redis  
**Purpose:** Modern, scalable e-commerce platform refactored from Laravel

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Technology Stack](#2-technology-stack)
3. [Project Structure](#3-project-structure)
4. [API Design](#4-api-design)
5. [Database Design](#5-database-design)
6. [Migration Strategy](#6-migration-strategy)
7. [Development Workflow](#7-development-workflow)
8. [Deployment Strategy](#8-deployment-strategy)
9. [Implementation Phases](#9-implementation-phases)

---

## 1. Architecture Overview

### 1.1. System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Next.js Frontend (SSR/SSG)                │
│  - App Router (Next.js 14+)                                  │
│  - React Server Components                                   │
│  - Client Components (Interactive UI)                        │
│  - API Routes (if needed for server-side operations)        │
└──────────────────────┬──────────────────────────────────────┘
                       │ HTTP/REST API
                       │ JWT Authentication
                       │
┌──────────────────────▼──────────────────────────────────────┐
│                    Go Backend (Gin Framework)                 │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  API Layer (Handlers)                                 │   │
│  │  - Product Handlers                                   │   │
│  │  - Cart Handlers                                      │   │
│  │  - Order Handlers                                     │   │
│  │  - Warehouse Handlers                                 │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Service Layer (Business Logic)                       │   │
│  │  - PriceEngineService                                 │   │
│  │  - CartService                                        │   │
│  │  - OrderService                                       │   │
│  │  - WarehouseService                                   │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Repository Layer (Data Access)                       │   │
│  │  - ProductRepository                                  │   │
│  │  - OrderRepository                                    │   │
│  │  - WarehouseRepository                                │   │
│  └──────────────────────────────────────────────────────┘   │
└──────────────────────┬──────────────────────────────────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
┌───────▼──────┐ ┌─────▼─────┐ ┌─────▼─────┐
│  PostgreSQL  │ │   Redis   │ │   R2/CDN  │
│  (Primary DB)│ │  (Cache)  │ │  (Storage)│
└──────────────┘ └───────────┘ └───────────┘
```

### 1.2. Key Principles

1. **Backend-First Architecture:**
   - All business logic in Go backend
   - Frontend only displays data
   - No business calculations in frontend

2. **API-First Design:**
   - RESTful API with OpenAPI/Swagger documentation
   - Consistent response format
   - Versioned APIs (v1, v2)

3. **Microservices Ready:**
   - Modular service layer
   - Can be split into microservices later
   - Service interfaces for dependency injection

4. **Performance Focused:**
   - Redis caching strategy
   - Database query optimization
   - CDN for static assets

---

## 2. Technology Stack

### 2.1. Backend (Go)

**Core Framework:**
- **Gin** - HTTP web framework (lightweight, fast)
- **GORM** - ORM for database operations
- **Viper** - Configuration management
- **Zap** - Structured logging

**Libraries:**
- **jwt-go** - JWT authentication
- **bcrypt** - Password hashing
- **validator** - Input validation
- **golang-migrate** - Database migrations
- **go-redis** - Redis client
- **gocron** - Cron jobs
- **testify** - Testing framework

**External Services:**
- **GHTK API** - Shipping integration
- **Google Merchant Center API** - Product sync
- **R2 Storage** - Cloud storage (S3-compatible)

### 2.2. Frontend (Next.js)

**Core:**
- **Next.js 14+** - React framework with App Router
- **React 18+** - UI library
- **TypeScript** - Type safety

**UI Libraries:**
- **Tailwind CSS** - Utility-first CSS
- **shadcn/ui** - Component library
- **React Hook Form** - Form handling
- **Zod** - Schema validation

**State Management:**
- **Zustand** - Lightweight state management
- **React Query (TanStack Query)** - Server state management

**HTTP Client:**
- **Axios** - HTTP client with interceptors

**Other:**
- **Next-Auth** - Authentication (if needed)
- **React Query DevTools** - Development tools

### 2.3. Database & Cache

- **PostgreSQL 15+** - Primary database
- **Redis 7+** - Caching and session storage

### 2.4. DevOps & Tools

- **Docker** - Containerization
- **Docker Compose** - Local development
- **GitHub Actions** - CI/CD
- **Swagger/OpenAPI** - API documentation
- **Prometheus** - Metrics
- **Grafana** - Monitoring

---

## 3. Project Structure

### 3.1. Go Backend Structure

```
lica-backend/
├── cmd/
│   └── api/
│       └── main.go                 # Application entry point
├── internal/
│   ├── api/
│   │   ├── handlers/               # HTTP handlers
│   │   │   ├── product.go
│   │   │   ├── cart.go
│   │   │   ├── order.go
│   │   │   ├── warehouse.go
│   │   │   └── ...
│   │   ├── middleware/             # HTTP middleware
│   │   │   ├── auth.go
│   │   │   ├── cors.go
│   │   │   ├── logger.go
│   │   │   └── validator.go
│   │   └── routes/                 # Route definitions
│   │       ├── v1/
│   │       │   ├── product.go
│   │       │   ├── cart.go
│   │       │   └── ...
│   │       └── v2/
│   │           └── ...
│   ├── service/                    # Business logic layer
│   │   ├── product/
│   │   │   ├── service.go
│   │   │   └── interface.go
│   │   ├── cart/
│   │   │   ├── service.go
│   │   │   └── interface.go
│   │   ├── order/
│   │   │   ├── service.go
│   │   │   └── interface.go
│   │   ├── warehouse/
│   │   │   ├── service.go
│   │   │   └── interface.go
│   │   ├── pricing/
│   │   │   ├── engine.go           # PriceEngineService
│   │   │   └── interface.go
│   │   └── ...
│   ├── repository/                 # Data access layer
│   │   ├── product/
│   │   │   ├── repository.go
│   │   │   └── interface.go
│   │   ├── order/
│   │   │   ├── repository.go
│   │   │   └── interface.go
│   │   └── ...
│   ├── model/                      # Domain models
│   │   ├── product.go
│   │   ├── order.go
│   │   ├── cart.go
│   │   └── ...
│   ├── dto/                        # Data Transfer Objects
│   │   ├── request/
│   │   │   ├── product.go
│   │   │   ├── cart.go
│   │   │   └── ...
│   │   └── response/
│   │       ├── product.go
│   │       ├── cart.go
│   │       └── ...
│   ├── config/                     # Configuration
│   │   └── config.go
│   ├── database/                    # Database setup
│   │   ├── postgres.go
│   │   └── redis.go
│   ├── cache/                      # Cache layer
│   │   └── cache.go
│   ├── external/                   # External services
│   │   ├── ghtk/
│   │   │   └── client.go
│   │   ├── gmc/
│   │   │   └── client.go
│   │   └── r2/
│   │       └── client.go
│   └── utils/                      # Utilities
│       ├── formatter.go            # Price formatting, etc.
│       ├── validator.go
│       └── ...
├── migrations/                     # Database migrations
│   ├── 001_create_products.up.sql
│   ├── 001_create_products.down.sql
│   └── ...
├── pkg/                           # Public packages
│   └── errors/
│       └── errors.go
├── scripts/                       # Utility scripts
│   └── migrate.sh
├── docker/
│   ├── Dockerfile
│   └── docker-compose.yml
├── docs/
│   └── api/
│       └── openapi.yaml
├── .env.example
├── .gitignore
├── go.mod
├── go.sum
└── README.md
```

### 3.2. Next.js Frontend Structure

```
lica-frontend/
├── app/                           # Next.js App Router
│   ├── (public)/                  # Public routes group
│   │   ├── layout.tsx
│   │   ├── page.tsx               # Home page
│   │   ├── products/
│   │   │   ├── page.tsx           # Product list
│   │   │   ├── [slug]/
│   │   │   │   └── page.tsx       # Product detail
│   │   │   └── loading.tsx
│   │   ├── cart/
│   │   │   ├── page.tsx           # Cart page
│   │   │   └── loading.tsx
│   │   ├── checkout/
│   │   │   ├── page.tsx           # Checkout page
│   │   │   └── loading.tsx
│   │   └── ...
│   ├── (admin)/                   # Admin routes group
│   │   ├── layout.tsx
│   │   ├── admin/
│   │   │   ├── products/
│   │   │   │   ├── page.tsx
│   │   │   │   ├── [id]/
│   │   │   │   │   └── page.tsx
│   │   │   │   └── new/
│   │   │   │       └── page.tsx
│   │   │   ├── orders/
│   │   │   │   └── ...
│   │   │   └── ...
│   │   └── ...
│   ├── api/                       # API routes (if needed)
│   │   └── ...
│   ├── layout.tsx                 # Root layout
│   └── globals.css
├── components/                    # React components
│   ├── ui/                        # shadcn/ui components
│   │   ├── button.tsx
│   │   ├── input.tsx
│   │   └── ...
│   ├── product/
│   │   ├── ProductCard.tsx
│   │   ├── ProductList.tsx
│   │   └── ProductDetail.tsx
│   ├── cart/
│   │   ├── CartItem.tsx
│   │   ├── CartSummary.tsx
│   │   └── CartList.tsx
│   ├── checkout/
│   │   ├── CheckoutForm.tsx
│   │   ├── ShippingForm.tsx
│   │   └── PaymentForm.tsx
│   └── ...
├── lib/                           # Utilities and configs
│   ├── api/                       # API client
│   │   ├── client.ts              # Axios instance
│   │   ├── product.ts             # Product API
│   │   ├── cart.ts                # Cart API
│   │   ├── order.ts               # Order API
│   │   └── ...
│   ├── services/                  # Business logic (minimal)
│   │   └── formatter.ts           # Display formatting only
│   ├── hooks/                     # Custom React hooks
│   │   ├── useCart.ts
│   │   ├── useProduct.ts
│   │   └── ...
│   ├── store/                     # Zustand stores
│   │   ├── cartStore.ts
│   │   └── authStore.ts
│   └── utils/
│       └── ...
├── types/                         # TypeScript types
│   ├── product.ts
│   ├── cart.ts
│   ├── order.ts
│   └── api.ts
├── public/                        # Static assets
│   ├── images/
│   └── ...
├── styles/                        # Global styles
│   └── ...
├── .env.local
├── .env.example
├── next.config.js
├── tailwind.config.js
├── tsconfig.json
├── package.json
└── README.md
```

---

## 4. API Design

### 4.1. API Versioning

```
/api/v1/*          # Public API V1
/api/v2/*          # Public API V2 (future)
/admin/api/v1/*    # Admin API V1
/admin/api/v2/*    # Admin API V2
```

### 4.2. Response Format (Standardized)

**Success Response:**
```json
{
  "success": true,
  "data": {
    // Response data
  },
  "message": "Operation successful",
  "meta": {
    "timestamp": "2026-01-25T10:00:00Z",
    "request_id": "uuid"
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "field": ["Error message"]
    }
  },
  "meta": {
    "timestamp": "2026-01-25T10:00:00Z",
    "request_id": "uuid"
  }
}
```

### 4.3. Key API Endpoints

**Public APIs:**
- `GET /api/v1/products` - List products
- `GET /api/v1/products/{id}` - Product detail
- `GET /api/v1/products/{id}/price` - Get price info
- `GET /api/v1/cart` - Get cart
- `POST /api/v1/cart/items` - Add to cart
- `PUT /api/v1/cart/items/{variant_id}` - Update cart item
- `DELETE /api/v1/cart/items/{variant_id}` - Remove from cart
- `POST /api/v1/checkout` - Create order
- `GET /api/v1/orders` - User orders
- `GET /api/v1/orders/{code}` - Order detail

**Admin APIs:**
- `GET /admin/api/v1/products` - List products (admin)
- `POST /admin/api/v1/products` - Create product
- `PUT /admin/api/v1/products/{id}` - Update product
- `DELETE /admin/api/v1/products/{id}` - Delete product
- `GET /admin/api/v1/orders` - List orders
- `PATCH /admin/api/v1/orders/{id}/status` - Update order status
- `GET /admin/api/v1/warehouse/inventory` - Inventory list
- `POST /admin/api/v1/warehouse/import-receipts` - Create import receipt

### 4.4. Complete Response Example (Backend-First)

**Cart API Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "variant_id": 123,
        "product_id": 10,
        "product_name": "Sản phẩm A",
        "variant_name": "Màu đỏ - Size M",
        "quantity": 2,
        "unit_price": 100000,
        "unit_price_formatted": "100.000₫",
        "subtotal": 200000,
        "subtotal_formatted": "200.000₫",
        "price_info": {
          "final_price": 100000,
          "final_price_formatted": "100.000₫",
          "original_price": 150000,
          "original_price_formatted": "150.000₫",
          "type": "flashsale",
          "label": "Flash Sale",
          "discount_percent": 33,
          "discount_amount": 50000,
          "discount_amount_formatted": "50.000₫"
        },
        "stock_info": {
          "available_stock": 50,
          "is_available": true,
          "stock_status": "in_stock",
          "stock_message": "Còn hàng"
        },
        "image": "https://cdn/...",
        "weight": 0.5,
        "weight_formatted": "500g"
      }
    ],
    "summary": {
      "total_qty": 2,
      "total_qty_formatted": "2 sản phẩm",
      "subtotal": 200000,
      "subtotal_formatted": "200.000₫",
      "discount": 0,
      "discount_formatted": "0₫",
      "shipping_fee": 30000,
      "shipping_fee_formatted": "30.000₫",
      "total": 230000,
      "total_formatted": "230.000₫"
    },
    "validation": {
      "all_items_available": true,
      "all_prices_valid": true,
      "can_checkout": true,
      "warnings": [],
      "errors": []
    }
  }
}
```

---

## 5. Database Design

### 5.1. Database Schema (PostgreSQL)

**Core Tables:**
- `products` - Products (from `posts` table)
- `variants` - Product variants
- `categories` - Product categories (from `posts` where type='taxonomy')
- `brands` - Brands
- `origins` - Origins
- `orders` - Orders
- `order_items` - Order items
- `cart_items` - Cart items (for authenticated users)
- `warehouse_receipts` - Import/Export receipts
- `warehouse_receipt_items` - Receipt items
- `inventory_stocks` - Stock per variant/warehouse
- `flash_sales` - Flash Sale campaigns
- `product_sales` - Flash Sale products
- `deals` - Deal campaigns
- `deal_products` - Deal main products
- `deal_sales` - Deal bundle products
- `marketing_campaigns` - Marketing campaigns
- `marketing_campaign_products` - Campaign products
- `promotions` - Promotion codes
- `users` - Admin users
- `members` - Customer users

### 5.2. Migration Strategy

1. **Phase 1:** Export Laravel database schema
2. **Phase 2:** Convert to PostgreSQL-compatible SQL
3. **Phase 3:** Create Go migrations using `golang-migrate`
4. **Phase 4:** Test migrations on staging
5. **Phase 5:** Run migrations on production

---

## 6. Migration Strategy

### 6.1. Phased Migration Approach

**Phase 1: Foundation (Weeks 1-2)**
- Set up Go backend project structure
- Set up Next.js frontend project
- Database migration scripts
- Basic API endpoints (read-only)

**Phase 2: Core Features (Weeks 3-6)**
- Product management APIs
- Cart APIs
- Order APIs
- Warehouse APIs

**Phase 3: Advanced Features (Weeks 7-10)**
- Flash Sale logic
- Deal Sốc logic
- Marketing Campaign
- Promotion codes

**Phase 4: Frontend Migration (Weeks 11-14)**
- Product pages
- Cart pages
- Checkout flow
- Admin dashboard

**Phase 5: Testing & Optimization (Weeks 15-16)**
- Integration testing
- Performance optimization
- Security audit
- Load testing

**Phase 6: Deployment (Week 17)**
- Staging deployment
- Production deployment
- Monitoring setup
- Rollback plan

### 6.2. Parallel Running Strategy

1. **Dual Write:** Write to both Laravel and Go databases
2. **Read from Go:** Gradually switch reads to Go
3. **Feature Flags:** Use feature flags for gradual rollout
4. **Monitoring:** Monitor both systems during transition
5. **Rollback:** Keep Laravel running until Go is stable

---

## 7. Development Workflow

### 7.1. Local Development Setup

**Backend:**
```bash
# Start PostgreSQL and Redis
docker-compose up -d

# Run migrations
make migrate-up

# Start server
go run cmd/api/main.go
```

**Frontend:**
```bash
# Install dependencies
npm install

# Start dev server
npm run dev
```

### 7.2. Code Organization

**Backend:**
- Follow Go best practices
- Use interfaces for dependency injection
- Write unit tests for services
- Write integration tests for APIs

**Frontend:**
- Use TypeScript strictly
- Component-based architecture
- Server Components for data fetching
- Client Components for interactivity

### 7.3. Testing Strategy

**Backend:**
- Unit tests: Services, repositories
- Integration tests: API endpoints
- E2E tests: Critical flows

**Frontend:**
- Component tests: React Testing Library
- E2E tests: Playwright
- API integration tests

---

## 8. Deployment Strategy

### 8.1. Infrastructure

**Backend:**
- Container: Docker
- Orchestration: Kubernetes (or Docker Swarm)
- Load Balancer: Nginx/HAProxy
- Database: Managed PostgreSQL (AWS RDS, Google Cloud SQL)
- Cache: Managed Redis (AWS ElastiCache, Google Cloud Memorystore)

**Frontend:**
- Hosting: Vercel (recommended) or self-hosted
- CDN: Cloudflare or Vercel Edge Network
- Static Assets: R2/CDN

### 8.2. CI/CD Pipeline

**Backend:**
1. Push to GitHub
2. GitHub Actions: Run tests
3. Build Docker image
4. Push to container registry
5. Deploy to staging/production

**Frontend:**
1. Push to GitHub
2. Vercel: Auto-deploy on push
3. Run tests and build
4. Deploy to preview/production

---

## 9. Implementation Phases

### Phase 1: Project Setup (Week 1-2)

**Backend:**
- [ ] Initialize Go project
- [ ] Set up Gin framework
- [ ] Configure database connection
- [ ] Set up Redis connection
- [ ] Create project structure
- [ ] Set up logging
- [ ] Set up configuration management

**Frontend:**
- [ ] Initialize Next.js project
- [ ] Set up TypeScript
- [ ] Configure Tailwind CSS
- [ ] Set up shadcn/ui
- [ ] Create project structure
- [ ] Set up API client
- [ ] Set up state management

### Phase 2: Core APIs (Week 3-6)

**Backend:**
- [ ] Product APIs (CRUD)
- [ ] Cart APIs
- [ ] Order APIs
- [ ] Warehouse APIs
- [ ] Authentication APIs
- [ ] Price calculation service
- [ ] Stock calculation service

**Frontend:**
- [ ] Product list page
- [ ] Product detail page
- [ ] Cart page
- [ ] Checkout page
- [ ] Admin layout
- [ ] Admin product management

### Phase 3: Advanced Features (Week 7-10)

**Backend:**
- [ ] Flash Sale logic
- [ ] Deal Sốc logic
- [ ] Marketing Campaign
- [ ] Promotion codes
- [ ] Google Merchant Center sync
- [ ] GHTK shipping integration

**Frontend:**
- [ ] Flash Sale pages
- [ ] Deal pages
- [ ] Admin Flash Sale management
- [ ] Admin Deal management
- [ ] Admin order management

### Phase 4: Testing & Optimization (Week 11-14)

- [ ] Write comprehensive tests
- [ ] Performance optimization
- [ ] Security audit
- [ ] Load testing
- [ ] Bug fixes
- [ ] Documentation

### Phase 5: Deployment (Week 15-17)

- [ ] Staging deployment
- [ ] Production deployment
- [ ] Monitoring setup
- [ ] Rollback plan
- [ ] Post-deployment monitoring

---

## 10. Key Implementation Details

### 10.1. Go Backend Service Example

**Price Engine Service:**
```go
package pricing

type Engine interface {
    CalculatePrice(productID, variantID, quantity int) (*PriceResult, error)
    CalculateDisplayPrice(productID, variantID int) (*PriceInfo, error)
}

type EngineService struct {
    productRepo    repository.ProductRepository
    flashSaleRepo  repository.FlashSaleRepository
    campaignRepo   repository.CampaignRepository
    dealRepo      repository.DealRepository
    warehouseRepo repository.WarehouseRepository
}

func (e *EngineService) CalculatePrice(productID, variantID, quantity int) (*PriceResult, error) {
    // Priority: Flash Sale > Marketing Campaign > Deal > Normal
    // Return complete formatted data
}
```

**Cart Service:**
```go
package cart

type Service interface {
    GetCart(sessionID string) (*CartResponse, error)
    AddItem(sessionID string, req *AddItemRequest) (*CartResponse, error)
    UpdateItem(sessionID string, variantID int, qty int) (*CartResponse, error)
    RemoveItem(sessionID string, variantID int) (*CartResponse, error)
    Checkout(sessionID string, req *CheckoutRequest) (*OrderResponse, error)
}

type CartService struct {
    cartRepo      repository.CartRepository
    priceEngine   pricing.Engine
    orderService  order.Service
    warehouseRepo repository.WarehouseRepository
}

func (s *CartService) GetCart(sessionID string) (*CartResponse, error) {
    // Get cart items
    // Recalculate all prices
    // Apply Deal logic
    // Format all data
    // Return complete response
}
```

### 10.2. Next.js Frontend Example

**API Client:**
```typescript
// lib/api/cart.ts
import { apiClient } from './client';

export const cartAPI = {
  getCart: async (): Promise<CartResponse> => {
    const response = await apiClient.get('/api/v1/cart');
    return response.data;
  },
  
  addItem: async (variantId: number, qty: number, isDeal?: boolean): Promise<CartResponse> => {
    const response = await apiClient.post('/api/v1/cart/items', {
      variant_id: variantId,
      qty,
      is_deal: isDeal ? 1 : 0,
    });
    return response.data;
  },
};
```

**Cart Component (Display Only):**
```typescript
// components/cart/CartList.tsx
'use client';

import { useQuery } from '@tanstack/react-query';
import { cartAPI } from '@/lib/api/cart';

export function CartList() {
  const { data, isLoading } = useQuery({
    queryKey: ['cart'],
    queryFn: () => cartAPI.getCart(),
  });

  if (isLoading) return <div>Loading...</div>;
  if (!data?.success) return <div>Error loading cart</div>;

  return (
    <div>
      {data.data.items.map((item) => (
        <div key={item.variant_id}>
          <h3>{item.product_name}</h3>
          <p>{item.price_info.final_price_formatted}</p>
          <p>Quantity: {item.quantity}</p>
          <p>Subtotal: {item.subtotal_formatted}</p>
          {/* Frontend chỉ hiển thị, không tính toán */}
        </div>
      ))}
      <div>
        <p>Total: {data.data.summary.total_formatted}</p>
        {/* Backend đã tính sẵn, frontend chỉ hiển thị */}
      </div>
    </div>
  );
}
```

---

## 11. Success Criteria

### 11.1. Performance Targets

- **API Response Time:** < 200ms (p95)
- **Page Load Time:** < 2s (First Contentful Paint)
- **Database Query Time:** < 50ms (p95)
- **Cache Hit Rate:** > 80%

### 11.2. Quality Targets

- **Test Coverage:** > 80%
- **API Uptime:** > 99.9%
- **Zero Business Logic in Frontend:** 100%
- **API Documentation:** 100% coverage

### 11.3. Migration Success

- All features migrated from Laravel
- No business logic in frontend
- All APIs documented
- Performance equal or better than Laravel
- Zero data loss during migration

---

## 12. Risk Mitigation

### 12.1. Technical Risks

1. **Database Migration:**
   - Risk: Data loss or corruption
   - Mitigation: Comprehensive backup, test migrations, rollback plan

2. **Performance:**
   - Risk: Slower than Laravel
   - Mitigation: Performance testing, optimization, caching strategy

3. **Business Logic:**
   - Risk: Missing logic during migration
   - Mitigation: Comprehensive documentation review, thorough testing

### 12.2. Business Risks

1. **Downtime:**
   - Risk: Service interruption during migration
   - Mitigation: Parallel running, gradual rollout, feature flags

2. **User Experience:**
   - Risk: Different UX causing confusion
   - Mitigation: Maintain similar UI/UX, user testing

---

## 13. Next Steps

1. **Review & Approval:** Review this proposal with team
2. **Resource Allocation:** Assign developers
3. **Timeline Confirmation:** Confirm 17-week timeline
4. **Infrastructure Setup:** Set up development environment
5. **Kickoff Meeting:** Start Phase 1

---

**Document Version:** 1.0  
**Last Updated:** 2026-01-25  
**Author:** AI Assistant  
**Status:** Proposal - Pending Approval

