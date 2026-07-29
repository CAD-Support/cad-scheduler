/**
 * CAD Scheduler v2 — Core
 * @module cad-core
 */
(function (global) {
  'use strict';

  const CAD = global.CAD || {};
  let config = {
    debug: false,
    dayStart: '08:00',
    dayEnd: '20:00',
    openHours: null,
    slotMinutes: 15,
    hourHeight: 64,
  };
  let state = {};

  CAD.VERSION = Object.freeze({ major: 3, minor: 2, patch: 0, build: '2026.07.29' });

  CAD.Config = Object.freeze({
    get(key) { return config[key]; },
    merge(values) { config = { ...config, ...values }; },
  });

  CAD.State = Object.freeze({
    get(key) { return state[key]; },
    set(key, value) { state[key] = value; return value; },
    update(values = {}) {
      Object.keys(values).forEach((key) => {
        state[key] = values[key];
      });
      return state;
    },
  });

  CAD.Logger = Object.freeze({
    log(...args) { if (config.debug) console.log('[CAD]', ...args); },
    warn(...args) { if (config.debug) console.warn('[CAD]', ...args); },
    error(...args) { console.error('[CAD]', ...args); },
  });

  /**
   * Display-only helpers. Never mutate Bookly / API payloads.
   */
  CAD.utils = Object.freeze({
    /**
     * Parse a Bookly/MySQL wall-clock datetime as a local Date (no TZ conversion).
     * Accepts `Y-m-d H:i:s` or `Y-m-dTH:i:s` (offset / Z are ignored if present).
     * @param {unknown} sql
     * @returns {Date}
     */
    parseBooklyLocal(sql) {
      const normalized = String(sql ?? '')
        .trim()
        .replace('T', ' ')
        .replace(/([Zz]|[+-]\d{2}:?\d{2})$/, '')
        .trim();
      const [date, time = '00:00:00'] = normalized.split(/\s+/);
      if (!date || !time) {
        return new Date(NaN);
      }
      const [y, m, d] = date.split('-').map(Number);
      const [h, min, s] = time.split(':').map(Number);
      if (![y, m, d, h, min].every((n) => Number.isFinite(n))) {
        return new Date(NaN);
      }
      return new Date(y, m - 1, d, h, min, Number.isFinite(s) ? s : 0);
    },

    /**
     * Format a phone for UI display (North American when recognizable).
     * @param {unknown} phone
     * @returns {string}
     */
    formatPhone(phone) {
      const raw = String(phone ?? '').trim();
      if (!raw) return '';

      const cleaned = raw.replace(/[\s\-().]/g, '');
      if (/^\d{10}$/.test(cleaned)) {
        return `(${cleaned.slice(0, 3)}) ${cleaned.slice(3, 6)}-${cleaned.slice(6)}`;
      }
      if (/^1\d{10}$/.test(cleaned)) {
        return `1 (${cleaned.slice(1, 4)}) ${cleaned.slice(4, 7)}-${cleaned.slice(7)}`;
      }

      return raw;
    },
  });

  CAD.init = function (options = {}) {
    CAD.Config.merge(options);
    return CAD;
  };

  global.CAD = CAD;
})(typeof window !== 'undefined' ? window : globalThis);
