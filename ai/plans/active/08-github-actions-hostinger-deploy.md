# Plan 08 — Deploy to Hostinger with GitHub Actions

**Status:** Pending. Draft 2026-11-26; decisions 1–4 settled with the user the same day.
**Target:** `petlogde.fun` on Hostinger **shared** hosting.
**Companion:** `docs/DEPLOYMENT_GUIDE.md` already covers SMTP, the queue cron and the
manual deploy. This plan only covers **automating** it and the shared-hosting constraints.

> **Domain spelling:** every config in the repo uses **`petlogde.fun`**
> (`.env.docker`, `docs/DEPLOYMENT_GUIDE.md`, `MAIL_FROM_ADDRESS`). "petlodge.fun" has
> been typed in chat — confirm which is the registered domain before the first deploy,
> because every path below contains it.

---

## Problem

A plain `git pull` on the server cannot deploy this app, for four separate reasons:

1. **`public/build` is gitignored.** Compiled Vite assets are never in a checkout, so a
   git-based deploy ships markup with no (or stale) JS/CSS. This already broke the wizard
   locally — see `ai/JOURNAL.md` 2026-11-26.
2. **`public/.htaccess` was gitignored too** (`.gitignore` had a blanket `**/.htaccess`),
   so a clean checkout had no rewrite rules and every route except `/` would 404.
3. **`symlink()` is disabled** in Hostinger's PHP, so `php artisan storage:link` cannot run.
4. **The docroot is fixed at `public_html`**, but Laravel must serve from its `public/`.

---

## Decisions (settled 2026-11-26)

| # | Question | Decision |
|---|---|---|
| 1 | Server PHP version | **8.3** |
| 2 | `public/.htaccess` | **Done** — `!public/.htaccess` added to `.gitignore`, file now tracked |
| 3 | Document root | **Cannot be changed on shared hosting.** Keep `public_html` as the docroot and point it at the app (see Blocker 3) |
| 4 | Laravel root on the server | **`domains/petlogde.fun/laravel/`**, not `domains/petlogde.fun/` |
| 5–7 | SSH vs FTP, `clear` vs `cache`, dry-run rollout | Still open |

### Why `laravel/` and not `domains/petlogde.fun/` directly

The deciding reason is **`rsync --delete`**. `domains/petlogde.fun/` is Hostinger-managed:
it contains `public_html` and the domain's `logs/`. If that directory were the deploy
target, a sync with `--delete` would try to remove everything CI did not send — including
`public_html` and Hostinger's own folders. One mistake in the exclude file takes the site
down.

A dedicated `laravel/` subdirectory is owned entirely by CI, so `--delete` can only ever
affect files the deploy is responsible for. It also keeps the app tree from mixing with
whatever Hostinger adds to the domain folder later.

---

## Verified facts (checked 2026-11-26 — re-check before coding)

| Fact | Evidence |
|---|---|
| **`storage:link` is NOT needed in production** | Nothing serves files through the public disk. `Signature::url()` returns `route('signatures.show', …)`; `PdfService::DISK = 'local'`; `.env.production` sets `FILESYSTEM_DISK=local`. Blocker 3's symlink restriction therefore does not block the deploy. |
| Hostinger shared hosting cannot change the document root | Hostinger support docs: the root is `public_html`, and they recommend a VPS if it must change. Corroborated by multiple Laracasts threads. |
| `public/.htaccess` is now tracked | `git ls-files public/.htaccess` → `public/.htaccess` |
| No CI exists yet | `.github/` absent |
| Server PHP **8.3**; `composer.json` allows `^8.1`, no platform pin; Dockerfile builds **8.2** | User-confirmed + `composer.json` / `Dockerfile` |
| Queue is `database` + per-minute cron | `docs/DEPLOYMENT_GUIDE.md` §3 |
| Server root | `/home/u189079842/domains/petlogde.fun/` |

> ⚠️ The `storage:link` row is the one most likely to go stale. The moment anything starts
> using `Storage::disk('public')` or `asset('storage/…')`, it becomes a real blocker.
> Re-run that grep before trusting it.

