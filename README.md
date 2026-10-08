# COE E-Commerce (Track A: Nuxt.js + Laravel)

Full-stack e-commerce — Milestone 1: Foundation.

## Stack
- Frontend: Nuxt 4 (Vue 3 + TypeScript) — http://localhost:3000
- Backend: Laravel 13 (PHP 8.4) — http://localhost:8000
- Database: PostgreSQL 16
- Container: Docker Compose

## Quick Start

```bash
docker compose --profile core up -d
docker compose --profile core exec backend cp .env.example .env
docker compose --profile core exec backend php artisan key:generate
docker compose --profile core exec backend php artisan migrate --seed
