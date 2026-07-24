# Bentonville — CAD Scheduler Validation Report

**Studio:** Bentonville (Crock A Doodle)  
**Report date:** YYYY-MM-DD  
**Tester:**  
**CAD Scheduler version:** 2.0.0  
**Bookly version:**  
**WordPress site URL:**  

**Related docs:** [Validation Checklist](validation-checklist.md) · [Sprint 1.7 — Live Validation](sprint-1.7-live-validation.md) · [Sprint 1.6 Parity](sprint-1.6-bentonville-parity.md)

---

## Executive summary

| Field | Value |
|-------|-------|
| **Overall result** | ☐ PASS &nbsp; ☐ FAIL &nbsp; ☐ PASS WITH KNOWN EXCEPTIONS |
| **Test dates** | |
| **Appointments compared** | |
| **Failures recorded** | |
| **Recommendation** | |

---

## Environment

| Item | Value |
|------|-------|
| Code Snippets 10, 11, 12, 20 active | ☐ Yes ☐ No |
| Validation Mode enabled during test | ☐ Yes ☐ No |
| CAD page URL | |
| Bookly calendar URL | |
| WP timezone | |
| Tester browser / OS | |

---

## Test sessions

### Session 1 — Busy day

| Field | Value |
|-------|-------|
| Date | |
| Bookly appointment count | |
| CAD appointment count | |
| Result | ☐ Pass ☐ Fail |

**Notes:**

---

### Session 2 — Quiet / empty day

| Field | Value |
|-------|-------|
| Date | |
| Bookly appointment count | |
| CAD appointment count | |
| Result | ☐ Pass ☐ Fail |

**Notes:**

---

### Session 3 — Extras duration day

| Field | Value |
|-------|-------|
| Date | |
| Appointments with extras checked | |
| Result | ☐ Pass ☐ Fail |

**Notes:**

---

## Failure log

Record every mismatch. Attach screenshots to `/validation-reports/bentonville/YYYY-MM-DD/` (or your team’s storage).

| # | Appointment ID | Field | Bookly value | CAD value | Severity | Screenshot |
|---|----------------|-------|--------------|-----------|----------|------------|
| 1 | | | | | ☐ Blocker ☐ Major ☐ Minor | |
| 2 | | | | | ☐ Blocker ☐ Major ☐ Minor | |
| 3 | | | | | ☐ Blocker ☐ Major ☐ Minor | |

---

## Edge-case observations

| Scenario | Observed at Bentonville? | Result | Action needed |
|----------|--------------------------|--------|---------------|
| Group / multi-customer booking | ☐ Yes ☐ No | ☐ Pass ☐ Fail ☐ N/A | |
| Custom / ad-hoc service | ☐ Yes ☐ No | ☐ Pass ☐ Fail ☐ N/A | |
| Custom Statuses add-on | ☐ Yes ☐ No | ☐ Pass ☐ Fail ☐ N/A | |
| Timezone offset vs Bookly admin | ☐ Yes ☐ No | ☐ Pass ☐ Fail ☐ N/A | |

---

## API spot-check

| Date tested | `appointments[]` count | Matches UI | Matches Bookly | Result |
|-------------|------------------------|------------|----------------|--------|
| | | ☐ Yes ☐ No | ☐ Yes ☐ No | ☐ Pass ☐ Fail |

**Sample appointment ID verified:**  

---

## Sign-off checklist

| Check | Date | Pass / Fail | Notes |
|-------|------|-------------|-------|
| Busy day side-by-side | | | |
| Empty day | | | |
| Extras duration | | | |
| Multi-customer (if applicable) | | | |
| Custom service (if applicable) | | | |
| API spot-check | | | |
| Demo layout (tables, grid, mobile) | | | |
| Validation Mode disabled after test | | | |

---

## Sign-off

| Role | Name | Signature / date |
|------|------|------------------|
| Tester | | |
| Studio lead | | |
| Engineering (if reviewed) | | |

---

## Follow-up actions

| Priority | Action | Owner | Target date | Status |
|----------|--------|-------|-------------|--------|
| 1 | | | | ☐ Open ☐ Done |
| 2 | | | | ☐ Open ☐ Done |
| 3 | | | | ☐ Open ☐ Done |
