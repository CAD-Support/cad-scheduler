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

  /** @returns {string} Trimmed phone, or empty string when missing */
  function phoneValue(appointment) {
    return String(appointment.phone ?? '').trim();
  }

  /** @returns {string} Display line e.g. "☎ (519) 267-9080", or "" when empty */
  function phoneLabel(appointment) {
    const phone = phoneValue(appointment);
    if (!phone) return '';
    const formatted = CAD.utils?.formatPhone ? CAD.utils.formatPhone(phone) : phone;
    return `☎ ${formatted}`;
  }

  /**
   * Read-only operational status badges from Bookly custom status slug
   * (same mechanism for Arrived, Paid, and No Show).
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
    } else if (slug === 'paid') {
      badges.push({ key: 'paid', icon: '💲', label: 'Paid' });
    } else if (slug === 'deposit-paid' || slug === 'depositpaid') {
      badges.push({ key: 'deposit-paid', icon: '💰', label: 'Deposit Paid' });
    } else if (slug === 'no-show' || slug === 'noshow') {
      badges.push({ key: 'no-show', icon: '❌', label: 'No Show' });
    }

    return badges;
  }

  /** Compact status line for tooltips (no field labels). */
  function tipStatusLine(appointment) {
    const badges = statusBadges(appointment);
    if (badges.length) {
      return badges.map((b) => `${b.icon} ${b.label}`).join(' · ');
    }
    const slug = String(appointment.status || '').trim().toLowerCase();
    if (!slug || slug === 'approved' || slug === 'confirmed') {
      return '✓ Confirmed';
    }
    return `✓ ${slug.replace(/[-_]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())}`;
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
    // Inline locks — WordPress/Elementor themes often restyle [role=tooltip].
    tipEl.style.setProperty('opacity', '1', 'important');
    tipEl.style.setProperty('background', '#ffffff', 'important');
    tipEl.style.setProperty('background-color', '#ffffff', 'important');
    tipEl.style.setProperty('background-image', 'none', 'important');
    tipEl.style.setProperty('backdrop-filter', 'none', 'important');
    tipEl.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
    tipEl.style.setProperty('filter', 'none', 'important');
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
    // Label-free stack; table omitted (columns already group by table).
    const lines = [
      {
        className: 'cad-appointment-tip__customer',
        text: appointment.customer || 'Walk-in',
      },
      {
        className: 'cad-appointment-tip__line',
        text: appointment.service || 'Service',
      },
      {
        className: 'cad-appointment-tip__line',
        text: formatTimeRange(appointment),
      },
      {
        className: 'cad-appointment-tip__line',
        text: paintersLabel(appointment),
      },
      {
        className: 'cad-appointment-tip__line',
        text: tipStatusLine(appointment),
      },
    ];
    const phone = phoneLabel(appointment);
    if (phone) {
      lines.push({ className: 'cad-appointment-tip__line', text: phone });
    }
    lines.forEach(({ className, text }) => {
      tip.appendChild(el('div', className, text));
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
   * Append status for large/xl: badges when applicable, otherwise ✓ Confirmed.
   * @param {DocumentFragment} fragment
   * @param {Record<string, unknown>} appointment
   */
  function appendStatus(fragment, appointment) {
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
      return;
    }
    fragment.appendChild(
      el('span', 'cad-appointment__status', tipStatusLine(appointment))
    );
  }

  /**
   * Hierarchy by density (table never rendered):
   * compact  → time, customer
   * standard → + service
   * large    → + painters, status
   * xl       → + phone (when present)
   *
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

    if (density === 'compact') {
      return fragment;
    }

    fragment.appendChild(
      el('span', 'cad-appointment__service', appointment.service || 'Service')
    );

    if (density === 'standard') {
      return fragment;
    }

    // large + xl
    fragment.appendChild(
      el('span', 'cad-appointment__painters', paintersLabel(appointment))
    );
    appendStatus(fragment, appointment);

    if (density === 'xl') {
      const phone = phoneLabel(appointment);
      if (phone) {
        fragment.appendChild(el('span', 'cad-appointment__phone', phone));
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
