# Bookly Reference

Read-only Bookly source used for CAD Scheduler development and parity analysis.

## Bookly Reference Repository Layout

The `bookly-reference` directory is divided into two purposes:

- **Archive Zip files/** — Original CodeCanyon packages preserved for historical reference.
- **Extracted source folders/** — The working reference used to inspect Bookly's implementation and compare CAD Scheduler behaviour.

CAD Scheduler itself is developed entirely outside `bookly-reference`.

```
bookly-reference/
├── Archive Zip files/     ← Original vendor ZIP packages
├── bookly-addon-pro-10.2/
├── bookly-addon-events-1.6/
├── ...
```

**Important:** CAD Scheduler never executes or modifies code from Archive Zip files. These files are retained only as historical vendor artifacts. Development and comparison are performed against the extracted source directories.

## Archive

Original vendor ZIP packages: [`Archive Zip files/`](Archive%20Zip%20files/README.md)

## Documentation

Full integration map: [`docs/bookly-reference-map.md`](../docs/bookly-reference-map.md)
