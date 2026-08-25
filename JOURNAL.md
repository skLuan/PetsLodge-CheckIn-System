# PetsLodge — Agent Journal

> Running log of AI-agent work sessions. **Append, don't rewrite.** Newest session at the top.
> Purpose: give the next session (human or AI) context on what was done, what worked,
> what didn't, and what was deliberately left alone.
>
> Companion files: `AGENTS.md` (project map), `AIplans/00-MASTER-PLAN-v1.0-beta.md` (roadmap).

---

## Session 2026-08-24 — Plan 02 (cont.): Hostinger SMTP + Docker/queue infrastructure

**Branch:** `vbeta.1`
**Plan:** `AIplans/02-transactional-emails.md` (steps 4–5: queue config + production SMTP)
**Status:** infrastructure complete and verified against mailpit. ❗ Still no send
through Hostinger — the mailbox password was not available this session.

### Decision that reshaped the plan: production is Hostinger SHARED HOSTING

Confirmed with the user. This invalidates the plan's queue design:

| Plan assumed | Reality on shared hosting |
|---|---|
| `QUEUE_CONNECTION=redis` in prod | **No Redis.** Must be `database`. |
| A supervisor/Docker worker daemon | **No long-lived processes.** Must be a per-minute cron. |

Local Docker still uses Redis + a worker container; only production differs.

### Production-breaking bugs found (would have meant ZERO emails in prod)

1. **The `jobs` table did not exist.** Only `create_failed_jobs_table` was ever
   migrated, yet `.env.production` already said `QUEUE_CONNECTION=database`. Every
   queued email would have died with `Base table or view not found: 'jobs'`.
   Added `2026_08_24_100000_create_jobs_table.php` (rollback verified both ways).
2. **`.env.production` was a Laravel 11 env file in a Laravel 10 app.** It used
   `MAIL_SCHEME`, `CACHE_STORE`, `BROADCAST_CONNECTION` — all silently ignored here.
   It also had `MAIL_MAILER=log` (mail written to disk, delivered to nobody),
   `APP_ENV=local` and `APP_DEBUG=true` in production (a stack trace would render
   the live DB credentials in the browser). Rewritten for Laravel 10 + Hostinger.
3. **The queue container could never survive.** `docker-compose.yml` paired
   `restart: no` with `--max-time=3600`. That flag makes the worker exit *on
   purpose* every hour; with no restart policy the first clean exit ends mail
   delivery permanently. Found it already dead (`Exited (137)`). Now
   `restart: unless-stopped`.

### The trap that cost the most time: `artisan serve` discards your environment

Laravel 10's `ServeCommand::$passthroughVariables` whitelists ~13 variables
(`APP_ENV`, `PATH`, `XDEBUG_*`, …) and **drops everything else** before spawning the
HTTP server. So `APP_KEY`, `DB_*`, `MAIL_*` set under `environment:` in
`docker-compose.yml` reach the CLI and the queue worker but **never a web request**.

The old compose was quietly relying on the `.env` baked into the image; its
`environment:` block was decorative for HTTP. Had Hostinger credentials been put
there, the worker would have sent mail and the web app would not — the worst kind
of split-brain bug to debug.

**Fix:** one source of truth. `.env.docker` is now mounted read-only over
`/var/www/.env`, and **no** `APP_/DB_/MAIL_` variables are exported from compose.
Verified `app` and `queue` resolve identical mail config.

### ⚠️ I wiped the development database — read this before running tests

`php artisan test` inside the container **truncated the dev `petslodge` database.**
`.env.testing` (created last session, gitignored) no longer exists, `phpunit.xml`
named no database, so `RefreshDatabase` ran against the live dev DB. The previous
session's journal warned about exactly this; I ran the suite without checking.

Lost: ~3 dev users, their pets and all existing check-in rows. Recovered by
re-seeding (lookup tables, statuses, terms and admin users are all back); the
ad-hoc check-in test data is gone and must be re-created by hand through the form.
**No production data was involved.**

