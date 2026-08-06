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

- [ ] **All 3 emails send in production via Hostinger**, branded, correct data.
      → *Blocked: credentials not yet supplied. All 3 verified over real SMTP
      (mailpit) instead; only the Hostinger leg is unproven.*
- [x] Sending is queued; a dead SMTP does not break check-in/checkout endpoints.
- [x] `Mail::fake` tests green (20 tests).
- [x] Credentials documented (location, not values) in DEPLOYMENT_GUIDE.

---

## Execution notes (2026-08-04, branch `feature/02-transactional-emails`)

**Status: code complete, 1 acceptance criterion open (production Hostinger send).**
This plan stays in `AIplans/` until that is done — move it to `plans/` with `git mv`
once a real Hostinger email has been received.

### Deviations from the plan as written

1. **The plan's main trigger point was wrong.** It proposed hanging the confirmation off
   `CheckInApiController::submitCheckIn` (`/api/checkin/submit`). That endpoint is **dead
   code** — it calls `CheckInService::submitCheckIn()`, which does not exist, so it always
   500s. (This is also the true cause of the two red `CheckInSubmissionTest` cases, which
   an earlier session had misattributed to unseeded lookup tables.) The live path is
   step1→step5, so the event fires at the **end of step 5** (`submitExtraInfo`).
   Fixing the dead endpoint is left to Plan 05.

2. **"Exactly once" required a coalescing design.** Steps 2–5 run in a **per-pet loop**,
   so a 3-pet booking fires `CheckInCompleted` 3×. Per the product decision, the owner
   gets **one** email listing every pet:
   - new column `check_ins.confirmation_sent_at`;
   - `SendCheckInConfirmation` runs on a **120s delay** (`withDelay()`), then claims each
     un-announced check-in for that owner with a conditional row-by-row `UPDATE`, so under
     concurrency each row lands in exactly one email;
   - a 30-minute window prevents sweeping in an unrelated older failed booking;
   - `CheckInConfirmationMail` takes a **`Collection`** of check-ins, not a single one.

3. **Two pieces of infrastructure the plan assumed existed, didn't:**
   - there was **no queue worker** anywhere (queued mail would have piled up in Redis
     forever) → added a `queue` service to `docker-compose.yml`;
   - there was **no mailpit service**, despite `.env` pointing `MAIL_HOST=mailpit`
     → added it (UI on :8025).

4. **No booking date range exists in the schema.** The plan asked for "check-in/check-out
   dates" in the confirmation. `check_ins.check_in` is the creation timestamp and
   `check_out` is only written at pickup — there is no stored departure date. The email
   shows "Checked in: <date>" instead. A real arrival/departure range is a schema change
   and a separate plan.

5. **Lodge contact details did not exist in the codebase.** Rather than invent an address
   or phone number, added `config/lodge.php` driven by `LODGE_*` env vars; the footer
   omits any blank field, so no placeholder text can ever reach a client. **These are
   still empty and need filling in.**

### Verification

- 20 tests in `tests/Feature/TransactionalEmailTest.php`, all green. Full suite
  62 passed / 4 failed — same 4 pre-existing failures, no new ones.
- Migration rollback + re-migrate verified by CLI.
- **Real SMTP end-to-end** via mailpit: a 3-pet booking + drop-in + drop-out produced
  exactly **3** messages, with all 3 pets in the single confirmation.

### Still to do

- Put the Hostinger credentials in the production `.env` and run the send test in
  `docs/DEPLOYMENT_GUIDE.md → Transactional Email & Queue Setup §4`.
- Verify SPF/DKIM on the live domain (`dig TXT`) and set `APP_URL` to the public https
  URL — the header logo is an `asset()` link and breaks otherwise.
- Fill in `LODGE_ADDRESS` / `LODGE_PHONE` / `LODGE_EMAIL` / `LODGE_WEBSITE`.
- Run the `queue` container in production (`php artisan queue:restart` after each deploy).
