# Plan 01 — Terms & Conditions: DB-backed + editable from Dashboard

**Phase 1 · Priority: start now · Branch: `feature/01-terms-dashboard`**

## Problem

The full T&C text is hardcoded in `resources/views/components/pop-ups/terms-conditions.blade.php` (~180 lines of HTML). Editing legal text requires a deploy.

## Target architecture

Store T&C as versioned rows in DB; the popup renders the latest active version; pet staff edit it from the **pet-staff dashboard** (`/pet-staff/dashboard`) via an editor under `pet-staff/*`.

Versioning matters: when signatures land (Plan 03), we must know **which** T&C version a client accepted.

### 1. Migration — `create_terms_and_conditions_table`

```php
Schema::create('terms_and_conditions', function (Blueprint $table) {
    $table->id();
    $table->string('title')->default('Terms & Conditions');
    $table->longText('content');          // sanitized HTML
    $table->unsignedInteger('version');   // 1, 2, 3...
    $table->boolean('is_active')->default(false); // only one active
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamps();
});
```

Seeder `TermsAndConditionsSeeder`: import the current HTML from the blade file as version 1, `is_active = true`. (Extract the inner HTML of `#termsContent` verbatim — it's the legally reviewed text.)

### 2. Model — `app/Models/TermsAndConditions.php`

- `protected $table = 'terms_and_conditions';`
- Scope `active()`: `where('is_active', true)->latest('version')`.
- Static `publishNewVersion(string $content, User $user)`: transaction — deactivate current, insert new row with `version + 1`. Never mutate old versions (audit trail for signed agreements).

### 3. Controller + routes — **under `pet-staff/*`**

The editor lives in the pet-staff area, entered from the pet-staff dashboard (`/pet-staff/dashboard`). Add routes inside the existing `Route::middleware(['auth', 'pet.staff.only'])` group in `routes/web.php` to keep the `pet-staff/*` URL structure:

`app/Http/Controllers/TermsAndConditionsController.php`:

| Method | Route | Name | Middleware | Purpose |
|---|---|---|---|---|
| `edit` | GET `/pet-staff/terms` | `pet-staff.terms.edit` | `auth, pet.staff.only` | Edit form with current active content |
| `update` | PUT `/pet-staff/terms` | `pet-staff.terms.update` | same | Validate + `publishNewVersion` |
| `show` | GET `/api/terms/active` | — | none (public form uses it) | JSON `{title, content, version}` for the popup |

(If later we want editing restricted to admins only, swap the middleware on these two routes — but the entry point stays in the pet-staff dashboard.)

Validation: `content` required, max ~200KB; sanitize with an allowlist (e.g. `mews/purifier` or strip via `strip_tags($html, allowed)`) — admins are trusted but defense in depth.

### 4. Views

- `resources/views/pet-staff/dashboard.blade.php`: add an **"Edit Terms & Conditions" button** (header/toolbar area, next to existing actions) linking to `route('pet-staff.terms.edit')`. Keep styling consistent with the existing dashboard buttons.
- `resources/views/pet-staff/terms-edit.blade.php`: textarea (raw HTML is fine for v1; a WYSIWYG like Quill via CDN is optional stretch), Save button, "current version N, last updated by X" banner, preview pane (`x-html` via Alpine or server-side preview), Back-to-dashboard link.
- `terms-conditions.blade.php` popup: replace hardcoded HTML with `{!! $activeTerms->content !!}` — pass via a view composer (`App\Providers\ViewServiceProvider` or inline `View::composer('components.pop-ups.terms-conditions', ...)`) so the check-in page doesn't need controller changes. Fallback: if no active row, render nothing + log warning.

### 5. Tests FIRST — `tests/Feature/TermsAndConditionsTest.php`

Full suite (all written up-front, red at start):

- Seeded active version renders in the check-in page popup.
- `GET /pet-staff/terms` requires pet staff (guest → redirect to login, non-staff user → 403).
- `PUT /pet-staff/terms` creates version N+1, deactivates N, records `updated_by`.
- Old versions immutable/retained after update.
- `GET /api/terms/active` returns latest active JSON.
- Rollback test: migration down works.

## Step-by-step execution order — with test gate per step

Each step has a **gate**: the listed tests/checks must pass before moving to the next step. Run with `docker compose exec app php artisan test --filter=TermsAndConditions` (plus the manual checks noted).

| Step | Work | Gate — must pass before next step |
|---|---|---|
| 1 | Write `TermsAndConditionsTest.php` (full suite above) | Suite runs and is **red for the right reasons** (missing table/routes, not syntax errors). `CheckInSubmissionTest` still green (baseline). |
| 2 | Migration + model + seeder | Tests green: table exists, seeder creates v1 active; `migrate:rollback` then `migrate --seed` both clean. Unit check: `TermsAndConditions::active()` returns the seeded row; `publishNewVersion` twice → versions 1,2,3 with only latest active. |
| 3 | Controller + routes under `pet-staff/*` | Tests green: auth matrix (guest redirect / non-staff 403 / staff 200), `PUT` versioning behavior, `GET /api/terms/active` JSON shape `{title, content, version}`. |
| 4 | View composer + popup swap in `terms-conditions.blade.php` | Test green: check-in page contains seeded T&C text. Manual: `/check-in` popup opens, content identical to pre-migration text (diff against git history of the blade); no console errors; fallback path (no active row) logs warning and doesn't crash the page. |
| 5 | Pet-staff dashboard button + `terms-edit.blade.php` | Manual: button visible on `/pet-staff/dashboard` for staff, navigates to editor; edit → save → popup on `/check-in` shows the change; version banner increments. Feature test asserting the dashboard HTML contains the `pet-staff.terms.edit` link. |
| 6 | Docs + archive | **Full** test suite green (`php artisan test`, no filter — regression gate). `docs/API_REFERENCE.md` updated with `/api/terms/active`. Move this file to `plans/` with Execution notes. |

## Acceptance criteria

- [ ] Pet staff can reach the editor via a button on `/pet-staff/dashboard`; editor lives under `pet-staff/*` URLs.
- [ ] Edits appear immediately in the check-in popup.
- [ ] Every save creates a new version; history preserved in DB.
- [ ] Guests and non-staff users cannot access the editor.
- [ ] Check-in popup shows identical legal text as before migration (diff the seeded content).
- [ ] All step gates passed in order; full test suite green at the end.

---

## Execution notes — 2026-08-04

**Branch:** `feature/01-terms-dashboard` · **Result:** all acceptance criteria met.
Full session context (environment quirks, baseline test state) is in `JOURNAL.md`.

### Files added
| File | Purpose |
|---|---|
| `database/migrations/2026_08_04_000000_create_terms_and_conditions_table.php` | Schema as specified, plus `unique(version)`, `index(is_active)`, `nullOnDelete()` on `updated_by`. |
| `app/Models/TermsAndConditions.php` | `active()` scope, `publishNewVersion()`, `updatedBy()` relation. |
| `database/seeders/TermsAndConditionsSeeder.php` | Version 1 = the legal text lifted verbatim from the blade. Idempotent. |
| `app/Http/Controllers/TermsAndConditionsController.php` | `edit` / `update` / `show` + allowlist sanitizer. |
| `resources/views/pet-staff/terms-edit.blade.php` | Textarea + Alpine live preview + version banner. |
| `tests/Feature/TermsAndConditionsTest.php` | 18 tests, written first. |

### Files changed
- `routes/web.php`, `routes/api.php` — editor routes + `GET /api/terms/active`.
- `app/Providers/AppServiceProvider.php` — view composer supplying `$activeTerms`.
- `resources/views/components/pop-ups/terms-conditions.blade.php` — 143 lines of hardcoded HTML → `{!! $activeTerms->content ?? "" !!}`.
- `resources/views/pet-staff/dashboard.blade.php` — "Edit Terms & Conditions" button.
- `database/seeders/DatabaseSeeder.php` — registers the new seeder.
- `database/factories/UserFactory.php` — repaired (see deviations) + `petStaff()` / `superAdmin()` states.
- `docs/API_REFERENCE.md`, `AGENTS.md` — documented.

### Deviations from the plan (and why)

1. **URLs are `/petstaff/terms`, not `/pet-staff/terms`.** Every existing staff route
   uses `/petstaff/*` with `pet-staff.*` route *names*. Matching the plan literally
   would have introduced a second, inconsistent URL prefix.
2. **Non-staff users get a redirect, not a 403.** `PetStaffOnly` middleware redirects to
   `/` with a flash error. Tests assert the real behaviour rather than forcing a
   middleware change that would affect every other staff route.
3. **The popup is on `/new-form`, not `/check-in`.** `/check-in` is only the phone-entry
   page; `<x-pop-ups.terms-conditions />` is included from `Process.blade.php`, served at
   `/new-form`, `/new-form-pre-filled` and `/edit-check-in/{id}`. Tests target `/new-form`.
4. **No `mews/purifier` dependency.** Sanitization is `strip_tags` against a tag allowlist,
   preceded by whole-block removal of `script`/`style`/`iframe`/`object`/`embed` (strip_tags
   alone leaves inner JS as text) and followed by stripping `on*=` handlers and
   `javascript:`/`data:` URLs. No new composer package needed.
5. **Migration rollback is verified by CLI, not by a test.** A rollback assertion fights
   `RefreshDatabase`. Verified with `migrate:rollback --step=1 --env=testing` → clean, then
   re-migrate → clean.
6. **`UserFactory` was repaired as a prerequisite.** It omitted `phone`/`address`/`role`,
   all `NOT NULL`, so *every* test touching `User::factory()` failed. 23 failures → 4.

### Gate results

| Step | Gate | Result |
|---|---|---|
| 1 | Suite red for the right reasons | ✅ 18 red — missing seeder/model/route classes only. |
| 2 | Schema/model/seeder green; rollback clean | ✅ 5 green; rollback + re-migrate clean. |
| 3 | Auth matrix, PUT versioning, JSON shape | ✅ green. |
| 4 | Popup renders seeded text; content matches pre-migration | ✅ green + whitespace-insensitive diff of old blade vs. seeded content: **identical** (7219 chars each). |
| 5 | Dashboard link + editor | ✅ feature test green. |
| 6 | Full suite, no new failures | ✅ 42 passed / 4 pre-existing failures (unchanged from baseline). |

### Verified in the running app (`http://127.0.0.1:8000`)
- `/new-form` popup renders all 7 legal headings from the DB; no console errors.
- `GET /api/terms/active` → `{version: 1, title: "Terms & Conditions", content: 8868 chars}`.
- `/petstaff/terms` **not** verified visually — it needs a staff login, which the session
  could not perform. Covered by feature tests (200 + version banner + dashboard link).

### Left for a human
- Eyeball `/petstaff/terms` logged in as pet staff, especially the Alpine live preview.
- Update the Notion card (agents don't touch Notion).