**Root cause fixed so it cannot recur** — `phpunit.xml` now pins
`<env name="DB_DATABASE" value="petslodge_testing" force="true"/>`, and the
`petslodge_testing` database was created. `force="true"` is essential *and was not
sufficient on its own*: a real environment variable arrives via `$_SERVER`, which
Laravel's env repository reads before PHPUnit's override, so compose's
`DB_DATABASE=petslodge` still won. Removing the DB_* exports from compose (see
above) is what actually made the override stick. Verified: dev DB now survives a
full suite run, and `petslodge_testing` holds the 21 tables.

### What was built

| Area | File |
|---|---|
| Queue table for prod | `database/migrations/2026_08_24_100000_create_jobs_table.php` |
| Diagnostics | `app/Console/Commands/MailTest.php` — `php artisan mail:test` |
| Compose | `docker-compose.yml` — one-source-of-truth env, restart policies, bind mount, hot entrypoint |
| Entrypoint | `docker-entrypoint.sh` — `SKIP_MIGRATIONS` guard |
| Test isolation | `phpunit.xml` — pinned test database |
| Env | `.env`, `.env.docker`, `.env.example`, `.env.production` — documented MAIL_*/LODGE_* blocks |
| Docs | `docs/DEPLOYMENT_GUIDE.md` §§1,3,4,5 rewritten for shared hosting |

`php artisan mail:test [address] [--template=confirmation|drop-in|drop-out]` prints
the *effective* config (not what `.env` claims — those differ whenever config is
cached), warns about the specific Hostinger mistakes (port/encryption mismatch,
`MAIL_FROM_ADDRESS` ≠ `MAIL_USERNAME`, empty password, localhost `APP_URL`), and
sends **synchronously** so SMTP errors surface instead of being swallowed by the
app's deliberate try/catch. Note `PendingMail::sendNow()` does not exist in
Laravel 10 — the immediate path is `$mailable->to(...)->send(app(Mailer::class))`.

### Other fixes made along the way

- **Resolved the `routes/web.php` merge conflict** (`UU`, left over from a stash).
  It was a hard blocker — conflict markers are a PHP parse error. The stashed side
  redirected `/pet-staff` → `/pet-staff/dashboard`, a URL that does not exist (the
  real prefix is `/petstaff`, no hyphen — route *names* keep the hyphen). Resolved
  so both spellings land on `/petstaff/dashboard`; verified 302 for each.
- **Migration race between containers.** `app` and `queue` share an entrypoint and
  start together, so both ran `migrate` simultaneously and the loser died on
  "table already exists". Added `SKIP_MIGRATIONS=true` for the queue container.
- **Bind mount added** (`.:/var/www`). Code was previously baked into the image, so
  every template tweak needed a full rebuild. The entrypoint is now run from the
  mount too (`entrypoint: ["sh", "/var/www/docker-entrypoint.sh"]`) so edits to it
  apply on `restart`. `.env.docker` needed its empty `APP_KEY` filled — the bind
  mount shadows the image's generated one, which 500s every request.

### Verification performed

- **Full suite: 62 passed / 4 failed** — identical to the documented baseline, the
  same 4 pre-existing failures, no new ones.
- **Queued path end-to-end:** dispatched a real `PetDroppedIn`; worker log shows
  `SendDropInNotification RUNNING → DONE` then `DropInMail RUNNING → DONE`, and the
  message landed in mailpit — correct subject, recipient, and `no-reply@petlogde.fun`
  sender. Re-verified *after* the config refactor.
- **All 3 templates** sent via `mail:test --template=…`; confirmation body checked
  for pet name, check-in ID, date and footer. Blank `LODGE_*` fields are correctly
  omitted rather than rendered empty.
- **Migration reversible:** rollback dropped `jobs`, re-migrate recreated it.
- Endpoints after the routes fix: `/check-in` 200, `/new-form` 200,
  `/pet-staff` and `/petstaff` both 302 → `/petstaff/dashboard`.

### ❗ Left for a human

1. **The Hostinger send has still never happened.** Put the mailbox password into
   `MAIL_PASSWORD` in the server's `.env` (`.env.production` is otherwise complete)
   and run `php artisan mail:test you@petlogde.fun`.
