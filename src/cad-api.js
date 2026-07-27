/**
 * CAD Scheduler v2 — API
 * @module cad-api
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-api.js');

  const api = Object.freeze({
    /**
     * @param {string} action
     * @param {Record<string, *>} [data]
     * @param {{ signal?: AbortSignal }} [options]
     */
    async request(action, data = {}, options = {}) {
      const body = new FormData();
      body.append('action', action);
      body.append('nonce', String(CAD.Config.get('nonce') ?? ''));
      Object.entries(data).forEach(([k, v]) => body.append(k, String(v ?? '')));

      const fetchOpts = {
        method: 'POST',
        body,
        credentials: 'same-origin',
      };
      if (options.signal) {
        fetchOpts.signal = options.signal;
      }

      const response = await fetch(String(CAD.Config.get('ajaxUrl') ?? ''), fetchOpts);

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

    /**
     * @param {string} date YYYY-MM-DD
     * @param {{ signal?: AbortSignal }} [options] optional AbortSignal for cancellation
     */
    getSchedule(date, options = {}) {
      return api.request('cad_get_schedule', { date }, options);
    },

    /**
     * @param {string|number} appointmentId
     * @param {string} status Bookly custom status slug
     */
    updateAppointmentStatus(appointmentId, status) {
      return api.request('cad_update_appointment_status', {
        appointment_id: appointmentId,
        status,
      });
    },
  });

  CAD.API = api;
})(typeof window !== 'undefined' ? window : globalThis);
