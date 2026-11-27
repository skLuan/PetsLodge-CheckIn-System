# Feature: Drop-In (staff)

## Purpose

Staff-only flow: look an owner up **by phone**, confirm their active check-in,
generate a PDF summary, and send it to a physical printer. Guards: `auth` +
`pet.staff.only`.

## Data model

Reuses `User`, `CheckIn` (+ relations), `Status`. The PDF output is stored on the
public disk and its URL persisted as `CheckIn.document_url`.

## Routes & endpoints

**Web** (`routes/web.php`, `auth` + `pet.staff.only`):
- `GET /drop-in` → `DropInController@show`
- `GET /drop-in/confirmation` → `DropInController@showDropConfirmation`
- `POST /drop-in/check-user` → `DropInController@checkUser`

**API** (`routes/api.php`):
- `POST /api/readyToPrint` → `DropInController@readyToPrint`

## Backend

- `DropInController::showDropConfirmation` — validates phone (`^[0-9]{10}$`), finds
  the user, loads `checkin_data` from session (or builds it from the user's latest
  active check-in via `CheckInTransformer`), stores it back in session.
- `DropInController::checkUser` — proxies `CheckInApiController@checkUser`, and on a
  hit loads + transforms the active check-in into the session.
- `DropInController::readyToPrint` —
  1. Reuses `document_url` if the check-in already has one.
  2. Otherwise calls `PdfService::generatePdf()` to render `pdf-for-print` to a PDF
     and stores the returned URL on the check-in.
  3. Sends the job via `PrintNodeService::sendPrintJob()`.

## Frontend

- Views: `resources/views/Drop-in.blade.php` (phone entry),
  `Drop-in-confirmation.blade.php` (confirm data → print),
  `Drop-in/check.blade.php`.
- JS: `components/CheckInHandler.js`, `CheckInSummaryUpdater`.

## Behavior & flow

1. Staff enters the owner's phone on `/drop-in`.
2. System confirms the owner exists and has an active check-in.
3. Confirmation page shows the check-in summary.
4. Staff clicks print → PDF generated once (cached as `document_url`) → PrintNode job dispatched.

> See [pdf-and-printing.md](pdf-and-printing.md) for the PDF/PrintNode internals and
> [pet-staff-dashboard.md](pet-staff-dashboard.md) for re-printing stored documents.

## Key files

`app/Http/Controllers/DropInController.php`, `app/Services/PdfService.php`,
`app/Services/PrintNodeService.php`, `resources/views/Drop-in-confirmation.blade.php`.
