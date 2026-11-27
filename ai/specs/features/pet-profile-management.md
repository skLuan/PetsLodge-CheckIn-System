# Feature: Pet Profile Management

## Purpose

Capture and store detailed pet records so the lodge has everything needed to care
for an animal: identity, physical attributes, reproductive status, and health notes.

## Data model

`Pet` (`app/Models/Pet.php`), `fillable`:
`name, race, color, weight, birth_date, health_conditions, warnings, gender_id,
kind_of_pet_id, castrated_id, user_id`.

Relationships:
- `belongsTo User` (owner)
- `belongsTo Gender`, `KindOfPet`, `Castrated` (lookup tables)
- `hasOne CheckIn`
- `belongsToMany EmergencyContact` (pivot `emergency_contact_pet`)

Lookup tables (seeded): `KindOfPet`, `Gender`, `Castrated`.

## Routes & endpoints

There is no dedicated pet CRUD surface; pets are created/updated **as part of the
check-in flow** and **read** via the owner/pre-fill lookups:

- `GET /new-form?phone=...` / `GET /new-form-pre-filled?phone=...` →
  `CheckInFormController@newForm` / `@newFormPreFilled` eager-loads
  `pets.kindOfPet`, `pets.gender`, `pets.castrated` to pre-fill the form.
- `POST /api/checkin/step2/pet-info` → `CheckInApiController@submitPetInfo` persists pet data.
- `POST /api/checkin/submit` → full submit also upserts pets via `CheckInPetService`.

## Backend

- Controller: `CheckInFormController` (reads), `CheckInApiController` (writes).
- Service: `CheckInPetService` — pet upsert/lookup keyed to the owner.
- Transformer: `CheckInTransformer` flattens the pet + its lookups into the cookie
  format and back (null-safe).

## Frontend

- View: `resources/views/components/forms/pet-info.blade.php` — the pet-info step
  (name, breed, gender, castrated, color, weight, birth date).
- JS: `managers/PetManager.js`, `managers/PetPillManager.js` (multi-pet "pill" UI),
  wired through `FormDataManager`.
- Health fields render via `components/forms/health-info.blade.php` and
  `managers/HealthFormManager.js` (health conditions, warnings, weight).

## Behavior & flow

- An owner can have **multiple pets**; the form supports adding several in one check-in.
- Lookup values (kind/gender/castrated) come from seeded reference tables, not free text.
- Editing a check-in re-populates the pet fields from the cookie (see
  [editing-mode.md](editing-mode.md)).

## Key files

`app/Models/Pet.php`, `app/Services/CheckInPetService.php`,
`resources/views/components/forms/pet-info.blade.php`,
`resources/js/cookies-and-form/managers/PetManager.js`.
