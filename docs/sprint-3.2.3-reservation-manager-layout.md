# Sprint 3.2.3 — Reservation Manager Layout Polish

**Status:** Shipped in **3.2.3**

**Goal:** Presentation-only refinement of the Reservation Manager to match the approved mock-up more closely. No save logic or field-mapping changes.

## Layout

| Row | Fields |
|-----|--------|
| 1 | Service · Table |
| 2 | Date (full width) |
| 3 | Start Time · End Time |
| 4 | Duration (read-only) · # of Painters |

## Visual polish

- Tighter section / field spacing
- Stronger editable inputs (border, weight, hover)
- Quieter duration display vs editable controls
- More prominent summary strip
- Sticky footer retained

## Deploy

1. Redeploy snippet **20** (Bridge) — CSS/JS via CDN pin  
2. Pin `CAD_SCHEDULER_VERSION` to **3.2.3**  
3. Hard-refresh
