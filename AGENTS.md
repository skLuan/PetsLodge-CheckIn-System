# AGENTS.md — Guide for AI Coding Agents

> **Read this file first, then `JOURNAL.md`.** This file is the shortcut map of the
> PetsLodge project so you (an AI coding agent) don't have to re-read the whole codebase
> every session; `JOURNAL.md` is the running log of what previous sessions actually did.
> When something here goes stale, update it as part of your change — and append your
> session to `JOURNAL.md` before you finish.

This file is written for two audiences:
- **The AI agent** doing the work — file map, commands, conventions, gotchas.
- **The non-coder** directing the agent — plain-language explanations of where things live.

---

## 1. What this project is (in one paragraph)

**PetsLodge** is a pet-lodging (boarding) management system. Front-desk staff **check
pets in** through a multi-step form, manage **pet profiles** (health, feeding,
medication), track **services** (grooming, inventory), and **print/PDF** a check-in
summary. The signature piece of the app is a **cookie-based form data flow**: the whole
check-in form's state lives in a browser cookie (`pl_checkin_data`) that acts as the
single source of truth, with automatic UI updates when the data changes.

---

## 2. Tech stack

| Layer        | Technology                                                        |
|--------------|-------------------------------------------------------------------|
| Backend      | **Laravel 10**, PHP 8.1+                                           |
| Database     | **MySQL 8.0** (via Docker); Redis 7 for cache/session/queue       |
| Auth         | Laravel Breeze + Sanctum                                           |
| Frontend     | **Vite**, **Tailwind CSS**, **Alpine.js**, vanilla JS, Iconify    |
| PDF / Print  | dompdf, PrintNode integration                                     |
| Testing      | PHPUnit, Faker, Mockery                                            |
| Formatting   | Laravel Pint (PHP)                                                 |
| Container    | Docker + docker-compose                                            |

---

## 3. How to run it (Docker is the primary way)

