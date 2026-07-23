/**
 * CAD Scheduler v2 — Editor
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-editor.js');

  CAD.editor = {
    selectedId: null,
    container: null,

    select(id) {
      this.selectedId = id;
      this.container?.querySelectorAll('.cad-appointment').forEach((el) => {
        el.classList.toggle('cad-appointment--selected', el.dataset.id === String(id));
      });
    },

    clear() {
      this.selectedId = null;
      this.container?.querySelectorAll('.cad-appointment--selected').forEach((el) => {
        el.classList.remove('cad-appointment--selected');
      });
    },

    bind(container) {
      if (this.container === container) return;
      this.container = container;

      container.addEventListener('click', (event) => {
        const block = event.target.closest('.cad-appointment');
        if (block?.dataset.id) {
          event.preventDefault();
          this.select(block.dataset.id);
          return;
        }
        if (event.target.closest('.cad-matrix__lane, .cad-matrix__scroll')) {
          this.clear();
        }
      });
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
