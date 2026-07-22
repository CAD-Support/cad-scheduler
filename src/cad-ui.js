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
      CAD.State.set('loading', true);
      CAD.State.set('error', null);

      try {
        const result = await CAD.API.getSchedule(date);
        CAD.State.set('appointments', result.data?.appointments ?? []);
        this.render();
      } catch (err) {
        CAD.State.set('error', err.message);
      } finally {
        CAD.State.set('loading', false);
      }

      return this;
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
