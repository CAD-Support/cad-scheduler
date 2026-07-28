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
   * @param {string} isoDate
   * @param {number} minutesFromMidnight
   * @param {number} durationMs
   */
  function toIsoRange(isoDate, minutesFromMidnight, durationMs) {
    const start = new Date(`${isoDate}T00:00:00`);
    start.setHours(0, 0, 0, 0);
    start.setMinutes(minutesFromMidnight);
    const end = new Date(start.getTime() + durationMs);
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

  function snapMinutes(lane, clientY, matrix) {
    const blocks = lane.querySelector('.cad-matrix__blocks') || lane;
    const rect = blocks.getBoundingClientRect();
    const y = clientY - rect.top;
    const slotPx = slotHeightPx(matrix);
    const slots = Math.max(0, Math.round(y / slotPx));
    return dayStartMin(matrix) + slots * SLOT_MINUTES;
  }

  function laneFromPoint(clientX, clientY) {
    const el = document.elementFromPoint(clientX, clientY);
    return el?.closest?.('.cad-matrix__lane') || null;
  }

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
    const el = event.target.closest?.('.cad-appointment');
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

    active = {
      pointerId: event.pointerId,
      appointmentId: String(appointmentId),
      el,
      originTableId: String(appt.tableId),
      originLane: lane,
      startClientX: event.clientX,
      startClientY: event.clientY,
      durationMs: Math.max(60 * 1000, end.getTime() - start.getTime()),
      dragging: false,
      ghost: null,
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
    const minutes = snapMinutes(lane, event.clientY, matrix);
    const slotPx = slotHeightPx(matrix);
    const topPx = ((minutes - dayStartMin(matrix)) / SLOT_MINUTES) * slotPx;
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
  }

  async function finishPointer(event) {
    if (!active || event.pointerId !== active.pointerId) return;
    const session = active;
    active = null;
    endSessionVisual(session);

    if (!session.dragging) return;

    const matrix =
      session.originLane.closest('.cad-matrix') || document.querySelector('.cad-matrix');
    if (!matrix) {
      reRender();
      return;
    }

    const lane = laneFromPoint(event.clientX, event.clientY) || session.originLane;
    const tableId = String(lane.dataset.tableId || session.originTableId);
    const minutes = snapMinutes(lane, event.clientY, matrix);
    const selectedDate = String(
      CAD.State.get('selectedDate') || CAD.Config.get('today') || ''
    );
    if (!/^\d{4}-\d{2}-\d{2}$/.test(selectedDate)) {
      CAD.notify?.show('Cannot reschedule: missing date.', 'error');
      reRender();
      return;
    }

    const startMysql = toMysqlLocal(selectedDate, minutes);
    const { startIso, endIso } = toIsoRange(selectedDate, minutes, session.durationMs);

    updateStateAppointment(session.appointmentId, {
      tableId,
      start: startIso,
      end: endIso,
    });
    reRender();

    try {
      const result = await CAD.API.updateAppointment({
        appointmentId: session.appointmentId,
        staffId: tableId,
        start: startMysql,
      });

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
    /**
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

  const prevRender = CAD.calendar?.render;
  if (typeof prevRender === 'function') {
    CAD.calendar.render = function patchedRender(container) {
      prevRender.call(CAD.calendar, container);
      if (container) {
        delete container.dataset.cadDndBound;
        CAD.DnD.bind(container);
      }
    };
  }
})(typeof window !== 'undefined' ? window : globalThis);
