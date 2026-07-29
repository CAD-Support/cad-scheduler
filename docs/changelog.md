# Changelog

## [3.2.0] — 2026-07-29

### Added

- **Sprint 3.2 Reservation Manager** — primary view/edit UI for existing reservations (replaces opening Bookly for normal edits)
- Dynamic **Reservation Details** from Bookly Custom Fields (service-scoped; no hard-coded birthday UI)
- `cad_get_reservation` / `cad_save_reservation` via Bookly `checkTime` + `save`; customer updated in place
- Duration sync (start/end ↔ duration)

### Notes

- Pin `CAD_SCHEDULER_VERSION` to **3.2.0**; redeploy snippets **10–12** and **20**
- Empty-slot create remains Quick Add (3.1)
- See [sprint-3.2-reservation-manager.md](sprint-3.2-reservation-manager.md)

## [3.1.0] — 2026-07-29

### Added

- **Sprint 3.1 Quick Add Reservation** — click an empty slot to create a Bookly reservation (customer + appointment) without leaving the scheduler
- `CAD.QuickAdd` modal, `CAD.API.createAppointment`, `wp_ajax_cad_create_appointment`, `CAD_Schedule_Provider::create_appointment`
- Slot highlight + pre-filled table / day / time summary

### Notes

- Pin `CAD_SCHEDULER_VERSION` to **3.1.0**; redeploy snippets **12** and **20**
- Optional: set `cad_scheduler_quick_add_service_id` to your Studio Reservation Bookly service id
- See [sprint-3.1-quick-add.md](sprint-3.1-quick-add.md)

## Sprint 3.0 — Drag & Drop Complete (2.7.0–2.7.8)

**Status:** P1 Drag & Drop is production-ready as of **2.7.8**.

### Release notes

- Fixed scheduler timezone rendering.
- Bookly wall-clock times now render correctly.
- Drag-and-drop between tables and times is fully functional.
- Scheduler and Bookly backend remain synchronized.
- Removed all temporary debugging instrumentation.

## [2.7.8] — 2026-07-29

### Fixed

- **Timezone rendering** — Bookly datetimes are treated as studio wall-clock times (`Y-m-d H:i:s`), not ATOM-with-offset instants. Browser TZ no longer shifts appointments (e.g. 11:00 stored → 11:00 rendered).

### Changed

- Mapper emits MySQL wall-clock strings (no timezone offset)
- Frontend parses via `CAD.utils.parseBooklyLocal` (`new Date(y, m-1, d, h, min, s)`)
- DnD optimistic State stores wall-clock strings (not `toISOString()`)
- Removed temporary render/DnD/PHP debug instrumentation from 2.7.4–2.7.7

### Notes

- Pin `CAD_SCHEDULER_VERSION` to **2.7.8**
- Redeploy Code Snippets **11** (Mapper), **12** (Provider), **20** (AJAX Bridge)

## [2.7.7] — 2026-07-29

### Added

- **Debug-only** render-path instrumentation: `[CAD RENDER raw|iso|json]` (PHP) and `[CAD RENDER api|date]` (JS) — **no behavior changes**
- Traces Bookly `start_date` → mapper `DateTimeInterface::ATOM` → `new Date` → `getHours()` / grid `top`

### Notes

- Pin `CAD_SCHEDULER_VERSION` to **2.7.7**; redeploy snippets **11** (Mapper), **12** (Provider), **20** (Bridge)
- Suspect: `CAD_Bookly_Mapper::iso()` attaches `wp_timezone()` offset; browser `getHours()` can shift by 1h if WP TZ ≠ browser TZ

## [2.7.6] — 2026-07-29

### Added

- **Debug-only** PHP save-path logs (`[CAD PHP STEP 0]`–`[CAD PHP STEP 5]`): raw `$_POST['start']`, sanitized, normalized, pre-`Bookly::save`, entity after save, DB `start_date` — **no behavior changes**

### Notes

- Pin `CAD_SCHEDULER_VERSION` to **2.7.6**
- Redeploy Code Snippets **12** (Provider) and **20** (AJAX Bridge); check `wp-content/debug.log` (or host PHP error log)
- Prior TEMP logs that printed the *old* entity start before save were misleading and are replaced

## [2.7.5] — 2026-07-29

### Added

- **Debug-only** drop logs: `[COORDS]`, `[LAST SNAP]`, `[RECTS]`, `[ROW INPUT]` (plus existing STEP / alignment / row-origin) — **no drag/drop behavior changes**

### Notes

- Pin `CAD_SCHEDULER_VERSION` to **2.7.5**
- `mouseY` is `clientY - .cad-matrix__blocks.getBoundingClientRect().top` (not matrix / not scrollport)
- `livePointerSnap` is computed on drop only for comparison; final snap selection is unchanged (`lastSnap || live`)

