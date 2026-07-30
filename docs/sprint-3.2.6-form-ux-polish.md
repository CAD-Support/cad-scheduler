# Sprint 3.2.6 — Form UX Polish + Shared Reservation Dialog

**Status:** **RC3** tagged as **`3.2.6-rc3`** — Bentonville acceptance candidate. Final **3.2.6** only after approval (pin bump only). Feature freeze: release-blocking fixes only.

**Goal:** Presentation-only polish and one maintainable reservation experience for New and Edit. Bookly remains source of truth. No changes to Provider, Mapper, AJAX save endpoints, or Bookly validation.

## Shared Reservation Dialog

| Mode | Title | Initial data | Footer |
|------|-------|--------------|--------|
| **New** | New Reservation | Slot context (table, date, start) + defaults | Cancel · Create Reservation |
| **Edit** | Edit Reservation | `getReservation` payload | Delete · Cancel · Save Reservation |

Everything else is shared: layout, cards, inputs, validation, summary strip, customer section, studio notes, capacity painters, categorized service select, outside-hours warning.

- **Module:** `src/components/reservation-dialog.js` → `CAD.ReservationDialog`
- **Facades:** `CAD.QuickAdd` (empty-lane bind + open new), `CAD.ReservationManager` (open/close edit) — call sites unchanged
- **Chrome:** `.cad-rm*` only (duplicate Quick Add form CSS removed)

## Layout polish (still applies)

| Area | Behavior |
|------|----------|
| Grid | Stronger hour separators; quarter-hours stay subtle; table column separators match hour weight |
| Service | Single dropdown; Bookly service categories as `<optgroup>` headings (Repository `get_services` only) |
| Customer | First Name · Last Name; Phone · Email (create still sends combined `customer_name`) |
| Reservation | Date beside Start · End; Service · Table; Painters · Duration |
| Painters | Options 1…table `capacity`; rebuilds when table changes |
| Service select | Sets Duration + End from Bookly `durationMinutes` |

## Deploy (RC3 — Bentonville acceptance)

1. Wait until jsDelivr serves `…/cad-scheduler@3.2.6-rc3/…` (HTTP 200)
2. Pin `CAD_SCHEDULER_VERSION` to **3.2.6-rc3** (already in bridge RC commit)
3. Redeploy snippets **10** (Repository — category fields on services) and **20** (Bridge)
4. Hard-refresh

After approval: tag **3.2.6**, change bridge pin to **3.2.6**, push — no other code changes unless release-blocking. CDN polyfills remain for this release.
