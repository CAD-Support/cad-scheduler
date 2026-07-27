/**
 * Event renderer placeholder (appointment.type === 'event').
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
      body.appendChild(H.el('div', 'cad-popover__eyebrow', 'Event'));
      H.appendIdentity(body, appointment);
      if (appointment.service) {
        body.appendChild(H.el('div', 'cad-popover__service', appointment.service));
      }
      H.appendDivider(body);
      body.appendChild(H.el('div', 'cad-popover__line', H.paintersLabel(appointment)));
      H.appendDivider(body);
      H.appendField(body, 'Status', H.statusLine(appointment));
      return {
        title: appointment.service || 'Event',
        body,
      };
    },
  };

  CAD.EventRenderer = EventRenderer;
  CAD.Renderers.register('event', EventRenderer);
})(typeof window !== 'undefined' ? window : globalThis);
