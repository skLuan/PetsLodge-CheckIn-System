# Feature: Pet Staff Dashboard

## Purpose

An operational console for `PET_STAFF` / `SUPER_ADMIN` users to see all currently
boarded pets and act on them: **drop-in**, **check-out**, **cancel**, and
**re-print**. Guards: `auth` + `pet.staff.only`.

## Data model

Driven by `Status` (seeded: `CHECKED_IN`, `PRINTED`, `DROPPED_IN`, `CHECKED_OUT`,
`CANCELLED`). The dashboard partitions check-ins by current status and uses
`document_url` for re-printing.

`CheckIn` relations used: `pet`, `pet.kindOfPet`, `user`.

## Routes & endpoints

All `auth` + `pet.staff.only` (`routes/web.php` → `PetStaffDashboardController`):
- `GET /pet-staff/dashboard` → `index`
- `POST /pet-staff/checkout/{id}` → `checkout`
- `POST /pet-staff/dropped-in/{id}` → `dropped_in`
- `POST /pet-staff/cancel/{id}` → `cancel`
- `POST /pet-staff/reprint/{id}` → `reprint`

## Backend (`PetStaffDashboardController`)

- `index` — queries `CHECKED_IN` and `DROPPED_IN` check-ins (with pet/owner) ordered
  by `check_in desc`, passes both lists to the view.
- `checkout` — sets status `CHECKED_OUT`, sets `check_out = now()`.
- `dropped_in` — sets status `DROPPED_IN`, sets `check_out = now()`.
- `cancel` — sets status `CANCELLED`.
- `reprint` — guards on `document_url`; calls `PrintNodeService::sendPrintJob()`
  with the stored URL (no re-generation).

## Frontend

- View: `resources/views/pet-staff/dashboard.blade.php` — two sections (checked-in /
  dropped-in), action buttons per row, success/error flash messages.

## Behavior & flow

```
CHECKED_IN ──dropped_in──▶ DROPPED_IN ──checkout──▶ CHECKED_OUT
     │                         │
     └──────────cancel─────────┴──▶ CANCELLED
```

- Re-print is idempotent: it reuses the stored `document_url`, only generating a new
  PDF if none exists (handled in [drop-in.md](drop-in.md) / `readyToPrint`).

## Key files

`app/Http/Controllers/PetStaffDashboardController.php`, `app/Models/Status.php`,
`resources/views/pet-staff/dashboard.blade.php`.
