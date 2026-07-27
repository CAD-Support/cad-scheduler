# Sprint 2.5 — Service-Aware Appointment Popover

**Calendar First:** ephemeral popover only; no permanent sidebar.  
**Contract:** [Normalized appointment model](sprint-2.5-normalized-appointment-model.md)

## Pipeline

```
Bookly tables
  → Repository (SQL only)
  → Mapper (Bookly → CAD normalization)
  → Provider (clean appointment model / API contract)
  → AJAX
  → CAD.State
  → CAD.Popover.render(appointment)
       → CAD.Renderers.render(appointment)  // by appointment.type
```

Frontend never reads Bookly custom field IDs, `json_data`, or DB schema details.

## Frontend layout

| Path | Role |
|------|------|
| `src/components/popover.js` | Shell only: open/close, ESC, focus, shared chrome — no type branches |
| `src/components/status-panel.js` | Quick status buttons (custom status slugs only) |
| `src/renderers/registry.js` | `CAD.Renderers` map keyed by `appointment.type`; unknown → studio |
| `src/renderers/helpers.js` | Shared presentation helpers |
| `src/renderers/reservation-renderer.js` | `studio` layout |
| `src/renderers/birthday-renderer.js` | `birthday` layout (`birthday.childName` etc.) |
| `src/renderers/event-renderer.js` | `event` placeholder |
| `includes/class-cad-bookly-mapper.php` | Normalization + field ID registry |
| `includes/class-cad-bookly-repository.php` | Selects `custom_fields` (+ filterable SQL) |

Legacy `src/cad-popover.js` / `src/cad-renderers.js` are deprecation stubs only.

## Scope (this sprint)

- Customer, phone, time, painters, birthday details, status
- Quick status buttons, read-only notes, Open in Bookly

**Out of scope:** add / delete / edit / resize / drag-create appointments.

## Verification

1. `php docs/fixtures/verify-mapper-normalize.php` — zero contract failures
2. Open [`fixtures/sprint-2.5-popover.html`](fixtures/sprint-2.5-popover.html)
3. Grep frontend: no Bookly field IDs / `json_data` (except comments)
4. Live: paste updated includes + bridge (`CAD_SCHEDULER_VERSION` stays **2.4.1** until CDN tag)