2. **Create the `no-reply@petlogde.fun` mailbox** in hPanel if it does not exist.
3. **Add the cron job** — the exact line is in `docs/DEPLOYMENT_GUIDE.md` §3.
   Confirm the PHP binary path first (`ls /usr/bin/php*`); Hostinger sometimes
   needs a versioned path.
4. **Run `php artisan migrate --force` on production** to create `jobs`.
5. **Verify SPF/DKIM** for petlogde.fun (`dig` commands in §1) or Gmail spam-files it.
6. **`LODGE_ADDRESS` / `LODGE_PHONE` / `LODGE_EMAIL` are still empty.** Emails ship
   with a name-only footer until filled. Nothing invented was put in them.
7. **Security: `.env.v0` is staged for commit and contains a live
   `PRINTNODE_API_KEY` and an `APP_KEY`.** It was added to `.gitignore` during this
   session, but that does not untrack an already-staged file. Unstage it
   (`git rm --cached .env.v0`) before committing, or the key lands in history.
8. Nothing was committed. Plan 02 is **not** archived to `plans/` — it stays in
   `AIplans/` until a real Hostinger message is delivered.

---

## Session 2026-08-04 (2) — Plan 02: Transactional emails (check-in / drop-in / drop-out)

**Branch:** `feature/02-transactional-emails` (branched off `feature/01-terms-dashboard`,
**not** `develop` — Plan 01 is still unmerged and its `UserFactory` fix is required by
these tests).
**Plan:** `AIplans/02-transactional-emails.md`
**Status:** ✅ code complete, tests green, verified end-to-end over real SMTP.
❗ **One item open:** no email has been sent through *Hostinger* yet — credentials
were not provided before the session ended. See "Not verified" below.

### The finding that changed the plan

The plan said to hang the confirmation email off `CheckInApiController::submitCheckIn`
(`/api/checkin/submit`). **That endpoint is dead code.** It calls
`CheckInService::submitCheckIn()`, a method that **does not exist** on the service —
every call throws `BadMethodCallException`, is swallowed by the controller's try/catch,
and returns 500.

> This also **corrects the previous session's journal entry**, which guessed the two
> red `CheckInSubmissionTest` cases were failing due to unseeded lookup tables. They
> aren't — it's the missing method. Verified in `storage/logs/laravel.log`:
> `Call to undefined method App\Services\CheckInService::submitCheckIn()`.
> Real fix belongs to Plan 05; left alone here (scope).

The **live** submission path is `SubmissionManager.submitSequentialCheckIn()` →
step1…step5. So the confirmation fires at the **end of step 5** (`submitExtraInfo`).

### The multi-pet problem (and the agreed solution)

Steps 2–5 run **inside a per-pet loop**. A 3-pet booking therefore creates 3 `check_ins`
rows and would fire 3 confirmation emails — contradicting the plan's "exactly once".

Decision (confirmed with the user): **one email per submission, listing every pet.**

Implementation — a coalescing queued listener:
1. New column `check_ins.confirmation_sent_at` (nullable timestamp).
2. `SendCheckInConfirmation` runs on a **120-second delay** (`withDelay()`), by which
   point every pet's row exists.
3. It claims each un-announced check-in for that owner **row by row** with a
   conditional `UPDATE ... WHERE confirmation_sent_at IS NULL`, so under concurrency
   each row lands in exactly one email.
4. Sibling jobs find nothing to claim and exit silently.
5. A 30-minute window stops it sweeping up an unrelated older booking whose email failed.

`CheckInConfirmationMail` therefore takes a **`Collection` of check-ins**, not one.

### What was built

| Area | Files |
|---|---|
| Events | `app/Events/CheckInCompleted.php`, `PetDroppedIn.php`, `PetDroppedOut.php` |
| Listeners (all `ShouldQueue`, tries=3, backoff=30) | `app/Listeners/SendCheckInConfirmation.php`, `SendDropInNotification.php`, `SendDropOutNotification.php` |
| Mailables (all `ShouldQueue`) | `app/Mail/CheckInConfirmationMail.php`, `DropInMail.php`, `DropOutMail.php` |
| Templates | `resources/views/emails/layouts/base.blade.php` + `check-in-confirmation`, `drop-in`, `drop-out` |
| Migration | `2026_08_04_120000_add_confirmation_sent_at_to_check_ins_table.php` |
| Config | `config/lodge.php` (footer contact details) |
| Wiring | `EventServiceProvider` map; dispatches in `CheckInApiController::submitExtraInfo` and `PetStaffDashboardController::dropped_in` / `checkout` |
| Infra | `docker-compose.yml`: new `queue` **and** `mailpit` services |

