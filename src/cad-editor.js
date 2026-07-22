/**
 * CAD Scheduler — Editor
 * Inline editing: select, move, and update appointments.
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;

  if (!CAD) {
    throw new Error('cad-core.js must be loaded before cad-editor.js');
  }

  CAD.editor = {
    selectedId: null,

    select(appointmentId) {
      this.selectedId = appointmentId;
      document.querySelectorAll('.cad-appointment--selected').forEach((el) => {
        el.classList.remove('cad-appointment--selected');
      });
      const el = document.querySelector(`.cad-appointment[data-id="${appointmentId}"]`);
      el?.classList.add('cad-appointment--selected');
    },

    bind(container) {
      container.addEventListener('click', (event) => {
        const block = event.target.closest('.cad-appointment');
        if (block?.dataset.id) {
          this.select(block.dataset.id);
        }
      });
    },

    async save(changes) {
      return CAD.API.request('cad_update_appointment', changes);
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
