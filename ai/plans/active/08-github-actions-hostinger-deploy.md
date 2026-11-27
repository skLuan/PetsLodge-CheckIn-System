# Plan 08 — Deploy to Hostinger with GitHub Actions

**Status:** Pending. Draft 2026-11-26; decisions 1–4 settled, then **rewritten the same
day against two working deployments of the user's own** (see "Prior art").
**Target:** `https://petlodge.fun/` on Hostinger **shared** hosting.
**Companion:** `docs/DEPLOYMENT_GUIDE.md` covers SMTP and the queue cron. This plan covers
**automating** the deploy and the shared-hosting constraints.

> The domain is **`petlodge.fun`**. "petlogde.fun" was a typo and was corrected across the
> whole repo on 2026-11-26 (36 occurrences in 10 files). ⚠️ The Hostinger mailbox must
> therefore be **`no-reply@petlodge.fun`** — Hostinger rejects mail whose From header is
> not the authenticated mailbox (`AGENTS.md` §6b), so a mailbox still named with the old
> spelling will silently break every client email.

---

## Prior art — don't invent this, copy it

The user already runs two Laravel deployments to Hostinger. Both were read in full on
2026-11-26; this plan is mostly "do what those do".

| | `skLuan/PersonalLaravelWepPage` (luane.online) | `skLuan/thelamb.studio-web` |
|---|---|---|
| Laravel root | **the domain dir**, `~/domains/luane.online/` | a separate `HOSTINGER_APP_PATH` |
| Docroot strategy | `public_html` → `public` (whole-dir symlink) | `public_html` stays a **real dir**; every entry of `public/` is symlinked into it (`scripts/hostinger/link-docroot.sh`) |
| Why | simplest | Hostinger only allows subdomains *inside* `public_html` (staging lives at `public_html/testing`), so it cannot be a symlink |
| PHP / Node | 8.2 / 20 | **8.3 / 22** |
| Caching | `optimize:clear`, `config:cache`, `view:cache` — **no `route:cache`** | `config:clear` before migrate, `optimize` after |
| `storage:link` | `ln -s ../storage/app/public public/storage` (bash, because PHP's `symlink()` is disabled) | same |
| Environments | single `production` | reusable workflow + `staging`/`production` GitHub Environments |
| Migration helper | `scripts/hostinger/migrate-layout.sh` (+ `rollback-layout.sh`) | — |

**Both confirm the two facts this plan was built on:** Hostinger disables PHP `symlink()`
(and `exec()`), and shell `ln -s` works fine as the workaround.

---

## Decisions

| # | Question | Decision |
|---|---|---|
| 1 | Server PHP | **8.3.** Local Docker moved to `php:8.3-fpm-alpine` on 2026-11-26, so local = CI = production |
| 2 | `public/.htaccess` | **Done** — `!public/.htaccess` in `.gitignore`, file tracked |
| 3 | Document root | **Cannot be changed on Hostinger shared hosting.** Keep `public_html` and link into it |
| 4 | Laravel root | **`~/domains/petlodge.fun/`** (the domain dir), matching luane.online — see below |
| 5 | SSH vs FTP | **SSH** — both existing deployments use it; FTP would rule out `ln -s`, `migrate --force` and the smoke tests |
| 6 | `clear` vs `cache` | **`config:cache` + `view:cache`, never `route:cache` or `optimize`** — see "Caching" |
| 7 | Rollout | Dry-run rsync first, then the migration script's own dry run, then deploy |

### Revisiting decision 4

An earlier draft argued for a `laravel/` subdirectory because `rsync --delete` aimed at
the domain dir could wipe `public_html` and Hostinger's `logs/`. That risk is real, but
**luane.online already solves it with one line** — `--exclude='/public_html'` — and has
been running that way. Consistency with a deployment the user already operates and can
debug beats a second layout, so: **Laravel root = `~/domains/petlodge.fun/`**.

The leading `/` in the exclude patterns matters: `/public_html` anchors to the transfer
root, so it cannot accidentally match something deeper.

**Switch to thelamb's layout instead if** a subdomain is ever needed under `public_html`
(e.g. `staging.petlodge.fun` served from `public_html/staging`). A whole-directory
symlink makes that impossible, which is exactly why thelamb links per entry.

---

## Verified facts (checked 2026-11-26 — re-check before coding)

| Fact | Evidence |
|---|---|
| **`storage:link` is not needed by PetsLodge** | Nothing uses the public disk: `Signature::url()` → `route('signatures.show', …)`, `PdfService::DISK = 'local'`, `.env.production` has `FILESYSTEM_DISK=local`. (Both reference repos *do* need it, and create it with `ln -s`. Copy that if a public disk ever appears.) |
| **`route:cache` will fail** | 3 live closure routes in `routes/web.php` — `/check-in` (:27), `/dashboard` (:31), `/admin/monitoring-dashboard` (:92). Laravel 10's `optimize` runs `config:cache` + `route:cache`, so `optimize` is out too. |
| Hostinger shared hosting cannot change the docroot | Hostinger support docs (they suggest a VPS instead); both reference repos work around it rather than changing it |
| `public/.htaccess` is tracked | `git ls-files public/.htaccess` |
| Local/CI/prod PHP all 8.3 | Dockerfile bumped 2026-11-26; `composer.json` still says `^8.1` with no platform pin |
| Queue = `database` + per-minute cron | `docs/DEPLOYMENT_GUIDE.md` §3 |

> ⚠️ The `storage:link` row is the likeliest to go stale. The moment anything uses
> `Storage::disk('public')` or `asset('storage/…')`, add the `ln -s` line.

---

## Target layout on the server

```
~/domains/petlodge.fun/              ← HOSTINGER_DEPLOY_PATH (Laravel root)
├── app/ bootstrap/ config/ database/ resources/ routes/ vendor/
├── public/                          ← the only web-served directory
├── storage/                         ← PERSISTS between deploys; never synced over
├── artisan
├── .env                             ← server-only, never produced by CI
└── public_html -> public            ← relative symlink; excluded from rsync
```

**`storage/app/signatures/` is legal evidence** (CLAUDE.md §5). It must survive every
deploy and be backed up. Any `rsync --delete` that can reach it is a data-loss bug.

---

## Step 0 — the one-time migration (do this first, by hand)

PetsLodge is currently served **entirely out of `public_html`**, so `.env`, `vendor/` and
`storage/` are reachable from the web. That is the security problem this plan fixes, and
it is exactly what `PersonalLaravelWepPage/scripts/hostinger/migrate-layout.sh` was
written for:

```
Before:  ~/domains/petlodge.fun/public_html/{app,vendor,.env,public,...}
After:   ~/domains/petlodge.fun/{app,vendor,.env,public,...}
         ~/domains/petlodge.fun/public_html -> public
```

Port that script into `scripts/hostinger/migrate-layout.sh` here, changing only
`DOMAIN_DIR` (default `$HOME/domains/petlodge.fun`). Keep its behaviour as-is — it already
does the right things:

- refuses to run if `public_html` is already a symlink, or if `artisan` isn't there
- checks for name clashes **before** moving anything
- backs up to `~/backups/public_html-<stamp>.tar.gz` first
- `artisan down` → move → symlink → recreate caches → `artisan up`
- **dry run by default**; `--apply` to commit
- `rollback-layout.sh` undoes it

Run the dry run, read the plan it prints, then `--apply`. Verify before touching CI:

```bash
readlink ~/domains/petlodge.fun/public_html   # → public
curl -sI https://petlodge.fun/ | head -1       # → 200
curl -s -o /dev/null -w '%{http_code}\n' https://petlodge.fun/.env   # → 403 or 404
```

---

## Blocker 1 — assets must be built in CI

`public/build` is gitignored, so a git checkout never carries compiled assets, and the
shared host has no Node. Build in the workflow and rsync the result. Same for `vendor/`
(`--no-dev`), so the server never runs Composer.

## Blocker 2 — `.htaccess` ✅ DONE

`.gitignore` keeps the blanket `**/.htaccess` guard plus `!public/.htaccess`, and the file
is tracked.

> Note both reference repos **exclude** `.htaccess` from rsync and manage it on the server
> (thelamb's `link-docroot.sh` copies it into the docroot with a managed marker). Here the
> docroot *is* `public/` via the symlink, so shipping `public/.htaccess` from the repo is
> simpler and keeps it in version control. Don't also add an rsync `--exclude='.htaccess'`,
> or the file will never arrive.

## Blocker 3 — docroot: `public_html -> public`

Handled by Step 0's migration script; nothing in the deploy touches it. The workflow only
*asserts* it, because hPanel can recreate `public_html` as a real directory during domain
operations:

```bash
[ -L public_html ] || { echo "::error::public_html is not a symlink — site is serving the wrong directory"; exit 1; }
```

---

## The workflow (`.github/workflows/deploy.yml`)

Modelled on thelamb's, which is the more hardened of the two. Highlights worth keeping,
each of which exists because something broke once:

- **SSH key normalisation.** A key copied on Windows carries CRLF and OpenSSH fails with
  `error in libcrypto`. Strip it and validate with `ssh-keygen -y` before connecting.
- **`ssh-keyscan`** into `known_hosts` instead of disabling host checking.
- **Server `.env` preflight** — fail if `APP_KEY` is missing (every session route 500s) or
  `APP_DEBUG=true` (that 500 then shows a stack trace to the public).
- **`config:clear` *before* `migrate`** — the previous deploy's cached config would
  otherwise make `migrate` use stale credentials.
- **`trap 'artisan up' EXIT`** so a failed step never leaves the site in maintenance mode.
- **`HOSTINGER_PHP` variable** for when `php` over SSH isn't the hPanel version
  (e.g. `/opt/alt/php83/usr/bin/php`).
- **`vars.` not `secrets.`** for `SITE_URL` / non-secret paths — stored as a secret,
  `vars.X` silently reads empty and the deploy half-works.

```yaml
name: Deploy to Hostinger

on:
  push:
    branches: [main]
  workflow_dispatch:

concurrency:
  group: production-deploy
  cancel-in-progress: false

permissions:
  contents: read

env:
  PHP_VERSION: '8.3'     # matches Hostinger and local Docker
  NODE_VERSION: '22'

jobs:
  test:
    uses: ./.github/workflows/tests.yml     # see "Tests as a gate"

  deploy:
    needs: test
    runs-on: ubuntu-latest
    environment:
      name: production
      url: https://petlodge.fun
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: pdo_mysql, zip, intl, gd, bcmath
          coverage: none
      - run: composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

      - uses: actions/setup-node@v4
        with: { node-version: '22', cache: npm }
      - run: npm ci --legacy-peer-deps --no-audit --no-fund && npm run build

      - name: Guard against shipping a broken release
        run: |
          test -s public/.htaccess           || { echo "::error::public/.htaccess missing"; exit 1; }
          test -f public/build/manifest.json || { echo "::error::vite manifest missing"; exit 1; }
          test -f vendor/autoload.php        || { echo "::error::vendor missing"; exit 1; }
          test ! -e public/hot               || { echo "::error::public/hot would disable all assets"; exit 1; }

      - name: Prepare SSH key
        env:
          SSH_KEY: ${{ secrets.HOSTINGER_SSH_KEY }}
          SSH_HOST: ${{ secrets.HOSTINGER_SSH_HOST }}
          SSH_PORT: ${{ secrets.HOSTINGER_SSH_PORT }}
        run: |
          mkdir -p ~/.ssh
          printf '%s\n' "$SSH_KEY" | tr -d '\r' > ~/.ssh/deploy_key
          chmod 600 ~/.ssh/deploy_key
          ssh-keygen -y -P '' -f ~/.ssh/deploy_key > /dev/null \
            || { echo "::error::HOSTINGER_SSH_KEY is not a valid passphrase-less OpenSSH private key"; exit 1; }
          ssh-keyscan -p "$SSH_PORT" "$SSH_HOST" >> ~/.ssh/known_hosts

      - name: Upload files (rsync)
        run: |
          rsync -az --delete --no-perms --omit-dir-times --itemize-changes \
            -e "ssh -i ~/.ssh/deploy_key -o IdentitiesOnly=yes -o BatchMode=yes -p ${{ secrets.HOSTINGER_SSH_PORT }}" \
            --exclude='/public_html' \
            --exclude='.git/' --exclude='.github/' --exclude='node_modules/' \
            --exclude='tests/' --exclude='docker/' --exclude='docker-compose.yml' \
            --exclude='Dockerfile*' --exclude='ai/' --exclude='docs/' --exclude='*.md' \
            --exclude='/.env' --exclude='/.env.*' \
            --exclude='/storage/' --exclude='/bootstrap/cache/*.php' \
            --exclude='/public/storage' --exclude='/public/hot' \
            --exclude='error_log' --exclude='.well-known/' \
            ./ "${{ secrets.HOSTINGER_SSH_USER }}@${{ secrets.HOSTINGER_SSH_HOST }}:${{ secrets.HOSTINGER_DEPLOY_PATH }}/"

      - name: Migrate and rebuild caches
        run: |
          ssh -i ~/.ssh/deploy_key -o IdentitiesOnly=yes -o BatchMode=yes -p "${{ secrets.HOSTINGER_SSH_PORT }}" \
            "${{ secrets.HOSTINGER_SSH_USER }}@${{ secrets.HOSTINGER_SSH_HOST }}" \
            "DEPLOY_PATH='${{ secrets.HOSTINGER_DEPLOY_PATH }}' PHP='${{ vars.HOSTINGER_PHP || 'php' }}' bash -s" <<'REMOTE'
          set -euo pipefail
          cd "$DEPLOY_PATH"
          [ -f artisan ] || { echo "::error::artisan not found in DEPLOY_PATH"; exit 1; }
          [ -L public_html ] || { echo "::error::public_html is not a symlink"; exit 1; }
          [ -f .env ] || { echo "::error::no .env on the server"; exit 1; }
          grep -Eq '^APP_KEY=["'"'"']?base64:' .env || { echo "::error::APP_KEY is empty on the server"; exit 1; }
          grep -Eiq '^APP_DEBUG=["'"'"']?(true|1|on|yes)' .env && { echo "::error::APP_DEBUG=true in production"; exit 1; }
          mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
          $PHP -v | head -1
          $PHP artisan config:clear          # stale cache would poison migrate
          $PHP artisan down --retry=15 || true
          trap '$PHP artisan up' EXIT
          $PHP artisan migrate --force
          $PHP artisan optimize:clear
          $PHP artisan config:cache
          $PHP artisan view:cache
          # NO route:cache / optimize — closure routes in web.php cannot serialize.
          $PHP artisan queue:restart
          REMOTE

      - name: Smoke test
        run: |
          code=$(curl -s -o /dev/null -w '%{http_code}' -L https://petlodge.fun/)
          echo "https://petlodge.fun/ -> $code"; [ "$code" = "200" ]
          # Nothing outside public/ may be reachable.
          leak=0
          for p in /.env /artisan /composer.json /composer.lock /vendor/autoload.php \
                   /storage/logs/laravel.log /storage/app/signatures/ /public/index.php; do
            c=$(curl -s -o /dev/null -w '%{http_code}' "https://petlodge.fun$p")
            echo "$p -> $c"
            [ "$c" = "200" ] && { echo "::error::$p is publicly reachable"; leak=1; }
          done
          exit $leak

      - name: Clean up SSH key
        if: always()
        run: rm -f ~/.ssh/deploy_key
```

### Caching — why not `optimize`

`routes/web.php` defines 3 routes as closures, and a closure cannot be serialised, so
`route:cache` fails. Laravel 10's `optimize` runs `config:cache` **and** `route:cache`
(`Foundation/Console/OptimizeCommand.php`), so `optimize` is out as well. Use
`config:cache` + `view:cache`, which is what luane.online already does.

To unlock `optimize` later, move those 3 closures into controller actions.

> Whichever is used, the rule holds: **any `.env` edit on the server requires re-running
> the cache commands**, or the old values keep winning. That is the trap
> `docs/DEPLOYMENT_GUIDE.md` already warns about.

### Tests as a gate

Both reference repos run tests before deploying. PetsLodge cannot yet: 4 tests are
known-red (`AGENTS.md` §4), so the gate would never pass. Either fix those first (Plan 05
owns the two `CheckInSubmissionTest` cases) or start with `deploy.yml` having no `needs:
test` and add it later. Don't paper over it with `continue-on-error`.

---

## Secrets and variables

**Secrets** (Settings → Secrets and variables → Actions → Secrets):

| Secret | Notes |
|---|---|
| `HOSTINGER_SSH_HOST` | |
| `HOSTINGER_SSH_PORT` | usually not 22 on shared plans |
| `HOSTINGER_SSH_USER` | e.g. `u189079842` |
| `HOSTINGER_SSH_KEY` | private half of a **deploy-only** passphrase-less OpenSSH key; public half in hPanel → SSH Access |
| `HOSTINGER_DEPLOY_PATH` | `/home/u189079842/domains/petlodge.fun` |

**Variables** (same screen → Variables, *not* Secrets):

| Variable | Notes |
|---|---|
| `HOSTINGER_PHP` | optional; set if `php` over SSH isn't 8.3 (e.g. `/opt/alt/php83/usr/bin/php`) |

> thelamb's workflow carries an explicit warning about this split: a value stored as a
> secret reads **empty** through `vars.`, and the deploy fails in a confusing way.

---

## Execution order

1. ~~PHP version~~ → **8.3** everywhere (done locally 2026-11-26). Optionally pin
   `config.platform.php` to `8.3` in `composer.json`.
2. ~~`.htaccess`~~ → **done**.
3. Confirm the Hostinger mailbox is `no-reply@petlodge.fun` (see the note at the top).
4. Port `migrate-layout.sh` + `rollback-layout.sh` from `PersonalLaravelWepPage`, adjust
   `DOMAIN_DIR`, run the **dry run**, read it, then `--apply`. Verify with the three
   commands in Step 0.
5. Create the GitHub secrets/variables above.
6. Add the workflow with `--dry-run` appended to the rsync switches. Read the
   `--itemize-changes` output — confirm nothing under `storage/`, `public_html/` or the
   domain's `logs/` is listed for deletion.
7. Remove `--dry-run`, deploy, walk the acceptance criteria.
8. Repoint the queue cron (`docs/DEPLOYMENT_GUIDE.md` §3) at the new Laravel root — it
   currently ends in `public_html`, which after Step 0 is a symlink to `public/`, so
   `artisan` is no longer there.

---

## Acceptance criteria

- [ ] A push to `main` produces a working site with **no manual step**.
- [ ] `https://petlodge.fun/` → 200; a deep route (`/check-in`) → 200, not 404.
- [ ] The leak loop passes: `/.env`, `/vendor/autoload.php`, `/storage/logs/laravel.log`,
      `/storage/app/signatures/` all non-200. **This is the security fix.**
- [ ] Console clean; assets load from `/build/assets/…`; no `public/hot` on the server.
- [ ] A drop-in produces a PDF and a signature, each readable only via its authenticated route.
- [ ] `storage/app/signatures/` still holds every pre-migration file (and the Step 0 backup exists).
- [ ] `readlink public_html` → `public`.
- [ ] `php artisan migrate:status` all run; `jobs` table present.
- [ ] A queued email arrives within ~2 minutes of the cron tick, From `no-reply@petlodge.fun`.
- [ ] Two consecutive deploys are safe.

---

## Out of scope

- **Staging.** thelamb's reusable-workflow + Environments pattern is the model when it is
  wanted; note it needs the per-entry `link-docroot.sh` layout, not the whole-dir symlink.
- Zero-downtime atomic releases — `artisan down/up` with the `trap` is the compromise.
- Automated DB backup before `migrate --force`. **Strongly recommended** before the first
  deploy carrying a destructive migration; own plan.
- Fixing the 4 known-red tests so the test gate can be enabled (Plan 05).
- Moving the 3 closure routes into controllers to unlock `optimize`.

---

## Execution notes — 2026-11-27 (`feature/08-hostinger-deploy`)

**Done in the repo (code complete; stays in `active/` until the server side is done):**

- `scripts/hostinger/migrate-layout.sh` / `rollback-layout.sh` — ported verbatim from
  `PersonalLaravelWepPage`, with these deviations:
  - `DOMAIN_DIR` default `$HOME/domains/petlodge.fun`, `SITE_URL` overridable.
  - **No `public/storage` link** (PetsLodge does not use the public disk — "Verified facts").
  - Clash check accounts for `.htaccess` being renamed to `.htaccess.public_html-old`
    (the original would have missed a clash there).
  - Dry run prints the signature file count, so it can be compared after `--apply`.
  - Smoke loop also hits `/check-in`, `/vendor/autoload.php`, `/storage/logs/laravel.log`.
  - Rollback warns that signatures/PDFs written after the migration live in the new
    `storage/`, not in the backup.
- `.github/workflows/deploy.yml` — the plan's YAML, with these deviations:
  - **Safety switches instead of a hand-edited `--dry-run`:** `workflow_dispatch` has a
    `dry_run` input that **defaults to true** (rsync `--dry-run`, migrate + smoke skipped).
    Pushes to `main` deploy **only if repo variable `DEPLOY_ON_PUSH == 'true'`**, so merging
    this branch cannot deploy to an unmigrated server. Execution-order steps 6/7 become
    "run manually with dry_run on, then off, then set `DEPLOY_ON_PUSH=true`".
  - **No `needs: test`** (no `tests.yml` exists; 4 known-red tests).
  - New "Check required secrets" step (also rejects a `DEPLOY_PATH` ending in `public_html`).
  - New "Assert server layout" step **before** rsync: refuses to `--delete` into a dir
    without the `public_html` symlink, `artisan`, `storage/` and `.env`. The plan only
    asserted it after the upload — too late.
  - Secrets passed through `env:` rather than inlined `${{ }}` in shell.
  - Extra excludes: `/logs/`, `/.htaccess.public_html-old`, `.user.ini`,
    `/docker-entrypoint.sh`; anchored the root-level ones.
  - `APP_DEBUG` check rewritten as `if …; then` instead of `grep && {…}` — equivalent
    under `set -e`, but unambiguous to read.
  - Smoke test also requires `/check-in` → 200 (acceptance criterion).
- `docs/DEPLOYMENT_GUIDE.md` §3 cron line repointed to the domain dir (step 8).

**Not verified:** the workflow has never run (no secrets, no migrated server). YAML was
written by hand; no `actionlint` available locally.
