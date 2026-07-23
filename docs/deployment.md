# Deployment — CAD Scheduler v2

## Code Snippets (4 snippets)

| Priority | Name | Source |
|----------|------|--------|
| 10 | CAD — Bookly Repository | `includes/class-cad-bookly-repository.php` |
| 11 | CAD — Bookly Mapper | `includes/class-cad-bookly-mapper.php` |
| 12 | CAD — Schedule Provider | `includes/class-cad-schedule-provider.php` |
| 20 | CAD — AJAX Bridge | `snippets/10A-ajax-bridge.php` |

Paste each file **in full** into Code Snippets. Run everywhere (or front-end only).

## Page

```
[cad_scheduler]
```

## Demo checklist (Rule #1)

- [ ] Table names appear **across the top**
- [ ] Time labels appear **down the left** (15-minute grid)
- [ ] Appointments sit at the correct time and span their duration
- [ ] Layout scrolls on mobile
- [ ] Data comes from Bookly via `cad_get_schedule`

## Assets

JS/CSS load from jsDelivr tag `2.0.0`. Tag GitHub release after push.

## Local asset override

```php
add_filter( 'cad_scheduler_asset_url', function ( $url, $path ) {
    return 'https://your-cdn.example/' . $path;
}, 10, 2 );
```
