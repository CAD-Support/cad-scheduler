# Bookly Reference

Read-only Bookly source used for CAD Scheduler development and parity analysis.

The repository intentionally excludes extracted Bookly source code.

To enable parity analysis:

1. Open `bookly-reference/Archive Zip files/`
2. Extract the required packages into:

- `core-extract/`
- `bookly-addon-*/`

These folders are ignored by Git and exist only for local development.

## Layout

```
bookly-reference/
├── Archive Zip files/     ← In git: original vendor ZIP packages
├── core-extract/            ← Local only: core plugin files (calendar, entities, utils)
├── bookly-addon-pro-10.2/   ← Local only: add-on source as needed
├── bookly-addon-events-1.6/
└── README.md
```

| Path | In git? | Purpose |
|------|---------|---------|
| `Archive Zip files/` | Yes | Preserve original CodeCanyon packages |
| `core-extract/` | No | Core Bookly source for parity analysis |
| `bookly-addon-*/` | No | Add-on source for deeper inspection |

Typical `core-extract/` contents:

```
core-extract/bookly-responsive-appointment-booking-tool/
├── backend/modules/calendar/
├── lib/entities/Appointment.php
└── lib/utils/DateTime.php
```

## Rules

- **Never modify** files under `bookly-reference/` — read-only reference.
- **Never execute** code from Archive ZIPs or extracts in production; CAD Scheduler runs from `includes/`, `snippets/`, and `src/`.
- CAD Scheduler development happens entirely outside `bookly-reference/`.

## Documentation

- Archive packages: [`Archive Zip files/README.md`](Archive%20Zip%20files/README.md)
- Full integration map: [`docs/bookly-reference-map.md`](../docs/bookly-reference-map.md)
