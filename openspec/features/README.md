# Features — petslodge-check-in

Detailed, one-file-per-feature breakdown of the PetsLodge functionality. Each file
grounds the feature in the **actual** code: routes, controllers, services, models,
Blade views, and JavaScript managers.

The high-level overview lives in [`../openspec.md`](../openspec.md); this folder is
the deeper, per-feature reference.

## Feature index

| Feature | File | Summary |
|---------|------|---------|
| Check-In | [check-in.md](check-in.md) | Multi-step (5-step) check-in flow with cookie as single source of truth. |
| Pet Profile Management | [pet-profile-management.md](pet-profile-management.md) | Detailed pet records (breed, gender, castrated, weight, health, warnings). |
| Owner (User) Management | [owner-user-management.md](owner-user-management.md) | Owner contact data + emergency contacts; phone-based lookup. |
| Services & Inventory | [services-and-inventory.md](services-and-inventory.md) | Grooming, feeding, medication schedules, and inventory items. |
| Editing Mode | [editing-mode.md](editing-mode.md) | Edit existing check-ins with change detection + snapshots. |
| Drop-In | [drop-in.md](drop-in.md) | Staff phone-lookup → PDF → physical print. |
| Pet Staff Dashboard | [pet-staff-dashboard.md](pet-staff-dashboard.md) | Drop-in, check-out, cancel, reprint actions. |
| PDF & Printing | [pdf-and-printing.md](pdf-and-printing.md) | dompdf generation + PrintNode REST print jobs. |
| Monitoring & Health | [monitoring-and-health.md](monitoring-and-health.md) | `/health` endpoints + admin monitoring dashboard. |
| Authentication | [authentication.md](authentication.md) | Laravel Breeze + Sanctum + role-based guards. |

## Reading order

If you're new to the project, read in this order:
1. **Authentication** — who can access what.
2. **Check-In** — the signature flow that drives most of the app.
3. **Pet Profile Management** + **Owner (User) Management** — the data it captures.
4. **Services & Inventory** — feeding, meds, grooming.
5. **Editing Mode** — how edits are tracked.
6. **PDF & Printing** + **Drop-In** + **Pet Staff Dashboard** — operations.
7. **Monitoring & Health** — production observability.

## Check-in status lifecycle

Shared across several features:

```
CHECKED_IN → PRINTED → DROPPED_IN → CHECKED_OUT
                                        ↘ CANCELLED (terminal)
```
