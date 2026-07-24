# Sprint 2.4 — Adaptive Appointment Cards

**Calendar First:** maximize visible calendar space. No permanent UI outside the calendar; no slide-in details panel (deferred to Sprint 2.5).

## Goals

- Appointment cards adapt content from **rendered pixel height** (not CSS breakpoints)
- Read-only status badges on tall cards
- Desktop hover tooltip with full summary (no buttons / editing)
- Keep existing click → selection behaviour

## Density tiers

| Density | Height rule | Shows |
|---------|-------------|--------|
| Compact | `< 48px` | Time, customer |
| Standard | `< 72px` | + service |
| Large | `< 96px` | + number of painters |
| Extra large (`xl`) | `≥ 96px` | + status badge(s) if applicable |

Thresholds live in `CAD.cardRenderer.HEIGHT_THRESHOLDS`.

## Status indicators (read-only)

| Indicator | When |
|-----------|------|
| 🟢 Arrived | Status slug `arrived` |
| ❌ No Show | Status slug `no-show` / `noshow` |
| 💲 Paid | Bookly payment status `completed` |
| _(none)_ | Confirmed / approved (unless also paid) |

No editing in this sprint.

## Hover tooltip (desktop)

On `(hover: hover) and (pointer: fine)`, hovering a card shows:

Customer · Service · Time · Table · Painters · Status

No buttons. No editing.

## Architecture

```
cad-calendar.js          → layoutBlock() supplies { top, height }
cad-components.js        → parses availableHeight, builds button shell
CAD.cardRenderer.render(appointment, availableHeight)
```

The calendar must not decide which fields appear on a card.

### Module

- `src/cad-card-renderer.js` — density, badges, tooltip, `render()`

### Data (additive)

Repository / mapper expose:

- `painters` ← `bookly_customer_appointments.number_of_persons`
- `paid` ← payment row status `completed`
- `notes` ← `internal_note` (kept for Sprint 2.5 details panel)

## Out of scope (Sprint 2.5+)

- Slide-in appointment details panel
- Any permanent sidebar or reserved chrome outside the matrix
- Inline editing

## Verification

Fixture: [`docs/fixtures/sprint-2.4-adaptive-cards.html`](fixtures/sprint-2.4-adaptive-cards.html)

1. Cards of different durations show compact → xl content correctly
2. Hover tooltip lists all six fields on desktop
3. Click still selects only (no details panel)
4. No sidebar / overlay chrome outside the calendar
