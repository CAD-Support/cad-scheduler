# Changelog

## [2.1.0] — 2026-07-24

### Added

- **Date navigation** — Previous / Today / Next and native date picker above the scheduler ([Sprint 2.1](sprint-2.1-date-navigation.md))
- `src/cad-navigation.js` (`CAD.Navigation`) owns day controls and locale date formatting
- `CAD.State.update()` and `selectedDate` (`YYYY-MM-DD`) for the active day

### Changed

- Init flow: `CAD.Navigation.init()` then `CAD.ui.load(today)` — date changes reload via existing `CAD.API.getSchedule` without a page refresh
- UI shell is preserved across loads (nav is not recreated)
- Schedule loads abort prior in-flight requests so rapid navigation cannot apply a stale day

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
