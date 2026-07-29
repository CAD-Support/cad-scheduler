/**
 * Staff schedule helpers — visual guidance + outside-hours warning only.
 * Does not change Bookly save / checkTime behaviour.
 * @module cad-schedule
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-schedule.js');

  /**
   * @param {string} value HH:MM or HH:MM:SS
   * @returns {number}
   */
  function parseClock(value) {
    const match = /^(\d{1,2}):(\d{2})(?::\d{2})?$/.exec(String(value || '').trim());
    if (!match) return NaN;
    return Number(match[1]) * 60 + Number(match[2]);
  }

  /**
   * @param {unknown} value Bookly wall-clock datetime
   * @returns {number} minutes from midnight
   */
  function minutesFromDatetime(value) {
    if (CAD.utils?.parseBooklyLocal) {
      const d = CAD.utils.parseBooklyLocal(value);
      if (!Number.isNaN(d.getTime())) {
        return d.getHours() * 60 + d.getMinutes();
      }
    }
    const raw = String(value || '');
    const m = raw.match(/(\d{2}):(\d{2})(?::\d{2})?/);
    if (!m) return NaN;
    return Number(m[1]) * 60 + Number(m[2]);
  }

  /**
   * Open intervals for a staff/table id from the latest schedule payload.
   * @param {string|number} staffId
   * @returns {Array<{start: string, end: string}>|null} null = unknown (no overlay/warn)
   */
  function intervalsForStaff(staffId) {
    const map = CAD.State.get('staffSchedules');
    if (!map || typeof map !== 'object') return null;
    const key = String(staffId);
    if (!Object.prototype.hasOwnProperty.call(map, key)) return null;
    const list = map[key];
    return Array.isArray(list) ? list : [];
  }

  /**
   * True when [start, end) is not fully inside the staff open intervals.
   * Missing schedule data → false (no warning).
   * Empty intervals (day off) → true if the reservation has any duration.
   *
   * @param {string|number} staffId
   * @param {string} startDatetime
   * @param {string} endDatetime
   * @returns {boolean}
   */
  function isOutsideStaffHours(staffId, startDatetime, endDatetime) {
    const intervals = intervalsForStaff(staffId);
    if (intervals == null) return false;

    const startMin = minutesFromDatetime(startDatetime);
    const endMin = minutesFromDatetime(endDatetime);
    if (!Number.isFinite(startMin) || !Number.isFinite(endMin) || endMin <= startMin) {
      return false;
    }

    if (intervals.length === 0) {
      return true;
    }

    const open = intervals
      .map((iv) => ({
        start: parseClock(iv.start),
        end: parseClock(iv.end),
      }))
      .filter((iv) => Number.isFinite(iv.start) && Number.isFinite(iv.end) && iv.end > iv.start);

    if (!open.length) return true;

    return !open.some((iv) => startMin >= iv.start && endMin <= iv.end);
  }

  /**
   * Prompt when outside schedule. Returns true to proceed, false to abort.
   * @param {string|number} staffId
   * @param {string} startDatetime
   * @param {string} endDatetime
   * @returns {Promise<boolean>}
   */
  async function confirmIfOutside(staffId, startDatetime, endDatetime) {
    if (!isOutsideStaffHours(staffId, startDatetime, endDatetime)) {
      return true;
    }
    if (typeof CAD.confirm !== 'function') {
      return true;
    }
    return CAD.confirm({
      title: 'Outside schedule',
      message:
        "This reservation falls outside this table's normal operating schedule.",
      confirmLabel: 'Continue',
      cancelLabel: 'Cancel',
    });
  }

  CAD.schedule = Object.freeze({
    intervalsForStaff,
    isOutsideStaffHours,
    confirmIfOutside,
    parseClock,
  });
})(typeof window !== 'undefined' ? window : globalThis);
