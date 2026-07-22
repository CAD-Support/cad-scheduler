/**
 * CAD Scheduler — Components
 * Reusable UI building blocks.
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;

  if (!CAD) {
    throw new Error('cad-core.js must be loaded before cad-components.js');
  }

  CAD.components = {
    timeSlot(time, label) {
      const el = document.createElement('div');
      el.className = 'cad-time-slot';
      el.dataset.time = time;
      el.textContent = label ?? time;
      return el;
    },

    appointmentBlock(appointment) {
      const el = document.createElement('div');
      el.className = 'cad-appointment';
      el.dataset.id = appointment.id;
      el.innerHTML = `
        <span class="cad-appointment__title">${appointment.title ?? 'Appointment'}</span>
        <span class="cad-appointment__time">${appointment.start} – ${appointment.end}</span>
      `;
      return el;
    },

    tableColumn(table) {
      const el = document.createElement('div');
      el.className = 'cad-table-column';
      el.dataset.tableId = table.id;
      el.innerHTML = `<header class="cad-table-column__header">${table.name}</header>`;
      return el;
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