### PHP version drift — decide before writing the workflow

Production is **8.3**, local Docker is **8.2**, and `composer.json` pins nothing. CI
resolving `vendor/` against the wrong version fails at *runtime*, not at build time.
Two ways out, **A preferred**:

- **A. Move local Docker to 8.3** (`FROM php:8.3-fpm-alpine`) so local, CI and production
  all match, then pin `config.platform.php` to `8.3`. Removes the drift instead of
  documenting it.
- **B. Keep Docker on 8.2** and pin `config.platform.php` to **`8.2`** — the *lowest*
  environment — so resolved packages run on both. Never pin 8.3 while local runs 8.2.

---

## Target layout on the server

```
/home/u189079842/domains/petlogde.fun/
├── laravel/                 ← the application (deploy target, owned by CI)
│   ├── app/ bootstrap/ config/ database/ resources/ routes/ vendor/
│   ├── public/              ← what public_html must resolve to
│   ├── storage/             ← PERSISTS between deploys, never synced over
│   ├── artisan
│   └── .env                 ← lives ONLY on the server, never produced by CI
├── public_html/             ← docroot, fixed by Hostinger (see Blocker 3)
└── logs/                    ← Hostinger-managed, must never be in rsync's path
```

**`storage/app/signatures/` is legal evidence** (CLAUDE.md §5). It must survive every
deploy and be backed up. Any `rsync --delete` that can reach it is a data-loss bug.

---

## Blocker 1 — assets must be built in CI

Build in the workflow and ship the result; never build on the shared host (no Node, tight
memory limits).

```yaml
- uses: actions/setup-node@v4
  with: { node-version: '22', cache: 'npm' }
- run: npm ci --legacy-peer-deps      # --legacy-peer-deps matches the Dockerfile
- run: npm run build                  # writes public/build/
```

`vendor/` likewise: build it in CI with `--no-dev`, ship it, so the server never runs
Composer.

## Blocker 2 — `.htaccess` must reach the server ✅ DONE

`.gitignore` keeps the blanket `**/.htaccess` guard but now carries `!public/.htaccess`,
and the file is tracked. Keep the workflow assertion anyway, as a regression guard:

```yaml
- run: test -s public/.htaccess || { echo "::error::public/.htaccess missing"; exit 1; }
```

## Blocker 3 — getting `public_html` to serve `laravel/public`

**An earlier draft of this plan recommended changing the document root in hPanel. That is
wrong for this hosting plan** — on Hostinger *shared* hosting the root is fixed at
`public_html`, and Hostinger's own docs say to move to a VPS if it must change. Both
options below leave the docroot alone.

Either option meets the security goal that prompted this: today the whole app is served
out of `public_html`, which exposes `.env`, `vendor/` and `storage/` to anything that can
guess a URL. After this, only `public/` content is reachable.

### Option A (try first) — symlink `public_html` → `laravel/public`

One-off, over SSH, **not** part of the deploy:

```bash
cd ~/domains/petlogde.fun
rm -rf public_html              # back it up first if anything live is still in there
ln -s laravel/public public_html
readlink public_html            # must print: laravel/public
```

`ln` is a shell binary, **not** PHP's disabled `symlink()`, so the `disable_functions`
setting does not apply.

- **Pros:** one command; `public/` stays a single source of truth; CI deploys to one
  destination; no patched `index.php`.
- **Cons:** needs SSH; requires `FollowSymLinks` (normally on); **hPanel can recreate
  `public_html` as a real directory** during domain operations, which silently breaks the
  site. Re-run `readlink public_html` after any hPanel change to the domain.

### Option B (fallback) — split: ship `public/` into `public_html/`

If the symlink is refused or keeps getting clobbered, deploy the *contents* of `public/`
into `public_html/` and keep the rest of the app in `laravel/`. `public_html/index.php`
must then be patched, because Laravel's stock file resolves `__DIR__.'/../vendor'`:

```php
<?php // deploy/public_html/index.php — deployed over public_html/index.php
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$base = __DIR__.'/../laravel';

if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $base.'/vendor/autoload.php';
$app = require_once $base.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request = Request::capture())->send();
$kernel->terminate($request, $response);
```

