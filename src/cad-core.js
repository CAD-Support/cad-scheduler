/**
 * CAD Scheduler — Core
 * Configuration, state, and Bookly API bridge.
 */
(function (global) {
  'use strict';

  const CAD = global.CAD || {};

  CAD.config = {
    ajaxUrl: '',
    nonce: '',
    tables: [],
    timezone: 'America/New_York',
  };

  CAD.state = {
    appointments: [],
    staff: [],
    loading: false,
    error: null,
  };

  CAD.api = {
    async request(action, data = {}) {
      const body = new FormData();
      body.append('action', action);
      body.append('nonce', CAD.config.nonce);
      Object.entries(data).forEach(([key, value]) => body.append(key, value));

      const response = await fetch(CAD.config.ajaxUrl, {
        method: 'POST',
        body,
        credentials: 'same-origin',
      });

      if (!response.ok) {
        throw new Error(`Request failed: ${response.status}`);
      }

      return response.json();
    },

    fetchSchedule(date) {
      return CAD.api.request('cad_get_schedule', { date });
    },
  };

  CAD.init = function (options = {}) {
    Object.assign(CAD.config, options);
    return CAD;
  };

  global.CAD = CAD;
})(typeof window !== 'undefined' ? window : globalThis);
