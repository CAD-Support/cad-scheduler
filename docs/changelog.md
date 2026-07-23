# Changelog

## [2.0.0] — 2026-07-23

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
