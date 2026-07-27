/**
 * Renderer registry keyed by appointment.type.
 *
 *   registry = {
 *     studio:   ReservationRenderer,
 *     birthday: BirthdayRenderer,
 *     event:    EventRenderer,
 *   }
 *
 * Unknown types fall back to studio (ReservationRenderer).
 * Popover looks up via CAD.Renderers.get(type) — no if/switch on type.
 *
 * @module renderers/registry
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before renderers/registry.js');

  /** @type {Record<string, { render: Function }>} */
  const registry = Object.create(null);

  function emptyBody() {
    return document.createDocumentFragment();
  }

  /**
   * @param {unknown} renderer
   * @returns {{ render: Function }|null}
   */
  function asRenderer(renderer) {
    if (!renderer) return null;
    if (typeof renderer.render === 'function') return renderer;
    if (typeof renderer === 'function') return { render: renderer };
    return null;
  }

  /**
   * @param {unknown} output
   * @param {Record<string, unknown>} appointment
   * @returns {{ title: string, body: DocumentFragment }}
   */
  function normalizeOutput(output, appointment) {
    if (output && typeof output === 'object' && output.body) {
      return {
        title: String(output.title || appointment.service || 'Appointment'),
        body: output.body,
      };
    }
    if (output instanceof DocumentFragment || (output && output.nodeType === 11)) {
      return {
        title: String(appointment.service || 'Appointment'),
        body: output,
      };
    }
    return {
      title: String(appointment.service || 'Appointment'),
      body: emptyBody(),
    };
  }

  CAD.Renderers = {
    /** Live map: appointment.type → renderer object. */
    get map() {
      return registry;
    },

    /**
     * Look up renderer by appointment.type.
     * Unknown / missing types → studio (ReservationRenderer).
     * @param {string} [type]
     * @returns {{ render: Function }|null}
     */
    get(type) {
      const key = String(type || 'studio').toLowerCase();
      return registry[key] || registry.studio || null;
    },

    /**
     * @param {Record<string, unknown>} appointment Normalized CAD appointment
     * @returns {{ title: string, body: DocumentFragment }}
     */
    render(appointment) {
      const appt = appointment || {};
      const renderer = this.get(appt.type);
      if (!renderer) {
        return normalizeOutput(null, appt);
      }
      return normalizeOutput(renderer.render(appt), appt);
    },

    /**
     * Register or replace a type renderer.
     * @param {string} type
     * @param {{ render: Function }|Function} renderer
     */
    register(type, renderer) {
      const normalized = asRenderer(renderer);
      if (!type || !normalized) return;
      registry[String(type).toLowerCase()] = normalized;
    },

    /** @returns {string[]} */
    types() {
      return Object.keys(registry);
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
