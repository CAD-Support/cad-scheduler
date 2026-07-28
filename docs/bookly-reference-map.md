# Bookly Reference Map — CAD Scheduler

Read-only reference library: `/bookly-reference`

**Rules:** Never modify files under `/bookly-reference`. Use this map before implementing Bookly integrations in CAD Scheduler (`includes/`, `snippets/`, `src/`).

## Bookly Reference Repository Layout

The `bookly-reference` directory is divided into two purposes:

- **Archive Zip files/** — Original CodeCanyon packages preserved for historical reference.
- **Extracted source folders/** — The working reference used to inspect Bookly's implementation and compare CAD Scheduler behaviour.

CAD Scheduler itself is developed entirely outside `bookly-reference`.

```
bookly-reference/
├── Archive Zip files/     ← In git: original vendor ZIP packages
├── core-extract/          ← Local only (gitignored)
├── bookly-addon-pro-10.2/ ← Local only (gitignored)
├── bookly-addon-events-1.6/
└── ...
```

**Important:** The repository intentionally excludes extracted Bookly source code. Extract locally from `Archive Zip files/` into `core-extract/` and `bookly-addon-*/` — see [`bookly-reference/README.md`](../bookly-reference/README.md). CAD Scheduler never executes or modifies code from Archive Zip files.

---

## How to Use This Reference

This document exists to keep CAD Scheduler behaviour aligned with Bookly while remaining upgrade-safe.

For each integration point we document:

- What Bookly does.
- What CAD does.
- Why the implementations are the same or intentionally different.
- How data flows through CAD Scheduler.
- Where to troubleshoot if behaviour differs.

When implementing new Bookly integrations:

1. Inspect the Bookly reference code.
2. Compare current CAD behaviour.
3. Mirror Bookly unless there is a documented business reason not to.
4. Record any intentional differences here.

The [End Time Calculation](#end-time-calculation) section is the reference template for this format.

---

## Architectural Decisions

Documented differences from Bookly that are **intentional**, not compatibility gaps.

### Diagnostics (frontend vs wp-admin)

| | Bookly | CAD Scheduler |
|---|--------|---------------|
| **Context** | Runs primarily in **wp-admin** | Runs on the **frontend** via `[cad_scheduler]` |
| **Missing dependency** | Admin notices, setup wizards | User-facing **diagnostic panel** on the schedule page |
| **Audience** | Site administrators in dashboard | Studio staff on the page where the scheduler is embedded |

**Why CAD differs:** Bookly’s admin notices are appropriate inside wp-admin. CAD has no wp-admin UI — failures must surface where the shortcode renders, or staff see a blank page with no explanation.

**What CAD does:** `cad_scheduler_health()` + diagnostic panel (`cad-scheduler__diagnostics`) via PHP (blocking issues) and JavaScript (config validation). See [Deployment — Diagnostics](deployment.md#diagnostics).

**This is intentional.** Do not replace frontend panels with wp-admin notices unless CAD gains a dedicated admin settings screen.

---

## Library inventory

### Extracted add-ons (16)

| Folder | Version | Plugin name |
|--------|---------|-------------|
| `bookly-addon-pro-10.2` | 10.2 | Bookly Pro |
| `bookly-addon-events-1.6` | 1.6 | Bookly Events |
| `bookly-addon-custom-statuses-2.8` | 2.8 | Bookly Custom Statuses |
| `bookly-addon-customer-cabinet-6.9` | 6.9 | Bookly Customer Cabinet |
| `bookly-addon-customer-groups-4.3` | 4.3 | Bookly Customer Groups |
| `bookly-addon-customer-information-4.1` | 4.1 | Bookly Customer Information |
| `bookly-addon-custom-fields-5.3` | 5.3 | Bookly Custom Fields |
| `bookly-addon-collaborative-services-4.0` | 4.0 | Bookly Collaborative Services |
| `bookly-addon-compound-services-4.3` | 4.3 | Bookly Compound Services |
| `bookly-addon-chain-appointments-2.5` | 2.5 | Bookly Chain Appointments |
| `bookly-addon-cart-3.4` | 3.4 | Bookly Cart |
| `bookly-addon-coupons-5.4` | 5.4 | Bookly Coupons |
| `bookly-addon-discounts-2.2` | 2.2 | Bookly Discounts |
| `bookly-addon-deposit-payments-3.9` | 3.9 | Bookly Deposit Payments |
| `bookly-addon-advanced-google-calendar-3.1` | 3.1 | Bookly Advanced Google Calendar |
| `bookly-addon-authorize-net-3.5` | 3.5 | Bookly Authorize.Net |

### Core Bookly (local extract)

Core source is **not in git**. Extract from `Archive Zip files/` into `bookly-reference/core-extract/`:

```
bookly-reference/core-extract/bookly-responsive-appointment-booking-tool/
```

Typical paths: `backend/modules/calendar/`, `lib/entities/`, `lib/utils/DateTime.php`.

All add-ons import core classes as `Bookly\Lib\...` and `Bookly\Backend\...`. For calendar queries, appointment save flows, and entity definitions, extract into `core-extract/` or inspect the live plugin on a WordPress install.

### Bookly Archive (ZIP packages, not extracted)

Original vendor ZIP packages live in `/bookly-reference/Archive Zip files/`. See that folder’s [README](../bookly-reference/Archive%20Zip%20files/README.md).

These ZIPs are **read-only** and **not part of the CAD Scheduler build**. Development and Bookly parity analysis use extracted source folders under `/bookly-reference/` (local only — see [`.gitignore`](../.gitignore)).

Archive contents include: core Bookly, Locations, Packages, Recurring Appointments, Group Booking, Staff Cabinet, Service Extras, Service Schedule, Special Hours, Special Days, Waiting List, Tasks, Invoices, Files, Taxes, Stripe, PayPal, PayU, Outlook Calendar, Google Maps Address, Custom Duration, Multiply Appointments, Multisite, Mailchimp, Ratings, and duplicate ZIP copies of extracted add-ons.

Re-extract a ZIP into `/bookly-reference` before inspecting if CAD needs that add-on. Extracted paths are gitignored; see [`bookly-reference/README.md`](../bookly-reference/README.md).

---

## How Bookly extends itself (Proxy system)

Bookly add-ons rarely use raw `add_action` / `apply_filters` for cross-module integration. Instead they register **Proxy providers**:

- Classes in `{Addon}\...\proxy_providers\Local` or `Shared`
- Extend abstract proxies under `Bookly\Lib\Proxy\*` (core)
- Registered via `ProxyProviders\Local::init()` in each add-on's `Plugin::init()`

**Implication for CAD:** Custom code cannot hook most Bookly internals without either:

1. Using `$wpdb` against Bookly tables (current CAD approach), or
2. Calling Bookly PHP APIs if exposed, or
3. Adding WordPress filters only where Bookly explicitly documents them (e.g. `bookly_plugins`, `cad_scheduler_tables` is CAD-owned)

### WordPress filters found in reference

| Filter | Location | Purpose |
|--------|----------|---------|
| `bookly_plugins` | Pro `lib/proxy_providers/Shared.php`, license UI | Lists installed Bookly add-ons |

Most integration points are **Proxy methods**, not WordPress hook names.

### AJAX registration pattern

Add-on AJAX classes extend `Bookly\Lib\Base\Ajax`. Public static methods become WordPress AJAX actions following Bookly's naming convention (typically `bookly_{action}` registered for `wp_ajax_*` and often `wp_ajax_nopriv_*`).

Permissions are declared per class via `protected static function permissions()` (e.g. `'anonymous'`, `'supervisor'`, `'staff'`).

---

## Confirmed Bookly behaviour (baseline for CAD)

Verified against Pro frontend calendar and related reference code:

| Behaviour | Bookly pattern |
|-----------|----------------|
| Appointment end time | `DATE_ADD(a.end_date, INTERVAL a.extras_duration SECOND)` |
| Calendar deduplication | One item per `a.id` when multiple `customer_appointments` rows exist |
| Staff visibility | Exclude `archive` only; **private staff remain visible** in admin calendar |
| Status source | `bookly_customer_appointments.status` |
| Multi-customer | Several `ca` rows can belong to one appointment |

## End Time Calculation

Permanent reference for Bookly parity. Follow [How to Use This Reference](#how-to-use-this-reference); this section is the template for documenting integration points.

CAD mirrors Bookly's SQL expression in the repository query to minimize behavioural differences and simplify long-term maintenance.

### Bookly (Pro Calendar)

**Reference:** `bookly-reference/bookly-addon-pro-10.2/bookly-addon-pro/frontend/modules/calendar/Ajax.php`

**What Bookly does:**

```sql
DATE_ADD(a.end_date, INTERVAL a.extras_duration SECOND) AS end_date
```

Bookly computes the calendar block end time in SQL by adding service extras (`extras_duration`, in seconds) to the stored `end_date`.

### CAD (Scheduler)

**File:** `includes/class-cad-bookly-repository.php`

**What CAD does:**

```sql
DATE_ADD(a.end_date, INTERVAL COALESCE(a.extras_duration, 0) SECOND) AS end_date
```

CAD uses the same SQL expression in the repository query. The mapper does not recalculate duration in PHP.

**Why the SQL is slightly different (`COALESCE`):**

Bookly selects `a.extras_duration` directly. CAD wraps it with `COALESCE(a.extras_duration, 0)` so a null column behaves like zero seconds of extras. When `extras_duration` is `0` or null, the result matches Bookly. This is a defensive read-layer guard, not a behavioural choice.

**How data flows (Repository → Mapper → Frontend):**

1. **Repository** (`class-cad-bookly-repository.php`) — SQL returns effective `end_date` via `DATE_ADD`.
2. **Mapper** (`class-cad-bookly-mapper.php`) — converts `end_date` to ISO 8601 (`start` / `end` in JSON). If computed `end_date` is null, falls back to `start + service_duration`.
3. **Frontend** (`cad-calendar.js`, `cad-components.js`) — uses `start` and `end` for block position, height, and displayed time labels. No end-time logic in JavaScript.

If appointment timing is wrong on the grid, inspect in that order: repository SQL → mapper ISO output → calendar layout math.

**CAD intentional differences** (must stay documented if kept):

- **Diagnostics** — frontend diagnostic panels instead of Bookly-style wp-admin notices; see [Architectural Decisions — Diagnostics](#diagnostics-frontend-vs-wp-admin)

**CAD aligned with Bookly:**

- Staff query excludes `archive` only (includes `public` and `private`; NULL/empty treated as visible) — `includes/class-cad-bookly-repository.php`
- Staff columns ordered by Bookly `position` — same file; see [Sprint 2.5.1](sprint-2.5.1-all-bookly-resources.md)
- Appointment end time — see [End Time Calculation](#end-time-calculation) above

---

## Core Bookly (inferred from add-on imports)

Not extracted in reference; entities and tables below are **inferred** from `use Bookly\Lib\Entities\...` across all add-ons.

### Relevant core entities (referenced by add-ons)

| Entity | Typical use |
|--------|-------------|
| `Appointment` | `bookly_appointments` — start/end, staff, service |
| `CustomerAppointment` | `bookly_customer_appointments` — status, customer link, tokens |
| `Customer` | `bookly_customers` — name, contact |
| `Staff` | `bookly_staff` — tables in CAD; visibility, position |
| `Service` | `bookly_services` — title, duration, color |
| `StaffService` | Staff–service capacity and pricing |
| `SubService` | Compound/collaborative sub-steps |
| `Payment` | Payments linked to appointments |
| `Notification` | Email/SMS templates |

### Relevant core classes (not in reference tree)

| Class | CAD relevance |
|-------|---------------|
| `Bookly\Backend\Modules\Calendar\Ajax` | `getAppointmentsQueryForCalendar()`, `buildStaffSchedule()` — **primary calendar query reference** |
| `Bookly\Lib\Base\Ajax` | AJAX action registration and permissions |
| `Bookly\Lib\Proxy\Shared` | Shared extension points (colors, queries, notifications) |
| `Bookly\Lib\Proxy\CustomStatuses` | Busy/free status lists when Custom Statuses add-on active |
| `Bookly\Lib\Utils\DateTime` | Timezone conversion for calendar display |

### Core tables CAD already uses

| Table | CAD usage |
|-------|-----------|
| `{prefix}bookly_staff` | Studio tables (`get_staff_tables`) |
| `{prefix}bookly_appointments` | Appointment times, staff assignment |
| `{prefix}bookly_customer_appointments` | Status, customer link |
| `{prefix}bookly_customers` | Customer display name |
| `{prefix}bookly_services` | Service title, duration fallback |

### Core AJAX (inferred; lives in core plugin)

Backend calendar and appointment CRUD handlers are in core `Bookly\Backend\Modules\*` — not present as extracted source. Pro extends:

- `BooklyPro\Backend\Modules\Appointments\Ajax` → extends `\Bookly\Backend\Modules\Appointments\Ajax`

For appointment **save flows**, inspect core `Backend\Components\Dialogs\Appointment\Edit\Ajax` after extracting core ZIP.

### CAD should reference core?

**Yes — always.** CAD reads core tables directly. Before changing queries, extract or inspect core `Calendar\Ajax` and entity schemas.

---

## Add-on reference entries

Legend for **CAD should reference?**

| Rating | Meaning |
|--------|---------|
| **Yes** | Inspect before related CAD work; affects schedule display or data model |
| **Maybe** | Reference if that add-on is installed at the studio |
| **No** | Booking/checkout/payment only; not needed for read-only studio grid v2 |

---

### 1. Bookly Pro (`bookly-addon-pro-10.2`)

**Purpose:** Unlocks Pro features, payments, frontend calendar, staff categories, tags, forms, Google/Zoom integrations, WooCommerce, modern booking form, license management. Required for other add-ons.

**Relevant classes**

| Class | Role |
|-------|------|
| `BooklyPro\Frontend\Modules\Calendar\Ajax` | Public frontend calendar appointments |
| `BooklyPro\Backend\Modules\Calendar\ProxyProviders\Local` | Admin calendar UI extensions |
| `BooklyPro\Backend\Modules\Staff\Ajax` | Staff categories, archive staff |
| `BooklyPro\Backend\Modules\Appointments\Ajax` | CSV export (extends core) |
| `BooklyPro\Lib\ProxyProviders\Local` | Staff dropdown, Google Calendar, cart validation |
| `BooklyPro\Lib\ProxyProviders\Shared` | Query prep, online meetings, notifications |
| `BooklyPro\Lib\Entities\StaffCategory` | Staff grouping |
| `BooklyPro\Lib\Entities\Tag` | Service/customer tags |
| `BooklyPro\Lib\Entities\Form` | Custom booking forms |
| `BooklyPro\Lib\Entities\EmailLog` | Sent email log |
| `BooklyPro\Lib\Entities\StaffPreferenceOrder` | Staff ordering per service |

**Relevant AJAX handlers**

| Handler class | Methods (public static) |
|---------------|---------------------------|
| `Frontend\Modules\Calendar\Ajax` | `getCalendarAppointments` |
| `Frontend\Modules\ModernBookingForm\Ajax` | `modernBookingFormGetServices`, `modernBookingFormGetSlots`, `modernBookingFormSave`, `modernBookingFormGetCalendarSchedule`, … |
| `Frontend\Modules\Booking\Ajax` | `applyTips`, `facebookLogin`, `cancelAppointments`, `bbb` |
| `Backend\Modules\Staff\Ajax` | `addStaffCategory`, `deleteStaffCategory`, `renameStaffCategory`, `archivingStaff` |
| `Backend\Components\Dialogs\Staff\Edit\Ajax` | `updateStaffAdvanced`, `getStaffAdvanced`, `checkGoogleCalendars` |
| `Backend\Modules\Appointments\Ajax` | `exportAppointments` |
| `Backend\Modules\Settings\Ajax` | Zoom OAuth helpers |
| `Backend\Modules\Appearance\Ajax` | Form appearance CRUD |
| `Backend\Components\License\Ajax` | License verification (not CAD relevant) |

**Relevant entities (add-on)**

- `StaffCategory`, `Tag`, `Form`, `EmailLog`, `StaffPreferenceOrder`

**Database tables (add-on)**

| Table | Purpose |
|-------|---------|
| `{prefix}bookly_staff_categories` | Staff category names, position |
| `{prefix}bookly_tags` | Tag labels and colors |
| `{prefix}bookly_forms` | Saved booking form layouts |
| `{prefix}bookly_email_log` | Email send log |
| `{prefix}bookly_staff_preference_orders` | Per-service staff sort order |

**Relevant hooks / proxies**

- `BooklyPro\Lib\ProxyProviders\Local::getStaffDataForDropDown()` — staff list excludes `archive` by default
- `BooklyPro\Frontend\Modules\Calendar\Ajax::getCalendarAppointments()` — canonical read pattern for calendar data
- Uses `Bookly\Backend\Modules\Calendar\Ajax::getAppointmentsQueryForCalendar()` (core)
- `Bookly\Lib\Proxy\CustomStatuses::prepareBusyStatuses()` when filtering busy slots

**CAD should reference?** **Yes** — primary source for calendar query shape, staff visibility, end-time calculation, and deduplication.

**Key file:** `bookly-reference/bookly-addon-pro-10.2/bookly-addon-pro/frontend/modules/calendar/Ajax.php`

---

### 2. Bookly Custom Statuses (`bookly-addon-custom-statuses-2.8`)

**Purpose:** Additional appointment status slugs beyond pending/approved/cancelled/rejected/waitlisted; busy/free classification.

**Relevant classes**

| Class | Role |
|-------|------|
| `BooklyCustomStatuses\Lib\Entities\CustomStatus` | Status definition entity |
| `BooklyCustomStatuses\Lib\ProxyProviders\Local` | Implements `Bookly\Lib\Proxy\CustomStatuses` |
| `BooklyCustomStatuses\Lib\ProxyProviders\Shared` | Calendar color map, settings UI |

**Relevant AJAX handlers**

| Class | Methods |
|-------|---------|
| `Backend\Modules\Settings\Ajax` | `getStatuses`, `updateStatusesPosition`, `deleteStatuses`, `saveStatus` |

**Entities**

- `CustomStatus` → `{prefix}bookly_custom_statuses`

**Database tables**

| Table | Columns (summary) |
|-------|---------------------|
| `{prefix}bookly_custom_statuses` | `slug`, `name`, `busy`, `color`, `position`, … |

**Relevant hooks / proxies**

- `Local::prepareAllStatuses()` — merges custom slugs into status lists
- `Local::prepareBusyStatuses()` — statuses with `busy = 1`
- `Local::prepareFreeStatuses()` — statuses with `busy = 0`
- `Shared::prepareColorsStatuses()` — adds custom status colors to calendar

**CAD should reference?** **Yes** — if Custom Statuses is installed, CAD CSS classes (`cad-appointment--{status}`) and filtering must include custom slugs.

---

### 3. Bookly Events (`bookly-addon-events-1.6`)

**Purpose:** Ticketed events separate from standard appointments; appears on staff calendar via calendar proxy.

**Relevant classes**

| Class | Role |
|-------|------|
| `BooklyEvents\Lib\Entities\Event` | Event master record |
| `BooklyEvents\Lib\Entities\EventStaff` | Staff assigned to event |
| `BooklyEvents\Lib\Entities\EventTicketType` | Ticket tiers |
| `BooklyEvents\Lib\Entities\EventAttendee` | Sold tickets / attendees |
| `BooklyEvents\Backend\Modules\Calendar\ProxyProviders\Local` | Injects events into admin calendar |

**Relevant AJAX handlers**

| Class | Methods |
|-------|---------|
| `Backend\Modules\Events\Ajax` | `getEvents`, `getEventSummary`, `deleteEvents` |
| `Backend\Components\Dialogs\Event\Ajax` | `getEvent`, `saveEvent` |
| `Backend\Components\Dialogs\TicketType\Ajax` | `getTicketTypeList`, `saveTicketTypeList` |
| `Backend\Components\Dialogs\Attendees\Ajax` | `getAttendeesFormData`, `getAttendees`, `createAttendeeTickets`, `deleteAttendeeTickets`, `checkInAttendee` |
| `Frontend\Modules\EventsForm\Ajax` | `getEventsList`, `addEventToCalendar` |

**Entities**

- `Event`, `EventStaff`, `EventTicketType`, `EventAttendee`

**Database tables**

| Table | Purpose |
|-------|---------|
| `{prefix}bookly_events` | Event title, dates, capacity, color |
| `{prefix}bookly_event_staff` | Event ↔ staff |
| `{prefix}bookly_event_ticket_types` | Ticket types and pricing |
| `{prefix}bookly_event_attendees` | Attendee records |

**Relevant hooks / proxies**

- `Local::buildEventsForCalendar()` — merges events into calendar alongside appointments

**CAD should reference?** **Maybe** — include in CAD schedule only if Crock A Doodle uses Bookly Events on studio calendars.

---

### 4. Bookly Collaborative Services (`bookly-addon-collaborative-services-4.0`)

**Purpose:** Services requiring multiple staff simultaneously; linked appointments via `collaborative_token` on `customer_appointments`.

**Relevant classes**

| Class | Role |
|-------|------|
| `BooklyCollaborativeServices\Lib\ProxyProviders\Shared` | Service/staff query modifications |
| `BooklyCollaborativeServices\Backend\Components\Dialogs\Appointment\Ajax` | `getAppointments` for linked rows |

**Relevant AJAX handlers**

| Class | Methods |
|-------|---------|
| `Backend\Components\Dialogs\Appointment\Ajax` | `getAppointments` |

**Entities**

- Uses core `Service`, `Staff`, `StaffService`, `SubService`, `CustomerAppointment`

**Database tables**

- No dedicated tables (options only: `bookly_collaborative_hide_staff`)

**Relevant hooks / proxies**

- Extends appointment dialog and calendar proxy providers
- Customer Cabinet reschedule generates `collaborative_token` on related rows

**CAD should reference?** **Maybe** — if collaborative services are used, one booking may span multiple staff columns; CAD may show multiple blocks or need token grouping.

---

### 5. Bookly Compound Services (`bookly-addon-compound-services-4.3`)

**Purpose:** Multi-segment appointments (service chains with gaps) via `compound_token` on `customer_appointments`.

**Relevant classes**

| Class | Role |
|-------|------|
| `BooklyCompoundServices\Lib\ProxyProviders\Shared` | Service structure, calendar hooks |
| `BooklyCompoundServices\Backend\Components\Dialogs\Appointment\Ajax` | `getAppointments` |

**Relevant AJAX handlers**

| Class | Methods |
|-------|---------|
| `Backend\Components\Dialogs\Appointment\Ajax` | `getAppointments` |

**Entities**

- Core `Service`, `SubService`, `Staff`, `StaffService`

**Database tables**

- None (options only)

**Relevant hooks / proxies**

- Calendar / appointments proxy providers
- `compound_token` links segments (see Customer Cabinet reschedule)

**CAD should reference?** **Maybe** — compound bookings may appear as multiple appointment rows that share a token.

---

### 6. Bookly Customer Groups (`bookly-addon-customer-groups-4.3`)

**Purpose:** Customer segmentation; can affect default appointment status per group.

**Relevant classes**

| Class | Role |
|-------|------|
| `BooklyCustomerGroups\Lib\Entities\CustomerGroups` | Group entity |
| `BooklyCustomerGroups\Lib\Entities\CustomerGroupsServices` | Group ↔ service rules |
| `BooklyCustomerGroups\Lib\ProxyProviders\Local` | Default status per group |

**Relevant AJAX handlers**

| Class | Methods |
|-------|---------|
| `Backend\Modules\CustomerGroups\Ajax` | `getGroups`, `deleteGroups` |
| `Backend\Components\Dialogs\CustomerGroup\Ajax` | `saveGroup` |

**Entities**

- `CustomerGroups`, `CustomerGroupsServices`

**Database tables**

| Table | Purpose |
|-------|---------|
| `{prefix}bookly_customer_groups` | Group definitions |
| `{prefix}bookly_customer_groups_services` | Group-service visibility/pricing |

**Relevant hooks / proxies**

- `Local::takeDefaultAppointmentStatus()` — group-based default status
- `Local::prepareDefaultAppointmentStatuses()`

**CAD should reference?** **No** for v2 read-only grid (unless displaying group-specific status defaults).

---

### 7. Bookly Custom Fields (`bookly-addon-custom-fields-5.3`)

**Purpose:** Extra fields on booking form; stored in options JSON, not normalized appointment columns.

**Relevant classes**

| Class | Role |
|-------|------|
| `BooklyCustomFields\Lib\ProxyProviders\Local` / `Shared` | Booking + calendar code data |
| `BooklyCustomFields\Backend\Modules\Calendar\ProxyProviders\Shared` | Appointment tooltip/code data |

**Relevant AJAX handlers**

| Class | Methods |
|-------|---------|
| `Backend\Modules\CustomFields\Ajax` | `saveCustomFields`, `loadTab`, `saveConditions` |
| `Frontend\Modules\Booking\Ajax` | `captcha`, `captchaRefresh` |

**Entities**

- None (data in `bookly_custom_fields_data` option)

**Database tables**

- None

**Relevant hooks / proxies**

- `Shared::prepareAppointmentCodesData()` — enriches calendar appointment metadata

**CAD should reference?** **Maybe** — only if CAD appointment blocks should show custom field values.

---

### 8. Bookly Customer Information (`bookly-addon-customer-information-4.1`)

**Purpose:** Extra profile fields on customer record (address components, etc.).

**Relevant classes**

- Proxy providers on Booking, Calendar, Customers, Modern Booking Form

**Relevant AJAX handlers**

| Class | Methods |
|-------|---------|
| `Backend\Modules\CustomerInformation\Ajax` | `saveFields` |

**Entities**

- Uses core `Customer`

**Database tables**

- Field definitions in options (no add-on entity tables in reference)

**CAD should reference?** **No** for v2 grid (customer name already from core `bookly_customers`).

---

### 9. Bookly Customer Cabinet (`bookly-addon-customer-cabinet-6.9`)

**Purpose:** Frontend customer account — view/reschedule/cancel own appointments.

**Relevant classes**

| Class | Role |
|-------|------|
| `BooklyCustomerCabinet\Frontend\Modules\CustomerCabinet\Ajax` | Customer appointment list |
| Reschedule/Cancel/Delete dialog AJAX classes | Mutation flows |

**Relevant AJAX handlers**

| Class | Methods |
|-------|---------|
| `Frontend\Modules\CustomerCabinet\Ajax` | `getAppointments`, `saveProfile` |
| `Frontend\Components\Dialogs\Reschedule\Ajax` | `getDaySchedule`, `saveReschedule` |
| `Frontend\Components\Dialogs\Cancel\Ajax` | `cancelAppointment` |
| `Frontend\Components\Dialogs\Delete\Ajax` | `checkFutureAppointments`, `deleteProfile` |

**Entities**

- Core `CustomerAppointment`, `Appointment`, `Staff`, `Service`

**Database tables**

- None (uses core tables)

**Relevant hooks / proxies**

- Uses `Bookly\Lib\Proxy\CustomStatuses::prepareBusyStatuses()` for availability checks

**CAD should reference?** **Maybe** — useful reference for **appointment reschedule/save** patterns when CAD implements editing (Phase 2).

---

### 10. Bookly Chain Appointments (`bookly-addon-chain-appointments-2.5`)

**Purpose:** Customer books multiple services/providers in one session at step 1.

**Relevant classes**

- `Frontend\Modules\Booking\ProxyProviders\Shared`
- Appearance proxy providers

**Relevant AJAX handlers**

- None dedicated (extends booking flow)

**Entities / tables**

- None

**CAD should reference?** **No** — booking UX only; resulting rows are normal core appointments.

---

### 11. Bookly Cart (`bookly-addon-cart-3.4`)

**Purpose:** Multi-appointment cart before checkout.

**Relevant classes**

- `Frontend\Modules\Booking\ProxyProviders\Local`

**Relevant AJAX handlers**

| Class | Methods |
|-------|---------|
| `Frontend\Modules\Booking\Ajax` | `dropItem` |

**Options**

- `bookly_cart_enabled`, cart step l10n strings

**CAD should reference?** **No**

---

### 12. Bookly Coupons (`bookly-addon-coupons-5.4`)

**Purpose:** Discount coupon codes at checkout.

**Relevant classes**

| Class | Role |
|-------|------|
| `BooklyCoupons\Lib\Entities\Coupon` | Coupon master |
| `CouponService`, `CouponStaff`, `CouponCustomer` | Restrictions |

**Relevant AJAX handlers**

| Class | Methods |
|-------|---------|
| `Backend\Modules\Coupons\Ajax` | `getCoupons`, `saveCoupon`, `getCouponLists`, `generateCode`, `deleteCoupons`, `export` |
| `Frontend\Modules\Booking\Ajax` | `applyCoupon` |
| `Frontend\Modules\ModernBookingForm\Ajax` | `modernBookingFormVerifyCoupon` |

**Database tables**

| Table |
|-------|
| `{prefix}bookly_coupons` |
| `{prefix}bookly_coupon_services` |
| `{prefix}bookly_coupon_staff` |
| `{prefix}bookly_coupon_customers` |

**CAD should reference?** **No**

---

### 13. Bookly Discounts (`bookly-addon-discounts-2.2`)

**Purpose:** Rule-based automatic discounts at checkout.

**Relevant classes**

- `BooklyDiscounts\Lib\Entities\Discount`, `ServiceDiscount`

**Relevant AJAX handlers**

| Class | Methods |
|-------|---------|
| `Backend\Modules\Discounts\Ajax` | `getDiscounts`, `deleteDiscounts` |

**Database tables**

| Table |
|-------|
| `{prefix}bookly_discounts` |
| `{prefix}bookly_service_discounts` |

**CAD should reference?** **No**

---

### 14. Bookly Deposit Payments (`bookly-addon-deposit-payments-3.9`)

**Purpose:** Partial payment at booking time.

**Relevant classes**

- Booking / Modern Booking Form proxy providers
- Settings, notifications integration

**Relevant AJAX handlers**

| Class | Methods |
|-------|---------|
| `Frontend\Modules\Booking\Ajax` | `applyPaymentMethod` |

**Database tables**

- None

**CAD should reference?** **No**

---

### 15. Bookly Advanced Google Calendar (`bookly-addon-advanced-google-calendar-3.1`)

**Purpose:** Two-way Google Calendar sync for staff.

**Relevant classes**

| Class | Role |
|-------|------|
| `BooklyAdvancedGoogleCalendar\Lib\Google\Calendar` | Sync logic (uses core `Appointment`) |
| `Backend\Modules\Calendar\ProxyProviders\Local` | Admin calendar sync UI |

**Relevant AJAX handlers**

| Class | Methods |
|-------|---------|
| `Backend\Modules\Calendar\Ajax` | `sync` |
| `Frontend\Modules\Google\Ajax` | `pushNotifications` |

**Database tables**

- None (staff `google_data` on core `bookly_staff`)

**CAD should reference?** **No** for read-only CAD grid (Bookly remains sync engine).

---

### 16. Bookly Authorize.Net (`bookly-addon-authorize-net-3.5`)

**Purpose:** Authorize.Net AIM payment gateway.

**Relevant classes**

- Payment and Settings proxy providers
- Uses core `Payment` entity

**Relevant AJAX handlers**

- None dedicated in reference (payment flow via core/pro booking)

**Database tables**

- None

**CAD should reference?** **No**

---

## CAD Scheduler quick reference matrix

| Add-on | Schedule display | Staff/tables | Appointment read | Appointment write | CAD v2 |
|--------|------------------|--------------|------------------|-------------------|--------|
| **Core Bookly** | — | Yes | Yes | Yes (extract ZIP) | **Required** |
| **Pro** | Yes | Yes | Yes | Partial | **Required** |
| **Custom Statuses** | Yes (colors/slugs) | — | Yes | — | **Yes if installed** |
| **Events** | Yes | Yes | Yes | Yes | Maybe |
| **Collaborative Services** | Yes (multi-staff) | Yes | Yes | — | Maybe |
| **Compound Services** | Yes (multi-segment) | — | Yes | — | Maybe |
| **Customer Cabinet** | — | — | Yes | Yes (reschedule) | Phase 2 reference |
| **Custom Fields** | Metadata | — | Maybe | — | Maybe |
| **Customer Groups** | — | — | — | — | No |
| **Customer Information** | — | — | — | — | No |
| **Chain / Cart / Coupons / Discounts / Deposit / Authorize.Net / Google Cal** | — | — | — | — | No |

---

## Recommended inspection order for CAD developers

When changing `includes/class-cad-bookly-repository.php` or mapper:

1. **Pro** `frontend/modules/calendar/Ajax.php` — end time, dedupe, staff filter, status
2. **Core** `Backend/Modules/Calendar/Ajax.php` (extract ZIP) — `getAppointmentsQueryForCalendar()`
3. **Custom Statuses** — if studio uses custom slugs
4. **Collaborative / Compound** — if multi-staff or multi-segment bookings appear wrong on grid
5. **Events** — if non-appointment blocks should appear on calendar

When implementing CAD appointment **save/update**:

1. **Core** `Backend\Components\Dialogs\Appointment\Edit\Ajax` (extract ZIP) — see [Appointment save path](#appointment-save-path-admin-edit)
2. **Customer Cabinet** `saveReschedule` flow (frontend reschedule; creates/cancels CA rows)
3. **Pro** `Lib\ProxyProviders\Shared` + `Common::syncWithCalendars`

---

## Appointment save path (admin edit)

Verified against Bookly core **27.2** (`codecanyon-…-bookly-booking-plugin-….zip` → inner `bookly-responsive-appointment-booking-tool.27.2.zip`).

### AJAX endpoint

| Item | Value |
|------|--------|
| **WP action** | `bookly_save_appointment_form` (Bookly `Lib\Base\Ajax` naming) |
| **Class** | `Bookly\Backend\Components\Dialogs\Appointment\Edit\Ajax` |
| **Method** | `saveAppointmentForm()` |
| **Permissions** | `staff`, `supervisor` |

Related:

| Action | Method | Role |
|--------|--------|------|
| `bookly_check_appointment_errors` | `checkAppointmentErrors()` | Pre-save validation / conflicts |
| `bookly_get_data_for_appointment` | `getDataForAppointment()` | Load form for edit |
| `bookly_get_day_schedule` | `getDaySchedule()` | Reschedule day slots |

### Controller flow

`saveAppointmentForm()` is a thin wrapper: it reads POST params and delegates to:

```php
Bookly\Lib\Utils\Appointment::save(
  $appointment_id, $staff_id, $service_id, $custom_service_name, $custom_service_price,
  $location_id, $skip_date, $start_date, $end_date, $repeat, $schedule, $reschedule_type,
  $customers, $notification, $internal_note, $created_from
);
```

Conflict / schedule checks use:

```php
Bookly\Lib\Utils\Appointment::checkTime(
  $appointment_id, $start_date, $end_date, $staff_id, $service_id, $location_id, $customers
);
```

`checkTime` returns flags including `date_interval_not_available` (staff/time overlap — **hard conflict**).

### What `Utils\Appointment::save()` does (single appointment edit)

1. Validates start/end, staff, service (or custom service name).
2. Capacity check via `StaffService`.
3. Loads/creates `Entities\Appointment`, sets staff / service / start / end / extras duration / note.
4. `$appointment->save()` then `$appointment->saveCustomerAppointments( $customers )`.
5. **Integrations:** `Proxy\Shared::syncOnlineMeeting()` + `Utils\Common::syncWithCalendars()` → Google (`Proxy\Pro::syncGoogleCalendarEvent`) and Outlook.
6. **Notifications:** when `$notification` is truthy, `Notifications\Booking\Sender::sendForCA( … )` (plus waiting-list proxies); may return a notification **queue** token for deferred send UI.
7. Clears sent reminders when start time changes (`_deleteSentReminders`).

### CAD reuse strategy (Sprint 3 P1)

CAD **`cad_update_appointment`** must call `Bookly\Lib\Utils\Appointment::checkTime` + `::save` (not raw `$wpdb` updates) so notifications and calendar sync keep working. Rebuild the `$customers` array from existing `CustomerAppointment` rows; change only staff + start/end (preserve duration from stored `end_date - start_date`).

Customer Cabinet `saveReschedule` is a **different** path (cancel + recreate CA) — useful reference for conflict SQL, not the primary CAD admin save target.

---

## CAD-owned integration points

These are defined in CAD Scheduler, not Bookly:

| Hook / API | File | Purpose |
|------------|------|---------|
| `cad_scheduler_tables` | `includes/class-cad-schedule-provider.php` | Filter mapped staff/tables |
| `cad_scheduler_asset_url` | `snippets/10A-ajax-bridge.php` | Override jsDelivr CDN URL |
| `wp_ajax_cad_get_schedule` | `snippets/10A-ajax-bridge.php` | CAD schedule read endpoint (logged-in users only; filter `cad_scheduler_get_schedule_capability`, default `read`) |
| `cad_scheduler_get_schedule_capability` | `snippets/10A-ajax-bridge.php` | Filter required WP capability for schedule AJAX |
| `cad_scheduler_health` | `snippets/10A-ajax-bridge.php` | Filter health-check issues (diagnostics) |
| `cad_scheduler_diagnostics_enabled` | `snippets/10A-ajax-bridge.php` | Show diagnostic panel when install is healthy |
| `cad_scheduler_validation_mode_enabled` | `snippets/10A-ajax-bridge.php` | Show appointment ID overlay on blocks (default `false`); see [Sprint 1.7](sprint-1.7-live-validation.md) |
| `wp_ajax_cad_update_appointment` | `snippets/10A-ajax-bridge.php` | Reschedule via Bookly `Utils\Appointment::save` (Sprint 3 P1) |
| `cad_scheduler_reschedule_notify` | `includes/class-cad-schedule-provider.php` | Whether drag/reschedule sends Bookly notifications (default `true`) |

---

## Document maintenance

- **Source path:** `/bookly-reference` (read-only; extracted source is local/gitignored)
- **When to update:** After adding extracted add-on ZIPs or upgrading Bookly versions
- **Version note:** Map generated from extracted add-on versions listed above; core Bookly **27.2** present as ZIP only
