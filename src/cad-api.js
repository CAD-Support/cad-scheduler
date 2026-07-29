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
      let parsed;
      try {
        parsed = JSON.parse(text);
      } catch (e) {
        if (!response.ok) {
          throw new Error(`Request failed (${response.status}): ${text.slice(0, 200)}`);
        }
        throw new Error('Invalid JSON response from server');
      }

      if (!response.ok) {
        const message =
          parsed?.data?.message ||
          parsed?.message ||
          `Request failed (${response.status})`;
        const err = new Error(String(message));
        err.payload = parsed?.data ?? parsed;
        err.status = response.status;
        err.response = parsed;
        throw err;
      }

      return parsed;
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

    /**
     * Reschedule via Bookly save path (staff + start; duration preserved server-side).
     * @param {{
     *   appointmentId: string|number,
     *   staffId: string|number,
     *   start: string,
     *   end?: string,
     * }} params
     */
    updateAppointment(params) {
      const data = {
        appointment_id: params.appointmentId,
        staff_id: params.staffId,
        start: params.start,
      };
      if (params.end) {
        data.end = params.end;
      }
      return api.request('cad_update_appointment', data);
    },

    /**
     * Create a reservation via Bookly save path (Quick Add).
     * @param {{
     *   staffId: string|number,
     *   start: string,
     *   end?: string,
     *   durationMinutes?: number,
     *   customerName: string,
     *   phone?: string,
     *   email?: string,
     *   painters?: number,
     *   notes?: string,
     *   serviceId?: string|number,
     * }} params
     */
    createAppointment(params) {
      const data = {
        staff_id: params.staffId,
        start: params.start,
        customer_name: params.customerName,
        phone: params.phone || '',
        email: params.email || '',
        painters: params.painters != null ? params.painters : 1,
        duration_minutes: params.durationMinutes != null ? params.durationMinutes : 90,
        notes: params.notes || '',
      };
      if (params.end) {
        data.end = params.end;
      }
      if (params.serviceId) {
        data.service_id = params.serviceId;
      }
      return api.request('cad_create_appointment', data);
    },

    /**
     * Pipeline dump with plain-text summary.
     * Prints the report (including UI column count when the grid is present).
     */
    async debugStaffPipeline() {
      const result = await api.request('cad_debug_staff_pipeline');
      const payload = result?.data || {};
      const report = payload.report || payload;
      const uiCount = document.querySelectorAll('.cad-matrix__table-label').length;
      if (CAD.StaffPipelineReport?.finalize) {
        const { summary } = CAD.StaffPipelineReport.finalize(report, uiCount);
        CAD.StaffPipelineReport.show(summary);
        return { ...result, data: { ...payload, summary, report } };
      }
      if (payload.summary) {
        // eslint-disable-next-line no-console
        console.warn(payload.summary);
      }
      return result;
    },
  });

  CAD.API = api;
})(typeof window !== 'undefined' ? window : globalThis);
