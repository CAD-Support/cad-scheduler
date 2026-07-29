# Sprint 3.2.2 — Reservation Manager UX Polish

**Status:** Shipped in **3.2.2**

**Goal:** Redesign Reservation Manager as the primary desktop staff interface. Layout/UI only — Bookly save path unchanged.

## Changes

| Item | Detail |
|------|--------|
| Width | ~880px desktop modal |
| Layout | Two-column sections; less scrolling |
| Summary strip | Service • Table • Date • Time range • Painters |
| Time pickers | 15-minute select lists (open hours) |
| Duration | Read-only, derived from start/end |
| Painters | Dropdown 1…table capacity (Bookly `capacity_max` or filter) |
| Sticky footer | Cancel / Save Reservation always visible |
| Notes | Reservation Notes (CA notes) + Studio Notes (internal) |
| Status | Compact chip / segmented buttons |
| CAPTCHA / website fields | Filtered out of detail fields |

## Not changed

- `get_reservation` / `save_reservation` Bookly `checkTime` + `save` contract
- Dynamic service custom fields (still API-driven; no Birthday/Studio branching in JS)

## Deploy

1. Redeploy snippets **11** (Mapper) and **20** (Bridge)  
2. Pin `CAD_SCHEDULER_VERSION` to **3.2.2**  
3. Hard-refresh
