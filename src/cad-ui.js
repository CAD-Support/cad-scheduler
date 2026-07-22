/**
 * CAD Scheduler — UI
 * Top-level layout orchestration and module mounting.
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;

  if (!CAD) {
    throw new Error('cad-core.js must be loaded before cad-ui.js');
  }

  CAD.ui = {
    root: null,

    mount(selector) {
      this.root = document.querySelector(selector);
      if (!this.root) {
        throw new Error(`CAD UI mount point not found: ${selector}`);
      }
      this.root.classList.add('cad-scheduler');
      return this;
    },

    render() {
      if (!this.root) return this;

      this.root.innerHTML = `
        <div class="cad-scheduler__header"></div>
        <div class="cad-scheduler__calendar"></div>
      `;

      if (CAD.calendar?.render) {
        CAD.calendar.render(this.root.querySelector('.cad-scheduler__calendar'));
      }

      return this;
    },

    async load(date) {
      CAD.state.loading = true;
      CAD.state.error = null;

      try {
        const result = await CAD.api.fetchSchedule(date);
        CAD.state.appointments = result.data?.appointments ?? [];
        this.render();
      } catch (err) {
        CAD.state.error = err.message;
      } finally {
        CAD.state.loading = false;
      }

      return this;
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
