# Sprint 3.2.7 — Reservation Dialog UI Polish

**Status:** Implemented in **3.2.7** (awaiting Bentonville review / tag)

**Goal:** Presentation-only polish on the shared Reservation Dialog. Keep the 3.2.6 layout as the base. No Bookly save/validation changes.

## Changes

| # | Item | Notes |
|---|------|--------|
| 1 | Bold field labels | Weight ~700; stand out from values |
| 2 | Regular field values | Inputs/selects/textareas at weight 400 |
| 3 | Purple select chevrons | Custom SVG; native arrows hidden |
| 4 | Wider left column | ~58% / 42% split |
| 5 | Vertical divider | `1px solid #E8E8E8`, 24px from each column |
| 6 | Remove accessibility notes | Filtered in UI by label match |
| 7 | Special Occasion dropdown | Bookly custom-field `items` / `values` passed through; `drop-down` → `select` |
| 8 | Booking Notes as field | Purple field label; no section divider |
| 9 | Studio Notes / Status spacing | Labels sit above controls |
| 10 | Current status highlight | Green selected chip (`#E8F5E9` / `#4CAF50`) |
| 11 | Footer bar | `#F7F7F7` + outlined Delete / Cancel + solid Save |
| 12 | Summary banner | Wrapped `#F7F7F7` container, 8px radius |
| 13 | Design language | White controls, light borders, purple headings |

## Files

- `assets/css/cad-scheduler.css`
- `src/components/reservation-dialog.js`
- `includes/class-cad-bookly-repository.php` — custom-field choice items passthrough
- `includes/class-cad-bookly-mapper.php` — select type + items on detail fields
- `snippets/10A-ajax-bridge.php` — version pin
- `src/cad-core.js` — `CAD.VERSION`

## Deploy

1. Wait until jsDelivr serves `…/cad-scheduler@3.2.7/…` (HTTP 200)
2. Pin `CAD_SCHEDULER_VERSION` to **3.2.7**
3. Redeploy snippets **10** (Repository / Mapper) and **20** (Bridge)
4. Hard-refresh
