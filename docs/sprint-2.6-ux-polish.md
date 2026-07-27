# Sprint 2.6 — Scheduler UX Polish (Final)

**Scope:** Presentation only. No repository / mapper / provider / API / normalized model changes.

## Cards (max ~4 lines)

| Type | Lines |
|------|--------|
| **Birthday** | Child name (largest) → booking contact → painter badge + status badge |
| **Studio** | Customer name → painter badge + status badge |
| **Event** | Event/service name → painter badge + status badge |

- No phone / email on cards.
- Left accent + wash by `appointment.type` — Crock A Doodle: **teal** (studio), **purple** (birthday), **orange** (event).
- Status badge is visually dominant; painter badge stays secondary.
- Compact density: primary name only (badges hidden).

## Shared badges — `CAD.Badges` (`src/components/badges.js`)

One reusable component for cards, helpers, and popover status:

| Class | Role |
|-------|------|
| `.cad-badge` | Shared height, padding, radius |
| `.cad-badge--painters` | Secondary `👥 N` chip |
| `.cad-badge--status` + `.cad-badge--{key}` | Primary status chip (equal weight for every label) |
| `.cad-badge-row` | Painter + status row |

Status icons/labels:

- 🟢 Approved
- 💰 Deposit Paid
- ✅ Arrived
- 💲 Paid
- ❌ No Show

Enqueue: `cad-badges` before `cad-card-renderer` in `snippets/10A-ajax-bridge.php`.

## Popover

Fixed width ~350px (full width on narrow viewports). Colored type ribbon (Crock A Doodle accents).

Sections in order (empty sections omitted):

1. **Appointment Type** header (ribbon)
2. **Child** (birthday only)
3. **Booked By** (name + phone when present)
4. **Party / Reservation / Event Details** (service, time, package, attendance)
5. **Status** (`CAD.Badges.statusBadge` + quick status buttons)
6. **Internal Notes** (when present)
7. **Actions** (Edit / Delete → Bookly manage URL; no CAD delete API this sprint)

Attendance labels:

- **Painters** — from `appointment.painters`
- **Guests** — hidden until a guests field exists on the model
- **Total Attending** — birthday/event only (presentation of painters as party size)

## Files

| Path | Change |
|------|--------|
| `src/components/badges.js` | Shared `CAD.Badges` API |
| `src/cad-card-renderer.js` | Max 4 lines; type hierarchy; `CAD.Badges` |
| `src/cad-components.js` | `cad-appointment--type-*` class |
| `src/renderers/*` | Sectioned bodies; helpers use badges |
| `src/components/popover.js` | Colored ribbon + Status / Notes / Actions chrome |
| `src/components/status-panel.js` | Status action icons/labels |
| `assets/css/cad-scheduler.css` | Shared `.cad-badge*`, ribbon, accents, fixed popover width |
| `snippets/10A-ajax-bridge.php` | Enqueue `cad-badges` |
| `docs/fixtures/sprint-2.4-adaptive-cards.html` | Final card/badge checks |
| `docs/fixtures/sprint-2.5-popover.html` | Ribbon + badge checks |

## Verification

1. Birthday card shows child, then contact; accent is purple.
2. Studio card shows customer; accent is teal.
3. Event card shows event name; accent is orange.
4. Status badge reads larger/stronger than the painter badge; all statuses share height/padding/radius.
5. Cards never show phone/email.
6. Popover ribbon color matches type; empty Notes / Child sections hidden.
7. Painters label visible; Guests absent without model field.
8. Sticky headers / scroll / adaptive density still work.
9. Open fixtures for visual check of badge strip + ribbon.
