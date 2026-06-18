# Load testing (k6)

MyDuaList uses [k6](https://k6.io/) to simulate Arafah traffic against **local** or **staging** environments.

Scripts live in `load-tests/` and target the hot paths from the performance work:

| Script | What it exercises |
|--------|-------------------|
| `smoke.js` | Quick sanity check: public list page + owner dashboard |
| `public-list.js` | Many concurrent visitors reading a public list page |
| `public-submit.js` | Sustained public dua submissions (writes) |
| `owner-dashboard.js` | List owners refreshing `/dashboard/lists/{slug}` |
| `arafah-mixed.js` | Combined scenario (~70% reads, ~20% owner refresh, ~10% submits) |

## 1. Install k6

**Windows (recommended)**

```powershell
winget install k6 --source winget
```

Or Chocolatey:

```powershell
choco install k6
```

Verify:

```powershell
k6 version
```

## 2. Prepare the app

Run these from the Laravel project root (`mydualist-laravel/`).

### Enable load-test mode (local/staging only)

Add to `.env`:

```env
MYDUALIST_LOAD_TESTING=true
MYDUALIST_LOAD_TESTING_SKIP_SPAM_GUARD=true
```

This relaxes public submission rate limits and skips duplicate-content spam checks so k6 can submit from one machine. **Never enable on production.**

Clear config cache if you use it:

```powershell
php artisan config:clear
```

### Seed deterministic test data

```powershell
php artisan load-test:seed --submissions=500
```

This creates:

- Owner: `loadtest@mydualist.local` / `loadtest-password`
- Public list slug: `arafah-load-test`
- 500 submissions on that list (for realistic owner dashboard load)

It writes `load-tests/fixtures/manifest.json`, which k6 reads for URLs and credentials.

To reset submissions:

```powershell
php artisan load-test:seed --submissions=500 --fresh
```

### Run the app like production

For meaningful numbers, use the same stack you deploy with:

```powershell
# Example local stack
php artisan migrate
php artisan queue:work
# Use your normal web server (Valet, Herd, nginx, etc.)
```

Use **MySQL/Redis** if that is what staging/production uses. SQLite and file sessions underestimate real latency.

## 3. Run tests

All commands assume you are in `mydualist-laravel/`.

### Smoke test (start here)

```powershell
k6 run load-tests/smoke.js
```

Override base URL if needed:

```powershell
k6 run -e BASE_URL=http://mydualist-laravel.test load-tests/smoke.js
```

### Individual scenarios

**Public list reads** (default: ramp to 200 VUs):

```powershell
k6 run load-tests/public-list.js
```

**Public submissions** (default: ramp to 20 req/s):

```powershell
k6 run load-tests/public-submit.js
```

**Owner dashboard** (default: ramp to 100 VUs):

```powershell
k6 run load-tests/owner-dashboard.js
```

### Full Arafah mix (500 concurrent users)

```powershell
k6 run load-tests/arafah-mixed.js
```

Tune concurrency:

```powershell
k6 run -e TARGET_VUS=2000 -e SUBMIT_RPS=25 load-tests/arafah-mixed.js
```

### Windows helper script

```powershell
.\load-tests\run.ps1 smoke
.\load-tests\run.ps1 public-list
.\load-tests\run.ps1 arafah
```

## 4. Read the results

k6 prints a summary when the run finishes. Focus on:

| Metric | Target (starting point) |
|--------|-------------------------|
| `http_req_failed` | < 1–3% |
| `http_req_duration` p(95) | < 800ms reads, < 2s writes |
| `checks` | All passing |

Named metrics (e.g. `http_req_duration{name:GET owner list dashboard}`) map to specific routes.

If you see many **429** responses, confirm `MYDUALIST_LOAD_TESTING=true` and `APP_ENV=local` (or `staging`), then run `php artisan config:clear`.

If submissions fail validation, ensure the seeded list is published and accepting submissions:

```powershell
php artisan load-test:seed --fresh
```

## 5. Recommended test progression

1. **Smoke** — 5 VUs, 30s (`smoke.js`)
2. **Read path** — 200–500 VUs on public list (`public-list.js`)
3. **Write path** — 10–20 submissions/s (`public-submit.js`)
4. **Owner path** — 100–500 VUs on dashboard (`owner-dashboard.js`)
5. **Combined** — `arafah-mixed.js` at 500 VUs, then scale toward 2,000

Run each step, fix bottlenecks (DB indexes, queue workers, PHP-FPM workers, opcache), then increase load.

## 6. Staging / pre-Arafah checklist

- [ ] Migrations applied (`php artisan migrate`)
- [ ] Counter reconcile run once: `php artisan submissions:reconcile-counters`
- [ ] Lock reconcile run once: `php artisan submissions:reconcile-locks`
- [ ] Queue workers scaled for submission side-effects
- [ ] `MYDUALIST_LOAD_TESTING=true` on staging only during the test window
- [ ] Monitor MySQL slow query log, PHP-FPM queue, and error rate during the run
- [ ] Turn **off** load-test mode after testing

## Environment variables

| Variable | Purpose |
|----------|---------|
| `MYDUALIST_LOAD_TESTING` | Enable relaxed rate limits (local/staging only) |
| `MYDUALIST_LOAD_TESTING_SKIP_SPAM_GUARD` | Skip duplicate-content cache guard during k6 submits |
| `BASE_URL` | k6 override for app URL |
| `TARGET_VUS` / `TARGET_RPS` | Concurrency tuning per script |
| `VUS` / `DURATION` | Smoke test sizing |

See `.env.example` for Laravel-side defaults.
