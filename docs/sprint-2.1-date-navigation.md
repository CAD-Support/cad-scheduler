# Sprint 2.1 — Calendar Date Navigation

Day navigation for CAD Scheduler without a full page reload.

## Goal

Add Previous / Today / Next / date picker above the scheduler. Changing the date reloads appointments via the existing `cad_get_schedule` AJAX action and re-renders the matrix in place.

## Behaviour

| Control | Action |
|---------|--------|
| ◀ Previous | Load previous calendar day |
| Today | Load `cadConfig.today` (`Y-m-d` from WordPress) |
| Next ▶ | Load next calendar day |
| Pick Date | Native `<input type="date">` — load any day |

- **No** full page refresh
- **No** new AJAX endpoint
- **No** recreation of the nav shell on each load
- Table layout preserved; only appointments + corner date update
- Rapid date changes cancel in-flight loads (`AbortController`); only the newest response may update appointments / render

## Init flow

```
CAD.init(cadConfig)
  ↓
CAD.ui.mount('#cad-scheduler')
  ↓
CAD.Navigation.init()
  ↓
CAD.ui.load(today)   // YYYY-MM-DD from cadConfig.today
```

## Navigation flow

```
CAD.State.update({ selectedDate })
  ↓
CAD.API.getSchedule(date)   // existing endpoint
  ↓
CAD.calendar.render(container)
```

## Stale response protection

`CAD.ui.load()` aborts any previous in-flight `fetch` via `AbortController` and tracks a monotonic load sequence id. Aborted or out-of-order responses are ignored — they must not clear errors, clear appointments, or call `CAD.calendar.render` for an older date. `CAD.API.getSchedule(date)` remains the same call site; an optional `{ signal }` is passed through to `fetch` only.

## State

```js
CAD.State.set('selectedDate', '2026-07-24');
// or
CAD.State.update({ selectedDate: '2026-07-24' });
```

- API / PHP always receive **`YYYY-MM-DD`**
- Header uses browser locale for display only (e.g. `Friday, July 24, 2026`)

## Module

| File | Role |
|------|------|
| `src/cad-navigation.js` | Prev / Next / Today / picker / display formatting |
| `src/cad-ui.js` | Shell + `load(date)` + abort/sequence guard — no nav control logic |
| `src/cad-api.js` | `getSchedule(date[, { signal }])` → `cad_get_schedule` |
| `src/cad-calendar.js` | Unchanged rendering; corner shows `selectedDate` |

## Acceptance

- [ ] Opens on today's date from `cadConfig.today`
- [ ] Previous / Next change the displayed day
- [ ] Today returns to today
- [ ] Date picker loads any date
- [ ] No full page refresh
- [ ] No new AJAX endpoint
- [ ] Existing appointment blocks still render correctly

## Deploy notes

1. Push tagged release **`2.1.0`** (jsDelivr).
2. Update Code Snippets priority **20** (`snippets/10A-ajax-bridge.php`) so it enqueues `cad-navigation.js` and uses the new init inline script.
