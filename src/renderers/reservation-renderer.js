/**
 * Studio reservation renderer — sectioned popover body (Sprint 2.6).
 * @module renderers/reservation-renderer
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD?.Renderers) {
    throw new Error('renderers/registry.js must load before reservation-renderer.js');
  }
  const H = CAD.RendererHelpers;
  if (!H) throw new Error('renderers/helpers.js must load before reservation-renderer.js');

  const ReservationRenderer = {
    /**
     * @param {Record<string, unknown>} appointment
     * @returns {{ title: string, body: DocumentFragment }}
     */
    render(appointment) {
      const body = document.createDocumentFragment();

      H.appendBookedBy(body, appointment);

      H.appendSection(body, 'Reservation Details', '🪑', (section) => {
        let any = false;
        if (appointment.service) {
          any = H.appendField(section, 'Service', appointment.service, '🖌') || any;
        }
        any =
          H.appendField(
            section,
            'Time',
            H.formatTimeRange(appointment),
            '🕒'
          ) || any;
        any = H.appendAttendance(section, appointment) || any;
        return any;
      });

      return {
        title: 'Studio Reservation',
        body,
      };
    },
  };

  CAD.ReservationRenderer = ReservationRenderer;
  CAD.Renderers.register('studio', ReservationRenderer);
})(typeof window !== 'undefined' ? window : globalThis);
