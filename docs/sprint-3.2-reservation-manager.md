# Sprint 3.2 — Reservation Manager

**Status:** Shipped in **3.2.0** (native service select in **3.2.1**)

**Goal:** Replace Bookly’s edit dialogs for day-to-day reservation management inside CAD Scheduler.

## Behaviour

| Action | Result |
|--------|--------|
| Click existing reservation | Opens **Reservation Manager** |
| Click empty slot | **Quick Add** (3.1) unchanged |
| Save | Bookly `checkTime` + `save`; update customer in place; patch State; re-render; toast |

## Layout

1. **Reservation** — Bookly service (selectable), table, date, start, end, duration (synced), painters  
2. **Customer** — first / last / phone / email (existing Bookly customer; no duplicates)  
3. **Reservation Details** — dynamic fields from Bookly Custom Fields (service-scoped)  
4. **Studio Notes** — internal notes  
5. **Status** — existing StatusPanel  

Duration updates when start/end change; changing duration updates end. Changing service syncs duration from Bookly (3.2.1).

Native Bookly service id is required on save — see [sprint-3.2.1-native-bookly-compatibility.md](sprint-3.2.1-native-bookly-compatibility.md).

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
