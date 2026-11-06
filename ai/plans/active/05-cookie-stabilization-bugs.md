# Plan 05 — Cookie system stabilization + related bugs

**Phase 3 (parallel) · Owner: mid dev · Branch: `fix/05-cookie-stabilization`**

## Scope

The cookie-based form state (`pl_checkin_data`) is the app's backbone and has UI bugs. This plan covers stabilization plus the concrete bugs that live in that layer.

Read first: `CHECKIN_COOKIE_REFACTOR_README.md`, `AGENTS.md` §cookie flow, `resources/js/cookies-and-form/` (CookieHandler, CookieManager, FormDataManager, form-processor, managers/*, reactivitySystem/*).

## Bug 1 — Add pet stays in edit mode (Notion: "Bug: al añadir mascota se queda en modo edit")

**Symptom:** "Add pet" creates the pet but the form remains in edit mode for that pet, so adding another pet **replaces** the first instead of appending.

**Where to look:** `managers/EditingModeManager.js`, `PetManager.js`, `PetPillManager.js`. Likely cause: after save, the active-pet index / `editingMode` flag isn't reset, so the next "add" writes to the same index.

**Fix outline:** on successful add → exit editing mode, clear form, set state to "new pet"; "edit" only via explicit pill action. Add a state assertion (log/throw in dev) when `addPet` is called while `editingMode === true`.

**QA:** add 3 pets consecutively → 3 pills, 3 distinct cookie entries; edit pet 2 → saves in place; add pet 4 → appends.

## Bug 2 — General cookie/UI stabilization

Systematic pass, not whack-a-mole:

1. **Inventory the failure modes** (collect from team + reproduce): stale UI after cookie write, lost fields on reload, race between autosave (`/api/checkin/autosave`) and cookie state, pill/summary desync.
2. **Single write path:** all writes go through `FormDataManager` → `CookieManager`; grep for rogue `document.cookie` usage and route through the manager.
3. **Schema validation on load:** `CookieManager` load → validate shape (version key!); on invalid, quarantine (`pl_checkin_data_backup`) + reset cleanly instead of half-loading. Add `schemaVersion` field to the cookie if absent — Plan 04 depends on this.
4. **Cookie size guard:** cookies cap at ~4KB/cookie. Multi-pet + food + inventory may overflow → silent truncation = "random" data loss. Measure real payload; if near limit, plan migration to `localStorage` mirror or server-side session (`/api/update-session-checkin` already exists) — decision recorded here.
5. **Reactivity audit:** `reactivitySystem/CookieReactivityManager.js` — ensure every UI region subscribes; fix regions updated manually.

## Bug 3 — Backend counterparts

- Verify step endpoints (`submitUserInfo` … `submitExtraInfo`) are idempotent — resubmitting a step (retry after network blip) must not duplicate pets/food/medicine rows. Add `updateOrCreate` keyed appropriately where missing.
- `autoSaveCheckIn` vs `submitCheckIn` interplay: confirm autosave can't resurrect deleted data.

## Tests

- `tests/Feature/CheckInSubmissionTest.php`: add idempotency cases (same step payload twice → same row count).
- Manual QA matrix (browsers: Chrome + Safari mobile — the form is used on tablets):
  - full flow 1 pet / 3 pets / with+without extra services
  - reload at every step
  - back-navigation between steps
  - add→edit→delete pet cycles
  - cookie deleted mid-flow → graceful reset, no console errors

## Definition of done

- [ ] Bug 1 fixed with QA steps passing.
- [ ] Cookie schema versioned + validated on load; corrupt cookie recovers gracefully.
- [ ] Size audit documented (actual bytes for a 3-pet check-in) + decision if migration needed.
- [ ] Idempotent step endpoints with tests.
- [ ] Findings written into `CHECKIN_COOKIE_REFACTOR_README.md`.
