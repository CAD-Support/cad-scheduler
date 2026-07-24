# Sprint 1.6 — Bentonville Parity Verification

**Objective:** Verify that CAD Scheduler displays the same appointment data as Bookly for a real studio before adding new functionality.

**Scope:** Code review and documented verification plan. No Bookly modifications. No CAD code changes in this sprint — remaining gaps are intentional, edge-case, or require validation against live Bentonville data before fixing.

**Related docs:** [Bookly Reference Map](bookly-reference-map.md) · [Deployment](deployment.md) · [Sprint 1.7 — Live Validation](sprint-1.7-live-validation.md)

---

## Data flow (verified)

```
bookly_* tables
    ↓  SQL (includes/class-cad-bookly-repository.php)
CAD_Bookly_Repository
    ↓  map_staff_tables / map_appointments
CAD_Bookly_Mapper
    ↓  get_tables / get_schedule
CAD_Schedule_Provider
    ↓  wp_ajax_cad_get_schedule
snippets/10A-ajax-bridge.php
    ↓  CAD.API.getSchedule(date)
cad-ui.js → cad-calendar.js → cad-components.js
```

**Bookly reference path (admin calendar):**

```
Staff::query()->whereNot('visibility', 'archive')
    ↓  getAppointmentsQueryForCalendar() — overlap + staff filter
    ↓  buildAppointmentsForCalendar() — dedupe by a.id, aggregate customers[], colors
Bookly wp-admin calendar
```

**Reference files:**

| Layer | File |
|-------|------|
| Repository | `includes/class-cad-bookly-repository.php` |
| Mapper | `includes/class-cad-bookly-mapper.php` |
| Provider | `includes/class-cad-schedule-provider.php` |
| AJAX / bootstrap | `snippets/10A-ajax-bridge.php` |
| API (JS) | `src/cad-api.js` |
| UI | `src/cad-ui.js`, `src/cad-calendar.js`, `src/cad-components.js` |
| Bookly Pro calendar | `bookly-reference/bookly-addon-pro-10.2/.../frontend/modules/calendar/Ajax.php` |
| Bookly core calendar | `bookly-reference/core-extract/.../backend/modules/calendar/Page.php` |

---

## Verification checklist

| Item | Status | Notes |
|------|--------|-------|
| Appointment IDs | **Pass** | `a.id` → `id` (string) |
| Customer names | **Pass** | `full_name` or `first_name + last_name` via SQL COALESCE |
| Service names | **Partial** | CAD uses `s.title` only. Bookly uses `COALESCE(s.title, a.custom_service_name)` — custom-service appointments may show blank in CAD |
| Staff (Tables) | **Pass** | `staff_id` → `tableId`; `full_name` → column header |
| Appointment status | **Pass** | `ca.status` → `status` (CSS class suffix) |
| Start time | **Pass** | `a.start_date` → ISO 8601 via `wp_timezone()` |
| End time (+ extras) | **Pass** | `DATE_ADD(a.end_date, INTERVAL COALESCE(a.extras_duration, 0) SECOND)` — matches Bookly; mapper does not recalculate in JS |
| Colours | **Intentional difference** | Bookly: service/staff/status hex from DB + `bookly_cal_coloring_mode`. CAD: fixed CSS palette by status slug |
| Multi-customer appointments | **Partial** | Both dedupe to one block per `a.id`. Bookly aggregates all customers; CAD keeps **first** `customer_appointments` row only |
| Empty days | **Pass** | Empty `appointments[]`; grid renders with no blocks |
| Multiple appointments, same table | **Pass** | All rows returned; UI filters by `tableId` per lane |
| Archived staff excluded | **Pass** | `visibility != 'archive'` on staff query — matches Bookly calendar |
| Private staff included | **Pass** | No `private` filter — matches Bookly admin calendar |
| Timezone handling | **Partial** | CAD: WP timezone → ISO → browser `toLocaleTimeString`. Bookly: optional conversion to current user's display timezone |
| Duplicate appointment prevention | **Pass** | Repository dedupes by appointment ID before mapper |

---

## Parity findings

### Aligned with Bookly (core read path)

- Repository SQL mirrors Pro/backend calendar for end time, status source, and staff visibility.
- One calendar block per appointment when multiple `customer_appointments` rows exist.
- Staff ordering: `position ASC, full_name ASC`.
- Mapper end-time fallback (`start + service_duration`) when computed end is null — defensive, same intent as Bookly.
- UI positioning uses ISO `start`/`end` only — no duplicate duration math in JavaScript.

