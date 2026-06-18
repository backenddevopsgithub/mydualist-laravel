# Legacy WordPress Migration — Production Order

Run imports in this order during production cutover. Each step is idempotent and supports `--dry-run` before the real import.

## Prerequisites

1. Configure the WordPress source (`WP_DB_*` in `.env`) for `--database` mode, or prepare SQL/CSV exports.
2. Run Laravel migrations so traceability columns exist (`wp_post_id`, `wp_order_id`).
3. Seed billing products: `php artisan db:seed --class=BillingProductSeeder`.

## Import sequence

```bash
# Phase 2A — foundation data
php artisan migrate:users       --database --report=storage/app/legacy-import-users-report.json
php artisan migrate:suggestions --database --report=storage/app/legacy-import-suggestions-report.json
php artisan migrate:lists       --database --report=storage/app/legacy-import-lists-report.json

# Phase 2B — billing, submissions, community
php artisan migrate:purchases        --database --report=storage/app/legacy-import-purchases-report.json
php artisan migrate:submissions      --database --report=storage/app/legacy-import-submissions-report.json
php artisan migrate:community-duas   --database --report=storage/app/legacy-import-community-duas-report.json

# Validation
php artisan migrate:validate --report=storage/app/legacy-import-validate-report.json
```

`blog:import` is independent and can run before or after domain imports.

## Dependency graph

```text
Users → Suggestions → Lists → Purchases → Entitlements
                                    ↓
                              Submissions → Lock Reconciliation
                                    ↓
                            Community Duas (links paid purchases)
                                    ↓
                               Validation
```

**Do not** run `migrate:submissions` before `migrate:purchases`. Submissions depend on imported purchases/entitlements for lock reconciliation and `_order_id` unlock links.

**Do not** run `migrate:community-duas` before `migrate:purchases` for paid community duas — fulfillment links purchases via `_order_id` / `wp_order_id`.

## Source modes

Every import command accepts exactly one source:

| Flag | Description |
|------|-------------|
| `--database` | Live WordPress MySQL connection (`database.connections.wordpress`) |
| `--sql=path` | WordPress SQL dump file |
| `--csv=path` | Normalized CSV export |

Community dua CSV imports optionally read `*-queue.csv` alongside the main file (e.g. `community-duas-queue.csv` next to `community-duas.csv`).

## Dry run

Add `--dry-run` to any import command to validate mapping and generate a JSON report without writing data:

```bash
php artisan migrate:purchases --database --dry-run
```

## What each Phase 2B command does

### `migrate:purchases`

- Imports WooCommerce completed orders (`wc-completed`, `wc-processing`).
- Maps product IDs `728`, `730`, `731`, `914`, `3211` to `billing_products.external_product_id`.
- Upserts `billing_purchases` by `wp_order_id`.
- Replays `PurchaseFulfillmentService` to create `entitlement_grants` with deterministic `dedupe_key` values.
- Defers `COMMUNITY_DUA_PAID` fulfillment until `migrate:community-duas` links the community dua.

### `migrate:submissions`

- Imports `submission` CPT records into `dua_submissions` (preserves `wp_post_id`).
- Expands legacy `dua_submissions` serialized arrays when list `migrated` meta is false.
- Maps `_list_id` via `dua_lists.wp_post_id`, status/show/reported fields, and WhatsApp phone parsing.
- Runs **lock reconciliation** after import: recomputes `is_locked` from entitlements + submission order (not legacy `_show` alone), then replays purchase unlocks.

### `migrate:community-duas`

- Imports `community_dua` CPT into `community_duas`.
- Imports user queue state (`_completed_community_duas`, `_seen_duas`, `_seeing_now`, `_showing`, `_pattern`).
- Links paid duas to purchases via `_order_id` → `wp_order_id` and fulfills pending community dua purchases.

### `migrate:validate`

Produces a JSON report with:

- Imported totals (users, lists, submissions, suggestions, community duas, purchases, locks)
- Broken relationships (orphan submissions/lists)
- Duplicate list slugs
- Missing cover images
- Entitlement / visible-quota mismatches
- Unfulfilled purchases (warnings)

Exit code `0` when validation passes; `1` when failures or mismatches exist.

## Reports

Default report paths (override with `--report=`):

| Command | Default path |
|---------|----------------|
| `migrate:purchases` | `storage/app/legacy-import-purchases-report.json` |
| `migrate:submissions` | `storage/app/legacy-import-submissions-report.json` |
| `migrate:community-duas` | `storage/app/legacy-import-community-duas-report.json` |
| `migrate:validate` | `storage/app/legacy-import-validate-report.json` |

Configured in `config/mydualist.php` under `legacy.import`.

## Troubleshooting

| Symptom | Likely cause |
|---------|----------------|
| Purchase failed: list not found | Run `migrate:lists` first; verify `_list_id` order meta |
| Purchase failed: customer not found | Run `migrate:users` first |
| Submission failed: list not found | List `wp_post_id` mismatch with `_list_id` |
| Community dua purchase unfulfilled | Run `migrate:community-duas` after purchases |
| Validation: visible exceeds quota | Re-run `migrate:submissions` to reconcile locks |
| Duplicate slug failure | Resolve slug conflicts manually before re-import |

## Testing

```bash
php artisan test tests/Feature/LegacyImport
```
