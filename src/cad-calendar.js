/**
 * CAD Scheduler v2 — Calendar
 * Matrix layout: tables across top, time down left, 15-minute grid.
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;

  if (!CAD) {
    throw new Error('cad-core.js must be loaded before cad-calendar.js');
  }

  /**
   * @param {string} value HH:MM
   * @returns {number}
   */
  function parseClock(value) {
    const match = /^(\d{1,2}):(\d{2})$/.exec(value);

    if (!match) {
      return 0;
    }

    return Number(match[1]) * 60 + Number(match[2]);
  }

  /**
   * @param {Date} date
   * @returns {number}
   */
  function toMinutes(date) {
    return date.getHours() * 60 + date.getMinutes();
  }

  /**
   * @param {number} minutes
   * @returns {string}
   */
  function formatLabel(minutes) {
    const label = new Date();
    label.setHours(Math.floor(minutes / 60), minutes % 60, 0, 0);

    return label.toLocaleTimeString([], {
      hour: 'numeric',
      minute: '2-digit',
    });
  }

  /**
   * @returns {{
   *   startMin: number,
   *   rangeMin: number,
   *   slotCount: number,
   *   slotHeight: number,
   *   gridHeight: number,
   *   labels: string[],
   *   slotMinutes: number
   * }}
   */
  function gridMetrics() {
    const dayStart = String(CAD.Config.get('dayStart') ?? '08:00');
    const dayEnd = String(CAD.Config.get('dayEnd') ?? '20:00');
    const slotMinutes = Number(CAD.Config.get('slotMinutes') ?? 15);
    const hourHeight = Number(CAD.Config.get('hourHeight') ?? 64);

    const startMin = parseClock(dayStart);
    const endMin = parseClock(dayEnd);
    const rangeMin = Math.max(endMin - startMin, slotMinutes);
    const slotCount = Math.ceil(rangeMin / slotMinutes);
    const slotHeight = (hourHeight * slotMinutes) / 60;
    const gridHeight = slotCount * slotHeight;
    const labels = [];

    for (let i = 0; i < slotCount; i += 1) {
      const minute = startMin + i * slotMinutes;
      labels.push(minute % 60 === 0 ? formatLabel(minute) : '');
    }

    return {
      startMin,
      rangeMin,
      slotCount,
      slotHeight,
      gridHeight,
      labels,
      slotMinutes,
    };
  }

  /**
   * @param {Date} start
   * @param {Date} end
   * @param {ReturnType<typeof gridMetrics>} metrics
   * @returns {{ top: string, height: string }}
   */
  function layoutBlock(start, end, metrics) {
    const relStart = Math.max(0, toMinutes(start) - metrics.startMin);
    const relEnd = Math.min(metrics.rangeMin, toMinutes(end) - metrics.startMin);
    const duration = Math.max(relEnd - relStart, metrics.slotMinutes / 2);
    const pxPerMinute = metrics.gridHeight / metrics.rangeMin;

    return {
      top: `${relStart * pxPerMinute}px`,
      height: `${Math.max(duration * pxPerMinute, metrics.slotHeight * 0.85)}px`,
    };
  }

  /**
   * @param {HTMLElement} container
   * @param {ReturnType<typeof gridMetrics>} metrics
   * @param {number} tableCount
   */
  function applyGridVariables(container, metrics, tableCount) {
    container.style.setProperty('--cad-table-count', String(tableCount));
    container.style.setProperty('--cad-grid-height', `${metrics.gridHeight}px`);
    container.style.setProperty('--cad-slot-height', `${metrics.slotHeight}px`);
  }

  /**
   * @param {Array<{ id: string, name: string }>} tables
   * @returns {HTMLElement}
   */
  function buildHeaderRow(tables) {
    const head = document.createElement('div');
    head.className = 'cad-matrix__head';

    const corner = document.createElement('div');
    corner.className = 'cad-matrix__corner';
    corner.textContent = CAD.State.get('date') || 'Today';
    head.appendChild(corner);

    tables.forEach((table) => {
      const label = document.createElement('div');
      label.className = 'cad-matrix__table-label';
      label.textContent = table.name;
      label.dataset.tableId = table.id;
      head.appendChild(label);
    });

    return head;
  }

  /**
   * @param {ReturnType<typeof gridMetrics>} metrics
   * @returns {HTMLElement}
   */
  function buildTimeColumn(metrics) {
    const timeCol = document.createElement('div');
    timeCol.className = 'cad-matrix__times';

    metrics.labels.forEach((label) => {
      const slot = document.createElement('div');
      slot.className = 'cad-matrix__time-slot';
      slot.textContent = label;
      timeCol.appendChild(slot);
    });

    return timeCol;
  }

  /**
   * @param {{ id: string, name: string }} table
   * @param {Array<Record<string, string>>} appointments
   * @param {ReturnType<typeof gridMetrics>} metrics
   * @returns {HTMLElement}
   */
  function buildTableLane(table, appointments, metrics) {
    const lane = document.createElement('div');
    lane.className = 'cad-matrix__lane';
    lane.dataset.tableId = table.id;

    const lines = document.createElement('div');
    lines.className = 'cad-matrix__lines';

    for (let i = 0; i < metrics.slotCount; i += 1) {
      const line = document.createElement('div');
      line.className = 'cad-matrix__line';
      lines.appendChild(line);
    }

    const blocks = document.createElement('div');
    blocks.className = 'cad-matrix__blocks';

    appointments
      .filter((appointment) => String(appointment.tableId) === String(table.id))
      .forEach((appointment) => {
        const start = new Date(appointment.start);
        const end = new Date(appointment.end);

        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
          return;
        }

        blocks.appendChild(
          CAD.components.appointmentBlock(appointment, layoutBlock(start, end, metrics))
        );
      });

    lane.append(lines, blocks);
    return lane;
  }

  /**
   * @param {Array<{ id: string, name: string }>} tables
   * @param {Array<Record<string, string>>} appointments
   * @param {ReturnType<typeof gridMetrics>} metrics
   * @returns {HTMLElement}
   */
  function buildBodyRow(tables, appointments, metrics) {
    const body = document.createElement('div');
    body.className = 'cad-matrix__body';
    body.appendChild(buildTimeColumn(metrics));

    tables.forEach((table) => {
      body.appendChild(buildTableLane(table, appointments, metrics));
    });

    return body;
  }

  CAD.calendar = {
    /**
     * @param {HTMLElement|null} container
     */
    render(container) {
      if (!container) {
        return;
      }

      const tables = CAD.Config.get('tables') ?? [];
      const stateAppointments = CAD.State.get('appointments');
      const appointments = Array.isArray(stateAppointments) ? stateAppointments : [];
      const metrics = gridMetrics();

      container.innerHTML = '';
      container.className = 'cad-matrix';

      if (tables.length === 0) {
        container.appendChild(CAD.components.emptyState('No tables configured.'));
        return;
      }

      applyGridVariables(container, metrics, tables.length);

      const scroll = document.createElement('div');
      scroll.className = 'cad-matrix__scroll';
      scroll.tabIndex = 0;
      scroll.setAttribute('role', 'region');
      scroll.setAttribute('aria-label', 'Studio schedule');
      scroll.append(buildHeaderRow(tables), buildBodyRow(tables, appointments, metrics));

      container.appendChild(scroll);
      CAD.editor?.bind(container);
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
