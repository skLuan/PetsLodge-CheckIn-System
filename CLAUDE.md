# CLAUDE.md — PetsLodge

Claude Code loads this file automatically. It is the **operating guide**: how to start,
how to work, how to finish, and the lessons previous sessions paid for. The **project
map** (stack, file locations, the cookie / email / signature / printing rules) is
`AGENTS.md`, imported here so there is one copy of it:

@AGENTS.md

---

## 1. Where the agent docs live

Everything written for agents is under **`ai/`** (index: `ai/README.md`):

| Path | Use it for |
|---|---|
| `ai/JOURNAL.md` | What previous sessions did, what broke, what was left alone. Newest on top. |
| `ai/plans/00-MASTER-PLAN-v1.0-beta.md` | Roadmap, phase order, status, Definition of Done. |
| `ai/plans/active/` | Plans not yet finished — pick work from here. |
| `ai/plans/done/` | Executed plans + "Execution notes". History; don't rewrite. |
| `ai/plans/archive/` | Legacy Jan–Mar 2026 data-flow refactor docs. Gitignored, local only. |
| `ai/specs/` | OpenSpec manifest + one spec per feature. Read the feature spec before touching a feature. |
| `ai/implementation/` | Implementation notes for specific changes. |

Older journal entries and done plans mention `AIplans/`, `plans/` and a root
`JOURNAL.md` — those are the pre-2026-11-06 paths; the mapping is in `ai/README.md`.

## 2. Starting a session

1. Read `ai/JOURNAL.md` (at least the latest two sessions and every **"Left for a
   human"** list).
2. Read the plan you are working on in `ai/plans/active/` and its row in the master plan.
3. **Check the environment — it has differed between sessions.** Run `docker compose ps`.
   - Stack up → use `docker compose exec app …`, app at `http://localhost:8080`.
   - Docker down → earlier sessions ran PHP 8.1 CLI + MySQL directly on the Windows host
     (`php artisan serve` → `:8000`). Don't assume; look.
4. **Before running any test**, confirm it targets `petslodge_testing`, not the dev DB.
   `RefreshDatabase` has already wiped the dev database once. If MySQL isn't up, the
   suite fails with `Access denied for user 'root'@…` — start MySQL first.
5. Note the baseline: the known-red tests are listed in `AGENTS.md` §4. The gate is
   **no new failures**.

## 3. How to work

- **Branch per plan:** `feature/NN-…` / `fix/NN-…` off `develop`. Never commit unless asked.
- **Tests first.** Every plan starts by writing/extending PHPUnit tests.
- **Test the failure branch through the entry point the user hits** (the controller /
  route), not only at the service level. Plan 07 shipped an `Undefined variable` 500 in a
  failure branch that service-level tests never reached.
- **Resolve services with `app(Foo::class)`, not `new Foo`,** so tests can swap them —
  otherwise a test can end up calling the real PrintNode API.
- **Pint only your files:** `./vendor/bin/pint path/to/File.php …`. Running it on `app/`
  reformats ~17 untouched files. (It also reports parse errors on
  `app/View/Components/forms.inventory.php` and `pop-ups.terms-conditions.php` — known,
  pre-existing, leave them.)
- **Follow the codebase over the plan text** when they disagree, and record the deviation
  in the plan's Execution notes.
- **Scope discipline:** note unrelated bugs in the journal/plan instead of fixing them.
- **Never invent business data** (lodge address/phone, copy, prices). Read it from config
  and omit it when blank — see `config/lodge.php`.
- **Agents never edit Notion.** The Notion board "Form PetsLogde v1.0" is reference only;
  a human ticks the cards.
- Repo docs and code comments are in **English**. The user often writes in Spanish —
  reply in the user's language.

## 4. Traps that already cost a session (from the journal)

- `/check-in` is only the **phone-entry** page. The real multi-step form is
  `Process.blade.php`, served at `/new-form`, `/new-form-pre-filled`, `/edit-check-in/{id}`.
- `PetStaffOnly` **redirects to `/`**, it does not `abort(403)` — don't assert 403 on staff routes.
- `PetStaffDashboardController::index()` crashes without seeded statuses — tests hitting
  the dashboard must `seed(StatusSeeder::class)`.
- `strip_tags` alone is **not** XSS-safe: remove script/style/iframe/object/embed blocks
  whole before it runs.
- `app.js` is a **deferred module**: inline Blade scripts run before it and need the
  existing `setTimeout` retry pattern to find `window.*` globals.
- The container reads **`.env.docker`**, not `.env`. A key added to `.env` is invisible
  to the app in Docker (this hid the real PrintNode key once).
- There is **no booking date range** in the DB: `check_ins.check_in` is the creation
  time and `check_out` is set only at pickup.
- `dropped_in()` sets `check_out = now()` like `checkout()` does. Looks wrong, is
  pre-existing — leave it (candidate for Plan 05).
- The check-in wizard never produces a PDF; only the drop-in flow does.
- A multi-pet booking = the owner's **open** rows (`check_out IS NULL` and not
  `CANCELLED` — cancel leaves `check_out` null): `CheckIn::scopeOpen()`. Drop-in uses
  `bookingSiblings()`, `/view-check-in` and Edit use `openBookingOf()`; one drop-in
  signature and one PDF cover every pet. Don't query `whereNull('check_out')` alone.
- A **signed** check-in can only be deleted by pet staff or above (`canBeDeletedBy()`).
- **The app is not live yet**, which is why Plan 07 could be destructive. If real
  check-ins exist, destructive migrations without backfill are wrong — stop and re-plan.

## 5. Open items waiting on a human

Check `ai/JOURNAL.md` for the current list; as of the last session:

- **Hostinger SMTP send never happened** (Plan 02 stays in `active/` until it does):
  `MAIL_PASSWORD` on the server, the `no-reply@petlogde.fun` mailbox, the cron line from
  `docs/DEPLOYMENT_GUIDE.md` §3, `migrate --force` for `jobs`, SPF/DKIM.
- `LODGE_ADDRESS` / `LODGE_PHONE` / `LODGE_EMAIL` are empty → emails have a name-only footer.
- `.env.docker` holds the **live PrintNode key**. It is gitignored — keep it that way.
- `storage/app/signatures/` is legal evidence outside the DB and git — must be writable
  and backed up in production.

## 6. Finishing a session

1. Tests: full suite, no new failures. Report the real numbers.
2. Pint on the PHP files you changed.
3. JS changes: verified in the browser, console clean (there is no JS test runner).
4. If a plan is fully done: `git mv` it to `ai/plans/done/`, append Execution notes,
   update the master plan's status column.
5. Append your session to the **top** of `ai/JOURNAL.md` — what you did, what worked,
   what didn't, what you left alone, and a "Left for a human" list.
6. If anything in `AGENTS.md` or this file went stale, fix it in the same session.
