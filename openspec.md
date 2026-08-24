# OpenSpec — petslodge-check-in

**Version:** 1.0.0

OpenSpec is a lightweight, file-based convention for recording changes to a project as structured Markdown. No tooling is required to read or author these files — they're plain Markdown — but the `openspec` CLI can validate and manage them once installed.

## Project

**petslodge-check-in** is a pet-lodging (boarding) management system for pet
hotels. Front-desk staff check pets in through a multi-step form, manage detailed
pet profiles (health, feeding, medication), track services (grooming, inventory),
and print/PDF a check-in summary. Its signature feature is a **cookie-based form
data flow**: the entire check-in form's state lives in a browser cookie
(`pl_checkin_data`) that acts as the single source of truth, with the UI updating
automatically when that data changes.

- **Runtime:** Laravel 10 (PHP 8.1+) monolith; Blade + vanilla JS frontend.
- **Database:** MySQL 8.0 (relational); Redis 7 for cache, session, and queue.
- **Key deps:** `laravel/framework@^10.10`, `laravel/breeze@^1.29`, `laravel/sanctum@^3.3`,
  `dompdf/dompdf@^3.1`, `guzzlehttp/guzzle@^7.2`, `alpinejs@^3.4`, `tailwindcss@^3.1`,
  `vite@^5.0`, `iconify-icon@^3.0`, `axios@^1.6`.
- **Entry points:** `public/index.php` (Laravel bootstrap) → route `/` redirects to `/check-in`;
  frontend bundle root `resources/js/app.js` built with Vite.
- **Architecture:** MVC Laravel backend (controllers → services → Eloquent models) serving
  Blade views plus a modular vanilla-JS "managers + reactivity system" frontend
  (`resources/js/cookies-and-form/`). A JSON API (`routes/api.php`) handles check-in
  submission, autosave, per-step saves, session updates, and user lookup.
- **Deployment:** Docker Compose (`docker-compose.yml`) orchestrating three services —
  `app` (Laravel via `php artisan serve`, host port **8080** → container 8000),
  `mysql` (3306), and `redis` (6379) — on a shared bridge network.

### App structure

The application is a single Laravel app whose functionality spans these modules.

- **Check-In** — The signature multi-step flow (owner info → pet info → pet health →
  check-in data → extra info/services). All form state is held in the `pl_checkin_data`
  cookie as the single source of truth, with per-step API submission
  (`/api/checkin/step1..step5`) and a one-shot submit path (`/api/checkin/submit`).
- **Pet Profile Management** — Detailed pet records: name, kind/breed, gender, castrated,
  color, weight, birth date, health conditions, and warnings; linked to an owner.
- **Owner (User) Management** — Owner contact info (name, phone, email, full address)
  and one-or-many emergency contacts; users are looked up by phone number.
- **Services & Inventory** — Grooming/extra services (with optional appointment day via
  a pivot table), feeding and medication schedules that can select **multiple times of
  day** (`MomentOfDay`), and per-pet inventory items.
- **Editing Mode** — Edit an existing check-in with explicit edit-vs-create tracking,
  an original-data snapshot, change detection, and detailed change summaries.
- **Drop-In (staff)** — Staff-only flow: look a user up by phone, confirm their active
  check-in, then generate a PDF (`PdfService`/dompdf) and send it to a physical printer
  via `PrintNodeService`.
- **Pet Staff Dashboard** — Lists currently `CHECKED_IN` and `DROPPED_IN` pets and
  exposes actions: drop-in, check-out, cancel, and re-print (re-uses stored `document_url`).
- **PDF & Printing** — Renders `pdf-for-print.blade.php` to a PDF via dompdf, stores it on
  the public disk, and dispatches print jobs through the PrintNode REST API (Guzzle).
- **Monitoring & Health** — `/health` and `/health/report` JSON endpoints (database,
  cache, filesystem, and data-integrity checks) plus an admin monitoring dashboard.

**Check-in status lifecycle** (seeded `Status` records):
`CHECKED_IN → PRINTED → DROPPED_IN → CHECKED_OUT`, with `CANCELLED` as a terminal branch.

### Authentication

Built on **Laravel Breeze** (server-rendered session auth) with **Sanctum** token support.

