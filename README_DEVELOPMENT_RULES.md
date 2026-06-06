# MyDualist Development Rules

Rules for building the new Laravel platform inside `/laravel-app`.

## Golden Rules

1. **Never modify `/wp-reference`** — read-only legacy reference
2. **All new code goes in `/laravel-app`**
3. **Do not clone WordPress** — build a modern, normalized Laravel platform
4. **No feature without tests**
5. **Prefer simplicity** over enterprise complexity

## Where Code Belongs

| Layer | Allowed | Not Allowed |
|-------|---------|-------------|
| Controllers | Authorize, validate, call Actions, return Resources | Business logic, calculations, quota rules |
| Filament | Display data, call Actions/Services | Business logic, direct mutation rules |
| Actions | Single-purpose business operations | HTTP concerns |
| Services | Shared domain logic | HTTP / UI concerns |
| Policies | Authorization | Business calculations |
| Jobs | Async side effects | Core synchronous validation |
| Routes | Wiring only | Logic |

## Feature Checklist

Every feature must include:

- [ ] Migration(s) with indexes
- [ ] Model(s) with relationships
- [ ] Policy
- [ ] Form Request validation
- [ ] Action(s) and/or Service(s)
- [ ] API Resource(s)
- [ ] Feature tests
- [ ] Unit tests for Actions/Services
- [ ] Authorization tests (guest, owner, admin, forbidden)

## API Development

- All APIs under `/api/v1/`
- Use `ApiController` helpers for consistent JSON
- Use `ApiResource` for response transformation
- Design for Blade frontend **and** future Flutter app
- Validate with Form Requests in `app/Http/Requests/Api/V1/`

## Testing

Stack: **Pest PHP**

```bash
composer test              # all tests
composer test:unit         # unit only
composer test:feature      # feature only
```

### Required coverage

- Successful flows
- Authorization and permissions
- Validation failures
- Edge cases
- DB state after mutations
- Cache invalidation (where applicable)
- Queue dispatch (where applicable)

### Concurrency (critical for submissions)

Test parallel requests against limited slots:

- No overbooking
- Accurate `remaining_slots` / counters
- Correct failure responses when full

Use factories — avoid manual seed boilerplate in tests.

Tests use SQLite in-memory (`phpunit.xml`) for speed and isolation.

## Git Workflow

- Small, feature-focused commits
- Build → test → fix → commit
- Do not commit `.env` or secrets

## Local Development (Laravel Herd)

1. Link the site in Herd pointing to `laravel-app/public`
2. Copy `.env.example` → `.env` and configure MySQL + Redis
3. Run migrations: `php artisan migrate`
4. Install JS assets: `npm install && npm run dev`
5. Create admin user when Filament auth is wired

Suggested local URL: `http://mydualist.test`

## Environment Defaults

| Setting | Production intent |
|---------|-------------------|
| `DB_CONNECTION` | `mysql` |
| `CACHE_STORE` | `redis` |
| `QUEUE_CONNECTION` | `redis` |
| `SESSION_DRIVER` | `redis` |

## Performance Priorities

The legacy WordPress system struggled under load. Always consider:

- Indexed queries
- Redis caching for hot reads
- Queue async work (email, notifications)
- Efficient pagination
- Transactional writes for quota/submission flows

## What We Intentionally Skip

- Docker / Kubernetes (for now)
- Microservices
- Kafka
- CQRS / event sourcing
- Over-abstracted enterprise patterns

Infrastructure can grow later. **Business logic correctness and scalability come first.**

## Code Style

- Match existing conventions in the file you edit
- Use Laravel Pint before committing: `./vendor/bin/pint`
- Keep diffs focused — no drive-by refactors

## Adding a New Domain Feature

1. Create domain folder under `app/Domains/{Name}/`
2. Add migration + model
3. Write Action(s) with unit tests
4. Add policy + authorization tests
5. Add thin controller + Form Request + Resource
6. Register route in `routes/api/v1.php`
7. Add Filament resource (thin) if admin needs it
8. Run `composer test`

## Questions?

When unsure about business behavior, check `/wp-reference` for **what** the feature should do — then implement it the **Laravel way** described in `README_ARCHITECTURE.md`.
