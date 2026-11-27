# Feature: Editing Mode

## Purpose

Allow staff to **edit an existing check-in** rather than re-enter everything.
Editing mode is explicit and tracked separately from create mode, with an
**original-data snapshot** so changes can be detected, summarized, and reset.

## Data model

Editing mode operates on the same `CheckIn` + related models as
[check-in.md](check-in.md); the difference is **state tracking**, not schema.

Editing flags are passed through the Laravel session:
- `editing_mode` (bool)
- `editing_check_in_id` (int)
- `checkin_data` (the transformed existing record)

## Routes & endpoints

Editing works on the **booking**: the owner's open rows, one per pet
(`CheckIn::openBookingOf()` — `check_out IS NULL`, status not `CHECKED_OUT`/`CANCELLED`).

- `GET /edit-check-in/{checkInId}` → `CheckInFormController@editCheckIn` — loads the
  row's whole booking (the row alone if it is closed), transforms it with
  `CheckInTransformer::transformBookingForWizard()` (every pet + `grooming.pets` indexes),
  flashes `wizard.prefill` + `wizard.editing_check_in_id` (= the booking's first id), then
  redirects to `/new-form-pre-filled?phone=...`.
- After the step 1–5 loop of an edit, `SubmissionManager` calls
  `POST /check-in/booking/prune` (web route; `check_in_id`, `pet_ids` min 1) →
  `CheckInService::pruneBooking()` deletes the rows of pets removed in the wizard.
  A **signed** row is kept unless pet staff is logged in (`kept_signed_check_in_ids`;
  the wizard alerts). Non-critical: a failure only warns, the redirect still happens.
- Deleting a signed check-in (one pet or the booking) needs pet staff or above:
  otherwise `403 {message}` (JSON) / redirect with `error`. The view hides those
  buttons and shows "🔒 Signed — only pet staff can delete".
- `DELETE /delete-check-in/{checkInId}` → `deleteCheckIn` — removes ONE pet's row
  (`CheckInService::deleteCheckIn()`: foods, medicines, items, extra services, row).
- `DELETE /delete-booking/{checkInId}` → `deleteBooking` — removes every row of the booking.
- `GET /view-check-in?phone=...` → `viewCheckIn` — ONE card for the booking: Edit /
  Delete in the header, one section per pet (`components/view-check-in/pet-section.blade.php`,
  with Remove when there is more than one pet), the inventory listed once.

## Backend

- Controller: `CheckInFormController` (`editCheckIn`, `deleteCheckIn`, `deleteBooking`, `viewCheckIn`).
- Transformer: `CheckInTransformer::transformCheckInToCookieFormat()` eager-loads
  `pet.gender`, `pet.kindOfPet`, `pet.castrated`, `user.emergencyContacts`,
  `foods.momentOfDay`, `medicines.momentOfDay`, `items`, `extraServices`.

## Frontend

- View: `resources/views/Process.blade.php` emits the editing flags + check-in data as
  `data-*` attributes for `form-processor.js` to consume.
- JS: `managers/EditingModeManager.js` — enables editing mode, stores the original
  snapshot, computes diffs, exposes reset.
- Reactivity: `CookieReactivityManager`, `UIManager`, `SummaryRenderer` keep the
  summary and change indicators live.

## Behavior & flow

1. Staff clicks **Edit** → `editCheckIn` builds the cookie-format snapshot, sets flags.
2. On load, `form-processor.js` extracts the flags **in the correct order**
   (race-condition-sensitive) and enables editing mode, snapshotting the original data.
3. As the user edits, `EditingModeManager` diffs current vs. snapshot → change summary.
4. Staff can **reset to original** (`FormDataManager.resetToOriginal()`).
5. Submit persists the edited record (services handle create-vs-update). Edit reuses
   the normal step1→step5 sequence, so it must be idempotent:
   - the transformer puts the pet's DB id in `pets[i].info.petId` → step 2 updates
     that pet instead of creating one;
   - steps 3 and 4 resolve the pet's open stay via
     `CheckInService::findActiveCheckIn()` (`check_out IS NULL`, status not
     `CHECKED_OUT`/`CANCELLED`) → the edited row is reused, never duplicated;
   - foods, medicines and items are replaced per check-in, extra services `sync()`ed.
   Covered by `tests/Feature/EditCheckInSubmissionTest.php`.

> ⚠️ Do not reorder the init/async sequence here — it is race-condition sensitive.

## Debug helpers (browser console)

```javascript
FormDataManager.isEditingMode();      // editing?
FormDataManager.hasDataChanged();     // any changes?
FormDataManager.getChangeSummary();   // what changed
FormDataManager.resetToOriginal();    // revert
```

## Key files

`app/Http/Controllers/CheckInFormController.php`,
`app/Services/CheckInTransformer.php`,
`resources/js/cookies-and-form/managers/EditingModeManager.js`,
`resources/js/cookies-and-form/form-processor.js`.
