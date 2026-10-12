# ADR 0001: Monolith vs Microservices untuk COE E-Commerce

- **Status**: Accepted
- **Date**: 2026-10-12
- **Deciders**: Adnan (developer), Team Lead (reviewer)
- **Context**: Milestone 5 — System Design & Scale

---

## Context

Reference project COE E-Commerce adalah small-scale e-commerce dengan:
- Product catalog + cart + checkout + order lifecycle
- Auth (Sanctum), RBAC (admin/customer)
- Redis cart, Garage object storage
- Async order flow (queue worker)
- Target deployment: single dev laptop (8GB RAM), kemungkinan scale ke production
- Team size: 1-2 developer untuk reference, tapi mungkin grow ke 5-10 untuk production

Kita perlu decide: **monolith** (satu Laravel app) atau **microservices** (split ke services kecil).

---

## Decision

**Kekal sebagai MONOLITH (modular monolith) untuk sekarang.**

Namun, kita telah **structurally prepare untuk microservices** jika perlu nanti:
- API gateway (Nginx) sebagai single entry point
- Backend stateless (session di Redis, bukan in-process)
- Load balancer sedia (2 replicas berfungsi)
- Service boundaries jelas (Auth, Cart, Order, Product, Category controllers)
- Async decoupling via queue (ProcessOrderJob)

---

## Considered Options

### Option A: Monolith (dipilih)

**Kelebihan:**
- **Simple deployment** — satu Docker image, satu CI/CD pipeline
- **Development speed tinggi** — single codebase, shared models, no network overhead
- **Transactions atomic** — checkout order + decrement stock mudah dalam satu DB transaction
- **RAM friendly** — 8GB laptop boleh run + 2 replicas (2 × 512MB)
- **Easier debugging** — satu log stream, satu code path
- **Iterasi cepat** — code change deploy sekali

**Kekurangan:**
- Single point of failure (mitigated: replicas)
- Vertical scaling hanya sampai limit hardware
- Coupling antara domain (product vs order vs auth)
- Team scaling terhad (code ownership ambigu)

### Option B: Microservices

**Kelebihan:**
- **Independent scaling** — service order boleh scale 5x, service category tak perlu
- **Tech diversity** — guna bahasa/framework berbeza per domain
- **Fault isolation** — order service crash tak matikan product listing
- **Team autonomy** — setiap service team boleh deploy sendiri

**Kekurangan:**
- **Operational overhead besar** — 6+ service, 6+ DB, 6+ pipeline
- **Network latency** — setiap cross-service call = HTTP overhead
- **Distributed transactions** — checkout perlu Saga pattern (complex)
- **Debugging susah** — distributed tracing wajib
- **RAM hunger** — setiap service JVM/PHP-FPM perlu memory
- **Dev speed turun** — locally test end-to-end flow kena up 6 containers
- **Duplikasi model** — DTO/event schema duplication

### Option C: Modular Monolith (middle ground) — **RECOMMENDED NEXT STEP**

**Kelebihan:**
- Monolith deployment tetap simple
- Domain boundaries explicit (modules with clear interfaces)
- Boleh extract ke microservice **later** tanpa refactor besar
- Untuk scale besar, extract hot domains (order, payment)

**Kekurangan:**
- Discipline tinggi — developer mesti respect module boundaries
- Overhead moderate (folder structure, event bus internal)

---

## Rationale

Untuk **reference project dan early-stage production**, monolith adalah pilihan terbaik:

1. **Scale belum justify microservices** — kita handle < 100 req/s, monolith + 2 replicas cukup.
2. **Domain boundaries belum jelas** — architecture emergent, lebih baik discover dulu.
3. **Team kecil** — microservices team topology Conway's Law tak padan.
4. **Operational maturity** — belum ada SRE/platform team untuk maintain 6+ services.
5. **Transactional consistency** — checkout flow butuh atomic DB transaction (order + items + stock), senang dalam monolith.
6. **Dev laptop 8GB** — microservices terlalu banyak container.

**Prepared for microservices:** Dengan API gateway + stateless backend + queue + service boundary, kita boleh **extract** service bila perlu tanpa rewrite:
- Order service → extract kalau order volume tinggi
- Payment service → extract kalau multi-gateway need
- Catalog service → extract kalau tinggi read (boleh guna separate DB/read replica)

---

## Consequences

**Positive:**
- Development iteration fast, deployment simple
- Debugging straightforward
- RAM/CPU efficient untuk dev laptop
- Boleh scale horizontal (2+ replicas) tanpa code change

**Negative:**
- Coupling antara domain — refactor risky bila codebase besar
- Vertical scaling limit — bila hit hardware ceiling, kena split
- Single point of failure code path (mitigated: replicas + health checks)

**Mitigations:**
- Maintain clean module boundaries dalam `app/Http/Controllers/Api/*`
- Gunakan events/jobs untuk decouple side effects (ProcessOrderJob sudah)
- Monitor metrics untuk tahu kapan perlu extract service
- Dokumentasi kontrak API via OpenAPI (Scramble)

---

## Migration Path ke Microservices (jika perlu)

Jika trafik grow, extract dalam urutan:

1. **Phase 1**: Extract **Notification Service** — email/SMS/Slack, tak critical path
2. **Phase 2**: Extract **Payment Service** — integrate multiple gateways (Stripe, PayPal)
3. **Phase 3**: Extract **Order Service** — high write volume, complex lifecycle
4. **Phase 4**: Extract **Product/Catalog Service** — high read, boleh guna read replicas
5. **Phase 5**: Keep **Auth Service** monolith paling lama (shared DB users)

Setiap extraction guna:
- API Gateway (Nginx/Kong) untuk routing
- Message queue (RabbitMQ/Kafka) untuk async events
- Shared database access pattern atau separate DB + saga

---

## References

- Sam Newman, "Monolith to Microservices" (O'Reilly)
- Martin Fowler, "Microservices Prerequisites" — https://martinfowler.com/bliki/MicroservicePrerequisites.html
- Martin Fowler, "Monolith First" — https://martinfowler.com/bliki/MonolithFirst.html
- AWS Prescriptive Guidance: Monolith vs Microservices

---

**Sign-off**:
- Developer: Adnan
- Reviewer: _(pending)_
- Date: 2026-10-12