- **Pros:** no symlink, survives hPanel recreating `public_html`, works on any shared host.
- **Cons:** CI deploys to **two** destinations; `public/` is split from the app; the
  patched `index.php` must be kept in step with Laravel upgrades.
- Verify `storage_path()`, `public_path()` and `asset()` by hand if you go this way —
  `public_path()` will point at `laravel/public`, not at the served directory.

## Non-blocker — `storage:link`

Not needed today (see Verified facts). **Do not put `php artisan storage:link` in the
workflow** — it will fail on the disabled `symlink()`. If a public disk is ever
introduced, either create the link once over SSH with
`ln -s ../storage/app/public public/storage`, or serve that feature through an
authenticated route as signatures and PDFs already do.

---

## The workflow (sketch — `.github/workflows/deploy.yml`)

Written for **Option A**. For Option B, add a second rsync of `public/` into
`public_html/` plus a copy of `deploy/public_html/index.php`.

```yaml
name: Deploy to Hostinger
on:
  push:
    branches: [main]        # decide: main, or a dedicated `production` branch
  workflow_dispatch:

concurrency:
  group: deploy-production  # never let two deploys overlap
  cancel-in-progress: false

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'            # matches the Hostinger server
          extensions: pdo_mysql, zip, intl, gd, bcmath

      - run: composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

      - uses: actions/setup-node@v4
        with: { node-version: '22', cache: 'npm' }
      - run: npm ci --legacy-peer-deps
      - run: npm run build

      - name: Guard against shipping a broken release
        run: |
          test -s public/.htaccess           || { echo "::error::public/.htaccess missing"; exit 1; }
          test -f public/build/manifest.json || { echo "::error::vite manifest missing"; exit 1; }
          test -f vendor/autoload.php        || { echo "::error::vendor missing"; exit 1; }
          test ! -e public/hot               || { echo "::error::public/hot would disable all assets"; exit 1; }

      - name: Rsync to Hostinger
        uses: burnett01/rsync-deployments@v7   # pin to a commit SHA in the real workflow
        with:
          switches: -avzr --delete --exclude-from=deploy/rsync-exclude.txt
          path: ./
          remote_path: /home/u189079842/domains/petlogde.fun/laravel/
          remote_host: ${{ secrets.HOSTINGER_SSH_HOST }}
          remote_port: ${{ secrets.HOSTINGER_SSH_PORT }}
          remote_user: ${{ secrets.HOSTINGER_SSH_USER }}
          remote_key:  ${{ secrets.HOSTINGER_SSH_KEY }}

      - name: Post-deploy
        uses: appleboy/ssh-action@v1           # pin to a commit SHA
        with:
          host: ${{ secrets.HOSTINGER_SSH_HOST }}
          port: ${{ secrets.HOSTINGER_SSH_PORT }}
          username: ${{ secrets.HOSTINGER_SSH_USER }}
          key: ${{ secrets.HOSTINGER_SSH_KEY }}
          script: |
            set -e
            cd /home/u189079842/domains/petlogde.fun/laravel
            readlink ../public_html || echo "::warning::public_html is not a symlink"
            php artisan down --retry=15 || true
            php artisan migrate --force
            php artisan config:clear
            php artisan route:clear
            php artisan view:clear
            php artisan queue:restart
            php artisan up
```

> The `remote_path` ends at `laravel/`, never at `domains/petlogde.fun/` — see
> "Why `laravel/`" above. Getting this wrong plus `--delete` removes `public_html`.

### `deploy/rsync-exclude.txt`

```
.git/
.github/
node_modules/
storage/
.env
.env.*
tests/
ai/
docs/
docker/
docker-compose.yml
Dockerfile*
*.md
```

> `storage/` and `.env` are excluded **because `--delete` is on**. Removing either line
> destroys the signatures (legal evidence) or the production credentials. Prove the
> exclude file is honoured with a `--dry-run` deploy before the first real one.

### Caching: `clear` vs `cache` (open — decision 6)

