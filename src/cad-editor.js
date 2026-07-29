/**
 * CAD Scheduler v2 — Editor (selection + popover)
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-editor.js');

  function findAppointment(id) {
    const list = CAD.State.get('appointments');
    if (!Array.isArray(list)) return null;
    return list.find((a) => String(a.id) === String(id)) || null;
  }

  CAD.editor = {
    selectedId: null,
    container: null,

    select(id) {
      this.selectedId = id;
      this.container?.querySelectorAll('.cad-appointment').forEach((el) => {
        el.classList.toggle('cad-appointment--selected', el.dataset.id === String(id));
      });

      const appointment = findAppointment(id);
      if (appointment && CAD.ReservationManager) {
        CAD.ReservationManager.open(appointment);
        return;
      }
      if (appointment && CAD.Popover) {
        (CAD.Popover.render || CAD.Popover.open).call(CAD.Popover, appointment);
      }
    },

    clear() {
      this.selectedId = null;
      this.container?.querySelectorAll('.cad-appointment--selected').forEach((el) => {
        el.classList.remove('cad-appointment--selected');
      });
      CAD.ReservationManager?.close?.();
      CAD.Popover?.close?.();
    },

    bind(container) {
      if (this.container === container) return;
      this.container = container;

      container.addEventListener('click', (event) => {
        const block = event.target.closest('.cad-appointment');
        if (block?.dataset.id) {
          event.preventDefault();
          CAD.QuickAdd?.close?.();
          this.select(block.dataset.id);
          return;
        }
        // Empty lane clicks are handled by CAD.QuickAdd (stopPropagation).
        if (event.target.closest('.cad-matrix__scroll') && !event.target.closest('.cad-matrix__lane')) {
          this.clear();
          CAD.QuickAdd?.close?.();
        }
      });
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
