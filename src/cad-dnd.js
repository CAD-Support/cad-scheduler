/**
 * CAD Scheduler — Drag & drop appointment rescheduling (Sprint 3 P1).
 * Moves cards between lanes/times; persists via CAD.API.updateAppointment.
 * @module cad-dnd
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-dnd.js');

  const SLOT_MINUTES = 15;
  const DRAG_THRESHOLD_PX = 6;

  /** @type {null | Record<string, *>} */
  let active = null;

  function appointments() {
    const list = CAD.State.get('appointments');
    return Array.isArray(list) ? list : [];
  }

  function findAppointment(id) {
    return appointments().find((a) => String(a.id) === String(id)) || null;
  }

  function slotHeightPx(matrix) {
    const raw = getComputedStyle(matrix).getPropertyValue('--cad-slot-height').trim();
    const n = parseFloat(raw);
    return Number.isFinite(n) && n > 0 ? n : 28;
  }

  function dayStartMin(matrix) {
    const raw = getComputedStyle(matrix).getPropertyValue('--cad-day-start-min').trim();
    const n = parseInt(raw, 10);
    return Number.isFinite(n) ? n : 8 * 60;
  }

  /**
   * Format a local wall-clock Date as Bookly/WP `Y-m-d H:i:s` (no UTC conversion).
   * @param {Date} date
   * @returns {string}
   */
  function formatBooklyDate(date) {
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
      return '';
    }
    const y = date.getFullYear();
    const mo = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    const h = String(date.getHours()).padStart(2, '0');
    const mi = String(date.getMinutes()).padStart(2, '0');
    const s = String(date.getSeconds()).padStart(2, '0');
    return `${y}-${mo}-${d} ${h}:${mi}:${s}`;
  }

  /**
   * @param {string} isoDate YYYY-MM-DD
   * @param {number} minutesFromMidnight
   * @returns {string} Y-m-d H:i:s
   */
  function toMysqlLocal(isoDate, minutesFromMidnight) {
    const h = Math.floor(minutesFromMidnight / 60);
    const m = minutesFromMidnight % 60;
    return `${isoDate} ${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:00`;
  }

  /**
   * Local Date for a schedule day + minutes-from-midnight (studio wall clock).
   * @param {string} isoDate
   * @param {number} minutesFromMidnight
   * @returns {Date}
   */
  function dropDateFromMinutes(isoDate, minutesFromMidnight) {
    const date = new Date(`${isoDate}T00:00:00`);
    date.setHours(0, 0, 0, 0);
    date.setMinutes(minutesFromMidnight);
    return date;
  }

  /**
   * @param {string} isoDate
   * @param {number} minutesFromMidnight
   * @param {number} durationMs
   */
  function toIsoRange(isoDate, minutesFromMidnight, durationMs) {
    const start = dropDateFromMinutes(isoDate, minutesFromMidnight);
    const end = new Date(start.getTime() + durationMs);
    // Optimistic State only — AJAX must NOT use toISOString() (UTC).
    return { startIso: start.toISOString(), endIso: end.toISOString() };
  }

  function updateStateAppointment(id, patch) {
    CAD.State.set(
      'appointments',
      appointments().map((a) => (String(a.id) === String(id) ? { ...a, ...patch } : a))
    );
  }

  function restoreSnapshot(snapshot) {
    if (!snapshot) return;
    updateStateAppointment(snapshot.id, {
      tableId: snapshot.tableId,
      start: snapshot.start,
      end: snapshot.end,
    });
  }

  function reRender() {
    const host =
      CAD.ui?.calendarEl ||
      document.querySelector('#cad-scheduler .cad-scheduler__calendar');
    if (host && CAD.calendar?.render) {
      CAD.calendar.render(host);
    }
  }

  /**
   * Snap using the appointment's TOP edge (clientY - grabOffsetY), not the cursor.
   * @param {HTMLElement} lane
   * @param {number} clientY pointer clientY
   * @param {HTMLElement} matrix
   * @param {number} grabOffsetY distance from card top to pointer at grab
   * @param {string} [rowInputSource] TEMP 2.7.5 — when set, log [ROW INPUT] before rowIndex
   * @returns {{
   *   mouseY: number,
   *   topEdgeY: number,
   *   grabOffsetY: number,
   *   rowIndex: number,
   *   slotPx: number,
   *   dayStartMin: number,
   *   snappedMinutes: number
   * }}
   */
  function resolveDropSnap(lane, clientY, matrix, grabOffsetY, rowInputSource) {
    // mouseY is Y within .cad-matrix__blocks (viewport clientY − blocksRect.top).
    // Not relative to .cad-matrix or .cad-matrix__scroll; scroll is baked into rect.top.
    const blocks = lane.querySelector('.cad-matrix__blocks') || lane;
    const rect = blocks.getBoundingClientRect();
    const mouseY = clientY - rect.top;
    const offset = Number(grabOffsetY) || 0;
    const topEdgeY = mouseY - offset;
    const slotPx = slotHeightPx(matrix);
    if (rowInputSource) {
      // eslint-disable-next-line no-console
      console.log('[ROW INPUT]', {
        yUsedForRowCalculation: topEdgeY,
        source: rowInputSource,
      });
    }
    const rowIndex = Math.max(0, Math.round(topEdgeY / slotPx));
    const dayStart = dayStartMin(matrix);
    const snappedMinutes = dayStart + rowIndex * SLOT_MINUTES;
    return {
      mouseY,
      topEdgeY,
      grabOffsetY: offset,
      rowIndex,
      slotPx,
      dayStartMin: dayStart,
      snappedMinutes,
    };
  }

  function laneFromPoint(clientX, clientY) {
    const el = document.elementFromPoint(clientX, clientY);
    return el?.closest?.('.cad-matrix__lane') || null;
  }

  const APPOINTMENT_ROOT = 'button.cad-appointment';

  function endSessionVisual(session) {
    session.el.classList.remove('cad-appointment--dragging');
    if (session.ghost) {
      session.ghost.remove();
      session.ghost = null;
    }
    document.body.classList.remove('cad-dnd-active');
  }

  function onPointerDown(event) {
    if (event.button != null && event.button !== 0) return;
    const el = event.target.closest?.(APPOINTMENT_ROOT);
    if (!el || !event.currentTarget.contains(el)) return;
    if (event.target.closest('.cad-popover')) return;

    const appointmentId = el.dataset.id;
    const appt = findAppointment(appointmentId);
    if (!appt) return;

    const start = new Date(appt.start);
    const end = new Date(appt.end);
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return;

    const lane = el.closest('.cad-matrix__lane');
    if (!lane) return;

    const elRect = el.getBoundingClientRect();

    active = {
      pointerId: event.pointerId,
      appointmentId: String(appointmentId),
      el,
      originTableId: String(appt.tableId),
      originLane: lane,
      startClientX: event.clientX,
      startClientY: event.clientY,
      /** Cursor offset from appointment TOP — drop/snap must use top edge, not cursor. */
      grabOffsetY: event.clientY - elRect.top,
      durationMs: Math.max(60 * 1000, end.getTime() - start.getTime()),
      dragging: false,
      ghost: null,
      lastSnap: null,
      snapshot: {
        id: appt.id,
        tableId: appt.tableId,
        start: appt.start,
        end: appt.end,
      },
    };

    try {
      el.setPointerCapture(event.pointerId);
    } catch (_e) {
      /* ignore */
    }
  }

  function onPointerMove(event) {
    if (!active || event.pointerId !== active.pointerId) return;

    const dx = event.clientX - active.startClientX;
    const dy = event.clientY - active.startClientY;
    if (!active.dragging) {
      if (Math.hypot(dx, dy) < DRAG_THRESHOLD_PX) return;
      active.dragging = true;
      document.body.classList.add('cad-dnd-active');
      active.el.classList.add('cad-appointment--dragging');
      CAD.editor?.clear?.();
    }

    event.preventDefault();

    const matrix = active.el.closest('.cad-matrix') || document.querySelector('.cad-matrix');
    if (!matrix) return;

    const lane = laneFromPoint(event.clientX, event.clientY) || active.originLane;
    const snap = resolveDropSnap(lane, event.clientY, matrix, active.grabOffsetY);
    active.lastSnap = snap;
    const topPx = ((snap.snappedMinutes - snap.dayStartMin) / SLOT_MINUTES) * snap.slotPx;
    const blocks = lane.querySelector('.cad-matrix__blocks');

    if (!active.ghost) {
      active.ghost = active.el.cloneNode(true);
      active.ghost.classList.add('cad-appointment--ghost');
      active.ghost.removeAttribute('data-id');
      active.ghost.setAttribute('aria-hidden', 'true');
      active.ghost.style.pointerEvents = 'none';
      (blocks || lane).appendChild(active.ghost);
    } else if (blocks && active.ghost.parentElement !== blocks) {
      blocks.appendChild(active.ghost);
    }

    active.ghost.style.top = `${Math.max(0, topPx)}px`;
    active.ghost.dataset.tableId = lane.dataset.tableId || '';
    active.ghost.dataset.snapMinutes = String(snap.snappedMinutes);
  }

  async function finishPointer(event) {
    if (!active || event.pointerId !== active.pointerId) return;
    const session = active;
    active = null;

    if (!session.dragging) {
      endSessionVisual(session);
      return;
    }

    const matrix =
      session.originLane.closest('.cad-matrix') || document.querySelector('.cad-matrix');
    if (!matrix) {
      endSessionVisual(session);
      reRender();
      return;
    }

    // Prefer last preview snap (matches ghost). Recompute from TOP edge if needed.
    // 2.7.5: always compute livePointerSnap for debug compare; selection unchanged.
    const lane = laneFromPoint(event.clientX, event.clientY) || session.originLane;
    const usedLastSnap = Boolean(session.lastSnap);
    const lastSnap = session.lastSnap;
    if (usedLastSnap && lastSnap) {
      // lastSnap.rowIndex was computed earlier; log the Y that produced it.
      // eslint-disable-next-line no-console
      console.log('[ROW INPUT]', {
        yUsedForRowCalculation: lastSnap.topEdgeY,
        source: 'lastSnap',
      });
    }
    const livePointerSnap = resolveDropSnap(
      lane,
      event.clientY,
      matrix,
      session.grabOffsetY,
      usedLastSnap ? undefined : 'livePointer'
    );
    const finalSnapUsed = lastSnap || livePointerSnap;
    const snap = finalSnapUsed;
    const tableId = String(
      (session.ghost && session.ghost.dataset.tableId) ||
        lane.dataset.tableId ||
        session.originTableId
    );

    // Geometry for debug only (does not affect snap / payload).
    const blocksEl = lane.querySelector('.cad-matrix__blocks') || lane;
    const scrollEl = matrix.querySelector('.cad-matrix__scroll');
    const headEl = matrix.querySelector('.cad-matrix__head');
    const blocksRectFull = blocksEl.getBoundingClientRect();
    const matrixRectFull = matrix.getBoundingClientRect();
    const scrollRectFull = scrollEl ? scrollEl.getBoundingClientRect() : null;
    const matrixTop = blocksRectFull.top;
    const matrixScrollTop = scrollEl ? scrollEl.scrollTop : 0;
    const headerHeight = headEl ? headEl.getBoundingClientRect().height : 0;
    const liveMouseYFromBlocks = event.clientY - blocksRectFull.top;
    const liveMouseYFromScroll =
      scrollEl && scrollRectFull
        ? event.clientY - scrollRectFull.top + scrollEl.scrollTop
        : null;
    const liveMouseYFromMatrix = event.clientY - matrixRectFull.top;

    // eslint-disable-next-line no-console
    console.log('[LAST SNAP]', {
      usedLastSnap,
      lastSnap,
      livePointerSnap,
      liveMouseYFromBlocks,
      liveMouseYFromScroll,
      finalSnapUsed,
    });
    // eslint-disable-next-line no-console
    console.log('[RECTS]', {
      blocksRect: {
        top: blocksRectFull.top,
        left: blocksRectFull.left,
        height: blocksRectFull.height,
        width: blocksRectFull.width,
      },
      scrollRect: scrollRectFull
        ? {
            top: scrollRectFull.top,
            left: scrollRectFull.left,
            height: scrollRectFull.height,
            width: scrollRectFull.width,
          }
        : null,
      matrixRect: {
        top: matrixRectFull.top,
        left: matrixRectFull.left,
        height: matrixRectFull.height,
        width: matrixRectFull.width,
      },
      scrollTop: scrollEl ? scrollEl.scrollTop : null,
    });

    endSessionVisual(session);

    const selectedDate = String(
      CAD.State.get('selectedDate') || CAD.Config.get('today') || ''
    );
    if (!/^\d{4}-\d{2}-\d{2}$/.test(selectedDate)) {
      CAD.notify?.show('Cannot reschedule: missing date.', 'error');
      reRender();
      return;
    }

    // --- 2.7.5 TEMP DEBUG: instrumentation only — values unchanged from 2.7.3 ---
    const pointerY = event.clientY;
    const mouseY = snap.mouseY;
    const topEdgeY = snap.topEdgeY;
    const grabOffsetY = snap.grabOffsetY;
    const rowIndex = snap.rowIndex;
    const slotMinutes = SLOT_MINUTES;
    const slotPx = snap.slotPx;
    const dayStartMin = snap.dayStartMin;
    const snappedMinutes = snap.snappedMinutes;
    const finalMinutes = snappedMinutes;
    const cssDayStartMinRaw = getComputedStyle(matrix)
      .getPropertyValue('--cad-day-start-min')
      .trim();
    const cssDayStartMin =
      cssDayStartMinRaw === '' ? null : parseInt(cssDayStartMinRaw, 10);
    const expected = rowIndex * slotMinutes + dayStartMin;
    const startMysql = toMysqlLocal(selectedDate, finalMinutes);
    const ajaxPayload = {
      appointmentId: session.appointmentId,
      staffId: tableId,
      start: startMysql,
    };

    const blocksRect = blocksRectFull;
    const matrixRect = matrixRectFull;
    const scrollRect = scrollRectFull;

    // eslint-disable-next-line no-console
    console.log('[COORDS]', {
      clientY: event.clientY,
      pageY: event.pageY,
      screenY: event.screenY,
      matrixRect: {
        top: matrixRect.top,
        left: matrixRect.left,
        height: matrixRect.height,
        width: matrixRect.width,
      },
      scrollTop: matrix.scrollTop,
      windowScrollY: window.scrollY,
      computedMouseY: mouseY,
      mouseYOrigin: '.cad-matrix__blocks',
      blocksRect: {
        top: blocksRect.top,
        left: blocksRect.left,
        height: blocksRect.height,
        width: blocksRect.width,
      },
      scrollElScrollTop: scrollEl ? scrollEl.scrollTop : null,
      scrollElRect: scrollRect
        ? {
            top: scrollRect.top,
            left: scrollRect.left,
            height: scrollRect.height,
            width: scrollRect.width,
          }
        : null,
      liveMouseYFromBlocks,
      liveMouseYFromScroll,
      liveMouseYFromMatrix,
      usedLastSnap,
      snapMouseYVsLiveBlocks: mouseY - liveMouseYFromBlocks,
    });
    // eslint-disable-next-line no-console
    console.log('[STEP 0]', {
      usedLastSnap,
      pointerY,
      mouseY,
      topEdgeY,
      grabOffsetY,
      cssDayStartMin,
    });
    // eslint-disable-next-line no-console
    console.log('[STEP 1]', {
      pointerY,
      mouseY,
      topEdgeY,
      grabOffsetY,
      slotPx,
    });
    // eslint-disable-next-line no-console
    console.log('[STEP 2]', {
      rowIndex,
      slotMinutes,
    });
    // eslint-disable-next-line no-console
    console.log('[STEP 3]', {
      dayStartMin,
    });
    // eslint-disable-next-line no-console
    console.log('[STEP 4]', {
      snappedMinutes,
    });
    // eslint-disable-next-line no-console
    console.log('[STEP 5]', {
      finalMinutes,
    });
    // eslint-disable-next-line no-console
    console.log('[STEP 6]', {
      startMysql,
    });
    // eslint-disable-next-line no-console
    console.log('[STEP 6b]', {
      expected,
      snappedMinutes,
    });
    // eslint-disable-next-line no-console
    console.log('[STEP 7]', {
      ajaxPayload,
    });
    // eslint-disable-next-line no-console
    console.log('[STEP 8]', {
      timezoneOffset: new Date().getTimezoneOffset(),
    });
    // eslint-disable-next-line no-console
    console.log('[CAD row origin]', {
      matrixTop,
      matrixScrollTop,
      headerHeight,
      pointerY,
      calculatedY: topEdgeY,
      rowIndex,
    });
    // --- end 2.7.5 TEMP DEBUG ---

    const { startIso, endIso } = toIsoRange(
      selectedDate,
      finalMinutes,
      session.durationMs
    );

    updateStateAppointment(session.appointmentId, {
      tableId,
      start: startIso,
      end: endIso,
    });
    reRender();

    try {
      const result = await CAD.API.updateAppointment(ajaxPayload);

      if (result?.success === false) {
        throw Object.assign(new Error(result.data?.message || 'Could not reschedule.'), {
          payload: result.data,
        });
      }

      const updated = result?.data?.appointment;
      if (updated && updated.id != null) {
        updateStateAppointment(session.appointmentId, updated);
        reRender();
      }

      CAD.notify?.show('Appointment moved.', 'success');
    } catch (err) {
      restoreSnapshot(session.snapshot);
      reRender();
      CAD.notify?.show(
        err?.payload?.message || err?.message || 'Could not move appointment.',
        'error'
      );
    }
  }

  function onPointerCancel(event) {
    if (!active || event.pointerId !== active.pointerId) return;
    const snapshot = active.snapshot;
    endSessionVisual(active);
    active = null;
    restoreSnapshot(snapshot);
    reRender();
  }

  function suppressNextClick(el) {
    const suppress = (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      el.removeEventListener('click', suppress, true);
    };
    el.addEventListener('click', suppress, true);
    setTimeout(() => el.removeEventListener('click', suppress, true), 0);
  }

  CAD.DnD = {
    /** Appointment card root rendered by CAD.components.appointmentBlock. */
    appointmentSelector: APPOINTMENT_ROOT,

    /**
     * Bind drag handlers on the calendar host (event delegation to button.cad-appointment).
     * Safe to call after every CAD.calendar.render — no-ops if already bound.
     * @param {HTMLElement} container calendar host
     */
    bind(container) {
      if (!container || container.dataset.cadDndBound === '1') return;
      container.dataset.cadDndBound = '1';

      container.addEventListener('pointerdown', onPointerDown);
      container.addEventListener('pointermove', onPointerMove);
      container.addEventListener('pointerup', (e) => {
        const sessionEl = active?.el;
        const didDrag = Boolean(active?.dragging);
        finishPointer(e).finally(() => {
          if (didDrag && sessionEl) suppressNextClick(sessionEl);
        });
      });
      container.addEventListener('pointercancel', onPointerCancel);
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
