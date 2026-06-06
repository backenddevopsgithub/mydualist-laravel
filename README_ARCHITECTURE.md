# MyDualist Architecture

This document describes the Laravel rebuild architecture for MyDualist.

## Goals

- Replace the legacy WordPress system with a scalable SaaS-style platform
- Support web (Blade), future Flutter app, and admin tooling via a shared API
- Prioritize query performance, caching, queues, and transactional correctness
- Remain simple enough for a small team to maintain

## What We Are NOT Building

- WordPress-style post/meta storage
- Plugin or hook architecture
- Microservices, Kafka, CQRS, or event sourcing
- Docker/Kubernetes as a requirement for local development

## Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 11, PHP 8.2+ |
| Database | MySQL |
| Cache / sessions / queues | Redis |
| API auth | Laravel Sanctum |
| Admin | Filament 3 |
| Frontend | Blade, Tailwind CSS, Alpine.js |
| Testing | Pest PHP |
| Local dev | Laravel Herd (Mac) |

## High-Level Structure

```
laravel-app/
├── app/
│   ├── Actions/              # Base action class
│   ├── Services/             # Base service class
│   ├── Domains/              # Business domains (modular monolith)
│   ├── Enums/                # Shared enums
│   ├── Exceptions/           # API + domain exceptions
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   ├── Requests/Api/V1/
│   │   └── Resources/Api/V1/
│   ├── Filament/             # Thin admin UI
│   └── Models/               # Shared/core models (domain models move into Domains over time)
├── routes/
│   ├── api.php               # API entry — versioned routing
│   └── api/v1.php            # Version 1 endpoints
├── tests/
│   ├── Feature/Api/V1/
│   └── Unit/
└── config/mydualist.php      # App-specific configuration
```

## Domain-Driven Modular Monolith

Business logic lives inside **domains**, not controllers or Filament resources.

Initial domains:

| Domain | Responsibility |
|--------|----------------|
| `Auth` | Registration, login, verification, password reset |
| `Lists` | Dua list creation, slugs, quotas, completion |
| `Submissions` | Public submissions, slot locking, counters |
| `Community` | Community duas and related flows |
| `Shared` | Cross-domain enums and exceptions |

Each domain may contain:

```
app/Domains/{Domain}/
├── Actions/       # Single-purpose command objects
├── Services/      # Reusable domain services
├── Policies/      # Authorization rules
└── Enums/         # Domain-specific enums
```

## Request Flow (API)

```
HTTP Request
  → Route (routes/api/v1.php)
  → Form Request (validation)
  → Controller (thin — authorize + delegate)
  → Action or Service (business logic)
  → Resource (JSON transformation)
  → Response
```

Controllers must **not** contain business rules, quota logic, or calculations.

## Actions vs Services

**Actions** — one clear operation per class.

```php
app(Domains/Lists/Actions/CreateListAction::class)->handle($user, $data);
```

**Services** — shared logic used by multiple actions (slug generation, counters, caching helpers).

Both extend the base classes in `app/Actions/Action.php` and `app/Services/Service.php`.

## API Versioning

All public APIs are prefixed:

```
/api/v1/...
```

- `routes/api.php` — mounts version groups
- `routes/api/v1.php` — v1 endpoints
- Future versions add `routes/api/v2.php` without breaking v1 clients (Flutter, etc.)

Standard JSON success shape (controllers using `ApiController`):

```json
{
  "message": "Success",
  "data": { }
}
```

Standard JSON error shape (`ApiException` and subclasses):

```json
{
  "message": "Human-readable error",
  "error_code": "quota_exceeded",
  "errors": { "field": ["validation message"] }
}
```

## Authentication

- **Web / SPA / future mobile**: Laravel Sanctum token or cookie-based auth
- **Admin**: Filament session auth at `/admin`
- User model uses `HasApiTokens`

## Caching & Queues

Production defaults (see `.env.example`):

- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- `SESSION_DRIVER=redis`

Use Redis for hot reads, rate limiting, and cache invalidation around high-traffic flows (submissions, list views).

Heavy work (emails, exports, notifications) goes through queued jobs.

## Database Design Principles

- Normalized relational tables — no `wp_posts` / `wp_postmeta` patterns
- Indexed foreign keys and filter columns
- Transactional writes for quota/submission flows
- Pessimistic locking where concurrent submissions matter

## Filament Admin

Filament is a **UI layer only**.

- Display data
- Call Actions / Services
- Never embed business logic, quota rules, or calculations

Resources live in `app/Filament/Resources/`.

## Testing Strategy

Every feature ships with tests:

| Type | Scope |
|------|-------|
| Feature | HTTP/API flows, auth, validation, permissions |
| Unit | Actions, services, enums, calculations |
| Concurrency | Submission slot locking under parallel requests |

Run tests:

```bash
composer test
```

## Legacy Reference

`/wp-reference` (WordPress) is **read-only**. Use it to understand business behavior — never copy its architecture.

## Next Implementation Steps

1. **Auth domain** — register, login, email verification, Sanctum tokens, WP password migration
2. **Lists domain** — migrations, models, slug generation, quota fields
3. **Submissions domain** — transactional slot decrement + concurrency tests

Build feature-by-feature. No feature is complete without migrations, policies, actions, API resources, and tests.
