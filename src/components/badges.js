/**
 * Shared badge UI (Sprint 2.6) — status + painter chips.
 * Presentation only; uses normalized appointment fields.
 * @module components/badges
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before components/badges.js');

  const STATUS_META = Object.freeze({
    approved: { key: 'approved', icon: '🟢', label: 'Approved' },
    confirmed: { key: 'approved', icon: '🟢', label: 'Approved' },
    'deposit-paid': { key: 'deposit-paid', icon: '💰', label: 'Deposit Paid' },
    depositpaid: { key: 'deposit-paid', icon: '💰', label: 'Deposit Paid' },
    arrived: { key: 'arrived', icon: '✅', label: 'Arrived' },
    paid: { key: 'paid', icon: '💲', label: 'Paid' },
    'no-show': { key: 'no-show', icon: '❌', label: 'No Show' },
    noshow: { key: 'no-show', icon: '❌', label: 'No Show' },
  });

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null && text !== '') node.textContent = text;
    return node;
  }

  function normalizeSlug(slug) {
    return String(slug || '')
      .trim()
      .toLowerCase()
      .replace(/[\s_]+/g, '-');
  }

  /**
   * @param {Record<string, unknown>|string} appointmentOrStatus
   * @returns {{ key: string, icon: string, label: string }}
   */
  function resolveStatus(appointmentOrStatus) {
    const slug =
      typeof appointmentOrStatus === 'string'
        ? normalizeSlug(appointmentOrStatus)
        : normalizeSlug(appointmentOrStatus?.status);
    return (
      STATUS_META[slug] || {
        key: 'approved',
        icon: '🟢',
        label: 'Approved',
      }
    );
  }

  function paintersCount(appointment) {
    const n = Number(appointment?.painters);
    return Number.isFinite(n) && n > 0 ? Math.floor(n) : 1;
  }

  /**
   * @param {Record<string, unknown>} appointment
   * @returns {HTMLElement}
   */
  function paintersBadge(appointment) {
    const count = paintersCount(appointment);
    const chip = el(
      'span',
      'cad-badge cad-badge--painters',
      `👥 ${count}`
    );
    chip.setAttribute(
      'aria-label',
      count === 1 ? '1 painter' : `${count} painters`
    );
    return chip;
  }

  /**
   * High-contrast status badge — equal height/padding/radius for every status.
   * @param {Record<string, unknown>|string} appointmentOrStatus
   * @returns {HTMLElement}
   */
  function statusBadge(appointmentOrStatus) {
    const meta = resolveStatus(appointmentOrStatus);
    const chip = el(
      'span',
      `cad-badge cad-badge--status cad-badge--${meta.key}`,
      `${meta.icon} ${meta.label}`
    );
    chip.setAttribute('aria-label', meta.label);
    return chip;
  }

  /**
   * Painter (secondary) + status (primary) on one row.
   * @param {Record<string, unknown>} appointment
   * @returns {HTMLElement}
   */
  function badgeRow(appointment) {
    const row = el('span', 'cad-badge-row');
    row.append(paintersBadge(appointment), statusBadge(appointment));
    return row;
  }

  CAD.Badges = Object.freeze({
    STATUS_META,
    resolveStatus,
    paintersCount,
    paintersBadge,
    statusBadge,
    badgeRow,
  });
})(typeof window !== 'undefined' ? window : globalThis);
