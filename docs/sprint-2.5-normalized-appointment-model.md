# Sprint 2.5 — Normalized Appointment Model

**Status:** Public API between backend and frontend (stable).  
**Goal:** Define the appointment JSON the frontend consumes so service-aware renderers stay presentation-only.

**Design rules**

- Calendar First — popover is ephemeral; no permanent sidebar.
- Bookly-specific parsing stays in PHP (Repository → Mapper → Provider).
- Renderers use meaningful property names (`birthday.childName`), never raw custom-field IDs.
- Do not reintroduce the Bookly payment-table join for Paid / Deposit Paid.

---

## Public API stability

The **normalized appointment object** (Provider → AJAX → `CAD.State`) is the **public contract** between backend and frontend.

| May change freely | Must stay stable |
|-------------------|------------------|
| Repository SQL / column sources | Property names on the CAD appointment |
| Mapper internal field-ID maps | Existing property types and meaning |
| Filters that only affect PHP | Presence of core keys (`id`, `type`, `customer`, `phone`, …) |

**Evolution rules**

1. Prefer **additive** changes: new properties, new nest fields, new `type` values + renderers.
2. Avoid renaming, removing, or changing the meaning of existing properties.
3. If a breaking change is unavoidable, version it explicitly and migrate the frontend in the same release.
4. Repository and Mapper may be refactored internally as long as Provider output still matches this document.
5. Frontend code must depend only on this contract — never on Bookly schema, field IDs, or `json_data`.