## [2.7.4] — 2026-07-28

### Added

- **Debug-only** instrumentation for +60m drop investigation: `[CAD first-minute alignment]`, `[STEP 0]`–`[STEP 8]`, `[CAD row origin]` — **no drag/drop behavior changes**

### Notes

- Pin `CAD_SCHEDULER_VERSION` to **2.7.4**
- Remove TEMP debug logs after QA

## [2.7.3] — 2026-07-28

### Fixed

- **DnD drop snap** — use appointment top edge (`topEdgeY = mouseY - grabOffsetY`) and prefer last preview snap (`lastSnap`) instead of raw cursor Y

### Notes

- Pin `CAD_SCHEDULER_VERSION` to **2.7.3**

## [2.7.2] — 2026-07-28

### Fixed

- **DnD day-start alignment** — bridge TEMP `forceRebuildFromState` now sets `--cad-day-start-min` (was missing; snap fell back to 08:00 and could shift drops by ±1h)

### Added

- TEMP console logging on drop (`[CAD DnD]` payload) and render (`[CAD day-start]` alignment) for live timezone QA

### Notes

- Pin `CAD_SCHEDULER_VERSION` to **2.7.2**
- Remove TEMP debug logs after QA

## [2.7.1] — 2026-07-28

### Fixed

- **Drag & drop initialization** — bind `CAD.DnD` from the calendar render lifecycle (and bridge `forceRebuildFromState` polyfill); remove the fragile `calendar.render` monkey-patch; use `button.cad-appointment`; prevent duplicate listeners

### Notes

- Pin `CAD_SCHEDULER_VERSION` to **2.7.1**
- Paste updated bridge (+ calendar/dnd via CDN) for live QA

## [2.7.0] — 2026-07-28

### Added

