# Sprint 2.4 — Adaptive Appointment Cards

**Calendar First:** maximize visible calendar space. No permanent UI outside the calendar; no slide-in details panel (deferred to Sprint 2.5).

## Goals

- Appointment cards adapt content from **rendered pixel height** (not CSS breakpoints)
- Read-only status badges on tall cards
- Desktop hover tooltip with full summary (no buttons / editing)
- Keep existing click → selection behaviour

## Density tiers

Priority order (when shown): Time → Customer (largest) → Service → Painters → Status → Phone. Never table.

| Density | Height rule | Shows |
|---------|-------------|--------|
| Compact | `< 48px` | Time, customer |
| Standard | `< 72px` | + service |
| Large | `< 96px` | + painters, status |
| Extra large (`xl`) | `≥ 96px` | + phone (when present) |

Thresholds live in `CAD.cardRenderer.HEIGHT_THRESHOLDS`.

## Status indicators (read-only)

Shown on large and xl. Badges when applicable; otherwise `✓ Confirmed`.

| Indicator | When |
|-----------|------|
| 🟢 Arrived | Status slug `arrived` |
| ❌ No Show | Status slug `no-show` / `noshow` |
| 💲 Paid | Bookly payment status `completed` |
| ✓ Confirmed | Approved / confirmed with no special badges |

No editing in this sprint.

## Hover tooltip (desktop)

On `(hover: hover) and (pointer: fine)`, hovering a card shows:

Label-free stack (customer bold/largest, then service, time, painters, status, phone when present).

(Table is not shown — the matrix already groups by table.)

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
