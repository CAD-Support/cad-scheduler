# Sprint 2.3 — Dynamic Day Range

Expand the matrix time range so appointments outside studio open hours stay fully visible and scrollable.

## Hard-coded 8:00 PM boundary (before this sprint)

| Location | Role |
|----------|------|
| `snippets/10A-ajax-bridge.php` → `cadConfig.dayEnd` | Was fixed `'20:00'` in `wp_localize_script` |
| `src/cad-core.js` → `dayEnd` default | `'20:00'` |
| `src/cad-calendar.js` → `gridMetrics()` | Built labels / `--cad-grid-height` only from `dayStart`/`dayEnd` |

CSS only consumes `--cad-grid-height` / `--cad-slot-height` from JS — it did not hard-code 20:00.

## Behaviour

1. Base range = studio open hours for the **selected weekday** (`cadConfig.openHours[0–6]`, else `dayStart`/`dayEnd`).
2. Expand start to the earlier of open start or earliest appointment start.
3. Expand end to the later of open end or latest appointment end.
4. Snap start **down** and end **up** to `slotMinutes`.
5. Add **one slot** of bottom padding.
6. Recalculate on every `CAD.ui.load()` / date navigation (appointments + `selectedDate` feed `gridMetrics`).

Closed day (`openHours[weekday] = null`) with appointments still spans those appointments. Closed day with no appointments falls back to `dayStart`/`dayEnd` for an empty shell.

## Example

Open hours `10:00`–`20:00`, slots `30`, latest appointment `20:30`–`22:00` → grid **`10:00`–`22:30`**.

## Config / filters

```php
add_filter( 'cad_scheduler_day_start', fn () => '10:00' );
add_filter( 'cad_scheduler_day_end', fn () => '20:00' );

add_filter( 'cad_scheduler_open_hours', function ( $weekly ) {
    $weekly[0] = null; // Sunday closed
    return $weekly;
} );
```

## Files

| File | Change |
|------|--------|
| `src/cad-calendar.js` | `resolveDayRange` / dynamic `gridMetrics` |
| `snippets/10A-ajax-bridge.php` | `openHours` in `cadConfig` + filters |
| `src/cad-core.js` | `openHours` config key |
| `docs/fixtures/sprint-2.3-day-range.html` | Verification fixture |

## Acceptance

- [x] 8:00–9:30 PM and 8:30–10:00 PM blocks reachable and fully visible via scroll
- [x] Grid end `22:30` for the example above
- [x] Sticky axes + internal scrollport preserved
- [x] In-hours-only days keep open hours (+ padding slot)
- [x] Closed weekday with appointments still expands
