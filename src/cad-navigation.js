/**
 * CAD Scheduler v2 — Date navigation
 * Previous / Today / Next / native date picker.
 * Display uses browser locale; API always receives YYYY-MM-DD.
 * @module cad-navigation
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-navigation.js');

  const ISO_DATE = /^\d{4}-\d{2}-\d{2}$/;

  /**
   * @param {Date} date
   * @returns {string} YYYY-MM-DD
   */
  function toIsoDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  /**
   * Parse YYYY-MM-DD at midday to avoid DST edge cases.
   * @param {string} iso
   * @returns {Date|null}
   */
  function parseIsoDate(iso) {
    if (!ISO_DATE.test(iso)) return null;
    const date = new Date(`${iso}T12:00:00`);
    return Number.isNaN(date.getTime()) ? null : date;
  }

  /**
   * @param {string} iso
   * @param {number} days
   * @returns {string|null}
   */
  function shiftIsoDate(iso, days) {
    const date = parseIsoDate(iso);
    if (!date) return null;
    date.setDate(date.getDate() + days);
    return toIsoDate(date);
  }

  /**
   * Locale display string only — never send to PHP.
   * @param {string} iso
   * @returns {string}
   */
  function formatDisplayDate(iso) {
    const date = parseIsoDate(iso);
    if (!date) return iso || '';
    return date.toLocaleDateString(undefined, {
      weekday: 'long',
      month: 'long',
      day: 'numeric',
      year: 'numeric',
    });
  }

  function todayIso() {
    return String(CAD.Config.get('today') || toIsoDate(new Date()));
  }

  function currentIso() {
    return String(CAD.State.get('selectedDate') || todayIso());
  }

  CAD.Navigation = {
    root: null,
    dateLabel: null,
    dateInput: null,

    /**
     * Mount navigation above the scheduler and wire controls.
     * Does not load appointments — call CAD.ui.load(today) after init.
     */
    init() {
      const host = CAD.ui?.root;
      if (!host) {
        throw new Error('CAD.ui.mount() must run before CAD.Navigation.init()');
      }

      if (host.querySelector('.cad-scheduler__diagnostics')) {
        return this;
      }

      let nav = host.querySelector('.cad-nav');
      if (!nav) {
        nav = document.createElement('nav');
        nav.className = 'cad-nav';
        nav.setAttribute('aria-label', 'Schedule date');
        nav.innerHTML = `
          <div class="cad-nav__controls">
            <button type="button" class="cad-nav__btn" data-cad-nav="prev">◀ Previous</button>
            <button type="button" class="cad-nav__btn cad-nav__btn--today" data-cad-nav="today">Today</button>
            <button type="button" class="cad-nav__btn" data-cad-nav="next">Next ▶</button>
          </div>
          <h2 class="cad-nav__date" aria-live="polite"></h2>
          <label class="cad-nav__picker">
            <span class="cad-nav__picker-icon" aria-hidden="true">📅</span>
            <span class="cad-nav__picker-text">Pick Date</span>
            <input type="date" class="cad-nav__date-input" />
          </label>
        `;
        host.insertBefore(nav, host.firstChild);
      }

      this.root = nav;
      this.dateLabel = nav.querySelector('.cad-nav__date');
      this.dateInput = nav.querySelector('.cad-nav__date-input');

      if (!nav.dataset.cadNavBound) {
        nav.addEventListener('click', (event) => {
          const button = event.target.closest('[data-cad-nav]');
          if (!button || !nav.contains(button)) return;
          const action = button.getAttribute('data-cad-nav');
          if (action === 'prev') this.previousDay();
          if (action === 'next') this.nextDay();
          if (action === 'today') this.goToday();
        });

        this.dateInput?.addEventListener('change', () => {
          const value = this.dateInput.value;
          if (ISO_DATE.test(value)) {
            this.goTo(value);
          }
        });

        nav.dataset.cadNavBound = '1';
      }

      const initial = todayIso();
      if (!CAD.State.get('selectedDate')) {
        CAD.State.set('selectedDate', initial);
      }
      this.sync();

      return this;
    },

    /**
     * Refresh label, picker value, and disabled state from CAD.State.
     */
    sync() {
      if (!this.root) return this;

      const iso = currentIso();
      if (this.dateLabel) {
        this.dateLabel.textContent = formatDisplayDate(iso);
      }
      if (this.dateInput && this.dateInput.value !== iso) {
        this.dateInput.value = iso;
      }

      const loading = Boolean(CAD.State.get('loading'));
      this.root.querySelectorAll('button, input').forEach((el) => {
        el.disabled = loading;
      });

      return this;
    },

    formatDate: formatDisplayDate,

    /**
     * @param {string} isoDate YYYY-MM-DD
     * @returns {Promise<*>}
     */
    async goTo(isoDate) {
      if (!ISO_DATE.test(isoDate)) {
        CAD.Logger.warn('Invalid date for navigation:', isoDate);
        return null;
      }

      CAD.State.update({ selectedDate: isoDate });
      this.sync();

      if (!CAD.ui?.load) {
        throw new Error('CAD.ui.load is required for date navigation');
      }

      return CAD.ui.load(isoDate);
    },

    previousDay() {
      const next = shiftIsoDate(currentIso(), -1);
      return next ? this.goTo(next) : null;
    },

    nextDay() {
      const next = shiftIsoDate(currentIso(), 1);
      return next ? this.goTo(next) : null;
    },

    goToday() {
      return this.goTo(todayIso());
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