See [Additive evolution](#additive-evolution) below for the current field set.

---

## Current state (as of 2.4.x)

### Data pipeline today

```
bookly_* tables
    → CAD_Bookly_Repository::get_appointments_for_date( $date )
    → CAD_Bookly_Mapper::map_appointment( $row )
    → CAD_Schedule_Provider::get_schedule( $date )
    → AJAX cad_get_schedule
    → CAD.API / CAD.State.appointments
    → calendar / cardRenderer
```

| File | Role |
|------|------|
| `includes/class-cad-bookly-repository.php` | SQL against Bookly tables |
| `includes/class-cad-bookly-mapper.php` | Row → CAD appointment object |
| `includes/class-cad-schedule-provider.php` | `{ date, appointments }` for AJAX |
| `snippets/10A-ajax-bridge.php` | Enqueue + `cad_get_schedule` |

### What the repository selects today

From `bookly_appointments`, `bookly_customer_appointments`, `bookly_customers`, `bookly_services`:

- appointment id, staff_id, start/end (+ extras_duration)
- `internal_note`
- `ca.status` (appointment / custom status slug)
- `ca.number_of_persons`
- customer name, customer phone
- service title, service duration

**Not selected today:** custom field values / `json_data` / service id / category.

### Inspection note — custom fields

- CAD does **not** currently expose custom fields or `json_data` in the schedule payload.
- Bookly Custom Fields add-on stores field **definitions** in the WordPress option `bookly_custom_fields_data` (not normalized columns).
- Per-appointment **values** live on the customer-appointment row (commonly a JSON column such as `custom_fields`; some sites/payloads refer to related JSON as `json_data`). Live birthday payloads already contain the needed values — the repository must select the correct column for this install.
- Field definitions are keyed by **numeric IDs**. Mapping ID → semantic name belongs in the **mapper**, not in JS renderers.

### Current mapper output (UI appointment shape today)

```json
{
  "id": "12345",
  "tableId": "3",
  "start": "2026-07-27T10:00:00-04:00",
  "end": "2026-07-27T11:30:00-04:00",
  "customer": "Lindsey Mueller",
  "phone": "519-267-9080",
  "service": "Birthday Party",
  "status": "approved",
  "painters": 8,
  "notes": ""
}
```

| Property | Source | Notes |
|----------|--------|--------|
| `id` | `a.id` | Keep for selection / Bookly deep-link; **do not show** in popover body |
| `tableId` | `a.staff_id` | Column grouping only; **do not show** as “Table” in popover |
| `start` / `end` | appointment dates | ISO; UI formats with locale |
| `customer` | customer name | Display as-is |
| `phone` | `bookly_customers.phone` | Raw string; UI formats via `CAD.utils.formatPhone()` |
| `service` | service title | Used for display + type detection input |
| `status` | `ca.status` | Custom status slug (see below) |
| `painters` | `ca.number_of_persons` | Integer ≥ 1 |
| `notes` | `a.internal_note` | Optional |

**No** `paid` boolean, **no** payment-table join (Paid is a status slug).

---

## Target normalized model (Sprint 2.5)

### Principles

1. **One CAD appointment object** per schedule block (still one row per appointment id after uniqueness).
2. **`type`** tells the popover which renderer to use (`studio` | `birthday` | `event` …).
3. **Service-specific nests** hold named fields (`birthday`, later `event`, etc.).
4. **ID → name mapping** is PHP-only (filterable registry). Frontend never sees `79073`.
5. **Presentation helpers** (`formatPhone`, status labels/icons) stay in JS.

### Proposed appointment shape

```json
{
  "id": "12345",
  "tableId": "3",
  "type": "birthday",
  "start": "2026-07-27T10:00:00-04:00",
  "end": "2026-07-27T11:30:00-04:00",
  "customer": "Lindsey Mueller",
  "phone": "5192679080",
  "service": "Birthday Party",
  "serviceId": "42",
  "status": "deposit-paid",
  "painters": 8,
  "notes": "",
  "birthday": {
    "childName": "Emma",
    "age": "10",
    "package": "ARTY-Tile Package"
  },
  "studio": null,
  "event": null
}
```

Studio reservation example:

```json
{
  "id": "67890",
  "tableId": "1",
  "type": "studio",
  "start": "2026-07-27T14:00:00-04:00",
  "end": "2026-07-27T16:00:00-04:00",
  "customer": "Ashley Hamby",
  "phone": "519-555-1234",
  "service": "Studio Reservation",
  "serviceId": "7",
  "status": "approved",
  "painters": 2,
  "notes": "",
  "birthday": null,
  "studio": {},
  "event": null
}
```

`studio` / `event` may stay empty objects `{}` until type-specific custom fields exist; renderers treat missing/empty nests as “no extras.”

### Core fields (all types)

| Property | Type | UI use |
|----------|------|--------|
| `id` | string | Selection, Open in Bookly; never displayed as “Appointment ID” |
| `tableId` | string | Calendar column only |
| `type` | `"studio"` \| `"birthday"` \| `"event"` | Renderer selection |
| `start` / `end` | ISO string | Time range |
| `customer` | string | Name (largest in popover) |
| `phone` | string \| null | Always present; `null` when empty; UI formats via `CAD.utils.formatPhone(phone)` |
| `service` | string | Service title line |
| `serviceId` | string \| null | Optional; type rules / filters (not shown) |
| `status` | string | Slug for badge + status actions |
| `painters` | number | “N painters” / 👥 |
| `notes` | string | Notes section; omit section if empty |

### Nested: `birthday`

| Property | Meaning | Popover label (example) |
|----------|---------|-------------------------|
| `childName` | Birthday child’s name | Birthday Child → value |
| `age` | Age turning / age text | e.g. “Turning 10” (renderer formats) |
| `package` | Package name | Package → value |

Omit individual lines when the value is empty. Omit the whole birthday block when `type !== "birthday"` or `birthday` is null.

### Nested: `studio` (placeholder)

Reserved for future studio-specific fields. Initial renderer uses core fields only.

### Nested: `event` (future)

Same pattern as birthday — add nest + renderer without changing the popover shell.

---

## Status slugs (unchanged from 2.4.x)

Operational badges / actions use **Bookly custom status** on `ca.status` only.

| Slug | Display |
|------|---------|
| `approved` / `confirmed` | ✓ Confirmed (no chip badge) |
| `deposit-paid` | 💰 Deposit Paid |
| `arrived` | 🟢 Arrived |
| `paid` | 💲 Paid |
| `no-show` / `noshow` | ❌ No Show |

One status at a time (Bookly model). No `bookly_payments` join.

---

## Where normalization happens

```
Repository
  • SELECT existing schedule columns
  • ADD: service id (recommended), custom-field JSON column (e.g. custom_fields / site-specific json_data)
  • Do NOT join bookly_payments for badge state

Mapper  ← primary normalization layer
  • Map core CAD fields (today’s shape + serviceId)
  • Decode custom-field JSON (id → value pairs)
  • Apply PHP field registry: Bookly ID → birthday.childName / age / package
  • Resolve type: studio | birthday | event (rules + filters)
  • Emit nested objects with string values (never raw IDs in output)
  • Registry configurable via filter, e.g. cad_scheduler_custom_field_map

Provider
  • Pass-through schedule envelope; optional filter cad_scheduler_appointment

Frontend
  • CAD.Popover → CAD.Renderers.render(appointment) by appointment.type
  • formatPhone, status icons/labels, layout only
  • No CAD.customFields ID maps in renderers
```

### PHP field registry (not frontend)

Keep ID knowledge server-side:

```php
// Conceptual — implement in a later mapper change, not in this doc sprint.
$map = apply_filters( 'cad_scheduler_custom_field_map', array(
	'birthday' => array(
		'childName' => 79073,
		'age'       => 84803,
		'package'   => 76858,
	),
) );
```

Frontend receives only:

```js
appointment.birthday.childName // "Emma"
```

### Type detection (mapper responsibility)

Suggested order (filterable later):

1. Explicit override if present in row/meta.
2. Service title / category heuristics (e.g. title contains “Birthday”).
3. Presence of populated birthday-mapped custom fields → `birthday`.
4. Default → `studio`.

Document heuristics in code comments when implemented; keep them out of JS.

---

## What stays presentation-only

| Concern | Layer |
|---------|--------|
| Which popover layout | Frontend renderer registry by `type` |
| Phone formatting `(519) 267-9080` | `CAD.utils.formatPhone` |
| Status emoji / “✓ Confirmed” copy | Card / popover UI |
| Adaptive card density | `CAD.cardRenderer` (unchanged) |
| Hide id / table / email / raw IDs | UI (never render those) |
| Custom field ID → name | **Mapper only** |
| Parsing Bookly JSON | **Repository select + Mapper decode** |

---

## Popover contract

```
CAD.Popover.render(appointment)
  → renderer = CAD.Renderers.get(appointment.type)  // unknown → studio
  → renderer.render(appointment)   // { title, body }
```

Registry shape:

```js
{
  studio:   ReservationRenderer,  // also the unknown-type fallback
  birthday: BirthdayRenderer,
  event:    EventRenderer,
}
```

`CAD.Popover` owns only: positioning, open/close, focus, shared chrome (notes, status actions, Open in Bookly).  
It never branches on booking type — it only asks the registry.  
Renderers own type-specific title and body content.

**Out of scope for Sprint 2.5:** add / delete / edit / resize / drag-create, Lightspeed automation.

---

## Additive evolution

The Provider appointment shape is the public API. Future work should:

1. **Keep** all existing core fields (`id`, `tableId`, `type`, `start`, `end`, `customer`, `phone`, `service`, `serviceId`, `status`, `painters`, `notes`).
2. **Keep** type nests (`birthday`, `studio`, `event`) with the same null / object semantics.
3. **Add** new properties or nest fields rather than renaming or removing.
4. **Add** new `appointment.type` values via `CAD.Renderers.register` without changing the popover shell.
5. Cards may ignore unknown nests/properties; popover/renderers opt into new fields.
6. Leave `CAD_SCHEDULER_VERSION` at the live CDN pin until the matching Git tag is on jsDelivr.

Repository SQL and Mapper ID maps may change anytime; Provider JSON should not break consumers.

---

## Acceptance for this documentation step

- [x] Current vs proposed appointment shapes documented
- [x] Birthday nest uses meaningful names (`childName`, `age`, `package`)
- [x] Normalization assigned to Repository/Mapper (not UI)
- [x] Status / phone / painters / notes carried forward from 2.4.x
- [x] No payment-table join
- [x] Mapper/registry implementation — complete
- [x] Popover UI — complete (see [sprint-2.5-appointment-popover.md](sprint-2.5-appointment-popover.md))
