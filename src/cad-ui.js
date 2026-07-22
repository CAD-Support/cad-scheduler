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

  /**
   * @param {string} date YYYY-MM-DD
   * @returns {string}
   */
  function formatHeaderDate(date) {
    const parsed = new Date(`${date}T12:00:00`);

    if (Number.isNaN(parsed.getTime())) {
      return date;
    }

    return parsed.toLocaleDateString([], {
      weekday: 'long',
      month: 'long',
      day: 'numeric',
      year: 'numeric',
    });
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

    updateHeader(header) {
      if (!header) {
        return;
      }

      header.className = 'cad-scheduler__header';
      header.replaceChildren();
      header.removeAttribute('aria-live');

      const loading = CAD.State.get('loading');
      const error = CAD.State.get('error');
      const date = CAD.State.get('date');
      const renderWarnings = CAD.State.get('renderWarnings') ?? [];

      if (loading) {
        header.classList.add('cad-scheduler__header--loading');
        header.setAttribute('aria-live', 'polite');
        const text = document.createElement('span');
        text.className = 'cad-scheduler__header-text';
        text.textContent = 'Loading schedule…';
        header.appendChild(text);
        return;
      }

      if (error) {
        header.classList.add('cad-scheduler__header--error');
        header.setAttribute('aria-live', 'assertive');
        const text = document.createElement('span');
        text.className = 'cad-scheduler__header-text';
        text.textContent = error;
        header.appendChild(text);
        return;
      }

      if (date) {
        const title = document.createElement('h2');
        title.className = 'cad-scheduler__title';
        title.textContent = formatHeaderDate(String(date));
        header.appendChild(title);
      }

      if (renderWarnings.length > 0) {
        header.classList.add('cad-scheduler__header--warning');
        const warning = document.createElement('p');
        warning.className = 'cad-scheduler__warning';
        warning.textContent = `${renderWarnings.length} appointment(s) could not be displayed.`;
        header.appendChild(warning);
      }
    },

    render() {
      if (!this.root) return this;

      this.root.innerHTML = `
        <div class="cad-scheduler__header"></div>
        <div class="cad-scheduler__calendar"></div>
      `;

      this.updateHeader(this.root.querySelector('.cad-scheduler__header'));

      if (!CAD.State.get('loading') && CAD.calendar?.render) {
        CAD.calendar.render(this.root.querySelector('.cad-scheduler__calendar'));
      }

      return this;
    },

    async load(date) {
      CAD.State.set('date', date);
      CAD.State.set('loading', true);
      CAD.State.set('error', null);
      CAD.State.set('renderWarnings', []);
      this.render();

      try {
        const result = await CAD.API.getSchedule(date);

        if (result && result.success === false) {
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
