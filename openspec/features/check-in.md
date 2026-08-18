# Feature: Check-In

## Purpose

The signature feature of PetsLodge. Front-desk staff capture a pet's boarding
details through a **5-step multi-step form**:

1. **Owner (user) info** — name, phone, email, address.
2. **Pet info** — name, kind/breed, gender, castrated, color, weight, birth date.
3. **Pet health** — health conditions, warnings.
4. **Check-in data** — check-in/check-out dates.
5. **Extra info/services** — inventory items, grooming, feeding & medication times.

All form state lives in a single browser cookie (`pl_checkin_data`) which acts as
the **single source of truth**; the UI reacts automatically to changes.

## Data model

- `CheckIn` (`check_in`, `check_out`, `pet_id`, `user_id`, `status_id`, `document_url`)
  — `app/Models/CheckIn.php`
- Related through the check-in: `Pet`, `User`, `Status`, `ExtraService` (pivot
  `check_in_extra_service` with `grooming_appointment_day`), `Food`, `Medicine`, `Item`.

## Routes & endpoints

**Web** (`routes/web.php`):
- `GET /check-in` → `checkIn` view (landing / start).
- `GET /new-form` → `CheckInFormController@newForm` (blank form).
- `GET /new-form-pre-filled` → `CheckInFormController@newFormPreFilled` (pre-filled by phone).
- `GET /view-check-in` → `CheckInFormController@viewCheckIn`.

**API** (`routes/api.php` → `CheckInApiController`):
- `POST /api/checkin/submit` → `submitCheckIn` (one-shot whole-form submit).
- `POST /api/checkin/autosave` → `autoSaveCheckIn` (stub — returns success).
- `POST /api/checkin/step1/user-info` → `submitUserInfo`
- `POST /api/checkin/step2/pet-info` → `submitPetInfo`
- `POST /api/checkin/step3/pet-health` → `submitPetHealth`
- `POST /api/checkin/step4/checkin-data` → `submitCheckInData`
- `POST /api/checkin/step5/extra-info` → `submitExtraInfo`
- `POST /api/update-session-checkin` → `updateSessionCheckIn` (refresh server session
  with the just-created check-in so confirmation pages aren't stale).
- `POST /api/check-user` → `checkUser` (lookup by phone; reports `hasCheckIn`).

## Backend

- Controllers: `CheckInApiController` (API), `CheckInFormController` (web pages),
  `CheckInController`.
- Services:
  - `CheckInService::processCheckIn()` — creates/updates a `CheckIn` with status
    `CHECKED_IN`; reuses an active check-in if one exists (`check_out IS NULL`).
  - `CheckInService::processInventory()` / `processExtraServices()` — items & grooming.
  - `CheckInUserService`, `CheckInPetService` — owner & pet upsert.
  - `CheckInTransformer` — lossless, null-safe DB ⇄ cookie format conversion.
  - `CheckInDataValidator` — structure & required-field validation.

## Frontend

- Views: `resources/views/checkIn.blade.php` (landing), `Process.blade.php`
  (multi-step host that emits `data-session-checkin` + editing flags into the DOM),
  `resources/views/components/forms/{pet-info,health-info,food-medication,inventory,owner-info}.blade.php`,
  `components/pop-ups/{feeding-medication,grooming,terms-conditions}.blade.php`,
  `components/CheckInSummary.blade.php`.
- JS (`resources/js/cookies-and-form/`):
  - `FormDataManager.js` — **main public API** for form data.
  - `CookieManager.js` / `CookieHandler.js`, `config.js` — `pl_checkin_data` I/O.
  - `form-processor.js` — merges session data into the cookie once at init.
  - `managers/` — `CoreDataManager`, `PetManager`, `HealthFormManager`,
    `InventoryFormManager`, `FormHandler`, `NavigationManager`, `ValidationManager`,
    `SubmissionManager`, `PopupManager`, `CheckInSummaryUpdater`, `FastCheckinManager`.
  - `reactivitySystem/` — `CookieReactivityManager` (MutationObserver, no polling),
    `UIManager`, `FormUpdater`, `SummaryRenderer`.

## Behavior & flow

```
Database → Laravel Session → DOM data-* attributes → Cookie (pl_checkin_data) → Form UI
```

1. On page load, `form-processor.js` extracts session/`data-*` data and merges it into
   the cookie **once** (race-condition-sensitive ordering).
2. Users edit the form; every change writes through `FormDataManager` → cookie.
3. `CookieReactivityManager` observes the cookie and refreshes the UI without wiping
   in-progress input.
4. Submit either step-by-step (`/step1..5`) or all-at-once (`/submit`); the service
   layer persists users, pets, the `CheckIn`, foods, medicines, items, and extra services.
5. `updateSessionCheckIn` then refreshes the server session with the new check-in id.

## Key files

`app/Http/Controllers/CheckInApiController.php`, `CheckInFormController.php`,
`app/Services/CheckInService.php`, `CheckInTransformer.php`,
`resources/views/Process.blade.php`, `resources/js/cookies-and-form/FormDataManager.js`.
