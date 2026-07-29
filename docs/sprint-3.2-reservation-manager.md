# Sprint 3.2 — Reservation Manager

**Status:** Shipped in **3.2.0** (native **3.2.1**; UX **3.2.2**; layout **3.2.3**; UI **3.2.4**)

**Goal:** Replace Bookly’s edit dialogs for day-to-day reservation management inside CAD Scheduler.

## Behaviour

| Action | Result |
|--------|--------|
| Click existing reservation | Opens **Reservation Manager** |
| Click empty slot | **Quick Add** (3.1) unchanged |
| Save | Bookly `checkTime` + `save`; update customer in place; patch State; re-render; toast |

## Layout (3.2.4)

1. **Sticky chrome** — title + summary card (service • table • date • time • painters)  
2. **Reservation** — Service/Table; Date; Start/End; **Painters / Duration**  
3. **Customer** — first / last / phone / email  
4. **Reservation Details** — dynamic Bookly custom fields (textareas full width)  
5. **Notes** — Reservation Notes · Studio Notes side-by-side  
6. **Status** — compact chips  
7. **Sticky footer** — Cancel / Save Reservation  

See [sprint-3.2.4-reservation-manager-ui.md](sprint-3.2.4-reservation-manager-ui.md).

## Architecture

```
Reservation Manager (UI)
        ↓
Common sections (reservation / customer / notes / status)
        ↓
Dynamic service detail fields (API-driven)
        ↓
Provider::save_reservation → Bookly Utils\Appointment::save
```

The UI does **not** branch on Birthday / Studio / Event. Field lists come from:

1. `bookly_custom_fields_data` (preferred — service-assigned fields), or  
2. Fallback via `cad_scheduler_custom_field_map` + `cad_scheduler_reservation_detail_field_fallback`

Sites should set Bookly field IDs with:

```php
add_filter( 'cad_scheduler_custom_field_map', function ( $map ) {
  $map['studio']['specialOccasion'] = 12345;
  $map['studio']['appointmentNotes'] = 12346;
  $map['birthday']['guests'] = 12347;
  $map['birthday']['nonPainters'] = 12348;
  return $map;
} );
```

## Surfaces

| Layer | File |
|-------|------|
| UI | `src/components/reservation-manager.js` |
| API | `CAD.API.getReservation` / `saveReservation` |
| AJAX | `cad_get_reservation`, `cad_save_reservation` |
| Provider | `get_reservation()`, `save_reservation()` |
| Mapper | `map_detail_fields()` |
| Repository | `get_custom_field_definitions()` |

## Out of scope (later)

Customer search, Lightspeed, resize, delete, payments — hooks/UI reserved for future sprints.

## Deploy

1. Redeploy snippets **10** (Repository), **11** (Mapper), **12** (Provider), **20** (Bridge)  
2. Pin `CAD_SCHEDULER_VERSION` to the current release (see changelog)  
3. Hard-refresh
