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

  /**
   * @param {string} isoDatetime
   * @returns {string}
   */
  function formatTime(isoDatetime) {
    const date = new Date(isoDatetime);

    if (Number.isNaN(date.getTime())) {
      return isoDatetime;
    }

    return date.toLocaleTimeString([], {
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  /**
   * @param {string} start
   * @param {string} end
   * @returns {string}
   */
  function formatTimeRange(start, end) {
    return `${formatTime(start)} – ${formatTime(end)}`;
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
      el.dataset.tableId = appointment.tableId;

      if (appointment.status) {
        el.dataset.status = appointment.status;
        el.classList.add(`cad-appointment--${appointment.status}`);
      }

      const customer = document.createElement('span');
      customer.className = 'cad-appointment__customer';
      customer.textContent = appointment.customer || 'Walk-in';

      const meta = document.createElement('span');
      meta.className = 'cad-appointment__meta';
      meta.textContent = `${appointment.service || 'Service'} · ${formatTimeRange(
        appointment.start,
        appointment.end
      )}`;

      el.append(customer, meta);
      return el;
    },

    tableColumn(table) {
      const el = document.createElement('div');
      el.className = 'cad-table-column';
      el.dataset.tableId = table.id;

      const header = document.createElement('header');
      header.className = 'cad-table-column__header';
      header.textContent = table.name;

      el.appendChild(header);
      return el;
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
