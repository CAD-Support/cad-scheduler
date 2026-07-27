/**
 * Event renderer — sectioned popover body (Sprint 2.6).
 * @module renderers/event-renderer
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD?.Renderers) {
    throw new Error('renderers/registry.js must load before event-renderer.js');
  }
  const H = CAD.RendererHelpers;
  if (!H) throw new Error('renderers/helpers.js must load before event-renderer.js');

  const EventRenderer = {
    /**
     * @param {Record<string, unknown>} appointment
     * @returns {{ title: string, body: DocumentFragment }}
     */
    render(appointment) {
      const body = document.createDocumentFragment();

      H.appendBookedBy(body, appointment);

      H.appendSection(body, 'Event Details', '📅', (section) => {
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
        any = H.appendAttendance(section, appointment, { showTotalAttending: true }) || any;
        return any;
      });

      return {
        title: appointment.service || 'Event',
        body,
      };
    },
  };

  CAD.EventRenderer = EventRenderer;
  CAD.Renderers.register('event', EventRenderer);
})(typeof window !== 'undefined' ? window : globalThis);
