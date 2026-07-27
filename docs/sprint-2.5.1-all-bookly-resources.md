# Sprint 2.5.1 — All Visible Bookly Resources as Columns

**Goal:** The scheduler shows every visible Bookly staff/resource as a calendar column, in Bookly order.

## Problem

CAD was missing columns that appear on the Bookly admin calendar (e.g. Party Space, Outside Studio). Columns must come from Bookly data — never hardcoded names.

## Pipeline

```
bookly_staff
  → CAD_Bookly_Repository::get_staff_tables()
  → CAD_Bookly_Mapper::map_staff_tables()   // preserves order → { id, name }
  → CAD_Schedule_Provider::get_tables()
  → cadConfig.tables (page load) + schedule.tables (each load)
  → CAD.calendar columns
```

## Requirements

| Rule | Implementation |
|------|----------------|
| All visible staff | Exclude `visibility = archive` only (`public`, `private`, NULL/empty included) |
| Hidden stay hidden | Archived staff never mapped to columns |
| Bookly order | `ORDER BY position ASC, full_name ASC` |
| No hardcoded names | Query returns whatever Bookly has |
| Future resources | New non-archived staff appear on next schedule load without code changes |
| Appointments | Still keyed by `tableId` = `staff_id`; no calendar CSS/layout changes |

## Filters (optional site overrides)

- `cad_scheduler_staff_visibility_sql` — WHERE fragment (default: non-archive)
- `cad_scheduler_staff_order_sql` — ORDER BY fragment (default: position, name)
- `cad_scheduler_tables` — post-map table list (do **not** use to drop Party Space / Outside Studio unless intentional)

## Frontend column sync (critical)

CDN **`2.4.1` `cad-ui.js` ignored `data.tables`** and only rendered `cadConfig.tables` from page load. After the staff query returned 9 resources, AJAX could show 9 tables while the grid stayed at the stale localize count (e.g. 7).

**Fix:**

1. `cad-ui.js` — on every schedule load, copy `payload.tables` → `CAD.Config` + `CAD.State.tables` (+ `window.cadConfig.tables`)
2. `cad-calendar.js` — `resolveTables()` prefers non-empty `State.tables`; rebuild headers/lanes/`--cad-table-count` every render
3. Bridge inline `cad_scheduler_tables_sync_inline_js()` — patches `ui.load` **and** replaces `calendar.render` so CDN **2.4.1** rebuilds the matrix from State (Config sync alone was not enough when State=9 / UI=7)

Never assume seven columns. Always render every resource in `CAD.State.get('tables')` after schedule load.

**TEMP DEBUG (Sprint 2.5.1):** after rebuild, console logs:

- `Rendering columns: […]` (names used for the DOM loops)
- `Header DOM nodes:` / `Body column DOM nodes:`
- `TEMP DEBUG --cad-table-count:` and `gridTemplateColumns:`

Interpretation: 9 nodes + wrong/clipped tracks → CSS; node counts &lt; 9 → DOM loop. Remove after live verify.

## Acceptance

- [ ] Party Space column appears (when non-archived in Bookly)
- [ ] Outside Studio column appears (when non-archived in Bookly)
- [ ] Column order matches Bookly calendar
- [ ] Existing appointments still render on the correct columns
- [ ] Sticky headers, horizontal scroll, adaptive cards unchanged
- [ ] Archived staff still absent

## Live deploy

Paste updated **Repository**, **Mapper**, **Provider**, and **bridge** snippets. `CAD_SCHEDULER_VERSION` stays at the current CDN pin until `2.5.1` (or `2.5.0`) is tagged.

## Pipeline tracing (easy mode)

1. Paste updated **Repository**, **Provider**, and **bridge**.
2. Enable:

```php
add_filter( 'cad_scheduler_diagnostics_enabled', '__return_true' );
```

3. Reload the scheduler page (logged in).

A yellow report appears under the grid (and in the console) with counts + missing IDs + failing layer. No manual JSON comparison needed.

Example:

```
Bookly resources: 9
Repository:       7
Mapper:           7
Provider:         7
AJAX tables:      7
UI columns:       7

Mismatch detected at: REPOSITORY

Missing:
- Party Space (ID 8) | position=8 | visibility=archive | archived=true
- Outside Studio (ID 9) | position=9 | visibility=archive | archived=true

Excluded because: archived = true (visibility=archive)
```

Confirm `Repository build: 2.5.1-staff-pipeline`. If missing, an older Repository snippet is still loaded.

Optional: `CAD.API.debugStaffPipeline()` in the console (when newer assets are loaded).

4. Turn diagnostics off when done.
