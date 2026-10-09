# COE E-Commerce (Track A: Nuxt.js + Laravel)

Full-stack e-commerce order system — Milestone 1-4 selesai.

## Stack

| Layer | Teknologi |
|---|---|
| Frontend | Nuxt 4 (Vue 3, TypeScript) — port 3000 |
| Backend | Laravel 13 (PHP 8.4) — port 8000 |
| Database | PostgreSQL 16 |
| Cache / Session / Queue | Redis 7 |
| Object Storage | Garage (S3-compatible) |
| Reverse Proxy | Nginx (HTTPS self-signed) — port 8443 |
| Observability | Loki + Promtail + Prometheus + Grafana |
| Container | Docker Compose |

## Quick Start

    git clone git@github.com:adnanmnor/coe-adnan.git
    cd coe-adnan
    docker compose --profile core up -d
    docker compose --profile core exec backend cp .env.example .env
    docker compose --profile core exec backend php artisan key:generate
    docker compose --profile core exec backend php artisan migrate --seed --force

Akses:
- Frontend (HTTP): http://localhost:3000
- Backend API (HTTP): http://localhost:8000/api
- Full stack (HTTPS): https://localhost:8443 (self-signed, guna `-k` di curl)
- API Docs: http://localhost:8000/docs/api
- Grafana: http://localhost:3200 (admin/admin)
- Prometheus: http://localhost:9090

Admin default: admin@example.com / password123

## Docker Profiles

    docker compose --profile core up -d                    # Core (frontend, backend, db, redis, garage, nginx)
    docker compose --profile observability up -d           # + Loki, Promtail, Prometheus, Grafana
    docker compose --profile core --profile observability up -d

## Struktur Repo

    coe-adnan/
      src/backend/         Laravel app
        app/Http/Controllers/Api/   Auth, Cart, Category, Order, Product
        app/Http/Middleware/        EnsureUserIsAdmin
        app/Http/Resources/         API resources
        app/Jobs/                   ProcessOrderJob
        app/Services/               OrderService
        app/Providers/              AppServiceProvider (metrics + rate limit)
        config/                     cors, filesystems, prometheus, scramble
        database/migrations/        users, categories, products, orders, order_items, payments
        database/seeders/           Category, Product, AdminUser
        routes/api.php
        tests/                      Feature + Unit (41 tests)
      src/frontend/        Nuxt app
        composables/                useAuth, useCart
        pages/                      login, register, profile, cart, orders, products
        server/api/[...].ts         Proxy ke backend
      infra/               Infrastructure configs
        garage/garage.toml
        nginx/nginx.conf
        observability/              loki, promtail, prometheus, grafana
      check.ps1            Check script (lint/test/build dengan path filtering)
      docker-compose.yml
      README.md

## API Endpoints

### Public
- GET /api/health
- GET /api/metrics (Prometheus)
- GET /api/products (search, filter, sort, pagination)
- GET /api/products/{id}
- GET /api/products/{id}/image (serve dari Garage)
- GET /api/categories
- POST /api/auth/register (throttle 5/min)
- POST /api/auth/login (throttle 5/min)

### Protected (auth:sanctum, throttle 60/min)
- GET /api/auth/me
- PUT /api/auth/profile
- POST /api/auth/logout
- GET /api/cart
- POST /api/cart/items
- PUT /api/cart/items/{productId}
- DELETE /api/cart/items/{productId}
- DELETE /api/cart
- GET /api/orders
- GET /api/orders/{order}
- POST /api/checkout

### Admin only
- POST/PUT/DELETE /api/products/*
- POST /api/products/{id}/image
- POST/PUT/DELETE /api/categories/*
- PUT /api/orders/{order}/status

## Milestone Checklist

### Milestone 1 — Foundation ✅
- [x] Monorepo (src/frontend, src/backend, infra at root)
- [x] docker compose up works
- [x] Product CRUD end-to-end
- [x] Frontend list & create products
- [x] Schema normalized + indexes + FK
- [x] Pagination, filtering, search, sorting
- [x] Consistent error shape
- [x] README

### Milestone 2 — Identity & State ✅
- [x] Register/login/profile via JWT (Sanctum)
- [x] Protected routes reject 401
- [x] Admin-only endpoints reject customer 403
- [x] Redis cart (add/remove/update, persist 7d TTL)
- [x] Redis cache/session
- [x] Object storage (Garage) — upload via admin, serve via Laravel proxy

### Milestone 3 — Async Order Flow ✅
- [x] Checkout → order dengan unique ID + pricing snapshot
- [x] Order creation return immediately (async)
- [x] Queue worker proses job (Laravel Queue, Redis driver)
- [x] Forced failure retry 3x + dead-letter (failed_jobs)
- [x] Mock payment recorded (success + fail)
- [x] Lifecycle: pending → paid → shipped → delivered
- [x] Order history + detail view

### Milestone 4 — Production Hardening ✅
- [x] Check script (lint/test/build) + path filtering + pre-push hook
- [x] Logs centralized (Loki + Promtail)
- [x] Metrics dashboard (Grafana + Prometheus)
- [x] Metrics endpoint /api/metrics (scrapeable)
- [x] Test suite (41 tests, 101 assertions)
- [x] OpenAPI spec (Scramble, /docs/api)
- [x] Rate limiting (auth 5/min, api 60/min)
- [x] HTTPS (Nginx reverse proxy, self-signed dev cert)
- [x] Config from env vars (.env.example)
- [x] Input validation + parameterized queries (Eloquent)

## Development

### Check script

    powershell -ExecutionPolicy Bypass -File .\check.ps1               # Auto-detect changes
    powershell -ExecutionPolicy Bypass -File .\check.ps1 -All          # Run all
    powershell -ExecutionPolicy Bypass -File .\check.ps1 -BackendOnly
    powershell -ExecutionPolicy Bypass -File .\check.ps1 -FrontendOnly

### Pre-push hook

Automatik run `check.ps1` sebelum `git push`. Skip dengan `git push --no-verify`.

### Run tests

    docker compose --profile core exec backend php artisan test

### Generate queue worker

    docker compose --profile core exec backend php artisan queue:work --tries=3 --timeout=60

## Architecture Decisions

1. **Frontend proxy via Nuxt server routes** — `/api/*` di-serve melalui Nuxt server route `server/api/[...].ts`, forward ke `http://backend:8000/api/*`. Kebaikan: no CORS, no host.docker.internal pada browser, service name Docker boleh kekal.

2. **Laravel proxy untuk image** — Garage v1.0.1 belum support anonymous access. Laravel stream image melalui `GET /api/products/{id}/image`. Kelebihan: keselamatan + fleksibiliti (boleh tambah auth).

3. **Sanctum vs JWT strict** — Sanctum default Laravel 13, API token berfungsi seperti JWT. Maintenance lebih mudah.

4. **Async order processing** — `ProcessOrderJob` di-dispatch ke Redis queue, worker proses dalam background. Retry 3x dengan backoff 5 detik, failed jobs ke `failed_jobs` table.

5. **Pricing snapshot** — OrderItem simpan `unit_price` dan `product_name` untuk elak isu bila harga produk berubah.

## Status

- ✅ Milestone 1 — Foundation
- ✅ Milestone 2 — Identity & State
- ✅ Milestone 3 — Async Order Flow
- ✅ Milestone 4 — Production Hardening
- ⏳ Milestone 5 — System Design & Scale (optional/stretch)
