# Changelog

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
