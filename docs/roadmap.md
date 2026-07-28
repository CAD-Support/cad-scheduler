# Roadmap

## Phase 1 — Foundation

- [x] AJAX bridge snippet (`10A-ajax-bridge.php`)
- [x] Core module: config, state, Bookly API wrapper
- [x] Basic multi-table calendar grid (read-only)

## Phase 2 — Editing / Calendar UX (Sprints 2.1–2.6)

- [x] Calendar date navigation (Previous / Today / Next / picker) — Sprint 2.1
- [x] Scrollable calendar (sticky headers / time labels, horizontal overflow) — Sprint 2.2
- [x] Dynamic day range (open hours ∪ appointments) — Sprint 2.3
- [x] Adaptive appointment cards (height-based density, badges, hover tooltip) — Sprint 2.4
- [x] Card readability (opaque appointment backgrounds) — Sprint 2.4.1
- [x] Service-aware appointment popover + normalized model — Sprint 2.5
- [x] All visible Bookly resources as calendar columns — Sprint 2.5.1
- [x] Scheduler UX polish (cards + popover + shared badges) — Sprint 2.6
- [ ] Inline appointment editor (partially superseded by Sprint 3 Quick Add / popover status)
- [ ] Drag-and-drop rescheduling — **Sprint 3.0 Phase 1 / P1**
- [ ] Conflict detection across tables — **Sprint 3.0 Phase 1 / P1**
- [ ] Quick Add appointment — **Sprint 3.0 Phase 1 / P2**

## Sprint 3.0 — Studio Operations (planned)

Daily-ops scheduler before handoff: view, create, move, edit, update without the native Bookly calendar. Planning doc: [sprint-3.0-studio-operations.md](sprint-3.0-studio-operations.md).

### Phase 1 — Critical (ship first; production-ready gate)

P1/P2 **must** use Bookly save/create flows (notifications, validation, integrations).

| Priority | Item | Issue | Status |
|----------|------|-------|--------|
| **P1** | Drag & drop reschedule + Bookly save + conflicts | [#3](https://github.com/CAD-Support/cad-scheduler/issues/3) | **2.7.0** / init fix **2.7.1** |
| **P2** | Quick Add from empty slot (Bookly create) | [#4](https://github.com/CAD-Support/cad-scheduler/issues/4) | Planned |

### Phase 2 — After Phase 1 is production-ready

| Priority | Item | Issue | Status |
|----------|------|-------|--------|
| **P3** | Undo (status / drag / delete) | [#2](https://github.com/CAD-Support/cad-scheduler/issues/2) | Planned |
| **P4** | Toolbar filters (type + status) | [#7](https://github.com/CAD-Support/cad-scheduler/issues/7) | Planned |
| **P5** | Live refresh (preserve scroll / date / filters) | [#6](https://github.com/CAD-Support/cad-scheduler/issues/6) | Planned |
| **P6** | Lightspeed customer lookup on create | [#5](https://github.com/CAD-Support/cad-scheduler/issues/5) | Planned |

**P1 shipped in 2.7.0.** Build order: P2 → P3 → P4 → P5 → P6.

## Phase 3 — Studio Features (later)

- [ ] Per-table availability rules
- [ ] Staff assignment per table
- [ ] Multi-studio view (filter by location)

## Phase 4 — Polish

- [ ] Responsive layout
- [ ] Keyboard navigation
- [ ] Print / export schedule view
