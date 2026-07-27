/**
 * Studio reservation renderer (appointment.type === 'studio').
 * Fallback for unknown appointment types.
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
      body.appendChild(H.el('div', 'cad-popover__eyebrow', 'Studio Reservation'));
      H.appendIdentity(body, appointment);
      if (appointment.service) {
        body.appendChild(H.el('div', 'cad-popover__service', appointment.service));
      }
      H.appendDivider(body);
      body.appendChild(H.el('div', 'cad-popover__line', H.paintersLabel(appointment)));
      H.appendDivider(body);
      H.appendField(body, 'Status', H.statusLine(appointment));
      return {
        title: 'Studio Reservation',
        body,
      };
    },
  };

  CAD.ReservationRenderer = ReservationRenderer;
  CAD.Renderers.register('studio', ReservationRenderer);
})(typeof window !== 'undefined' ? window : globalThis);
