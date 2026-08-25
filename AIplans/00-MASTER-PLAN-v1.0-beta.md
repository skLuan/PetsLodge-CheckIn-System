# PetsLodge — Master Plan to v1.0-beta

> Audience: humans AND AI agents (Claude Code). Read `AGENTS.md` first for the project map.
> Source of truth for scope: Notion board "Form PetsLogde v1.0" (reference only — do NOT edit Notion from code sessions).
> Notion view: https://well-ray-e7f.notion.site/2bfa050d37e481d2bc17fc714b3e0b69?v=31ca050d37e480f28652000c5c3487a4

## Goal

Ship **v1.0-beta**: all features below implemented, tested, and tagged. From that tag onward, every feature/fix is tracked against v1.0-beta.

## Workflow conventions (humans + agents)

1. **Plans live in `AIplans/`.** One file per feature. Each plan is self-contained: architecture, files to touch, migrations, tests, acceptance criteria.
2. **Executed plans move to `plans/`.** When a plan is fully done, move its file to `plans/` and append an "Execution notes" section (what was done, deviations, gotchas). `plans/` is the historical record.
3. **Test-first.** Every plan starts by writing/extending PHPUnit tests (`tests/Feature`, `tests/Unit`). Run with `docker compose exec app php artisan test`. A plan is not done with failing tests.
4. **Journal.** Append your session to **`JOURNAL.md`** (repo root) — what you did, what
   worked, what didn't, what you deliberately left alone. Read it before starting.
   Detailed per-plan notes still go in the plan's "Execution notes" section.
   ⚠️ `plans/` is **gitignored** — archive with `git mv` (not `mv`) so the plan stays
   tracked. `JOURNAL.md` is the always-tracked record.
5. **Keep `AGENTS.md` fresh** — if a change makes it stale, update it in the same session.
6. **Git:** one branch per plan (`feature/01-terms-dashboard`, `fix/05-add-pet-edit-mode`). Merge to main when acceptance criteria pass. Tag `v1.0-beta` when Phases 1–4 are merged.
7. **Human-readable code**, commented, docs in `docs/` updated when APIs change.

## Phases and order of execution

| Phase | Plan file | What | Who | Status |
|---|---|---|---|---|
| 1 | `01-terms-conditions-dashboard.md` → `plans/` | T&C: migration, editable from pet-staff dashboard (`petstaff/*`) | Us | **Done** 2026-08-04 (`feature/01-terms-dashboard`) |
| 2 | `02-transactional-emails.md` | Emails via Hostinger SMTP: check-in, drop-in, drop-out + templates | Us | **Code complete** 2026-08-04 (`feature/02-transactional-emails`) — verified over real SMTP (mailpit); **awaiting Hostinger credentials** for the production send, then archive to `plans/` |
| 3 (parallel) | `05-cookie-stabilization-bugs.md` | Cookie system stabilization + backend/UI bugs (incl. add-pet stuck in edit) | Mid dev | Pending |
| 4 | `03-signature-module.md` → `plans/` | Signature pad for drop-in, stored in storage + URL in DB | Us | **Done** 2026-08-24 (`feature/03-signature-module`) — dedicated `signatures` table (not a `users` column), private disk, drop-in gated on signing. ⚠️ **Step 7 (printing config) still open for production**: PrintNode API key not yet held, and a privacy decision is pending on publicly-fetchable PDFs. Local flow unblocked via `PRINTNODE_FAKE=true`. |
| 5 | `04-multi-pet-health-food.md` | Per-pet warnings/health + per-pet food (currently single-pet only) | Us | Pending |
| 6 | `06-ui-polish-quick-wins.md` | Small Notion tasks: wording, misspellings, discount color, checkout ID, check-in time, pro check-in ID, branding | Anyone / fill-in work | Pending |

Phases 1→2 are sequential for us; Phase 3 runs in parallel (mid dev). 4–6 follow. In-progress Notion items ("Cambio de hora en el check in" — Simon) stay with their owners; they're listed in `06` for tracking only.

## Definition of Done (every plan)

- [ ] Tests written first and passing (`php artisan test`)
- [ ] Migrations reversible (`migrate:rollback` works)
- [ ] No regressions in the check-in flow (run `tests/Feature/CheckInSubmissionTest.php`)
- [ ] Docs updated (`docs/`, `AGENTS.md` if structure changed)
- [ ] Plan file moved `AIplans/` → `plans/` with Execution notes
- [ ] Notion task updated by a human (agents don't touch Notion)

## Versioning

- Work happens on feature branches off `main`.
- When Phases 1–4 land: `git tag v1.0-beta`. Bugs found after tagging are tracked as `v1.0-beta` fixes; new scope goes to a future `v1.1` plan file.

## Architecture snapshot (for agents landing cold)

- Laravel 10, PHP 8.1, MySQL 8 (Docker, app on :8080), Redis, Breeze+Sanctum.
- Check-in form state = cookie `pl_checkin_data` (single source of truth), managed by `resources/js/cookies-and-form/` (CookieManager, FormDataManager, managers/*, reactivitySystem/*).
- API: `routes/api.php` → `CheckInApiController` (step1–step5 sequential submission endpoints).
- Staff flows: `routes/web.php` → `DropInController`, `PetStaffDashboardController` (dropped_in / checkout / cancel / reprint).
- Admin dashboard (`/dashboard`, middleware `admin.only`) is a Breeze stub; the working staff UI is `/pet-staff/dashboard` (`pet.staff.only`) — Phase 1 hangs the T&C editor off it under `pet-staff/*`.
- Models: User, Pet, CheckIn, Food, Medicine, Item, ExtraService, EmergencyContact, Status, etc.
