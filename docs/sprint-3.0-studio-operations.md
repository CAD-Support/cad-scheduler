# Sprint 3.0 — Studio Operations (Planning)

**Status:** Phase 1 **complete** — P1 Drag & Drop (**2.7.8**) + P2 Quick Add (**3.1.0**). Phase 2 (Undo / Filters / Live Refresh / Lightspeed) not started.

**Goal:** Deliver a scheduler studios can use for **daily operations** before project handoff — view, create, move, edit, and update appointments **without opening the native Bookly calendar**.

## P2 complete — Quick Add (3.1.0)

See [sprint-3.1-quick-add.md](sprint-3.1-quick-add.md).

## P1 complete — Drag & Drop (2.7.8)

Release notes:

- Fixed scheduler timezone rendering.
- Bookly wall-clock times now render correctly.
- Drag-and-drop between tables and times is fully functional.
- Scheduler and Bookly backend remain synchronized.
- Removed all temporary debugging instrumentation.

Pin `CAD_SCHEDULER_VERSION` to **2.7.8** and redeploy snippets **11**, **12**, and **20**.

## Reprioritization (2026-07-27)

Immediate focus is **Phase 1 (Critical)** only. Do not start Phase 2 work until Phase 1 is **complete and production-ready**.

| Phase | Priority | Issue | Estimate | Depends on |
|-------|----------|-------|----------|------------|
| **1 · Critical** | **P1** Drag & Drop | [#3](https://github.com/CAD-Support/cad-scheduler/issues/3) | XL · ~20–32h · 13 pts | — |
| **1 · Critical** | **P2** Quick Add | [#4](https://github.com/CAD-Support/cad-scheduler/issues/4) | L · ~12–20h · 8 pts | Soft: P1 write helpers |
| **2 · After Phase 1** | **P3** Undo | [#2](https://github.com/CAD-Support/cad-scheduler/issues/2) | S–M · ~4–8h · 3 pts | Soft: P1/P2 write paths |
| **2 · After Phase 1** | **P4** Filters | [#7](https://github.com/CAD-Support/cad-scheduler/issues/7) | S · ~3–6h · 2 pts | Soft: P5 (re-apply) |
| **2 · After Phase 1** | **P5** Live Refresh | [#6](https://github.com/CAD-Support/cad-scheduler/issues/6) | S–M · ~4–8h · 3 pts | Soft: P4 |
| **2 · After Phase 1** | **P6** Lightspeed lookup | [#5](https://github.com/CAD-Support/cad-scheduler/issues/5) | M · ~8–12h · 5 pts | **Hard: P2** |

**Suggested build order:** **P1** (backend + DnD UI) → **P2** → then **P3 → P4 → P5 → P6**.

Labels: `sprint-3.0`, `enhancement`, `size/S|M|L|XL`.

### Phase 1 non-negotiable: Bookly save/create flows

P1 and P2 **must** use Bookly’s existing **save / create** flows wherever possible so:

- notifications (SMS/email)
- validation
- Google Calendar / other Bookly integrations

continue to fire. Prefer Bookly PHP/AJAX APIs over raw `$wpdb` writes. Document any unavoidable direct DB updates in the implementation PR.

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

## P1 implementation notes (Drag & Drop)

### Bookly save path (research)

See [Appointment save path](bookly-reference-map.md#appointment-save-path-admin-edit).

| Layer | Detail |
|-------|--------|
| Admin AJAX | `bookly_save_appointment_form` → `Edit\Ajax::saveAppointmentForm()` |
| Core API | `Bookly\Lib\Utils\Appointment::save()` / `::checkTime()` |
| Sync | `Common::syncWithCalendars()` inside `save()` (Google / Outlook) |
| Notify | CAD calls `Sender::sendForCA` after save (avoids admin notification-queue UI) |

### CAD surfaces

| Surface | Role |
|---------|------|
| `CAD_Schedule_Provider::update_appointment` | checkTime → save → optional notify; returns structured result |
| `wp_ajax_cad_update_appointment` | Thin bridge: auth, sanitize, Provider, JSON |
| `CAD.API.updateAppointment` | Front-end wrapper |
| `CAD.DnD` + `CAD.notify` | Drag between lanes/times; optimistic UI; revert on failure |
| Fixture | [`docs/fixtures/sprint-3.0-dnd.html`](fixtures/sprint-3.0-dnd.html) |

### Behaviour

- Duration preserved from Bookly `end_date - start_date` (extras duration unchanged).
- Hard-block when `date_interval_not_available` (no silent overwrite).
- Filter `cad_scheduler_reschedule_notify` (default `true`).
- Single selected day only (multi-day drag deferred).

### Still out of scope for P1

- Quick Add / create (`cad_create_appointment`)
- Undo toast stack (P3)
- Collaborative/compound multi-segment move edge cases (document if encountered live)

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

### Sprint 3 status

| Surface | Status |
|---------|--------|
| `wp_ajax_cad_update_appointment` | **P1** — Bookly `Utils\Appointment::save` |
| CAD create appointment | **Missing** — P2 |
| Customer search | **Missing** — P6 |
| Live poll / filters | **Missing** — P5 / P4 |

### Proposed new CAD AJAX actions (additive)

| Action | Priority | Provider responsibility |
|--------|----------|-------------------------|
| `cad_update_appointment` (real) | **P1** | Reschedule via Bookly save; conflict check; notifications |
| `cad_create_appointment` | **P2** | Create via Bookly create (+ custom fields/painters) |
| `cad_search_customers` (name TBD) | **P6** | LS search + Bookly fallback |

Bridge handlers must stay thin: nonce, capability, sanitize, call Provider, `wp_send_json_*`.

### Suggested `src/` modules

| Module | Priority |
|--------|----------|
| `CAD.DnD` + `CAD.notify` | **P1** (shipped in tree) |
| `CAD.QuickAdd` dialog | **P2** |
| `CAD.Toast` / undo stack | **P3** |
| `CAD.Filters` + toolbar | **P4** |
| `CAD.LiveRefresh` | **P5** |
| Customer lookup field (in Quick Add) | **P6** |

---

## Fixture expectations (per priority)

| Priority | Fixture |
|----------|---------|
| P1 | `docs/fixtures/sprint-3.0-dnd.html` |
| P2 | `docs/fixtures/sprint-3.0-quick-add.html` |
| P3 | `docs/fixtures/sprint-3.0-undo.html` |
| P4 | `docs/fixtures/sprint-3.0-filters.html` |
| P5 | Manual QA checklist (optional JS mock) |
| P6 | `docs/fixtures/sprint-3.0-customer-lookup.html` |

---

## Out of scope for Sprint 3.0 kickoff / Phase 1

- Implementing Phase 2 (P3–P6) before Phase 1 is production-ready
- Removing CDN shim / TEMP DEBUG
- Putting business logic in the bridge
- Full Lightspeed customer **create** (document as future; lookup is P6)
- Multi-studio location filter (still Phase 3 roadmap item beyond this sprint’s P-list)

---

## Related docs

- [Roadmap](roadmap.md) — Phase 2 editing + Sprint 3.0 priorities
- [Changelog](changelog.md) — planned Sprint 3.0 section
- [Bookly reference map](bookly-reference-map.md)
- Sprint 2.5 normalized model / popover (status write path foundation)