See [End Time Calculation](bookly-reference-map.md#end-time-calculation) in the reference map for the permanent SQL parity note.

### Verified differences

| Area | Bookly | CAD | Severity for Bentonville |
|------|--------|-----|--------------------------|
| **Colours** | Dynamic hex (service/staff/status mode) | Status-based CSS classes | Visual only — confirm staff accept CAD styling |
| **Multi-customer** | All participants in tooltip/title | First customer name only | **High** if studio uses group bookings |
| **Custom services** | `custom_service_name` fallback | Missing | **Medium** if studio uses ad-hoc services |
| **Timezone display** | User display TZ conversion | WP TZ + browser local | **Low** if WP site TZ matches staff browsers |
| **Date filter** | Range overlap + visible staff IDs | `DATE(start_date) = YYYY-MM-DD` | **Low** for normal same-day bookings; edge cases at midnight |
| **Busy-status filter** | Optional frontend filter | Shows all statuses | **Low** unless Bookly hides cancelled/rejected on calendar |
| **Write path** | Full Bookly save | `cad_update_appointment` stub | Out of scope for read parity |

---

## Remaining known differences

### Intentional (documented)

- Frontend diagnostics vs wp-admin notices — see [Architectural Decisions](bookly-reference-map.md#architectural-decisions).
- CAD visual design (matrix grid, status CSS) vs Bookly FullCalendar colours.
- Read-only v2 — no appointment editing.

### Data parity gaps (fix only if Bentonville confirms impact)

1. `custom_service_name` not in repository SELECT.
2. Multi-customer: first row wins on dedup (Bookly accumulates `customers[]`).
3. No Bookly `bookly_cal_coloring_mode` / Custom Statuses colour proxy.
4. No per-user timezone conversion (Bookly `DateTime::convertTimeZone`).
5. Appointments on archived staff: fetched but no column — same effective result as Bookly (staff not on calendar).

---

## Files changed (Sprint 1.6)

**None.** Verification and documentation only.

---

## Testing procedure (Bentonville)

### Prerequisites

- All 4 Code Snippets active (priorities 10, 11, 12, 20) — see [Deployment](deployment.md).
- `[cad_scheduler]` on a login-protected staff page.
- Bookly wp-admin calendar open for the same date.

### Side-by-side comparison

1. Pick a **busy day**, a **quiet/empty day**, and a day with **extras** (service extras affecting block height).
2. For each appointment on the Bookly calendar, locate the same ID on the CAD grid:
   - Same table (staff) column
   - Same start/end times (within 1 minute)
   - Same customer name, service name, status
3. Explicitly check:
   - Group / multi-customer booking → note if CAD shows wrong customer
   - Custom service (no linked service record) → note if service name is blank
   - Private staff column present
   - Archived staff **not** in header row
   - Two or more appointments stacked on one table
4. **API spot-check** (logged-in, browser devtools):

```http
POST /wp-admin/admin-ajax.php
action=cad_get_schedule
nonce=<from cadConfig>
date=YYYY-MM-DD
```

Compare JSON `appointments[]` count and fields to the Bookly appointment list for that date.

5. Run the [demo checklist](deployment.md#demo-checklist-rule-1) (table headers, time grid, duration blocks, mobile scroll).

### Pass criteria

Same appointment **count**, **IDs**, **times**, **tables**, **names**, and **statuses** for standard single-customer bookings. Document any failures with appointment ID and screenshot.

---

## Risks

| Risk | Impact |
|------|--------|
| Group bookings at Bentonville | Wrong customer name on block — looks like a data bug |
| Custom/ad-hoc services | Blank service label |
| Custom Statuses add-on with non-standard slugs | Status CSS class may not match expected styling |
| WP timezone ≠ staff browser timezone | Time labels may differ from Bookly admin calendar by offset |
| Direct `$wpdb` reads | Bookly upgrade could change schema — monitor on Bookly updates |
| jsDelivr `@2.0.0` tag | Production JS must match deployed snippets |
| `cad_update_appointment` stub | Staff might expect editing — scope creep if not communicated |

---

## Recommendation

**Sprint 1 is ready for Bentonville read-only parity verification testing.**

The Repository → Provider → Mapper → UI pipeline is structurally correct and aligned with Bookly on the items that matter most for a studio day view: IDs, times (including extras), staff columns, status, and deduplication.

**Proceed with Bentonville side-by-side testing.** Do not block on code changes beforehand.

**After Bentonville confirms real data:**

| If observed | Action |
|-------------|--------|
| Group bookings | Fix multi-customer aggregation (priority 1) |
| Custom services | Add `custom_service_name` to repository SQL |
| Colour mismatch unacceptable | Revisit Bookly hex parity (currently intentional) |

**Not ready for:** appointment editing, drag/reschedule, or Sprint 2 features — `cad_update_appointment` remains a stub by design.

---

## Sign-off (Bentonville)

| Check | Date | Tester | Pass / Fail | Notes |
|-------|------|--------|-------------|-------|
| Busy day side-by-side | | | | |
| Empty day | | | | |
| Extras duration | | | | |
| Multi-customer (if applicable) | | | | |
| Custom service (if applicable) | | | | |
| API spot-check | | | | |
