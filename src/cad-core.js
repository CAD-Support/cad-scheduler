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

  CAD.VERSION = Object.freeze({ major: 2, minor: 4, patch: 2, build: '2026.07.27' });

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
