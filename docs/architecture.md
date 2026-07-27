# Architecture

## Overview

CAD Scheduler replaces the default Bookly frontend with a custom multi-table studio scheduling interface. WordPress handles authentication and data persistence via Bookly; the frontend modules in `src/` render and manage the scheduling UI.

## Layers

```
WordPress + Bookly (backend)
        ↕  AJAX bridge (snippets/10A-ajax-bridge.php)
    cad-core.js (config, state)
        ↕
    cad-api.js (WordPress AJAX)
        ↕
    cad-ui.js (shell + load orchestration)
        ↕
    cad-navigation.js (day controls)
        ↕
  cad-components.js / cad-card-renderer.js / cad-editor.js / cad-calendar.js
        ↕
    assets/ (css, images)
```

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

### cad-editor.js

Selection / future inline editing.

### cad-calendar.js

Multi-table matrix grid: tables across top, time down left, duration-sized blocks. Scrollport is `.cad-matrix__scroll` with sticky headers and time labels (Sprint 2.2). Layout height is passed through to components; card field choices live in the renderer.

## Data Flow

1. Page loads; `10A-ajax-bridge.php` enqueues scripts and exposes AJAX endpoints.
2. `CAD.init(cadConfig)` → `CAD.ui.mount()` → `CAD.Navigation.init()` → `CAD.ui.load(today)`.
3. Navigation updates `CAD.State.selectedDate`, then reuses `CAD.API.getSchedule(date)`.
4. `cad-calendar.js` re-renders appointment blocks in the existing shell.
5. Future edits POST back through the bridge; Bookly persists to the database.
