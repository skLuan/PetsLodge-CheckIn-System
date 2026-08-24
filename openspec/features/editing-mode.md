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

- `GET /edit-check-in/{checkInId}` → `CheckInFormController@editCheckIn` — loads the
  check-in with all relations, transforms it to cookie format via
  `CheckInTransformer`, stores `checkin_data` + editing flags in session, then
  redirects to `/new-form-pre-filled?phone=...`.
- `DELETE /delete-check-in/{checkInId}` → `CheckInFormController@deleteCheckIn` —
  removes foods, medicines, items, detaches extra services, deletes the check-in.
- `GET /view-check-in?phone=...` → `viewCheckIn` — lists a user's active check-ins
  (with foods/medicines/items), each with edit/delete actions.

## Backend

- Controller: `CheckInFormController` (`editCheckIn`, `deleteCheckIn`, `viewCheckIn`).
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
5. Submit persists the edited record (services handle create-vs-update).

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
