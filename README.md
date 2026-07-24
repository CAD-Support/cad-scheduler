# CAD Scheduler v2

Modern Bookly frontend for multi-table studio scheduling.

**Layout:** tables across the top · time down the left · 15-minute grid · duration-sized appointment blocks.

## Design Principles

- Modular first
- No plugin modifications
- Code Snippets compatible
- Bookly remains the scheduling engine
- Modern JavaScript (ES6+)
- Fast and responsive
- Progressive enhancement
- Fully documented

## Rule #1

If it can't be demonstrated, it isn't finished.

## Structure

```
docs/           Project documentation
src/            Frontend modules
snippets/       WordPress / Bookly integration snippets
assets/         Static assets (css, images)
```

## Modules

| File | Purpose |
|------|---------|
| `cad-core.js` | Config, state, init |
| `cad-api.js` | WordPress AJAX (`CAD.API`) |
| `cad-ui.js` | Shell + load orchestration |
| `cad-navigation.js` | Previous / Today / Next / date picker |
| `cad-calendar.js` | v2 matrix grid |
| `cad-components.js` | Appointment blocks |
| `cad-editor.js` | Selection |

## Documentation

- [Architecture](docs/architecture.md)
- [Roadmap](docs/roadmap.md)
- [Changelog](docs/changelog.md)
- [Sprint 2.1 — Date Navigation](docs/sprint-2.1-date-navigation.md)

## Snippets

See [Deployment](docs/deployment.md). Use `[cad_scheduler]` on a page. Assets via jsDelivr `@2.1.0`.
