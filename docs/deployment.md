# Deployment — CAD Scheduler v2

## Code Snippets (4 snippets)

| Priority | Name | Source |
|----------|------|--------|
| 10 | CAD — Bookly Repository | `includes/class-cad-bookly-repository.php` |
| 11 | CAD — Bookly Mapper | `includes/class-cad-bookly-mapper.php` |
| 12 | CAD — Schedule Provider | `includes/class-cad-schedule-provider.php` |
| 20 | CAD — AJAX Bridge | `snippets/10A-ajax-bridge.php` |

Paste each file **in full** into Code Snippets. Run everywhere (or front-end only).

## Access control

`cad_get_schedule` requires a logged-in WordPress user with the `read` capability (default). Anonymous requests are rejected.

- Place `[cad_scheduler]` on a **login-protected** page for studio staff.
- To require a stricter capability, use:

```php
add_filter( 'cad_scheduler_get_schedule_capability', function () {
    return 'edit_posts'; // example: editors and administrators only
} );
```

The endpoint returns customer names and appointment details from Bookly — treat it as internal staff data, not public.

## Diagnostics

CAD Scheduler shows a diagnostic panel when required components are missing. It is **automatic** when blocking issues are detected; optional detail mode is available for healthy installs.

**Architectural note:** This intentionally differs from Bookly. Bookly surfaces missing dependencies as **wp-admin notices**; CAD runs on the **frontend**, so it uses **user-friendly diagnostic panels** on the schedule page instead. See `docs/bookly-reference-map.md` — Architectural Decisions.

**Blocking (scheduler does not load):**

- Missing Repository, Mapper, or Provider snippets (priorities 10–12)
- Missing AJAX bridge snippet (priority 20) — shortcode will not register at all

**Non-blocking (scheduler loads with warnings):**

- Bookly database tables not found

**Optional health check on a working install:**

```php
add_filter( 'cad_scheduler_diagnostics_enabled', '__return_true' );
```

Shows a confirmation panel below the scheduler when all components are healthy.

## Validation Mode

Optional overlay that displays Bookly appointment IDs (`#123`) on each CAD block for side-by-side parity testing. **Off by default.**

```php
add_filter( 'cad_scheduler_validation_mode_enabled', '__return_true' );
```

Disable when validation is complete:

```php
remove_filter( 'cad_scheduler_validation_mode_enabled', '__return_true' );
// or
add_filter( 'cad_scheduler_validation_mode_enabled', '__return_false' );
```

See [Sprint 1.7 — Live Validation](sprint-1.7-live-validation.md) and the [Validation Checklist](validation-checklist.md).

**Filters:**

| Filter | Purpose |
|--------|---------|
| `cad_scheduler_health` | Add or modify health issues |
| `cad_scheduler_diagnostics_enabled` | Force diagnostic panel when healthy |
| `cad_scheduler_validation_mode_enabled` | Show appointment ID on each block (default `false`) |
| `cad_scheduler_day_start` | Default open time `HH:MM` (default `08:00`) |
| `cad_scheduler_day_end` | Default close time `HH:MM` (default `20:00`) |
| `cad_scheduler_open_hours` | Weekly map `0–6 → { start, end }` or `null` (closed) |

## Page

```
[cad_scheduler]
```

## Demo checklist (Rule #1)

- [ ] Table names appear **across the top**
- [ ] Time labels appear **down the left** (15-minute grid)
- [ ] Appointments sit at the correct time and span their duration
- [ ] Previous / Today / Next and date picker change the day **without** a page reload
- [ ] Calendar body scrolls vertically; table headers stay sticky
- [ ] Horizontal scroll keeps time labels visible; many tables do not compress below min column width
- [ ] Layout scrolls on mobile
- [ ] Data comes from Bookly via `cad_get_schedule`

## Assets

JS/CSS load from jsDelivr using the Git tag in `CAD_SCHEDULER_VERSION` (currently `2.4.0` after a successful release).

### Release order

Never bump `CAD_SCHEDULER_VERSION` (or paste a bridge that already points at a new tag) until jsDelivr is serving that tag. Doing so breaks the scheduler: the CDN returns 404s for assets that do not exist yet.

```
Local commit
    ↓
Local QA
    ↓
Push commit
    ↓
Create tag
    ↓
Push tag
    ↓
Verify jsDelivr serves the tag
    ↓
Update CAD_SCHEDULER_VERSION
    ↓
Final smoke test
```

**Verify a tag on jsDelivr** (replace `2.4.0` / path as needed):

```
https://cdn.jsdelivr.net/gh/CAD-Support/cad-scheduler@2.4.0/src/cad-core.js
https://cdn.jsdelivr.net/gh/CAD-Support/cad-scheduler@2.4.0/assets/css/cad-scheduler.css
```

Expect HTTP 200 and file contents that match the tagged commit. If the CDN is still warming, wait and retry before changing `CAD_SCHEDULER_VERSION` in WordPress.

## Local asset override

```php
add_filter( 'cad_scheduler_asset_url', function ( $url, $path ) {
    return 'https://your-cdn.example/' . $path;
}, 10, 2 );
```
