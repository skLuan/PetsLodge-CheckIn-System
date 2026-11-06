# Feature: Services & Inventory

## Purpose

Track the services and items associated with a boarding pet: **grooming/extra
services**, **feeding schedules**, **medication schedules**, and **inventory items**
(e.g. toys, beds, food brought by the owner).

## Data model

- `ExtraService` (`app/Models/ExtraService.php`) — e.g. grooming add-ons.
  Linked to `CheckIn` via pivot `check_in_extra_service` with pivot column
  `grooming_appointment_day` (added by migration
  `2026_01_23_..._add_grooming_appointment_day_to_check_in_extra_service`).
- `Food` — `hasMany CheckIn`; `belongsTo MomentOfDay` (time-of-day reference).
- `Medicine` — `hasMany CheckIn`; `belongsTo MomentOfDay`.
- `Item` — `hasMany CheckIn`; per-pet inventory line (`name`, `pet_id`, `check_in_id`).
- `MomentOfDay` — seeded reference of day-parts (e.g. morning, afternoon, evening);
  `afternoon` was added by `2026_03_24_..._seed_afternoon_moment_of_day`.

`CheckIn` exposes: `extraServices()` (belongsToMany w/ pivot), `foods()`,
`medicines()`, `items()`.

## Routes & endpoints

- `POST /api/checkin/step5/extra-info` → `CheckInApiController@submitExtraInfo`
  (inventory + grooming).
- `POST /api/checkin/submit` → full submit also calls
  `CheckInService::processInventory()` and `processExtraServices()`.
- Feeding & medication are captured in step 3/5 and rendered through the
  `feeding-medication` pop-up.

## Backend

- `CheckInService::processInventory(array $checkIns, array $inventoryData)` — creates
  one `Item` per line, per check-in/pet.
- `CheckInService::processExtraServices(array $checkIns, array $groomingData)` —
  `ExtraService::firstOrCreate(['name' => $service])`, then
  `$checkIn->extraServices()->syncWithoutDetaching([$id => ['grooming_appointment_day' => $day]])`.
  Skips non-service keys like `appointmentDay`.

## Frontend

- Views: `resources/views/components/forms/inventory.blade.php`,
  `components/forms/food-medication.blade.php`,
  `components/pop-ups/feeding-medication.blade.php`,
  `components/pop-ups/grooming.blade.php`.
- JS: `managers/InventoryManager.js`, `managers/InventoryFormManager.js`,
  `managers/PopupManager.js`, `managers/HealthFormManager.js`.

## Behavior & flow

- **Multiple times of day** can be selected for feeding and medication in a single
  action (checkboxes, not radios) — each selection maps to a `MomentOfDay`.
- Grooming services carry an optional **appointment day**, stored on the pivot.
- Inventory items are free-text lines attached to the pet's check-in.

## Key files

`app/Models/{ExtraService,Food,Medicine,Item,MomentOfDay}.php`,
`app/Services/CheckInService.php` (`processInventory`, `processExtraServices`),
`resources/views/components/forms/inventory.blade.php`,
`resources/js/cookies-and-form/managers/InventoryManager.js`.