The project runs in Docker. `docker-compose.yml` defines five services: `app`
(Laravel on port **8080** → container 8000), `queue` (the mail worker), `mysql` (3306),
`redis` (6379), and `mailpit` (catches local email — UI on **http://localhost:8025**).

> ⚠️ Client emails are **queued**. If the `queue` service isn't running, no email is
> ever delivered — the job just sits in Redis. `docker compose logs -f queue` to check.

```bash
# Start everything (app, MySQL, Redis)
docker compose up -d

# App is served at:  http://localhost:8080
# Landing route "/" redirects to /check-in

# View logs
docker compose logs -f app

# Run an artisan command inside the container
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed

# Stop everything
docker compose down
```

**Frontend assets** are built with Vite. During active UI work you usually want:
```bash
npm install          # first time only
npm run dev          # hot-reload dev server (Vite)
npm run build        # production build
```

**Non-Docker (local) fallback** (see README for full steps): `composer install`,
`npm install`, copy `.env.example` → `.env`, `php artisan key:generate`,
`php artisan migrate --seed`, then `php artisan serve` + `npm run dev`.

---

## 4. How to test & format

> ⚠️ **Tests need a separate database.** They use `RefreshDatabase`, which drops every
> table on the configured connection. `phpunit.xml` sets no DB (the sqlite lines are
> commented out and the sqlite PHP extension isn't installed), so tests fall back to
> whatever `.env` points at — i.e. they would **wipe the dev database**.
> A gitignored `.env.testing` pointing at a `petslodge_testing` schema is what keeps that
> from happening; Laravel loads it automatically because `phpunit.xml` sets `APP_ENV=testing`.
> If you clone fresh, recreate both:
> `CREATE DATABASE petslodge_testing;` + copy `.env` → `.env.testing` and change `DB_DATABASE`.

```bash
# PHP tests
docker compose exec app php artisan test        # or: php artisan test
php artisan test --filter=SomeTest              # single test

# Format PHP code (do this before finishing a PHP change)
./vendor/bin/pint

# JS: there is no configured JS test runner. Verify JS changes in the browser.
```

> ⚠️ There is **no automated JS test suite**. For frontend changes, verify manually
> in the browser (see the debug helpers in §8) and check the browser console for errors.

**Known-red tests (pre-existing, not your change):** `ExampleTest`, `Auth\RegistrationTest
> new users can register`, and both `CheckInSubmissionTest` cases. The practical gate is
"**no new failures**", not "all green". Current baseline: **62 passed / 4 failed**.

> The two `CheckInSubmissionTest` failures are caused by `CheckInApiController::submitCheckIn`
> calling `CheckInService::submitCheckIn()`, **a method that does not exist** — so
> `/api/checkin/submit` always returns 500. (Confirmed 2026-08-04; an earlier guess that
> blamed missing seed data was wrong.) Fixing it belongs to Plan 05.

---

## 5. Project map — where things live

### Backend (`app/`)
| Path | What it does |
|------|--------------|
| `app/Http/Controllers/CheckInApiController.php` | **API endpoints** for the check-in form (submit, autosave, per-step submission, session update). Frontend talks to this. |
| `app/Http/Controllers/CheckInFormController.php` | Renders form pages (new / pre-filled / view / **edit** / delete check-in). |
| `app/Http/Controllers/CheckInController.php`     | Check-in web controller. |
| `app/Http/Controllers/DropInController.php`      | Drop-in flow (staff-only). |
| `app/Http/Controllers/PetStaffDashboardController.php` | Staff dashboard: checkout, cancel, reprint. |
| `app/Http/Controllers/TermsAndConditionsController.php` | T&C editor for pet staff (`/petstaff/terms`) + public `GET /api/terms/active`. |
| `app/Http/Controllers/HealthCheckController.php` | `/health` monitoring endpoints. |
| `app/Services/CheckInTransformer.php`   | **Converts** between DB format ⇄ cookie/form format (null-safe). |
| `app/Services/CheckInDataValidator.php` | Validates check-in data structure & required fields. |
| `app/Services/CheckInService.php`       | Core check-in business logic. |
| `app/Services/CheckInPetService.php`    | Pet-related operations. |
| `app/Services/CheckInUserService.php`   | User/owner operations. |
| `app/Services/PdfService.php` / `PrintNodeService.php` | PDF generation & physical printing. |
| `app/Models/` | Eloquent models: `CheckIn`, `Pet`, `EmergencyContact`, `Food`, `Medicine`, `Item`, `ExtraService`, `KindOfPet`, `Gender`, `Castrated`, `MomentOfDay`, `Status`, `TermsAndConditions`, `User`. |
| `app/Providers/AppServiceProvider.php` | View composer that injects `$activeTerms` into the T&C popup. |
| `app/Providers/EventServiceProvider.php` | Maps the check-in/drop-in/drop-out events to their queued mail listeners. |
| `app/Http/Middleware/AdminOnly.php`, `PetStaffOnly.php` | Role gates (`admin.only`, `pet.staff.only`). |
| `app/Events/` | `CheckInCompleted`, `PetDroppedIn`, `PetDroppedOut` — each carries a `CheckIn`. |
| `app/Listeners/` | `SendCheckInConfirmation` (coalesces multi-pet bookings into one email), `SendDropInNotification`, `SendDropOutNotification`. All `ShouldQueue`. |
| `app/Mail/` | `CheckInConfirmationMail` (takes a **Collection** of check-ins), `DropInMail`, `DropOutMail`. |

### Frontend JS (`resources/js/`)
The heart of the app. **`cookies-and-form/` is where most feature work happens.**

| Path | What it does |
|------|--------------|
| `cookies-and-form/FormDataManager.js` | **Main public API** for form data. Start here for form-state work. |
| `cookies-and-form/CookieManager.js` / `CookieHandler.js` | Read/write the `pl_checkin_data` cookie. |
| `cookies-and-form/config.js` | Cookie/form configuration. |
| `cookies-and-form/form-processor.js` | Processes/normalizes form data. |
| `cookies-and-form/managers/` | Specialized managers (one concern each) — see below. |
| `cookies-and-form/reactivitySystem/` | Auto-updates the UI when cookie data changes. |
| `components/CheckInHandler.js`, `datePicker.js` | Check-in orchestration & date picker. |
| `config/checkInConfig.js` | Check-in front-end config. |
| `Pill.js`, `tabbar.js`, `Utils.js`, `app.js`, `bootstrap.js` | UI widgets, tab bar, utilities, entry points. |

**`managers/`** (each handles one slice of the form):
`EditingModeManager` (edit vs. create + original snapshot), `CoreDataManager`,
`PetManager` / `PetPillManager`, `HealthFormManager`, `InventoryManager` /
`InventoryFormManager`, `FormHandler`, `NavigationManager`, `ValidationManager`,
`SubmissionManager`, `PopupManager`, `CheckInSummaryUpdater`, `FastCheckinManager`,
`UtilitiesManager`, `index.js` (barrel export).

**`reactivitySystem/`**:
`CookieReactivityManager` (watches cookie via MutationObserver — no aggressive polling),
`UIManager` (updates DOM without wiping user input), `FormUpdater` (form field updates),
`SummaryRenderer`, `index.js`.

### Views (`resources/views/`) — Blade templates
| Path | What it does |
|------|--------------|
| `checkIn.blade.php` | Main check-in page (route `/check-in`). |
| `view-check-in.blade.php`, `Process.blade.php` | View / process a check-in. |
| `components/forms/` | Form partials: `pet-info`, `health-info`, `food-medication`, `inventory`, `owner-info`. |
| `components/steps/` | One component per wizard step: `owner-info`, `pet-info`, `feeding-medication`, `health-info`, `inventory`, `grooming`, `review-submit`. |
| `components/ui/` | Shared form/visual primitives: `input`, `select`, `radio`, `checkbox`, `date-picker`, `field`, `card`. |
| `components/wizard/` | Sticky wizard header (progress bar + pet pills). |
| `components/pop-ups/` | Modals: `feeding-medication`, `grooming`, `terms-conditions`. |
| `components/progress/` | Progress bar / circle. |
| `components/tabbar.blade.php`, `CheckInSummary.blade.php` | Tab bar, summary. |
| `pet-staff/dashboard.blade.php`, `Drop-in*.blade.php` | Staff dashboard, drop-in pages. |
| `pet-staff/terms-edit.blade.php` | T&C editor (HTML textarea + Alpine live preview). |
| `emails/layouts/base.blade.php` | Shared branded email shell (inline CSS + tables — email clients ignore external CSS). |
| `emails/check-in-confirmation.blade.php`, `drop-in.blade.php`, `drop-out.blade.php` | The three client emails. |
| `pdf-for-print.blade.php` | The PDF/print template. |
| `admin/monitoring-dashboard.blade.php` | Admin monitoring view. |
| `layouts/`, `auth/`, `profile/` | Layouts, Breeze auth pages, profile pages. |

### Routes
- `routes/web.php` — pages: `/check-in` (phone entry), `/new-form` (the actual multi-step
  form), `/edit-check-in/{id}`, `/view-check-in`, `/drop-in`, `/petstaff/dashboard`,
  `/petstaff/terms`, `/health`, admin monitoring.
- `routes/api.php` — form API: `/api/checkin/submit`, `/api/checkin/autosave`,
  per-step `/api/checkin/step1..step5/...`, `/api/update-session-checkin`, `/api/check-user`,
  `/api/terms/active`.

> ⚠️ Staff URLs are `/petstaff/*` (no hyphen) while their **route names** are
> `pet-staff.*` (with hyphen). Easy to get wrong — always use `route()`.
- `routes/auth.php` — Breeze auth routes.

### Other
- `database/migrations/`, `database/seeders/` — schema & seed data.
- `docs/` — deeper developer docs (see §9). `plans/`, `AIplans/` — planning notes.
- `config/`, `.env` (local secrets), `.env.example` (template).

---

## 6. The core concept you must understand: cookie-based data flow

The check-in form does **not** keep state in scattered variables. Instead:

```
Database → Laravel Session → DOM data-* attributes → Cookie (pl_checkin_data) → Form UI
```

Rules that keep this from breaking:
1. **The cookie is the single source of truth.** All form state lives in `pl_checkin_data`.
2. **Session data is merged into the cookie once** at init, in a specific order.
3. **Editing mode is explicit** — a dedicated flag tracks edit-vs-create, and an
   *original data snapshot* is stored so changes can be diffed and reset.
4. **Transformations are lossless & null-safe** (see `CheckInTransformer`).
5. **UI reacts automatically** to cookie changes via `CookieReactivityManager`
   (MutationObserver) — don't add polling loops.
6. **Order matters.** Editing-mode init and cookie init have race conditions if you
   reorder async steps. Preserve existing async/await ordering unless you fully
   understand it.

If you touch form state, go through **`FormDataManager`** rather than writing cookies
directly.

---

## 6b. The second concept: transactional emails

Three emails go to clients, all fired as **events → queued listeners → mailables**:

| Trigger | Fired from | Event → Listener → Mail |
|---|---|---|
| Check-in finished | `CheckInApiController::submitExtraInfo` (**step 5**) | `CheckInCompleted` → `SendCheckInConfirmation` → `CheckInConfirmationMail` |
| Pet arrived | `PetStaffDashboardController::dropped_in` | `PetDroppedIn` → `SendDropInNotification` → `DropInMail` |
| Pet picked up | `PetStaffDashboardController::checkout` | `PetDroppedOut` → `SendDropOutNotification` → `DropOutMail` |

Rules that keep this from breaking:

1. **Steps 2–5 run once per pet** (`SubmissionManager.submitSequentialCheckIn`), so a
   3-pet booking fires `CheckInCompleted` three times. `SendCheckInConfirmation` runs on
   a **~2-minute delay**, then claims every un-announced check-in for that owner
   (`check_ins.confirmation_sent_at IS NULL`) and sends **one** email listing them all.
   Sibling jobs find nothing left to claim and exit. Don't "fix" the delay away.
2. **Never let mail break the flow.** Every dispatch is wrapped in try/catch +
   `Log::warning`. A dead SMTP server must not 500 a check-in or a checkout.
3. **Invalid/missing owner email is a skip, not an error** — guarded in each listener.
4. **Inline CSS + tables only** in `resources/views/emails/`. Email clients discard
   `<style>` blocks and external stylesheets.
5. **`/api/checkin/submit` is dead code** — it calls `CheckInService::submitCheckIn()`,
   which does not exist, so it always 500s. Don't wire anything new to it; the live
   path is step1→step5.

---

## 7. Conventions & rules for agents

- **Match the surrounding style.** This is vanilla JS with ES modules and class-style
  "manager" objects; Blade + Tailwind for markup; standard Laravel for PHP.
- **One manager = one concern.** New form behavior usually belongs in a new/existing
  file under `cookies-and-form/managers/`, exported via `managers/index.js`.
- **Don't bypass the cookie flow.** Use `FormDataManager` / the managers.
- **Run Pint** on PHP changes; **check the browser console** on JS changes.
- **Don't reorder the init/async sequence** in the reactivity & editing-mode code
  without strong reason — it's race-condition sensitive.
- **Git:** work on a feature branch, not directly on `develop`. Commit only when asked.
- **Secrets:** never commit `.env`. Use `.env.example` as the template.
- **Windows host:** the repo lives on Windows; the app runs in Linux containers. Prefer
  running artisan/composer **inside** the container (`docker compose exec app ...`).
- **Scope discipline:** make the change requested; if you spot unrelated issues, note
  them rather than silently expanding the change.

---

## 8. Debugging helpers (run in the browser console)

```javascript
FormDataManager.debugCheckinData();   // dump all check-in data + cookie size
FormDataManager.isEditingMode();      // are we editing an existing check-in?
FormDataManager.hasDataChanged();     // has the user changed anything?
FormDataManager.getChangeSummary();   // what changed
FormDataManager.resetToOriginal();    // revert to original snapshot (edit mode)
CookieReactivityManager.triggerCheck(); // force a reactivity/UI refresh
```

Common symptoms → look here:
- **Form not pre-filled when editing** → `data-session-checkin` attribute valid JSON?
  editing flag set? clear cookies & reload.
- **Changes not detected** → editing mode on? original snapshot stored?
- **UI not updating** → reactivity listeners registered? console errors from
  MutationObserver? try `CookieReactivityManager.triggerCheck()`.
- **Cookie too big** → too many pets/inventory items; check `debugCheckinData()`.

---

## 9. Deeper docs (read only when the task needs them)

| File | When to read it |
|------|-----------------|
| `JOURNAL.md` | **Read at the start of every session.** What previous agent sessions did, what worked, what didn't, and known-broken things deliberately left alone. |
| `AIplans/00-MASTER-PLAN-v1.0-beta.md` | Roadmap to v1.0-beta and the phase order. |
| `README.md` | Full feature list, setup, troubleshooting. |
| `docs/DATA_FLOW.md` | Complete architecture of the cookie data flow. |
| `docs/DEVELOPER_GUIDE.md` | FormDataManager API usage & examples. |
| `docs/API_REFERENCE.md` | Method-level reference. |
| `docs/DEPLOYMENT_GUIDE.md` / `DEPLOYMENT_LOG.md` | Deploy & rollback. |
| `docs/MIGRATION_GUIDE.md` | Upgrading between versions of the system. |
| `DOCKER_README.md` | Docker details. |
| `CHECKIN_COOKIE_REFACTOR_README.md` | History/rationale of the cookie refactor. |
| `plans/` , `AIplans/` | Prior implementation plans & phase reports. |

---

## 10. Quick task recipes

- **Add/change a form field** → edit the relevant `resources/views/components/forms/*.blade.php`
  partial, wire its data through the matching manager in `cookies-and-form/managers/`,
  and make sure it's included in the cookie via `FormDataManager` / transformer.
- **Change what a check-in saves to the DB** → `CheckInApiController` (endpoint) +
  `CheckInService` / `CheckInTransformer` (transform) + model + a migration if the
  schema changes.
- **Add an API endpoint** → `routes/api.php` → method in `CheckInApiController` (or a
  new controller) → service for logic.
- **Change the printed/PDF summary** → `resources/views/pdf-for-print.blade.php` +
  `PdfService`.
- **Add a staff dashboard action** → `routes/web.php` (under `pet.staff.only`) +
  `PetStaffDashboardController` + `resources/views/pet-staff/dashboard.blade.php`.

---

_Keep this file current: when you move, rename, or add a major piece, update the map above._
