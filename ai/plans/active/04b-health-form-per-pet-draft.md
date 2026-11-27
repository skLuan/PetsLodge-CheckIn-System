# Plan: Make `#healthInfoForm` per-pet aware (multi-pet)

> **Status: draft, NOT executed** (checked 2026-11-06 — `Pill.js` still has no
> `HealthFormManager` import). Written by a Kilo Code session (was
> `.kilo/plans/1780534251648-stellar-engine.md`). It covers the health-form half of
> **Plan 04** (`04-multi-pet-health-food.md`); food is not covered. Line numbers below
> may have drifted — re-verify before coding.

## Problem

The `#healthInfoForm` (step 3) is currently single-pet:

- The Blade template hardcodes `pets[0]` and only pre-renders `warnings` for pet 0
  (`resources/views/components/forms/health-info.blade.php:1-8, 34`).
- Clicking a pet pill never repopulates the health form — `Pill.select()` only knows
  about `#petInfoForm` (`resources/js/Pill.js:62-66, 71-84`).
- Clicking a pet pill never saves the *current* health form back into the previously
  selected pet — so any edits are lost on switch (and end up applied to whichever pet
  happens to be selected when Next is pressed).
- `FormUpdater.updateHealthInfoForm` (the per-pet read path triggered by cookie
  reactivity) is missing the `warnings` field
  (`resources/js/cookies-and-form/reactivitySystem/FormUpdater.js:243-293`). The
  complete version exists as dead code in `populateHealthInfoFromCookie`
  (`FormUpdater.js:122-180`).

The cookie/session shape already supports per-pet `health` data
(`resources/js/cookies-and-form/config.js:79-97`), and the write helper
`PetManager.updatePetHealthInfo(petIndex, healthData)` is already in place
(`resources/js/cookies-and-form/managers/PetManager.js:146-161`). We just need to
wire the UI/JS to use them properly per pet.

## Goal

When the user clicks a different pet pill on step 3:

1. The current `#healthInfoForm` values are saved into the previously selected pet's
   `pets[prevIndex].health` slot in the cookie.
2. The form is reset and repopulated from `pets[newIndex].health` so each pet keeps
   its own answers (Yes/No, "Which?" text, Warnings text).

When the user navigates Next/Back from step 3, the current pet's edits are persisted
(this already mostly works on Next via `ValidationManager` step 3 — we just verify it).

Grooming + inventory data remains **global** (not per pet). That part of the form is
not changing.

## Scope (files we will edit)

1. `resources/views/components/forms/health-info.blade.php`
2. `resources/js/cookies-and-form/managers/HealthFormManager.js`
3. `resources/js/cookies-and-form/reactivitySystem/FormUpdater.js`
4. `resources/js/Pill.js`
5. `resources/js/tabbar.js` *(added: save on Back)*

No changes to `ValidationManager`, `PetManager`, `CookieHandler`, or the cookie
schema.

## Confirmed scope additions (from user)

In addition to the core "make `#healthInfoForm` per-pet" work, this change also folds
in three small related fixes/cleanups:

- **Save on pill deselect**: clicking the currently selected pill saves health data
  before clearing the form.
- **Save on Back button**: `tabbar.js` `prevStep` saves the current step's form
  (health on step 3) before navigating back.
- **Delete dead code**: remove the never-called
  `FormUpdater.populateHealthInfoFromCookie`.

Explicitly **out of scope**: `#petInfoForm` switch-save (mentioned as a related
pre-existing gap but not fixed here).

---

## Changes

### 1. Blade template — drop the `pets[0]` hardcode

**File:** `resources/views/components/forms/health-info.blade.php`

- Remove the `@php ... $healInfo = $checkinData["pets"][0]['health']; ... @endphp`
  block at the top (lines 1-8). JS owns population.
- Remove the `value="{{$healInfo['warnings'] ?? old('warnings')}}"` attribute on the
  `warnings` textarea (line 34). JS will set this from cookie on load and on pill
  click. (Also, `value=""` on a textarea is wrong HTML anyway — initial content
  belongs between the tags.)

This makes the form render blank server-side; the existing
`FormHandler.populateFormWithCookies()` already populates step 3 from the selected
pill on DOMContentLoaded (`resources/js/cookies-and-form/managers/FormHandler.js:109-141`),
so initial load still works.

### 2. Add per-pet save/load helpers to `HealthFormManager`

**File:** `resources/js/cookies-and-form/managers/HealthFormManager.js`

Add two static methods (in addition to existing `initializeHealthForm`):

- `static saveCurrentPetHealth(petIndex)`
  - Reads `#healthInfoForm` via `FormHandler.extractFormInputValues('#healthInfoForm')`.
  - Builds the same shape the Next path uses (`ValidationManager.js:35-39`):
    ```js
    {
      unusualHealthBehavior: formData.unusualHealthBehavior === 'yes',
      healthBehaviors: formData.healthBehaviorDetails || '',
      warnings: formData.warnings || '',
    }
    ```
  - Guards: if `#healthInfoForm` doesn't exist or `petIndex` is null/invalid, no-op.
  - Calls `FormDataManager.updatePetHealthInfo(petIndex, healthData)` (delegates to
    `PetManager.updatePetHealthInfo`, `PetManager.js:146-161`).

- `static loadPetHealth(petIndex)`
  - Reads `pets[petIndex].health` from `FormDataManager.getCheckinData()`.
  - Resets the form fields first (Yes/No, "Which?", Warnings) to empty/unchecked.
  - Populates from the pet's health if present, plus toggles the
    `.conditional-health-details` visibility for the Yes/No state.
  - Mirrors the logic already in `FormHandler.populateFormWithCookies` step 3
    (`FormHandler.js:109-141`) so behavior matches.

