# CAD Scheduler

Modern Bookly frontend for multi-table studio scheduling.

## Design Principles

- Modular first
- No plugin modifications
- Code Snippets compatible
- Bookly remains the scheduling engine
- Modern JavaScript (ES6+)
- Fast and responsive
- Progressive enhancement
- Fully documented

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
| `cad-core.js` | Core state, config, and API bridge |
| `cad-ui.js` | UI orchestration and layout |
| `cad-components.js` | Reusable UI components |
| `cad-editor.js` | Schedule editor interactions |
| `cad-calendar.js` | Multi-table calendar view |

## Documentation

- [Architecture](docs/architecture.md)
- [Roadmap](docs/roadmap.md)
- [Changelog](docs/changelog.md)

## Snippets

WordPress snippets live in `snippets/`. Start with `10A-ajax-bridge.php` for the Bookly AJAX bridge.
