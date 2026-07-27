# Architecture

## Overview

CAD Scheduler replaces the default Bookly frontend with a custom multi-table studio scheduling interface. WordPress handles authentication and data persistence via Bookly; the frontend modules in `src/` render and manage the scheduling UI.

## Layers

```
Bookly tables
        ↓
Repository (SQL only)
        ↓
Mapper (Bookly → CAD normalization)
        ↓
Provider (clean appointment model / API contract)
        ↓
AJAX bridge (snippets/10A-ajax-bridge.php)
        ↓
CAD.State (cad-core.js)
        ↓
Popover / Renderers (presentation only)
```

Frontend modules in `src/` never see Bookly custom-field IDs, `json_data`, or SQL details.

### Public API (stable)

The Provider’s normalized appointment object is the **public contract** between backend and frontend. Prefer additive changes (new properties / types). Repository and Mapper may evolve internally; do not break or remove existing appointment properties without a coordinated release. See [Normalized appointment model](sprint-2.5-normalized-appointment-model.md#public-api-stability).

## Modules

### cad-core.js

Central configuration (`CAD.Config`), state (`CAD.State`), display helpers (`CAD.utils`, e.g. `formatPhone`), and `CAD.init()`.

### cad-api.js

WordPress AJAX wrapper (`CAD.API.getSchedule(date)` → `cad_get_schedule`).

### cad-ui.js

Top-level UI shell: mounts the scheduler, ensures status + calendar containers, loads appointments.

### cad-navigation.js

Day navigation: Previous / Today / Next / native date picker. Formats the header with the browser locale; always stores and sends `YYYY-MM-DD`.

### cad-components.js

Appointment block shell: applies layout (`top` / `height`) and delegates card content to `CAD.cardRenderer`.

### cad-card-renderer.js

Height-based adaptive card content (`CAD.cardRenderer.render(appointment, availableHeight)`), read-only status badges, and desktop hover tooltips. The calendar only supplies available height.

### src/renderers/

Service-aware popover bodies (`CAD.Renderers.render(appointment)` by `appointment.type`). Presentation only — uses `birthday.childName` etc., never Bookly field IDs.

- `helpers.js` — shared formatting
- `registry.js` — `appointment.type` → renderer map (`get()`; unknown → studio)
- `reservation-renderer.js` — `studio` (`CAD.ReservationRenderer`)
- `birthday-renderer.js` — `birthday` (`CAD.BirthdayRenderer`)
- `event-renderer.js` — `event` (`CAD.EventRenderer`)

### src/components/

- `popover.js` — ephemeral shell (`CAD.Popover.render` / `open`); notes + Open in Bookly
- `status-panel.js` — quick status actions (Bookly custom status slugs only)

### cad-editor.js

Selection; opens `CAD.Popover` with the normalized appointment from state.

### cad-calendar.js

Multi-table matrix grid: tables across top, time down left, duration-sized blocks. Scrollport is `.cad-matrix__scroll` with sticky headers and time labels (Sprint 2.2). Layout height is passed through to components; card field choices live in the renderer.

## Data Flow

1. Page loads; `10A-ajax-bridge.php` enqueues scripts and exposes AJAX endpoints.
2. `CAD.init(cadConfig)` → `CAD.ui.mount()` → `CAD.Navigation.init()` → `CAD.ui.load(today)`.
3. Navigation updates `CAD.State.selectedDate`, then reuses `CAD.API.getSchedule(date)`.
4. Provider returns normalized appointments only; calendar re-renders blocks.
5. Card click → `CAD.Popover.render(appointment)` → `CAD.Renderers.render(appointment)`.
6. Status updates POST through the bridge; Bookly custom statuses persist (no payment-table join).
