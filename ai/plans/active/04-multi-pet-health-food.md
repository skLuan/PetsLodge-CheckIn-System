# Plan 04 — Multi-pet health warnings + food ("Revisar warnings para multi pet" + "multi mascota con la comida")

**Phase 5 · Branch: `feature/04-multi-pet-health-food`**

## Problem

The check-in form supports multiple pets (pet pills UI: e.g. "Masimo" + "Max"), but **health info and food are single-instance**:

- `resources/views/components/forms/health-info.blade.php` has ONE `unusualHealthBehavior` radio, ONE `healthBehaviorDetails`, ONE `warnings` textarea — whatever is typed applies ambiguously to "the check-in", not to a specific pet.
- Same pattern for food in `resources/views/components/forms/food-medication.blade.php`.

Users can't tell which pet a warning/food entry belongs to. Either scope the forms per-pet, or make the current scope explicit.

## Current state (verify before coding — read these first)

- `resources/js/cookies-and-form/managers/PetManager.js`, `PetPillManager.js` — pet add/select/remove; pills likely carry an active-pet index.
- `HealthFormManager.js` — how health fields are read/written to the cookie (`FormDataManager`).
- Cookie `pl_checkin_data` schema — find where pets array vs health/food live (`resources/js/cookies-and-form/config.js`, `FormDataManager.js`).
- Backend: `CheckInApiController::submitPetHealth` (step3) — what shape it expects; `pets` / `medicines` / `food` tables.

**Deliverable of this reading step: a short "Current data flow" section appended to this plan BEFORE implementation** (agents: do this, it prevents breaking the cookie reactivity system).

## Target UX (decide with team — two options)

**Option A — per-pet forms (recommended, matches Notion intent):**
The health step and food step render scoped to the **currently selected pet pill**. Switching pills swaps the form contents (already the pattern used for pet-info). Each pet's data stored under `pets[i].health = {unusualBehavior, details, warnings}` and `pets[i].food = {...}` in the cookie.

**Option B — minimal:** keep single fields but label them clearly ("applies to all pets") and add a per-pet free-text prefix. Only if A turns out to be too invasive for the cookie system. Document choice in Execution notes.

## Implementation (Option A)

### 1. Cookie schema migration (frontend)

- Move `health`/`food` keys under each pet object in `FormDataManager`.
- Backward compatibility: on cookie load, if legacy top-level `health` exists, copy it into pet[0] and delete (one-shot migrator in `CookieManager` load path). Old in-flight cookies must not crash the form.

### 2. UI

- `health-info.blade.php` + `food-medication.blade.php`: bind to active pet. Add a visible header "Health info for: <pet name>" so staff always knows the scope.
- `PetPillManager`: on pill switch, `HealthFormManager`/food manager re-render from the selected pet's data (mirror whatever pet-info does today).
- Summary (`SummaryRenderer.js`, `CheckInSummary.blade.php`): show warnings/food grouped per pet.

### 3. Backend

- `submitPetHealth` (step3): accept per-pet payload `{pets: [{pet_id|index, unusual_behavior, details, warnings}]}`. Keep accepting the old flat shape during transition (map to first pet) OR bump the endpoint contract and update the JS in the same PR — prefer the latter since we control the only client.
- **Verified:** `pets` table already has a per-pet `warnings` text column (and `Pet` model fillable includes it). So the DB is correct — the bug is that the **frontend collapses all pets into one form**, and `submitPetHealth` presumably writes the same values to each (verify). No new migration expected; fix is cookie/UI scoping + endpoint payload shape.

### 4. Tests FIRST

- `tests/Feature/CheckInSubmissionTest.php` — extend: submit step3 with 2 pets, each gets its own warnings/food rows; single-pet path unchanged.
- JS: no test harness exists — minimum: a manual QA script section below; stretch: add Vitest for `FormDataManager` pet-scoping logic.

### Manual QA script (must pass before merge)

1. Add pet A (cat) → fill warnings "food aggressive" + food.
2. Add pet B (dog) → its health form is EMPTY; fill different warnings.
3. Switch pills A↔B repeatedly → data persists per pet, no bleed-over.
4. Reload page mid-form → cookie restores both pets' data correctly.
5. Submit → DB rows correct per pet; summary + printed PDF show per-pet sections.
6. Legacy cookie (crafted with old schema) → loads without JS errors.

## Acceptance criteria

- [ ] Warnings, unusual-behavior and food are unambiguously per-pet in UI, cookie, API and DB.
- [ ] Pill switching swaps form state with no data loss.
- [ ] Old cookies don't break the form.
- [ ] Feature tests + manual QA script pass.
