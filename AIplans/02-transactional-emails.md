# Plan 02 — Transactional emails to clients (Hostinger SMTP)

**Phase 2 · Branch: `feature/02-transactional-emails`**

## Problem

No emails are sent to clients. Current `.env.example` points to mailpit (dev). Provider is **Hostinger**. Required triggers: successful check-in, drop-in (pet left), drop-out (pet picked up).

## Target architecture

Laravel Mailables + queued sending. Trigger points already exist in the codebase:

| Event | Where it happens today | Mailable |
|---|---|---|
| Check-in submitted | `CheckInApiController::submitCheckIn` and end of step5 `submitExtraInfo` (verify which one finalizes — likely both paths must fire it exactly once) | `CheckInConfirmationMail` |
| Drop-in (pet left at lodge) | `PetStaffDashboardController::dropped_in` | `DropInMail` |
| Drop-out (pet picked up) | `PetStaffDashboardController::checkout` | `DropOutMail` |

### 1. Hostinger SMTP configuration (research step — verify in Hostinger hPanel)

Expected values (confirm against the actual Hostinger account, Emails → Connect Devices):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl        # or 587 + tls
MAIL_USERNAME=no-reply@<ourdomain>
MAIL_PASSWORD=<mailbox password>
MAIL_FROM_ADDRESS=no-reply@<ourdomain>
MAIL_FROM_NAME="Pet Lodge & Spa"
```

Prerequisites: mailbox created in Hostinger; SPF/DKIM records set (Hostinger sets these automatically for its own mail service — verify with `dig TXT <domain>`). Document final values in `docs/DEPLOYMENT_GUIDE.md` (never commit real credentials).

Local dev keeps mailpit; only production `.env` uses Hostinger.

### 2. Queueing

Redis is already in docker-compose but `QUEUE_CONNECTION=sync`. Emails MUST be queued (`implements ShouldQueue`) so SMTP latency/failures never block check-in UX.

- Set `QUEUE_CONNECTION=redis` in prod.
- Add a queue worker to docker-compose (`command: php artisan queue:work --tries=3 --backoff=30`) or supervisor entry.
- Failed jobs: `failed_jobs` table already migrated — add `php artisan queue:failed` check to ops runbook.

### 3. Mailables + templates

`app/Mail/CheckInConfirmationMail.php`, `DropInMail.php`, `DropOutMail.php` — each takes `CheckIn $checkIn` (loads `->pet`, `->user`, `->extraServices`).

Templates in `resources/views/emails/`:
- `layouts/base.blade.php` — shared branded layout (logo, lodge colors, footer with address/contact). Use Laravel Markdown mailables OR plain blade with inline CSS (email clients ignore external CSS — inline only, table layout).
- `check-in-confirmation.blade.php` — pet name(s), check-in/check-out dates, services booked, T&C reference.
- `drop-in.blade.php` — "We've received <pet>", timestamp, contact info.
- `drop-out.blade.php` — "<pet> was picked up", timestamp, thanks/come-back.

### 4. Wiring (in `api.php`-driven controllers, per Notion note)

Prefer **events** over inline `Mail::to(...)` so triggers stay testable and decoupled:

- `App\Events\CheckInCompleted`, `PetDroppedIn`, `PetDroppedOut` (each carries `CheckIn`).
- Listeners `SendCheckInConfirmation`, etc. (`implements ShouldQueue`), registered in `EventServiceProvider`.
- Fire from the three controller points above. Guard: only send if `$checkIn->user->email` is valid; wrap dispatch in try/catch + `Log::warning` — email failure must never 500 the API.

### 5. Tests FIRST — `tests/Feature/TransactionalEmailTest.php`

Use `Mail::fake()` / `Event::fake()`:
- Completing a check-in dispatches `CheckInConfirmationMail` to the owner's email, exactly once.
- `POST /pet-staff/dropped-in/{id}` queues `DropInMail`.
- `POST /pet-staff/checkout/{id}` queues `DropOutMail`.
- No mail when user has empty/invalid email; endpoint still returns success.
- Mailable content test: renders pet name and dates (`->assertSeeInHtml`).

## Step-by-step execution order

1. Tests (red).
2. Events + listeners + empty mailables (green on dispatch tests).
3. Templates (base layout first, then the 3 emails). Preview via mailpit locally.
4. Queue config + docker worker.
5. Hostinger credentials in prod `.env`; send real test email to ourselves.
6. Docs + move plan to `plans/`.

## Acceptance criteria

- [ ] All 3 emails send in production via Hostinger, branded, correct data.
- [ ] Sending is queued; a dead SMTP does not break check-in/checkout endpoints.
- [ ] `Mail::fake` tests green.
- [ ] Credentials documented (location, not values) in DEPLOYMENT_GUIDE.
