# Sprint 1.7 — Live Studio Validation

**Objective:** Build the Live Validation framework for CAD Scheduler so Bentonville (and future studios) can compare CAD against Bookly with documented procedures, pass/fail criteria, and optional on-screen appointment IDs.

**Scope:** Documentation and optional Validation Mode overlay only. No Bookly modifications. No functional scheduler changes.

**Related docs:** [Validation Checklist](validation-checklist.md) · [Bentonville Report Template](bentonville-validation-report-template.md) · [Sprint 1.6 Parity](sprint-1.6-bentonville-parity.md) · [Deployment](deployment.md) · [Bookly Reference Map](bookly-reference-map.md)

---

## Deliverables

| Deliverable | Location |
|-------------|----------|
| Validation checklist (reusable) | [validation-checklist.md](validation-checklist.md) |
| Bentonville report template | [bentonville-validation-report-template.md](bentonville-validation-report-template.md) |
| Validation Mode (optional feature) | Filter `cad_scheduler_validation_mode_enabled` |
| Pass / fail criteria | This doc + checklist |
| Testing procedure | This doc — [Testing procedure](#testing-procedure) |

---

## Validation Mode

Validation Mode is an **optional, read-only UI overlay** that displays the Bookly appointment ID on each CAD block. It exists only to make side-by-side comparison with the Bookly admin calendar faster — no data or behavior changes.

### How it works

```
cad_scheduler_validation_mode_enabled (filter, default false)
    ↓
cadConfig.validationMode → CAD.init()
    ↓
cad-components.js → appointmentBlock() appends <span class="cad-appointment__id">#id</span>
    ↓
cad-scheduler.css → badge styling on .cad-appointment--validation
```

Appointment IDs were already present on blocks as `data-id`; Validation Mode makes them **visible** without opening devtools.

### Enable (Bentonville or any studio)

Add to a Code Snippet or theme `functions.php`:

```php
add_filter( 'cad_scheduler_validation_mode_enabled', '__return_true' );
```

Reload the page with `[cad_scheduler]`. Each block shows `#<appointment_id>` in the top-right corner.

### Disable

Remove the filter or return `false`:

```php
add_filter( 'cad_scheduler_validation_mode_enabled', '__return_false' );
```

Default is **off** — production staff never see IDs unless explicitly enabled.

### What Validation Mode does not do

- Does not change SQL, mapper, or API responses
- Does not enable editing or Bookly writes
- Does not log to console (use `cadConfig.debug` separately if needed)
- Does not affect appointment layout logic beyond making room for the ID badge

---

## Pass / fail criteria

### PASS

All of the following for **standard single-customer bookings**:

- Same appointment **count** as Bookly for the test date
- Same appointment **IDs** (visible in Validation Mode or via API)
- Same **staff column** (`tableId` matches Bookly staff)
- **Start** and **end** times within **1 minute** (extras included in end time)
- Same **customer name**, **service name**, and **status**
- Empty days render with zero blocks
- Install health: no blocking diagnostic issues

Edge-case failures (group bookings, custom services, colour differences) may be **PASS WITH KNOWN EXCEPTIONS** if documented in the report and accepted by studio lead.

### FAIL

Any of:

- Missing or extra appointment on CAD vs Bookly (count mismatch)
- Wrong appointment ID on a block
- Wrong table, time (>1 min), customer, service, or status on a standard booking
- CAD scheduler does not load (blocking diagnostics)
- API `cad_get_schedule` returns different data than the CAD UI for the same date

### Document on FAIL

For each failure record:

1. Appointment ID
2. Field that failed (ID, time, customer, etc.)
3. Bookly value vs CAD value
4. Screenshot (Bookly + CAD side by side)
5. Severity: Blocker / Major / Minor

Use the [failure log table](bentonville-validation-report-template.md#failure-log) in the Bentonville report template.

---

## Testing procedure

Follow these steps in order. The [Validation Checklist](validation-checklist.md) is the printable working copy.

### Step 1 — Prerequisites

1. Confirm Code Snippets 10, 11, 12, 20 are active ([Deployment](deployment.md)).
2. Confirm `[cad_scheduler]` is on a login-protected staff page.
3. Enable Validation Mode (filter above).
4. Open Bookly wp-admin calendar for the test date in a second window.

### Step 2 — Select test dates

Pick three dates:

| Date type | Purpose |
|-----------|---------|
| **Busy day** | Full grid, multiple staff, stacked appointments |
| **Quiet / empty day** | Zero appointments, empty grid |
| **Extras day** | At least one appointment with service extras affecting duration |

### Step 3 — Side-by-side comparison

For each date:

1. Set Bookly calendar to the date.
2. Load the same date on CAD (date picker or default today).
3. For **each** Bookly appointment, find `#<id>` on the matching CAD block.
4. Verify table, times, customer, service, status (checklist columns).
5. Note count match at day level.

### Step 4 — Edge cases (if present at studio)

Explicitly look for:

- Group / multi-customer bookings
- Custom / ad-hoc services (no linked service record)
- Cancelled or rejected appointments
- Private staff columns
- Archived staff (should **not** appear in CAD headers)

See [Sprint 1.6 — Parity findings](sprint-1.6-bentonville-parity.md#parity-findings) for known partial gaps.

### Step 5 — API spot-check

1. DevTools → Console: `window.cadConfig.nonce`
2. POST to `admin-ajax.php`:

```http
action=cad_get_schedule
nonce=<from cadConfig>
date=YYYY-MM-DD
```

3. Compare `appointments[]` length and fields to Bookly and to the CAD UI.

### Step 6 — Layout smoke test

Run the [demo checklist](deployment.md#demo-checklist-rule-1): table headers, 15-minute grid, block placement, mobile scroll.

### Step 7 — Report and cleanup

1. Complete [bentonville-validation-report-template.md](bentonville-validation-report-template.md).
2. Attach screenshots to team storage.
3. **Disable Validation Mode** before handoff to daily studio use.
4. Sign off in the report template.

---

## Files changed (Sprint 1.7)

| File | Change |
|------|--------|
| `snippets/10A-ajax-bridge.php` | `cad_scheduler_validation_mode_enabled()` filter; `validationMode` in `cadConfig` |
| `src/cad-components.js` | Optional `#id` badge when Validation Mode on |
| `assets/css/cad-scheduler.css` | `.cad-appointment__id`, `.cad-appointment--validation` styles |
| `docs/sprint-1.7-live-validation.md` | This document |
| `docs/validation-checklist.md` | Reusable checklist |
| `docs/bentonville-validation-report-template.md` | Bentonville report template |
| `docs/deployment.md` | Validation Mode section |
| `docs/bookly-reference-map.md` | Filter registry entry |
| `docs/changelog.md` | Sprint 1.7 note |

**No changes to:** Bookly, `includes/` repository/mapper/provider logic, appointment editing, or read-path SQL.

---

## Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Validation Mode left enabled in production | Staff see internal IDs on blocks | Document disable step; default filter is `false` |
| Group bookings at Bentonville | Wrong customer name — false FAIL | Mark as known exception; see Sprint 1.6 |
| Custom services | Blank service label — false FAIL | Mark as known exception; verify in edge-case section |
| WP timezone ≠ staff browser | Time labels differ by offset | Compare against Bookly admin TZ; document in report |
| jsDelivr `@2.0.0` tag | Stale JS if not tagged after push | Tag GitHub release after deploy; see [Deployment — Assets](deployment.md#assets) |
| Side-by-side manual process | Human error, missed appointments | Use checklist + ID overlay + API spot-check |
| Bookly schema change on upgrade | Silent data drift | Re-run validation after Bookly updates |

---

## Recommendation

**Proceed with Bentonville live validation** using this framework:

1. Deploy updated snippet 20 (AJAX bridge) and ensure jsDelivr assets are tagged `2.0.0`.
2. Enable Validation Mode only for the test window.
3. Work through [validation-checklist.md](validation-checklist.md).
4. File [bentonville-validation-report-template.md](bentonville-validation-report-template.md).
5. Use report outcomes to prioritize Sprint 2 fixes (multi-customer, custom services) only if Bentonville confirms real impact.

---

## Sign-off (Sprint 1.7 framework)

| Check | Date | Owner | Status |
|-------|------|-------|--------|
| Validation Mode implemented | | Engineering | |
| Checklist published | | Engineering | |
| Bentonville report template published | | Engineering | |
| Bentonville live test completed | | Studio | |
