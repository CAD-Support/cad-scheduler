# Sprint 2.2 — Scrollable Calendar

Scrollable multi-table scheduler layout while preserving Sprint 2.1 date navigation and loading behavior.

## Goal

Keep `.cad-nav` fixed above the grid. Scroll only the calendar body. Sticky table headers (vertical) and sticky time labels (horizontal). Many tables scroll horizontally instead of compressing columns.

## Behaviour

| Area | Behaviour |
|------|-----------|
| `.cad-nav` | Outside the scrollport — stays visible above the calendar |
| `.cad-scheduler__status` | Outside the scrollport |
| `.cad-matrix__scroll` | Vertical + horizontal overflow |
| `.cad-matrix__head` | `position: sticky; top: 0` while scrolling down |
| `.cad-matrix__corner` | Sticky on both axes (`top` via head + `left: 0`) |
| `.cad-matrix__times` | `position: sticky; left: 0` while scrolling sideways |
| Table columns | Min width (`9rem` / `7rem` on small screens); grid `min-width` forces horizontal scroll when needed |

## Non-goals

- No editing / drag-and-drop
- No Repository, Mapper, Provider, or AJAX changes
- No appointment load / positioning algorithm changes
- Shell is not recreated on scroll (CSS only)

## Structure (unchanged)

```
.cad-scheduler
  .cad-nav
  .cad-scheduler__status
  .cad-scheduler__calendar.cad-matrix
    .cad-matrix__scroll          ← overflow: auto
      .cad-matrix__head          ← sticky top
      .cad-matrix__body
        .cad-matrix__times       ← sticky left
        .cad-matrix__lane…
```

## Files

| File | Change |
|------|--------|
| `assets/css/cad-scheduler.css` | Scrollport sizing, sticky rules, non-compressing column track |
| `src/cad-calendar.js` | Comment only — scroll wrapper already present |
| Docs | This file, changelog, roadmap |

## Acceptance

- [ ] Nav remains visible above the calendar while the grid scrolls
- [ ] Vertical scroll keeps table headers visible
- [ ] Horizontal scroll keeps time labels (and corner) visible
- [ ] Many tables scroll horizontally without squashing columns below the min width
- [ ] 15-minute grid and appointment block geometry unchanged
- [ ] Sprint 2.1 date navigation still works (single calendar host, abort/sequence load)

## Sticky stacking (Chrome / Edge / Safari)

Z-index inside `.cad-matrix__scroll`:

| Layer | z-index | Role |
|-------|---------|------|
| Corner | 5 | Above both sticky axes |
| Header | 4 | Above appointments when scrolling vertically |
| Time column | 3 | Above appointment blocks when scrolling horizontally |
| Appointments | 1 | Contained in lane (`isolation: isolate`, lane `z-index: 0`) |

Hardening:

- `position: -webkit-sticky` + `sticky` for Safari
- `align-items: start` on matrix grids (avoids stretch breaking sticky in Safari)
- Opaque sticky backgrounds (`--cad-sticky-tint` / surface)
- No `transform` on time labels or appointment hover (reduces scroll flicker / compositor stacking bugs)
- Fixture: `docs/fixtures/sprint-2.2-sticky.html`

**Verified (Chromium — Chrome / Edge / Cursor):** dual-axis scroll with `scrollLeft=220`, `scrollTop=180` — corner/head/times stuck; z-order `5/4/3`; corner above both axes.

**Safari:** same CSS applied; confirm on macOS/iOS with the fixture (sticky inside `overflow: auto` + CSS grid is the risk area this hardening targets).
