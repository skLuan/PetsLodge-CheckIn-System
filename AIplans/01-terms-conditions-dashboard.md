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
