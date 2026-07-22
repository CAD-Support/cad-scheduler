# Deployment

CAD Scheduler is designed for a **GitHub-first, Code Snippets** workflow. No theme or filesystem deployment is required until the plugin migration.

## Source of truth

| Asset | Location | Deploy method |
|-------|----------|---------------|
| JavaScript | `src/` | jsDelivr CDN (automatic via snippet 10A) |
| CSS | `assets/css/` | jsDelivr CDN (automatic via snippet 10A) |
| PHP modules | `includes/` | Copy into Code Snippets |
| Bootstrap | `snippets/10A-ajax-bridge.php` | Copy into Code Snippets |

Push to GitHub. Tag releases with semver matching `CAD_SCHEDULER_VERSION` in snippet 10A.

## Code Snippets setup

Create **four snippets** in this order (priority matters):

| Priority | Snippet name | Source file |
|----------|--------------|-------------|
| 10 | CAD — Bookly Repository | `includes/class-cad-bookly-repository.php` |
| 11 | CAD — Bookly Mapper | `includes/class-cad-bookly-mapper.php` |
| 12 | CAD — Schedule Provider | `includes/class-cad-schedule-provider.php` |
| 20 | CAD — AJAX Bridge | `snippets/10A-ajax-bridge.php` |

For each snippet:

1. Open the source file in GitHub.
2. Copy the **entire file contents** (including the opening `<?php` tag).
3. Paste into a new Code Snippets entry.
4. Set **Run snippet everywhere** (or limit to site front end).
5. Activate.

The `includes/` files are written to be pasted verbatim. When the plugin ships, the same files move into `includes/` unchanged and are loaded with `require_once` instead of Code Snippets.

## Page setup

Add a WordPress page containing:

```
[cad_scheduler]
```

## Asset delivery

Snippet 10A loads JS/CSS from jsDelivr:

```
https://cdn.jsdelivr.net/gh/CAD-Support/cad-scheduler@{version}/src/cad-core.js
```

Bump `CAD_SCHEDULER_VERSION` in snippet 10A when you tag a GitHub release so browsers fetch the new assets.

### Local development override

Point assets at a local copy without changing GitHub:

```php
add_filter( 'cad_scheduler_asset_url', function ( $url, $path ) {
    return get_stylesheet_directory_uri() . '/cad-scheduler/' . $path;
}, 10, 2 );
```

Add this to a separate dev-only snippet or your theme's `functions.php`.

## Release checklist

1. Merge changes to `main` on GitHub.
2. Tag the release (e.g. `0.2.1`).
3. Update `CAD_SCHEDULER_VERSION` in snippet 10A if JS/CSS changed.
4. Re-paste snippet 10A only if the bootstrap changed.
5. Re-paste `includes/` snippets only if PHP modules changed.
6. Hard-refresh the scheduler page (bypass CDN cache if needed).

## Plugin migration (future)

When ready to ship as a plugin:

1. Move `includes/` into the plugin directory unchanged.
2. Replace Code Snippets with a plugin bootstrap:

```php
define( 'CAD_SCHEDULER_DIR', plugin_dir_path( __FILE__ ) );
define( 'CAD_SCHEDULER_URL', plugin_dir_url( __FILE__ ) );

require_once CAD_SCHEDULER_DIR . 'includes/class-cad-bookly-repository.php';
require_once CAD_SCHEDULER_DIR . 'includes/class-cad-bookly-mapper.php';
require_once CAD_SCHEDULER_DIR . 'includes/class-cad-schedule-provider.php';
// Enqueue, AJAX routes, and shortcode from 10A move here.
```

3. Switch asset URLs from jsDelivr to `CAD_SCHEDULER_URL` (or keep the filter).
4. Deactivate the four Code Snippets.

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| Blank scheduler area | PHP modules not loaded | Verify snippets 10–12 are active and ordered |
| 500 on `cad_get_schedule` | Missing `CAD_Schedule_Provider` | Activate repository, mapper, provider snippets |
| Old JavaScript behavior | CDN cache / version mismatch | Bump `CAD_SCHEDULER_VERSION`, tag GitHub release |
| No columns | No Bookly staff | Add staff in Bookly |
| No appointments | None scheduled for today | Bootstrap loads today's date only (Sprint 2) |
