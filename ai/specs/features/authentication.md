# Feature: Authentication

## Purpose

Control who can reach staff/admin surfaces while keeping the public check-in flow
open. Built on **Laravel Breeze** (server-rendered session auth) with **Sanctum**
token support for the API.

## Data model

- `User` (`app/Models/User.php`) — `Authenticatable`, `HasApiTokens` (Sanctum),
  `Notifiable`. `fillable` includes `role`. Passwords cast to hashed.
- Roles seeded (`database/seeders/UserSeeder.php`):
  - `SUPER_ADMIN` — `admin@petslodge.com`
  - `PET_STAFF` — `staff@petslodge.com`
- The `admin.only` gate also honors an `ADMIN` role value.

Standard Laravel auth tables: `users`, `password_reset_tokens`,
`personal_access_tokens` (Sanctum), `failed_jobs`.

## Routes & endpoints

**Auth (Breeze)** (`routes/auth.php`):
- Register / login / logout, email verification, password reset / update / confirmation.

**Profile** (`routes/web.php`, `auth`):
- `GET /profile`, `PATCH /profile` (info), `DELETE /profile` (account),
  password change via `ProfileController` + `ProfileUpdateRequest`.

**API** (`routes/api.php`):
- `GET /api/user` (guard `auth:sanctum`).
- Check-in/drop-in API endpoints are currently **unauthenticated** (kiosk-style flow).

**Gated surfaces** (`routes/web.php`):
- `/dashboard`, `/admin/monitoring-dashboard` → `auth` + `verified` + `admin.only`.
- `/drop-in*`, `/pet-staff/*` → `auth` + `pet.staff.only`.

## Backend

- Controllers: `app/Http/Controllers/Auth/*` (Breeze), `ProfileController`,
  `app/Http/Requests/Auth/LoginRequest`, `ProfileUpdateRequest`.
- Middleware:
  - `AdminOnly` (`admin.only`) — requires `role === 'ADMIN'`.
  - `PetStaffOnly` (`pet.staff.only`) — allows `PET_STAFF`, `SUPER_ADMIN` (+ case
    variants).

## Frontend

- Views: `resources/views/auth/*` (login, register, verify-email, password reset/confirm),
  `resources/views/profile/*` (edit + partials), `resources/views/layouts/navigation.blade.php`.
- `resources/views/layouts/app.blade.php` provides the authed shell.

## Behavior & flow

- The public check-in (`/`, `/check-in`, `/new-form`, form API) does **not** require login.
- Staff drop-in/dashboard require `PET_STAFF`/`SUPER_ADMIN`.
- Admin dashboard + monitoring require `ADMIN` (or SUPER_ADMIN via the admin path) + verified email.
- Sessions/cache/queue default to **Redis** in Docker; locally they can be file/database.

## Key files

`app/Models/User.php`, `app/Http/Middleware/{AdminOnly,PetStaffOnly}.php`,
`app/Http/Controllers/ProfileController.php`, `routes/auth.php`, `routes/web.php`,
`database/seeders/UserSeeder.php`.
