# Sprint 3.1 — Quick Add Reservation

**Status:** Shipped in **3.1.0**

**Goal:** Let studio staff create a Bookly reservation from an empty scheduler slot without opening the Bookly admin calendar.

## User story

As a studio employee, I want to click an empty time slot so I can quickly create a reservation for a customer.

## MVP behaviour

1. Click empty lane → slot highlight + modal
2. Summary shows **table**, **weekday**, **start time**
3. Form: **service** (required Bookly service), customer name (required), phone, email, painters, duration (defaults from service), notes
4. Save → create/find Bookly customer → `Utils\Appointment::checkTime` + `::save` with real `service_id` → refresh grid + toast
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
| `cadConfig.services` / `cad_scheduler_services` | Bookly list | Services for the dropdown |
| `cad_scheduler_quick_add_service_id` / `cadConfig.quickAddServiceId` | `0` | Default pre-selected Bookly service id |
| `cad_scheduler_native_service_id` | — | Resolve/require native service id |
| `cad_scheduler_quick_add_default_duration` | `90` | Fallback minutes if service duration unknown |
| `cad_scheduler_quick_add_status` | `approved` | CA status slug |
| `cad_scheduler_quick_add_notify` | `true` | Bookly notifications after create |
| `cad_scheduler_create_appointment_capability` | `edit_posts` | AJAX capability |

A real Bookly service is **required**. Custom service names are not created (see [sprint-3.2.1-native-bookly-compatibility.md](sprint-3.2.1-native-bookly-compatibility.md)).

## Out of scope (later)

Customer search, Lightspeed lookup, edit/delete, drag-resize, payments, status changes in the create form.

## Deploy

1. Redeploy snippets **12** (Provider) and **20** (AJAX Bridge)
2. Pin `CAD_SCHEDULER_VERSION` to the current release (see changelog)
3. Hard-refresh the scheduler page
