# Sprint 3.0 — Studio Operations (Planning)

**Status:** Planned — kickoff only. **Implementation has not started.**

**Goal:** Make CAD Scheduler the primary day-to-day scheduling UI for studio staff so most appointment management no longer requires the Bookly calendar.

## GitHub issues

| Priority | Issue | Estimate | Depends on |
|----------|-------|----------|------------|
| **P0** Undo | [#2](https://github.com/CAD-Support/cad-scheduler/issues/2) | S–M · ~4–8h · 3 pts | — |
| **P1** Drag & Drop | [#3](https://github.com/CAD-Support/cad-scheduler/issues/3) | XL · ~20–32h · 13 pts | Soft: P0 |
| **P2** Quick Add | [#4](https://github.com/CAD-Support/cad-scheduler/issues/4) | L · ~12–20h · 8 pts | Soft: P1 write helpers |
| **P3** Lightspeed lookup | [#5](https://github.com/CAD-Support/cad-scheduler/issues/5) | M · ~8–12h · 5 pts | **Hard: P2** |
| **P4** Live Refresh | [#6](https://github.com/CAD-Support/cad-scheduler/issues/6) | S–M · ~4–8h · 3 pts | Soft: P5 |
| **P5** Filters | [#7](https://github.com/CAD-Support/cad-scheduler/issues/7) | S · ~3–6h · 2 pts | Soft: P4 (re-apply) |

**Suggested build order:** P0 → P5 (parallel-friendly) → P1 backend → P1 DnD UI → P2 → P3 → P4 (or P4 earlier with stub filter state).

Labels: `sprint-3.0`, `enhancement`, `size/S|M|L|XL`.

---

## Architecture constraints (non-negotiable)

1. **Permanent logic in `src/` only** (plus additive Provider/Repository in `includes/` for Bookly I/O).
2. **Bridge** (`snippets/10A-ajax-bridge.php`): enqueue, localization, AJAX bootstrap only — **no business logic**.
3. **Bookly = source of truth** for appointments, customers, statuses, notifications.
4. Modular components, a11y, Crock A Doodle styling.
5. Avoid duplicate business logic (one conflict checker, one toast/undo stack, one API wrapper).
6. Prefer **additive** Provider/Repository APIs; do not put presentation logic in PHP.
7. Do **not** remove CDN shim / TEMP DEBUG as part of this sprint’s feature work.

---

## Bookly / CAD API research summary

### Already shipped (reuse)

| Surface | Location | Role |
|---------|----------|------|
| `wp_ajax_cad_get_schedule` | Bridge → `CAD_Schedule_Provider::get_schedule` | Read day schedule |
| `wp_ajax_cad_update_appointment_status` | Bridge → Provider → Repository | Writes `bookly_customer_appointments.status` |
| `CAD.API.getSchedule` / `updateAppointmentStatus` | `src/cad-api.js` | Front-end wrappers |
| Status UI | `src/components/status-panel.js`, popover `setStatus` | Optimistic status + re-render |
| Delete / Edit links | Popover actions | Open Bookly manage URL — **no CAD delete API** |
| Normalized model | Mapper / Sprint 2.5 | `type`, nests, `status`, `painters`, etc. |
| Filters (WP) | `cad_scheduler_*` | Custom fields, appointment shape — **not** UI toolbar filters |

### Stub / incomplete (Sprint 3 must replace or extend)

| Surface | Current behavior | Sprint 3 need |
|---------|------------------|---------------|
| `wp_ajax_cad_update_appointment` | Returns `{ updated: true }` **without DB write** | Real reschedule (staff/time) for **P1** |
| CAD create appointment | **Missing** | **P2** `cad_create_appointment` (proposed) |
| CAD delete appointment | **Missing** (Bookly deep-link only) | Optional later; P0 should leave undo hooks for delete |
| Customer search | **Missing** | **P3** LS + Bookly fallback |
| Live poll / filters | **Missing** | **P4** / **P5** client-side |

### Bookly references to inspect before write paths

From [`docs/bookly-reference-map.md`](bookly-reference-map.md):

1. **Core** `Backend\Components\Dialogs\Appointment\Edit\Ajax` — admin create/save (extract core ZIP / live WP).
2. **Customer Cabinet** `saveReschedule` / `getDaySchedule` — reschedule patterns.
3. **Pro** `Lib\ProxyProviders\Shared` — notifications / query prep.
4. **Pro** ModernBookingForm save — validation + notification reference (not necessarily call path).
5. Entities / tables: `bookly_appointments`, `bookly_customer_appointments`, `bookly_customers`, `bookly_services`, `bookly_staff`.

**Unknowns (document in implementation PRs):**

- Whether CAD should call Bookly PHP APIs vs `$wpdb` so SMS/email/Google sync fire.
- Collaborative / compound appointment move rules.
- Lightspeed: **no in-repo client**; discover site plugin/credentials during P3 spike.
- Multi-day visible range: CAD is currently **single selected day** — P1 “drag to another day” may mean date-nav + drop target or defer until multi-day UI.

### Proposed new CAD AJAX actions (additive)

| Action | Priority | Provider responsibility |
|--------|----------|-------------------------|
| `cad_update_appointment` (real) | P1 | Reschedule staff/start/end; conflict check; optional notify |
| `cad_create_appointment` | P2 | Create appointment + customer_appointment (+ custom fields/painters) |
| `cad_search_customers` (name TBD) | P3 | LS search + Bookly fallback |
| (optional) `cad_delete_appointment` | Later | Soft-delete / Bookly cancel — not required for P0 toast design |

Bridge handlers must stay thin: nonce, capability, sanitize, call Provider, `wp_send_json_*`.

### Suggested `src/` modules

| Module | Priority |
|--------|----------|
| `CAD.Toast` / undo stack | P0 |
| `CAD.DnD` + calendar bindings | P1 |
| `CAD.QuickAdd` dialog | P2 |
| Customer lookup field (in Quick Add) | P3 |
| `CAD.LiveRefresh` | P4 |
| `CAD.Filters` + toolbar | P5 |
| Extend `CAD.API` only | all |

---

## Fixture expectations (per priority)

| Priority | Fixture |
|----------|---------|
| P0 | `docs/fixtures/sprint-3.0-undo.html` |
| P1 | `docs/fixtures/sprint-3.0-dnd.html` (+ optional PHP conflict fixture) |
| P2 | `docs/fixtures/sprint-3.0-quick-add.html` |
| P3 | `docs/fixtures/sprint-3.0-customer-lookup.html` |
| P4 | Manual QA checklist (optional JS mock) |
| P5 | `docs/fixtures/sprint-3.0-filters.html` |

---

## Out of scope for Sprint 3.0 kickoff

- Implementing P0–P5 features
- Removing CDN shim / TEMP DEBUG
- Putting business logic in the bridge
- Full Lightspeed customer **create** (document as future)
- Multi-studio location filter (still Phase 3 roadmap item beyond this sprint’s P-list)

---

## Related docs

- [Roadmap](roadmap.md) — Phase 2 editing + Sprint 3.0 priorities
- [Changelog](changelog.md) — planned Sprint 3.0 section
- [Bookly reference map](bookly-reference-map.md)
- Sprint 2.5 normalized model / popover (status write path foundation)
