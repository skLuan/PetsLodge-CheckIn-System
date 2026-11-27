# Feature: Owner (User) Management

## Purpose

Record the pet **owner** (a `User`) — contact details and one-or-many emergency
contacts — and let staff find an owner quickly by phone number to pre-fill forms
or confirm active check-ins.

## Data model

`User` (`app/Models/User.php`), `fillable`:
`name, email, password, phone, address, city, state, zip, role`.
- `hasMany Pet`, `hasMany CheckIn`, `hasMany EmergencyContact` (also `hasOne` alias).

`EmergencyContact` — `hasMany` from `User`; also `belongsToMany Pet`
(pivot `emergency_contact_pet`) so contacts can be associated per-pet.

> Note: the DB stores full address fields (`city`, `state`, `zip`); the check-in
> flow and phone-lookup endpoints primarily surface `name/phone/email/address`.

## Routes & endpoints

- `GET /new-form?phone=...` / `GET /new-form-pre-filled?phone=...` →
  `CheckInFormController` looks up `User::where('phone', ...)` and eager-loads pets.
- `POST /api/check-user` → `CheckInApiController@checkUser` — phone lookup; returns
  `userExists`, `hasCheckIn` (active check-in), `userId`, `userName`, `userEmail`,
  `userAddress`.
- `POST /drop-in/check-user` → `DropInController@checkUser` (staff flow; see
  [drop-in.md](drop-in.md)).
- `POST /api/checkin/step1/user-info` → `submitUserInfo` persists owner info.
- Profile management (self-service, auth): `GET /profile`, `PATCH /profile`,
  `DELETE /profile` → `ProfileController`.

## Backend

- Controller: `CheckInApiController` (lookup + step1), `CheckInFormController`
  (pre-fill), `ProfileController` (self-service profile).
- Service: `CheckInUserService` — owner upsert keyed by phone.
- Transformer: `CheckInTransformer` handles user + `emergencyContacts` null-safely.

## Frontend

- Views: `resources/views/components/forms/owner-info.blade.php` (owner step),
  `resources/views/profile/edit.blade.php` + `profile/partials/*` (self-service).
- JS: `managers/CoreDataManager.js` (owner core fields), `FormDataManager`.

## Behavior & flow

- **Phone is the natural key** for lookups across check-in, drop-in, and view flows.
- When an owner already exists, their data (and pets) pre-fill the form to avoid re-entry.
- Emergency contacts are optional but support multiple entries; transformation is
  null-safe for owners with no contacts.

## Key files

`app/Models/User.php`, `app/Models/EmergencyContact.php`,
`app/Services/CheckInUserService.php`, `app/Http/Controllers/ProfileController.php`,
`resources/views/components/forms/owner-info.blade.php`.
