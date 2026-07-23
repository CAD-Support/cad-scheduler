/**
 * CAD Scheduler v2 — UI
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-ui.js');

  CAD.ui = {
    root: null,

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

      if (this.root.querySelector('.cad-scheduler__diagnostics')) {
        return this;
      }

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
