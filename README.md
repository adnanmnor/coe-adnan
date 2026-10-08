# COE E-Commerce (Track A: Nuxt.js + Laravel)

Full-stack e-commerce order system — Milestone 1 & 2 selesai.

## Stack

| Layer | Teknologi |
|---|---|
| Frontend | Nuxt 4 (Vue 3, TypeScript) — http://localhost:3000 |
| Backend | Laravel 13 (PHP 8.4) — http://localhost:8000 |
| Database | PostgreSQL 16 |
| Cache / Session / Queue | Redis 7 |
| Object Storage | Garage (S3-compatible) |
| Container | Docker Compose |

## Quick Start

git clone git@github.com:adnanmnor/coe-adnan.git
cd coe-adnan
docker compose --profile core up -d
docker compose --profile core exec backend cp .env.example .env
docker compose --profile core exec backend php artisan key:generate
docker compose --profile core exec backend php artisan migrate --seed --force

Akses:
- Frontend: http://localhost:3000
- Backend API: http://localhost:8000/api
- Garage S3: http://localhost:3900

Admin default: admin@example.com / password123

## Struktur Repo

coe-adnan/
  src/
    frontend/           Nuxt 4
      app.vue
      composables/useAuth.ts
      pages/index.vue
      pages/login.vue
      pages/register.vue
      pages/profile.vue
      pages/products/index.vue
      pages/products/create.vue
      pages/products/[id].vue
      server/api/[...].ts    Proxy ke backend
    backend/            Laravel 13
      app/Http/Controllers/Api/AuthController.php
      app/Http/Controllers/Api/CartController.php
      app/Http/Controllers/Api/CategoryController.php
      app/Http/Controllers/Api/ProductController.php
      app/Http/Middleware/EnsureUserIsAdmin.php
      app/Http/Resources/ProductResource.php
      app/Http/Resources/CategoryResource.php
      bootstrap/app.php
      config/cors.php
      config/filesystems.php
      database/migrations/
      database/seeders/
      routes/api.php
  infra/garage/garage.toml
  docker-compose.yml
  README.md

## API Endpoints

Public:
- GET /api/health
- GET /api/products?search=&sort=&order=&page=&per_page=&category_id=&is_active=
- GET /api/products/{id}
- GET /api/products/{id}/image
- GET /api/categories
- POST /api/auth/register
- POST /api/auth/login

Protected (auth:sanctum):
- GET /api/auth/me
- PUT /api/auth/profile
- POST /api/auth/logout
- GET /api/cart
- POST /api/cart/items
- PUT /api/cart/items/{productId}
- DELETE /api/cart/items/{productId}
- DELETE /api/cart

Admin only:
- POST /api/products
- PUT /api/products/{id}
- DELETE /api/products/{id}
- POST /api/products/{id}/image
- POST/PUT/DELETE /api/categories/*

## Milestone 1 Checklist

- [x] Monorepo — src/frontend, src/backend, infra di root
- [x] docker compose up dari root
- [x] CRUD produk end-to-end
- [x] Frontend list & create produk
- [x] Schema normalized + index + FK
- [x] Pagination, filtering, search, sorting
- [x] Consistent error shape
- [x] README

## Milestone 2 Checklist

- [x] Register, login, update profile (JWT via Sanctum)
- [x] Protected routes reject tanpa token (401)
- [x] Admin-only endpoints tolak customer (403)
- [x] Cart — add/remove/update quantity, persist di Redis
- [x] Cache / session hits visible di Redis
- [x] Product image upload & retrieval via object storage (Garage)

## Architecture Decisions

1. Frontend proxy via Nuxt server routes
   Browser panggil /api/* (same-origin), Nuxt forward ke http://backend:8000/api/*.
   Kelebihan: tiada CORS, tiada host.docker.internal di browser.

2. Laravel proxy untuk image
   Garage v1.0.1 belum support anonymous access. Laravel stream image dari
   Garage melalui GET /api/products/{id}/image.

3. Sanctum vs JWT strict
   Sanctum memberi API token (Bearer <token>) yang berfungsi sama seperti JWT.
   Pilihan ini default Laravel 13.

## Environment Variables Penting

src/backend/.env:

APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=coe
DB_USERNAME=coe
DB_PASSWORD=secret

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379

FILESYSTEM_DISK=garage
AWS_ACCESS_KEY_ID=<garage-key-id>
AWS_SECRET_ACCESS_KEY=<garage-secret>
AWS_DEFAULT_REGION=garage
AWS_BUCKET=coe-products
AWS_ENDPOINT=http://garage:3900
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_URL=http://localhost:3900/coe-products

## Docker Profiles

docker compose --profile core up -d                            # Core
docker compose --profile core --profile queue up -d             # + Queue (M3)
docker compose --profile core --profile observability up -d     # + Observability (M4)

## Status

- [x] Milestone 1 — Foundation
- [x] Milestone 2 — Identity & State
- [ ] Milestone 3 — Async Order Flow
- [ ] Milestone 4 — Production Hardening
- [ ] Milestone 5 — System Design & Scale (optional)
