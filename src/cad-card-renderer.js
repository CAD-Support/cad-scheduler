/**
 * CAD Scheduler v2 — Adaptive appointment card renderer
 *
 * Density is driven by rendered pixel height, not CSS breakpoints.
 * @module cad-card-renderer
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-card-renderer.js');

  /** @type {Readonly<{ compact: number, standard: number, large: number }>} */
  const HEIGHT_THRESHOLDS = Object.freeze({
    compact: 48,
    standard: 72,
    large: 96,
  });

  let tipEl = null;
  let tipHideTimer = null;

  /**
   * @param {number} availableHeight
   * @returns {'compact'|'standard'|'large'|'xl'}
   */
  function densityForHeight(availableHeight) {
    const h = Number(availableHeight);
    if (!Number.isFinite(h) || h < HEIGHT_THRESHOLDS.compact) return 'compact';
    if (h < HEIGHT_THRESHOLDS.standard) return 'standard';
    if (h < HEIGHT_THRESHOLDS.large) return 'large';
    return 'xl';
  }

  function formatTime(iso) {
    const d = new Date(iso);
    return Number.isNaN(d.getTime())
      ? String(iso || '')
      : d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
  }

  function formatTimeRange(appointment) {
    return `${formatTime(appointment.start)} – ${formatTime(appointment.end)}`;
  }

  function paintersLabel(appointment) {
    const n = Number(appointment.painters);
    const count = Number.isFinite(n) && n > 0 ? Math.floor(n) : 1;
    return count === 1 ? '1 painter' : `${count} painters`;
  }

  function tableName(appointment) {
    const tables = CAD.Config.get('tables') || [];
    const id = String(appointment.tableId ?? '');
    const match = tables.find((t) => String(t.id) === id);
    return match?.name || (id ? `Table ${id}` : '—');
  }

  /**
   * Read-only status indicators. Confirmed/approved show no booking badge
   * unless paid (paid is independent of confirmation).
   * @param {Record<string, unknown>} appointment
   * @returns {Array<{ key: string, icon: string, label: string }>}
   */
  function statusBadges(appointment) {
    const slug = String(appointment.status || '')
      .trim()
      .toLowerCase()
      .replace(/[\s_]+/g, '-');
    const badges = [];

    if (slug === 'arrived') {
      badges.push({ key: 'arrived', icon: '🟢', label: 'Arrived' });
    } else if (slug === 'no-show' || slug === 'noshow') {
      badges.push({ key: 'no-show', icon: '❌', label: 'No Show' });
    }

    if (appointment.paid === true || appointment.paid === 1 || appointment.paid === '1') {
      badges.push({ key: 'paid', icon: '💲', label: 'Paid' });
    }

    return badges;
  }

  function statusSummary(appointment) {
    const badges = statusBadges(appointment);
    if (badges.length) {
      return badges.map((b) => `${b.icon} ${b.label}`).join(', ');
    }
    const slug = String(appointment.status || '').trim().toLowerCase();
    if (!slug || slug === 'approved' || slug === 'confirmed') {
      return 'Confirmed';
    }
    return slug.replace(/[-_]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
  }

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null && text !== '') node.textContent = text;
    return node;
  }

  function finePointerHover() {
    return (
      typeof window.matchMedia === 'function' &&
      window.matchMedia('(hover: hover) and (pointer: fine)').matches
    );
  }

  function ensureTip() {
    if (tipEl && tipEl.isConnected) return tipEl;
    tipEl = el('div', 'cad-appointment-tip');
    tipEl.setAttribute('role', 'tooltip');
    tipEl.hidden = true;
    document.body.appendChild(tipEl);
    return tipEl;
  }

  function hideTip() {
    if (tipHideTimer) {
      clearTimeout(tipHideTimer);
      tipHideTimer = null;
    }
    if (!tipEl) return;
    tipEl.hidden = true;
    tipEl.replaceChildren();
    tipEl.remove();
    tipEl = null;
  }

  function fillTip(tip, appointment) {
    tip.replaceChildren();
    const rows = [
      ['Customer', appointment.customer || 'Walk-in'],
      ['Service', appointment.service || 'Service'],
      ['Time', formatTimeRange(appointment)],
      ['Table', tableName(appointment)],
      ['Painters', paintersLabel(appointment)],
      ['Status', statusSummary(appointment)],
    ];
    rows.forEach(([label, value]) => {
      const row = el('div', 'cad-appointment-tip__row');
      row.append(
        el('span', 'cad-appointment-tip__label', label),
        el('span', 'cad-appointment-tip__value', value)
      );
      tip.appendChild(row);
    });
  }

  function positionTip(tip, anchor) {
    const rect = anchor.getBoundingClientRect();
    const margin = 8;
    tip.hidden = false;
    tip.style.left = '0px';
    tip.style.top = '0px';
    const tipRect = tip.getBoundingClientRect();
    let left = rect.left;
    let top = rect.top - tipRect.height - margin;
    if (top < margin) {
      top = rect.bottom + margin;
    }
    left = Math.min(left, window.innerWidth - tipRect.width - margin);
    left = Math.max(margin, left);
    tip.style.left = `${Math.round(left)}px`;
    tip.style.top = `${Math.round(top)}px`;
  }

  /**
   * Desktop hover tooltip. Created on demand; removed on leave
   * (no permanent chrome outside the calendar).
   * @param {HTMLElement} card
   * @param {Record<string, unknown>} appointment
   */
  function bindTooltip(card, appointment) {
    card.addEventListener('mouseenter', () => {
      if (!finePointerHover()) return;
      if (tipHideTimer) {
        clearTimeout(tipHideTimer);
        tipHideTimer = null;
      }
      const tip = ensureTip();
      fillTip(tip, appointment);
      positionTip(tip, card);
    });

    card.addEventListener('mouseleave', () => {
      tipHideTimer = setTimeout(hideTip, 80);
    });
  }

  /**
   * @param {Record<string, unknown>} appointment
   * @param {number} availableHeight Pixel height available for the card
   * @returns {DocumentFragment}
   */
  function render(appointment, availableHeight) {
    const density = densityForHeight(availableHeight);
    const fragment = document.createDocumentFragment();

    fragment.appendChild(el('span', 'cad-appointment__time', formatTimeRange(appointment)));
    fragment.appendChild(
      el('span', 'cad-appointment__customer', appointment.customer || 'Walk-in')
    );

    if (density !== 'compact') {
      fragment.appendChild(
        el('span', 'cad-appointment__service', appointment.service || 'Service')
      );
    }

    if (density === 'large' || density === 'xl') {
      fragment.appendChild(
        el('span', 'cad-appointment__painters', paintersLabel(appointment))
      );
    }

    if (density === 'xl') {
      const badges = statusBadges(appointment);
      if (badges.length) {
        const row = el('span', 'cad-appointment__badges');
        badges.forEach((badge) => {
          const chip = el(
            'span',
            `cad-appointment__badge cad-appointment__badge--${badge.key}`,
            `${badge.icon} ${badge.label}`
          );
          chip.setAttribute('aria-label', badge.label);
          row.appendChild(chip);
        });
        fragment.appendChild(row);
      }
    }

    return fragment;
  }

  CAD.cardRenderer = Object.freeze({
    HEIGHT_THRESHOLDS,
    densityForHeight,
    statusBadges,
    render,
    bindTooltip,
  });
})(typeof window !== 'undefined' ? window : globalThis);
