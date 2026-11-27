# ai/ — Everything written for AI agents

This folder centralizes the documents that exist to steer AI coding agents (Claude Code,
Kilo Code, …) and the humans directing them. Human-facing developer docs stay where they
were (`README.md`, `docs/`, `DOCKER_README.md`, `DEPLOYMENT_LOG.md`,
`CHECKIN_COOKIE_REFACTOR_README.md`).

The two entry points stay at the repo root, because that is where tools look for them:

- **`CLAUDE.md`** — loaded automatically by Claude Code. Imports `AGENTS.md`.
- **`AGENTS.md`** — the project map, read by any agent.

## Layout

```text
ai/
├── README.md                     ← this file
├── JOURNAL.md                    ← running log of agent sessions (newest on top, append-only)
├── plans/
│   ├── 00-MASTER-PLAN-v1.0-beta.md  ← roadmap: phases, status, Definition of Done
│   ├── active/                   ← plans not yet finished (02, 04, 04b, 05, 06)
│   ├── done/                     ← executed plans + their "Execution notes" (01, 03, 07)
│   └── archive/                  ← legacy Jan–Mar 2026 data-flow refactor docs (gitignored)
├── specs/
│   ├── openspec.md               ← OpenSpec project manifest
│   └── features/                 ← one spec per feature (check-in, drop-in, printing, …)
└── implementation/               ← implementation notes / specs for specific changes
```

## Plan workflow

1. A new plan starts in `plans/active/` as `NN-short-name.md` — self-contained:
   problem, architecture, files to touch, migrations, tests, acceptance criteria.
   Add it to the phase table in `00-MASTER-PLAN-v1.0-beta.md`.
2. Work on a branch named after it (`feature/NN-…`, `fix/NN-…`). Tests first.
3. When **all** acceptance criteria pass, `git mv` it to `plans/done/` and append:

   ```markdown
   ## Execution notes (YYYY-MM-DD)
   - What was actually done (deviations from the plan and why)
   - What worked / what didn't
   - Gotchas for future sessions
   - Related commits / PRs
   ```

4. Update the master plan's status column and append the session to `JOURNAL.md`.

Files in `plans/done/` are the historical record — edit them only to fix factual errors.
`JOURNAL.md` is append-only. Both still mention the old paths (`AIplans/`, `plans/`,
root `JOURNAL.md`); that is history, not a bug — the mapping is below.

## Where things used to be (moved 2026-11-06)

| Old path | New path |
|---|---|
| `JOURNAL.md` | `ai/JOURNAL.md` |
| `AIplans/00-MASTER-PLAN-v1.0-beta.md` | `ai/plans/00-MASTER-PLAN-v1.0-beta.md` |
| `AIplans/0N-*.md` | `ai/plans/active/0N-*.md` |
| `plans/01-…`, `plans/03-…`, `plans/07-…` | `ai/plans/done/` |
| `plans/*` (DataFlow*, PHASE_*, …), `plans/notebook/` | `ai/plans/archive/` |
| `.kilo/plans/1780534251648-stellar-engine.md` | `ai/plans/active/04b-health-form-per-pet-draft.md` |
| `openspec.md`, `openspec/features/` | `ai/specs/openspec.md`, `ai/specs/features/` |
| `implementation/*.md`, `view-check-in-plan.md` | `ai/implementation/` |
