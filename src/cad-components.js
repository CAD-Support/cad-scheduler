/**
 * CAD Scheduler v2 — Components
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-components.js');

  function formatTime(iso) {
    const d = new Date(iso);
    return Number.isNaN(d.getTime())
      ? iso
      : d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
  }

  CAD.components = {
    appointmentBlock(appointment, layout) {
      const validationMode = CAD.Config.get('validationMode');
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = `cad-appointment cad-appointment--${appointment.status || 'default'}`;
      if (validationMode) {
        btn.classList.add('cad-appointment--validation');
      }
      btn.dataset.id = appointment.id;
      btn.dataset.tableId = appointment.tableId;
      btn.style.top = layout.top;
      btn.style.height = layout.height;
      btn.setAttribute('aria-label', `${appointment.customer || 'Walk-in'}, ${appointment.service || 'Service'}`);

      const time = document.createElement('span');
      time.className = 'cad-appointment__time';
      time.textContent = `${formatTime(appointment.start)} – ${formatTime(appointment.end)}`;

      const customer = document.createElement('span');
      customer.className = 'cad-appointment__customer';
      customer.textContent = appointment.customer || 'Walk-in';

      const service = document.createElement('span');
      service.className = 'cad-appointment__service';
      service.textContent = appointment.service || 'Service';

      btn.append(time, customer, service);

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
