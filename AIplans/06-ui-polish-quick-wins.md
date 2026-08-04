# Plan 06 — UI polish & quick wins (small Notion tasks)

**Phase 6 · Fill-in work between bigger plans · Branch: `chore/06-ui-polish` (or one branch per item)**

Small tasks from the Notion board "Form PetsLogde v1.0". Each is ≤ a few hours. Good first tasks for any dev/agent; each needs a before/after screenshot in the PR.

| # | Notion task | Notes / where to look | Status on board |
|---|---|---|---|
| 1 | Cambio de hora en el check in | Owned by **Simon Escobar** (In Progress) — do not duplicate; coordinate | In Progress |
| 2 | Drop inn change word in dashboard | Wording fix: "Drop inn" → "Drop-in" (or agreed copy). Grep `resources/views/` + `resources/js/` for `inn` | Not Started |
| 3 | Bug: eliminar `?` del formato | Stray "?" rendering in a formatted string — grep templates/JS for the literal, check date/string formatting in summary & print views | Not Started |
| 4 | Revisar misspelling en check-in confirmation | Proofread `Drop-in-confirmation.blade.php`, `CheckInSummary.blade.php`, confirmation popups/emails | Not Started |
| 5 | Discount con color para llamar la atención | Highlight discount amount in summary/checkout UI — use existing Tailwind accent (see branding tokens; coordinate with #8) | Not Started |
| 6 | Remover id check-in en checkout | Hide internal check-in ID from the checkout screen (`PetStaffDashboardController::checkout` view / dashboard blade) | Not Started |
| 7 | Crear checkIn ID mas pro | Human-friendly check-in code (e.g. `PL-2026-000123`) instead of raw autoincrement. Needs tiny design: column `public_code` on `check_ins` + generator; used on prints/emails. **On Pause** — confirm before starting | On Pause |
| 8 | Ajustar branding (colores) | Color pass over Tailwind config / CSS variables. **On Pause** — needs brand decision from Lamb | On Pause |

## Rules

- One commit per item, message referencing the Notion task name.
- Items 7 & 8 are paused on the board — require explicit go-ahead.
- Copy changes in English reviewed by a native/fluent speaker before merge.
- After each item: human ticks the Notion task (agents never edit Notion).

## Definition of done

- [ ] Items 2–6 merged with screenshots.
- [ ] 1 confirmed done by Simon.
- [ ] 7–8 either done (if unpaused) or explicitly deferred to v1.1 in the master plan.