Every dispatch is wrapped in try/catch + `Log::warning` — a dead SMTP server can never
500 a check-in or a checkout. Listeners skip (and log) owners with missing/malformed
email rather than erroring.

### Traps worth remembering

1. **`docker-compose.yml` had no mailpit service** even though `.env` pointed
   `MAIL_HOST=mailpit`. Local mail could never have worked. Added it (UI :8025).
2. **There was no queue worker.** `QUEUE_CONNECTION=redis` was already set on the `app`
   service, so queued mail would have silently piled up in Redis forever. Added a
   `queue` service — **without it, no email is ever delivered.**
3. **No booking date range exists in the DB.** `check_ins.check_in` is the *creation*
   timestamp and `check_out` is only set at pickup — there is no stored departure date.
   The confirmation email therefore shows "Checked in: <date>", **not** a date range.
   If a real arrival/departure range is wanted, that's a schema change (future plan).
4. **`dropped_in()` sets `check_out = now()`** — same as `checkout()`. That looks wrong
   (a pet *arriving* shouldn't get a check-out time) but it is pre-existing behaviour;
   left alone deliberately. Candidate for Plan 05.
5. **No lodge contact details exist anywhere in the codebase.** Rather than invent an
   address/phone, `config/lodge.php` reads them from `.env` and the footer **omits any
   blank line**, so nothing fake can reach a client. The user must fill `LODGE_*` in.
6. Pint rewrites `new Foo()` → `new Foo` and `'a' . 'b'` → `'a'.'b'`; it touched
   pre-existing lines in `PetStaffDashboardController::reprint`. Expected, not a bug.

### Verification performed

- **Tests:** `--filter=TransactionalEmailTest` → **20 passed**. Full suite →
  **62 passed / 4 failed** — the same 4 pre-existing failures, **no new ones**
  (baseline was 42 passed / 4 failed; 42 + 20 = 62).
- **Migration reversible:** `migrate:rollback --step=1 --env=testing` then re-migrate,
  both clean.
- **Real SMTP end-to-end** (not just `Mail::fake`): ran mailpit in Docker, dispatched
  the real events against the testing DB with a genuine SMTP transport.
  A **3-pet booking + drop-in + drop-out produced exactly 3 messages** —
  `Booking confirmed for Luna, Rocky & Milo`, `Luna has arrived safely`,
  `Thank you for visiting — Luna`. Confirmation body contained all 3 pets, the
  grooming appointment day, and the footer address/phone. `confirmation_sent_at`
  left 0 rows unannounced.
- **Rendered previews** (open in a browser) in this session's scratchpad:
  `confirmation-single.html`, `confirmation-multi.html`, `drop-in.html`, `drop-out.html`.

### Not verified / left for a human

- ❗ **No mail has gone through Hostinger.** Needs a real mailbox + password in the
  production `.env`, then the send test in
  `docs/DEPLOYMENT_GUIDE.md → Transactional Email & Queue Setup §4`.
  Also still to confirm on the real domain: **SPF/DKIM** (`dig TXT`) and that
  **`APP_URL` is the public https URL** — the header logo is an `asset()` link and
  breaks in every client if `APP_URL` is still `http://localhost`.
- `LODGE_ADDRESS` / `LODGE_PHONE` / `LODGE_EMAIL` / `LODGE_WEBSITE` are **empty**;
  until filled, emails ship with a footer containing only the lodge name.
- Emails were not opened in Gmail/Outlook/Apple Mail — rendering checked in a browser
  and in mailpit only.
- Nothing committed; changes sit on `feature/02-transactional-emails`.
- Notion card for Phase 2 still needs a human.

### Environment notes (differs again from last session)

- Docker Desktop was **down at session start**, then the user started a standalone
  `mysql_general` container (`mysql:latest`, 3306). The project's compose stack was
  **never** running — app/artisan ran on the Windows host (PHP 8.1.10 CLI).
- `.env.testing` → `petslodge_testing` still works and is still what protects the dev DB.
- `php artisan test` errors with `Access denied for user 'root'@'172.19.0.1'` if the
  MySQL container isn't up yet. Start MySQL first.

---

## Session 2026-08-04 — Plan 01: Terms & Conditions DB-backed + dashboard editor

**Branch:** `feature/01-terms-dashboard`
**Plan:** `AIplans/01-terms-conditions-dashboard.md`
**Status:** ✅ complete — all 6 plan steps + acceptance criteria met. Not committed (waiting on review).

### Environment findings (important for future sessions)

| Finding | Detail |
|---|---|
| Docker is **not** running | `docker compose ps` returns nothing. The app is served locally (`php artisan serve` at `http://127.0.0.1:8000`), not at the `:8080` documented in `AGENTS.md`. Artisan/composer run **directly on the Windows host** (PHP 8.1.10 CLI), not via `docker compose exec`. |
| MySQL is local | `127.0.0.1:3306`, user `root`, DB `petslodge`. Reachable from the host. |
| No SQLite extension | `php -m` has `pdo_mysql` only — the commented-out sqlite lines in `phpunit.xml` cannot be used. |
| Tests would have wiped the dev DB | `phpunit.xml` sets no DB, so `RefreshDatabase` targeted the **live `petslodge` DB**. Created a dedicated `petslodge_testing` database + `.env.testing` (gitignored) pointing at it. Laravel auto-loads `.env.testing` when `APP_ENV=testing`. |

### Baseline test suite (before any Plan 01 work)

First run: **23 failed / 5 passed**. Root cause: `UserFactory` didn't supply `phone`,
`address`, `role`, all of which are `NOT NULL` in `create_users_table` (`phone` is also
unique). Every test that called `User::factory()` died on insert.

**Fixed** `database/factories/UserFactory.php` — added the three columns plus
`petStaff()` / `superAdmin()` states (needed for this plan's auth-matrix tests).
Result: **4 failed / 24 passed**.

The remaining 4 failures are **pre-existing and unrelated to Plan 01**. Left alone
deliberately (scope discipline) — logged here so nobody re-discovers them:

| Test | Failure | Likely cause / owner |
|---|---|---|
| `ExampleTest > the application returns a successful response` | expects 200 on `/`, gets 302 | Stale Breeze stub — `/` now redirects to `/check-in`. Trivial fix, belongs to Plan 06. |
| `Auth\RegistrationTest > new users can register` | user not authenticated after POST | Registration now requires the extra user fields; the test posts the old payload. |
| `CheckInSubmissionTest > it can submit complete checkin data` | 500 | Test uses `RefreshDatabase` **without seeding** the lookup tables (`genders`, `kind_of_pets`, `statuses`, `moment_of_days`), so `'dog'`/`'male'` lookups fail. Belongs to Plan 05. |
| `CheckInSubmissionTest > it updates existing user and creates checkin` | 500 | Same cause. |

> ⚠️ The master plan's Step-1 gate says "`CheckInSubmissionTest` still green (baseline)".
> It is **not** green and was not green before this session. The usable gate is
> "no *new* failures", which is what's being enforced instead.

### What was done

#### Step 0 — setup ✅
- Branch `feature/01-terms-dashboard` created off `develop`.
- `.env.testing` + `petslodge_testing` DB created; `.env.testing` added to `.gitignore`.
- `UserFactory` repaired (see above) + `petStaff()` / `superAdmin()` states added.

#### Step 1 — tests first ✅
`tests/Feature/TermsAndConditionsTest.php`, 18 tests written before any implementation.
Confirmed red for the right reasons (missing seeder/model/route classes, not syntax errors).

#### Step 2 — migration + model + seeder ✅
`terms_and_conditions` table (append-only versioning), `TermsAndConditions` model with
`active()` scope and `publishNewVersion()`, and `TermsAndConditionsSeeder` holding the
legal text verbatim. Rollback verified by CLI (`migrate:rollback --step=1 --env=testing`
then re-migrate — both clean); a rollback *test* fights `RefreshDatabase`, so it was
deliberately left as a CLI check.

#### Step 3 — controller + routes ✅
`TermsAndConditionsController` (`edit` / `update` / `show`). Routes added inside the
existing `['auth','pet.staff.only']` group; public `GET /api/terms/active`.

#### Step 4 — view composer + popup swap ✅
`AppServiceProvider` composes `$activeTerms` into `components.pop-ups.terms-conditions`.
143 lines of hardcoded HTML in the popup replaced by `{!! $activeTerms->content ?? "" !!}`.

#### Step 5 — dashboard button + editor view ✅
"Edit Terms & Conditions" button on the staff dashboard; `pet-staff/terms-edit.blade.php`
with an HTML textarea, Alpine live preview, and a "Version N, last updated by X" banner.

#### Step 6 — docs + archive ✅
`docs/API_REFERENCE.md` gained an "HTTP Endpoints" section; `AGENTS.md` updated;
plan moved to `plans/01-terms-conditions-dashboard.md` with full Execution notes;
master plan Phase 1 marked done.

### Traps this session hit (worth remembering)

1. **`/check-in` is NOT the check-in form.** It's just the phone-entry page. The real
   multi-step form is `Process.blade.php`, served at `/new-form`,
   `/new-form-pre-filled` and `/edit-check-in/{id}` — that's where the T&C popup lives
   (`<x-pop-ups.terms-conditions />`). The plan said to test the popup on `/check-in`;
   that would never have worked.
2. **Staff URL prefix is `/petstaff/` (no hyphen) but route names are `pet-staff.*`
   (with hyphen).** The plan text said `/pet-staff/terms`; the codebase says otherwise.
   Followed the codebase. Always use `route()`, never a literal URL.
3. **`PetStaffOnly` redirects to `/`, it does not `abort(403)`.** Any test asserting 403
   on a staff route will fail.
4. **`PetStaffDashboardController::index()` explodes without seeded statuses** —
   `Status::where('name','CHECKED_IN')->first()->id` on `null`. Any test hitting the
   dashboard must `seed(StatusSeeder::class)` first. (The missing null-guard is a real
   fragility; left alone as out of scope — candidate for Plan 05.)
5. **`strip_tags` alone is not XSS-safe** — it removes `<script>` tags but leaves the
   JavaScript behind as visible text. Script/style/iframe/object/embed blocks are removed
   whole *before* `strip_tags` runs.
6. **`plans/` is gitignored.** A plain `mv` into it makes the archived plan invisible to
   git. Used `git mv` instead, which keeps the file tracked despite the ignore rule —
   do the same for plans 02-06. `JOURNAL.md` (tracked, unignored) is the durable record
   either way.

### Verification performed

- **Tests:** `php artisan test --filter=TermsAndConditions` → **18 passed**.
  Full suite → **42 passed, 4 failed** (the same 4 pre-existing failures; no new ones).
- **Legal text integrity:** whitespace-insensitive diff of the pre-migration blade HTML
  (from `git show HEAD:...`) against the seeded DB content → **identical, 7219 chars each**.
- **Live app** (`http://127.0.0.1:8000`): `/new-form` popup renders all 7 legal headings
  from the DB, no console errors. `GET /api/terms/active` → `{version:1, title:"Terms &
  Conditions", content: 8868 chars}`.
- **Dev DB migrated + seeded** (`petslodge`): `terms_and_conditions` v1 active, 8874 chars.

### Not verified / left for a human

- `/petstaff/terms` was **not** opened in a browser — it needs a staff login and this
  session does not enter passwords. Feature tests cover 200 / version banner / dashboard
  link, but the **Alpine live preview pane was not visually confirmed**. Worth an eyeball.
- Nothing was committed. Changes sit on `feature/01-terms-dashboard`.
- Notion card for Phase 1 still needs a human to mark it done.

### Environment gotcha for the next session

If `AGENTS.md` tells you to run `docker compose exec app php artisan ...` — check
`docker compose ps` first. In this session Docker was down and everything ran on the
Windows host with the local PHP 8.1 CLI and local MySQL.