The script above uses `config:clear`, matching `docs/DEPLOYMENT_GUIDE.md`, which warns
that a cached config outranks `.env` and has already hidden changed mail credentials.
`config:cache` + `route:cache` + `view:cache` is the faster production choice **only** if
the rule "any `.env` edit requires re-running the cache commands" is actually followed.
Decide before executing; do not mix the two.

---

## Secrets to create (repo → Settings → Secrets and variables → Actions)

| Secret | Notes |
|---|---|
| `HOSTINGER_SSH_HOST` | Hostinger SSH host |
| `HOSTINGER_SSH_PORT` | Usually not 22 on shared plans |
| `HOSTINGER_SSH_USER` | e.g. `u189079842` |
| `HOSTINGER_SSH_KEY` | Private half of a **deploy-only** keypair; add the public half in hPanel → SSH Access |

**If the plan has no SSH** (decision 5), swap both steps for an FTP action
(`SamKirkland/FTP-Deploy-Action`) and run the artisan commands from the existing
per-minute cron instead. Two consequences: Blocker 3 Option A becomes impossible (no shell
for `ln -s`), forcing Option B; and there is no trigger for `migrate --force`, so every
schema change becomes manual.

---

## Step-by-step execution order

1. ~~Confirm the server PHP version~~ → **8.3**. Set `php-version: '8.3'` in the workflow
   and resolve the drift (Dockerfile 8.2) per "PHP version drift" above.
2. ~~Fix Blocker 2~~ → **done 2026-11-26**; `public/.htaccess` is tracked.
3. Apply Blocker 3 **Option A** (symlink). Verify with `readlink public_html` and by
   loading the site **before** wiring any CI. Fall back to Option B if hPanel clobbers it.
4. Create `laravel/` on the server and upload `.env` **once**, by hand. Confirm
   `storage/` exists, is writable, and still holds the existing `app/signatures/`.
5. Add `deploy/rsync-exclude.txt`, then the workflow with `--dry-run` in `switches`.
   Read the output — especially that nothing under `storage/`, `public_html/` or `logs/`
   is listed for deletion.
6. Remove `--dry-run`, deploy, then walk the acceptance criteria.
7. Confirm the queue cron from `docs/DEPLOYMENT_GUIDE.md` §3 points at the new
   `laravel/` path, not at `public_html`.

---

## Acceptance criteria

- [ ] A push to the deploy branch produces a working site with **no manual step**.
- [ ] `https://petlogde.fun/` loads, and a deep route (`/check-in`) returns 200, not 404
      (proves `.htaccess` arrived and rewriting works).
- [ ] **`https://petlogde.fun/.env` returns 403/404, not the file.** Same for
      `/vendor/autoload.php` and `/storage/logs/laravel.log`. This is the security fix.
- [ ] Browser console is clean and assets load from `/build/assets/…` (proves the Vite
      build shipped and that no stale `public/hot` exists on the server).
- [ ] A drop-in produces a PDF and a signature, both readable only through their
      authenticated routes.
- [ ] `storage/app/signatures/` still contains every pre-deploy file.
- [ ] `php artisan migrate:status` shows all migrations run, with the `jobs` table present.
- [ ] A queued email is delivered within ~2 minutes of the cron tick.
- [ ] Two deploys in a row are safe — nothing duplicated, nothing wiped.
- [ ] `readlink public_html` still resolves (Option A only).

---

## Out of scope

- Zero-downtime atomic releases (`releases/` + a swapped symlink). Possible over SSH, but
  it adds a second symlink dependency on a host that already fights them; `artisan down/up`
  is the compromise here.
- A staging environment.
- Automated DB backups before `migrate --force` — **strongly recommended** before the
  first production deploy carrying a destructive migration, but it belongs in its own plan.
- Running PHPUnit as a deploy gate. Worth adding once the 4 known-red tests
  (`AGENTS.md` §4) are fixed; until then the gate could never be green.
- Moving to a VPS, which is Hostinger's own answer to the document-root limitation. Worth
  revisiting if the symlink proves unstable in practice.
