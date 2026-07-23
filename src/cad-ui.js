/**
 * CAD Scheduler v2 — UI
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-ui.js');

  CAD.ui = {
    root: null,

    mount(selector) {
      this.root = document.querySelector(selector);
      if (!this.root) throw new Error(`Mount point not found: ${selector}`);
      this.root.classList.add('cad-scheduler');
      return this;
    },

    renderHeader() {
      const header = this.root?.querySelector('.cad-scheduler__header');
      if (!header) return;

      header.className = 'cad-scheduler__header';
      header.textContent = '';

      if (CAD.State.get('loading')) {
        header.textContent = 'Loading schedule…';
        return;
      }
      if (CAD.State.get('error')) {
        header.classList.add('cad-scheduler__header--error');
        header.textContent = CAD.State.get('error');
        return;
      }

      const date = CAD.State.get('date');
      if (date) {
        const title = document.createElement('h2');
        title.className = 'cad-scheduler__title';
        title.textContent = new Date(`${date}T12:00:00`).toLocaleDateString([], {
          weekday: 'long',
          month: 'long',
          day: 'numeric',
          year: 'numeric',
        });
        header.appendChild(title);
      }
    },

    render() {
      if (!this.root) return this;

      this.root.innerHTML = `
        <div class="cad-scheduler__header"></div>
        <div class="cad-scheduler__calendar"></div>
      `;

      this.renderHeader();

      if (!CAD.State.get('loading')) {
        CAD.calendar?.render(this.root.querySelector('.cad-scheduler__calendar'));
      }

      return this;
    },

    async load(date) {
      CAD.State.set('date', date);
      CAD.State.set('loading', true);
      CAD.State.set('error', null);
      this.render();

      try {
        const result = await CAD.API.getSchedule(date);
        if (result?.success === false) {
          throw new Error(result.data?.message || 'Failed to load schedule');
        }
        CAD.State.set('appointments', result.data?.appointments ?? []);
      } catch (err) {
        CAD.State.set('error', err.message);
        CAD.State.set('appointments', []);
      } finally {
        CAD.State.set('loading', false);
        this.render();
      }

      return this;
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
