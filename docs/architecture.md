# Architecture

## Overview

CAD Scheduler replaces the default Bookly frontend with a custom multi-table studio scheduling interface. WordPress handles authentication and data persistence via Bookly; the frontend modules in `src/` render and manage the scheduling UI.

## Layers

```
WordPress + Bookly (backend)
        ↕  AJAX bridge (snippets/10A-ajax-bridge.php)
    cad-core.js (state, API)
        ↕
    cad-ui.js (layout orchestration)
        ↕
  cad-components.js / cad-editor.js / cad-calendar.js
        ↕
    assets/ (css, images)
```

## Modules

### cad-core.js

Central configuration, event bus, and Bookly API communication.

### cad-ui.js

Top-level UI shell: mounts components, handles global layout and navigation.

### cad-components.js

Shared UI primitives (tables, time slots, staff rows, modals).

### cad-editor.js

Inline editing: drag, resize, assign staff/tables, validate conflicts.

### cad-calendar.js

Multi-table calendar grid: day/week views, table columns, availability overlay.

## Data Flow

1. Page loads; `10A-ajax-bridge.php` enqueues scripts and exposes AJAX endpoints.
2. `cad-core.js` fetches appointments, staff, and table config from Bookly.
3. `cad-calendar.js` renders the grid; `cad-editor.js` handles user edits.
4. Changes POST back through the bridge; Bookly persists to the database.
