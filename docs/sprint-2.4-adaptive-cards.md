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

Shown on large and xl. Driven by Bookly **custom status** slug on the customer appointment (`ca.status`). Confirmed / approved show `✓ Confirmed` (no badge).

| Indicator | Status slug |
|-----------|-------------|
| 🟢 Arrived | `arrived` |
| 💲 Paid | `paid` |
| 💰 Deposit Paid | `deposit-paid` |
| ❌ No Show | `no-show` / `noshow` |
| ✓ Confirmed | `approved` / `confirmed` (or empty) |

No payment-table mapping — operational badges use Bookly custom status slugs only (ready for Lightspeed-driven status updates later).

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
- `status` ← Bookly customer-appointment status (custom statuses: arrived / paid / deposit-paid / no-show)
- `notes` ← `internal_note` (kept for Sprint 2.5 details panel)
- `phone` ← `bookly_customers.phone` (display-formatted in UI only)

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
