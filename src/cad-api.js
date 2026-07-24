/**
 * CAD Scheduler v2 — API
 * @module cad-api
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-api.js');

  const api = Object.freeze({
    async request(action, data = {}) {
      const body = new FormData();
      body.append('action', action);
      body.append('nonce', String(CAD.Config.get('nonce') ?? ''));
      Object.entries(data).forEach(([k, v]) => body.append(k, String(v ?? '')));

      const response = await fetch(String(CAD.Config.get('ajaxUrl') ?? ''), {
        method: 'POST',
        body,
        credentials: 'same-origin',
      });

      const text = await response.text();
      if (!response.ok) {
        throw new Error(`Request failed (${response.status}): ${text.slice(0, 200)}`);
      }

      try {
        return JSON.parse(text);
      } catch (e) {
        throw new Error('Invalid JSON response from server');
      }
    },

    getSchedule(date) {
      return api.request('cad_get_schedule', { date });
    },
  });

  CAD.API = api;
})(typeof window !== 'undefined' ? window : globalThis);
