# Sprint 3.2.5 — Scheduler Workflow Improvements

**Status:** Implemented in **3.2.5** (Live QA required before tagging)

**Goal:** Improve the scheduler as the primary day-to-day reservation app — workflow, usability, and visual clarity. No changes to Bookly’s save flow, validation, or business logic.

## Decisions

| Topic | Choice |
|-------|--------|
| Show All Tables | Investigate first; do **not** change visibility SQL unless data proves it wrong |
| Bookly colours | Use `service.color` when present; fall back to CAD type palette |

## Phase summary

### Phase 1 — Investigation & foundation

**Show All Tables (investigation)**

Code audit of the staff → columns pipeline:

```
bookly_staff → Repository::get_staff_tables() → Mapper → Provider → AJAX tables → State → calendar lanes
```

Findings:

- CAD already renders **every non-archived** Bookly staff member as a column.
- Empty tables already get empty lanes (appointments do not drive column presence).
- Party Space / Outside Studio disappear when Bookly `visibility = archive`, or when a site filter (`cad_scheduler_tables` / `cad_scheduler_staff_visibility_sql`) drops them, or (historically) when a stale CDN UI ignored AJAX `tables`.
- **No CAD visibility-rule change** in this sprint. If live diagnostics show them as archived, un-archive in Bookly — do not change Bookly behaviour from CAD.

Live check:

```php
add_filter( 'cad_scheduler_diagnostics_enabled', '__return_true' );
```

Reload the scheduler and inspect the yellow staff pipeline report (or `CAD.API.debugStaffPipeline()`).

**Staff schedules**

- `CAD_Bookly_Repository::get_staff_schedules_for_date()` reads `bookly_staff_schedule_items` (+ `bookly_schedule_item_breaks` when present).
- Schedule payload includes `staffSchedules: { [staffId]: [{ start, end }, ...] }`.
- Day off / missing hours → empty interval list (full grey column for that day).

**Service colours**

- Appointments include `color` from `bookly_services.color` when available.
- Cards apply Bookly hex via `--cad-type-accent` / wash; otherwise keep type palette.

### Phase 2 — Scheduler UI

- Grey outside-schedule bands (pointer-events: none).
- Outside-hours warning on Quick Add / Reservation Manager save / drag: *This reservation falls outside this table's normal operating schedule.* → **Continue** / **Cancel**. Does not block Bookly save.
- Stronger hour lines (`.cad-matrix__line--hour`) and table column separators.
- Popover restyled to match Reservation Manager; Bookly Edit/Delete redirects removed; **Edit Reservation** opens RM.

### Phase 3 — Reservation Manager

- Empty customer fields: placeholder `—` + *Customer information not available.* Still editable and saveable.
- Footer: **Delete Reservation** | **Cancel** | **Save Reservation**.
- Delete confirm → `cad_delete_reservation` → Bookly `Entities\Appointment::delete()`. No Bookly calendar redirect.

### Phase 4 — Release

- Pin `CAD_SCHEDULER_VERSION` to **3.2.5** after the Git tag is on jsDelivr.
- Redeploy snippets **10** (Repository), **11** (Mapper), **12** (Provider), **20** (Bridge).

## Live QA (required before tagging)

- [ ] Party Space and Outside Studio render if active (non-archived) in Bookly
- [ ] Grey schedule overlay matches Bookly Staff schedules
- [ ] Outside-hours warning appears but Continue still saves
- [ ] Delete removes the reservation and refreshes the scheduler
- [ ] Bookly `service.color` used when configured; CAD type colours as fallback
- [ ] Quick Add, Drag & Drop, Reservation Manager Save, and Status updates still work

## Acceptance

- [x] All non-archived Bookly tables/resources are visible (empty columns remain)
- [x] Placeholder customer information displays correctly
- [x] Staff schedules visually indicated; outside-schedule warning allowed
- [x] Popover matches Reservation Manager styling
- [x] Hour and table separators improved
- [x] Reservation Manager handles edit/delete without opening Bookly
- [ ] Live QA checklist above
