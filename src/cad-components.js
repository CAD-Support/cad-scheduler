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
      hour: 'numeric',
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
    /**
     * @param {string} cornerLabel
     * @param {string[]} slotLabels
     * @param {number} gridHeightPx
     * @returns {HTMLElement}
     */
    timeAxis(cornerLabel, slotLabels, gridHeightPx) {
      const axis = document.createElement('div');
      axis.className = 'cad-time-axis';
      axis.style.setProperty('--cad-grid-height', `${gridHeightPx}px`);

      const corner = document.createElement('div');
      corner.className = 'cad-time-axis__corner';
      corner.textContent = cornerLabel;

      const track = document.createElement('div');
      track.className = 'cad-time-axis__track';

      slotLabels.forEach((label) => {
        const slot = document.createElement('div');
        slot.className = 'cad-time-axis__slot';
        slot.textContent = label;
        track.appendChild(slot);
      });

      axis.append(corner, track);
      return axis;
    },

    /**
     * @param {object} appointment
     * @param {{ top: string, height: string, minHeight: string }} layout
     * @returns {HTMLElement}
     */
    appointmentBlock(appointment, layout) {
      const el = document.createElement('button');
      el.type = 'button';
      el.className = 'cad-appointment';
      el.dataset.id = appointment.id;
      el.dataset.tableId = appointment.tableId;
      el.style.top = layout.top;
      el.style.height = layout.height;
      el.style.minHeight = layout.minHeight;

      const status = appointment.status || 'default';
      el.dataset.status = status;
      el.classList.add(`cad-appointment--${status}`);

      const time = document.createElement('span');
      time.className = 'cad-appointment__time';
      time.textContent = formatTimeRange(appointment.start, appointment.end);

      const customer = document.createElement('span');
      customer.className = 'cad-appointment__customer';
      customer.textContent = appointment.customer || 'Walk-in';

      const service = document.createElement('span');
      service.className = 'cad-appointment__service';
      service.textContent = appointment.service || 'Service';

      const label = `${customer.textContent}, ${service.textContent}, ${time.textContent}`;
      el.setAttribute('aria-label', label);
      el.setAttribute('aria-selected', 'false');
      el.title = label;
      el.append(time, customer, service);
      return el;
    },

    /**
     * @param {object} table
     * @param {number} gridHeightPx
     * @param {number} slotCount
     * @returns {{ column: HTMLElement, body: HTMLElement, appointmentsLayer: HTMLElement }}
     */
    tableColumn(table, gridHeightPx, slotCount) {
      const column = document.createElement('div');
      column.className = 'cad-table-column';
      column.dataset.tableId = table.id;
      column.style.setProperty('--cad-grid-height', `${gridHeightPx}px`);

      const header = document.createElement('header');
      header.className = 'cad-table-column__header';
      header.setAttribute('role', 'columnheader');
      header.textContent = table.name;

      const body = document.createElement('div');
      body.className = 'cad-table-column__body';

      const slots = document.createElement('div');
      slots.className = 'cad-table-column__slots';
      slots.setAttribute('aria-hidden', 'true');

      for (let i = 0; i < slotCount; i += 1) {
        const line = document.createElement('div');
        line.className = 'cad-table-column__slot-line';
        slots.appendChild(line);
      }

      const appointmentsLayer = document.createElement('div');
      appointmentsLayer.className = 'cad-table-column__appointments';

      body.append(slots, appointmentsLayer);
      column.append(header, body);

      return { column, body, appointmentsLayer };
    },

    emptyState(message) {
      const el = document.createElement('div');
      el.className = 'cad-calendar__empty';
      el.textContent = message;
      return el;
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
