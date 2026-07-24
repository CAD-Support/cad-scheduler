# Bookly Archive

Original CodeCanyon ZIP packages preserved for historical reference. Part of [`bookly-reference`](../README.md).

These ZIP files are **read-only** and are **not part of the CAD Scheduler build**.

## Extract for parity analysis

The repository intentionally excludes extracted Bookly source code. To inspect Bookly implementation locally:

1. Open this folder (`bookly-reference/Archive Zip files/`)
2. Extract the required packages into:

- `bookly-reference/core-extract/` — core plugin (calendar, entities, utils)
- `bookly-reference/bookly-addon-*/` — add-ons as needed (e.g. Pro, Events)

These folders are ignored by Git and exist only for local development.

**Important:** CAD Scheduler never executes or modifies code from these archives. Development and comparison use the extracted folders on your machine, not the ZIPs directly.
