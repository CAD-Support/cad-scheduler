# Sprint 3.2.1 — Native Bookly Compatibility

**Status:** Shipped in **3.2.1**

**Goal:** Reservation Manager and Quick Add behave like Bookly for service identity, group booking, custom fields, customers, notifications, analytics, and durations — with CAD’s UI.

## Checklist

| Goal | How |
|------|-----|
| Save real Bookly **Service ID** | Create/save always pass `service_id`; custom name/price cleared (`null`, `''`) |
| Remove **Custom** service creation | No `cad_scheduler_quick_add_custom_service_name` path; missing service → `invalid_service` |
| Group Booking (# of painters) | `number_of_persons` on CA payload (create + save) |
| Service-specific custom fields | `detail_fields` → `merge_custom_field_values` → Bookly `custom_fields` |
| Customer edits update existing | `Customer::load` + save in place (no duplicate) |
| Notifications | `Sender::sendForCA` after create/save when notify filters are true |
| Analytics / reports | Appointments store native `service_id`, so Bookly reports match |
| Service durations synchronized | UI defaults duration from Bookly service; provider falls back to service duration |

## UI

- **Quick Add:** required Service dropdown from `cadConfig.services`; duration follows selected service
- **Reservation Manager:** Service is a Bookly select (not read-only label); changing service syncs duration

## Configuration

| Filter / config | Purpose |
|-----------------|---------|
| `cadConfig.services` / `cad_scheduler_services` | Bookly services (`id`, `name`, `durationMinutes`) |
| `cad_scheduler_quick_add_service_id` / `quickAddServiceId` | Default service pre-select (required to be a real id if set) |
| `cad_scheduler_native_service_id` | Last chance to resolve/require a native service id |
| `cad_scheduler_quick_add_default_duration` | Fallback minutes only when service duration unknown |
| `cad_scheduler_quick_add_notify` / `cad_scheduler_reservation_notify` | Bookly notifications |

**Removed:** creating appointments with a custom service name when `service_id` is 0.

## Deploy

1. Redeploy snippets **10** (Repository), **12** (Provider), **20** (Bridge)  
2. Pin `CAD_SCHEDULER_VERSION` to **3.2.1**  
3. Hard-refresh the scheduler page  
4. Confirm Bookly has at least one visible service for the dropdown  
