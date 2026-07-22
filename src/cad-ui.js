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

      const header = this.root.querySelector('.cad-scheduler__header');
      const loading = CAD.State.get('loading');
      const error = CAD.State.get('error');

      if (header) {
        if (loading) {
          header.textContent = 'Loading schedule…';
          header.classList.add('cad-scheduler__header--loading');
        } else if (error) {
          header.textContent = error;
          header.classList.add('cad-scheduler__header--error');
        }
      }

      if (!loading && CAD.calendar?.render) {
        CAD.calendar.render(this.root.querySelector('.cad-scheduler__calendar'));
      }

      return this;
    },

    async load(date) {
      CAD.State.set('loading', true);
      CAD.State.set('error', null);
      this.render();

      try {
        const result = await CAD.API.getSchedule(date);

        if (result && result.success === false) {
          throw new Error(result.data?.message || 'Failed to load schedule');
        }

        CAD.State.set('appointments', result.data?.appointments ?? []);
      } catch (err) {
        CAD.State.set('error', err.message);
      } finally {
        CAD.State.set('loading', false);
        this.render();
      }

      return this;
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
