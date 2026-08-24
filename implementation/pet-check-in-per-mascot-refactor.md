# Implementation — Per-Mascot Check-In Refactor

**Status:** In progress
**Scope:** Frontend (Blade + vanilla JS) and Backend (Laravel) — multi-pet personalization.

Six related changes that turn the multi-step check-in into a per-mascot flow.

---

## 1. Grooming as its own step (optional, per mascot) — Option B (full per-pet)

Move Grooming Options out of the final review step into a dedicated, skippable step,
and record which mascot(s) take each grooming service.

### Backend (store grooming per mascot)
- **Migration** — add nullable `pet_id` FK to `check_in_extra_service` pivot.
- **`app/Models/ExtraService.php`** — `withPivot('grooming_appointment_day', 'pet_id')`.
- **`app/Services/CheckInService.php`** — `processExtraServices()` accepts
  `grooming.pets` (map pet-index → `{bath,nails,grooming}`) and attaches each service
  per pet with the appointment day.
- **`app/Services/CheckInTransformer.php`** — round-trip per-pet grooming between
  cookie format and DB format.
- **`app/Http/Controllers/CheckInApiController.php`** — pass the new grooming structure.

### Frontend (new step + renumbering)
- **`resources/js/cookies-and-form/config.js`** — `GROOMING: 6`, `THANKS: 7`; default
  `grooming.pets: []`.
- **`resources/views/Process.blade.php`** — move grooming UI into new `#step6`, rename
  final review to `#step7`, add a 7th `<x-progress.circle />`.
- **`resources/js/tabbar.js`** — bump the hardcoded `1..6` step loops/bounds to `1..7`.
- **`resources/js/cookies-and-form/managers/NavigationManager.js`,
  `ValidationManager.js`, `SubmissionManager.js`** — add/renumber grooming-step logic.
- Per-mascot selector (checkboxes) stores `grooming.pets` (array of pet indices).
- Optional: "No" option; user may proceed without selecting services.
- Summary/PDF reflects per-mascot grooming.

---

## 2. "Unusual health behavior" moves into the mascot definition (per pet)
- `pet-info.blade.php`: add Yes/No radios (`unusualHealthBehavior`) + "Which?" details
  (`healthBehaviorDetails`).
- `PetManager.js`: route those fields into `pet.health` in `addPetToCheckin`/
  `updatePetInCheckin`.
- `health-info.blade.php`: remove the health-behavior section (keep Warnings).
- `HealthFormManager.js`: persist only `warnings`; add the conditional "Which?" toggle
  for the pet form.
- `ValidationManager.js`: health step persists only `warnings` (+ grooming).
- `FormUpdater.js`: `updatePetForm` populates the new radios/details; `updateHealthInfoForm`
  populates only `warnings`.

---

## 3. Edit icon + behavior on the pet pill (pet-info step only)
- `Pill.js`: add ✏️ edit icon → dispatch `pet:edit-request`.
- `app.css`: `.pill .edit-icon { display:none; }` + `body.pet-info-step … { display:inline-flex; }`.
- `tabbar.js`: toggle `body.pet-info-step` in `showStep` (step === 2).
- `form-processor.js`: `editingPetIndex`; on edit load pet into form; submit updates
  (button becomes "Save Pet").
- `pet-info.blade.php`: give the submit button `id="addPetBtn"`.

---

## 4. Feeding/medication modal — specific mascot vs "same for all"
- `food-medication.blade.php`: remove the page-level `#sameFeedingContainer`.
- `feeding-medication.blade.php`: add "Apply to" pet `<select>` + `#sameFeedingForAll`
  checkbox.
- `PopupManager.js`: populate the select; on submit apply to all (checked) or to the
  selected pet.
- `UIManager.js`: remove `updateSameFeedingCheckbox()` + its call.

---

## 5. Inventory "Amount" field (in front of Item Name)
- `inventory.blade.php`: add a number input `#itemAmount` before `#itemName`.
- `InventoryFormManager.js`: store label as `"<amount> × <name>"` (fallback to name).
- No backend change (inventory stays a list of strings).

---

## 6. stepProgress circles → pointer cursor
- `components/progress/circle.blade.php`: add `cursor-pointer`.

---

## Verification
- Rebuild Vite assets + Docker image; clear Blade view cache.
- Browser: grooming step is optional and per-mascot; per-mascot health/edit; feeding
  modal target; inventory amount; pointer cursor on progress circles.
