/**
 * CAD Scheduler v2 — UI
 * Shell orchestration and schedule loading. Date controls live in cad-navigation.js.
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-ui.js');

  /**
   * @param {unknown} err
   * @returns {boolean}
   */
  function isAbortError(err) {
    return Boolean(err && typeof err === 'object' && err.name === 'AbortError');
  }

  CAD.ui = {
    root: null,
    /** @type {AbortController|null} */
    _loadController: null,
    /** Monotonic id — only the newest load may apply results. */
    _loadSeq: 0,

    collectIssues() {
      const issues = [];
      const health = CAD.Config.get('health');

      if (Array.isArray(health)) {
        health.forEach((issue) => {
          if (issue?.message) {
            issues.push(String(issue.message));
          }
        });
      }

      if (typeof window.cadConfig === 'undefined') {
        issues.push('JavaScript configuration (cadConfig) is missing.');
      }

      if (!CAD.Config.get('ajaxUrl')) {
        issues.push('AJAX URL is not configured.');
      }

      if (!CAD.Config.get('nonce')) {
        issues.push('Security nonce is not configured.');
      }

      return issues;
    },

    showDiagnostics(messages) {
      if (!this.root || !messages.length) {
        return this;
      }

      this.root.innerHTML = '';
      this.root.classList.add('cad-scheduler');

      const panel = document.createElement('div');
      panel.className = 'cad-scheduler__diagnostics';
      panel.setAttribute('role', 'alert');

      const title = document.createElement('p');
      title.className = 'cad-scheduler__diagnostics-title';
      title.innerHTML = '<strong>CAD Scheduler</strong>';
      panel.appendChild(title);

      const list = document.createElement('ul');
      list.className = 'cad-scheduler__diagnostics-list';
      messages.forEach((message) => {
        const item = document.createElement('li');
        item.textContent = message;
        list.appendChild(item);
      });
      panel.appendChild(list);

      this.root.appendChild(panel);
      return this;
    },

    mount(selector) {
      this.root = document.querySelector(selector);
      if (!this.root) throw new Error(`Mount point not found: ${selector}`);
      this.root.classList.add('cad-scheduler');

      const issues = this.collectIssues();
      if (issues.length) {
        this.showDiagnostics(issues);
      }

      return this;
    },

    /**
     * Create status + calendar containers once. Never wipe .cad-nav.
     */
    ensureShell() {
      if (!this.root || this.root.querySelector('.cad-scheduler__diagnostics')) {
        return;
      }

      if (!this.root.querySelector('.cad-scheduler__status')) {
        const status = document.createElement('div');
        status.className = 'cad-scheduler__status';
        status.setAttribute('aria-live', 'polite');
        this.root.appendChild(status);
      }

      if (!this.root.querySelector('.cad-scheduler__calendar')) {
        const calendar = document.createElement('div');
        calendar.className = 'cad-scheduler__calendar';
        this.root.appendChild(calendar);
      }
    },

    renderStatus() {
      const status = this.root?.querySelector('.cad-scheduler__status');
      if (!status) return;

      status.className = 'cad-scheduler__status';
      status.textContent = '';

      if (CAD.State.get('loading')) {
        status.textContent = 'Loading schedule…';
        return;
      }

      if (CAD.State.get('error')) {
        status.classList.add('cad-scheduler__status--error');
        status.textContent = CAD.State.get('error');
      }
    },

    render() {
      if (!this.root) return this;

      if (this.root.querySelector('.cad-scheduler__diagnostics')) {
        return this;
      }

      this.ensureShell();
      this.renderStatus();
      CAD.Navigation?.sync?.();

      if (!CAD.State.get('loading')) {
        CAD.calendar?.render(this.root.querySelector('.cad-scheduler__calendar'));
      }

      return this;
    },

    /**
     * @param {string} [date] YYYY-MM-DD — defaults to selectedDate or cadConfig.today
     */
    async load(date) {
      const scheduleDate =
        date || CAD.State.get('selectedDate') || CAD.Config.get('today');

      this._loadController?.abort();
      const controller = new AbortController();
      this._loadController = controller;
      const seq = ++this._loadSeq;

      CAD.State.update({
        selectedDate: scheduleDate,
        loading: true,
        error: null,
      });
      this.render();

      try {
        const result = await CAD.API.getSchedule(scheduleDate, {
          signal: controller.signal,
        });

        if (seq !== this._loadSeq) {
          return this;
        }

        if (result?.success === false) {
          throw new Error(result.data?.message || 'Failed to load schedule');
        }
        CAD.State.set('appointments', result.data?.appointments ?? []);
      } catch (err) {
        if (isAbortError(err) || seq !== this._loadSeq) {
          return this;
        }
        CAD.State.set('error', err.message);
        CAD.State.set('appointments', []);
      } finally {
        if (seq === this._loadSeq) {
          CAD.State.set('loading', false);
          this.render();
        }
      }

      return this;
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
