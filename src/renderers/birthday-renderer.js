/**
 * Birthday party renderer (appointment.type === 'birthday').
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

      body.appendChild(H.el('div', 'cad-popover__eyebrow', '🎂 Birthday Party'));
      H.appendIdentity(body, appointment);
      H.appendDivider(body);

      if (bday.childName || ageDisplay(bday.age)) {
        body.appendChild(H.el('div', 'cad-popover__field-label', 'Birthday Child'));
        if (bday.childName) {
          body.appendChild(
            H.el('div', 'cad-popover__field-value', String(bday.childName))
          );
        }
        const ageText = ageDisplay(bday.age);
        if (ageText) {
          body.appendChild(H.el('div', 'cad-popover__line', ageText));
        }
      }

      H.appendField(body, 'Package', bday.package);
      body.appendChild(H.el('div', 'cad-popover__line', H.paintersLabel(appointment)));
      H.appendDivider(body);
      H.appendField(body, 'Status', H.statusLine(appointment));

      return {
        title: 'Birthday Party',
        body,
      };
    },
  };

  CAD.BirthdayRenderer = BirthdayRenderer;
  CAD.Renderers.register('birthday', BirthdayRenderer);
})(typeof window !== 'undefined' ? window : globalThis);