- **Local credentials:** Email/password registration and login with hashed passwords,
  plus password reset, email verification, and a profile (edit info / change password /
  delete account) flow.
- **Roles:** `SUPER_ADMIN` and `PET_STAFF` (seeded; `ADMIN` also honored by the admin gate).
- **Route guards:** `admin.only` middleware (`AdminOnly`) gates `/dashboard` and the
  monitoring dashboard; `pet.staff.only` middleware (`PetStaffOnly`) gates the drop-in and
  pet-staff dashboard routes. The public check-in flow is accessible without auth.
- **API auth:** Sanctum-protected `/api/user`; the check-in API endpoints currently run
  unauthenticated for the kiosk-style front-desk flow.

## Directory layout

```text
openspec.md                                  ← this file (project manifest)
openspec/
  changes/                                   ← one Markdown file per change (empty after v1.0.0 consolidation)
README.md                                    ← setup, features, troubleshooting
AGENTS.md                                    ← agent guide / project map
DOCKER_README.md                             ← Docker details
DEPLOYMENT_LOG.md                            ← deployment history
CHECKIN_COOKIE_REFACTOR_README.md            ← cookie-refactor rationale & history
docker-compose.yml                           ← services: app (8080) + mysql (3306) + redis (6379)
Dockerfile                                   ← app image (PHP/Laravel)
Dockerfile.nginx                             ← nginx image
docker-entrypoint.sh                         ← container bootstrap
docker/                                      ← nginx + php config used by images
composer.json / package.json                 ← PHP & JS dependencies
vite.config.js / tailwind.config.js          ← frontend build & styling config
.env.example                                 ← environment template
app/
  Http/
    Controllers/
      Auth/                                  ← Breeze: register, login, verify email, password reset/update
      CheckInApiController.php               ← API: submit, autosave, step1..5, check-user, update-session
      CheckInFormController.php              ← Web: new / pre-filled / view / edit / delete check-in
      CheckInController.php                  ← Check-in web controller
      DropInController.php                   ← Drop-in flow + PDF/PrintNode handoff (readyToPrint)
      PetStaffDashboardController.php        ← Staff dashboard: drop-in, checkout, cancel, reprint
      HealthCheckController.php              ← /health + /health/report monitoring endpoints
      ProfileController.php                  ← user profile edit/update/delete
    Middleware/
      AdminOnly.php                          ← admin.only gate (role ADMIN)
      PetStaffOnly.php                       ← pet.staff.only gate (PET_STAFF / SUPER_ADMIN)
      Authenticate.php, VerifyCsrfToken.php, EncryptCookies.php, ...  ← standard Laravel middleware
    Requests/                                ← form requests (LoginRequest, ProfileUpdateRequest)
  Models/                                    ← Eloquent models
    User.php, Pet.php, CheckIn.php, EmergencyContact.php,
    Food.php, Medicine.php, Item.php, ExtraService.php,
    KindOfPet.php, Gender.php, Castrated.php, MomentOfDay.php, Status.php
  Services/
    CheckInTransformer.php                   ← DB ⇄ cookie format (null-safe, lossless)
    CheckInDataValidator.php                 ← validates check-in structure & required fields
    CheckInService.php                       ← core logic: create/update check-in, inventory, extra services
    CheckInPetService.php                    ← pet operations
    CheckInUserService.php                   ← owner/user operations
    PdfService.php                           ← dompdf PDF generation (pdf-for-print view)
    PrintNodeService.php                     ← PrintNode REST client (sendPrintJob, getPrinters)
  Providers/, Console/, Exceptions/, View/   ← Laravel plumbing + Blade view components
resources/
  css/app.css                                ← Tailwind entry
  js/
    app.js, bootstrap.js                     ← bundle entry points (Axios, Alpine)
    Pill.js, tabbar.js, Utils.js             ← UI widgets & utilities
    components/
      CheckInHandler.js                      ← check-in orchestration
      datePicker.js                          ← date picker
    config/
      checkInConfig.js                       ← front-end check-in config
    cookies-and-form/                        ← heart of the app (feature work lives here)
      FormDataManager.js                     ← main public API for form data
      CookieManager.js / CookieHandler.js    ← read/write pl_checkin_data
      config.js                              ← cookie/form configuration
      form-processor.js                      ← normalizes/merges session data into cookie
      managers/                              ← one concern each:
        EditingModeManager, CoreDataManager, PetManager, PetPillManager,
        HealthFormManager, InventoryManager, InventoryFormManager, FormHandler,
        NavigationManager, ValidationManager, SubmissionManager, PopupManager,
        CheckInSummaryUpdater, FastCheckinManager, UtilitiesManager, index.js
      reactivitySystem/                      ← auto-updates UI on cookie change (MutationObserver):
        CookieReactivityManager, UIManager, FormUpdater, SummaryRenderer, index.js
  views/
    checkIn.blade.php                        ← main check-in page (route /check-in)
    Process.blade.php                        ← multi-step form host (data-* attributes → cookie)
    view-check-in.blade.php                  ← view a check-in
    pdf-for-print.blade.php                  ← PDF / print template
    Drop-in.blade.php, Drop-in-confirmation.blade.php, Drop-in/check.blade.php
    pet-staff/dashboard.blade.php            ← staff dashboard
    admin/monitoring-dashboard.blade.php     ← admin monitoring view
    dashboard.blade.php, welcome.blade.php
    components/
      forms/                                 ← pet-info, health-info, food-medication, inventory, owner-info
      pop-ups/                               ← feeding-medication, grooming, terms-conditions
      progress/                              ← bar, circle
      tabbar.blade.php, CheckInSummary.blade.php, + Breeze UI kit components
    layouts/                                 ← app, guest, navigation
    auth/                                    ← Breeze auth pages
    profile/                                 ← edit + partials (info, password, delete)
routes/
  web.php                                    ← pages + staff/admin routes
  api.php                                    ← check-in + drop-in JSON API
  auth.php                                   ← Breeze auth routes
  channels.php, console.php                  ← broadcasting + console
database/
  migrations/                                ← schema (users, pets, check_ins, foods, medicines,
                                             ←   items, extra_services, pivots, statuses, etc.)
  seeders/                                   ← User, Status, Gender, Castrated, KindOfPet,
                                             ←   MomentOfDay, EmergencyContact, Pet, CheckIn,
                                             ←   ExtraService, Food, Medicine, Item, Database
  factories/UserFactory.php
config/                                      ← Laravel config (app, auth, database, cache, services, ...)
tests/
  Feature/                                   ← Auth tests, CheckInSubmissionTest, ProfileTest
  Unit/                                      ← unit tests
docs/                                        ← DATA_FLOW, DEVELOPER_GUIDE, API_REFERENCE,
                                             ←   DEPLOYMENT_GUIDE, MIGRATION_GUIDE
scripts/                                     ← monitor-data-flow.js, pre-deployment-check.sh,
                                             ←   post-deployment-verify.sh, rollback.sh
```

