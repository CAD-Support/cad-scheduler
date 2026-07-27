/**
 * Birthday party renderer — sectioned popover body (Sprint 2.6).
 * @module renderers/birthday-renderer
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD?.Renderers) {
    throw new Error('renderers/registry.js must load before birthday-renderer.js');
  }
  const H = CAD.RendererHelpers;
  if (!H) throw new Error('renderers/helpers.js must load before birthday-renderer.js');

  function ageDisplay(age) {
    const raw = String(age ?? '').trim();
    if (!raw) return '';
    if (/^\d+$/.test(raw)) return `Turning ${raw}`;
    return raw;
  }

  const BirthdayRenderer = {
    /**
     * @param {Record<string, unknown>} appointment
     * @returns {{ title: string, body: DocumentFragment }}
     */
    render(appointment) {
      const body = document.createDocumentFragment();
      const bday =
        appointment.birthday && typeof appointment.birthday === 'object'
          ? appointment.birthday
          : {};

      H.appendSection(body, 'Child', '🎂', (section) => {
        let any = false;
        if (bday.childName) {
          section.appendChild(
            H.el('div', 'cad-popover__field-value cad-popover__field-value--lg', String(bday.childName))
          );
          any = true;
        }
        const ageText = ageDisplay(bday.age);
        if (ageText) {
          section.appendChild(H.el('div', 'cad-popover__line', ageText));
          any = true;
        }
        return any;
      });

      H.appendBookedBy(body, appointment);

      H.appendSection(body, 'Party Details', '🎉', (section) => {
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
        any = H.appendField(section, 'Package', bday.package, '📦') || any;
        any =
          H.appendAttendance(section, appointment, { showTotalAttending: true }) ||
          any;
        return any;
      });

      return {
        title: 'Birthday Party',
        body,
      };
    },
  };

  CAD.BirthdayRenderer = BirthdayRenderer;
  CAD.Renderers.register('birthday', BirthdayRenderer);
})(typeof window !== 'undefined' ? window : globalThis);
