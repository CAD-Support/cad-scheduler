# Sprint 3.1 — Quick Add Reservation

**Status:** Shipped in **3.1.0**

**Goal:** Let studio staff create a Bookly reservation from an empty scheduler slot without opening the Bookly admin calendar.

## User story

As a studio employee, I want to click an empty time slot so I can quickly create a reservation for a customer.

## MVP behaviour

1. Click empty lane → slot highlight + modal
2. Summary shows **table**, **weekday**, **start time**
3. Form: customer name (required), phone, email, painters, duration (default 90m), notes
4. Save → create/find Bookly customer → `Utils\Appointment::checkTime` + `::save` → refresh grid + toast
5. Cancel closes with no changes

## Surfaces

| Layer | Detail |
|-------|--------|
| `CAD.QuickAdd` | `src/components/quick-add.js` |
| `CAD.API.createAppointment` | `src/cad-api.js` |
| `wp_ajax_cad_create_appointment` | Bridge |
| `CAD_Schedule_Provider::create_appointment` | Bookly customer + appointment create |
| CSS | `.cad-quick-add*`, `.cad-matrix__slot-highlight` |

## Configuration

| Filter / config | Default | Purpose |
|-----------------|---------|---------|
| `cad_scheduler_quick_add_service_id` / `cadConfig.quickAddServiceId` | `0` | Bookly service id (0 = custom “Studio Reservation”) |
| `cad_scheduler_quick_add_custom_service_name` | `Studio Reservation` | Used when service id is unset |
| `cad_scheduler_quick_add_default_duration` | `90` | Minutes |
| `cad_scheduler_quick_add_status` | `approved` | CA status slug |
| `cad_scheduler_quick_add_notify` | `true` | Bookly notifications after create |
| `cad_scheduler_create_appointment_capability` | `edit_posts` | AJAX capability |

Prefer setting a real Bookly **Studio Reservation** service id in production so reports and capacity rules match admin bookings.

## Out of scope (later)

Customer search, Lightspeed lookup, edit/delete, drag-resize, payments, status changes in the create form.

## Deploy

1. Redeploy snippets **12** (Provider) and **20** (AJAX Bridge)
2. Pin `CAD_SCHEDULER_VERSION` to **3.1.0**
3. Hard-refresh the scheduler page
