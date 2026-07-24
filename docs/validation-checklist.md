# CAD Scheduler — Live Validation Checklist

Reusable checklist for comparing CAD Scheduler against Bookly on any studio site. Use with [Validation Mode](sprint-1.7-live-validation.md#validation-mode) enabled so appointment IDs are visible on each block.

**Related docs:** [Sprint 1.7 — Live Validation](sprint-1.7-live-validation.md) · [Bentonville Report Template](bentonville-validation-report-template.md) · [Sprint 1.6 Parity](sprint-1.6-bentonville-parity.md) · [Deployment](deployment.md)

---

## Before you start

| Step | Done | Notes |
|------|------|-------|
| All 4 Code Snippets active (priorities 10, 11, 12, 20) | ☐ | See [Deployment](deployment.md) |
| `[cad_scheduler]` on a login-protected staff page | ☐ | |
| Validation Mode enabled (see below) | ☐ | |
| Bookly wp-admin calendar open for the same date | ☐ | |
| Tester has `read` capability (or configured capability) | ☐ | |

### Enable Validation Mode

Add to a Code Snippet or theme `functions.php`:

```php
add_filter( 'cad_scheduler_validation_mode_enabled', '__return_true' );
```

Reload the CAD page. Each appointment block shows `#<id>` in the top-right corner. Disable the filter when validation is complete.

---

## Per-appointment checks

For each appointment visible on the Bookly calendar for the test date:

| Check | Pass | Fail | Notes |
|-------|------|------|-------|
| **ID** — CAD `#id` matches Bookly appointment ID | ☐ | ☐ | |
| **Table** — same staff column | ☐ | ☐ | |
| **Start time** — within 1 minute | ☐ | ☐ | |
| **End time** — within 1 minute (includes extras) | ☐ | ☐ | |
| **Customer name** — matches Bookly | ☐ | ☐ | |
| **Service name** — matches Bookly | ☐ | ☐ | |
| **Status** — same slug (approved, pending, cancelled, etc.) | ☐ | ☐ | |

---

## Day-level checks

Run for each test date (busy day, quiet/empty day, extras day):

| Check | Pass | Fail | Notes |
|-------|------|------|-------|
| **Count** — same number of appointments as Bookly | ☐ | ☐ | |
| **Empty day** — CAD grid renders, zero blocks | ☐ | ☐ | |
| **Multiple blocks on one table** — all present, no overlap errors | ☐ | ☐ | |
| **Private staff** — column appears when Bookly shows them | ☐ | ☐ | |
| **Archived staff** — not in CAD header row | ☐ | ☐ | |
| **Extras duration** — block height matches Bookly duration | ☐ | ☐ | |

---

## Edge-case checks (if applicable at this studio)

| Check | Pass | Fail | N/A | Notes |
|-------|------|------|-----|-------|
| **Group / multi-customer booking** — customer name correct | ☐ | ☐ | ☐ | Known partial parity; see Sprint 1.6 |
| **Custom service** (no linked service record) — name not blank | ☐ | ☐ | ☐ | Known partial parity |
| **Cancelled / rejected** — visible and styled | ☐ | ☐ | ☐ | |
| **Timezone** — times match Bookly admin view | ☐ | ☐ | ☐ | |

---

## API spot-check

1. Open browser devtools on the CAD page (logged in).
2. Copy `nonce` from `window.cadConfig.nonce`.
3. Run:

```http
POST /wp-admin/admin-ajax.php
Content-Type: application/x-www-form-urlencoded

action=cad_get_schedule&nonce=<nonce>&date=YYYY-MM-DD
```

| Check | Pass | Fail | Notes |
|-------|------|------|-------|
| Response is `success: true` | ☐ | ☐ | |
| `appointments[]` count matches Bookly | ☐ | ☐ | |
| Each `id`, `tableId`, `start`, `end`, `customer`, `service`, `status` match UI | ☐ | ☐ | |

---

## Layout / UX checks (demo checklist)

| Check | Pass | Fail | Notes |
|-------|------|------|-------|
| Table names across the top | ☐ | ☐ | |
| Time labels down the left (15-minute grid) | ☐ | ☐ | |
| Blocks at correct time and duration | ☐ | ☐ | |
| Mobile scroll works | ☐ | ☐ | |

---

## Pass / fail summary

| Result | Criteria |
|--------|----------|
| **PASS** | All per-appointment and day-level checks pass for standard single-customer bookings; edge-case failures are documented and accepted |
| **FAIL** | Any mismatch in ID, count, time, table, customer, service, or status for a standard booking; or blocking install/config issue |

Document every **FAIL** with: appointment ID, field, Bookly value, CAD value, screenshot path.

---

## After validation

| Step | Done |
|------|------|
| Fill in [Bentonville Validation Report](bentonville-validation-report-template.md) (or studio-specific copy) | ☐ |
| Disable Validation Mode filter | ☐ |
| File report and attach screenshots | ☐ |
