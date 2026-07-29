# Sprint 3.2.4 — Reservation Manager UI Refinement

**Status:** Shipped in **3.2.4**

**Goal:** Presentation-only refinement so Reservation Manager feels like a Crock A Doodle desktop app, not a stacked WordPress form. No save / mapping / Bookly logic changes.

## Layout

| Section | Layout |
|---------|--------|
| Reservation | Service · Table; Date (full); Start · End; Painters · Duration |
| Customer | First · Last; Phone · Email (equal columns) |
| Reservation Details | Two-column grid; textareas full width |
| Notes | Reservation Notes · Studio Notes side-by-side (stack on mobile) |
| Status | Compact chips |
| Chrome | Sticky title + summary card; sticky Cancel / Save footer |

## Visual

- ~20–25% less vertical whitespace
- Stronger summary card (gradient, radius, hierarchy)
- Equal control heights; lighter labels; purple section titles
- Duration remains read-only

## Deploy

1. Redeploy snippet **20** (Bridge) — CSS/JS via CDN pin  
2. Pin `CAD_SCHEDULER_VERSION` to **3.2.4**  
3. Hard-refresh
