/**
 * CAD Scheduler — Calendar
 * Multi-table time-grid schedule.
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;

  if (!CAD) {
    throw new Error('cad-core.js must be loaded before cad-calendar.js');
  }

  /** @type {readonly string[]} */
  const KNOWN_STATUSES = [
    'approved',
    'pending',
    'cancelled',
    'rejected',
    'waitlisted',
    'done',
  ];

  /**
   * @returns {{ dayStart: string, dayEnd: string, slotMinutes: number, hourHeight: number }}
   */
  function getGridConfig() {
    return {
      dayStart: String(CAD.Config.get('dayStart') ?? '08:00'),
      dayEnd: String(CAD.Config.get('dayEnd') ?? '20:00'),
      slotMinutes: Number(CAD.Config.get('slotMinutes') ?? 30),
      hourHeight: Number(CAD.Config.get('hourHeight') ?? 64),
    };
  }

  /**
   * @param {string} value HH:MM
   * @returns {number}
   */
  function parseClockToMinutes(value) {
    const match = /^(\d{1,2}):(\d{2})$/.exec(value);

    if (!match) {
      return 0;
    }

    return Number(match[1]) * 60 + Number(match[2]);
  }

  /**
   * @param {number} minutes
   * @returns {string}
   */
  function formatMinutesLabel(minutes) {
    const hours24 = Math.floor(minutes / 60);
    const mins = minutes % 60;
    const date = new Date();
    date.setHours(hours24, mins, 0, 0);

    return date.toLocaleTimeString([], {
      hour: 'numeric',
      minute: '2-digit',
    });
  }

  /**
   * @param {{ dayStart: string, dayEnd: string, slotMinutes: number, hourHeight: number }} config
   */
  function buildGridMetrics(config) {
    const startMinutes = parseClockToMinutes(config.dayStart);
    const endMinutes = parseClockToMinutes(config.dayEnd);
    const rangeMinutes = Math.max(endMinutes - startMinutes, config.slotMinutes);
    const slotCount = Math.ceil(rangeMinutes / config.slotMinutes);
    const totalHours = rangeMinutes / 60;
    const gridHeightPx = Math.round(totalHours * config.hourHeight);
    const slotLabels = [];

    for (let i = 0; i < slotCount; i += 1) {
      const minute = startMinutes + i * config.slotMinutes;
      slotLabels.push(minute % 60 === 0 ? formatMinutesLabel(minute) : '');
    }

    return {
      startMinutes,
      endMinutes,
      rangeMinutes,
      slotCount,
      gridHeightPx,
      slotLabels,
    };
  }

  /**
   * @param {Date} date
   * @returns {number}
   */
  function dateToMinutes(date) {
    return date.getHours() * 60 + date.getMinutes();
  }

  /**
   * @param {unknown} appointment
   * @returns {string|null}
   */
  function validateAppointment(appointment) {
    if (!appointment || typeof appointment !== 'object') {
      return 'Appointment is not an object';
    }

    const appt = /** @type {Record<string, unknown>} */ (appointment);

    if (!appt.id || !appt.tableId) {
      return 'Missing id or tableId';
    }

    const start = new Date(String(appt.start ?? ''));
    const end = new Date(String(appt.end ?? ''));

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
      return 'Invalid start or end datetime';
    }

    if (end.getTime() <= start.getTime()) {
      return 'End time must be after start time';
    }

    return null;
  }

  /**
   * @param {number} relativeStartMin Minutes from grid start.
   * @param {number} relativeEndMin Minutes from grid start.
   * @param {number} rangeMinutes Total visible range in minutes.
   * @param {number} gridHeightPx Total grid height in pixels.
   * @returns {{ top: string, height: string, minHeight: string }}
   */
  function computeLayout(relativeStartMin, relativeEndMin, rangeMinutes, gridHeightPx) {
    const pxPerMinute = gridHeightPx / rangeMinutes;
    const topPx = relativeStartMin * pxPerMinute;
    const durationPx = (relativeEndMin - relativeStartMin) * pxPerMinute;
    const minHeightPx = 32;

    return {
      top: `${topPx}px`,
      height: `${Math.max(durationPx, minHeightPx)}px`,
      minHeight: `${minHeightPx}px`,
    };
  }

  /**
   * @param {Date} start
   * @param {Date} end
   * @param {{ startMinutes: number, endMinutes: number, rangeMinutes: number }} metrics
   * @returns {{ start: number, end: number }}
   */
  function clipToGrid(start, end, metrics) {
    const startMin = dateToMinutes(start);
    const endMin = dateToMinutes(end);
    let relativeStart = startMin - metrics.startMinutes;
    let relativeEnd = endMin - metrics.startMinutes;

    relativeStart = Math.max(0, relativeStart);
    relativeEnd = Math.min(metrics.rangeMinutes, relativeEnd);
    relativeEnd = Math.max(relativeStart + 1, relativeEnd);

    return { start: relativeStart, end: relativeEnd };
  }

  CAD.calendar = {
    container: null,

    /**
     * @param {HTMLElement|null} container
     */
    render(container) {
      this.container = container;

      if (!this.container) {
        return;
      }

      const tables = CAD.Config.get('tables') ?? [];
      const appointments = Array.isArray(CAD.State.get('appointments'))
        ? CAD.State.get('appointments')
        : [];
      const config = getGridConfig();
      const metrics = buildGridMetrics(config);
      const renderWarnings = [];
      const validAppointments = [];

      appointments.forEach((appointment) => {
        const reason = validateAppointment(appointment);

        if (reason) {
          renderWarnings.push({
            id: appointment?.id ?? 'unknown',
            reason,
          });
          CAD.Logger.warn('Skipped appointment during render:', appointment, reason);
          return;
        }

        validAppointments.push(appointment);
      });

      CAD.State.set('renderWarnings', renderWarnings);

      this.container.innerHTML = '';
      this.container.className = 'cad-calendar';

      if (tables.length === 0) {
        this.container.appendChild(
          CAD.components.emptyState('No tables configured. Add Bookly staff to display the schedule.')
        );
        return;
      }

      const scroll = document.createElement('div');
      scroll.className = 'cad-calendar__scroll';
      scroll.tabIndex = 0;
      scroll.setAttribute('role', 'region');
      scroll.setAttribute('aria-label', 'Studio schedule grid');

      const grid = document.createElement('div');
      grid.className = 'cad-calendar__grid';
      grid.setAttribute('role', 'grid');
      grid.setAttribute('aria-readonly', 'true');
      grid.style.setProperty('--cad-grid-height', `${metrics.gridHeightPx}px`);
      grid.style.setProperty('--cad-hour-height', `${config.hourHeight}px`);
      grid.style.setProperty('--cad-slot-minutes', String(config.slotMinutes));

      const dateLabel = CAD.State.get('date')
        ? new Date(String(CAD.State.get('date'))).toLocaleDateString([], {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
          })
        : 'Schedule';

      grid.appendChild(
        CAD.components.timeAxis(dateLabel, metrics.slotLabels, metrics.gridHeightPx)
      );

      const tableIds = new Set(tables.map((table) => String(table.id)));

      tables.forEach((table) => {
        const { column, appointmentsLayer } = CAD.components.tableColumn(
          table,
          metrics.gridHeightPx,
          metrics.slotCount
        );

        validAppointments
          .filter((appt) => String(appt.tableId) === String(table.id))
          .forEach((appt) => {
            const start = new Date(appt.start);
            const end = new Date(appt.end);
            const clipped = clipToGrid(start, end, metrics);

            if (clipped.end <= clipped.start) {
              return;
            }

            const layout = computeLayout(
              clipped.start,
              clipped.end,
              metrics.rangeMinutes,
              metrics.gridHeightPx
            );

            const block = CAD.components.appointmentBlock(appt, layout);

            if (!KNOWN_STATUSES.includes(String(appt.status))) {
              block.classList.add('cad-appointment--unknown');
            }

            appointmentsLayer.appendChild(block);
          });

        grid.appendChild(column);
      });

      const orphanCount = validAppointments.filter(
        (appt) => !tableIds.has(String(appt.tableId))
      ).length;

      if (orphanCount > 0) {
        renderWarnings.push({
          id: 'orphans',
          reason: `${orphanCount} appointment(s) did not match a configured table`,
        });
        CAD.State.set('renderWarnings', renderWarnings);
      }

      scroll.appendChild(grid);
      this.container.appendChild(scroll);

      CAD.editor?.bind(this.container);
      CAD.editor?.restoreSelection?.();
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
