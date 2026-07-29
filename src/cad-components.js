/**
 * CAD Scheduler v2 — Components
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-components.js');

  function availableHeightFromLayout(layout) {
    const raw = layout?.height;
    if (typeof raw === 'number' && Number.isFinite(raw)) return raw;
    const parsed = parseFloat(String(raw ?? ''), 10);
    return Number.isFinite(parsed) ? parsed : 0;
  }

  /**
   * @param {unknown} color
   * @returns {string|null} #RRGGBB or null
   */
  function normalizeBooklyColor(color) {
    if (color == null || color === '') return null;
    let raw = String(color).trim();
    if (!raw) return null;
    if (raw[0] !== '#') raw = `#${raw}`;
    const short = raw.match(/^#([0-9A-Fa-f]{3})$/);
    if (short) {
      const h = short[1];
      return `#${h[0]}${h[0]}${h[1]}${h[1]}${h[2]}${h[2]}`.toUpperCase();
    }
    if (/^#[0-9A-Fa-f]{6}$/.test(raw)) return raw.toUpperCase();
    return null;
  }

  /**
   * Light wash derived from Bookly accent (keeps card readable).
   * @param {string} hex #RRGGBB
   * @returns {string}
   */
  function washFromHex(hex) {
    const m = hex.match(/^#([0-9A-Fa-f]{2})([0-9A-Fa-f]{2})([0-9A-Fa-f]{2})$/);
    if (!m) return '#e8e8e8';
    const r = Math.round(parseInt(m[1], 16) * 0.22 + 255 * 0.78);
    const g = Math.round(parseInt(m[2], 16) * 0.22 + 255 * 0.78);
    const b = Math.round(parseInt(m[3], 16) * 0.22 + 255 * 0.78);
    return `rgb(${r}, ${g}, ${b})`;
  }

  CAD.components = {
    /**
     * Appointment card shell. Layout supplies pixel height; card content
     * comes from CAD.cardRenderer (calendar does not decide card internals).
     * @param {Record<string, unknown>} appointment
     * @param {{ top: string, height: string }} layout
     * @returns {HTMLButtonElement}
     */
    appointmentBlock(appointment, layout) {
      const validationMode = CAD.Config.get('validationMode');
      const availableHeight = availableHeightFromLayout(layout);
      const density = CAD.cardRenderer
        ? CAD.cardRenderer.densityForHeight(availableHeight)
        : 'standard';

      const type = String(appointment.type || 'studio')
        .toLowerCase()
        .replace(/[^a-z0-9-]/g, '');
      const typeClass =
        type === 'birthday' || type === 'event' ? type : 'studio';

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = [
        'cad-appointment',
        `cad-appointment--${appointment.status || 'default'}`,
        `cad-appointment--${density}`,
        `cad-appointment--type-${typeClass}`,
      ].join(' ');
      if (validationMode) {
        btn.classList.add('cad-appointment--validation');
      }
      btn.dataset.id = appointment.id;
      btn.dataset.tableId = appointment.tableId;
      btn.dataset.type = typeClass;
      btn.dataset.density = density;
      btn.style.top = layout.top;
      btn.style.height = layout.height;

      const booklyColor = normalizeBooklyColor(appointment.color);
      if (booklyColor) {
        btn.style.setProperty('--cad-type-accent', booklyColor);
        btn.style.setProperty('--cad-type-wash', washFromHex(booklyColor));
        btn.dataset.booklyColor = booklyColor;
      }

      const childName =
        typeClass === 'birthday' &&
        appointment.birthday &&
        typeof appointment.birthday === 'object'
          ? String(appointment.birthday.childName || '').trim()
          : '';
      const primary =
        childName || appointment.customer || 'Walk-in';
      btn.setAttribute(
        'aria-label',
        `${primary}, ${appointment.service || 'Service'}`
      );

      if (CAD.cardRenderer) {
        btn.append(CAD.cardRenderer.render(appointment, availableHeight));
        CAD.cardRenderer.bindTooltip(btn, appointment);
      } else {
        // Fallback when card-renderer is not loaded (older fixtures / partial enqueue).
        const customer = document.createElement('span');
        customer.className = 'cad-appointment__customer';
        customer.textContent = appointment.customer || 'Walk-in';
        const service = document.createElement('span');
        service.className = 'cad-appointment__service';
        service.textContent = appointment.service || 'Service';
        btn.append(customer, service);
      }

      if (validationMode && appointment.id != null && appointment.id !== '') {
        const idLabel = document.createElement('span');
        idLabel.className = 'cad-appointment__id';
        idLabel.textContent = `#${appointment.id}`;
        idLabel.setAttribute('aria-hidden', 'true');
        btn.append(idLabel);
      }

      return btn;
    },

    emptyState(message) {
      const el = document.createElement('p');
      el.className = 'cad-matrix__empty';
      el.textContent = message;
      return el;
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