These two helpers are the single source of truth for per-pet health read/write. The
existing `initializeHealthForm` stays untouched — it only wires conditional UI.

### 3. Add `warnings` to `FormUpdater.updateHealthInfoForm`

**File:** `resources/js/cookies-and-form/reactivitySystem/FormUpdater.js`

In `updateHealthInfoForm` (`FormUpdater.js:243-293`), add the missing warnings block
(copy from the dead `populateHealthInfoFromCookie` at lines 152-157):

```js
// Update warnings field
const warningsField = document.getElementById('warnings')
    || document.querySelector('[name="warnings"]');
if (warningsField) {
    warningsField.value = currentPet.health?.warnings || '';
}
```

Also ensure the unusual-behavior radios and `healthBehaviorDetails` field are *reset*
when the pet has no value, so switching to a pet with no entry doesn't leave the
previous pet's values visible. (Currently the `if` guards mean stale values stick.)

This keeps the cookie-reactivity refresh path correct for all three per-pet fields.

**Dead-code cleanup:** Remove the unused `populateHealthInfoFromCookie` method
(`FormUpdater.js:122-180`) entirely. A grep confirms it has zero callers, and its
useful `warnings` logic is being merged into `updateHealthInfoForm` above.

### 4. Hook `Pill.select()` to save outgoing + load incoming health data

**File:** `resources/js/Pill.js`

Modify `select()` (`Pill.js:57-85`):

- Before any selection change, capture the previously selected pill's index:
  ```js
  const prevPill = document.querySelector('.pill.selected');
  const prevIndex = prevPill ? parseInt(prevPill.dataset.index, 10) : null;
  ```
- **Deselect branch** (`Pill.js:60-66`, user clicked the already-selected pill):
  - Call `HealthFormManager.saveCurrentPetHealth(prevIndex)` first.
  - Keep existing `form.reset()` on `#petInfoForm`.
  - Also reset `#healthInfoForm` via `HealthFormManager.loadPetHealth(null)` or by
    calling a dedicated `clearHealthForm()` helper (whichever is cleaner — the load
    helper already needs to handle "no pet" by clearing fields, so reusing it is
    fine).
  - Return as before.
- **Switch branch** (`Pill.js:67-84`, user clicked a different pill):
  - If `prevIndex !== null && prevIndex !== this.index`, call
    `HealthFormManager.saveCurrentPetHealth(prevIndex)` to persist outgoing pet.
  - Run the existing selection-toggle logic (`Pill.js:67-70`).
  - Keep the existing `#petInfoForm` population (`Pill.js:71-84`).
  - Add `HealthFormManager.loadPetHealth(this.index)` after the petInfo population.

Import `HealthFormManager` at the top of `Pill.js`.

Notes:
- Saving the outgoing pet's data is fire-and-forget against the cookie; the cookie
  reactivity system will not loop because `updateHealthInfoForm` reads the *new*
  selected pet, not the one we just wrote.
- We deliberately do *not* extend this to `#petInfoForm` save-before-switch in this
  task — that's a pre-existing gap and is out of scope.

### 5. Save on Back button

**File:** `resources/js/tabbar.js` (around `prevStep`, lines 15-22)

Before decrementing the step, inspect the current step. If it's the health step
(step index 3), call `HealthFormManager.saveCurrentPetHealth(selectedPetIndex)`
using the selected pill index (default 0 if none), mirroring what Next already does
via `ValidationManager`.

Minimal, surgical addition — we are *not* extending this to other steps in this
change; only step 3 health is in scope.

Import `HealthFormManager` (and `PetPillManager` for the selected index helper) at
the top of `tabbar.js`.

---

## Behavior after the change

| User action                            | What happens                                                                                                                          |
| -------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| Page load on step 3                    | `FormHandler.populateFormWithCookies` fills health form from the selected (or first) pet — unchanged.                                 |
| Click a different pet pill on step 3   | `Pill.select()` saves current form → outgoing pet's `health`, then loads incoming pet's `health` into the form.                       |
| Edit Yes/No, "Which?", or Warnings     | UI changes only; cookie not yet written.                                                                                              |
| Click Next                             | `ValidationManager` step 3 handler saves current form into the currently selected pet via `updatePetHealthInfo` — already works.      |
| Click Back on step 3                   | `tabbar.js` calls `HealthFormManager.saveCurrentPetHealth(selectedPetIndex)` before navigating (new in this change).                  |
| Cookie reactivity tick on step 3       | `FormUpdater.updateHealthInfoForm` repopulates from the selected pet, now including `warnings`.                                       |

---

## Verification (manual)

1. Step 3 with two pets (Max, Masimo):
   - Select Max → enter "Yes" + "Diarrhea" + "Aggressive when wet" warning.
   - Click Masimo pill → form clears (or shows Masimo's previously saved values).
   - Enter "No" + "Anxiety" warning.
   - Click Max pill → "Yes" + "Diarrhea" + "Aggressive when wet" reappear.
   - Click Masimo pill → "No" + "Anxiety" reappear.
2. Click Next on Max selected, advance to step 4, then click Back → values still
   per-pet (NOTE: only if Back-save is implemented; current scope leaves this as-is,
   so we'll verify the cookie still has each pet's data correctly).
3. Reload mid-flow → step 3 still shows the selected pet's data on next visit
   (`FormHandler.populateFormWithCookies` handles this).
4. Sanity check `pl_checkin_data` cookie in DevTools: `pets[0].health` and
   `pets[1].health` hold distinct values.

---

## Resolved decisions

- Save on pill deselect: **included** (Pill.js section 4).
- Save on Back: **included** (tabbar.js section 5).
- Delete dead `populateHealthInfoFromCookie`: **included** (FormUpdater section 3).
- `#petInfoForm` save-before-switch: **deferred** — separate task.
