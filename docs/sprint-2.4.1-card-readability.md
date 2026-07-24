# Sprint 2.4.1 — Card Readability

## Goal

Improve readability when many appointments are close together.

## Changes

- Appointment cards use a **completely opaque** background (solid hex / opaque gradient).
- No transparency, no backdrop blur.
- Grid lines and appointments underneath must never show through.
- Rounded corners and shadow unchanged.
- Brand colors preserved (teal accent border, light teal tint).
- Card sizing and adaptive density logic unchanged.
- **No table label** on cards or hover tooltips (matrix columns already group by table; `tableId` remains in the data model).
- **Customer phone** on XL cards and tooltips (`☎ …` from Bookly `customers.phone`); omitted entirely when empty.
- **Card layout hierarchy** — Time → Customer (largest) → Service → Painters → Status → Phone; adaptive by density (compact/standard/large/xl).
- **Status chips** — smaller, lighter weight so customer name stays the focal point (Arrived / Paid / No Show still distinct).
- **Tooltip UX** — no field labels; customer name largest/bold; remaining lines normal weight, left-aligned, read-only.

## Cancelled / rejected

These statuses previously used `opacity: 0.65`, which let the grid show through. They now use muted **opaque** fills instead.

## Acceptance

- [x] Cards are fully opaque
- [x] Adjacent appointments are easy to read
- [x] Calendar remains visually clean
- [x] No appointment or tooltip displays table information
- [x] Phone appears on cards and tooltips when present; empty phone yields no placeholder row
