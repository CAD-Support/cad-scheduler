/**
 * Quick Add facade — Sprint 3.2.6.
 * Empty-lane hit-testing + slot highlight open New Reservation via shared dialog.
 * @module components/quick-add
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before components/quick-add.js');

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

  function slotMinutes() {
    const n = Number(CAD.Config.get('slotMinutes'));
    return Number.isFinite(n) && n > 0 ? n : 15;
  }

  function snapMinutesFromPoint(lane, clientY, matrix) {
    const blocks = lane.querySelector('.cad-matrix__blocks') || lane;
    const rect = blocks.getBoundingClientRect();
    const y = clientY - rect.top;
    const slotPx = slotHeightPx(matrix);
    const rowIndex = Math.max(0, Math.round(y / slotPx));
    return dayStartMin(matrix) + rowIndex * slotMinutes();
  }

  CAD.QuickAdd = {
    container: null,

    init() {
      CAD.ReservationDialog?.init?.();
      return this;
    },

    isOpen() {
      return (
        CAD.ReservationDialog?.isOpen?.() &&
        CAD.ReservationDialog.mode === 'new'
      );
    },

    /**
     * @param {{
     *   tableId: string,
     *   date: string,
     *   startMinutes: number,
     *   lane?: HTMLElement,
     *   matrix?: HTMLElement
     * }} ctx
     */
    open(ctx) {
      CAD.ReservationDialog?.open?.({ mode: 'new', ...ctx });
      return this;
    },

    close() {
      CAD.ReservationDialog?.close?.();
      return this;
    },

    /**
     * Bind empty-lane clicks on the calendar host.
     * @param {HTMLElement} container
     */
    bind(container) {
      if (!container) return this;
      this.init();

      if (this.container === container && container.dataset.cadQuickAddBound === '1') {
        return this;
      }

      this.container = container;
      container.dataset.cadQuickAddBound = '1';

      container.addEventListener('click', (event) => {
        if (document.body.classList.contains('cad-dnd-active')) return;
        if (
          event.target.closest(
            '.cad-appointment, .cad-popover, .cad-rm, .cad-quick-add'
          )
        ) {
          return;
        }

        const lane = event.target.closest('.cad-matrix__lane');
        if (!lane || !container.contains(lane)) return;

        const matrix = lane.closest('.cad-matrix') || container;
        const tableId = lane.dataset.tableId;
        if (!tableId) return;

        const date = String(
          CAD.State.get('selectedDate') || CAD.Config.get('today') || ''
        );
        if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) return;

        event.preventDefault();
        event.stopPropagation();

        const startMinutes = snapMinutesFromPoint(lane, event.clientY, matrix);
        this.open({
          tableId: String(tableId),
          date,
          startMinutes,
          lane,
          matrix,
        });
      });

      return this;
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