- **P1 Drag & drop reschedule** — move appointments across tables/time slots; persist via Bookly `Utils\Appointment::checkTime` + `::save` (`cad_update_appointment`); optimistic UI with revert on conflict/failure; `CAD.DnD` + `CAD.notify` ([#3](https://github.com/CAD-Support/cad-scheduler/issues/3))
- Fixture: [`docs/fixtures/sprint-3.0-dnd.html`](fixtures/sprint-3.0-dnd.html)

### Changed

- `CAD.API.request` surfaces structured JSON error messages on non-OK HTTP responses
- Bridge enqueues `cad-notify.js` / `cad-dnd.js`; pin `CAD_SCHEDULER_VERSION` to **2.7.0**

### Notes

- Paste updated Repository / Provider / bridge snippets for live QA
- Notifications + Google/Outlook sync rely on Bookly’s save path — verify on a real install after deploy
- Sprint 3 P2 Quick Add is not included

## [3.0.0] — Planned (Sprint 3.0 Studio Operations)

See [sprint-3.0-studio-operations.md](sprint-3.0-studio-operations.md).

### Phase 1 — Critical

- **P1 Drag & Drop** — Shipped in **2.7.0**; init fix in **2.7.1** ([#3](https://github.com/CAD-Support/cad-scheduler/issues/3))
- **P2 Quick Add** — Not started ([#4](https://github.com/CAD-Support/cad-scheduler/issues/4))

### Phase 2 — After Phase 1 is production-ready

- **P3 Undo** — [#2](https://github.com/CAD-Support/cad-scheduler/issues/2)
- **P4 Filters** — [#7](https://github.com/CAD-Support/cad-scheduler/issues/7)
- **P5 Live Refresh** — [#6](https://github.com/CAD-Support/cad-scheduler/issues/6)
- **P6 Lightspeed customer lookup** — [#5](https://github.com/CAD-Support/cad-scheduler/issues/5)

### Notes

- Goal: daily-ops scheduler (view / create / move / edit / update) without the native Bookly calendar
- Permanent logic stays in `src/` (+ Provider/Repository); bridge stays enqueue/AJAX bootstrap only

## [2.6.0] — 2026-07-27

### Fixed

- **Column count from schedule payload** — rebuild headers/grid from `CAD.State.get('tables')` on every load (fixes stale 7-column `cadConfig` while AJAX returns 9); bridge replaces CDN `calendar.render` so live pins rebuild from State, not page-load Config
- **Nine columns clipped to seven** — pin `grid-template-columns` with a literal `repeat(N, …)` on head/body (CDN `repeat(var(--cad-table-count))` + scrollport clipping could hide cols 8–9); bridge force-rebuild applies the same inline tracks for paste+refresh on 2.4.1

### Changed

- **Appointment cards** — max ~4 lines; birthday prioritizes child name; painter badge + dominant status badge; no phone/email; Crock A Doodle type accents (teal / purple / orange) ([Sprint 2.6](sprint-2.6-ux-polish.md))
- **Shared badges** — `CAD.Badges` (`.cad-badge` / `--painters` / `--status`) used on cards, helpers, and popover; equal height/weight for every status label
- **Popover** — colored type ribbon; fixed ~350px width; section order Type → Child → Booked By → Details → Status → Internal Notes → Actions; empty sections hidden

### Notes

- Presentation only — normalized appointment model and backend APIs unchanged
- Leave WordPress `CAD_SCHEDULER_VERSION` at the live CDN pin until this tag is on jsDelivr

## [2.5.1] — 2026-07-27

### Fixed

- **All visible Bookly resources as columns** — staff query excludes archived only (NULL-safe); order follows Bookly `position` ([Sprint 2.5.1](sprint-2.5.1-all-bookly-resources.md))
- Schedule payload includes `tables` additively so columns refresh with each load (no hardcoded resource names)

### Added

- **Staff pipeline diagnostics** — plain-text summary (Bookly → UI counts, missing IDs/reasons, failing layer); panel + `CAD.API.debugStaffPipeline()`

### Notes

- Leave WordPress `CAD_SCHEDULER_VERSION` at the live CDN pin until this tag is on jsDelivr
- Paste updated Repository / Mapper / Provider snippets for live QA

## [2.5.0] — 2026-07-27

### Added

- **Normalized appointment model** — mapper emits `type`, `serviceId`, `birthday` / `studio` / `event` nests; custom-field IDs stay in PHP ([model](sprint-2.5-normalized-appointment-model.md))
- **Service-aware appointment popover** — `CAD.Popover.render(appointment)` → `CAD.Renderers.render(appointment)` by `appointment.type`
- Frontend layout: `src/renderers/` (reservation / birthday / event) + `src/components/` (popover / status-panel)
- Status actions update Bookly custom statuses via `cad_update_appointment_status` (no payment-table join)
- Filters: `cad_scheduler_custom_field_map`, `cad_scheduler_appointment_type`, `cad_scheduler_appointment`, `cad_scheduler_appointments`, `cad_scheduler_custom_fields_select_sql`
- Fixtures: [`docs/fixtures/sprint-2.5-popover.html`](fixtures/sprint-2.5-popover.html), [`docs/fixtures/verify-mapper-normalize.php`](fixtures/verify-mapper-normalize.php)

### Notes

- The normalized appointment object is the **public API** between backend and frontend — prefer additive property changes; Repository/Mapper may evolve internally ([stability](sprint-2.5-normalized-appointment-model.md#public-api-stability))
- Leave WordPress `CAD_SCHEDULER_VERSION` at **2.4.1** until this release is tagged and verified on jsDelivr
- Legacy `src/cad-popover.js` / `src/cad-renderers.js` are deprecation stubs only

## [2.4.2] — 2026-07-27

### Fixed

- **Tooltip opacity** — hover tooltip forces solid `#ffffff` background (`opacity: 1`, no backdrop-filter) so the calendar grid cannot show through

### Added

- **Phone display formatting** — `CAD.utils.formatPhone()` formats North American numbers for cards and tooltips only (Bookly data unchanged)

### Changed

- **Paid badge** — driven by Bookly custom status slug `paid` (same as Arrived / No Show); removed `bookly_payments` join and `paid` boolean mapping
- **Deposit Paid badge** — custom status slug `deposit-paid` → 💰 Deposit Paid

## [2.4.1] — 2026-07-24

### Fixed

- **Card readability** — appointment cards use fully opaque backgrounds so grid lines and overlapping appointments never show through ([Sprint 2.4.1](sprint-2.4.1-card-readability.md))
- Cancelled / rejected cards no longer use `opacity` (muted opaque fills instead)

### Changed

- Hover tooltips omit table name/line (redundant with column grouping; `tableId` unchanged in the model)

### Added

- Customer phone on cards and tooltips from Bookly `customers.phone` (hidden when empty)
- Compact label-free hover tooltips (customer bold; service, time, painters, status, phone)
- Consistent card hierarchy by density: time → customer → service → painters → status → phone (XL)

## [2.4.0] — 2026-07-24

### Added

- **Adaptive appointment cards** — content density from rendered pixel height (compact / standard / large / xl), not CSS breakpoints ([Sprint 2.4](sprint-2.4-adaptive-cards.md))
- `src/cad-card-renderer.js` (`CAD.cardRenderer.render(appointment, availableHeight)`)
- Read-only status badges (arrived / paid / no-show) on extra-large cards
- Desktop hover tooltip (customer, service, time, table, painters, status)
- Appointment `painters` and `paid` from Bookly (Repository / Mapper additive fields)
- Fixture: [`docs/fixtures/sprint-2.4-adaptive-cards.html`](fixtures/sprint-2.4-adaptive-cards.html)

### Notes

- Appointment details panel deferred to Sprint 2.5 (Calendar First — no permanent chrome outside the matrix)
- `notes` (`internal_note`) remains in the payload for the upcoming details panel

## [2.2.2] — 2026-07-24

### Added

- **Dynamic day range** — matrix start/end follows weekday open hours and expands for appointments outside business hours, with slot snap + one bottom padding slot ([Sprint 2.3](sprint-2.3-dynamic-day-range.md))
- `cadConfig.openHours` (weekday map) plus filters `cad_scheduler_open_hours`, `cad_scheduler_day_start`, `cad_scheduler_day_end`
- Fixture: [`docs/fixtures/sprint-2.3-day-range.html`](fixtures/sprint-2.3-day-range.html)

### Changed

- `CAD.calendar.gridMetrics()` derives `--cad-grid-height` / time labels from the computed range for the selected date (recalculated on Previous / Next / Today / picker)

## [2.2.1] — 2026-07-24

### Fixed

- Calendar scrollport — `.cad-matrix__scroll` now uses an explicit responsive `height` (not `max-height` alone) so the bordered card scrolls internally instead of expanding past the page and clipping late appointments ([Sprint 2.2](sprint-2.2-scrollable-calendar.md))
- WordPress/Elementor flex parents can no longer grow the matrix with content (`min-width: 0` on hosts; nav stays outside the scrollport)

### Added

- WordPress DOM scrollport fixture: [`docs/fixtures/sprint-2.2-wp-scrollport.html`](fixtures/sprint-2.2-wp-scrollport.html)

## [2.2.0] — 2026-07-24

### Added

- **Scrollable calendar** — sticky table headers and time labels; horizontal scroll for many tables without column compression ([Sprint 2.2](sprint-2.2-scrollable-calendar.md))
- Sticky stacking test fixture: [`docs/fixtures/sprint-2.2-sticky.html`](fixtures/sprint-2.2-sticky.html)

### Changed

- `.cad-matrix__scroll` is the sole calendar scrollport; `.cad-nav` stays outside it (Sprint 2.1 navigation unchanged)
- Sticky chrome stacking hardened for Chrome / Edge / Safari (corner `z-index: 5`, header `4`, times `3`; no transform flicker on appointments)

## [2.1.0] — 2026-07-24

### Added

- **Date navigation** — Previous / Today / Next and native date picker above the scheduler ([Sprint 2.1](sprint-2.1-date-navigation.md))
- `src/cad-navigation.js` (`CAD.Navigation`) owns day controls and locale date formatting
- `CAD.State.update()` and `selectedDate` (`YYYY-MM-DD`) for the active day

### Changed

- Init flow: `CAD.Navigation.init()` then `CAD.ui.load(today)` — date changes reload via existing `CAD.API.getSchedule` without a page refresh
- UI shell is preserved across loads (nav is not recreated)
- Schedule loads abort prior in-flight requests so rapid navigation cannot apply a stale day

### Fixed

- Date navigation no longer appends duplicate calendars — `CAD.calendar.render()` keeps `.cad-scheduler__calendar` and only replaces that container’s contents

## [2.0.0] — 2026-07-23

### Added

- **Validation Mode** — optional appointment ID overlay via `cad_scheduler_validation_mode_enabled` filter; see [Sprint 1.7 — Live Validation](sprint-1.7-live-validation.md)
- Live validation docs: [checklist](validation-checklist.md), [Bentonville report template](bentonville-validation-report-template.md)

### Fixed

- Staff/table query now excludes only archived Bookly staff (private staff appear on the studio grid, matching Bookly admin calendar)
- Appointment end time now includes `extras_duration`, matching Bookly Pro calendar block duration
- `cad_get_schedule` requires authentication; removed public (`nopriv`) access

### Added

- Health check and diagnostic panel for missing snippets, Bookly tables, and JavaScript configuration
- **v2 matrix scheduler:** tables across top, time down left, 15-minute grid
- Appointment blocks positioned and sized by ISO start/end duration
- Bookly PHP layer (`includes/`) deployable via Code Snippets
- `[cad_scheduler]` shortcode, jsDelivr asset delivery
- Responsive layout with Crock A Doodle brand styling
- `CAD.API.getSchedule()` and streamlined `CAD.Config` / `CAD.State`

### Demonstrable

Open a page with `[cad_scheduler]` — see today's Bookly appointments on the studio grid.
