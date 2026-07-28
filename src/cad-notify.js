/**
 * CAD Scheduler — lightweight status notifications (success / error).
 * @module cad-notify
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-notify.js');

  let clearTimer = null;

  function statusEl() {
    return document.querySelector('#cad-scheduler .cad-scheduler__status')
      || document.querySelector('.cad-scheduler__status');
  }

  CAD.notify = {
    /**
     * @param {string} message
     * @param {'info'|'success'|'error'} [type]
     * @param {{ ttlMs?: number }} [options]
     */
    show(message, type = 'info', options = {}) {
      const el = statusEl();
      if (!el) {
        if (type === 'error') {
          // eslint-disable-next-line no-console
          console.error(message);
        }
        return;
      }

      if (clearTimer) {
        clearTimeout(clearTimer);
        clearTimer = null;
      }

      el.className = 'cad-scheduler__status';
      if (type === 'error') {
        el.classList.add('cad-scheduler__status--error');
      } else if (type === 'success') {
        el.classList.add('cad-scheduler__status--success');
      }
      el.textContent = String(message || '');

      const ttl = options.ttlMs ?? (type === 'error' ? 8000 : 4000);
      if (ttl > 0) {
        clearTimer = setTimeout(() => {
          if (el.textContent === String(message || '')) {
            el.className = 'cad-scheduler__status';
            el.textContent = '';
          }
        }, ttl);
      }
    },

    clear() {
      const el = statusEl();
      if (!el) return;
      if (clearTimer) {
        clearTimeout(clearTimer);
        clearTimer = null;
      }
      el.className = 'cad-scheduler__status';
      el.textContent = '';
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
