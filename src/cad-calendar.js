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
   * @param {number} minutes
   * @returns {string} HH:MM
   */
  function formatClock(minutes) {
    const clamped = Math.max(0, Math.min(24 * 60, Math.round(minutes)));
    const h = Math.floor(clamped / 60);
    const m = clamped % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
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
   * Studio open hours for a YYYY-MM-DD date.
   * @param {string} isoDate
   * @returns {{ startMin: number, endMin: number }|null} null = closed
   */
  function openHoursForDate(isoDate) {
    const fallbackStart = String(CAD.Config.get('dayStart') ?? '08:00');
    const fallbackEnd = String(CAD.Config.get('dayEnd') ?? '20:00');
    const date = new Date(`${isoDate}T12:00:00`);
    const weekday = Number.isNaN(date.getTime()) ? new Date().getDay() : date.getDay();
    const weekly = CAD.Config.get('openHours');

    let entry = null;
    if (weekly && typeof weekly === 'object') {
      entry = weekly[weekday] ?? weekly[String(weekday)];
    }

    if (entry === null || entry === false) {
      return null; // explicitly closed
    }

    if (entry && typeof entry === 'object') {
      const start = parseClock(String(entry.start ?? fallbackStart));
      const end = parseClock(String(entry.end ?? fallbackEnd));
      if (end > start) {
        return { startMin: start, endMin: end };
      }
      return null;
    }

    // No weekly map — use global dayStart/dayEnd every day.
    const startMin = parseClock(fallbackStart);
    const endMin = parseClock(fallbackEnd);
    return endMin > startMin ? { startMin, endMin } : null;
  }

  /**
   * @param {Array<Record<string, string>>} appointments
   * @returns {{ earliest: number|null, latest: number|null }}
   */
  function appointmentBounds(appointments) {
    let earliest = null;
    let latest = null;

    appointments.forEach((appointment) => {
      const start = new Date(appointment.start);
      const end = new Date(appointment.end);
      if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        return;
      }
      const startMin = toMinutes(start);
      const endMin = toMinutes(end);
      earliest = earliest === null ? startMin : Math.min(earliest, startMin);
      latest = latest === null ? endMin : Math.max(latest, endMin);
    });

    return { earliest, latest };
  }

  /**
   * Display range: open hours ∪ appointments, snapped to slots, + one bottom slot.
   * @param {Array<Record<string, string>>} appointments
   * @param {string} isoDate
   * @param {number} slotMinutes
   * @returns {{ startMin: number, endMin: number }}
   */
  function resolveDayRange(appointments, isoDate, slotMinutes) {
    const slot = Math.max(1, slotMinutes);
    const open = openHoursForDate(isoDate);
    const { earliest, latest } = appointmentBounds(appointments);

    let startMin;
    let endMin;

    if (open) {
      startMin = open.startMin;
      endMin = open.endMin;
      if (earliest !== null) {
        startMin = Math.min(startMin, earliest);
      }
      if (latest !== null) {
        endMin = Math.max(endMin, latest);
      }
    } else if (earliest !== null && latest !== null) {
      // Closed day with appointments — show appointment span only.
      startMin = earliest;
      endMin = latest;
    } else {
      // Closed day, no appointments — fall back to configured day window for an empty shell.
      startMin = parseClock(String(CAD.Config.get('dayStart') ?? '08:00'));
      endMin = parseClock(String(CAD.Config.get('dayEnd') ?? '20:00'));
      if (endMin <= startMin) {
        startMin = 8 * 60;
        endMin = 20 * 60;
      }
    }

    startMin = Math.floor(startMin / slot) * slot;
    endMin = Math.ceil(endMin / slot) * slot;
    endMin += slot; // one slot of bottom padding so the latest block is fully visible

    if (endMin <= startMin) {
      endMin = startMin + slot;
    }

    return { startMin, endMin };
  }

  /**
   * @param {Array<Record<string, string>>} [appointments]
   * @returns {{
   *   startMin: number,
   *   endMin: number,
   *   rangeMin: number,
   *   slotCount: number,
   *   slotHeight: number,
   *   gridHeight: number,
   *   labels: string[],
   *   slotMinutes: number,
   *   dayStart: string,
   *   dayEnd: string
   * }}
   */
  function gridMetrics(appointments) {
    const slotMinutes = Number(CAD.Config.get('slotMinutes') ?? 15);
    const hourHeight = Number(CAD.Config.get('hourHeight') ?? 64);
    const isoDate = String(
      CAD.State.get('selectedDate') || CAD.Config.get('today') || '1970-01-01'
    );
    const list = Array.isArray(appointments)
      ? appointments
      : (Array.isArray(CAD.State.get('appointments')) ? CAD.State.get('appointments') : []);

    const { startMin, endMin } = resolveDayRange(list, isoDate, slotMinutes);
    const rangeMin = Math.max(endMin - startMin, slotMinutes);
    const slotCount = Math.ceil(rangeMin / slotMinutes);
    const slotHeight = (hourHeight * slotMinutes) / 60;
    const gridHeight = slotCount * slotHeight;
    const labels = [];

    for (let i = 0; i < slotCount; i += 1) {
      const minute = startMin + i * slotMinutes;
      labels.push(minute % 60 === 0 ? formatLabel(minute) : '');
    }

    CAD.State.update({
      gridStart: formatClock(startMin),
      gridEnd: formatClock(endMin),
    });

    return {
      startMin,
      endMin,
      rangeMin,
      slotCount,
      slotHeight,
      gridHeight,
      labels,
      slotMinutes,
      dayStart: formatClock(startMin),
      dayEnd: formatClock(endMin),
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
    container.style.setProperty('--cad-day-start-min', String(metrics.startMin));
  }

  /**
   * Pin column tracks with a literal repeat count (not only --cad-table-count).
   * Some WP/browser combos drop `repeat(var(--n), …)` while still painting ~7 auto tracks.
   * @param {HTMLElement} el
   * @param {number} tableCount
   */
  function applyColumnTracks(el, tableCount) {
    const n = Math.max(0, Number(tableCount) || 0);
    el.style.setProperty('--cad-table-count', String(n));
    el.style.display = 'grid';
    el.style.alignItems = 'start';
    el.style.gridTemplateColumns =
      `var(--cad-time-width) repeat(${n}, minmax(var(--cad-col-min), 1fr))`;
    el.style.minWidth =
      `max(100%, calc(var(--cad-time-width) + (${n} * var(--cad-col-min))))`;
  }

  /**
   * TEMP DEBUG Sprint 2.5.1 — remove after live column-count verified
   * @param {HTMLElement} head
   * @param {HTMLElement} body
   * @param {number} tableCount
   * @param {Array<{ id: string, name: string }>} tables
   */
  function debugColumnCounts(head, body, tableCount, tables) {
    const headerCount = head.querySelectorAll('.cad-matrix__table-label').length;
    const bodyColumnCount = body.querySelectorAll(':scope > .cad-matrix__lane').length;
    console.log(
      'Rendering columns:',
      tables.map((t) => t && t.name)
    );
    console.log('Header DOM nodes:', headerCount);
    console.log('Body column DOM nodes:', bodyColumnCount);
    console.log('TEMP DEBUG --cad-table-count:', String(tableCount));
    try {
      console.log(
        'TEMP DEBUG gridTemplateColumns:',
        getComputedStyle(head).gridTemplateColumns
      );
    } catch (e) {
      /* ignore */
    }
  }

  /**
   * Active resource columns: prefer latest schedule State; Config only as fallback.
   * @returns {Array<{ id: string, name: string }>}
   */
  function resolveTables() {
    const stateTables = CAD.State.get('tables');
    if (Array.isArray(stateTables) && stateTables.length > 0) {
      return stateTables;
    }
    const configTables = CAD.Config.get('tables');
    if (Array.isArray(configTables) && configTables.length > 0) {
      return configTables;
    }
    return Array.isArray(stateTables) ? stateTables : [];
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
    corner.textContent = CAD.State.get('selectedDate') || 'Today';
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
    /** @see resolveDayRange */
    resolveDayRange,
    /** @see gridMetrics */
    gridMetrics,

    /**
     * @param {HTMLElement|null} container
     */
    render(container) {
      if (!container) {
        return;
      }

      // Always rebuild from State after schedule load; Config only if State empty.
      const tables = resolveTables();
      const stateAppointments = CAD.State.get('appointments');
      const appointments = Array.isArray(stateAppointments) ? stateAppointments : [];
      const metrics = gridMetrics(appointments);

      // Replace grid contents only — keep host classes (e.g. cad-scheduler__calendar).
      container.innerHTML = '';
      container.classList.add('cad-matrix');

      if (tables.length === 0) {
        container.appendChild(CAD.components.emptyState('No tables configured.'));
        return;
      }

      applyGridVariables(container, metrics, tables.length);

      // Sole scrollport: contains corner, headers, times, lanes, appointments.
      const scroll = document.createElement('div');
      scroll.className = 'cad-matrix__scroll';
      scroll.tabIndex = 0;
      scroll.setAttribute('role', 'region');
      scroll.setAttribute('aria-label', 'Studio schedule');
      scroll.style.maxWidth = '100%';
      scroll.style.overflowX = 'auto';
      scroll.style.overflowY = 'auto';
      applyGridVariables(scroll, metrics, tables.length);

      const head = buildHeaderRow(tables);
      const body = buildBodyRow(tables, appointments, metrics);
      applyColumnTracks(head, tables.length);
      applyColumnTracks(body, tables.length);

      scroll.append(head, body);
      container.appendChild(scroll);
      // TEMP DEBUG Sprint 2.5.1 — remove after live column-count verified
      debugColumnCounts(head, body, tables.length, tables);
      CAD.editor?.bind(container);
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
