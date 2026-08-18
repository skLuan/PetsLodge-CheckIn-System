# Feature: Monitoring & Health

## Purpose

Production observability: structured `/health` endpoints that probe the database,
cache, filesystem, and data integrity, plus an admin-facing monitoring dashboard.

## Data model

No dedicated tables. The feature reads runtime state (DB connectivity, cache
round-trip, Laravel log file) and reports metrics (memory, PHP/Laravel versions,
environment, server uptime).

## Routes & endpoints

- `GET /health` → `HealthCheckController@index` — aggregate health JSON.
- `GET /health/report` → `HealthCheckController@report` — detailed config + recent errors.
- `GET /admin/monitoring-dashboard` → `admin.monitoring-dashboard` view
  (guards: `auth` + `verified` + `admin.only`).

## Backend (`HealthCheckController`)

**`index`** returns:
- `status`: `healthy` | `unhealthy` (HTTP `200` vs `503`).
- `checks`:
  - `database` — PDO ping + `information_schema` table count + response time.
  - `cache` — write/read/forget round-trip via `Cache` facade (Redis) + response time.
  - `filesystem` — `storage_path()` writability.
  - `data_integrity` — spot-checks key tables/counts.
- `metrics` — memory usage (current/peak), server uptime, PHP & Laravel versions, env.

**`report`** returns app/db/cache/logging config + the last ~10 error lines parsed
from `storage/logs/laravel.log`.

## Frontend

- View: `resources/views/admin/monitoring-dashboard.blade.php` — admin-only dashboard
  polling/displaying the health data.

## Behavior & flow

- Health is the aggregate `AND` of all checks; any `unhealthy` check flips the whole
  status to `unhealthy` (HTTP 503), suitable for load-balancer / uptime probes.
- Deployment tooling (`scripts/pre-deployment-check.sh`,
  `post-deployment-verify.sh`, `rollback.sh`) can target `/health` to verify releases.

## Key files

`app/Http/Controllers/HealthCheckController.php`,
`resources/views/admin/monitoring-dashboard.blade.php`,
`scripts/{pre-deployment-check,post-deployment-verify,rollback}.sh`.