## Version history

### v1.0.0 — Consolidated release

Initial consolidated spec version combining all previously tracked changes:

1. **Initial Architecture** — Laravel 10 + MySQL monolith with Breeze auth; multi-step
   pet check-in flow with owner, pet, health, and inventory data.
2. **Cookie-Based Data Flow** — Unified `pl_checkin_data` cookie as the single source of
   truth, lossless `CheckInTransformer` between DB and cookie formats, and a
   MutationObserver-driven reactivity system for automatic UI updates.
3. **Editing Mode & Change Detection** — Explicit edit-vs-create tracking with an original
   data snapshot, change detection, and detailed change summaries for editing existing check-ins.
4. **Multiple Feeding/Medication Times** — Select multiple times of day (`MomentOfDay`)
   for feeding and medication in a single action.
5. **Services & Inventory Tracking** — Grooming/extra services with appointment day (pivot),
   feeding and medication schedules, and per-pet inventory items.
6. **PDF Generation & Printing** — dompdf-based check-in summary PDF plus PrintNode REST
   integration for physical printing; stored `document_url` for re-prints.
7. **Staff Operations** — Pet-staff dashboard (drop-in, check-out, cancel, reprint) and a
   phone-lookup drop-in confirmation flow.
8. **Monitoring & Health** — `/health` and `/health/report` endpoints, admin monitoring
   dashboard, and pre/post-deployment verification + rollback scripts.
9. **Docker Deployment** — Containerized app, MySQL, and Redis via Docker Compose for
   reproducible local and production deployments.



